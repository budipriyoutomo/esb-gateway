<?php

/**
 * ERP ESB integration configuration.
 *
 * Base URLs are modelled as named families rather than a single ESB_BASE_URL,
 * because the vendor declares a different host per endpoint group and the same
 * logical environment maps to different hosts per family (ADR-010). See
 * docs/05-api-integration.md for where each value comes from, and
 * docs/esb/*-endpoints.md for which family an endpoint group belongs to.
 *
 * This is the only place that reads ESB environment variables.
 */
return [

    /*
    |---------------------------------------------------------------------
    | Environment
    |---------------------------------------------------------------------
    |
    | Which ESB environment this installation talks to: production, staging,
    | or staging_int. Which one this project is entitled to is still TBD with
    | the ESB team.
    |
    */

    'environment' => env('ESB_ENV', 'staging_int'),

    /*
    |---------------------------------------------------------------------
    | Authentication
    |---------------------------------------------------------------------
    |
    | "static_token" sends a long-lived API key as a bearer token. "login"
    | exchanges a username and password for an access token.
    |
    | static_token is preferred where the ESB team can issue a key: the ESB
    | permits only one active session per credential, so the login flow needs a
    | shared, locked token cache to stop concurrent logins invalidating each
    | other (ADR-009).
    |
    */

    'auth' => [
        'mechanism' => env('ESB_AUTH_MECHANISM', 'static_token'),
        'static_token' => env('ESB_STATIC_TOKEN'),
        'username' => env('ESB_USERNAME'),
        'password' => env('ESB_PASSWORD'),

        // Where the login flow authenticates. The vendor documents the auth
        // endpoints under the Core base URLs for both ESB Core and ESB OMS.
        'family' => 'core',
    ],

    /*
    |---------------------------------------------------------------------
    | Timeouts
    |---------------------------------------------------------------------
    |
    | Seconds. The vendor documents no server-side timeout budget, so these are
    | our own limits and are marked TBD in docs/05-api-integration.md.
    |
    */

    'timeouts' => [
        'connect' => (int) env('ESB_CONNECT_TIMEOUT', 5),
        'request' => (int) env('ESB_TIMEOUT', 30),
    ],

    /*
    |---------------------------------------------------------------------
    | Retry
    |---------------------------------------------------------------------
    |
    | Reads are retried with exponential backoff from this base delay. Writes
    | are never retried automatically: no idempotency key is documented, so a
    | repeated write can create a duplicate ERP document.
    |
    */

    'retry' => [
        'base_delay_ms' => (int) env('ESB_RETRY_BASE_DELAY_MS', 200),
    ],

    /*
    |---------------------------------------------------------------------
    | Base URLs
    |---------------------------------------------------------------------
    |
    | Transcribed from the vendor documentation. A null entry means the vendor
    | documents no host for that combination; resolving it throws rather than
    | falling back, so an unconfirmed value cannot quietly send staging traffic
    | at production ERP data.
    |
    */

    'families' => [

        'core' => [
            'production' => 'https://services.esb.co.id/core',
            'staging' => 'https://stg7.esb.co.id/core-stg',
            'staging_int' => 'https://stg7.esb.co.id/core-int',
        ],

        'voucher' => [
            'production' => 'https://services.esb.co.id/voucher',
            'staging' => 'https://stg7.esb.co.id/voucher-service-stg',
            'staging_int' => 'https://stg7.esb.co.id/voucher-service',
        ],

        'fnb_backend' => [
            'production' => 'https://core-api.esb.co.id',
            'staging' => 'https://stg7.esb.co.id/api-fnb-backend/web',
            'staging_int' => 'https://stg7.esb.co.id/api-fnb-backend-int/web',
        ],

        'pos' => [
            'production' => 'https://esbcore.co.id',
            'staging' => 'https://int-erp.esb.co.id',
            // TBD -- the vendor documents no staging-int host for this family.
            'staging_int' => null,
        ],

    ],

    /*
    |---------------------------------------------------------------------
    | Base URL overrides
    |---------------------------------------------------------------------
    |
    | Point a single family somewhere else without changing the table above.
    | Takes precedence over the environment lookup.
    |
    */

    'overrides' => [
        'core' => env('ESB_BASE_URL_CORE'),
        'voucher' => env('ESB_BASE_URL_VOUCHER'),
        'fnb_backend' => env('ESB_BASE_URL_FNB_BACKEND'),
        'pos' => env('ESB_BASE_URL_POS'),
    ],

];
