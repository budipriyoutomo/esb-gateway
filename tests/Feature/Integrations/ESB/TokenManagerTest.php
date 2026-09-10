<?php

namespace Tests\Feature\Integrations\ESB;

use App\Integrations\ESB\Auth\TokenManager;
use App\Integrations\ESB\Exceptions\EsbAuthenticationException;
use App\Integrations\ESB\Exceptions\EsbConfigurationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The ESB permits only one active session per credential: "A successful API
 * login will log you out of any existing ESB Core session using the same
 * credentials, vice versa."
 *
 * That single sentence is why this class exists (ADR-009). Logging in per
 * request or per job would make concurrent operations invalidate each other's
 * tokens, producing intermittent Unauthorized failures with no obvious cause --
 * a failure mode that will not show up in manual testing.
 */
class TokenManagerTest extends TestCase
{
    // Credentials now resolve through the effective connection, which may be a
    // database row rather than the environment (ADR-018), so the schema has to
    // exist even for the tests that only ever read configuration.
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('esb.environment', 'staging_int');
    }

    private function useLoginMechanism(): void
    {
        config()->set('esb.auth.mechanism', 'login');
        config()->set('esb.auth.username', 'gateway-integration');
        config()->set('esb.auth.password', 'not-a-real-password');
    }

    /**
     * @return array<string, mixed>
     */
    private function loginResponse(string $access = 'access-1', string $refresh = 'refresh-1'): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/auth/login',
            'timestamp' => '2024-05-20 13:35:29',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => [
                'username' => 'gateway-integration',
                'fullName' => 'Gateway Integration',
                'companyID' => 1,
                'companyCode' => 'QA1',
                'companyName' => 'QA 1 Company',
                'accessToken' => $access,
                'refreshToken' => $refresh,
                'flagActive' => 1,
                'logInfo' => ['logID' => 1, 'username' => 'gateway-integration', 'loginTime' => null, 'logoutTime' => null],
            ],
            'errors' => null,
        ];
    }

    private function manager(): TokenManager
    {
        return app(TokenManager::class);
    }

    /**
     * Replace Cache::lock() with a stand-in while leaving get() and put()
     * backed by the real store.
     */
    private function interceptLockWith(mixed $lock, ?callable $argumentMatcher = null): void
    {
        $proxy = Mockery::mock(Cache::store());

        $expectation = $proxy->shouldReceive('lock')->once();

        if ($argumentMatcher !== null) {
            $expectation->withArgs($argumentMatcher);
        }

        $expectation->andReturn($lock);

        Cache::swap($proxy);
    }

    #[Test]
    public function static_token_mode_never_touches_the_network(): void
    {
        Http::fake();
        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', 'a-static-api-key');

        $this->assertSame('a-static-api-key', $this->manager()->token());

        // A static key sidesteps the single-session problem entirely, which is
        // why it is the preferred mechanism.
        Http::assertNothingSent();
    }

    #[Test]
    public function static_token_mode_without_a_key_fails_as_misconfiguration(): void
    {
        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', null);

        $this->expectException(EsbConfigurationException::class);

        $this->manager()->token();
    }

    #[Test]
    public function login_mode_without_credentials_fails_as_misconfiguration(): void
    {
        config()->set('esb.auth.mechanism', 'login');
        config()->set('esb.auth.username', null);
        config()->set('esb.auth.password', null);

        $this->expectException(EsbConfigurationException::class);

        $this->manager()->token();
    }

    #[Test]
    public function login_mode_exchanges_credentials_for_an_access_token(): void
    {
        $this->useLoginMechanism();
        Http::fake(['*/auth/login' => Http::response($this->loginResponse())]);

        $this->assertSame('access-1', $this->manager()->token());

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://stg7.esb.co.id/core-int/auth/login'
            && $request->method() === 'POST'
            && $request['username'] === 'gateway-integration'
            && $request['password'] === 'not-a-real-password');
    }

    #[Test]
    public function many_callers_with_an_empty_cache_produce_exactly_one_login(): void
    {
        // The requirement from ADR-009. Each extra login would invalidate the
        // token the previous one just obtained.
        $this->useLoginMechanism();
        Http::fake(['*/auth/login' => Http::response($this->loginResponse())]);

        for ($caller = 0; $caller < 10; $caller++) {
            // A fresh instance each time, standing in for a separate worker
            // resolving the manager out of the container.
            $this->assertSame('access-1', app(TokenManager::class)->token());
        }

        Http::assertSentCount(1);
    }

    #[Test]
    public function it_re_reads_the_cache_after_acquiring_the_lock(): void
    {
        // This is the mechanism that makes concurrency safe. Without the second
        // read, every process that queued on the lock would log in as soon as
        // it was granted -- and each login would invalidate the one before it.
        $this->useLoginMechanism();
        Http::fake(['*/auth/login' => Http::response($this->loginResponse())]);

        $manager = $this->manager();

        $lock = Mockery::mock();
        $lock->shouldReceive('block')->once()->andReturnUsing(
            function (int $seconds, callable $callback) use ($manager) {
                // Stand in for another process that finished logging in while
                // this caller was waiting for the lock.
                $manager->storeTokens('access-from-another-process', 'refresh-x', 3600);

                return $callback();
            },
        );

        // Proxy the real store so only lock() is intercepted -- get() and
        // put() must keep working, since the cache re-read is what is under
        // test here.
        $this->interceptLockWith($lock);

        $this->assertSame('access-from-another-process', $manager->token());

        Http::assertNothingSent();
    }

    #[Test]
    public function the_lock_wait_is_bounded(): void
    {
        // A stuck lock holder must not hang every worker in the fleet.
        $this->useLoginMechanism();
        Http::fake(['*/auth/login' => Http::response($this->loginResponse())]);

        $lock = Mockery::mock();
        $lock->shouldReceive('block')
            ->once()
            ->withArgs(fn (int $seconds, callable $callback): bool => $seconds > 0 && $seconds <= 30)
            ->andReturnUsing(fn (int $seconds, callable $callback) => $callback());

        $this->interceptLockWith($lock, fn (string $key, int $ttl): bool => $ttl > 0);

        $this->manager()->token();
    }

    #[Test]
    public function an_invalid_credential_raises_an_authentication_failure(): void
    {
        $this->useLoginMechanism();
        Http::fake(['*/auth/login' => Http::response([
            'path' => 'https://stg7.esb.co.id/core-int/auth/login',
            'timestamp' => '2024-05-20 13:32:13',
            'status' => 'fail',
            'code' => 'EC03100032',
            'message' => 'Invalid username or password, please check again',
            'result' => null,
            'errors' => null,
        ])]);

        $this->expectException(EsbAuthenticationException::class);

        $this->manager()->token();
    }

    #[Test]
    public function forgetting_the_token_forces_a_fresh_login(): void
    {
        $this->useLoginMechanism();
        Http::fake(['*/auth/login' => Http::sequence()
            ->push($this->loginResponse('access-1', 'refresh-1'))
            ->push($this->loginResponse('access-2', 'refresh-2')),
        ]);

        $manager = $this->manager();

        $this->assertSame('access-1', $manager->token());

        // This is what happens on EC03100001: the cached token is discarded
        // immediately rather than retried.
        $manager->forget();

        $this->assertSame('access-2', $manager->token());
        Http::assertSentCount(2);
    }

    #[Test]
    public function it_refreshes_with_the_refresh_token_in_the_authorization_header(): void
    {
        $this->useLoginMechanism();
        Http::fake([
            '*/auth/login' => Http::response($this->loginResponse('access-1', 'refresh-1')),
            '*/auth/refresh' => Http::response($this->loginResponse('access-2', 'refresh-2')),
        ]);

        $manager = $this->manager();
        $manager->token();

        // Expire the access token but keep the refresh token.
        $manager->storeTokens('access-1', 'refresh-1', -1);

        $this->assertSame('access-2', $manager->token());

        // The vendor documents refresh as a GET carrying the refresh token in
        // the Authorization header -- it takes no body.
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/auth/refresh')
            && $request->method() === 'GET'
            && $request->header('Authorization') === ['Bearer refresh-1']);
    }

    #[Test]
    public function a_rejected_refresh_falls_back_to_a_full_login(): void
    {
        $this->useLoginMechanism();
        Http::fake([
            '*/auth/refresh' => Http::response([
                'code' => 'EC03100001',
                'message' => 'Invalid Token',
                'status' => 'fail',
                'result' => null,
            ]),
            '*/auth/login' => Http::response($this->loginResponse('access-fresh', 'refresh-fresh')),
        ]);

        $manager = $this->manager();
        $manager->storeTokens('expired', 'stale-refresh', -1);

        $this->assertSame('access-fresh', $manager->token());
    }

    #[Test]
    public function the_cached_token_never_reaches_the_log(): void
    {
        $this->useLoginMechanism();
        Http::fake(['*/auth/login' => Http::response($this->loginResponse('super-secret-access', 'super-secret-refresh'))]);

        $logFile = storage_path('logs/laravel.log');
        $before = file_exists($logFile) ? (string) file_get_contents($logFile) : '';

        $this->manager()->token();

        $after = file_exists($logFile) ? (string) file_get_contents($logFile) : '';
        $written = substr($after, strlen($before));

        $this->assertStringNotContainsString('super-secret-access', $written);
        $this->assertStringNotContainsString('super-secret-refresh', $written);
        $this->assertStringNotContainsString('not-a-real-password', $written);
    }
}
