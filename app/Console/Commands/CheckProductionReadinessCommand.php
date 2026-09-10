<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Turns the pre-production checklist in docs/10-security.md into something a
 * deploy can run.
 *
 * A checklist nobody executes is a checklist that gets skipped on the release
 * that needed it. Run this as a deployment step, and locally before a release
 * as a dry run.
 *
 * Checks that are correct-for-local are skipped outside production, so running
 * it on a developer machine does not produce noise nobody reads.
 */
class CheckProductionReadinessCommand extends Command
{
    protected $signature = 'app:check-production-readiness';

    protected $description = 'Verify the security and configuration checklist before a release';

    /**
     * @var list<string>
     */
    private array $problems = [];

    /**
     * @var list<string>
     */
    private array $warnings = [];

    public function handle(): int
    {
        $isProduction = config('app.env') === 'production';

        if ($isProduction) {
            $this->checkProductionOnlySettings();
        }

        $this->checkApplicationKey();
        $this->checkEsbCredentials($isProduction);
        $this->checkEsbEnvironmentMatchesApp($isProduction);

        foreach ($this->warnings as $warning) {
            $this->warn('WARN  '.$warning);
        }

        if ($this->problems === []) {
            $this->info(sprintf(
                'Production readiness: OK (%d warning%s).',
                count($this->warnings),
                count($this->warnings) === 1 ? '' : 's',
            ));

            return self::SUCCESS;
        }

        foreach ($this->problems as $problem) {
            $this->error('FAIL  '.$problem);
        }

        $this->newLine();
        $this->error(sprintf('Production readiness: %d problem(s) found.', count($this->problems)));

        return self::FAILURE;
    }

    private function checkProductionOnlySettings(): void
    {
        if (config('app.debug') === true) {
            $this->problems[] = 'APP_DEBUG is enabled. Stack traces would expose configuration and query detail.';
        }

        $appUrl = (string) config('app.url');

        if (! Str::startsWith($appUrl, 'https://')) {
            $this->problems[] = 'APP_URL is not HTTPS. Sessions and ESB-derived data would travel in clear text.';
        }

        if (config('session.secure') !== true) {
            $this->problems[] = 'SESSION_SECURE_COOKIE is not enabled. The session cookie would be sent over plain HTTP.';
        }

        if (config('session.http_only') !== true) {
            $this->problems[] = 'Session cookies are readable by JavaScript. Set http_only.';
        }

        if (config('session.same_site') === null) {
            $this->problems[] = 'Session same_site is unset, which leaves CSRF protection weaker than it needs to be.';
        }
    }

    private function checkApplicationKey(): void
    {
        $key = config('app.key');

        if (! is_string($key) || trim($key) === '') {
            $this->problems[] = 'APP_KEY is not set. Encrypted cookies and cached ESB tokens cannot be protected.';
        }
    }

    /**
     * Credentials are environment-specific, so a dry run on a machine that has
     * none cannot tell you whether production has them. Outside production
     * these are warnings: failing here would make the command useless locally,
     * and a check nobody runs protects nothing.
     */
    private function checkEsbCredentials(bool $isProduction): void
    {
        $report = $isProduction
            ? function (string $message): void {
                $this->problems[] = $message;
            }
        : function (string $message): void {
            $this->warnings[] = $message;
        };

        $mechanism = config('esb.auth.mechanism');

        if ($mechanism === 'static_token') {
            if (! $this->filled(config('esb.auth.static_token'))) {
                $report('ESB_AUTH_MECHANISM is "static_token" but ESB_STATIC_TOKEN is empty.');
            }

            return;
        }

        if ($mechanism === 'login') {
            if (! $this->filled(config('esb.auth.username'))) {
                $report('ESB_AUTH_MECHANISM is "login" but ESB_USERNAME is empty.');
            }

            if (! $this->filled(config('esb.auth.password'))) {
                $report('ESB_AUTH_MECHANISM is "login" but ESB_PASSWORD is empty.');
            }

            // Not a failure, but worth saying: a static key sidesteps the
            // single-session constraint entirely (ADR-009).
            $this->warnings[] = 'Using the login mechanism. A static API key avoids the ESB\'s one-active-session-per-credential constraint; ask the ESB team whether one is available.';

            return;
        }

        // An unrecognised mechanism is a config error regardless of
        // environment -- nothing would work anywhere.
        $this->problems[] = sprintf(
            'ESB_AUTH_MECHANISM is "%s". Expected "static_token" or "login".',
            is_scalar($mechanism) ? (string) $mechanism : gettype($mechanism),
        );
    }

    private function checkEsbEnvironmentMatchesApp(bool $isProduction): void
    {
        $esbEnvironment = (string) config('esb.environment');

        if (! $isProduction && $esbEnvironment === 'production') {
            // The mistake worth catching most. A staging or local install
            // wired to the production ESB writes to real ERP data, and nothing
            // in the application would look wrong until it did.
            $this->problems[] = sprintf(
                'APP_ENV is "%s" but ESB_ENV is the production ESB. This install would write to real ERP data.',
                (string) config('app.env'),
            );

            return;
        }

        if ($isProduction && $esbEnvironment !== 'production') {
            // Harmless to ERP data -- production would just be reading test
            // data -- so say it out loud without blocking the deploy.
            $this->warnings[] = sprintf(
                'APP_ENV is production but ESB_ENV is "%s". Production will read and write test ERP data.',
                $esbEnvironment,
            );
        }
    }

    private function filled(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }
}
