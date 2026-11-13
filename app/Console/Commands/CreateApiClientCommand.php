<?php

namespace App\Console\Commands;

use App\Audit\AuditAction;
use App\Models\ApiClient;
use App\Models\AuditLog;
use Illuminate\Console\Command;

/**
 * Issue a token to a system that calls the gateway's /api routes, such as a
 * POS (ADR-022).
 */
class CreateApiClientCommand extends Command
{
    protected $signature = 'api-client:create
        {name : A unique name for the client, e.g. "pos-rxt"}
        {--scope=* : What it may call: sales-data (default) or closing-totals}';

    protected $description = 'Issue an API token to a POS or other client';

    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));

        if ($name === '' || mb_strlen($name) > 100) {
            $this->error('The name must be 1 to 100 characters.');

            return self::FAILURE;
        }

        if (ApiClient::query()->where('name', $name)->exists()) {
            $this->error(sprintf('A client named "%s" already exists. Revoke it or choose another name.', $name));

            return self::FAILURE;
        }

        $scopes = array_values(array_filter(
            array_map(fn (mixed $scope): string => trim((string) $scope), (array) $this->option('scope')),
            fn (string $scope): bool => $scope !== '',
        ));
        $scopes = $scopes === [] ? [ApiClient::SCOPE_SALES_DATA] : $scopes;
        $unknown = array_diff($scopes, ApiClient::SCOPES);

        if ($unknown !== []) {
            $this->error(sprintf(
                'Unknown scope "%s". Use one of: %s.',
                implode('", "', $unknown),
                implode(', ', ApiClient::SCOPES),
            ));

            return self::FAILURE;
        }

        [$client, $token] = ApiClient::issue($name, $scopes);

        AuditLog::record(
            action: AuditAction::ApiClientIssued,
            auditable: $client,
            metadata: ['name' => $client->name, 'scopes' => $client->scopes()],
        );

        $this->info(sprintf(
            'Issued a token to %s (scopes: %s). It is shown once; store it in the client now:',
            $client->name,
            implode(', ', $client->scopes()),
        ));
        $this->line($token);

        return self::SUCCESS;
    }
}
