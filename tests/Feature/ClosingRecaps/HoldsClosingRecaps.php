<?php

namespace Tests\Feature\ClosingRecaps;

use App\Actions\ClosingRecaps\ReceiveClosingRecap;
use App\Authorization\Role;
use App\Models\ApiClient;
use App\Models\ClosingRecap;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Fixtures\ClosingTotals;

/**
 * Hold recaps the way POST /api/v1/closing-totals does, without HTTP.
 */
trait HoldsClosingRecaps
{
    private ?ApiClient $syncApi = null;

    /**
     * @param  array<string, mixed>  $overrides  Top-level payload fields.
     */
    protected function holdRecap(array $overrides = []): ClosingRecap
    {
        $this->syncApi ??= ApiClient::issue('sync-api', [ApiClient::SCOPE_CLOSING_TOTALS])[0];

        // A fresh messageId unless the test names one of its own: the
        // fixture's fixed id would make a second recap a "different body".
        $messageId = ($overrides['messageId'] ?? ClosingTotals::MESSAGE_ID) === ClosingTotals::MESSAGE_ID
            ? (string) Str::uuid()
            : $overrides['messageId'];
        $payload = ClosingTotals::payload([...$overrides, 'messageId' => $messageId]);
        $outlet = mb_strtoupper(trim((string) $payload['outlet']['code']));

        $receipt = app(ReceiveClosingRecap::class)->handle(
            $this->syncApi,
            $outlet,
            $payload,
            ClosingTotals::encode($payload),
        );

        return $receipt->recap ?? ClosingRecap::query()
            ->where('outlet_code', $outlet)
            ->whereDate('production_date', (string) $payload['productionDate'])
            ->sole();
    }

    protected function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }
}
