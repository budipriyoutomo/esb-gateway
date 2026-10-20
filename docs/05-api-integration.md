# API Integration — ERP ESB

## Purpose

This document is the authoritative project record for integration contracts with the ERP
ESB. Everything documented here must reflect the real ESB, not assumptions.

## Current Status

The vendor documentation has been imported and transcribed. Two ESB products are in
scope:

| Product | Version | Operations | Official documentation |
| --- | --- | --- | --- |
| ESB Core API | 2.0.0 | 1224 across 116 groups | https://developers.esb.co.id/esb-core/ |
| ESB OMS API | 2.0.0 | 38 across 10 groups | https://developers.esb.co.id/esb-oms/ |

First imported on 2026-09-09; catalogs last regenerated on 2026-09-30.

The full operation index lives in:

- `docs/esb/esb-core-endpoints.md`
- `docs/esb/esb-oms-endpoints.md`

Those two files are **generated**, not hand-written. Regenerate them with
`docs/esb/generate-catalog.py` rather than editing them. The two products now publish
different machine-readable sources:

| Product | Source | Docs link format |
| --- | --- | --- |
| ESB Core | OpenAPI 3.0, `https://developers.esb.co.id/esb-core/openapi.json` (Stoplight Elements site) | `#/paths/{slug}/{method}` |
| ESB OMS | apiDoc, `https://developers.esb.co.id/esb-oms/api_data.json` + `api_project.json` | `#api-{Group}-{name}` |

### Catalog sync 2026-09-30 — what changed

ESB Core's apiDoc `api_data.json` has returned 404 since 2026-09-25. The generator was
ported to read the OpenAPI document for Core, and both catalogs were regenerated.
`esb:import-catalog` accepts both link formats: an OpenAPI operation is identified as
`core:{METHOD} {path}`, and an apiDoc operation keeps `{product}:{Group}:{name}@{version}`.

- **ESB OMS: no change.** Same 38 operations; only the generation date moved.
- **ESB Core: 355 callable operations → 1224.** Compared by method and normalised path
  (the old apiDoc wrote parameters as `:id`, `[id]`, or `{{id}}`):
  - **333 still documented.** This includes everything the gateway calls:
    `/auth/login`, `/auth/refresh`, `/product/list`, and the purchase request operations
    (`[ESB-001]`–`[ESB-007]`), with the same fields.
  - **891 newly documented.** Examples: the Purchase Request group grew from 7 to 19
    operations. There are new groups such as Asset (60), User (29), and Common (26), and
    Report grew by 60.
  - **22 no longer in the Core document.** Three were only renamed: the old `simple-transfer`
    authorize/reject paths lacked braces, and `PATCH /inventory/receipt/{receiptNum}/reject`
    is now `PATCH /receipt/{receiptNum}/reject`. The rest are gone from the Core document:
    Online Voucher (`POST /v1/online-voucher/`, `/burn`, `/validate`, on the voucher host);
    `/corev1/general-ledger` and `/corev1/master/product` (the FnB backend host); and 14
    Core paths. Those 14 are Master Product `validate-delete` (product and product
    detail), `validate-barcode-number`, `browse-category`, `browse-sub-category`,
    `export-template`, `pull`, and `push`;
    `supplier/export/BANK`; `product/bom/upload/template`; the goods-delivery and
    goods-receipt attachment uploads; `PUT /inventory/goods-transfer-request/{transferNum}`;
    and the simple-transfer attachment upload. None of them is used by the gateway.
  - The four Core "Flow" narrative pages are gone; OMS still has its "Flow" page.
- **Base URLs.** The Core document declares one set of servers for every operation:
  Production `https://services.esb.co.id/core`, Staging `https://stg7.esb.co.id/core-stg`,
  and Staging (INT) `https://stg7.esb.co.id/core-int`. No Core operation declares a
  different host any more, so the catalog maps all of Core to the `core` family. The
  voucher host now has no documented operations at all (see Base URLs).
- **Contradictions inside the new Core document** (`TBD`, confirm with the ESB team; the
  gateway is unchanged). The intro says the envelope `code` "mirrors the HTTP status"
  (`200`) and that pagination returns `dataCount`. Every per-operation example instead
  shows `code: "EC03100000"` and `result.count`. Real staging responses seen by this
  project use the `EC…` codes (`EC03100001`, 2026-09-29), so the examples are the ones
  that match the running service. This follows the rule from `[ESB-001]`.
- **Refresh.** Besides `GET /auth/refresh`, the document now lists
  `POST /auth/refresh-token` with body `{ "token": "<refresh token>" }`. Both are marked
  as needing a Bearer token. `GET /auth/refresh` is described as "using the authenticated
  user's current session", which does not say whether the bearer is the access token or
  the refresh token. The gateway still sends the refresh token to `GET /auth/refresh` as
  before. See `[ESB-AUTH-002]`.
- **The new intro no longer states** the 1-hour and 24-hour token lifetimes or the
  single-active-session rule. They are not contradicted either, so the rules below still
  stand.

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
| Voucher | `https://services.esb.co.id/voucher` | `https://stg7.esb.co.id/voucher-service-stg` | `https://stg7.esb.co.id/voucher-service` | Formerly Core `Online_Voucher`; no longer in the Core document (2026-09-30), TBD |
| FnB backend | `https://core-api.esb.co.id` | `https://stg7.esb.co.id/api-fnb-backend/web` | `https://stg7.esb.co.id/api-fnb-backend-int/web` | Most OMS groups; a few Core operations |
| POS / ESB Core web | `https://esbcore.co.id` | `https://int-erp.esb.co.id` | TBD | OMS `Master_POS` and parts of `Report` |

Consequences for implementation:

- Configuration must model base URLs as a **map of named families**, not a single
  `ESB_BASE_URL`.
- Each endpoint group must declare which family it belongs to.
- The generated catalogs record the production base URL declared for each group; treat
  that as the source of truth when wiring an adapter.
- A small number of operations declare no base URL at all in the vendor documentation
  (some OMS `Push_Sales_Data` / `Report` entries). These are `TBD` — confirm before
  implementing.
- Since 2026-09-30 the ESB Core OpenAPI document declares only the Core hosts. Operations
  that the old apiDoc placed on the voucher or FnB backend hosts are either gone from it
  or now documented under Core. Before wiring any non-Core family, confirm where the
  vendor documents it now.

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

Since 2026-09-30 the Core OpenAPI document also lists `POST /auth/refresh-token` with a
JSON body `{ "token": "<refresh token>" }`, alongside this `GET`. It describes the `GET`
as refreshing "using the authenticated user's current session", which is ambiguous about
which token is the bearer. Nothing was changed: `TokenManager` still calls the `GET` with
the refresh token, and falls back to a fresh login when refresh fails. `TBD` — ask the
ESB team which refresh operation a server-to-server client should use.

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

## Documented Operations

Operations are transcribed here only once they are implemented. The generated catalogs
in `docs/esb/` index all 1262; this section records the decisions we made about the ones
we actually call.

```
### [ESB-001] Product List

Purpose:         Read product master data from ESB Core.
Method:          GET
Path:            {{base_url}}/product/list
Family:          Core
Authentication:  Authorization: Bearer {access token or static key}
Request Headers: Authorization, Accept
Request Body:    none
Query:           page (default 1), limit (default 20, max 100), productName,
                 productCode, categoryID, subCategoryID, categoryTypeID, bomName,
                 sort, flagActive (0 or 1)
Sortable:        productName, productCode, categoryID, subCategoryID, bomName,
                 uomName, categoryTypeID, FlagActive
Response:        paginated envelope; result.data rows carry productID, productName,
                 productCode, categoryID, categoryName, subCategoryID,
                 subCategoryName, bomID, bomName, categoryTypeID, categoryTypeName,
                 flagActive
Errors:          EC03100001 (unauthorized / invalid token),
                 EC03100032 for an unsupported sort column and for a non-numeric
                 page or limit
Timeout:         config('esb.timeouts')
Retry Policy:    read -- retried with backoff, up to three attempts
Idempotency:     not applicable (read)
Laravel Adapter: App\Integrations\ESB\Clients\ProductClient::list()
DTOs:            ProductListQuery, ProductSummary, ProductListResult
Mapper:          App\Integrations\ESB\Mappers\ProductMapper
Tests:           tests/Feature/Products/ProductListTest.php,
                 tests/Unit/Integrations/ESB/ProductMapperTest.php
Docs:            https://developers.esb.co.id/esb-core/#api-Master_Product-product-list
```

Two things about this operation are worth carrying to the next adapter:

- **The ESB reports bad input as `EC03100032` "Unknown Error".** An unsupported `sort`
  value and a non-numeric `page` both come back that way, one of them carrying a raw Go
  `strconv.ParseInt` message. Validating these in a Form Request before the call turns an
  opaque upstream failure into a field error and saves a round trip.
- **The vendor's field table has a typo.** It lists `result.data.categoryNameCategory`
  with the description "Type Name". The vendor's own JSON example shows the field is
  `categoryName`. Where the field table and the example disagree, the example is the one
  that matches the running service.

```
### [ESB-002] Purchase Request Index

Purpose:         List purchase requests from ESB Core.
Method:          GET
Path:            {{base_url}}/purchase/purchase-request
Family:          Core
Authentication:  Authorization: Bearer {access token or static key}
Request Body:    none
Query (used):    page (default 1), limit (default 20, max 100), purchaseRequestNum,
                 purchaseRequestDateFrom, purchaseRequestDateTo, requiredDateFrom,
                 requiredDateTo (all dates YYYY-MM-DD), branchIDs (comma-separated),
                 indexSearch
Query (unused):  sort, statusID, purchaseRequestTemplateIDs, additionalInfo -- see below
Response:        paginated envelope; result.data rows carry purchaseRequestNum,
                 purchaseRequestDate, requiredDate, createdDate, branchID, branchName,
                 requestTemplateID, requestTemplateName, statusID, statusName,
                 additionalInfo
Retry Policy:    read -- retried with backoff, up to three attempts
Idempotency:     not applicable (read)
Laravel Adapter: App\Integrations\ESB\Clients\PurchaseRequestClient::listPurchaseRequests()
Contract:        App\Integrations\Contracts\PurchaseRequestSource
DTOs:            App\Domain\Canonical\PurchaseRequestQuery, PurchaseRequestSummary,
                 PurchaseRequestPage
Mapper:          App\Integrations\ESB\Mappers\PurchaseRequestMapper
Tests:           tests/Feature/PurchaseRequests/PurchaseRequestListTest.php,
                 tests/Unit/Integrations/ESB/PurchaseRequestMapperTest.php

### [ESB-003] View Purchase Request

Purpose:         Read one purchase request, with its lines, by document number.
Method:          GET
Path:            {{base_url}}/purchase/purchase-request/{purchaseRequestNum}
Family:          Core
Response:        result carries the header (branch, cost center, project, template,
                 status, created/edited/authorized by and date, additionalInfo,
                 isTemplate), purchaseRequestDetails[] (productID, productDetailID,
                 productCode, productName, uomName, qty, notes, stock, suggestionQty,
                 minBranchProductQty, maxBranchProductQty, requestProcessID, ...),
                 purchaseNums[], transferNums[], imageAttachments[], print{}
Errors:          EC03100004 Not Found (HTTP 400) -> 404 in the gateway
Retry Policy:    read -- retried with backoff, up to three attempts
Laravel Adapter: App\Integrations\ESB\Clients\PurchaseRequestClient::findPurchaseRequest()
DTOs:            App\Domain\Canonical\PurchaseRequest, PurchaseRequestLine
Tests:           tests/Feature/PurchaseRequests/PurchaseRequestDetailTest.php
```

Decisions and open points for these two operations:

- **Source.** Fields were transcribed on 2026-09-25 from the vendor's OpenAPI description
  (`https://developers.esb.co.id/esb-core/openapi.json`). The vendor's examples for both
  operations are zero-valued placeholders (`""`, `0`), so they confirm field names and
  types but nothing else.
- **Dates are carried as strings.** The response date format is `TBD` — the examples are
  empty. Nothing in the gateway parses them; they are displayed as the ESB sent them.
- **Status is carried as the ESB's own `statusID` + `statusName`.** The id-to-meaning
  mapping is `TBD`, so there is no status filter and nothing branches on the id.
- **`sort` is not sent.** Only the default (`-purchaseRequestNum`) is documented, not the
  accepted columns. The Product List experience shows an unsupported value comes back as
  the opaque `EC03100032`, so a guessed whitelist would fail badly. `TBD`.
- **`0` means "none" for reference ids.** The detail result carries `projectID: 0` /
  `costCenterID: 0` with an empty name when nothing is attached; the mapper turns those
  into null rather than inventing entity 0. This is inferred from the placeholder
  example, not stated by the vendor — revisit against a real response.
- **`print` and `imageAttachments` are dropped.** Both hold absolute URLs on the vendor's
  hosts; the browser must not be pointed at them (ADR-006). Showing attachments needs a
  gateway-side proxy, deferred until someone needs it.
- **The document number is constrained at the route** (`[A-Za-z0-9][A-Za-z0-9_-]{0,63}`)
  and `rawurlencode`d by the adapter, so it cannot rewrite the upstream path (e.g.
  `PR1/../authorize`).
- **Writes.** Authorize and reject are `[ESB-004]`; create, update, and delete are
  `[ESB-005]`; file import is `[ESB-006]`. `requestProcessID` is now carried on detail
  lines (`PurchaseRequestLine::$requestProcessId`) because an update sends it back.

```
### [ESB-004] Authorize / Reject Purchase Request

Purpose:         Record an approval decision on a purchase request in ESB Core.
Method:          PATCH
Path:            {{base_url}}/purchase/purchase-request/{purchaseRequestNum}/authorize
                 {{base_url}}/purchase/purchase-request/{purchaseRequestNum}/reject
Family:          Core
Request Body:    none -- the vendor documents no body and no reason field for reject
Response:        envelope with result null
Errors:          EC03100003 validation (HTTP 400), EC03100004 not found (HTTP 400),
                 EC03100001 unauthorized, EC03100032 unknown (HTTP 500)
Retry Policy:    none -- exactly one attempt (EsbClient never retries a write)
Idempotency:     none documented; assume not idempotent
Laravel Adapter: App\Integrations\ESB\Clients\PurchaseRequestClient::decidePurchaseRequest()
Action:          App\Actions\PurchaseRequests\DecidePurchaseRequest
Route:           POST /purchase-requests/{number}/decisions  (decision=authorize|reject)
Permissions:     esb.invoke + purchase-requests.approve
Tests:           tests/Feature/PurchaseRequests/PurchaseRequestDecisionTest.php,
                 tests/Unit/Integrations/ESB/WriteOutcomeTest.php
```

How a failed decision is handled:

- **Definite refusal vs. unknown outcome.** `EsbException::mayHaveBeenApplied()` makes
  the distinction. Authentication, authorization, validation, business rejection, and
  not found return false: the ESB refused, so the user is told nothing happened. Timeout,
  connection, upstream (5xx), malformed response, and unknown (`EC03100032`) return
  true. For these the user sees "not confirmed; check the status before trying again".
  The case is also audited as `purchase-request.decision-unconfirmed` with its
  correlation id.
- **Connection failures count as unknown for writes.** cURL reports a reset after send
  the same way as a refused connection. Reads still retry them.
- **Reconciliation is the detail read.** Every outcome redirects to the detail page, and
  that page re-reads the document from the ESB. There is no automatic reconciliation,
  because the vendor does not document which `statusID` means "authorized".
- **Double submission.** A per-document cache lock (`purchase-request-write:{number}`,
  shared with update and delete) refuses a second write while the first is in flight.
  The lock TTL is the connect timeout plus the request timeout plus 30 s. The buttons
  are also disabled while a request runs.
- **Who decided.** The ERP sees the shared service credential (ADR-009) as the actor.
  The gateway audit row records the person.
- **TBD:** which statuses accept a decision. The buttons appear on every request, and
  the ERP refuses a decision it does not accept.

```
### [ESB-005] Create / Update / Delete Purchase Request

Purpose:         Raise, replace, or delete a purchase request in ESB Core.
Method / Path:   POST   {{base_url}}/purchase/purchase-request
                 PUT    {{base_url}}/purchase/purchase-request/{purchaseRequestNum}
                 DELETE {{base_url}}/purchase/purchase-request/{purchaseRequestNum}
Family:          Core
Request Body:    (POST and PUT share one schema) branchID*, purchaseRequestDate*,
                 requiredDate*, purchaseRequestDetails*[] (productDetailID*,
                 requestProcessID*, qty, notes <=100), requestTemplateID, costCenterID,
                 projectID, isTemplate, additionalInfo <=1000, countApplySuggestionQty
                 (not sent). Lines: minItems 1, uniqueItems. DELETE takes no body.
Response:        POST: result.purchaseRequestNum. PUT / DELETE: result null.
Errors:          EC03100003 validation with errors[] (HTTP 400), EC03100004 not found
                 (PUT/DELETE, HTTP 400), EC03100001, EC03100032 (HTTP 500)
Retry Policy:    none -- exactly one attempt
Idempotency:     none documented; assume not idempotent
Laravel Adapter: PurchaseRequestClient::createPurchaseRequest(), updatePurchaseRequest(),
                 deletePurchaseRequest()
DTOs:            App\Domain\Canonical\PurchaseRequestDraft, PurchaseRequestDraftLine
Actions:         App\Actions\PurchaseRequests\CreatePurchaseRequest,
                 UpdatePurchaseRequest, DeletePurchaseRequest
Routes:          GET  /purchase-requests/create, POST /purchase-requests,
                 GET  /purchase-requests/{number}/edit, PUT /purchase-requests/{number},
                 DELETE /purchase-requests/{number}
Permissions:     esb.invoke + purchase-requests.manage
Tests:           tests/Feature/PurchaseRequests/PurchaseRequestWriteTest.php,
                 PurchaseRequestFormTest.php,
                 tests/Unit/Integrations/ESB/PurchaseRequestMapperTest.php

### [ESB-006] Import Purchase Requests

Purpose:         Create purchase requests in bulk from an Excel sheet.
Method:          POST
Path:            {{base_url}}/purchase/purchase-request/import
Family:          Core
Request Body:    multipart/form-data, field `file`: xlsx or xls, at most 2 MB
Response:        envelope with result null -- no list of created documents
Errors:          EC03100003 validation with errors[] carrying `row` (HTTP 400)
Retry Policy:    none -- exactly one attempt
Idempotency:     none documented; importing a sheet twice may create everything twice
Laravel Adapter: PurchaseRequestClient::importPurchaseRequests() via EsbClient::upload()
Action:          App\Actions\PurchaseRequests\ImportPurchaseRequests
Routes:          GET /purchase-requests/import, POST /purchase-requests/import
Permissions:     esb.invoke + purchase-requests.manage
Tests:           tests/Feature/PurchaseRequests/PurchaseRequestImportTest.php

### [ESB-007] Purchase Request Form Lookups

Purpose:         Feed the create form: branches, their request templates, and each
                 template's lines with the ids a line needs.
Method / Path:   GET {{base_url}}/branch?flagActive=1
                 GET {{base_url}}/document-template/list?branchID=&flagActive=true
                 GET {{base_url}}/purchase/purchase-request/template-details
                     ?branchID=&requestTemplateID=
Family:          Core
Response:        result is a bare array (not paginated) for all three.
                 /branch: branchID, branchCode, branchName.
                 /document-template/list: requestTemplateID, requestTemplateName,
                 requestTemplateTypeID.
                 template-details: productDetailID, requestProcessID, requestProcessName,
                 productID, productCode, productName, uomName,
                 requestTemplateDetailName, stock, suggestionQty,
                 min/maxBranchProductQty, ...
Retry Policy:    read -- retried with backoff, up to three attempts
Laravel Adapter: PurchaseRequestClient::listBranches(), listRequestTemplates(),
                 listTemplateLines()
DTOs:            App\Domain\Canonical\Branch, RequestTemplate, PurchaseRequestTemplateLine
```

Decisions and open points for these operations:

- **Source.** Transcribed on 2026-09-30 from the vendor's OpenAPI description
  (`https://developers.esb.co.id/esb-core/openapi.json`). All of them appear in the
  catalog regenerated the same day.
- **Where line ids come from.** Create requires `productDetailID` and `requestProcessID`
  on every line, and the vendor documents no lookup for request processes. The only
  documented source of both is `template-details`, so the form builds a request from a
  request template: branch, then template, then fill quantities. Only rows with a
  quantity are sent. Template rows missing either id are dropped by the mapper.
- **`templateTypeID` is not sent** to `/document-template/list`: which type id means
  "purchase request" is `TBD`, and a wrong guess would hide every template. The list may
  therefore include templates of other types; the ERP refuses those on create.
- **Dates are sent as `YYYY-MM-DD`.** The create schema only says `string`. This is the
  format the list filters take and what an HTML date input produces. `TBD`, confirm with
  the ESB team. The edit form pre-fills a date only if the ERP's value starts with
  `YYYY-MM-DD`.
- **An update replaces the document.** PUT takes the full create schema, so the edit
  form carries `costCenterID`, `projectID`, `isTemplate` and `requestTemplateID` back
  unchanged. There is no picker for cost center or project, and they are not settable on
  create. An edit keeps the branch because lines, stock, and suggestions belong to it.
- **The new number is validated before use.** A create's `purchaseRequestNum` is used
  only if it matches the route's document-number pattern. Otherwise the user lands on
  the list. The vendor's example is an empty placeholder.
- **`errors[]` is now surfaced.** `ErrorClassifier` carries each entry as an
  `EsbFieldError` (`field`, `row`, `message`) on the exception. Entries are bounded to
  100 and messages to 300 characters, and field names that are not identifiers are
  dropped. For create/update the vendor field names map onto form fields
  (`branchID` -> `branchId`, `purchaseRequestDate` -> `date`, `purchaseRequestDetails`
  -> `lines`, ...), and anything else becomes a form-level message. For import they are
  shown as a row/column/problem table. These messages are shown to users; the envelope
  `message` still is not.
- **The import sheet is not parsed.** Its layout is the ERP's own and undocumented. The
  gateway checks the size, the extension, and the file's leading bytes (zip for xlsx,
  OLE2 for xls). It does not rely on MIME sniffing, which reports xlsx as
  `application/zip` on some systems. The filename is reduced to a safe basename. The file
  is never stored; the audit row records its name, size, and SHA-256.
- **One write at a time.** Every write goes through `App\Actions\Support\GuardedEsbWrite`
  (one lock, one attempt, audit an unconfirmed outcome). Writes to an existing document
  (update, delete, authorize, reject) share the lock `purchase-request-write:{number}`,
  so none can race another. Create and import have no number yet, so they lock per
  person: `purchase-request-create:{userId}` and `purchase-request-import:{userId}`.
- **Unconfirmed outcomes** are audited as `purchase-request.write-unconfirmed` with the
  operation and correlation id. The user lands where the real state is visible: the
  document after an update, and the list after a create, a delete, or an import.
- **TBD:** which statuses accept an update or delete (the buttons are always shown), and
  whether the vendor's attachment endpoints
  (`PATCH|DELETE .../{purchaseRequestNum}/attachment`) are wanted.

```
### [ESB-008] Purchase Invoice Index

Purpose:         List purchase (supplier) invoices from ESB Core.
Method:          GET
Path:            {{base_url}}/purchase/purchase-invoices
Family:          Core
Request Body:    none
Query (used):    page (default 1), limit (default 20, max 100), purchaseInvoiceNum,
                 supplierInvoiceNum, dateFrom, dateTo (YYYY-MM-DD),
                 branchIDs (form style, explode false: comma-separated), indexSearch
Query (unused):  sort, statusID, supplierID, supplierIDs, currencyID, branchID, rate,
                 invoiceTotal, createdBy, additionalInfo -- see below
Response:        paginated envelope; result.data rows carry purchaseInvoiceNum,
                 supplierInvoiceNum, purchaseInvoiceDate, purchaseInvoiceDueDate,
                 branchID, branchName, supplierID, supplierName, currencyID,
                 currencySign, invoiceTotal, statusID, statusName, createdBy,
                 createdByFullName, additionalInfo, overDue, linkInvoiceNumEsbGoods
Retry Policy:    read -- retried with backoff, up to three attempts
Laravel Adapter: App\Integrations\ESB\Clients\PurchaseInvoiceClient::listPurchaseInvoices()
Contract:        App\Integrations\Contracts\PurchaseInvoiceSource
DTOs:            App\Domain\Canonical\PurchaseInvoiceQuery, PurchaseInvoiceSummary,
                 PurchaseInvoicePage
Mapper:          App\Integrations\ESB\Mappers\PurchaseInvoiceMapper
Tests:           tests/Feature/PurchaseInvoices/PurchaseInvoiceListTest.php,
                 tests/Unit/Integrations/ESB/PurchaseInvoiceMapperTest.php

### [ESB-009] View Purchase Invoice

Purpose:         Read one purchase invoice, with its lines and costs.
Method:          GET
Path:            {{base_url}}/purchase/purchase-invoices/{purchaseInvoiceNum}
Family:          Core
Response:        result carries the header (supplier, supplierInvoiceNum,
                 vatInvoiceNum, dates, dueDay, refDate, branch, currency, rate,
                 referenceTotal, costTotal, invoiceTotal, statusID, additionalInfo,
                 creator/editor/authorizer {fullName, username} with their dates),
                 details[] (productDetailID, productCode, productName, refNum,
                 transDate, purchaseNumber, qty, qtyGoods, uomName, price, discount,
                 discountPercent, taxName, taxRate, vat*, dppValue, total,
                 costCenter*, project*, ...), costs[] (coaNo, coaDescription, amount),
                 unverifiedDetails[], imageAttachments[], printData{}
Errors:          EC03100004 Not Found (HTTP 400) -> 404 in the gateway
Retry Policy:    read -- retried with backoff, up to three attempts
Laravel Adapter: PurchaseInvoiceClient::findPurchaseInvoice()
DTOs:            App\Domain\Canonical\PurchaseInvoice, PurchaseInvoiceLine,
                 PurchaseInvoiceCost
Tests:           tests/Feature/PurchaseInvoices/PurchaseInvoiceDetailTest.php

### [ESB-010] Authorize / Reject / Delete Purchase Invoice

Purpose:         Record an approval decision on, or delete, a purchase invoice.
Method / Path:   PATCH  {{base_url}}/purchase/purchase-invoices/{purchaseInvoiceNum}/authorize
                 PATCH  {{base_url}}/purchase/purchase-invoices/{purchaseInvoiceNum}/reject
                 DELETE {{base_url}}/purchase/purchase-invoices/{purchaseInvoiceNum}
Family:          Core
Request Body:    none for all three
Response:        envelope with result null
Errors:          400 "validation error or resource not found -- see errors array",
                 EC03100001 unauthorized, EC03100032 unknown (HTTP 500)
Retry Policy:    none -- exactly one attempt
Idempotency:     none documented; assume not idempotent
Laravel Adapter: PurchaseInvoiceClient::decidePurchaseInvoice(), deletePurchaseInvoice()
Actions:         App\Actions\PurchaseInvoices\DecidePurchaseInvoice,
                 DeletePurchaseInvoice
Routes:          POST   /purchase-invoices/{number}/decisions (decision=authorize|reject)
                 DELETE /purchase-invoices/{number}
Permissions:     decision: esb.invoke + purchase-invoices.approve
                 delete:   esb.invoke + purchase-invoices.manage
Tests:           tests/Feature/PurchaseInvoices/PurchaseInvoiceDecisionTest.php,
                 PurchaseInvoiceDeleteTest.php
```

Decisions and open points for these operations:

- **Source.** Transcribed on 2026-09-30 from the vendor's OpenAPI description. As with
  purchase requests, the examples are zero-valued placeholders, so they confirm field
  names and types only. Date formats, `statusID` meanings, and accepted `sort` columns
  are `TBD`; dates are displayed as sent and nothing branches on the status.
- **The detail has no top-level `statusName`.** Only `statusID` is documented there; the
  label appears as `printData.statusName`. The mapper uses a top-level `statusName` if one
  ever arrives, else `printData.statusName`. That this label is the document status is
  inferred from the field name, `TBD`.
- **Amounts are display-only.** They are carried as floats (the vendor types them as JSON
  `number`), never summed or recomputed by the gateway. The ERP is the authority.
- **Tax figures per line are not shown.** The create schema describes `vatTotal` as
  `db:"vatValue"` and `vatValue` as `db:"vat"`, so which field is the tax amount is
  unclear. Only `taxName` is carried until the ESB team confirms. `TBD`.
- **Not carried:** `printData` and `imageAttachments` (vendor-hosted URLs, ADR-006),
  `unverifiedDetails`, `supplierDueDate`, `overDue`, `isJournalExist`,
  `isStockCardExist`, and the `link*` fields. Their meaning is undocumented and no
  screen needs them.
- **Filters not offered:** `statusID` and `sort` (meanings undocumented), and
  `supplierID(s)` / `currencyID` (no lookup to pick one from yet). `dateFrom`/`dateTo`
  are described only as "filter start/end date"; which date they filter on is `TBD`
  (the screen labels them "invoice date").
- **Separate permissions.** Approving a supplier invoice commits the company to pay it,
  a finance decision rather than a purchasing one. `purchase-invoices.approve` and
  `purchase-invoices.manage` are therefore separate from the purchase request
  permissions. No role holds either yet; only Super Admin can use them.
- **One write at a time.** Authorize, reject, and delete go through `GuardedEsbWrite`
  under one lock, `purchase-invoice-write:{number}`. An ambiguous failure is audited as
  `purchase-invoice.write-unconfirmed` with the operation and correlation id.
- **Not built:** create and update. Their body needs supplier, currency, rate,
  goods-receipt references (`browse-references`, `browse-data-details`), and per-line
  tax figures. Building them means either computing totals in the gateway, which
  duplicates ERP rules, or finding out which fields the ERP derives itself. Decide
  this with the ESB team first. Also not built: attachments, `export-csv`, payment and
  settlement reads, and the `validate-*` checks.

### [ESB-011] Push Sales Data V2

```
Purpose:         Send one POS sales transaction to the ERP. The first operation the
                 gateway forwards on behalf of another system rather than a person
                 (ADR-022).
Method / Path:   POST {{base_url}}/extv1/push/sales-data
Family:          FnB backend (ESB OMS group Push_Sales_Data)
Authentication:  vendor documents "Bearer {Static token}" from the project PIC. The
                 gateway sends its usual token (ESB_AUTH_MECHANISM, currently the
                 Core username/password login). Whether the FnB backend accepts a
                 Core login token is TBD -- confirm on staging first.
Request Body:    { "salesHead": { ...head, "menu": [ {..., "packages": [], "extras": []} ],
                 "payment": [ ... ] } } -- forwarded exactly as the POS sent it
Response:        { path, timestamp, code: "200", status: "ok", message: null,
                 result: { status: "00", syncDate: "YYYY-MM-DD HH:MM:SS" } }
Errors:          code 400 (number) with `data`: a list of strings -- "Missing attribute:
                 <path>", "<Thing> not found: <path>", "<path> cannot be blank".
                 code 401 (number), no `data`: invalid credentials.
Retry Policy:    EsbClient: one attempt (it is a POST). The queue job repeats an
                 unknown outcome (timeout, connection, 5xx, unreadable body) up to
                 config('sales_push.tries') times with config('sales_push.backoff').
Idempotency:     vendor: "When push sales with the existing salesNum, it will
                 overwrite the existing sales data". Repeating a push is safe; sending
                 an OLDER version after a newer one is not (see below).
Laravel Adapter: App\Integrations\ESB\Clients\SalesDataClient (contract SalesDataSink)
Actions / Job:   App\Actions\SalesPushes\ReceiveSalesData, SendSalesPush, RetrySalesPush;
                 App\Jobs\PushSalesData
Routes:          POST /api/v1/sales-data, GET /api/v1/sales-data/{id} (API client token)
                 GET /sales-pushes, GET /sales-pushes/{id}, POST /sales-pushes/{id}/retry
Permissions:     web: sales-pushes.view; retry also sales-pushes.retry
Tests:           tests/Feature/Integrations/ESB/SalesDataClientTest.php,
                 tests/Feature/SalesPushes/*
```

Decisions and open points for this operation:

- **Source.** Transcribed on 2026-10-01 from the vendor's apiDoc
  (`esb-oms/api_data.json`, `push-sales-data-v2`). The staging hosts named in its
  description match the FnB backend family in `config/esb.php`.
- **A different envelope.** Success is `code: "200"` (a string) instead of `EC03100000`;
  failures use the numbers `400` and `401`. `ErrorCode::isSuccess()` accepts both success
  forms, and `ErrorCode` maps `400` to validation and `401` to authentication. No other
  numeric code is mapped; any other one is logged as unrecognised.
- **Refusals are strings in `data`, not objects in `errors`.** `ErrorClassifier` falls
  back to `data[]` when `errors[]` is empty. `EsbFieldError::fromMessage()` takes the
  dotted path the message names as the field. These messages are stored on the push and
  returned to the POS.
- **`result.status`.** Only `"00"` is documented. The gateway treats the envelope code as
  the answer and records `result.status` for display. What another value means is `TBD`.
- **The vendor documentation contradicts itself on types.** `promotionID` and
  `payment.paymentMethod` are typed Integer but the examples send `"0"` and `"Cash"`.
  `flagInclusive` and `otherTaxOnVat` are typed Boolean but the examples send `0`. The
  package and extra examples leave out fields their own "Missing attribute" example
  requires. So the gateway validates only what it needs: `salesNum` (max 20),
  `branchCode` (max 20), `salesDateIn` (`Y-m-d H:i:s`), `salesDate` (`Y-m-d` or empty),
  and that `menu`, `payment`, `packages`, and `extras` are lists. The ESB validates the
  rest, and its messages reach the POS through the status endpoint.
- **Empty strings.** Several attributes are "mandatory, but can contain empty string".
  `/api/*` is excluded from `TrimStrings` and `ConvertEmptyStringsToNull`, so `""` reaches
  the ESB as `""`.
- **Order per salesNum.** Because the ESB keeps whatever arrives last, a push is marked
  `superseded` and never sent when a newer push of the same `salesNum` is queued or has
  succeeded. A newer push the ESB refused does not supersede an older one. Pushes of one
  `salesNum` run one at a time (`WithoutOverlapping`).
- **Outcomes.** A 400 (validation, not found, business rejection) is final: `rejected`,
  the POS must fix the data. Authentication, authorization, and configuration failures
  are final too: `failed`, a person must fix the setup and press "Send again". Anything
  else is repeated by the queue, then `failed` when attempts run out.
- **Not built:** Shift Data V2 (`POST /corev1/shift-data/push`), the V1 operations, and a
  canonical sales model (ADR-017).

### [ESB-012] Master Menu (read-only)

```
Purpose:         List point-of-sale menus.
Method / Path:   GET {{base_url}}/menu/browse
Family:          Core (ESB Core group Menu)
Query:           page (default 1), limit (default 20; gateway cap 100), menuName,
                 menuCode, flagActive (0/1). Also documented but not offered:
                 menuShortName, categoryID, categoryDetailID, notes, productID, sort
                 (accepted values undocumented beyond the default menuName).
Response:        standard envelope, EC03100000; result { count, data[], limit, next,
                 page, prev }. Row: menuID, menuCode, menuName, menuShortName,
                 categoryID, categoryDesc, categoryDetailID, categoryDetailDesc, notes.
Errors:          EC03100003 validation (errors[]), EC03100001 unauthorized,
                 EC03100032 unknown
Retry Policy:    GET -- EsbClient retries with backoff
Laravel Adapter: App\Integrations\ESB\Clients\MenuClient (contract MenuSource),
                 MenuMapper; canonical Menu, MenuQuery, MenuPage
Routes:          GET /menus
Permissions:     esb.invoke
Tests:           tests/Feature/Menus/MenuTest.php,
                 tests/Unit/Integrations/ESB/MenuMapperTest.php
```

Decisions and open points:

- **Source.** Transcribed on 2026-10-01 from the ESB Core OpenAPI document. The example
  row is zero-valued, so it confirms field names and types only.
- **Core and OMS side by side.** First built on the OMS `get-menu`, switched to Core on
  2026-10-01, then the OMS version was added back as its own screen (`[ESB-013]`,
  `/master-menus`) at the owner's request. Core `/menu/browse` carries only the nine
  fields above and the Core Menu group has no detail operation, so `/menus` has no
  detail page; prices, packages, and extras are on `/master-menus`.
- **The row has no active flag.** `flagActive` works as a filter only; the list cannot
  show whether a row is active unless the filter is set.
- **Not built:** create / update (none documented in the Core Menu group),
  `/menu/browse-for-product`, `/menu/validate-bom-usage`.

### [ESB-013] Master Menu, ESB OMS (read-only)

```
Purpose:         List point-of-sale menus and show one, with template prices, package
                 groups, extras, icons, tags, and related menus.
Method / Path:   GET {{base_url}}/corev1/master/get-menu
Family:          FnB backend (ESB OMS group Master_Menu)
Query:           page (default 1), menuCode. Page size is fixed by the ESB (20 in the
                 example). The active-flag parameter has no usable name in the vendor
                 documentation (the field is literally called "Boolean"); it is not
                 sent, so the ESB default applies: active menus only. TBD.
Response:        code "EC011200"; result { page (string), limit, count, data[] }.
                 Flags in rows are "Yes"/"No" labels; flagTax is "No" / "Yes, Tax" /
                 "Yes, VAT"; prices and quantities are decimal strings.
Errors:          EC011401 unauthorized
Retry Policy:    GET -- EsbClient retries with backoff
Laravel Adapter: App\Integrations\ESB\Clients\MasterMenuClient (contract
                 MasterMenuSource), MasterMenuMapper; canonical MasterMenu,
                 MasterMenuTemplatePrice, MasterMenuPackageGroup, MasterMenuPackageItem,
                 MasterMenuExtra, MasterMenuReference, MasterMenuQuery, MasterMenuPage
Routes:          GET /master-menus, GET /master-menus/{code}
Permissions:     esb.invoke
Tests:           tests/Feature/MasterMenus/MasterMenuTest.php,
                 tests/Unit/Integrations/ESB/MasterMenuMapperTest.php
```

Decisions and open points:

- **Source.** Transcribed on 2026-10-01 from the vendor's apiDoc
  (`esb-oms/api_data.json`, group `Master_Menu`). Auth uses the gateway's configured
  credential; the vendor documents a static token, as for `[ESB-011]` (TBD).
- **Two canonical shapes.** `Menu` (`[ESB-012]`) is the Core browse row; `MasterMenu` is
  the OMS row with POS configuration. They describe the same ERP menu at different
  depth and are deliberately not merged until one source is chosen.
- **`EC011200` is success** for the OMS master operations. `ErrorCode::isSuccess()`
  accepts it alongside `EC03100000` and `"200"`.
- **No detail operation.** `findMenu()` reads the list with `menuCode` and keeps only a
  row whose code matches exactly, because whether the filter matches exactly or by
  prefix is undocumented. No exact match is a 404.
- **Vendor-hosted URLs are not carried.** `menuImage` and `menuIconUrl` stay behind the
  adapter (ADR-006); only icon names are shown. `themeMenuOnPos` is shown only when it
  is a hex colour.
- **Menu codes in URLs** are held to `[A-Za-z0-9][A-Za-z0-9 _.-]{0,49}` (vendor size
  String(50)). The character set is undocumented; a menu outside it can be listed but
  not opened. TBD.
- **Not built:** create (`POST /corev1/master/create-menu`) and update. The update path
  is documented as `/web/corev1/master/update-menu` while the FnB backend base URL
  already ends in `/web`, so the real path is TBD. Both take an **array** of menus, and
  their `EC0110` failures put per-field errors as JSON inside `message`. Menu category
  and menu template reads are also not built.

### [ESB-014] Master Bill of Material (read-only)

```
Purpose:         List bills of material (recipes) and show one with its materials,
                 substitutes, other costs, and per-menu replacements.
Method / Path:   GET {{base_url}}/product/bom
                 GET {{base_url}}/product/bom/{bomID}
Family:          Core (group "Master - Product / Bill of Material")
Query (list):    page (default 1), limit (default 20; gateway ceiling 100), bomName,
                 bomCode, productName, flagActive (0 or 1). Sent only when set.
Not sent:        bomTypeID (needs a BOM type lookup), productCode, exceptProductID,
                 uomName, notes, sort (no documented list of sortable columns; ESB
                 default is bomName). locationID on the detail (no location picker).
Response (list): paginated envelope; rows carry bomID, bomCode, bomName, bomTypeID,
                 bomTypeName, productName, uomName, notes, flagActive (1/0)
Response (view): result object: header (bomID, bomCode, bomName, bomType*, accessType,
                 product*, uom*, notes, flagActive, bomCostTotal, otherCostTotal,
                 weightFactorTotal, created*/edited*), bomDetails[] (materials, each
                 with subtitution[] and subtitutionManufacturing[]), bomCosts[],
                 bomReplacements[]
Errors:          EC03100001 unauthorized; documented responses 400/401/500. Not found
                 is assumed to be EC03100004 like the other Core detail reads (TBD --
                 the vendor documents no 404 for this path); it renders as a 404.
Retry Policy:    GET -- EsbClient retries with backoff
Laravel Adapter: App\Integrations\ESB\Clients\BillOfMaterialClient (contract
                 BillOfMaterialSource), BillOfMaterialMapper; canonical BillOfMaterial,
                 BillOfMaterialSummary, BillOfMaterialLine, BillOfMaterialSubstitute,
                 BillOfMaterialCost, BillOfMaterialReplacement, BillOfMaterialQuery,
                 BillOfMaterialPage
Operations:      bom.list, bom.view (integration log)
Routes:          GET /bills-of-material, GET /bills-of-material/{bomId}
Permissions:     esb.invoke
Tests:           tests/Feature/BillsOfMaterial/BillOfMaterialTest.php,
                 tests/Unit/Integrations/ESB/BillOfMaterialMapperTest.php
Docs:            https://developers.esb.co.id/esb-core/#/paths/product-bom/get
                 https://developers.esb.co.id/esb-core/#/paths/product-bom-bomID/get
```

Decisions and open points:

- **Source.** Transcribed on 2026-10-13 from the vendor's OpenAPI document
  (`esb-core/openapi.json`). The vendor's examples are zero-valued, so test fixtures use
  realistic stand-ins with the vendor's field names and types.
- **Detail via `/product/bom/{bomID}`, not `/product/bom/detail/{bomID}`.** The former
  returns the header plus materials, costs, and replacements; the latter returns only
  material lines (with `stockQty`, which needs a location to mean anything).
- **A successful response without a `bomID` is a 404.** The vendor's example result is
  zero-valued; the mapper treats an id of 0 as "no BOM" rather than rendering an empty
  record.
- **Vendor spelling stops at the mapper.** `subtitution` becomes `substitutes`,
  `convertionQty` becomes `conversionQty`, `lastHpp` becomes `lastCost`.
- **Costs are display-only.** `bomCostTotal`, `lastHpp`, and `price` are the ERP's
  figures; the gateway does not recompute them.
- **BOM ids in URLs** are held to `[1-9][0-9]{0,9}`; the id is interpolated into the
  upstream path.
- **Not built:** create / update / delete / restore, upload and export, push/pull to
  child companies, `check-bom-infinite`, `validate-replacement`, user access mapping,
  BOM type lookup (`/common/bom-type/*`). Writes are business actions and need their own
  permission and an unknown-outcome design, as for purchase requests.

### [ESB-016] Master Document Template (list, detail, create, spreadsheet import)

```
Purpose:         List document templates (product sets that pre-fill a purchase
                 request, usage, or item journal), show one with its products, and
                 create one.
Method / Path:   GET  {{base_url}}/document-template
                 GET  {{base_url}}/document-template/{requestTemplateID}
                 POST {{base_url}}/document-template
                 GET  {{base_url}}/product/list, GET {{base_url}}/product/{productID}
                      (product lookup for the create picker and the import)
Family:          Core (group "Master Document Template")
Query (list):    page (default 1), limit (default 20; gateway ceiling 100),
                 requestTemplateName, requestTemplateTypeID (1 = Purchase Request,
                 2 = Usage Management, 3 = Item Journal), flagActive (0 or 1). Sent only
                 when set.
Not sent:        branchID (no branch picker).
Response (list): paginated envelope; rows carry requestTemplateID, requestTemplateName,
                 requestTemplateTypeNames (comma-separated string), branchNames
                 (comma-separated string), flagActive (1/0)
Response (view): result object: requestTemplateID, requestTemplateName,
                 requestTemplateTypeID, flagActive, flagAutoTransfer, allowZeroPricelist,
                 branchIDs[], costCenterIDs[], supplierID, currencyID,
                 destinationBranchID, created*/edited*, syncDate,
                 requestTemplateDetails[] (ID, productID, productDetailID, productCode,
                 productName, requestTemplateDetailName, uomName, categoryName,
                 categoryTypeID, purposeID, lastHpp, stockQty, availableQty, mode,
                 requestProcessID, flagActive)
Errors:          EC03100001 unauthorized; documented responses 400 ("validation error or
                 resource not found"), 401, 500. Not found is assumed to be EC03100004
                 like the other Core detail reads (TBD); it renders as a 404.
Retry Policy:    GET -- EsbClient retries with backoff
Laravel Adapter: App\Integrations\ESB\Clients\DocumentTemplateClient (contract
                 DocumentTemplateSource), DocumentTemplateMapper; canonical
                 DocumentTemplate, DocumentTemplateSummary, DocumentTemplateLine,
                 DocumentTemplateType, DocumentTemplateQuery, DocumentTemplatePage,
                 DocumentTemplateDraft, DocumentTemplateDraftLine, ProductDetailOption.
                 Create is wrapped by App\Actions\DocumentTemplates\CreateDocumentTemplate
                 (GuardedEsbWrite: per-person lock, audit).
Body (create):   requestTemplateName (no emoji), requestTemplateTypeID (1-3),
                 flagActive (1/0), branchIDs[] (min 1), requestTemplateDetails[] (min 1;
                 productDetailID unique, requestTemplateDetailName (no emoji), purposeID
                 optional). Not sent: costCenterIDs, supplierID, currencyID,
                 destinationBranchID, flagAutoTransfer, allowZeroPricelist, line mode and
                 requestProcessID.
Response (create): 201; result is a map of integers, example {}. The new id is read
                 from result.requestTemplateID if present (TBD); otherwise the user lands
                 on the list.
Product lookup:  GET /product/list (picker: page=1, limit=10, productName, flagActive=1;
                 import: page=1, limit=50, productCode) for productIDs, then
                 GET /product/{productID} for productDetails[] (productDetailID, uomName,
                 flagActive) -- one unit per row; inactive units are not offered.
Retry (create):  none -- one attempt; a timeout/5xx is "may have been applied", audited
                 as document-template.write-unconfirmed, user sent to the list
Operations:      document-template.list, document-template.view, document-template.create,
                 product.list, product.view (integration log)
Routes:          GET /document-templates, GET /document-templates/{templateId},
                 GET /document-templates/create, POST /document-templates
Permissions:     esb.invoke (read); esb.invoke + document-templates.manage (create)
Audit:           document-template.created, document-template.write-unconfirmed
Tests:           tests/Feature/DocumentTemplates/DocumentTemplateTest.php,
                 tests/Feature/DocumentTemplates/DocumentTemplateCreateTest.php,
                 tests/Unit/Integrations/ESB/DocumentTemplateMapperTest.php
Docs:            https://developers.esb.co.id/esb-core/#/paths/document-template/get
                 https://developers.esb.co.id/esb-core/#/paths/document-template-requestTemplateID/get
                 https://developers.esb.co.id/esb-core/#/paths/document-template/post
                 https://developers.esb.co.id/esb-core/#/paths/product-list/get
                 https://developers.esb.co.id/esb-core/#/paths/product-productID/get
```

Decisions and open points:

- **Source.** Transcribed on 2026-10-15 from the vendor's OpenAPI document
  (`esb-core/openapi.json`). The vendor's examples are zero-valued, so test fixtures use
  realistic stand-ins with the vendor's field names and types.
- **Template types are an enum.** The vendor documents ids 1-3 only in the list filter's
  description; `DocumentTemplateType` holds them in one place. A detail with any other
  id shows an unknown type rather than failing.
- **Comma-separated names become lists** in the mapper (`typeNames`, `branchNames`).
- **Ids without names.** The detail carries branch and cost-center ids only; resolving
  names needs `/{id}/template-branch` and `/{id}/cost-center` (not built). Supplier,
  currency, and destination-branch ids are not carried at all yet.
- **Left out of lines:** `stockQty` and `availableQty` (the detail is read without a
  branch, so the location is unstated), `mode` and `requestProcessID` (meanings
  undocumented, TBD), `categoryTypeID`, `productID`.
- **A successful response without a `requestTemplateID` is a 404**, as for BOMs.
- **Template ids in URLs** are held to `[1-9][0-9]{0,9}`.
- **Create has its own permission** (`document-templates.manage`, 2026-10-15). A
  template decides what every assigned branch can request, so `esb.invoke` alone does
  not grant it. No role holds it yet; only Super Admin, until the business decides.
- **"No emoji"** is undefined by the vendor; the gateway refuses the pictographic and
  dingbat blocks plus ZWJ/VS16 on both names. Names are capped at 255 by the gateway.
- **ESB field errors** are mapped by the name before any index or dot, so
  `requestTemplateDetails[0].productDetailID` shows on the lines. The vendor does not
  document the `field` format for nested errors (TBD).
- **Branch list** comes from `GET /branch` via `PurchaseRequestSource::listBranches()`,
  the lookup the purchase request screen already uses. Purposes for the line picker
  come from `PurposeSource` (active, first 100).
- **Product lookup, found on staging (2026-10-15).** The vendor documents every
  parameter of `GET /product-detail/browse` as optional, but the ESB refuses it with
  `EC03100003` unless `productDetailID` or `productDetailIDs` is sent: it fetches by
  id, it does not search. `GET /product/detail/browse` searches, but returns only
  non-inventory products (47 of 1125 on staging). `GET /product/list` holds every
  product, and its `productCode` filter matches **partially** and ignores case (`FC00`
  returns every FC00xx). So the gateway lists, keeps the exact code, and reads each
  product's units from `GET /product/{productID}`.
- **Spreadsheet import** (ADR-023): upload into the gateway's tables, validate with
  `GET /branch`, `GET /product/list?productCode=` + `GET /product/{productID}` (code
  compared exactly), and `GET /document-template?requestTemplateName=` (names compared ignoring
  case), then sync through the same create. The ESB's own
  `POST /document-template/upload` is not used: its layout is undocumented and it gives
  no per-template result.
- **Not built:** update / delete / restore, ESB upload and export, push/pull to
  child companies, `copy-template`, `find-by-type`, `list` (dropdown),
  `detail/findOne`, `filter-data`, `validate-requestable-products`, `check-branch`.

### [ESB-017] Purchase Order (list, detail, authorize / reject, delete)

```
Purpose:         List and read purchase orders from ESB Core; authorize, reject, or
                 delete one.
Family:          Core
Method / Path:   GET    {{base_url}}/purchase/purchase-order
                 GET    {{base_url}}/purchase/purchase-order/{purchaseNum}
                 PATCH  {{base_url}}/purchase/purchase-order/{purchaseNum}/authorize
                 PATCH  {{base_url}}/purchase/purchase-order/{purchaseNum}/reject
                 DELETE {{base_url}}/purchase/purchase-order/{purchaseNum}
Query (used):    page (default 1), limit (default 20, max 100), purchaseNum,
                 dateFrom, dateTo, requiredDateFrom, requiredDateTo (YYYY-MM-DD),
                 branchIDs (comma-separated), indexSearch
Query (unused):  statusID, sort, branchID, supplierID, supplierIDs,
                 purchaseRequestNums, purchaseTotal, printedBy, sendEmail, flagPOSO,
                 additionalInfo -- see below
Request Body:    authorize, delete: none. reject: {"reason": string}, marked required.
Response:        list: paginated envelope; rows carry purchaseNum, purchaseDate,
                 requiredDate, branch*, supplier*, currencyID, currencySign,
                 purchaseTotal, statusID, statusName, purchaseRequestNums, createdBy,
                 additionalInfo, access{canDelete, canEdit, unclose}, approval[],
                 approvalFlow[], mapSupplierLink{}, printed*/emailed*, ...
                 detail: header (as above plus purchaseTypeName, supplierAddress,
                 supplierPhone1, supplierBranchName, picName, currencyName, rate,
                 dueDay, costCenter*, project*, footNote, creator/editor/authorizer
                 {fullName, username} with their dates), purchaseDetails[]
                 (productDetailID, productCode, productName, qty, requestQty,
                 uomName, price, discount, discountPercent, total, notes, vat,
                 vatValue, dppValue, pricelist*/lastPrice*, ...), goodsReceiptHead[],
                 purchaseInvoiceHead[], images[], supplierDefaultPIC{}
                 writes: envelope with result null
Errors:          400 "validation error or resource not found -- see errors array";
                 EC03100004 -> 404 in the gateway (assumed, as for purchase invoices);
                 EC03100001 unauthorized; EC03100032 unknown (HTTP 500)
Retry Policy:    reads retried with backoff (three attempts); writes exactly one attempt
Idempotency:     none documented; assume not idempotent
Laravel Adapter: App\Integrations\ESB\Clients\PurchaseOrderClient
Contract:        App\Integrations\Contracts\PurchaseOrderSource
DTOs:            App\Domain\Canonical\PurchaseOrderQuery, PurchaseOrderSummary,
                 PurchaseOrderPage, PurchaseOrder, PurchaseOrderLine,
                 PurchaseOrderLinkedDocument, ApprovalDecision
Mapper:          App\Integrations\ESB\Mappers\PurchaseOrderMapper
Actions:         App\Actions\PurchaseOrders\DecidePurchaseOrder, DeletePurchaseOrder
Routes:          GET    /purchase-orders, /purchase-orders/{number}
                 POST   /purchase-orders/{number}/decisions
                        (decision=authorize|reject, reason required for reject)
                 DELETE /purchase-orders/{number}
Permissions:     read:     esb.invoke
                 decision: esb.invoke + purchase-orders.approve
                 delete:   esb.invoke + purchase-orders.manage
Log operations:  purchase-order.list, .view, .authorize, .reject, .delete
Tests:           tests/Feature/PurchaseOrders/ (List, Detail, Decision, Delete),
                 tests/Unit/Integrations/ESB/PurchaseOrderMapperTest.php,
                 fixture tests/Fixtures/CorePurchaseOrder.php
```

Decisions and open points for these operations:

- **Source.** Transcribed on 2026-10-19 from the vendor's OpenAPI description
  (`openapi.json`). The examples are zero-valued placeholders, so they confirm field
  names and types only. Date formats are `TBD`; dates are displayed as sent.
- **Reject requires a reason.** Unlike purchase requests and invoices, the vendor
  documents a required body `{reason}` for reject. The gateway refuses a blank reason
  before the call and caps it at 500 characters (its own ceiling; the vendor documents
  no length, `TBD`). The reason is kept in the `purchase-order.rejected` audit row. A
  reason sent with an authorize is dropped, not forwarded.
- **The ERP's `access` flags are used, not re-derived.** The detail's
  `access.canDelete` hides the Delete button when it is `false`. When the flag is
  missing the button is shown and the ERP is left to refuse. `canEdit` is carried for
  the future edit screen; `unclose` is not carried yet. There is no access flag for
  authorize/reject, so those buttons are always shown.
- **Status filter not offered.** The vendor names `1 = New, 2 = Rejected,
  3 = Authorized` by example only and points to `GET /common/status` for the rest. The
  filter waits for that lookup. `sort` columns are undocumented.
- **`purchaseRequestNums` is carried as one string.** Its separator is not documented,
  so it is not split into a list.
- **Line tax figures are not shown**, for the same reason as purchase invoices: which of
  `vat` / `vatValue` / `dppValue` is the tax amount is `TBD`.
- **Not carried:** `images` (vendor-hosted URLs, ADR-006); `supplierDefaultPIC`,
  `phoneNumber`, `address`, `emailSupplier` (a supplier person's contact details that
  no screen needs); `approval[]` / `approvalFlow[]` (undocumented semantics);
  `mapSupplierLink`, `flagImportDoc`, the `link*`, `printed*` and `emailed*` fields.
- **One write at a time.** Authorize, reject, and delete go through `GuardedEsbWrite`
  under one lock, `purchase-order-write:{number}`. An ambiguous failure is audited as
  `purchase-order.write-unconfirmed` with the operation and correlation id.
- **Not built:** create / update (incl. drafts, `with-asset-image`), close / unclose /
  unfinish / auto-close, print, send-email-authorize, asset-image upload, bulk
  `upload` and `form-import`. Create/update need supplier, currency and pricelist
  lookups, and the same "which totals does the ERP derive" question as purchase
  invoices.

### [ESB-018] Simple Purchase (list, detail, authorize / reject, delete)

```
Purpose:         List and read simple (cash) purchases from ESB Core -- goods bought
                 and paid for in one step; authorize, reject, or delete one.
Family:          Core
Method / Path:   GET    {{base_url}}/purchase/simple-purchase
                 GET    {{base_url}}/purchase/simple-purchase/{cashPurchaseNum}
                 PATCH  {{base_url}}/purchase/simple-purchase/{cashPurchaseNum}/authorize
                 PATCH  {{base_url}}/purchase/simple-purchase/{cashPurchaseNum}/reject
                 DELETE {{base_url}}/purchase/simple-purchase/{cashPurchaseNum}
Query (used):    page (default 1), limit (default 20, max 100), cashPurchaseNum,
                 supplierInvoiceNum, dateFrom, dateTo (YYYY-MM-DD), branchID (single),
                 indexSearch
Query (unused):  statusID, sort (vendor default -cashPurchaseNum), supplierID,
                 cashPurchaseTotal, additionalInfo, otherCashPurchaseNum
Request Body:    authorize, delete: none. reject: {"reason": string}, marked required.
Response:        list: paginated envelope; rows carry cashPurchaseNum,
                 cashPurchaseDate, supplierInvoiceNum, branch*, supplier*,
                 currencySign, cashPurchaseTotal, statusID, statusName, createdBy,
                 additionalInfo, approval[], approvalFlow[]
                 detail: header (as above plus cashPurchaseDueDate, dueDate (int),
                 purchaseTypeName, locationName, paymentName, picName, supplierPhone1,
                 currency*, rate, costCenter*, project*, costTotal, coaNo,
                 description, creator/editor/authorizer {fullName, username} with
                 their dates), simplePurchaseDetails[] (product*, qty, uomName, price,
                 discount, discountPercent, taxName, taxRate, vat, vatValue, dppValue,
                 total, notes, pricelist*), simplePurchaseCosts[] (coaNo, description,
                 amount, notes), purchasePayments[] (purchasePaymentNum, paymentDate,
                 paymentTotal, bankAccountName, statusName, ...),
                 simplePurchaseExpirations[], image[], supplierDefaultPIC{}
                 writes: envelope with result null
Errors:          400 "validation error or resource not found -- see errors array";
                 EC03100004 -> 404 in the gateway (assumed); EC03100001; EC03100032
Retry Policy:    reads retried with backoff (three attempts); writes exactly one attempt
Idempotency:     none documented; assume not idempotent
Laravel Adapter: App\Integrations\ESB\Clients\SimplePurchaseClient
Contract:        App\Integrations\Contracts\SimplePurchaseSource
DTOs:            App\Domain\Canonical\SimplePurchaseQuery, SimplePurchaseSummary,
                 SimplePurchasePage, SimplePurchase, SimplePurchaseLine,
                 SimplePurchaseCost, SimplePurchasePayment, ApprovalDecision
Mapper:          App\Integrations\ESB\Mappers\SimplePurchaseMapper
Actions:         App\Actions\SimplePurchases\DecideSimplePurchase, DeleteSimplePurchase
Routes:          GET    /simple-purchases, /simple-purchases/{number}
                 POST   /simple-purchases/{number}/decisions
                        (decision=authorize|reject, reason required for reject)
                 DELETE /simple-purchases/{number}
Permissions:     read:     esb.invoke
                 decision: esb.invoke + simple-purchases.approve
                 delete:   esb.invoke + simple-purchases.manage
Log operations:  simple-purchase.list, .view, .authorize, .reject, .delete
Tests:           tests/Feature/SimplePurchases/ (List, Detail, Decision, Delete),
                 tests/Unit/Integrations/ESB/SimplePurchaseMapperTest.php,
                 fixture tests/Fixtures/CoreSimplePurchase.php
```

Decisions and open points for these operations:

- **Source.** Transcribed on 2026-10-19 from the vendor's `openapi.json`. Examples are
  zero-valued placeholders; field names and types only. Date formats are `TBD`.
- **Single branch filter.** This list documents `branchID`, not the comma-separated
  `branchIDs` of the purchase order and invoice lists. The gateway takes one
  `branchId`. The screen does not offer it yet (no branch picker on this page).
- **Reject requires a reason**, exactly as for purchase orders (`[ESB-017]`): blank is
  refused before the call, capped at 500 (gateway's own, `TBD`), kept in the audit row.
- **No `access` flags** in this detail, unlike purchase orders, so Delete is offered to
  every holder of `simple-purchases.manage` and the ERP is left to refuse.
- **`validate` not used yet.** `POST .../{cashPurchaseNum}/validate?action=` checks
  cost-center access, active supplier, open period, and existing payments/returns
  without saving. A pre-check before authorize/delete is a natural slice 2; it was left
  out to keep slice 1 the same shape as the other purchasing documents.
- **`dueDate` (integer) not carried.** The detail sends both `cashPurchaseDueDate`
  (string) and `dueDate` (integer, undocumented unit). Only the string is shown. `TBD`.
- **Line tax:** only `taxName` is shown, as for purchase invoices.
- **Not carried:** `image` (vendor URLs, ADR-006), `supplierDefaultPIC`, `phoneNumber`,
  header `coaNo` / `description` (undocumented meaning), `simplePurchaseExpirations`,
  `approval[]`, `approvalFlow[]`, `releasePaymentNum`, `exportCsvCounter`,
  `branchFlagActive`.
- **One write at a time** under `simple-purchase-write:{number}`; ambiguous failures
  audited as `simple-purchase.write-unconfirmed`.
- **Not built:** create / update, `validate`, asset-image upload, bulk `upload` and
  `upload-form`.

### [ESB-019] Shared Lookups (pickers)

```
Purpose:         Reference data for searchable pickers used by many modules
                 (TODO-modules.md §1): branches, suppliers, locations, COA accounts,
                 document statuses, products for a transfer; metrics, category
                 types and taxes (added with [ESB-030]); product categories,
                 sub-categories, units, currencies, product details ([ESB-031]).
Family:          Core
Method / Path:   GET {{base_url}}/branch?flagActive=1
                 GET {{base_url}}/supplier/list?flagActive=1
                 GET {{base_url}}/location/list?flagActive=1
                 GET {{base_url}}/accounting/coa/active-non-module
                     ?coaNo=|description=&page=&limit=          (paginated)
                 GET {{base_url}}/accounting/coa/cash-bank
                 GET {{base_url}}/common/status
                 GET {{base_url}}/product/browse-transfer
                     ?branchID= (required)&productName=&page=&limit=  (paginated)
                 GET {{base_url}}/common/metrics
                 GET {{base_url}}/common/category-types/list
                 GET {{base_url}}/tax/list   (inactive rows dropped by the mapper)
                 GET {{base_url}}/product/category/all   (inactive rows dropped)
                 GET {{base_url}}/product/sub-category/all?flagActive=1
                 GET {{base_url}}/units/list?flagActive=1
                 GET {{base_url}}/common/currency/active
                 GET {{base_url}}/product-detail/browse?productName=&page=&limit=  (paginated)
                 GET {{base_url}}/purpose?purposeName=&flagActive=1&page=&limit=  (paginated)
Response:        bare arrays (all rows, "no pagination") except the two marked
                 paginated, which use the standard paginated envelope.
                 /branch:          branchID, branchCode, branchName
                 /supplier/list:   supplierID, supplierCode, supplierName
                 /location/list:   locationID, locationName, branchID, locationTypeID,
                                   flagActive, allowMinusStock
                 active-non-module: coaNo, description
                 cash-bank:        coaNo, description, branchID, flagActive
                 /common/status:   statusID, statusName
                 browse-transfer:  productDetailID, productID, productCode,
                                   productName, uomID, uomName, stockQty, pendingQty,
                                   categoryName, subCategoryName, ...
                 /common/metrics:  metricID, metricName
                 category-types:   categoryTypeID, categoryTypeName
                 /tax/list:        taxID, taxName, taxRate (-> meta.rate), flagActive, ...
Retry Policy:    read -- retried with backoff, up to three attempts
Idempotency:     n/a (reads)
Laravel Adapter: App\Integrations\ESB\Clients\LookupClient
Contract:        App\Integrations\Contracts\LookupSource
DTOs:            App\Domain\Canonical\LookupKind, LookupQuery, LookupOption, LookupPage
Mapper:          App\Integrations\ESB\Mappers\LookupMapper
Routes:          GET /lookups/{kind}?search=&page=&branchId=   (JSON)
                 kind = branches | suppliers | locations | coa-accounts |
                        cash-bank-accounts | statuses | transfer-products |
                        metrics | category-types | taxes | product-categories |
                        product-sub-categories | units | currencies | product-details |
                        purposes ([ESB-035])
Permissions:     esb.invoke; throttle:esb (per user)
Log operations:  lookup.branches, .suppliers, .locations, .coa-accounts,
                 .cash-bank-accounts, .statuses, .transfer-products, .metrics,
                 .category-types, .taxes, .product-categories, .product-sub-categories,
                 .units, .currencies, .product-details
Tests:           tests/Unit/Integrations/ESB/LookupMapperTest.php,
                 tests/Feature/Lookups/LookupTest.php, fixture tests/Fixtures/CoreLookup.php
```

Decisions and open points for these operations:

- **Source.** Transcribed on 2026-10-19 from the vendor's `openapi.json`. Examples are
  zero-valued placeholders; field names and types only.
- **One option shape.** Every row becomes a `LookupOption`: `value` (the id as a
  string; the COA number for accounts), `label`, `code`, and a small `meta` map
  (e.g. `branchId`, `uomName`). A row with no id or no label (vendor zero values `0` /
  `""`) is dropped.
- **Unpaginated lists are searched and paged in the gateway** (case-insensitive on
  label and code, 20 per page), so a picker never receives thousands of rows. The ESB
  still returns the whole list on every call; caching is not added yet.
- **No array query parameters are sent.** `/location/list` (`branchIDs`) and
  `cash-bank` (`branchID`) take multi-value filters whose wire format (`a=1&a=2` vs
  `a[]=1`) is not documented. The gateway asks for all active rows and filters by the
  row's own `branchID`. `TBD`, confirm the format to filter upstream.
- **Search upstream where the ESB pages.** COA accounts: a search that starts with a
  digit is sent as `coaNo`, anything else as `description` (the vendor documents no
  combined search). Transfer products: `productName`. `branchID` is required for
  transfer products and refused before the call when missing.
- **`/common/status` is global.** It lists every status id the ERP knows; which ids
  apply to which document is not documented (`/common/status/mapping` exists, `TBD`).
- **Purchase request form unchanged.** It keeps `PurchaseRequestSource::listBranches()`
  (`[ESB-007]`); moving it to the lookup is a later refactor.

### [ESB-020] Unit of Measure (list, detail)

```
Purpose:         Read units of measure (PCS, KG, ...) from ESB Core.
Family:          Core
Method / Path:   GET {{base_url}}/units
                 GET {{base_url}}/units/{uomID}
Query (used):    page (default 1), limit (default 20, gateway max 100), indexSearch
                 ("free-text search on unit name or metric name"), flagActive (1/0)
Query (unused):  uomName, metricID, notes, sort (vendor default uomName)
Response:        list: paginated; rows uomID, uomName, metricID, metricName, notes,
                 refTaxCode, refTaxName, flagActive (1/0)
                 detail: uomID, uomName, metricID, metricName, notes, refTaxID,
                 flagActive, createdBy, createdDate, editedBy, editedDate
Errors:          EC03100004 -> 404 (assumed, as the other Core details)
Retry Policy:    read -- retried with backoff, up to three attempts
Laravel Adapter: App\Integrations\ESB\Clients\UnitOfMeasureClient
Contract:        App\Integrations\Contracts\UnitOfMeasureSource
DTOs:            App\Domain\Canonical\UnitOfMeasureQuery, UnitOfMeasureSummary,
                 UnitOfMeasurePage, UnitOfMeasure
Routes:          GET /units, /units/{uomId}
Permissions:     esb.invoke
Log operations:  unit.list, unit.view
Tests:           tests/Feature/Units/UnitOfMeasureTest.php,
                 tests/Unit/Integrations/ESB/UnitOfMeasureMapperTest.php,
                 fixture tests/Fixtures/CoreUnitOfMeasure.php
```

### [ESB-021] Product Category (list, detail)

```
Purpose:         Read product categories, with the accounts they post to.
Family:          Core
Method / Path:   GET {{base_url}}/product/category
                 GET {{base_url}}/product/category/{categoryID}
Query (used):    page, limit, indexSearch ("free-text search on category name or
                 category type name"), categoryTypeID (1 Inventory, 2 Non Inventory,
                 3 Asset, 4 Non Depreciated Asset), flagActive (1/0)
Query (unused):  categoryName, categoryTypeIDs (multi-value, format undocumented),
                 notes, sort
Response:        list: paginated; rows categoryID, categoryName, categoryTypeID,
                 categoryTypeName, flagActive, notes
                 detail: categoryID, categoryCode, categoryName, categoryTypeID, coaNo,
                 cogsCoaNo, accrualBasisCoaNo, opnameCoaNo, productSalesCoaNo,
                 depCoaNo, depExpenseCoaNo, depLength, depLengthFiscal (months),
                 depreciationTypeID, depreciationTypeIDFiscal, flagAssetTax, notes,
                 flagActive, createdBy, createdDate, editedBy, editedDate
Errors:          EC03100004 -> 404 (assumed)
Retry Policy:    read -- retried with backoff, up to three attempts
Laravel Adapter: App\Integrations\ESB\Clients\ProductCategoryClient
Contract:        App\Integrations\Contracts\ProductCategorySource
DTOs:            App\Domain\Canonical\ProductCategoryQuery, ProductCategorySummary,
                 ProductCategoryPage, ProductCategory
Routes:          GET /product-categories, /product-categories/{categoryId}
Permissions:     esb.invoke
Log operations:  product-category.list, product-category.view
Tests:           tests/Feature/ProductCategories/ProductCategoryTest.php,
                 tests/Unit/Integrations/ESB/ProductCategoryMapperTest.php,
                 fixture tests/Fixtures/CoreProductCategory.php
```

### [ESB-022] Product Sub Category (list, detail)

```
Purpose:         Read product sub-categories.
Family:          Core
Method / Path:   GET {{base_url}}/product/sub-category
                 GET {{base_url}}/product/sub-category/{subCategoryID}
Query (used):    page, limit, indexSearch ("free-text search on sub-category name or
                 notes"), flagActive (1/0)
Query (unused):  subCategoryName, notes, sort
Response:        list: paginated; rows subCategoryID, subCategoryName,
                 deadStockThreshold (days), notes, flagActive
                 detail: the same plus createdBy, createdDate, editedBy, editedDate
Errors:          EC03100004 -> 404 (assumed)
Retry Policy:    read -- retried with backoff, up to three attempts
Laravel Adapter: App\Integrations\ESB\Clients\ProductSubCategoryClient
Contract:        App\Integrations\Contracts\ProductSubCategorySource
DTOs:            App\Domain\Canonical\ProductSubCategoryQuery,
                 ProductSubCategorySummary, ProductSubCategoryPage, ProductSubCategory
Routes:          GET /product-sub-categories, /product-sub-categories/{subCategoryId}
Permissions:     esb.invoke
Log operations:  product-sub-category.list, product-sub-category.view
Tests:           tests/Feature/ProductSubCategories/ProductSubCategoryTest.php,
                 tests/Unit/Integrations/ESB/ProductSubCategoryMapperTest.php,
                 fixture tests/Fixtures/CoreProductSubCategory.php
```

### [ESB-023] Supplier Category (list, detail)

```
Purpose:         Read supplier categories.
Family:          Core
Method / Path:   GET {{base_url}}/supplier/category/list      (paginated)
                 GET {{base_url}}/supplier/category/{supplierCategoryID}
Query (used):    page, limit, supplierCategoryName, flagActive (1/0)
Query (unused):  sort. (GET /supplier/category without /list returns all rows,
                 unpaginated -- not used.)
Response:        list: paginated; rows supplierCategoryID, supplierCategoryName,
                 flagActive
                 detail: the same plus createdBy, createdDate, editedBy, editedDate
Errors:          EC03100004 -> 404 (assumed)
Retry Policy:    read -- retried with backoff, up to three attempts
Laravel Adapter: App\Integrations\ESB\Clients\SupplierCategoryClient
Contract:        App\Integrations\Contracts\SupplierCategorySource
DTOs:            App\Domain\Canonical\SupplierCategoryQuery, SupplierCategorySummary,
                 SupplierCategoryPage, SupplierCategory
Routes:          GET /supplier-categories, /supplier-categories/{supplierCategoryId}
Permissions:     esb.invoke
Log operations:  supplier-category.list, supplier-category.view
Tests:           tests/Feature/SupplierCategories/SupplierCategoryTest.php,
                 tests/Unit/Integrations/ESB/SupplierCategoryMapperTest.php,
                 fixture tests/Fixtures/CoreSupplierCategory.php
```

Decisions and open points for `[ESB-020]`–`[ESB-023]`:

- **Source.** Transcribed on 2026-10-19 from the vendor's `openapi.json`. Examples are
  zero-valued placeholders; field names and types only. Date formats `TBD` (shown with
  `formatDateTime`, which leaves an unknown shape as-is).
- **Slice 1 is read-only.** Create / update / delete / restore / inactive, import /
  export and push / pull are not built (`TODO-modules.md` §2).
- **Sub-categories cannot be filtered by category.** The vendor documents no category
  filter on `/product/sub-category`, and its rows carry no `categoryID`. The plan's
  "filter by category" is `TBD` with the ESB team.
- **Category type names.** List rows carry `categoryTypeName`; the detail only
  `categoryTypeID`. The detail shows the name from the vendor's own legend (1 Inventory,
  2 Non Inventory, 3 Asset, 4 Non Depreciated Asset); an unknown id is shown as the id.
- **Category depreciation ids** (`depreciationTypeID`, `...Fiscal`) have no documented
  legend and are shown as ids.
- **`flagActive` is 1/0 here** (documented as integer), mapped to a boolean; anything
  else is unknown, not false.

### [ESB-024] Supplier (list, detail)

```
Purpose:         Read suppliers, with their contacts, bank accounts and product codes.
Family:          Core
Method / Path:   GET {{base_url}}/supplier                    (paginated)
                 GET {{base_url}}/supplier/{supplierID}
Query (used):    page, limit, indexSearch ("free-text search across supplier fields"),
                 flagActive (1/0)
Query (unused):  supplierName, supplierCode, dueDate, address, picName, picPhone,
                 supplierCategoryName, linkSupplierCodeEsbGoods,
                 includeEmptySupplierCode, selectedSupplierID, sort
Response:        list rows: supplierID, supplierCode, supplierName, supplierCategoryID,
                 category (name), address, contactPerson, cellPhone, dueDate (credit
                 days), vatSubject, lockVAT, flagActive (boolean here),
                 linkSupplierCodeEsbGoods, supplierNameEsbGoods
                 detail: the above plus city, state, country, zipCode, phone1, phone2,
                 fax, npwp, notes, payableCoaNo, advancePurchaseCoaNo, paymentID,
                 flagInvoiceOnReceipt, isAnyTransaction, supplierPic[] (picSupplierID,
                 picName, cellPhone, email, flagDefault, flagSendingPOEmail,
                 greetingID), supplierBank[] (bankID, bankName, bankAccountName,
                 bankAccountNumber), supplierBankOy[], supplierProduct[] (ID,
                 productDetailID, productName, externalProductCode, uomName),
                 imageAttachments[], createdBy/Date, editedBy/Date
Errors:          EC03100004 -> 404 (assumed)
Retry Policy:    read -- retried with backoff, up to three attempts
Laravel Adapter: App\Integrations\ESB\Clients\SupplierClient
Contract:        App\Integrations\Contracts\SupplierSource
DTOs:            App\Domain\Canonical\SupplierQuery, SupplierSummary, SupplierPage,
                 Supplier, SupplierContact, SupplierBankAccount, SupplierProduct
Routes:          GET /suppliers, /suppliers/{supplierId}
Permissions:     esb.invoke
Log operations:  supplier.list, supplier.view
Tests:           tests/Feature/Suppliers/SupplierTest.php,
                 tests/Unit/Integrations/ESB/SupplierMapperTest.php,
                 fixture tests/Fixtures/CoreSupplier.php
```

Decisions and open points:

- **One call for the detail.** The detail already nests `supplierPic[]` and
  `supplierBank[]`, so `GET /supplier/{id}/contacts` and `/banks` (same fields) are not
  called.
- **Bank account numbers are masked in the gateway.** The mapper keeps only the last
  four digits (`•••• 6789`); the full number never reaches the browser. A read-only
  screen does not need it and the ERP still has it. `TBD`: the business may decide
  otherwise, or S3 (edit) may need the full value behind a stronger permission.
- **Not carried:** `supplierBankOy[]` (payment-provider accounts, purpose undocumented),
  `imageAttachments[]` (vendor URLs, ADR-006), ESB Goods link fields, `greetingID`,
  `paymentID` is carried as an id only (no payment method name in the detail).
- **`flagActive` is a boolean in supplier rows** (1/0 in the query), unlike the
  `[ESB-020]`–`[ESB-023]` lists. Both are accepted.

### [ESB-025] Chart of Accounts (list, detail)

```
Purpose:         Read the chart of accounts, as an outline by level, and one account
                 with its mapping rows.
Family:          Core
Method / Path:   GET {{base_url}}/accounting/coa            ("all active", unpaginated)
                 GET {{base_url}}/accounting/coa/{coaNo}
Query:           none documented on the list
Response:        list and detail rows: coaNo, description, coaLevel (1-4), orderList,
                 branchID, currency (sign), flagModule (1/0), allowBudget (1/0),
                 flagActive (1/0), additionalInfo, releasePaymentCode,
                 createdBy/Date, editedBy/Date, mapCoa[] (ID, coaNo, key,
                 mapCoaTypeID, currencyID)
Errors:          EC03100004 -> 404 (assumed)
Retry Policy:    read -- retried with backoff, up to three attempts
Laravel Adapter: App\Integrations\ESB\Clients\CoaAccountClient
Contract:        App\Integrations\Contracts\CoaAccountSource
DTOs:            App\Domain\Canonical\CoaAccountQuery, CoaAccountSummary,
                 CoaAccountPage, CoaAccount, CoaMapping
Routes:          GET /coa-accounts?search=&level=&page=, /coa-accounts/{coaNo}
Permissions:     esb.invoke (managing COA will need its own permission, TODO §2.7)
Log operations:  coa.list, coa.view
Tests:           tests/Feature/CoaAccounts/CoaAccountTest.php,
                 tests/Unit/Integrations/ESB/CoaAccountMapperTest.php,
                 fixture tests/Fixtures/CoreCoaAccount.php
```

Decisions and open points:

- **Active accounts only.** The documented list is "all active". `/accounting/coa/index`
  ("with mapping rows") may include inactive ones; its scope is undocumented (`TBD`).
- **Searched, filtered and paged in the gateway** (50 per page): the list is
  unpaginated. Search matches the number (prefix) or the description; level 1–4.
- **Outline, not a tree.** Rows carry `coaLevel` and `orderList` but no parent. The
  page lists accounts in `orderList` order (then number) indented by level; no parent
  is derived (`TBD` whether `orderList` reliably groups children under parents).
- **`coaNo` must start with a digit.** The detail path `/accounting/coa/{coaNo}` shares
  its segment with literal vendor paths (`/export`, `/index`, `/tax`, `/cash-bank`), so
  a value like `export` would reach the export endpoint. The route allows
  `[0-9][0-9A-Za-z._-]{0,31}` only and the value is URL-encoded. Account numbers that
  start with a letter would be unreachable (`TBD`, none seen).
- **`branchID` 0** is carried as null ("not branch-specific", assumed).
- **Mapping rows** are shown as key / type id / currency id; the type and currency ids
  have no documented legend here.

### [ESB-026] Product Detail

```
Purpose:         Read one product with its units (product details), for the product
                 detail page. Extends [ESB-001], which stays the list.
Family:          Core
Method / Path:   GET {{base_url}}/product/{productID}
Response:        productID, productCode, productName, categoryID, categoryName,
                 subCategoryID, subCategoryName, bomID, bomName, purchasable,
                 saleable, requestable, VAT, flagLuxuryItem (1/0), receiptTolerance,
                 coretaxProductCode, notes, flagActive (boolean), customFields
                 {field1..field5}, createdBy/Date, editedBy/Date,
                 productDetails[] (productDetailID, uomID, uomName, qty, baseUnit,
                 isBase, isPurchase, isSales, isStock, isTransfer, SKU, basePrice,
                 weight, cubication, flagActive, menuID, menuName, ...)
Errors:          EC03100004 -> 404 (assumed)
Retry Policy:    read -- retried with backoff, up to three attempts
Laravel Adapter: App\Integrations\ESB\Clients\ProductClient::findProduct()
Contract:        App\Integrations\Contracts\ProductSource::findProduct()
DTOs:            App\Domain\Canonical\ProductDetail, ProductUnit
Routes:          GET /products/{productId}
Permissions:     esb.invoke
Log operations:  product.view (the same operation DocumentTemplateClient uses for its
                 import check, [ESB-016])
Tests:           tests/Feature/Products/ProductDetailTest.php,
                 tests/Unit/Integrations/ESB/ProductDetailMapperTest.php,
                 fixture tests/Fixtures/CoreProductDetail.php
```

Decisions and open points:

- **The list stays on `/product/list`** (`[ESB-001]`). `GET /product` returns a subset
  of its fields (no category / sub-category / BOM ids, no BOM or unit filter), so it
  is not used.
- **Two adapters read this endpoint.** `DocumentTemplateClient` maps it to the narrow
  `ProductUnits` for the template import (`[ESB-016]`); `ProductClient` maps the whole
  product for the detail page. Same upstream call, different canonical shapes.
- **`basePrice` is display-only** (the ERP is the authority on prices); amounts shown
  with `formatAmount`.
- **Not carried:** `customFields` (labels undocumented), `coretaxProductCodeID`,
  `canEditSKU`, `goAppProduct`, `isUsedInTransaction`, `productDetailMenus[]` (the
  unit's single `menuName` is shown).

### [ESB-027] Pricelist (active list; temporary list and detail)

```
Purpose:         Read supplier prices: the active pricelist rows, and temporary
                 pricelists (proposed prices waiting for approval, or rejected).
Family:          Core
Method / Path:   GET {{base_url}}/pricelist                       (paginated)
                 GET {{base_url}}/pricelist/temp                  (paginated)
                 GET {{base_url}}/pricelist/temp/{pricelistNum}
Query (used):    /pricelist: page, limit, indexSearch
                 /pricelist/temp: page, limit, indexSearch, statusID
                 (38 = Waiting For Approval, 2 = Rejected)
Query (unused):  priceDate, supplierName, productName, productCode, unit, currencyID,
                 expireDate, price, currencyIDs / unitIDs (comma-separated),
                 branchID, branchName, pricelistNum, sort
Response:        /pricelist rows: ID ("pricelist detail row ID"), pricelistNum,
                 productID, productDetailID, productCode, productName, uomID, unit,
                 supplierID, supplierName, currencyID, currencyName, price,
                 priceDate (YYYY-MM-DD), expireDate, applicableBranch {type
                 ALL|SPECIFIC, branches[]}
                 /pricelist/temp rows: pricelistNum, supplierName, currencyName,
                 priceDate, expireDate, statusID, statusName, applicableBranch
                 temp detail: the above plus supplierID, currencyID, pricelistDetails[]
                 (productDetailID, productCode, productName, unitName, price,
                 lastPrice), createdBy/Date, editedBy/Date, rejectedBy/Date,
                 applicableBranch.branches[] (branchID, branchCode, branchName,
                 flagActive)
Errors:          EC03100004 -> 404 (assumed)
Retry Policy:    read -- retried with backoff, up to three attempts
Laravel Adapter: App\Integrations\ESB\Clients\PricelistClient
Contract:        App\Integrations\Contracts\PricelistSource
DTOs:            App\Domain\Canonical\PricelistQuery, PricelistEntry, PricelistPage,
                 TempPricelistQuery, TempPricelistSummary, TempPricelistPage,
                 TempPricelist, TempPricelistLine, BranchScope
Routes:          GET /pricelists, /pricelists/temp, /pricelists/temp/{pricelistNum}
Permissions:     esb.invoke
Log operations:  pricelist.list, pricelist.temp-list, pricelist.temp-view
Tests:           tests/Feature/Pricelists/PricelistTest.php,
                 tests/Unit/Integrations/ESB/PricelistMapperTest.php,
                 fixture tests/Fixtures/CorePricelist.php
```

Decisions and open points:

- **No detail for active pricelist rows yet.** List rows carry `ID`, documented as the
  "pricelist detail row ID"; `GET /pricelist/{pricelistID}` takes the "master pricelist
  ID". Whether they are the same is not documented, and guessing could open the wrong
  record. The rows already carry everything the detail does except `lastPrice` and
  the audit fields. `[!]` ask the ESB team.
- **`pricelistNum` must contain a digit.** `/pricelist/temp/{pricelistNum}` shares its
  segment with the literal `/pricelist/temp/export`; a number without a digit is
  refused before the call. The value is URL-encoded.
- **Prices are display-only** (`formatAmount` with the currency name); the ERP is the
  authority.
- **`applicableBranch`** becomes a `BranchScope`: `all` (type `ALL`) or the listed
  branch names (`SPECIFIC`). Any other type is unknown (null).
- **Temporary → active flow** (authorize / reject) is S2 and `[!]` in TODO §2.5.

### [ESB-028] Payment Method (OMS Master POS) — documented, NOT built

```
Purpose:         List the payment methods configured for one branch.
Family:          POS (production https://esbcore.co.id, staging
                 https://int-erp.esb.co.id, staging-int TBD)
Method / Path:   POST {{base_url}}/external/general/get-payment-method
Auth:            Authorization: Basic base64(backend_username:backend_password)
                 -- NOT the gateway's shared bearer token (ADR-009)
Request Body:    {"filterBranchCode": "OUT1"}  (required: "Branch Code cannot be
                 blank.")
Response:        NOT the ESB envelope. A top-level object keyed "1", "2", ...:
                 { "1": { "paymentMethodType": "CASH",
                          "paymentMethods": [ { "paymentMethodID": 9,
                            "paymentMethodCode": "CASH",
                            "paymentMethodName": "CASH" } ] } }
Errors:          validation: {"errors": {"branchCode": ["..."]}} (documented under
                 "success"); auth: [{"name": "Unauthorized", "status": 401, ...}]
Retry Policy:    would be a read via POST (ADR-025) once built
Source:          vendor apiDoc esb-oms/api_data.json, group Master_POS, downloaded
                 2026-10-19
```

Why it is not built (`TODO-modules.md` §2.8, `[!]`):

- **Different credentials.** HTTP Basic with a "backend" username and password. The
  gateway holds one ESB service credential used to obtain a bearer token (ADR-009,
  ADR-018); it has no POS backend credential, and how such a second credential would
  be stored and rotated is an architecture decision (ADR needed), and the credential
  itself must come from the ESB team / business.
- **No envelope.** `EsbClient` and `ErrorClassifier` classify responses by the
  envelope's `code` (`TODO-modules.md` §0.3); this response has none, and its
  validation error is shaped differently again. Reading it needs its own, explicitly
  designed response handling — part of the same ADR.
- **No staging-int host** for the POS family (`config/esb.php`).
- Open: whether payment methods are also exposed by ESB Core (none found in the
  2026-10-19 OpenAPI under that name).

### [ESB-029] Master Data Writes, S2 (delete / deactivate / restore / decide)

```
Purpose:         Change the state of an existing master data record from the gateway
                 (TODO-modules.md §2, S2).
Family:          Core
Method / Path:   units                 DELETE /units/{uomID}            -> delete
                                       PATCH  /units/{uomID}/restore    -> restore
                 product categories    DELETE /product/category/{id}    -> delete
                                       PATCH  .../{id}/inactive         -> deactivate
                                       PATCH  .../{id}/restore          -> restore
                 product sub-categories  same shape under /product/sub-category
                 supplier categories   GET    /supplier/category/{id}/check-delete (pre-check)
                                       DELETE /supplier/category/{id}   -> delete
                                       PATCH  .../{id}/restore          -> restore
                 suppliers             DELETE /supplier/{id}            -> deactivate
                                       PATCH  /supplier/{id}/restore    -> restore
                 COA accounts          DELETE /accounting/coa/{coaNo}   -> deactivate
                                       PATCH  .../{coaNo}/restore       -> restore
                 products              DELETE /product/{id}             -> delete
                                       PATCH  /product/{id}/restore     -> restore
                 temporary pricelists  PATCH  /pricelist/temp/{num}/authorize | /reject
                                       DELETE /pricelist/temp/{num}
Request Body:    none for any of them
Response:        envelope with result null; DELETE /units also documents `hardDelete`
                 (removed vs deactivated) and the unit name in result
Errors:          400 validation / not found (EC03100004 -> 404); supplier deactivate
                 answers a conflict while transactions are ongoing; COA delete is
                 blocked by system-generated descendants; unit restore fails when
                 another unit has the same name and metric
Retry Policy:    writes -- exactly one attempt; check-delete is a read (retried)
Idempotency:     none documented; assume not idempotent
Laravel:         App\Actions\Support\RecordWrites (+ MasterRecord, RecordOperation),
                 App\Actions\<Module>\<X>Writes; temporary pricelists reuse
                 DocumentWrites (DecideTempPricelist, DeleteTempPricelist)
Routes:          DELETE /<module>/{id}, PATCH /<module>/{id}/deactivate|restore;
                 POST /pricelists/temp/{num}/decisions, DELETE /pricelists/temp/{num}
Permissions:     esb.invoke + units.manage | product-categories.manage |
                 product-sub-categories.manage | supplier-categories.manage |
                 suppliers.manage | coa.manage | products.manage |
                 pricelists.approve / pricelists.manage
Audit:           <module>.deleted | .deactivated | .restored, pricelist.authorized |
                 .rejected | .deleted, and <module>.write-unconfirmed
Tests:           tests/Feature/MasterData/MasterDataWriteTestCase.php (+ one per module),
                 tests/Feature/Actions/Support/RecordWritesTest.php,
                 tests/Feature/Http/HandlesRecordWritesTest.php,
                 tests/Feature/Pricelists/TempPricelistWriteTest.php
```

Decisions and open points:

- **Names follow what the ERP does, not the HTTP verb.** The vendor's `DELETE` on
  suppliers and COA accounts deactivates (COA: with its active sub-accounts), so the
  gateway calls it *deactivate* and offers no permanent delete for them.
- **One write per record at a time** (`<prefix>-write:{id}`), whatever the operation.
- **The supplier-category pre-check runs before the guarded write.** It is a read;
  inside the guard, its timeout would be audited as an unconfirmed delete that never
  happened. `result: true` is the only "yes"; false → "still in use", nothing sent.
- **A deleted unit may only have been deactivated** (`hardDelete: false`); the flag is
  audited when the ERP sends it.
- **Authorizing a temporary pricelist removes it**, so the outcome lands on the list,
  not on its (now missing) page. Decisions are offered only for status 38 ("Waiting For
  Approval"), the one the vendor accepts.
- **The ERP's own reasons are shown** with a refusal (up to three `errors[]` messages,
  ADR-021); the envelope `message` is not.
- **Not built:** temporary-product authorize / reject / update / delete
  (`PATCH /product/authorize|reject`, `/product/{id}/temp`) — `[!]` TODO §2.1: the
  difference between "pending" and "temp" products and who authorizes them is
  undocumented.

### [ESB-030] Master Data Writes, S3 (create / update)

```
Purpose:         Create and edit master data records from the gateway
                 (TODO-modules.md §2, S3). First batch: units, product sub-categories,
                 supplier categories, product categories.
Family:          Core
Method / Path:   units                   POST /units, PUT /units/{uomID}
                 product sub-categories  POST /product/sub-category, PUT .../{id}
                 supplier categories     POST /supplier/category/  (trailing slash, as
                                         documented), PUT /supplier/category/{id}
                 product categories      POST /product/category, PUT .../{id}
Request Body:    units:          uomName* (<=50, unique), metricID* (must exist),
                                 refTaxID, notes (<=100)
                 sub-categories: subCategoryName* (<=50), deadStockThreshold* (>=1 day),
                                 notes (<=100); flagActive not sent
                 supplier cat.:  supplierCategoryName* (<=50, ASCII only, unique)
                 categories:     categoryName* (<=50, unique), categoryTypeID*,
                                 categoryCode (<=3), notes (<=100), coaNo, cogsCoaNo,
                                 accrualBasisCoaNo, opnameCoaNo, productSalesCoaNo,
                                 depCoaNo, depExpenseCoaNo (each <=20), depLength,
                                 depLengthFiscal, depreciationTypeID,
                                 depreciationTypeIDFiscal, flagAssetTax (0/1)
Response:        envelope; result [] / null for all but the supplier-category create,
                 whose result is {supplierCategoryID}
Errors:          400 validation with errors[] (attribute = request field); not found
                 EC03100004 -> 404 on update
Retry Policy:    writes -- exactly one attempt
Idempotency:     none documented; assume not idempotent
Laravel:         App\Actions\Support\RecordWrites::create() / run(Update);
                 App\Actions\<Module>\<X>Writes::create() / update();
                 App\Http\Controllers\Concerns\HandlesRecordForms;
                 drafts App\Domain\Canonical\<X>Draft; mappers ::requestFrom()
Routes:          GET /<module>/create, POST /<module>, GET /<module>/{id}/edit,
                 PUT /<module>/{id}
Permissions:     esb.invoke + the module's manage permission (as [ESB-029])
Audit:           <module>.created (provider, name, id when returned) | .updated
                 (provider, id), and <module>.write-unconfirmed (operation create:
                 id null + name)
Tests:           tests/Feature/MasterData/MasterDataFormTestCase.php (+ one per module),
                 tests/Feature/Actions/Support/RecordWritesTest.php,
                 tests/Feature/Http/HandlesRecordFormsTest.php,
                 resources/js/components/record-form.test.tsx, pages/*/form.test.tsx
```

Decisions and open points:

- **Validated here first.** Each form request mirrors the vendor schema (required
  fields, lengths, ASCII for supplier categories, COA number shape), so a bad value is
  a field error before anything reaches the ESB.
- **The ERP's field errors go back to the form.** `errors[].attribute` is mapped from
  the vendor field to the form field; anything unmapped, or a refusal without field
  errors, is shown for the form as a whole.
- **Where a create lands.** The ERP returns no id for most creates, so the person goes
  back to the list; a supplier-category create returns its id and lands on it. An
  unconfirmed create lands on the list with its correlation id.
- **One create per person at a time** (`<prefix>-create:{userId}`) — a new record has
  no id to lock on; it stops a double submit. Updates share the per-record lock.
- **Product category: every field is sent.** `PUT` replaces the record, so empty
  fields go as the vendor's own "none" (`""` / `0`, as its detail returns them) rather
  than being left out. Depreciation type ids have no documented legend; the form takes
  the id.
- **Unit: no tax → no `refTaxID`.** Sending `0` might fail a "must exist" check;
  whether leaving it out on update clears an existing reference is `TBD`.
- **Sub-category: `flagActive` is not sent**; deactivate / restore own it. Whether
  `PUT` without it resets the flag is `TBD`.
- **Not in this batch:** suppliers (`PATCH`, contacts / banks), products, COA accounts
  (+ reorder), pricelists — P9c.

### [ESB-031] Master Data Writes, S3 batch 2 (product, pricelist, COA reorder)

```
Purpose:         Create / edit products and proposed pricelists, and move COA accounts
                 (TODO-modules.md §2, S3 batch 2).
Family:          Core
Method / Path:   products              POST /product, PUT /product/{productID}
                                       (the update first GETs /product/{productID})
                 pricelists            POST /pricelist, PUT /pricelist/temp/{num}
                 COA accounts          PATCH /accounting/coa/reorder {coaNo, type: up|down}
Request Body:    product:   productName* (<=100), productCode (<=50), categoryID*,
                            subCategoryID*, notes (<=100), purchasable, saleable,
                            requestable, vat, flagLuxuryItem, receiptTolerance,
                            bomID, coretaxProductCodeID, customFields,
                            productDetails[]* {productDetailID, uomID*, qty, isBase,
                            isPurchase, isSales, isStock, isTransfer, sku (<=50),
                            basePrice, weight, flagActive, menuID, cubication}
                 pricelist: supplierID*, currencyID*, priceDate*, expireDate*
                            (YYYY-MM-DD), branchIDs (empty = all), pricelistDetails[]*
                            {productDetailID* (unique), price (>=0, decimal(20,4))}
Response:        product create: {isTemp, productID}; others [] / null;
                 reorder echoes {coaNo, type}
Errors:          400 validation with errors[]; EC03100004 -> 404
Retry Policy:    writes -- exactly one attempt; the product GET before an update is a
                 read (retried) and runs outside the write lock
Idempotency:     none documented; assume not idempotent
Laravel:         App\Actions\Products\ProductWrites::create()/update(),
                 App\Actions\Pricelists\PricelistWrites, CoaAccountWrites::move();
                 App\Actions\Support\CreatedRecord (id + pendingApproval)
Routes:          GET /products/create, POST /products, GET /products/{id}/edit,
                 PUT /products/{id}; GET /pricelists/create, POST /pricelists,
                 GET /pricelists/temp/{num}/edit, PUT /pricelists/temp/{num};
                 PATCH /coa-accounts/{coaNo}/move {direction}
Permissions:     esb.invoke + products.manage | pricelists.manage | coa.manage
Audit:           product.created (id, pending_approval, name, units) | .updated;
                 pricelist.created (name, supplier_id, lines) | .updated; coa.moved
                 (direction); plus *.write-unconfirmed
Tests:           tests/Feature/Products/ProductFormTest.php,
                 tests/Unit/Integrations/ESB/ProductRequestMapperTest.php,
                 tests/Feature/Pricelists/PricelistFormTest.php,
                 tests/Feature/CoaAccounts/CoaAccountMoveTest.php, page tests
```

Decisions and open points:

- **Product update is read-modify-write.** PUT replaces the product, but the form does
  not edit BOM, CoreTax code, custom fields, or a unit's menu and cubication. The
  gateway reads the product just before the write (outside the lock, so a read
  failure is never an "unconfirmed update") and carries those over. A unit id must be
  one of the product's own. `pushToGoApp` is never read, so never sent (`TBD`).
- **A new product may wait for approval.** `isTemp: true` → the toast says so and the
  person lands on the list (the product's page may not show it yet).
  Authorizing / rejecting such products is still `[!]` (TODO §2.1).
- **Exactly one base unit, each unit of measure once** — assumed; the vendor states
  neither.
- **A new pricelist lands on the proposals.** The ERP returns no number; authorizing
  copies a proposal into the active prices. Whether *every* create becomes a proposal
  is still `[!]` (TODO §2.5). Editing a proposal shares the lock with authorize /
  reject / delete (`pricelist-write:{num}`). The temp detail now carries `currencyId`
  and branch ids so an edit can send them back. Active pricelists are not editable:
  their detail id is ambiguous (`[!]`).
- **COA reorder:** the vendor lists `coaNo` as a "path parameter" of a path that has
  none; it is sent in the body. No confirmation in the UI — the opposite move undoes it.
- **Not built (`[!]`):**
  - Supplier create / update: `supplierPic[].greetingID` is required and must exist,
    and no endpoint lists greetings; `paymentID` belongs to Payment Method (blocked,
    [ESB-028]); the update is a PATCH whose effect on omitted `supplierBank[]` is
    undocumented, and the gateway only holds masked account numbers to send back.
  - COA create / update: `coaNo` is "ignored on create" (how the number is assigned is
    undocumented), the parent account is not in the schema, and the detail carries a
    currency name but no `currencyID` to send back.

### [ESB-032] Master Data S4 (imports, pricelist prices, supplier attachments)

```
Purpose:         Hand the ERP master data spreadsheets, show prices that help fill a
                 pricelist, and manage supplier attachments (TODO-modules.md §2, S4).
Family:          Core
Method / Path:   imports (multipart, field "file"):
                   units                  POST  /units/import
                   product categories     POST  /product/category/upload
                   product sub-categories POST  /product/sub-category/upload
                   new products           POST  /product/import
                   product changes        PUT   /product/import
                   pricelists             POST  /pricelist/import
                   new suppliers          POST  /supplier/upload
                   supplier changes       PATCH /supplier/upload
                   COA + account mappings POST  /accounting/coa/upload
                 prices (reads):
                   GET /pricelist/last-price?supplierID&currencyID&productDetailIDs=1,2&priceDate
                   GET /pricelist/history/purchase-invoice?supplierID&currencyID
                       &productDetailID&flagShowAll=0|1
                 supplier attachments:
                   PATCH  /supplier/{id}/attachment  (multipart, field "files", repeated)
                   DELETE /supplier/{id}/attachment  (removes every attachment)
Response:        imports: result null (queued) except COA {coaCount, accountMappingCount};
                 last-price [{productDetailID, price}] (unknown -> 0);
                 history [{purchaseInvoiceNum, purchaseInvoiceDate, price, supplierID,
                 supplierName}] (up to 5); attachment upload {urls}
Errors:          400 with errors[] (row / attribute for sheets); EC03100004 -> 404
Retry Policy:    imports and attachments -- writes, exactly one attempt; price lookups
                 are GETs (retried)
Idempotency:     none; importing the same sheet twice may create every row twice
Laravel:         App\Actions\MasterData\ImportMasterData (+ MasterDataImport enum,
                 MasterDataImportSource / MasterDataImportClient);
                 App\Http\Requests\Concerns\ValidatesExcelUpload (shared with the
                 purchase request import); PricelistSource::lastPrices() /
                 purchasePrices(); SupplierWrites::attach() / deleteAttachments();
                 EsbClient::upload(method:) / uploadFiles()
Routes:          GET|POST /imports/{kind}; GET /pricelists/last-prices,
                 GET /pricelists/purchase-history (JSON); POST|DELETE
                 /suppliers/{id}/attachments
Permissions:     imports: esb.invoke + the module's manage permission (checked per kind);
                 prices: esb.invoke; attachments: esb.invoke + suppliers.manage
Audit:           master-data.imported (kind, filename, bytes, sha256, saved counts) /
                 master-data.import-unconfirmed; supplier.attachments-uploaded (each
                 file's name, size, sha256) / .attachments-deleted; supplier.write-unconfirmed
Tests:           tests/Feature/MasterData/MasterDataImportTest.php,
                 tests/Feature/Pricelists/PricelistPriceReadTest.php,
                 tests/Feature/Suppliers/SupplierAttachmentTest.php, EsbClientTest
                 (verb + several files), page / component tests
```

Decisions and open points:

- **The gateway neither parses nor stores a sheet** (as for purchase requests). It
  checks size (2 MB — documented for products and pricelists, applied to all), the
  extensions the vendor names (.xlsx; products and COA also .xls), and the leading
  bytes. One import per kind per person at a time.
- **Most imports are queued by the ERP.** "Success" only means the ERP took the file;
  rows it rejects later are not reported back, and the toast says so. Only the COA upload
  saves in the request and returns counts. The import templates are the ERP's; no
  endpoint serves them (`TBD`: link to where users get them).
- **Last price 0 is "no active price"**: the vendor answers 0 for an unknown product, so a
  real price of 0 cannot be told apart.
- **Supplier attachments are counted, not shown.** The detail's `imageAttachments` are URLs
  in the ERP's file storage; see exports below. Upload uses POST on the gateway route
  (PHP parses multipart only on POST) and PATCH upstream. The vendor removes all
  attachments at once, so the UI confirms "remove all". Accepted: PDF / JPEG / PNG, up to
  5 files of 5 MB — assumed, the vendor documents no limit.
- **Exports are not built (`[!]`).** Every export (`/units/export`, `/product/category/export`,
  `/product/sub-category/export`, `POST /product/export`, `/pricelist/export`,
  `/supplier/export/{mode}`, `/accounting/coa/export`) makes the ERP write a file and answer
  with its location — under `result.path`, `result.url` or `url` depending on the endpoint,
  and on an undocumented host. Letting the browser follow that URL bypasses ADR-006
  (browser never calls the ESB); proxying it means the gateway fetching a URL the ERP
  chose. Needs: where the files live, whether the URL is signed / authenticated / expiring,
  and a decision (ADR) between proxying (restricted to the ESB hosts) and generating
  exports in the gateway from the lists.

### [ESB-033] Inventory Documents, S1 (list, detail)

```
Purpose:         Read the five inventory documents (TODO-modules.md §3, S1): goods
                 transfer requests, goods deliveries, goods receipts, item journals,
                 simple transfers. One canonical shape for all five (ADR-026).
Family:          Core
Method / Path:   GTR   GET /inventory/goods-transfer-request      ?transferNum&originBranchID
                       &statusID&dateFrom&dateTo&indexSearch&page&limit
                       GET /inventory/goods-transfer-request/{transferNum}
                 GD    GET /inventory/goods-delivery              ?goodsDeliveryNum&branchID
                       &statusID&dateFrom&dateTo&page&limit
                       GET /inventory/goods-delivery/{goodsDeliveryNum}
                       GET /inventory/goods-delivery/get-details-outstanding-qty
                           ?goodsDeliveryNum&refNum*&transType*   (outstanding per line)
                 GR    GET /inventory/goods-receipt               ?goodsReceiptNum&branchID
                       &statusID&dateFrom&dateTo&page&limit
                       GET /inventory/goods-receipt/{goodsReceiptNum}
                       GET /inventory/goods-receipt/stock-check/{goodsReceiptNum}
                 IJ    GET /inventory/item-journal                ?itemJournalNum&branchID
                       &statusID&dateFrom&dateTo&page&limit
                       GET /inventory/item-journal/{itemJournalNum}
                 ST    GET /simple-transfer                       ?simpleTransferNum&statusID
                       &dateFrom&dateTo&page&limit
                       GET /simple-transfer/{simpleTransferNum}
                       GET /simple-transfer/stock-check/{simpleTransferNum}
Response:        lists: standard paginated envelope; details: one object with a lines
                 array (transferDetails / goodsDeliveryDetails / goodsReceiptDetail /
                 itemJournalDetails / simpleTransferDetails); stock-check
                 {isStockHasBeenUsed}; outstanding [{productDetailID, qty}]
Statuses:        GTR 1 New, 2 Rejected, 3 Authorized, 6 Delivering, 8 Finished;
                 GD 1, 2, 3, 8 Finished, 27 Released;
                 GR 1, 2, 3, 4 Receiving, 5 Received, 8 Finished, 11 Invoice, 25 Closed;
                 IJ 1, 2, 3, 38 Waiting For Approval, 53 Approved; ST 1, 2, 3
Errors:          EC03100004 -> 404
Retry Policy:    reads -- retried with backoff, up to three attempts
Idempotency:     n/a (reads)
Laravel:         App\Domain\Canonical\InventoryKind, InventoryDocument(Summary),
                 InventoryLine, InventoryParty, InventoryDocumentQuery / Page;
                 App\Integrations\Contracts\InventoryDocumentSource;
                 App\Integrations\ESB\Clients\InventoryDocumentClient;
                 App\Integrations\ESB\Mappers\InventoryDocumentMapper
Routes:          GET /goods-transfer-requests, /goods-deliveries, /goods-receipts,
                 /item-journals, /simple-transfers, and /{kind}/{number}
Permissions:     esb.invoke
Tests:           tests/Unit/Integrations/ESB/InventoryDocumentMapperTest.php,
                 tests/Feature/Inventory/InventoryDocumentTestCase.php (+ one per kind),
                 fixture tests/Fixtures/CoreInventory.php
```

Decisions and open points:

- **Source.** Transcribed on 2026-10-19 from the vendor's `openapi.json`; the examples are
  zero-valued, so 0 / "" are "none".
- **Numbers must contain a digit.** Every detail path shares its segment with literal
  vendor paths (`/goods-receipt/initialize`, `/browse-return`, `/goods-delivery/create`,
  `/get-details-outstanding-qty`, `/goods-transfer-request/detail`), so a number
  without a digit is a 404 before anything is sent.
- **Common list filters only:** number, date range, status, and branch where the vendor
  offers one (origin branch for transfer requests; none for simple transfers, which
  filter by location). Free-text `indexSearch` exists only on transfer requests.
- **Where from / where to.** Transfer requests and simple transfers have an origin and a
  destination; a delivery goes from a branch (+ location) to a branch or a customer; a
  receipt comes from a source (supplier, branch or customer — `sourceName`) into a branch
  and location; an item journal has one branch and location.
- **Extra reads on the detail.** A goods delivery with a reference also asks for the
  outstanding quantity per line; a goods receipt and a simple transfer ask the stock
  check (whether the received / transferred stock has been used since — it blocks a
  delete later). A failure of these extra reads leaves their column / notice empty rather
  than failing the page.
- **Attachments are counted, not shown** (`imageAttachments`, `goodsDeliveryURLAttachments`
  are URLs in the ERP's storage; same open point as [ESB-032] exports).
- **Not read yet:** GTR product details (`POST /detail`, needed for S3 create), item
  journal approval flow rows (`approval[]`, `approvalFlow[]` — the levels' meaning is
  undocumented), goods receipt expirations and assets, goods delivery multi-address.

### [ESB-034] Inventory Documents, S2 (authorize, reject, delete, finish)

```
Purpose:         Change the state of the five inventory documents (TODO-modules.md §3,
                 S2): authorize, reject, delete; finish / reopen a goods transfer request.
Family:          Core
Method / Path:   PATCH  {path}/{number}/authorize          (no body)
                 PATCH  {path}/{number}/reject             (no body; item journal:
                                                            optional {reason})
                 DELETE {path}/{number}
                 PATCH  /inventory/goods-transfer-request/{transferNum}/finish
                 PATCH  /inventory/goods-transfer-request/{transferNum}/unfinished
                 {path}: /inventory/goods-transfer-request, /inventory/goods-delivery,
                 /inventory/goods-receipt, /inventory/item-journal, /simple-transfer
Response:        result null (a goods receipt delete echoes {goodsReceiptNum}); ignored
Statuses:        documented for transfer requests only: authorize / reject while New,
                 finish while Authorized or Delivering, reopen ("unfinished") while
                 Finished; delete is blocked once a goods delivery exists (ERP refusal)
Errors:          400 with errors[] -> the ERP's message in the toast; EC03100004 -> 404
Retry Policy:    writes -- exactly one attempt, never retried
Idempotency:     none documented; an unknown outcome is audited as *.write-unconfirmed
                 and the person is sent to re-read the document
Laravel:         App\Actions\Inventory\{DecideInventoryDocument, DeleteInventoryDocument,
                 CompleteGoodsTransferRequest, InventoryDocuments}; DocumentWrites::
                 transition(); InventoryDocumentSource::decideDocument() /
                 deleteDocument() / completeTransferRequest();
                 App\Http\Controllers\Inventory\InventoryDocumentWriteController;
                 App\Authorization\InventoryPermissions
Routes:          POST /{kind}/{number}/decisions, DELETE /{kind}/{number};
                 PATCH /goods-transfer-requests/{number}/finish | /reopen
Permissions:     esb.invoke + <kind>.approve (decisions) or <kind>.manage (delete,
                 finish, reopen) -- ten new permissions, Super Admin only (docs/06)
Audit:           <document>.authorized / .rejected (+ reason, item journals only) /
                 .deleted; goods-transfer-request.finished / .reopened;
                 <document>.write-unconfirmed (operation, correlation id)
Tests:           tests/Feature/Inventory/InventoryWriteTestCase.php (+ one per kind;
                 the transfer request's adds finish / reopen), mapper canEdit /
                 canDelete, pages/inventory/show.test.tsx, confirm-action.test.tsx
```

Decisions and open points:

- **One lock per document** (`<document>-write:{number}`) covers authorize, reject,
  delete, finish, reopen -- and the S3 update and S4 attachments later.
- **A reason only where the vendor takes one.** Only the item journal's reject has a body
  (`reason`, not required); for the others a reason is neither sent nor audited. The
  gateway caps it at 500 characters (vendor documents no length).
- **What is offered.** Transfer requests: buttons follow the documented statuses, and
  delete is hidden when the detail says `canDelete: false` (`canEdit` / `canDelete` are
  now read; the other documents do not send them -> null, the ERP decides). The other
  documents' allowed statuses are undocumented, so every decision is offered and the
  ERP's refusal is shown.
- **Finish / reopen need `goods-transfer-requests.manage`** (closing a request is part of
  running it, not an approval) -- `TBD` with the business, like every role mapping.

### [ESB-035] Inventory Documents, S3 (create, update)

```
Purpose:         Create and edit goods transfer requests, item journals and simple
                 transfers (TODO-modules.md §3, S3).
Family:          Core
Method / Path:   GTR  POST  /inventory/goods-transfer-request
                      PATCH /inventory/goods-transfer-request/{transferNum}   (whole request)
                 IJ   POST  /inventory/item-journal
                      PUT   /inventory/item-journal/{itemJournalNum}
                 ST   POST  /simple-transfer
                      PUT   /simple-transfer/{simpleTransferNum}
Request Body:    GTR  {transferDate, originBranchID, originLocationID?, destinationBranchID,
                       categoryTypeID: 1, additionalInfo, purchaseRequestNum: null | [..],
                       transferDetailAssets: [], transferDetails: [{productDetailID, qty,
                       requestQty?}]}
                 IJ   {itemJournalDate, branchID, locationID, additionalInfo,
                       itemJournalDetails: [{productDetailID, qty, purposeID?, hpp?}],
                       requestTemplateID?}
                 ST   {simpleTransferDate, originLocationID, destinationLocationID,
                       additionalInfo, simpleTransferDetails: [{productDetailID, qty}],
                       costCenterID?, projectID?}
Response:        IJ create {itemJournalNum}; GTR / ST create "a map of strings" (example
                 {}) -- only the detail's own number field is read; updates ignored
Errors:          400 with errors[] -> on the form fields they name (ADR-021); anything
                 unmapped on the form as a whole; EC03100004 -> 404
Retry Policy:    writes -- exactly one attempt; the read before an update is a GET
Idempotency:     none documented; a create is one per person and kind at a time, an
                 update holds the document's lock (shared with [ESB-034])
Laravel:         App\Domain\Canonical\InventoryDraft / InventoryDraftLine /
                 InventoryCarryOver; InventoryDocumentSource::createDocument() /
                 updateDocument(); App\Actions\Inventory\InventoryDocumentForms;
                 App\Http\Requests\Inventory\SaveInventoryDocumentRequest;
                 App\Http\Controllers\Inventory\InventoryDocumentFormController
                 (HandlesRecordForms); lookup "purposes" ([ESB-019], GET /purpose)
Routes:          GET /{kind}/create, POST /{kind}, GET /{kind}/{number}/edit,
                 PUT /{kind}/{number} -- kind = goods-transfer-requests | item-journals |
                 simple-transfers
Permissions:     esb.invoke + <kind>.manage
Audit:           <document>.created (number when the ERP gave one, date, lines) /
                 .updated (number, lines); <document>.write-unconfirmed
Tests:           tests/Feature/Inventory/InventoryFormTestCase.php (+ one per kind),
                 mapper carry-over / purposeId, pages/inventory/form.test.tsx
```

Decisions and open points:

- **Validated against the vendor schema first** (SaveInventoryDocumentRequest): required
  sides per kind, YYYY-MM-DD, at least one line, each product detail once (GTR, IJ),
  qty > 0 (GTR, ST; ST with at most four decimals), requested qty >= 0, item journal date
  not in the future (Asia/Jakarta; the stock period is the ERP's to check), notes up to
  1000 (the vendor gives none for ST: same cap). A negative item journal qty is allowed
  (an adjustment out). Up to 500 lines (gateway bound).
- **An update reads the document first** (outside the lock, so a failed read is never an
  "unconfirmed update") and sends back what the form does not show: the purchase
  requests a transfer request came from, a simple transfer's cost center and project, an
  item journal's request template. The notes are always sent ("" clears them).
- **Not edited here:** a transfer request the ERP marks `canEdit: false`; any document that
  moves assets (GTR `categoryTypeID` 3 or `transferDetailAssets`, ST
  `simpleTransferAssets` / asset lines) -- the form has no assets and a full replace
  would drop them. New requests are inventory only (`categoryTypeID` 1,
  `transferDetailAssets: []` -- assumed accepted when products are given).
- **Pickers:** branch, location (narrowed to the branch), products from
  `/product/browse-transfer` for the chosen branch (also used for item journals and
  simple transfers: the vendor documents no other product browse for them), purposes
  (active, `GET /purpose`; `purposeAppliedTo` values are undocumented, so not filtered).
  A simple transfer's branch only narrows the pickers and is not sent.
- **Not built:** cost center / project pickers (no lookup; the ERP says when its cost
  center setting requires one), item journal request templates, asset transfers.
- **Goods deliveries and goods receipts (`[!]`)** are created from a reference document
  (`GET /goods-delivery/create/{refNum}` -> `POST /goods-delivery/{refNum}`;
  `GET /goods-receipt/initialize?refNum&transType` -> `POST /goods-receipt/{refNum}`)
  with expiration batches, assets and (deliveries) multi-address rows. Which documents
  are valid references for a delivery, and how expirations must be filled, is not
  documented -- asked of the ESB team; not built.

### [ESB-036] Inventory Documents, S4 (attachments, simple transfer import)

```
Purpose:         Attach files to inventory documents, remove them, and hand the ERP a
                 simple transfer spreadsheet (TODO-modules.md §3, S4).
Family:          Core
Method / Path:   PATCH  {path}/{number}/attachment   multipart, field "files" (repeated)
                 DELETE {path}/{number}/attachment   (removes every attachment)
                   {path}: /inventory/goods-transfer-request, /inventory/goods-delivery,
                   /inventory/item-journal, /simple-transfer (no DELETE)
                 POST   /simple-transfer/import      multipart, field "file" (.xlsx)
Request Body:    upload: files[] (+ the vendor's optional "existing" -- never sent, below)
Response:        upload: GTR {assetImage[], refNum, transactionName}; GD {urls}; IJ / ST
                 {} -- ignored (URLs in the ERP's storage); delete / import: null
Errors:          400 with errors[]; EC03100004 -> 404
Retry Policy:    writes -- exactly one attempt
Idempotency:     none; a second upload adds (or replaces) again; importing the same
                 sheet twice may create every transfer twice
Laravel:         InventoryDocumentSource::attachFiles() / deleteAttachments();
                 App\Actions\Inventory\InventoryAttachments (DocumentWrites::transition()
                 with the files audited); AttachInventoryFilesRequest +
                 Requests\Concerns\ForwardsAttachments (shared with suppliers);
                 InventoryAttachmentController; the import is MasterDataImport::
                 SimpleTransfers on the shared import screen ([ESB-032])
Routes:          POST|DELETE /{kind}/{number}/attachments (DELETE not for simple
                 transfers); GET|POST /imports/simple-transfers
Permissions:     esb.invoke + <kind>.manage
Audit:           <document>.attachments-uploaded (each file's name, size, sha256) /
                 .attachments-deleted; <document>.write-unconfirmed (attach / detach);
                 the import: master-data.imported / .import-unconfirmed with kind
                 "simple-transfers" (the shared import's audit names)
Tests:           tests/Feature/Inventory/InventoryAttachmentTestCase.php (+ one per kind),
                 MasterDataImportTest (simple transfers), attachment-actions.test.tsx,
                 pages/inventory/show.test.tsx, index.test.tsx
```

Decisions and open points:

- **"existing" is never sent.** Deliveries, item journals and simple transfers document an
  upload that keeps the "existing attachment URLs" it is sent -- so an upload without
  them presumably drops the current attachments. The gateway does not hold those URLs
  (ADR-006; the detail's URLs are counted, not kept) and the field's format (one string)
  is undocumented, so for these kinds files are attached **only while the document has
  none**; otherwise the person removes them first (deliveries, item journals) or uses the
  ERP (simple transfers: no delete). The transfer request's upload takes no "existing"
  and is taken to add, as the supplier's (assumed). `TBD` with the ESB team.
- **File types, as the vendor names them:** transfer requests images (JPEG, PNG);
  deliveries and simple transfers PDF, BMP, GIF, JPEG, PNG; item journals name none (PDF,
  JPEG, PNG assumed). Up to 5 files at a time; 2 MB each for simple transfers
  (documented, 10 MB total), 5 MB elsewhere (assumed).
- **Not built (`[!]`):** goods receipt asset images (`POST /goods-receipt/{refNum}/asset-images`
  -- assets are not handled here), goods delivery multi-address upload (part of the
  create-from-reference flow, [ESB-035]), return browse / details (goods receipt returns
  are their own module). Attachments are counted, never opened from the gateway (same
  open point as the [ESB-032] exports).
- **The simple transfer import** reuses the master data import screen: same file checks
  (.xlsx, 2 MB, leading bytes), one per person at a time, queued by the ERP ("success"
  means the ERP took the file).

### [ESB-037] Disbursements, S1 (list, detail)

```
Purpose:         Read the ERP's disbursements (cash / credit payments out), TODO-modules.md
                 §4.1, S1.
Family:          Core
Method / Path:   GET /finance/disbursement
                 GET /finance/disbursement/{disbursementNum}
Query (list):    disbursementNum, disbursementDateFrom, disbursementDateTo (YYYY-MM-DD),
                 branchID, supplierID, paymentID (1 cash, 2 credit), statusID (1 New,
                 2 Rejected, 3 Authorized, 38 Waiting For Approval), ref (partial),
                 isOverDue (true = overdue only), page, limit. Not sent: grandTotal,
                 additionalInfo, refIs, sort (the vendor's default order).
Response:        list {count, data[{disbursementNum, disbursementDate, branchID,
                 branchName, supplierID, supplierName, paymentMethodName, ref,
                 additionalInfo, grandTotal, invoiceDueDate, overDue, hasApproval,
                 statusID, statusName}], page, limit, next, prev}
                 detail {disbursementNum, disbursementDate, branch*, supplier*, paymentID,
                 paymentName, disbursementCoaNo, currencyID, currencyName, currencySign,
                 rate, creditTerm, invoiceDueDate, ref, additionalInformation, bankName,
                 bankAccountName, bankAccountNumber, supplierBankID, costCenter*, project*,
                 grandTotal, releasePaymentCode, statusID, disbursementDetails[{branchID,
                 branchName, coaNo, coaDescription, description, amount}],
                 purchasePaymentDetails[], supplierSettlementDetails[], imageAttachments[],
                 created* / edited* / authorized*}
Errors:          EC03100004 -> 404
Retry Policy:    reads -- the GET policy
Laravel:         App\Integrations\Contracts\DisbursementSource; ESB\Clients\
                 DisbursementClient; ESB\Mappers\DisbursementMapper; Domain\Canonical\
                 Disbursement*; Http\Requests\Disbursements\ListDisbursementsRequest;
                 Http\Controllers\Disbursements\DisbursementController
Routes:          GET /disbursements, GET /disbursements/{number}
Permissions:     esb.invoke
Tests:           tests/Unit/Integrations/ESB/DisbursementMapperTest.php,
                 tests/Feature/Disbursements/DisbursementTest.php,
                 pages/disbursements/{index,show}.test.tsx
```

Decisions and open points:

- **A number must contain a digit**: `/finance/disbursement/import` shares the detail's
  segment.
- **The grand total, overdue flag and amounts are the ERP's** -- shown as sent, never
  summed or recomputed in the gateway.
- **The bank account number is masked** to its last four digits in the adapter, as for
  suppliers ([ESB-024]; `TBD` business). The full number never reaches the browser; an
  edit sends back the ERP's own value read just before ([ESB-039]).
- **Linked documents**: purchase payments and supplier settlements are listed with number,
  date, total and status; they are not opened from the gateway (their modules are not
  built).
- Status names come from the ERP; the four ids above are the vendor's documented set.

### [ESB-038] Disbursements, S2 (authorize, reject, delete)

```
Purpose:         Change a disbursement's state (TODO-modules.md §4.1, S2).
Family:          Core
Method / Path:   PATCH  /finance/disbursement/{disbursementNum}/authorize   (no body)
                 PATCH  /finance/disbursement/{disbursementNum}/reject      {reason?}
                 DELETE /finance/disbursement/{disbursementNum}
Response:        result null; ignored
Statuses:        authorize / reject: New or Waiting For Approval (authorize may leave it
                 Waiting For Approval when more approval levels remain); delete: New,
                 Rejected, Authorized or Waiting For Approval -- removes its journal,
                 payable, attachments and approvals with it
Errors:          400 with errors[] -> the ERP's message in the toast; EC03100004 -> 404
Retry Policy:    writes -- exactly one attempt, never retried
Idempotency:     none documented; an unknown outcome is audited as
                 disbursement.write-unconfirmed and the person re-reads the document
Laravel:         App\Actions\Disbursements\{Disbursements, DecideDisbursement,
                 DeleteDisbursement} on DocumentWrites; Http\Controllers\Disbursements\
                 DisbursementWriteController (HandlesDocumentWrites);
                 Http\Requests\Disbursements\DecideDisbursementRequest
Routes:          POST /disbursements/{number}/decisions, DELETE /disbursements/{number}
Permissions:     esb.invoke + disbursements.approve (decisions) / disbursements.manage
                 (delete) -- Super Admin only (docs/06)
Audit:           disbursement.authorized / .rejected (+ reason) / .deleted;
                 disbursement.write-unconfirmed (operation, correlation id)
Tests:           tests/Feature/Disbursements/DisbursementWriteTest.php,
                 pages/disbursements/show.test.tsx
```

Decisions and open points:

- **The reason is optional** (the vendor does not mark it required); capped at 500
  characters by the gateway.
- **What is offered follows the documented statuses**: decisions on New / Waiting For
  Approval, delete on the four documented statuses. The ERP's refusal is shown either way.
- **Segregation of duties** (the creator may not authorize) is not enforced by the
  gateway: every write goes out under the shared service credential (ADR-009), so the ERP
  cannot tell. `TBD` business (TODO-modules.md §9); the audit names the real person.

### [ESB-039] Disbursements, S3 (create, update)

```
Purpose:         Create and edit disbursements (TODO-modules.md §4.1, S3).
Family:          Core
Method / Path:   POST /finance/disbursement
                 PUT  /finance/disbursement/{disbursementNum}/validate   (no body; nothing
                                                                          is saved)
                 PUT  /finance/disbursement/{disbursementNum}
Request Body:    {disbursementDate, branchID, paymentID (1 | 2), currencyID, rate,
                 disbursementCoaNo (cash only), supplierID (required for credit),
                 creditTerm? / ref? (credit only), invoiceDueDate?, additionalInformation,
                 disbursementDetails: [{branchID, coaNo, description, amount}],
                 costCenterID?, projectID?, bankName?, bankAccountName?,
                 bankAccountNumber?}
Response:        create: "returns the new disbursementNum" (example {}) -- read from
                 result.disbursementNum when present; validate / update: null
Errors:          400 with errors[] -> on the form fields they name (ADR-021); anything
                 unmapped on the form as a whole; EC03100004 -> 404
Retry Policy:    writes -- exactly one attempt; the validate is a PUT and also gets one
Idempotency:     none documented; a create is one per person at a time, an update holds
                 the document's lock (shared with [ESB-038])
Laravel:         Domain\Canonical\DisbursementDraft / DisbursementDraftLine /
                 DisbursementCarryOver; DisbursementSource::createDisbursement() /
                 validateUpdate() / updateDisbursement(); Actions\Disbursements\
                 DisbursementForms; Http\Requests\Disbursements\SaveDisbursementRequest;
                 Http\Controllers\Disbursements\DisbursementFormController
                 (HandlesRecordForms)
Routes:          GET /disbursements/create, POST /disbursements,
                 GET /disbursements/{number}/edit, PUT /disbursements/{number}
Permissions:     esb.invoke + disbursements.manage
Audit:           disbursement.created (number when the ERP gave one, date, payment,
                 lines) / .updated (number, lines); disbursement.write-unconfirmed
Tests:           tests/Feature/Disbursements/DisbursementFormTest.php,
                 pages/disbursements/form.test.tsx
```

Decisions and open points:

- **Validated against the vendor schema first** (SaveDisbursementRequest): YYYY-MM-DD,
  cash needs the cash / bank account and takes no credit term or reference; credit needs
  the supplier and takes no cash / bank account; rate > 0; credit term 0-99999; reference
  up to 50, notes up to 1000, each line's description up to 100 and amount with at most
  four decimals (decimal(20,4)); at least one line, up to 500 (gateway bound). The
  locked period, the supplier being active and every "must exist" are the ERP's to check.
- **The validate comes first.** The edit page and the update call `PUT .../validate`
  (end period, supplier, cost center, payment and reconciliation). A refusal -- or no
  answer -- sends the person back to the document with the ERP's reason; nothing is
  sent. It is a PUT, so it is not retried (ADR-025 covers POST reads only); it saves
  nothing, so an unanswered one is reported as "could not check", not as unconfirmed.
- **An update reads the document first** (outside the lock) and sends back what the form
  does not show: cost center, project, and the bank name / account name / account number
  (the full number, from that read -- never from the browser).
- **Not in the form (`TBD`)**: cost center and project pickers (no lookup; the ERP says
  when its cost center setting requires one), bank details on a new disbursement, the
  supplier's bank account (`supplierBankID` is read-only in the vendor body).
- **Pickers**: branch, supplier, currency, cash / bank account (narrowed to the branch),
  COA account per line ([ESB-019]).

### [ESB-040] Disbursements, S4 (attachments, import, import form)

```
Purpose:         Attach files to a disbursement, hand the ERP a disbursement spreadsheet,
                 and fill a form's lines from the ERP's Excel form (TODO-modules.md §4.1,
                 S4).
Family:          Core
Method / Path:   PATCH /finance/disbursement/{disbursementNum}/attachment
                       multipart: files[] (pdf, bmp, gif, jpg, jpeg, png; 2 MB each,
                       10 MB total), existing[] (never sent)
                 POST  /finance/disbursement/import        multipart "file" (.xlsx/.xls,
                       2 MB); queued
                 POST  /finance/disbursement/import/form   multipart "file" (.xlsx/.xls,
                       2 MB); "nothing is saved"
Response:        attachment: {urls} -- ignored; import: null;
                 import form: [{branchID, branchName, coaNo, coaDescription, description,
                 amount}]
Errors:          400 with errors[]; EC03100004 -> 404
Retry Policy:    one attempt for all three (uploads); the import form saves nothing, so
                 it is neither locked nor audited
Laravel:         DisbursementSource::attachFiles() / readFormLines();
                 Actions\Disbursements\DisbursementAttachments (DocumentWrites::
                 transition()); Http\Requests\Disbursements\{AttachDisbursementFilesRequest,
                 ReadDisbursementFormRequest}; Http\Controllers\Disbursements\
                 {DisbursementAttachmentController, DisbursementFormLinesController};
                 the import is MasterDataImport::Disbursements on the shared import
                 screen ([ESB-032])
Routes:          POST /disbursements/{number}/attachments; POST /disbursements/form-lines;
                 GET|POST /imports/disbursements
Permissions:     esb.invoke + disbursements.manage
Audit:           disbursement.attachments-uploaded (each file's name, size, sha256);
                 disbursement.write-unconfirmed (attach); the import:
                 master-data.imported / .import-unconfirmed with kind "disbursements"
Tests:           tests/Feature/Disbursements/{DisbursementAttachmentTest,
                 DisbursementFormLinesTest}.php, MasterDataImportTest (disbursements),
                 pages/disbursements/form.test.tsx
```

Decisions and open points:

- **Attach only while there are none**, as for the inventory documents ([ESB-036]): the
  upload keeps the "existing" references it is sent, whose format is undocumented. The
  vendor has no delete for disbursement attachments, so the person adds further files in
  the ERP.
- **The import form only fills the form.** Its rows become lines on the create / edit
  form, where the person checks them before saving; the gateway sends nothing else
  until the form is submitted. Its rows replace the form's lines.
- **Not built (`[!]`)**: the ERP export (same open point as [ESB-032]) and opening
  attachments from the gateway.

### [ESB-041] General Journal, S1 (list, lines)

```
Purpose:         Read the ERP's general journal: the journal entries every module posts
                 (TODO-modules.md §5.3, S1).
Family:          Core
Method / Path:   GET /accounting/general-journal/search
                 GET /accounting/general-journal/{journalHeadID}/detail
Query:           search: dateFrom, dateTo (YYYY-MM-DD), journalSearch (free text over
                 COA, account, branch, transaction type, reference, notes), page, limit
                 detail: page, limit
                 Not sent: branchID[], account[] / accounts[], costCenterID[],
                 projectID[] (array parameters, format undocumented -- [ESB-019]),
                 filterByCostCenter, typeMode (fullset | partial: undocumented meaning),
                 sort (the vendor's default, newest first)
Response:        search {count, data[{journalHeadID, journalDate, transactionType, refNum,
                 notes, typeHasAccessView}], page, limit, next, prev}
                 detail {count, data[{journalDetailID, journalHeadID, branchID,
                 branchName, coaNo, coaDesc, costCenterID, costCenterName, projectID,
                 projectName, currencyID, currencyName, rate, drAmount, crAmount, notes}],
                 ...paging}
Errors:          EC03100004 -> 404
Retry Policy:    reads -- the GET policy
Laravel:         Integrations\Contracts\GeneralJournalSource; ESB\Clients\
                 GeneralJournalClient; ESB\Mappers\JournalMapper; Domain\Canonical\
                 {GeneralJournalEntry, GeneralJournalPage, GeneralJournalQuery,
                 JournalLine, JournalLinePage}; Http\Controllers\Accounting\
                 GeneralJournalController
Routes:          GET /general-journals, GET /general-journals/{journalHeadId}
Permissions:     esb.invoke
Tests:           tests/Unit/Integrations/ESB/JournalMapperTest.php,
                 tests/Feature/Accounting/GeneralJournalTest.php,
                 pages/general-journals/*.test.tsx
```

Decisions and open points:

- **The detail has lines only.** The vendor's detail answers the journal's lines, not its
  head (date, transaction type, reference). The page shows the lines; the list carries the
  head. A journal head id is a positive integer.
- **Debit and credit are shown as sent**, never summed or balanced by the gateway (the ERP
  is the authority, TODO-modules.md §0.3).
- `typeHasAccessView` is shown as "not viewable in the ERP" when false; the gateway does
  not hide the row (`TBD` what the ERP means by it beyond its own UI).
- **Not built (`[!]`)**: the export (same open point as [ESB-032]); filters by branch,
  account, cost center and project (array parameters).

### [ESB-042] General Ledger, S1 (entries, summary)

```
Purpose:         The ledger of one account over a period, and the period's summary per
                 branch and account (TODO-modules.md §5.2, S1).
Family:          Core
Method / Path:   POST /accounting/general-ledger            (a read, ADR-025)
                 POST /accounting/general-ledger/summary    (a read, ADR-025)
Request Body:    entries {coaNo*, dateFrom, dateTo, branchID?, debitCreditFilter?
                 (1 debit, 2 credit), reference?, notes?, transactionType?}
                 summary {startPeriod*, endPeriod*, accountNo?, branchID?, page, limit}
                 Not sent: useCostCenter / costCenterIDs, projectIDs, alreadyReconcile,
                 branchCode
Response:        entries: a list, not paged [{journalDate, branchID, branchName,
                 coaNumber, coaDescription, transactionType, referenceNumber, notes,
                 debitAmount, creditAmount, costCenterName, projectName,
                 generalLedgerInfo, createdDate}]
                 summary {count, data[{journalDate, branchID, branchCode, branchName,
                 accountNo, accountDescription, debitAmount, creditAmount}], ...paging}
Errors:          400 with errors[] -> the ERP's reason on the page; no 404
Retry Policy:    EsbClient::postRead() -- the GET policy (ADR-025: documented as "List…"
                 with no side effect)
Laravel:         Integrations\Contracts\GeneralLedgerSource; ESB\Clients\
                 GeneralLedgerClient; ESB\Mappers\JournalMapper; Domain\Canonical\
                 {LedgerEntry, LedgerEntryPage, LedgerQuery, LedgerSummaryRow,
                 LedgerSummaryPage, LedgerSummaryQuery}; Http\Controllers\Accounting\
                 GeneralLedgerController
Routes:          GET /general-ledger, GET /general-ledger/summary
Permissions:     esb.invoke
Tests:           tests/Feature/Accounting/GeneralLedgerTest.php,
                 pages/general-ledger/*.test.tsx
```

Decisions and open points:

- **The account and the period are required** (TODO-modules.md §5.2) and no call is made
  without them: the page asks first. The period is at most 366 days (gateway bound: the
  entries come back as one unpaged list), and the gateway pages that list itself.
- **No balance is computed.** The vendor gives no opening balance; a running balance from
  the period's entries alone would be wrong, so none is shown. Debit and credit are the
  ERP's.
- **CSV export from the gateway: not built** (TODO-modules.md §5.2 asks to decide). It
  would be the gateway's own rendering of ERP data -- `TBD` with the business, together
  with the ERP's queued report export (`POST /report/accounting/general-ledger/export`).
- Filters by cost center / project and the reconciliation flag are not offered (pickers
  and meaning `TBD`).

### [ESB-043] Memorial Journal, S1 (list, detail)

```
Purpose:         Read the ERP's memorial journals -- manual journal entries
                 (TODO-modules.md §5.4, S1).
Family:          Core
Method / Path:   GET /accounting/memorial-journal
                 GET /accounting/memorial-journal/{memorialJournalNum}
Query (list):    memorialJournalNum, dateFrom, dateTo (YYYY-MM-DD), statusID (1 New,
                 2 Rejected, 3 Authorized, 38 Waiting For Approval), page, limit
Response:        list {count, data[{memorialJournalNum, memorialJournalDate,
                 additionalInfo, approval, createdBy, statusID, statusName}], ...paging}
                 detail {memorialJournalNum, memorialJournalDate, additionalInfo, approval,
                 statusID, statusName, created* / edited* / authorized* (+ creator /
                 editor / authorizer {username, fullName}), image[{ID, imageUrl, ...}],
                 memorialJournalDetails[{ID, branchID, branchName, coaNo, description,
                 costCenterID, costCenterName, projectID, projectName, currencyID,
                 currencyName, rate, drAmount, crAmount, notes}]}
Errors:          EC03100004 -> 404
Retry Policy:    reads -- the GET policy
Laravel:         Integrations\Contracts\MemorialJournalSource; ESB\Clients\
                 MemorialJournalClient; ESB\Mappers\JournalMapper; Domain\Canonical\
                 {MemorialJournal, MemorialJournalSummary, MemorialJournalPage,
                 MemorialJournalQuery, JournalLine}; Http\Controllers\Accounting\
                 MemorialJournalController
Routes:          GET /memorial-journals, GET /memorial-journals/{number}
Permissions:     esb.invoke
Tests:           tests/Feature/Accounting/MemorialJournalTest.php,
                 pages/memorial-journals/*.test.tsx
```

Decisions and open points:

- **A number must contain a digit**: `/access`, `/export`, `/form-upload` and `/upload`
  share the detail's segment.
- **`GET /accounting/memorial-journal/access` is not used (`[!]`).** It "returns true when
  the current user's branches can access every memorial journal number passed in the
  query", but the query parameter's name is undocumented -- and the "current user" is the
  gateway's shared credential (ADR-009), not the person. Actions in S2 are offered by the
  gateway's own permissions and the documented statuses. Asked of the ESB team.
- Lines share the canonical `JournalLine` with the general journal ([ESB-041]). Debit and
  credit are shown as sent; the gateway does not check that they balance.
- Attachment images are counted, not opened (same open point as [ESB-032]).

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
| `POST` the vendor documents as a read, sent with `EsbClient::postRead()` (ADR-025) | same as `GET` |
| `POST` / `PUT` / `PATCH` / `DELETE` | do not retry automatically on timeout or 5xx |
| Authentication failure (`EC03100001`) | refresh token once, then retry once; then fail |
| Validation / authorization / not found / business rejection | never retry |

A `POST` gets the read policy only when its adapter calls `postRead()` and its
`[ESB-0xx]` entry says "read via POST (ADR-025)". The policy is never inferred from the
path: `POST /report/.../export` queues a job and stays a write.

Exception: Push Sales Data V2 (`[ESB-011]`) is documented as overwriting by `salesNum`,
so its queue job repeats an unknown outcome. EsbClient itself still makes one attempt.

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
