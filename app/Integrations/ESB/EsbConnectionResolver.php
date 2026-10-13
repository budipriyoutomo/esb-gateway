<?php

namespace App\Integrations\ESB;

use App\Integrations\ESB\Exceptions\EsbConfigurationException;
use App\Models\EsbConnection;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * The single place that decides which ESB a request actually reaches.
 *
 * ADR-018 makes connections editable, which introduces a second source of
 * truth next to config/esb.php. Two sources that can disagree are exactly what
 * ADR-013 refused this feature over, so precedence is defined once, here, and
 * runs one way only:
 *
 *   1. an active esb_connections row for this family in the configured
 *      environment;
 *   2. the ESB_BASE_URL_* override;
 *   3. the family table in config/esb.php.
 *
 * An inactive row does not fall through to 2 or 3: it switches the family off
 * (ADR-020, superseding the ADR-018 fallback).
 *
 * Nothing ever writes back into configuration. A row for an environment other
 * than the configured one is a record, not a route: it is ignored, so writing
 * down a production connection on a staging install cannot repoint it.
 *
 * Because a database row outranks the environment override, an operator who
 * sets ESB_BASE_URL_CORE and sees no effect would otherwise have nothing to go
 * on. source() exists so the connections screen can show which of the three
 * actually won.
 */
class EsbConnectionResolver
{
    /**
     * @var array<string, EsbConnection|null>
     */
    private array $connections = [];

    public function __construct(
        private readonly Config $config,
        private readonly BaseUrlResolver $baseUrlResolver,
    ) {}

    /**
     * The effective base URL for a family.
     *
     * Throws for a family with no documented host, exactly as the
     * configuration resolver does: guessing would send staging traffic at
     * production ERP data.
     */
    public function resolve(EsbFamily $family, ?EsbEnvironment $environment = null): string
    {
        if ($environment === null) {
            // Switched off on the connections screen (ADR-020). Falling back to
            // configuration here would route traffic at a host the operator
            // just said not to use.
            if ($this->isDisabled($family)) {
                throw new EsbConfigurationException(sprintf(
                    'The %s ESB connection is disabled. Enable it on the ESB connections screen to use it.',
                    $family->value,
                ));
            }

            $connection = $this->connectionFor($family);

            if ($connection !== null) {
                return rtrim($connection->base_url, '/');
            }
        }

        return $this->baseUrlResolver->resolve($family, $environment);
    }

    public function configuredEnvironment(): EsbEnvironment
    {
        return $this->baseUrlResolver->configuredEnvironment();
    }

    /**
     * Which layer supplied the effective base URL.
     *
     * @return 'disabled'|'database'|'override'|'config'|'none'
     */
    public function source(EsbFamily $family): string
    {
        if ($this->isDisabled($family)) {
            return 'disabled';
        }

        if ($this->connectionFor($family) !== null) {
            return 'database';
        }

        $override = $this->config->get("esb.overrides.{$family->value}");

        if (is_string($override) && $override !== '') {
            return 'override';
        }

        $environment = $this->configuredEnvironment();
        $baseUrl = $this->config->get("esb.families.{$family->value}.{$environment->value}");

        return is_string($baseUrl) && $baseUrl !== '' ? 'config' : 'none';
    }

    /**
     * The active row for a family in the configured environment, if any.
     */
    public function connectionFor(EsbFamily $family): ?EsbConnection
    {
        $row = $this->rowFor($family);

        return $row?->is_active ? $row : null;
    }

    /**
     * The family's row in the configured environment, active or not.
     */
    public function rowFor(EsbFamily $family): ?EsbConnection
    {
        $environment = $this->configuredEnvironment()->value;
        $key = $family->value.'@'.$environment;

        if (! array_key_exists($key, $this->connections)) {
            $this->connections[$key] = EsbConnection::query()
                ->where('family', $family->value)
                ->where('environment', $environment)
                ->first();
        }

        return $this->connections[$key];
    }

    /**
     * Has an operator switched this family off (ADR-020)?
     */
    public function isDisabled(EsbFamily $family): bool
    {
        $row = $this->rowFor($family);

        return $row !== null && ! $row->is_active;
    }

    /**
     * The effective ESB credential set.
     *
     * The vendor authenticates against one family for the whole installation
     * (config esb.auth.family), so credentials come from that family's row
     * when it has them, and from configuration otherwise. Each field falls
     * back independently: a row that only repoints a base URL must not blank
     * out a credential that configuration still supplies.
     *
     * @return array{mechanism: string, static_token: string|null, username: string|null, password: string|null}
     */
    public function auth(): array
    {
        $connection = $this->authConnection();

        return [
            'mechanism' => $this->firstFilled(
                $connection?->auth_mechanism,
                $this->config->get('esb.auth.mechanism'),
            ) ?? 'static_token',
            'static_token' => $this->firstFilled(
                $connection?->staticToken(),
                $this->config->get('esb.auth.static_token'),
            ),
            'username' => $this->firstFilled(
                $connection?->username(),
                $this->config->get('esb.auth.username'),
            ),
            'password' => $this->firstFilled(
                $connection?->password(),
                $this->config->get('esb.auth.password'),
            ),
        ];
    }

    private function authConnection(): ?EsbConnection
    {
        $family = EsbFamily::tryFrom((string) $this->config->get('esb.auth.family'));

        return $family === null ? null : $this->connectionFor($family);
    }

    private function firstFilled(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }
}
