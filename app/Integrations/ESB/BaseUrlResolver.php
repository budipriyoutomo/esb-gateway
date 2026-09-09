<?php

namespace App\Integrations\ESB;

use App\Integrations\ESB\Exceptions\EsbConfigurationException;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Resolves the base URL for an ESB family in a given environment.
 *
 * Never guesses. A family with no documented host for an environment throws,
 * because falling back to another environment would send staging traffic at
 * production ERP data.
 */
class BaseUrlResolver
{
    public function __construct(private readonly Config $config) {}

    public function resolve(EsbFamily $family, ?EsbEnvironment $environment = null): string
    {
        $override = $this->config->get("esb.overrides.{$family->value}");

        if (is_string($override) && $override !== '') {
            return rtrim($override, '/');
        }

        $environment ??= $this->configuredEnvironment();

        $baseUrl = $this->config->get("esb.families.{$family->value}.{$environment->value}");

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new EsbConfigurationException(sprintf(
                'No ESB base URL is documented for family "%s" in environment "%s". '
                .'Confirm the host with the ESB team and set it in config/esb.php '
                .'or via the ESB_BASE_URL_%s override.',
                $family->value,
                $environment->value,
                strtoupper($family->value),
            ));
        }

        return rtrim($baseUrl, '/');
    }

    public function configuredEnvironment(): EsbEnvironment
    {
        $configured = $this->config->get('esb.environment');

        $environment = is_string($configured)
            ? EsbEnvironment::tryFrom($configured)
            : null;

        if ($environment === null) {
            throw new EsbConfigurationException(sprintf(
                'Unknown ESB environment "%s". Expected one of: %s.',
                is_scalar($configured) ? (string) $configured : gettype($configured),
                implode(', ', array_column(EsbEnvironment::cases(), 'value')),
            ));
        }

        return $environment;
    }
}
