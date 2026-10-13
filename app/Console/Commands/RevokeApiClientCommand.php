<?php

namespace App\Console\Commands;

use App\Audit\AuditAction;
use App\Models\ApiClient;
use App\Models\AuditLog;
use Illuminate\Console\Command;

/**
 * Stop a client's token working. The row is kept so its sales push history
 * still names it.
 */
class RevokeApiClientCommand extends Command
{
    protected $signature = 'api-client:revoke
        {name : The client name given to api-client:create}';

    protected $description = 'Revoke an API client token';

    public function handle(): int
    {
        $client = ApiClient::query()->where('name', (string) $this->argument('name'))->first();

        if ($client === null) {
            $this->error(sprintf('No client named "%s".', $this->argument('name')));

            return self::FAILURE;
        }

        $client->update(['is_active' => false]);

        AuditLog::record(
            action: AuditAction::ApiClientRevoked,
            auditable: $client,
            metadata: ['name' => $client->name],
        );

        $this->info(sprintf('Revoked %s.', $client->name));

        return self::SUCCESS;
    }
}
