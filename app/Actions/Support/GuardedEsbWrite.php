<?php

namespace App\Actions\Support;

use App\Integrations\ESB\Exceptions\EsbException;
use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Cache;

/**
 * The rules every non-idempotent ESB write follows, in one place.
 *
 * Introduced under ADR-015 once purchase request create, update, delete and
 * import joined the authorize/reject decision: five writes, one set of rules.
 *
 * - One write per lock key at a time, so a double-click does not become two
 *   calls. The key decides the scope: per document for writes to an existing
 *   one, per person for writes that create documents.
 * - No retry here. EsbClient already makes a single attempt for any write.
 * - An ambiguous failure (EsbException::mayHaveBeenApplied()) is handed to the
 *   caller's $onUnconfirmed before being rethrown, so the caller can audit it
 *   with its correlation id. A definite refusal is only rethrown.
 */
class GuardedEsbWrite
{
    /**
     * Seconds the lock outlives the slowest possible ESB exchange, so it
     * never expires while a call is still in flight.
     */
    private const LOCK_MARGIN_SECONDS = 30;

    public function __construct(private readonly Config $config) {}

    /**
     * @template T
     *
     * @param  Closure(): T  $write
     * @param  Closure(EsbException): void  $onUnconfirmed
     * @return T
     *
     * @throws WriteAlreadyInFlight when the lock is held
     * @throws EsbException when the write fails
     */
    public function run(string $lockKey, Closure $write, Closure $onUnconfirmed): mixed
    {
        $lock = Cache::lock($lockKey, $this->lockSeconds());

        if (! $lock->get()) {
            throw new WriteAlreadyInFlight($lockKey);
        }

        try {
            return $write();
        } catch (EsbException $exception) {
            if ($exception->mayHaveBeenApplied()) {
                $onUnconfirmed($exception);
            }

            throw $exception;
        } finally {
            $lock->release();
        }
    }

    private function lockSeconds(): int
    {
        return (int) $this->config->get('esb.timeouts.connect', 5)
            + (int) $this->config->get('esb.timeouts.request', 30)
            + self::LOCK_MARGIN_SECONDS;
    }
}
