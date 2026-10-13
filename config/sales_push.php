<?php

/**
 * Inbound sales data from POS systems, pushed on to the ESB (ADR-022).
 *
 * POS clients call POST /api/v1/sales-data with an API client token issued by
 * `php artisan api-client:create`. The gateway stores the transaction and
 * pushes it to ESB Push Sales Data V2 from the queue.
 */
return [

    /*
    |---------------------------------------------------------------------
    | Rate limit
    |---------------------------------------------------------------------
    |
    | Requests per minute, per API client. Our own limit: the ESB's rate
    | limits are undocumented (TBD in docs/05-api-integration.md).
    |
    */

    'rate_limit_per_minute' => (int) env('SALES_PUSH_RATE_LIMIT_PER_MINUTE', 120),

    /*
    |---------------------------------------------------------------------
    | Body size
    |---------------------------------------------------------------------
    |
    | Largest request body accepted, in kilobytes. One transaction is a few
    | kilobytes; the limit stops a runaway client filling the table.
    |
    */

    'max_body_kb' => (int) env('SALES_PUSH_MAX_BODY_KB', 512),

    /*
    |---------------------------------------------------------------------
    | Queue
    |---------------------------------------------------------------------
    |
    | A push whose outcome is unknown (timeout, connection failure, upstream
    | error) is tried again: the ESB overwrites a transaction pushed with the
    | same salesNum, so repeating one is safe. `tries` includes the first
    | attempt. `backoff` is seconds between attempts.
    |
    */

    'queue' => env('SALES_PUSH_QUEUE', 'default'),

    'tries' => (int) env('SALES_PUSH_TRIES', 5),

    'backoff' => [30, 120, 300, 900],

];
