<?php

namespace App\Actions\SalesPushes;

use App\Integrations\Contracts\SalesDataSink;
use App\Integrations\ESB\Exceptions\EsbAuthenticationException;
use App\Integrations\ESB\Exceptions\EsbAuthorizationException;
use App\Integrations\ESB\Exceptions\EsbBusinessRejectionException;
use App\Integrations\ESB\Exceptions\EsbConfigurationException;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use App\Integrations\ESB\Exceptions\EsbValidationException;
use App\Integrations\ESB\Responses\EsbFieldError;
use App\Models\SalesPush;

/**
 * Send one stored transaction to the provider and record the outcome.
 *
 * Three outcomes besides success:
 * - a refusal of the data is final (rejected): the POS has to fix it;
 * - a problem with the gateway's own ESB setup is final (failed): repeating
 *   will not fix a credential or a disabled family, a person must;
 * - anything whose outcome is unknown is rethrown for the queue to repeat.
 *   That is safe because the ESB overwrites a transaction pushed again with
 *   the same salesNum ([ESB-011]).
 */
class SendSalesPush
{
    public function __construct(private readonly SalesDataSink $sink) {}

    /**
     * @throws EsbException when the outcome is unknown and worth repeating
     */
    public function handle(SalesPush $push): void
    {
        if ($push->status !== SalesPush::STATUS_QUEUED) {
            return;
        }

        if ($push->hasNewerPush()) {
            $push->update(['status' => SalesPush::STATUS_SUPERSEDED]);

            return;
        }

        $push->update([
            'attempts' => $push->attempts + 1,
            'last_attempted_at' => now(),
        ]);

        try {
            $receipt = $this->sink->pushSalesData($push->payload);
        } catch (EsbConfigurationException) {
            $push->update(['status' => SalesPush::STATUS_FAILED, 'failure' => 'configuration']);

            return;
        } catch (EsbException $exception) {
            $push->fill([
                'failure' => $exception->kind(),
                'correlation_id' => $exception->context->correlationId,
                'errors' => array_map(
                    fn (EsbFieldError $error): array => ['field' => $error->field, 'message' => $error->message],
                    $exception->fieldErrors,
                ),
            ]);

            if ($this->isRefusal($exception)) {
                $push->update(['status' => SalesPush::STATUS_REJECTED]);

                return;
            }

            if ($this->needsAPerson($exception)) {
                $push->update(['status' => SalesPush::STATUS_FAILED]);

                return;
            }

            $push->save();

            throw $exception;
        }

        $push->update([
            'status' => SalesPush::STATUS_SUCCEEDED,
            'esb_status' => $receipt->status,
            'esb_sync_date' => $receipt->syncDate,
            'pushed_at' => now(),
            'failure' => null,
            'correlation_id' => null,
            'errors' => [],
        ]);
    }

    private function isRefusal(EsbException $exception): bool
    {
        return $exception instanceof EsbValidationException
            || $exception instanceof EsbNotFoundException
            || $exception instanceof EsbBusinessRejectionException;
    }

    /**
     * EsbClient has already retried an authentication failure once with a
     * fresh token; one that persists is a credential problem.
     */
    private function needsAPerson(EsbException $exception): bool
    {
        return $exception instanceof EsbAuthenticationException
            || $exception instanceof EsbAuthorizationException;
    }
}
