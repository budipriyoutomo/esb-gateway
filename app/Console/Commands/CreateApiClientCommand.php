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
        {name : A unique name for the client, e.g. "pos-rxt"}';

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

        [$client, $token] = ApiClient::issue($name);

        AuditLog::record(
            action: AuditAction::ApiClientIssued,
            auditable: $client,
            metadata: ['name' => $client->name],
        );

        $this->info(sprintf('Issued a token to %s. It is shown once; store it in the client now:', $client->name));
        $this->line($token);

        return self::SUCCESS;
    }
}
