<?php

namespace App\Support;

use App\Models\IntegrationLog;

/**
 * The values that can appear in `integration_logs.outcome`.
 *
 * Either "success" or an EsbException's kind(). Kept here so the log viewer's
 * filter, its validation, and the dashboard all read from one list.
 * IntegrationOutcomeTest fails the build if this drifts from the exception
 * hierarchy in either direction.
 */
final class IntegrationOutcome
{
    /**
     * Failure outcomes, in rough order of how actionable they are.
     *
     * @var list<string>
     */
    private const FAILURES = [
        'validation',
        'authentication',
        'authorization',
        'not_found',
        'business_rejection',
        'timeout',
        'connection',
        'upstream',
        'malformed_response',
        'unknown',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [IntegrationLog::OUTCOME_SUCCESS, ...self::FAILURES];
    }

    /**
     * @return list<string>
     */
    public static function failures(): array
    {
        return self::FAILURES;
    }
}
