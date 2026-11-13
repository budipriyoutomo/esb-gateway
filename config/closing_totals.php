<?php

/**
 * Closing recaps from sync-api Maharasa (ADR-032).
 *
 * sync-api calls POST /api/v1/closing-totals with an API client token issued
 * by `php artisan api-client:create sync-api --scope=closing-totals`. The
 * gateway holds the recap; nothing reaches the ESB on receipt.
 */
return [

    /*
    | Requests per minute, per API client. The sender sends about one recap
    | per outlet per day, retrying at most twice.
    */

    'rate_limit_per_minute' => (int) env('CLOSING_TOTALS_RATE_LIMIT_PER_MINUTE', 60),

    /*
    | Largest request body accepted, in kilobytes. A recap is "rarely more
    | than 200 items", a few tens of kilobytes.
    */

    'max_body_kb' => (int) env('CLOSING_TOTALS_MAX_BODY_KB', 512),

    /*
    | Most items accepted in one recap.
    */

    'max_items' => (int) env('CLOSING_TOTALS_MAX_ITEMS', 2000),

];
