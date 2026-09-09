# API Integration — ERP ESB

## Purpose

This document is the authoritative project record for integration contracts with the ERP
ESB. Everything documented here must reflect the real ESB, not assumptions.

## Current Status

The vendor documentation has been imported and transcribed. Two ESB products are in
scope:

| Product | Version | Operations | Official documentation |
| --- | --- | --- | --- |
| ESB Core API | 2.0.0 | 363 across 41 groups | https://developers.esb.co.id/esb-core/ |
| ESB OMS API | 2.0.0 | 38 across 10 groups | https://developers.esb.co.id/esb-oms/ |

Imported on 2026-09-09.

The full operation index lives in:

- `docs/esb/esb-core-endpoints.md`
- `docs/esb/esb-oms-endpoints.md`

Those two files are **generated**, not hand-written. Regenerate them with
`docs/esb/generate-catalog.py` rather than editing them; the vendor publishes an apiDoc
site whose machine-readable source is `api_data.json` at each documentation root.

Per-field request/response schemas are not duplicated into this repository — the catalog
links every operation to its vendor documentation anchor. Transcribe an operation's
fields into a DTO only when that operation is actually implemented.

### What is still TBD

The vendor documentation does not state these, and they must be confirmed with the ESB
team before the relevant behavior is finalized:

- Which base URL family and environment this project is entitled to.
- Which credentials this project will use, and whether the login flow or a static API
  key is the intended mechanism for a server-to-server gateway (see Authentication).
- Rate limits and throttling behavior.
- Server-side timeout budgets per operation.
- Whether any operation is asynchronous or eventually consistent.
- Whether the ESB accepts a caller-supplied correlation/request ID header.
- Idempotency support for write operations (no idempotency key is documented).
- Clock-skew tolerance on token expiry.
- Whether `companyID` / `companyCode` scoping is implied by the credential or must be
  sent per request.

Do not invent values for the above. Mark assumptions `TBD` and isolate them.

## Base URLs

Base URLs are **not uniform**. The vendor declares a different base URL family per
endpoint group, and the same logical environment maps to different hosts per family.

| Family | Production | Staging | Staging INT | Used by |
| --- | --- | --- | --- | --- |
| Core | `https://services.esb.co.id/core` | `https://stg7.esb.co.id/core-stg` | `https://stg7.esb.co.id/core-int` | Almost all ESB Core groups |
| Voucher | `https://services.esb.co.id/voucher` | `https://stg7.esb.co.id/voucher-service-stg` | `https://stg7.esb.co.id/voucher-service` | Core `Online_Voucher` |
| FnB backend | `https://core-api.esb.co.id` | `https://stg7.esb.co.id/api-fnb-backend/web` | `https://stg7.esb.co.id/api-fnb-backend-int/web` | Most OMS groups; a few Core operations |
| POS / ESB Core web | `https://esbcore.co.id` | `https://int-erp.esb.co.id` | TBD | OMS `Master_POS` and parts of `Report` |

Consequences for implementation:

- Configuration must model base URLs as a **map of named families**, not a single
  `ESB_BASE_URL`.
- Each endpoint group must declare which family it belongs to.
- The generated catalogs record the production base URL declared for each group; treat
  that as the source of truth when wiring an adapter.
- A small number of operations declare no base URL at all in the vendor documentation
  (four Core "Flow" pages, and some OMS `Push_Sales_Data` / `Report` entries). These
  are `TBD` — confirm before implementing.

`{{base_url}}` in the vendor documentation and in the generated catalogs is a
placeholder for the family/environment base URL, not a literal.

## Authentication

ESB authentication is entirely separate from application user authentication. See
`docs/06-authentication-authorization.md`.

The vendor supports three mechanisms:

1. **Username and password** — HTTP basic auth. Access is scoped to the permissions
   granted to that ESB Core user.
2. **API key / static token** — sent as `Authorization: Bearer {static token}`. Issued
   and managed in ESB Core (https://erp.esb.co.id) or obtained from the project's PIC.
   Privileges are configurable per key.
3. **Access token** — obtained from the login endpoint, sent as
   `Authorization: Bearer {access token}`.

An access token and a static token are **not interchangeable** despite sharing the
`Bearer` scheme. Configuration must record which mechanism a connection uses.

### Login

```
### [ESB-AUTH-001] Login

Purpose:        Obtain an access token and refresh token.
Method:         POST
Path:           {{base_url}}/auth/login
Family:         Core (also documented under OMS with the same Core base URLs)
Authentication: none (this is the credential exchange)
Headers:        Content-Type: application/json
Body:           username (String, required)
                password (String, required)
Response:       standard envelope; result contains username, fullName, companyID,
                companyCode, companyName, accessToken, refreshToken, flagActive,
                logInfo{logID, username, loginTime, logoutTime}
Errors:         EC03100032 — "Invalid username or password, please check again"
Timeout:        config('esb.timeouts') -- our own budget, vendor budget TBD
Retry Policy:   do not retry on EC03100032; retry only on transport failure
Idempotency:    not applicable
Laravel Adapter: App\Integrations\ESB\Auth\TokenManager
Tests:          tests/Feature/Integrations/ESB/TokenManagerTest.php
```

### Refresh

```
### [ESB-AUTH-002] Refresh Token

Purpose:        Exchange a refresh token for a new access token.
Method:         GET
Path:           {{base_url}}/auth/refresh
Family:         Core
Authentication: Authorization: Bearer {refresh token}
Headers:        Authorization
Body:           none
Response:       same envelope and result shape as login
Errors:         EC03100001 — "Invalid Token"
Timeout:        config('esb.timeouts') -- our own budget, vendor budget TBD
Retry Policy:   do not retry on EC03100001; re-login instead
Idempotency:    not applicable
Laravel Adapter: App\Integrations\ESB\Auth\TokenManager
Tests:          tests/Feature/Integrations/ESB/TokenManagerTest.php
```

Note the refresh endpoint is a `GET` that carries the refresh token in the
`Authorization` header — it does not take a body.

### Token lifetime

- Access token: **expires after 1 hour**.
- Refresh token: **expires after 24 hours**.

Both are JWTs. The access token payload observed in the vendor examples carries
`companyCode`, `companyID`, `dbName`, `serverCode`, `userRoleID`, `username`, and `exp`.
Do not parse the JWT for authorization decisions — the ESB is the authority. Reading
`exp` to schedule proactive refresh is acceptable; treat it as advisory and always
handle a `EC03100001` response.

### Critical constraint: single active session

> "A successful API login will log you out of any existing ESB Core session using the
> same credentials, vice versa."

This is a hard constraint on the gateway's design:

- **Never** log in per user request or per job. Concurrent logins with the same
  credentials will invalidate each other and cause intermittent, hard-to-diagnose
  `Unauthorized` failures across the whole application.
- Tokens must be **cached and shared** across web requests and queue workers — Redis is
  the appropriate store (see ADR-005).
- Token acquisition and refresh must be **mutually exclusive** — take a lock so only one
  process performs a login or refresh at a time, and other processes wait for the
  result rather than logging in themselves.
- A human logging into the ESB Core web UI with the same credentials will invalidate the
  gateway's token. Use dedicated integration credentials, not a person's account.
- Prefer a static API key over the login flow if the ESB team confirms one is available
  for this project — it sidesteps the single-session problem entirely. This is `TBD`.

### Token storage

- Cached in Redis, server-side only.
- Never returned to React, never written to logs, never placed in Inertia props.
- Encrypted at rest where the deployment requires it.
- Invalidate the cached token immediately on `EC03100001`.

## Response Envelope

Every documented operation returns the same envelope:

```json
{
    "path": "https://stg7.esb.co.id/core/product/list",
    "timestamp": "2024-05-28 18:20:51",
    "status": "ok",
    "code": "EC03100000",
    "message": "OK",
    "result": { },
    "errors": null
}
```

| Field | Type | Meaning |
| --- | --- | --- |
| `path` | String | The URL the ESB resolved the request to |
| `timestamp` | String | `Y-m-d H:i:s`, no timezone offset — see Timezone below |
| `status` | String | `ok` on success; `fail` or `failed` on error |
| `code` | String | ESB status code — the reliable signal, see below |
| `message` | String | Human-readable message, **not stable** |
| `result` | Object/Array/null | Payload |
| `errors` | Array/null | Per-field error detail |

**Classify on `code`, never on `message`.** The vendor documentation contains 942
distinct code/message pairs for only 29 distinct codes — messages are free-form,
frequently interpolate document numbers (`Successfully save Goods Receipt
#GR202406120006`), and vary in capitalisation (`OK`, `Ok`, `ok`).

`status` is also unreliable as a discriminator: both `fail` and `failed` appear, and
some response examples put unrelated domain values in that field. Use `code`.

**Do not rely on the HTTP status code alone.** The vendor documents errors through the
envelope. Assume a `200` response may still carry a failure code, and always parse the
envelope.

### Paginated list responses

List operations wrap their payload:

```json
"result": {
    "page": 1,
    "limit": 20,
    "count": 92,
    "data": [ ],
    "prev": "",
    "next": "https://stg7.esb.co.id/core/product/list?page=2&limit=20"
}
```

Common query parameters: `page` (default 1, minimum 1), `limit` (default 20, minimum 1,
**maximum 100** per `EC03100040`), `sort`, and per-resource filters. `prev` and `next`
are absolute URLs containing the ESB host — never pass them through to the browser;
re-derive pagination from `page`/`limit` on our side.

### Timezone

`timestamp` is formatted `Y-m-d H:i:s` with no offset. The `logInfo.loginTime` example
carries `+07:00`, indicating Asia/Jakarta. Treat bare ESB timestamps as Asia/Jakarta and
convert explicitly on ingest — do not let them adopt the application's default timezone
implicitly. Date parameters use `Y-m-d` (`EC03100036` enforces this).

## Error Code Catalog

Transcribed from the vendor documentation. The message column shows a representative
message, not the only one.

| Code | Representative message | Application classification |
| --- | --- | --- |
| `EC03100000` | OK | success |
| `EC03100001` | Unauthorized / Invalid Token | authentication failure — invalidate cached token |
| `EC011401` | Unauthorized | authentication failure |
| `EC03100002` | you did not have access to this resource | authorization failure |
| `EC03100005` | Access denied, cannot update … | authorization failure |
| `EC03100009` | No Access to Transaction | authorization failure |
| `EC03100003` | Validation Error | validation error |
| `EC03100023` | field X is required and cannot be null | validation error — missing field |
| `EC03100024` | field X cannot consist less than N | validation error — min items |
| `EC03100025` | field X cannot consist more than N | validation error — max length |
| `EC03100026` | sort can only consist of alphabet | validation error — sort |
| `EC03100027` | qty value must greater than 0 | validation error — range |
| `EC03100036` | X does not implement Y-m-d date format | validation error — date format |
| `EC03100039` | field page must have value minimum of 1 | validation error — range |
| `EC03100040` | limit value must less than or equal 100 | validation error — pagination cap |
| `EC03100042` | X can only consist of alphabet and number | validation error — charset |
| `EC03100043` | X can only consist of alphabet, number and space | validation error — charset |
| `EC03100045` | required if user have cost center | validation error — conditional |
| `EC03100046` | X cannot contain emojis | validation error — charset |
| `EC03100004` | Not Found | not found |
| `EC03100038` | X data is not found | not found — referenced entity |
| `EC03100029` | X data already taken by other data | business rejection — conflict |
| `EC031Custom` | currencyID is different with references currency | business rejection |
| `EC04100022` | Budget … is not found / budget amount will be minus | business rejection (budget domain) |
| `EC03100032` | Unknown Error | unknown integration error |
| `EC03110000` | Field X must have value minimum of 0 | validation error (voucher domain) |
| `EC04100001` | Validation Error | validation error |
| `EC0110` | (OMS) validation payload | validation error (OMS) |
| `EC0118` | (OMS) Undefined index: menuTemplateID | upstream defect — treat as unknown error |

Notes:

- `EC03100001` covers both "Unauthorized" and "Invalid Token", and in at least one Core
  example is also used for a business rejection ("This transaction is linked with
  Product Sales …"). Handle the authentication case first, but do not assume the code is
  exclusively authentication.
- `EC03100032` is documented both as a generic "Unknown Error" and as specific business
  rejections such as "this transaction date has exceed close period date". Log the
  message for diagnostics; do not branch business logic on it.
- `EC0118` ("Undefined index") is an upstream PHP notice leaking through the envelope.
  Treat it as an upstream defect and report it to the ESB team.
- Bare `401` and `max` appear as `code` values in a small number of examples. These look
  like documentation errors; handle unknown codes defensively.

## Error Normalization

Map every ESB outcome onto exactly one application-level error class:

| Application error | Trigger |
| --- | --- |
| `EsbValidationException` | validation codes above |
| `EsbAuthenticationException` | `EC03100001`, `EC011401`, HTTP 401 |
| `EsbAuthorizationException` | `EC03100002`, `EC03100005`, `EC03100009` |
| `EsbNotFoundException` | `EC03100004`, `EC03100038` |
| `EsbBusinessRejectionException` | `EC03100029`, `EC031Custom`, `EC04100022` |
| `EsbTimeoutException` | connect/read timeout |
| `EsbConnectionException` | DNS/TLS/transport failure |
| `EsbUpstreamException` | HTTP 5xx, or an envelope that cannot be classified |
| `EsbMalformedResponseException` | non-JSON body, or missing `code` |
| `EsbUnknownException` | `EC03100032` and any unrecognised code |

Every exception carries the correlation ID, operation identifier, ESB `code`, and
duration. The raw `message` is retained for logs and never rendered verbatim to end
users — it can contain internal document numbers and, in the `EC0118` case, upstream
stack detail.

Treat an unrecognised code as `EsbUnknownException` and log it at warning level so new
vendor codes surface rather than being silently swallowed.

## Implementation

The integration layer lives under `app/Integrations/ESB/`:

| Class | Responsibility |
| --- | --- |
| `EsbFamily`, `EsbEnvironment` | The base URL families and environments |
| `BaseUrlResolver` | Resolves a family's base URL; throws rather than guessing |
| `Responses\EsbEnvelope` | Parses the envelope, pagination, and timestamps |
| `ErrorCode` | The code-to-exception table transcribed from this document |
| `ErrorClassifier` | Turns a failed envelope into an application exception |
| `Exceptions\*` | The normalized error hierarchy |
| `Auth\TokenManager` | Static-token and login modes, shared cache, lock (ADR-009) |
| `Clients\EsbClient` | The single path to the ESB: routing, timeouts, retry, logging |
| `App\Models\IntegrationLog` | Per-call operational record |

Configuration is in `config/esb.php`, which is the only place that reads ESB environment
variables.

Adapters for individual ERP operations build on `EsbClient` and own their own request and
response mapping. The client itself knows nothing about any particular operation.

## Integration Principles

- Laravel is the only component that communicates with the ESB (ADR-006).
- Browser clients never call the ESB directly.
- Credentials and tokens remain server-side.
- All outbound calls use explicit connect and overall timeouts.
- Retries are used only where the operation is safe to retry.
- Every integration operation has a correlation ID.
- Errors preserve diagnostic context while exposing safe user-facing messages.
- ESB responses are untrusted input — validate structure before persisting or acting on
  it (`docs/10-security.md`).

## Retry and Idempotency

No idempotency key is documented for any write operation. Until the ESB team confirms
otherwise, assume **writes are not idempotent**.

| Operation class | Retry |
| --- | --- |
| `GET` (list, detail, browse, dropdown, report) | safe to retry with backoff |
| `POST` / `PUT` / `PATCH` / `DELETE` | do not retry automatically on timeout or 5xx |
| Authentication failure (`EC03100001`) | refresh token once, then retry once; then fail |
| Validation / authorization / not found / business rejection | never retry |

A write that times out is in an unknown state — the ESB may have committed it. Surface
it for human resolution and, where the resource exposes a `GET` by document number, use
that to determine the actual outcome before any manual retry. Do not build blind
automatic retry into write paths.

Document-number-keyed paths (e.g. `/purchase/purchase-request/PR202503260005`) make this
reconciliation possible for most transactional groups.

## Correlation IDs

The ESB does **not** document a correlation or request ID header (`TBD` — ask whether
one is accepted). Until confirmed:

- Generate a correlation ID per operation on our side.
- Record it in application logs, integration logs, and queue jobs
  (`docs/11-logging-monitoring.md`).
- Do not send an undocumented header to the ESB without confirmation.
- Correlate ESB-side activity using the returned `path`, `timestamp`, and — for
  transactional operations — the document number in the response.

## Logging

Never log:

- ESB usernames' passwords, or the credentials themselves;
- static API keys;
- access tokens or refresh tokens;
- `Authorization` headers;
- full sensitive personal or financial payloads.

The login and refresh responses contain `accessToken` and `refreshToken` in the body —
these responses must be redacted before any logging, not just their headers.

Log:

- correlation ID;
- operation identifier and endpoint group;
- base URL family and environment;
- ESB `code`;
- upstream HTTP status;
- latency;
- retry count;
- timestamp.

## Endpoint Documentation Template

Use this template when documenting an operation the project actually implements. The
generated catalogs index every operation; this template records the decisions we make
about one.

```
### [ESB-XXX] Operation Name

Purpose:         
Method:          
Path:            
Family:          Core | Voucher | FnB backend | POS
Authentication:  
Request Headers: 
Request Body:    
Response:        
Errors:          
Timeout:         
Retry Policy:    
Idempotency:     
Laravel Adapter: 
Tests:           
```

## Contract Change Procedure

When the ESB changes:

1. Re-download `api_data.json` and regenerate the catalogs with
   `docs/esb/generate-catalog.py`.
2. Diff the regenerated catalogs to identify added, removed, or changed operations.
3. Update this document — base URLs, authentication, envelope, error codes.
4. Update integration DTOs and mappers.
5. Update the adapter/client.
6. Update tests.
7. Review backward compatibility.
8. Update `docs/project-state.md`.
9. Record an ADR in `DECISIONS.md` if the architecture is materially affected.
