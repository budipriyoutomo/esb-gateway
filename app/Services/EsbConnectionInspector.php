<?php

namespace App\Services;

use App\Integrations\ESB\BaseUrlResolver;
use App\Integrations\ESB\Clients\EsbClient;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbConfigurationException;
use App\Integrations\ESB\Exceptions\EsbException;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Reports what ESB connectivity is configured, and probes it on request.
 *
 * ESB connections are configuration, not database records (ADR-013). This
 * reads config/esb.php and never exposes a credential.
 */
class EsbConnectionInspector
{
    public function __construct(
        private readonly Config $config,
        private readonly BaseUrlResolver $baseUrlResolver,
        private readonly EsbClient $client,
    ) {}

    /**
     * One row per base URL family.
     *
     * @return list<array{
     *     family: string,
     *     baseUrl: string|null,
     *     configured: bool,
     *     probeAvailable: bool,
     *     note: string|null
     * }>
     */
    public function connections(): array
    {
        $connections = [];

        foreach (EsbFamily::cases() as $family) {
            $baseUrl = null;
            $note = null;

            try {
                $baseUrl = $this->baseUrlResolver->resolve($family);
            } catch (EsbConfigurationException) {
                // The vendor documents no host for this family in the current
                // environment. Saying so beats showing a plausible-looking URL
                // nobody confirmed.
                $note = 'No base URL is documented for this family in this environment.';
            }

            $probe = $this->probeFor($family);

            $connections[] = [
                'family' => $family->value,
                'baseUrl' => $baseUrl,
                'configured' => $baseUrl !== null,
                'probeAvailable' => $probe !== null,
                'note' => $note ?? ($probe === null
                    ? 'The vendor documents no health endpoint for this family, so connectivity cannot be probed.'
                    : null),
            ];
        }

        return $connections;
    }

    /**
     * Probe a family's connectivity.
     *
     * `reachable` is null when there is nothing safe to call. An authorization
     * failure still counts as reachable: the ESB answered, so the host is up
     * and the network is fine -- what failed is the gateway credential's
     * privileges. Reporting that as unreachable would send someone to check
     * firewalls instead of ESB user permissions.
     *
     * @return array{reachable: bool|null, outcome: string|null, message: string}
     */
    public function probe(EsbFamily $family): array
    {
        $probe = $this->probeFor($family);

        if ($probe === null) {
            return [
                'reachable' => null,
                'outcome' => null,
                'message' => 'No connectivity probe is documented for this family.',
            ];
        }

        try {
            $this->client->get(
                $family,
                $probe['path'],
                $probe['query'],
                operation: 'connection.probe',
            );
        } catch (EsbConfigurationException $exception) {
            return [
                'reachable' => false,
                'outcome' => 'configuration',
                'message' => $exception->getMessage(),
            ];
        } catch (EsbException $exception) {
            return [
                'reachable' => $this->answeredDespiteFailing($exception),
                'outcome' => $exception->kind(),
                'message' => $exception->safeMessage(),
            ];
        }

        return [
            'reachable' => true,
            'outcome' => null,
            'message' => 'The ERP service responded successfully.',
        ];
    }

    /**
     * Did the ESB answer at all?
     *
     * Anything other than a transport-level failure means it did.
     */
    private function answeredDespiteFailing(EsbException $exception): bool
    {
        return ! in_array($exception->kind(), ['connection', 'timeout'], true);
    }

    /**
     * @return array{path: string, query: array<string, mixed>}|null
     */
    private function probeFor(EsbFamily $family): ?array
    {
        $probe = $this->config->get("esb.probes.{$family->value}");

        if (! is_array($probe) || ! isset($probe['path']) || ! is_string($probe['path'])) {
            return null;
        }

        return [
            'path' => $probe['path'],
            'query' => is_array($probe['query'] ?? null) ? $probe['query'] : [],
        ];
    }
}
