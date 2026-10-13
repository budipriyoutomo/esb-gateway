<?php

namespace Tests\Feature\EsbConnections;

use App\Authorization\Role;
use App\Integrations\ESB\Clients\EsbClient;
use App\Integrations\ESB\EsbConnectionResolver;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbConfigurationException;
use App\Models\AuditLog;
use App\Models\EsbConnection;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ESB connections are editable records (ADR-018, superseding ADR-013).
 *
 * ADR-013 refused this feature for three concrete reasons, and each one has to
 * stay refused in the implementation: credentials must not sit in the database
 * in plaintext, the two sources of truth must not be able to disagree
 * silently, and a production/staging switch must not be one mis-click away.
 * Most of the tests below exist to hold those three lines.
 */
class EsbConnectionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(RolePermissionSeeder::class);

        config()->set('esb.environment', 'staging_int');
        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', 'config-api-key');
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'base_url' => 'https://esb.example.test/core-runtime',
            'auth_mechanism' => 'static_token',
            'static_token' => 'runtime-api-key',
            'is_active' => true,
        ], $overrides);
    }

    // ----------------------------------------------------------- the route

    #[Test]
    public function a_connection_can_be_edited_through_the_interface(): void
    {
        // Inverted from the ADR-013 era, when this route deliberately did not
        // exist. ADR-018 supersedes that: an ESB environment has to be
        // repointable without a deploy.
        $this->assertTrue(
            app('router')->has('esb-connections.update'),
            'ADR-018 makes ESB connections editable; the update route must exist.',
        );
    }

    // -------------------------------------------------------- authorization

    #[Test]
    public function a_guest_cannot_update_a_connection(): void
    {
        $this->put(route('esb-connections.update', ['family' => 'core']), $this->payload())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('esb_connections', 0);
    }

    #[Test]
    public function a_user_without_the_manage_permission_cannot_update_a_connection(): void
    {
        // The guarantee the old "there is no update route" test was really
        // protecting. Losing the route must not mean losing this.
        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->put(route('esb-connections.update', ['family' => 'core']), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('esb_connections', 0);
    }

    #[Test]
    public function an_it_admin_can_update_a_connection(): void
    {
        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->put(route('esb-connections.update', ['family' => 'core']), $this->payload())
            ->assertRedirect(route('esb-connections.index'));

        $connection = EsbConnection::query()->sole();

        $this->assertSame('core', $connection->family);
        $this->assertSame('staging_int', $connection->environment);
        $this->assertSame('https://esb.example.test/core-runtime', $connection->base_url);
        $this->assertTrue($connection->is_active);
    }

    #[Test]
    public function an_unknown_family_is_rejected(): void
    {
        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->put(route('esb-connections.update', ['family' => 'nonsense']), $this->payload())
            ->assertNotFound();
    }

    // ------------------------------------------------------------ validation

    #[Test]
    public function a_base_url_must_be_https(): void
    {
        // Plain HTTP would put ESB credentials and ERP data on the wire in
        // clear text, and the form is the one place a person can introduce it.
        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->put(
                route('esb-connections.update', ['family' => 'core']),
                $this->payload(['base_url' => 'http://esb.example.test/core']),
            )
            ->assertSessionHasErrors('base_url');

        $this->assertDatabaseCount('esb_connections', 0);
    }

    #[Test]
    public function a_base_url_must_be_a_url(): void
    {
        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->put(
                route('esb-connections.update', ['family' => 'core']),
                $this->payload(['base_url' => 'not a url']),
            )
            ->assertSessionHasErrors('base_url');
    }

    #[Test]
    public function an_unknown_auth_mechanism_is_rejected(): void
    {
        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->put(
                route('esb-connections.update', ['family' => 'core']),
                $this->payload(['auth_mechanism' => 'magic']),
            )
            ->assertSessionHasErrors('auth_mechanism');
    }

    #[Test]
    public function the_login_mechanism_requires_a_username_and_password(): void
    {
        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->put(
                route('esb-connections.update', ['family' => 'core']),
                ['base_url' => 'https://esb.example.test/core', 'auth_mechanism' => 'login', 'is_active' => true],
            )
            ->assertSessionHasErrors(['username', 'password']);
    }

    // ------------------------------------------------------------ encryption

    #[Test]
    public function credentials_are_encrypted_at_rest(): void
    {
        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->put(route('esb-connections.update', ['family' => 'core']), $this->payload());

        // Read the raw column, not the model. Asserting through the model
        // would only prove the cast round-trips, which is true of a plaintext
        // column too.
        $raw = DB::table('esb_connections')->where('family', 'core')->value('credentials');

        $this->assertIsString($raw);
        $this->assertStringNotContainsString('runtime-api-key', $raw);
        $this->assertSame('runtime-api-key', EsbConnection::query()->sole()->staticToken());
    }

    #[Test]
    public function a_login_password_is_encrypted_at_rest(): void
    {
        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->put(
                route('esb-connections.update', ['family' => 'core']),
                $this->payload([
                    'auth_mechanism' => 'login',
                    'static_token' => null,
                    'username' => 'gateway-integration',
                    'password' => 'not-a-real-password',
                ]),
            );

        $raw = DB::table('esb_connections')->where('family', 'core')->value('credentials');

        $this->assertIsString($raw);
        $this->assertStringNotContainsString('not-a-real-password', $raw);
    }

    // ----------------------------------------------------------- write-only

    #[Test]
    public function a_stored_credential_is_never_returned_to_the_browser(): void
    {
        $admin = $this->userWithRole(Role::ItAdmin);

        $this->actingAs($admin)
            ->put(route('esb-connections.update', ['family' => 'core']), $this->payload());

        $response = $this->actingAs($admin)->get(route('esb-connections.index'));

        $response->assertOk();
        $response->assertDontSee('runtime-api-key');
        $response->assertInertia(fn (AssertableInertia $page) => $page
            // A masked presence indicator is all a reader gets. It answers the
            // only question an operator actually has -- is a credential set?
            ->where('connections.0.hasCredential', true),
        );
    }

    #[Test]
    public function a_stored_credential_is_never_serialized_by_the_model(): void
    {
        $connection = EsbConnection::query()->create([
            'family' => 'core',
            'environment' => 'staging_int',
            'base_url' => 'https://esb.example.test/core',
            'auth_mechanism' => 'static_token',
            'credentials' => ['static_token' => 'runtime-api-key'],
            'is_active' => true,
        ]);

        // Anything that reaches a log line, a job payload, or a response goes
        // through here first.
        $this->assertStringNotContainsString('runtime-api-key', $connection->toJson());
        $this->assertArrayNotHasKey('credentials', $connection->toArray());
    }

    // ------------------------------------------------------------ precedence

    #[Test]
    public function an_active_row_overrides_the_configured_base_url(): void
    {
        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->put(route('esb-connections.update', ['family' => 'core']), $this->payload());

        $this->assertSame(
            'https://esb.example.test/core-runtime',
            app(EsbConnectionResolver::class)->resolve(EsbFamily::Core),
        );
    }

    #[Test]
    public function an_inactive_row_disables_the_family(): void
    {
        // ADR-020, superseding the ADR-018 fallback: switching a connection
        // off means the gateway stops talking to that family. Quietly
        // re-routing to config/esb.php would send traffic at a host the
        // operator just said not to use.
        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->put(
                route('esb-connections.update', ['family' => 'core']),
                $this->payload(['is_active' => false]),
            );

        $this->expectException(EsbConfigurationException::class);

        app(EsbConnectionResolver::class)->resolve(EsbFamily::Core);
    }

    #[Test]
    public function disabling_one_family_leaves_the_others_active(): void
    {
        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->put(
                route('esb-connections.update', ['family' => 'voucher']),
                $this->payload(['base_url' => 'https://esb.example.test/voucher', 'is_active' => false]),
            );

        $this->assertSame(
            'https://stg7.esb.co.id/core-int',
            app(EsbConnectionResolver::class)->resolve(EsbFamily::Core),
        );
    }

    #[Test]
    public function a_disabled_family_sends_nothing_to_the_esb(): void
    {
        Http::fake();

        EsbConnection::query()->create([
            'family' => 'core',
            'environment' => 'staging_int',
            'base_url' => 'https://esb.example.test/core',
            'auth_mechanism' => 'static_token',
            'credentials' => null,
            'is_active' => false,
        ]);

        try {
            app(EsbClient::class)->get(EsbFamily::Core, '/product/list', ['limit' => 1], operation: 'test');
            $this->fail('A disabled family must refuse the call.');
        } catch (EsbConfigurationException) {
            // expected
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function the_screen_says_which_connections_are_active(): void
    {
        $admin = $this->userWithRole(Role::ItAdmin);

        $this->actingAs($admin)->put(
            route('esb-connections.update', ['family' => 'voucher']),
            $this->payload(['base_url' => 'https://esb.example.test/voucher', 'is_active' => false]),
        );

        $this->actingAs($admin)
            ->get(route('esb-connections.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('connections.0.active', true)
                ->where('connections.1.family', 'voucher')
                ->where('connections.1.active', false)
                ->where('connections.1.source', 'disabled')
                // The stored URL is kept on screen so switching the connection
                // back on does not mean retyping it.
                ->where('connections.1.baseUrl', 'https://esb.example.test/voucher')
                ->where('connections.1.probeAvailable', false),
            );
    }

    #[Test]
    public function the_screen_reports_each_rows_auth_mechanism(): void
    {
        // The edit form starts from this. Defaulting it would flip a login
        // connection to static_token on an unrelated edit, like toggling it off.
        $this->actingAs($this->userWithRole(Role::ItAdmin))->put(
            route('esb-connections.update', ['family' => 'core']),
            $this->payload(['auth_mechanism' => 'login', 'username' => 'gw', 'password' => 'pw']),
        );

        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->get(route('esb-connections.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('connections.0.authMechanism', 'login')
                ->where('connections.1.authMechanism', 'static_token'),
            );
    }

    #[Test]
    public function a_saved_change_is_confirmed_on_the_page(): void
    {
        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->followingRedirects()
            ->put(route('esb-connections.update', ['family' => 'core']), $this->payload())
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('flash.status', 'The core connection was updated.'),
            );
    }

    #[Test]
    public function a_row_for_another_environment_is_not_used(): void
    {
        EsbConnection::query()->create([
            'family' => 'core',
            'environment' => 'production',
            'base_url' => 'https://services.esb.co.id/core',
            'auth_mechanism' => 'static_token',
            'credentials' => null,
            'is_active' => true,
        ]);

        // The install is configured for staging_int. A production row exists
        // as a record, but must not silently take effect.
        $this->assertSame(
            'https://stg7.esb.co.id/core-int',
            app(EsbConnectionResolver::class)->resolve(EsbFamily::Core),
        );
    }

    #[Test]
    public function the_effective_source_is_reported_for_each_family(): void
    {
        $admin = $this->userWithRole(Role::ItAdmin);

        $this->actingAs($admin)
            ->put(route('esb-connections.update', ['family' => 'core']), $this->payload());

        // An operator who sets an override and sees no effect needs to be able
        // to find out why. Precedence that is not visible is precedence that
        // gets debugged by guesswork.
        $this->actingAs($admin)
            ->get(route('esb-connections.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('connections.0.source', 'database')
                ->where('connections.1.source', 'config'),
            );
    }

    #[Test]
    public function reverting_a_connection_restores_the_configured_base_url(): void
    {
        $admin = $this->userWithRole(Role::ItAdmin);

        $this->actingAs($admin)
            ->put(route('esb-connections.update', ['family' => 'core']), $this->payload());

        $this->actingAs($admin)
            ->delete(route('esb-connections.destroy', ['family' => 'core']))
            ->assertRedirect(route('esb-connections.index'));

        $this->assertDatabaseCount('esb_connections', 0);
        $this->assertSame(
            'https://stg7.esb.co.id/core-int',
            app(EsbConnectionResolver::class)->resolve(EsbFamily::Core),
        );
    }

    #[Test]
    public function a_user_without_the_manage_permission_cannot_revert_a_connection(): void
    {
        EsbConnection::query()->create([
            'family' => 'core',
            'environment' => 'staging_int',
            'base_url' => 'https://esb.example.test/core',
            'auth_mechanism' => 'static_token',
            'credentials' => null,
            'is_active' => true,
        ]);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->delete(route('esb-connections.destroy', ['family' => 'core']))
            ->assertForbidden();

        $this->assertDatabaseCount('esb_connections', 1);
    }

    // ---------------------------------------------------------- credentials

    #[Test]
    public function a_stored_credential_is_used_instead_of_the_configured_one(): void
    {
        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->put(route('esb-connections.update', ['family' => 'core']), $this->payload());

        $auth = app(EsbConnectionResolver::class)->auth();

        $this->assertSame('static_token', $auth['mechanism']);
        $this->assertSame('runtime-api-key', $auth['static_token']);
    }

    #[Test]
    public function configuration_supplies_the_credential_when_no_row_exists(): void
    {
        $auth = app(EsbConnectionResolver::class)->auth();

        $this->assertSame('config-api-key', $auth['static_token']);
    }

    #[Test]
    public function submitting_a_blank_credential_keeps_the_stored_one(): void
    {
        $admin = $this->userWithRole(Role::ItAdmin);

        $this->actingAs($admin)
            ->put(route('esb-connections.update', ['family' => 'core']), $this->payload());

        // The form cannot show the stored value, so it comes back empty on
        // every subsequent edit. Treating empty as "clear it" would wipe the
        // credential every time somebody changed the base URL.
        $this->actingAs($admin)
            ->put(
                route('esb-connections.update', ['family' => 'core']),
                $this->payload(['base_url' => 'https://esb.example.test/core-moved', 'static_token' => '']),
            );

        $connection = EsbConnection::query()->sole();

        $this->assertSame('https://esb.example.test/core-moved', $connection->base_url);
        $this->assertSame('runtime-api-key', $connection->staticToken());
    }

    #[Test]
    public function changing_a_credential_discards_the_cached_esb_token(): void
    {
        config()->set('esb.auth.mechanism', 'login');
        Cache::put('esb:token:staging_int', ['access_token' => 'stale', 'expires_at' => now()->addHour()->timestamp], 3600);

        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->put(
                route('esb-connections.update', ['family' => 'core']),
                $this->payload([
                    'auth_mechanism' => 'login',
                    'static_token' => null,
                    'username' => 'gateway-integration',
                    'password' => 'a-new-password',
                ]),
            );

        // A token minted with the old credential is worthless and, worse,
        // keeps working until it expires -- so the change would look like it
        // did nothing for up to an hour (ADR-009).
        $this->assertNull(Cache::get('esb:token:staging_int'));
    }

    // ---------------------------------------------------------------- audit

    #[Test]
    public function updating_a_connection_writes_an_audit_row(): void
    {
        $admin = $this->userWithRole(Role::ItAdmin);

        $this->actingAs($admin)
            ->put(route('esb-connections.update', ['family' => 'core']), $this->payload());

        $audit = AuditLog::query()->sole();

        $this->assertSame('esb-connection.updated', $audit->action);
        $this->assertSame($admin->id, $audit->actor_id);
        $this->assertSame('core', $audit->metadata['family']);
        $this->assertContains('credentials', $audit->metadata['changed']);
    }

    #[Test]
    public function an_audit_row_records_which_field_changed_but_never_the_value(): void
    {
        $admin = $this->userWithRole(Role::ItAdmin);

        $this->actingAs($admin)
            ->put(route('esb-connections.update', ['family' => 'core']), $this->payload());

        // A credential must not reach the audit log any more than it reaches
        // the application log.
        $this->assertStringNotContainsString('runtime-api-key', AuditLog::query()->sole()->toJson());
    }

    #[Test]
    public function reverting_a_connection_writes_an_audit_row(): void
    {
        $admin = $this->userWithRole(Role::ItAdmin);

        $this->actingAs($admin)
            ->put(route('esb-connections.update', ['family' => 'core']), $this->payload());
        $this->actingAs($admin)
            ->delete(route('esb-connections.destroy', ['family' => 'core']));

        $this->assertSame(
            'esb-connection.reverted',
            AuditLog::query()->latest('id')->first()?->action,
        );
    }

    // ----------------------------------------------------------- uniqueness

    #[Test]
    public function a_family_has_at_most_one_row_per_environment(): void
    {
        $admin = $this->userWithRole(Role::ItAdmin);

        $this->actingAs($admin)
            ->put(route('esb-connections.update', ['family' => 'core']), $this->payload());
        $this->actingAs($admin)
            ->put(
                route('esb-connections.update', ['family' => 'core']),
                $this->payload(['base_url' => 'https://esb.example.test/core-again']),
            );

        $this->assertDatabaseCount('esb_connections', 1);
        $this->assertSame(
            'https://esb.example.test/core-again',
            EsbConnection::query()->sole()->base_url,
        );
    }
}
