<?php

namespace Tests\Feature\Hardening;

use App\Models\EsbConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `php artisan app:check-production-readiness` turns the pre-production
 * checklist in docs/10-security.md into something a deploy can run.
 *
 * A checklist nobody executes is a checklist that gets skipped on the release
 * that needed it.
 */
class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function configureSafeProduction(): void
    {
        config()->set('app.env', 'production');
        config()->set('app.debug', false);
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        config()->set('app.url', 'https://gateway.example.test');
        config()->set('session.secure', true);
        config()->set('session.http_only', true);
        config()->set('session.same_site', 'lax');
        config()->set('esb.environment', 'production');
        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', 'a-static-api-key');
    }

    #[Test]
    public function a_correctly_configured_production_install_passes(): void
    {
        $this->configureSafeProduction();

        $this->artisan('app:check-production-readiness')->assertSuccessful();
    }

    #[Test]
    public function debug_mode_in_production_fails_the_check(): void
    {
        $this->configureSafeProduction();
        config()->set('app.debug', true);

        $this->artisan('app:check-production-readiness')
            ->expectsOutputToContain('APP_DEBUG')
            ->assertFailed();
    }

    #[Test]
    public function a_plain_http_app_url_fails_the_check(): void
    {
        $this->configureSafeProduction();
        config()->set('app.url', 'http://gateway.example.test');

        $this->artisan('app:check-production-readiness')
            ->expectsOutputToContain('HTTPS')
            ->assertFailed();
    }

    #[Test]
    public function an_insecure_session_cookie_fails_the_check(): void
    {
        $this->configureSafeProduction();
        config()->set('session.secure', false);

        $this->artisan('app:check-production-readiness')
            ->expectsOutputToContain('SESSION_SECURE_COOKIE')
            ->assertFailed();
    }

    #[Test]
    public function a_missing_esb_credential_fails_the_check(): void
    {
        $this->configureSafeProduction();
        config()->set('esb.auth.static_token', null);

        $this->artisan('app:check-production-readiness')
            ->expectsOutputToContain('ESB_STATIC_TOKEN')
            ->assertFailed();
    }

    #[Test]
    public function login_mode_without_credentials_fails_the_check(): void
    {
        $this->configureSafeProduction();
        config()->set('esb.auth.mechanism', 'login');
        config()->set('esb.auth.static_token', null);
        config()->set('esb.auth.username', null);
        config()->set('esb.auth.password', null);

        $this->artisan('app:check-production-readiness')
            ->expectsOutputToContain('ESB_USERNAME')
            ->assertFailed();
    }

    #[Test]
    public function a_non_production_app_pointing_at_the_production_esb_fails_the_check(): void
    {
        $this->configureSafeProduction();
        config()->set('app.env', 'staging');
        config()->set('esb.environment', 'production');

        // This is the mistake worth catching most: a staging or local install
        // wired to the production ESB writes to real ERP data, and nothing in
        // the application would look wrong until it did.
        $this->artisan('app:check-production-readiness')
            ->expectsOutputToContain('production ESB')
            ->assertFailed();
    }

    #[Test]
    public function a_production_app_on_a_staging_esb_is_only_a_warning(): void
    {
        $this->configureSafeProduction();
        config()->set('esb.environment', 'staging_int');

        // The reverse mismatch is harmless to ERP data -- it just means
        // production is reading test data -- so it is worth saying out loud
        // without blocking a deploy.
        $this->artisan('app:check-production-readiness')
            ->expectsOutputToContain('staging_int')
            ->assertSuccessful();
    }

    #[Test]
    public function a_database_row_pointing_at_the_production_esb_fails_the_check(): void
    {
        $this->configureSafeProduction();
        config()->set('app.env', 'staging');
        config()->set('esb.environment', 'staging_int');

        EsbConnection::query()->create([
            'family' => 'core',
            'environment' => 'staging_int',
            'base_url' => 'https://services.esb.co.id/core',
            'auth_mechanism' => 'static_token',
            'credentials' => null,
            'is_active' => true,
        ]);

        // ADR-018 makes connections editable, so the check has to run against
        // the effective connection rather than config/esb.php alone. Otherwise
        // a database row becomes a way around the one check that exists to
        // stop a staging install writing to real ERP data.
        $this->artisan('app:check-production-readiness')
            ->expectsOutputToContain('production ESB')
            ->assertFailed();
    }

    #[Test]
    public function an_inactive_row_pointing_at_production_does_not_fail_the_check(): void
    {
        $this->configureSafeProduction();
        config()->set('app.env', 'staging');
        config()->set('esb.environment', 'staging_int');

        EsbConnection::query()->create([
            'family' => 'core',
            'environment' => 'staging_int',
            'base_url' => 'https://services.esb.co.id/core',
            'auth_mechanism' => 'static_token',
            'credentials' => null,
            'is_active' => false,
        ]);

        // An inactive row is a record, not a route. Failing on it would train
        // people to ignore the check.
        $this->artisan('app:check-production-readiness')->assertSuccessful();
    }

    #[Test]
    public function an_env_override_pointing_at_production_fails_the_check(): void
    {
        $this->configureSafeProduction();
        config()->set('app.env', 'staging');
        config()->set('esb.environment', 'staging_int');
        config()->set('esb.overrides.core', 'https://services.esb.co.id/core');

        // Same hole, different lever. The override is checked for the same
        // reason the database row is.
        $this->artisan('app:check-production-readiness')
            ->expectsOutputToContain('production ESB')
            ->assertFailed();
    }

    #[Test]
    public function it_can_be_run_outside_production_for_a_dry_run(): void
    {
        config()->set('app.env', 'local');
        config()->set('app.debug', true);
        config()->set('esb.environment', 'staging_int');

        // Running it locally must not fail on things that are correct for
        // local, or nobody will run it before deploying.
        $this->artisan('app:check-production-readiness')->assertSuccessful();
    }
}
