# Project State

> This file is the hand-off state for humans and AI coding agents. Keep it current.

## Last Updated

2026-10-19

## Current Phase

Phase 8 complete. Phase 9 (multi-provider generalization) is **partially started**: the
provider contracts and canonical DTOs were extracted from the working ESB implementation
on 2026-09-10 under ADR-019, which amends ADR-017's gate for that step only. Everything
in Phase 9 that needs a second provider's actual contract remains unbuilt and gated —
no second provider is named. Remaining work needs real infrastructure or business
decisions — see `TODO.md`.

## Current Objective

Prepare the foundation and documentation for an ERP Gateway & Middleware application
that functions as a multi-provider integration hub (ADR-016, ADR-017). The ERP ESB is
the first connected provider and the current implementation focus; the architecture is
recorded so a second provider, when contracted, is integrated as an adapter against the
canonical model rather than bolted onto ESB-specific code.

## Architecture Status

- [x] Laravel selected as the main application
- [x] React selected for frontend
- [x] React will live inside Laravel
- [x] Inertia.js selected
- [x] TypeScript selected
- [x] Vite selected
- [x] Tailwind CSS selected
- [x] shadcn/ui selected
- [x] PostgreSQL selected
- [x] Redis selected
- [x] ESB API contract imported/documented
- [x] Multi-provider integration concept recorded (ADR-016, ADR-017) — ESB is the
      reference provider; generic infrastructure (`integration_providers`,
      `sync_transactions`, adapter contracts, canonical DTOs) intentionally not yet built
      pending a real second provider
- [ ] ESB credentials, environment, and auth mechanism confirmed with ESB team
- [ ] Authentication flow finalized against ESB
- [ ] Initial database schema finalized
- [ ] Deployment topology finalized
- [x] Laravel 13 + Inertia 3 + React 19 + TypeScript scaffolded
- [x] Tailwind 4 + shadcn/ui in place
- [x] Formatting, linting, static analysis, and CI configured
- [x] Roles, permissions, policies, and an immutable audit log implemented
- [x] ESB integration foundation implemented (config, envelope, error classification,
      token manager, HTTP client, retry, redaction, integration logs)
- [ ] Role set confirmed with the business

## Completed

Adopted mandatory TDD workflow and base-class conventions (ADR-015).
ADR-018 (2026-09-10) supersedes ADR-013: ESB connections are editable records with
credentials encrypted at rest, write-only after storage, and every change audited. Built
in Phase 8 the same day.
Corrected the ADR cross-references in the 2026-09-10 documentation update, which cited
ADR-015/ADR-016 for the multi-provider concept; the correct numbers are ADR-016 and
ADR-017.
Synced docs/04, docs/02, docs/08, docs/11 with ADR-013 and actual resources/js layout.

- Architecture direction agreed
- Agent-agnostic documentation structure defined
- Initial architectural decisions recorded
- ESB API contract imported from the vendor documentation (2026-09-09):
  - ESB Core API v2.0.0 — 363 operations across 41 groups
  - ESB OMS API v2.0.0 — 38 operations across 10 groups
  - Sources: https://developers.esb.co.id/esb-core/ and https://developers.esb.co.id/esb-oms/
- ESB authentication flow, token lifetimes, response envelope, pagination, and the
  29-code error catalog documented in `docs/05-api-integration.md`
- Generated operation catalogs under `docs/esb/`, reproducible via
  `docs/esb/generate-catalog.py`
- ADR-009 (shared ESB token in Redis behind a lock) and ADR-010 (base URLs as named
  families) recorded
- Phase 1 scaffold (2026-09-09): Laravel 13.17, Inertia 3, React 19, TypeScript strict,
  Tailwind 4, shadcn/ui, Vite 8, from `laravel/react-starter-kit` `main`
- Auth features: 2FA kept; registration, email verification, password confirmation, and
  passkeys removed at scaffold time
- Test framework is PHPUnit 12 (not Pest) — 26 tests passing
- Toolchain green: `vp check`, `tsc --noEmit`, Pint, PHPStan level 7, Vite build
- CI workflow runs the suite against real PostgreSQL 16 and Redis 7 service containers
- Phase 2 (2026-09-09), built test-first: roles and permissions as typed PHP enums synced
  to the database by an idempotent seeder; `UserPolicy`; permission-gated routes and
  sidebar; `auth.role` / `auth.permissions` shared to Inertia with a `usePermissions()`
  hook; users administration page; immutable `audit_logs` (ADR-012);
  `php artisan user:assign-role` to bootstrap the first Super Admin
- ADR-011 (spatie/laravel-permission) and ADR-012 (immutable audit log) recorded
- Phase 3 (2026-09-09), built test-first: `config/esb.php` with base URLs as named
  families; `EsbEnvelope` with pagination and Asia/Jakarta timestamp conversion;
  `ErrorCode` mapping all 29 documented codes; a ten-class exception hierarchy carrying
  `EsbContext`; `TokenManager` implementing ADR-009; `EsbClient` with family-aware
  routing, timeouts, retry policy, correlation IDs, and redacted logging;
  `integration_logs`
- Phase 4 (2026-09-09), built test-first: the first ESB vertical slice —
  `GET /product/list` via `ProductClient`, `ProductMapper`, `ProductListQuery`,
  `ListProductsRequest`, a thin controller, and an Inertia page with search and paging;
  `EsbExceptionRenderer` maps normalized ESB failures onto HTTP statuses
- Documented as `[ESB-001]` in `docs/05-api-integration.md`
- Phase 5 (2026-09-09/10), built test-first: integration log viewer; activity dashboard;
  ESB connections screen with connectivity probing; endpoint catalog imported from
  `docs/esb/` into `integration_endpoints` via `php artisan esb:import-catalog`;
  `GET /health` for application, database, cache, and queue
- ADR-013 (ESB connections are configuration, not records) and ADR-014 (probes use a
  documented read, or report that none exists) recorded
- Phase 6 (2026-09-10), built test-first: per-user rate limiting on ESB-reaching routes;
  secure headers and production HTTPS enforcement;
  `php artisan app:check-production-readiness` automating the security checklist and
  wired into `composer ci:check`; `php artisan integration-logs:prune` with a
  disabled-safe retention window, scheduled daily; `docs/13-deployment.md` written
  concretely
- 272 tests passing (949 assertions, verified 2026-09-10)
- Phase 8 (2026-09-10), built test-first: editable ESB connections per ADR-018 —
  `esb_connections` table, `EsbConnection` model with credentials encrypted at rest and
  hidden from every serialization, `EsbConnectionResolver` as the single place applying
  precedence (active row, then `ESB_BASE_URL_*` override, then `config/esb.php`),
  `SaveEsbConnection` and `RevertEsbConnection` actions writing immutable audit rows and
  discarding the cached ESB token when a credential changes, `UpdateEsbConnectionRequest`
  (HTTPS-only base URL), edit/revert routes gated by `esb-connections.manage`, and a
  connections screen with an edit dialog, masked credential fields and a Source column.
  `app:check-production-readiness` now resolves the effective base URL per family, so
  neither a row nor an override can point a non-production install at the production ESB.
  `TokenManager` and `EsbClient` resolve base URLs and credentials through the resolver.
- 302 tests passing (1027 assertions, verified 2026-09-10)

## In Progress

- Nothing in code. The build is feature-complete against `TODO.md` Phases 1-8 and waiting
  on external inputs: ESB credentials, business confirmation of the role set, and
  infrastructure decisions. Phase 9 is deliberately not started (ADR-017).
- Functional requirements
- Selecting the ESB operations that are actually in scope

## Blocked

One local verification step remains:

- ~~`php artisan migrate` against PostgreSQL~~ — **done 2026-09-10.** All migrations
  applied and `db:seed` verified against the local PostgreSQL 16 on port **5433**
  (17 tables, 4 roles, 14 permissions, 401 catalog endpoints). Needs
  `CACHE_STORE=array` locally, because spatie's permission migration touches the cache
  and Redis is unavailable here.
- Queued job round-trip against Redis — still blocked. No Redis server and no Docker on
  this machine.

Neither blocked Phase 2, and neither blocks most of Phase 3: the test suite runs on
SQLite in-memory locally, and CI exercises real PostgreSQL and Redis. Local Redis does
become worth having for Phase 3, where the ADR-009 token lock is the thing under test.

Also open:

- The four roles in `docs/06-authentication-authorization.md` are implemented but **not
  yet confirmed by the business**.
- The ESB layer is fully tested against faked HTTP, but has never spoken to a real ESB.
  No credentials exist yet, and the entitled environment is unconfirmed.
- The ADR-009 lock is exercised in one process against the array cache store. The
  distributed behaviour it exists for is reasoned about, not yet exercised against Redis
  — see the Phase 3 follow-ups in `TODO.md`.

## Next Tasks

The detailed, ordered kickoff checklist is in `TODO.md`. Summary:

1. Confirm with the ESB team: entitled environment/base URL family, credentials, whether
   a static API key is available instead of the login flow, rate limits, timeout
   budgets, and whether a correlation ID header is accepted.
2. Define application roles and permissions.
3. Define initial database schema (`esb_connections` must hold multiple base URL
   families per ADR-010).
4. Scaffold Laravel + Inertia + React + TypeScript.
5. Establish coding, testing, and CI checks.
6. Implement authentication foundation.
7. Implement the ESB token manager (shared Redis cache + lock, per ADR-009).
8. Implement ESB connection/configuration foundation and the envelope/error normalizer.
9. Implement request/response logging with redaction (login and refresh response bodies
   contain tokens and must be redacted, not just headers).
10. Implement the first read-only ESB adapter end to end as a reference pattern —
    `GET /product/list` is the vendor's own tutorial endpoint and a good candidate.
11. Build initial dashboard.
12. When a second external system is contracted: generalize the existing ESB client into
  the `OutboundAdapter`/`InboundAdapter` contracts described in `docs/02-architecture.md`
  before writing the second provider's adapter, so both fit the same shape from day one.

## Update 2026-09-10 — Phase 8: editable ESB connections (ADR-018)

**Selesai**

- `database/migrations/2026_09_10_090000_create_esb_connections_table.php` — unique on
  `(family, environment)`.
- `app/Models/EsbConnection.php` — `credentials` cast `encrypted:array` and listed in
  `$hidden`, plus `hasCredential()` as the only credential-shaped thing ever shown.
- `app/Integrations/ESB/EsbConnectionResolver.php` — the single place applying
  precedence, plus `source()` and `auth()`.
- `app/Actions/EsbConnections/SaveEsbConnection.php` and `RevertEsbConnection.php` —
  audited in the same transaction; `TokenManager::forget()` on a credential change.
- `app/Http/Requests/UpdateEsbConnectionRequest.php` — HTTPS-only base URL; a blank
  credential field means "keep what is stored", never "clear it".
- `app/Http/Controllers/EsbConnections/EsbConnectionController.php` — `update` and
  `destroy`; unknown family is a 404, not a validation error.
- `app/Console/Commands/CheckProductionReadinessCommand.php` — new
  `checkEffectiveConnections()`.
- `app/Integrations/ESB/Clients/EsbClient.php`, `app/Integrations/ESB/Auth/TokenManager.php`,
  `app/Services/EsbConnectionInspector.php` — moved from `BaseUrlResolver` to
  `EsbConnectionResolver`. `BaseUrlResolver` is unchanged and still the configuration-only
  layer underneath.
- `resources/js/pages/esb-connections/index.tsx` — edit dialog, Source and Credential
  columns, revert action.
- `AuditAction::EsbConnectionUpdated` / `EsbConnectionReverted`.
- Docs: ADR-018 in `DECISIONS.md`, `docs/04`, `docs/07`, `docs/10` (the "explicitly
  required and protected" exception), `docs/13` (`APP_KEY` rotation and backups).

**Tersisa**

- `APP_KEY` rotation rehearsal against real infrastructure — the path in `docs/13` has
  never been walked.
- Rotation/retention policy for stored ESB credentials (left open by ADR-018).
- Everything already listed under Phase 6's "not claimable from this machine".

**Known issues**

- `TokenManagerTest` now needs `RefreshDatabase`, because credentials resolve through the
  effective connection. That is correct rather than incidental, but it means the ESB auth
  tests are no longer pure unit tests.
- `EsbConnectionResolver` memoizes per instance, not per request, so one ESB call may
  issue one extra `esb_connections` query. Left as is: a request-scoped singleton would
  cache a stale row across a save in the same request.

**Assumptions / TBD**

- A database row outranks `ESB_BASE_URL_*`. ADR-018 says an active row wins over
  configuration, and an override is configuration. The trap this creates — setting an
  override and seeing no effect — is answered by the Source column on the connections
  screen rather than by changing the order.
- The auth family is still whole-installation (`config('esb.auth.family')`, default
  `core`). Per-family credentials are not modelled; no vendor documentation asks for
  them.

**Checks dijalankan**

- `php artisan test` — **pass**, 302 tests, 1027 assertions.
- `npx tsc --noEmit` — pass. `npx vp check` — pass. `npm run build` — pass.
- `./vendor/bin/pint` — pass. `phpstan analyse` (level 7) — pass, needs
  `php -d memory_limit=1G` on this machine.
- `php artisan migrate` against local PostgreSQL 16 (port 5433, `CACHE_STORE=array`) —
  pass.
- `php artisan app:check-production-readiness` — pass, one warning (no local ESB token).
- Blocked: still no local Redis and no Docker, so the ADR-009 lock and the queued-job
  round-trip remain untested outside CI. Unchanged by this phase.

## Update 2026-09-10 — Seeded accounts for local sign-in

A fresh installation could not be signed in to at all. Three deliberate decisions
combined into a gap nobody owned: Fortify's registration feature was removed at scaffold
time, `DatabaseSeeder` creates no users on purpose, and `user:assign-role` only promotes
an account that already exists. The local database held zero users, so every login
attempt failed correctly and for a reason that was invisible from the login screen.

**Selesai**

- `database/seeders/DevUserSeeder.php` — one account per role
  (`superadmin@`, `itadmin@`, `operator@`, `viewer@` `maharasa.test`), password
  `password`, idempotent on the email address. Deliberately **not** called from
  `DatabaseSeeder`, so `php artisan db:seed` still creates zero accounts and
  `DatabaseSeederTest::it_creates_no_users` keeps passing. Throws in the `production`
  environment. Roles are assigned through `AssignUserRole`, so the one-role-per-user rule
  and the audit trail apply exactly as they do in the UI.
- `tests/Feature/DevUserSeederTest.php` — five tests, written before the seeder.
- `docs/06-authentication-authorization.md` — new "Seeded accounts for local and CI work"
  section, and a note that `user:assign-role` promotes rather than creates.
- Local PostgreSQL seeded and verified: four users, one role each.

**Tersisa**

- No supported way to create the **first production account**. The documented path is
  `php artisan tinker`; a `user:create` command is now a TODO under Phase 2.

**Known issues**

- Redis is still absent on this machine, and `.env` sets `CACHE_STORE=redis`. This is a
  second, independent cause of apparent login failure: authentication itself succeeds,
  but `/dashboard` is gated on `dashboard.view`, and spatie's permission lookup hits the
  cache, so the request fails with
  `Predis\Connection\Resource\Exception\StreamInitException` on
  `tcp://127.0.0.1:6379`. Any artisan command touching permissions needs
  `CACHE_STORE=array` locally, as already noted under Blocked. `.env` was deliberately
  left unchanged — running Redis or switching the local cache store is an environment
  decision, not a code one.

**Assumptions / TBD**

- The seeded email domain (`maharasa.test`) and the shared password (`password`,
  `DevUserSeeder::PASSWORD`) are development conventions, not requirements. The
  production guard is what makes a known password acceptable, so it must not be relaxed
  to make the seeder reusable elsewhere.

**Checks dijalankan**

- `php artisan test` — **pass**, 307 tests, 1040 assertions (302 before this change).
- `./vendor/bin/pint` — pass. `phpstan analyse` (level 7) — pass, needs
  `--memory-limit=1G` on this machine; the default 128M crashes the parallel worker.
- `php artisan migrate` — nothing to migrate. `php artisan db:seed --class=DevUserSeeder`
  against local PostgreSQL 16 (port 5433, `CACHE_STORE=array`) — pass.
- Not run: `npx tsc --noEmit` and `npm run build` — this change touches no frontend code.
- Blocked: unchanged — no local Redis and no Docker, so the ADR-009 lock and the
  queued-job round-trip remain untested outside CI.

## Update 2026-09-10 — Phase 9 started: provider contracts extracted (ADR-019)

Phase 9 was gated by ADR-017 until a second provider was contracted. No second provider
is named. The project owner directed that Phase 9 begin and reaffirmed it after the gate
was raised, so **ADR-019** records the deviation and amends the gate for the extraction
step only. ADR-017's status line now points at it.

Only the part grounded in real code was built. A second provider's auth model, envelope,
error catalogue, and pagination style are unknown, and inventing them would be
fabricating vendor documentation.

**Selesai**

- `app/Domain/Canonical/` — `Product` (promoted from `ProductSummary`, plus a `provider`
  key so a record carries its origin), `Pagination` (page arithmetic promoted out of
  `EsbPagination`), `ProductPage`, `ProductQuery`.
- `app/Integrations/Contracts/` — `OutboundAdapter` (`provider()` only) and
  `ProductSource` (`listProducts(ProductQuery): ProductPage`). Capability is declared by
  narrow contracts, so no provider stubs out an operation it lacks.
- `ProductClient implements ProductSource`; it now owns the translation from canonical
  filters to the vendor's parameter spelling (`name` → `productName`, `activeOnly` →
  `flagActive` as 1/0). `EsbPagination::toCanonical()` added.
- `ProductController` type-hints `ProductSource`, not `ProductClient`;
  `AppServiceProvider::register()` binds the two. Which provider answers is now one line.
- Removed: `App\Integrations\ESB\DTOs\ProductSummary`, `ProductListResult`,
  `ProductListQuery`. `ListProductsRequest` builds a canonical `ProductQuery` and gained
  `validatedFilters()` so the page keeps its existing query-string spelling.
- `resources/js/pages/products/index.tsx` — `Product` type gains `provider`.
- Tests written first: `tests/Unit/Domain/Canonical/PaginationTest.php` and
  `tests/Feature/Integrations/Contracts/ProductSourceTest.php` (11 tests).

**Tersisa** — still gated, and correctly so

- The second provider's **name and contract**. Everything below needs it.
- `InboundAdapter` — not written. No inbound flow exists to extract it from.
- `integration_providers` and `sync_transactions` tables, machine-to-machine inbound
  authentication, `routes/api.php`, `SyncOrchestrator`, per-provider queues, per-provider
  health and rate limiting, replay/dead-letter path.

**Known issues**

- `ProductSource::listProducts()` still declares `EsbException`. A provider-neutral
  exception hierarchy needs a second error model to design against; `EsbExceptionRenderer`
  maps the current hierarchy onto HTTP statuses today.
- `EsbFamily` still appears in every `EsbClient` public method. That is ESB-internal
  routing (ADR-010) and stays behind `ProductClient`, but it means `EsbClient` itself is
  not yet reusable transport for another provider. Deliberately not generalized — the
  retry, timeout, and logging machinery is the reusable part, and splitting it from
  family routing without a second caller would be speculation.

**Assumptions / TBD**

- The canonical field set is the ESB's, because the ESB is the only example.
  `bomId`/`bomName` are the likeliest fields to prove ERP-specific rather than canonical.
  Marked TBD in `app/Domain/Canonical/Product.php`.
- `ProductQuery::SORTABLE` holds the ESB's own column names, not a canonical sort
  vocabulary. A second provider will not accept them. Marked TBD in
  `app/Domain/Canonical/ProductQuery.php`.

**Checks dijalankan**

- `php artisan test` — **pass**, 318 tests, 1063 assertions (307 before this change).
- `npx tsc --noEmit` — pass. `npm run build` — pass.
- `./vendor/bin/pint` — pass. `phpstan analyse` (level 7) — pass, with
  `--memory-limit=1G`.
- Blocked: unchanged — no local Redis and no Docker, so the ADR-009 lock and the
  queued-job round-trip remain untested outside CI.

## Update 2026-09-25 — Purchase Request module, slice 1 (read-only)

The project owner started the Purchase Request module and chose a read-only first slice
(list + detail). Writes follow in later slices — see `TODO.md` § Module — Purchase
Request.

**Selesai**

- `app/Domain/Canonical/` — `PurchaseRequestSummary`, `PurchaseRequest`,
  `PurchaseRequestLine`, `PurchaseRequestPage`, `PurchaseRequestQuery`.
- `app/Integrations/Contracts/PurchaseRequestSource.php` — `listPurchaseRequests()`,
  `findPurchaseRequest()`; bound to `PurchaseRequestClient` in `AppServiceProvider`.
- `app/Integrations/ESB/Clients/PurchaseRequestClient.php` (Core family) and
  `Mappers/PurchaseRequestMapper.php`.
- `app/Http/Requests/BaseFormRequest.php` (ADR-015: second consumer arrived);
  `ListProductsRequest` now extends it. `ListPurchaseRequestsRequest` validates dates as
  `Y-m-d`, ranges, `branchIds.*` as positive ints, and limit ≤ 100.
- `PurchaseRequestController` (`index`, `show`), routes `purchase-requests.index` /
  `purchase-requests.show` under `throttle:esb` + `esb.invoke`; the document number is
  constrained by a route regex.
- Inertia pages `purchase-requests/index.tsx` (search, request-date range, paging) and
  `purchase-requests/show.tsx` (header, lines, linked documents); sidebar entry.
- Documented as `[ESB-002]` / `[ESB-003]` in `docs/05-api-integration.md`.
- Tests written first: `tests/Unit/Integrations/ESB/PurchaseRequestMapperTest.php`,
  `tests/Feature/PurchaseRequests/PurchaseRequestListTest.php`,
  `tests/Feature/PurchaseRequests/PurchaseRequestDetailTest.php` (34 tests).

**Finding — vendor documentation moved**

- `https://developers.esb.co.id/esb-core/api_data.json` now 404s. The vendor site serves
  an OpenAPI 3.0 document at `.../esb-core/openapi.json` with 963 paths (vs 363
  operations imported on 2026-09-09). `docs/esb/generate-catalog.py` can no longer run,
  and the catalog is stale. Recorded in `docs/05-api-integration.md` and `TODO.md`; the
  catalog files were not hand-edited.

**Assumptions / TBD**

- `statusID` meanings, accepted `sort` columns, and the response date format are not
  documented (vendor examples are placeholders). No status filter, no sort, dates shown
  as raw strings.
- `0` reference ids (`projectID`, `costCenterID`, ...) are read as "none" — inferred
  from the placeholder example, not stated by the vendor.
- `print` and `imageAttachments` are deliberately not carried (vendor-hosted URLs).

**Checks dijalankan**

- `php artisan test` — **pass**, 352 tests.
- `pint --test`, `phpstan analyse` — pass. `tsc --noEmit`, `vp check`, `npm run build` —
  pass.
- Not run against a real ESB — faked HTTP only, like every other slice.

## Update 2026-09-25 — Purchase Request slice 2: authorize / reject

The gateway's first ESB write. Documented as `[ESB-004]` in `docs/05-api-integration.md`.

**Selesai**

- `Permission::ApprovePurchaseRequests` (`purchase-requests.approve`) is mirrored in
  `resources/js/types/permissions.ts`. No role is granted it, so only Super Admin can
  approve, through `Gate::before`.
- Added `AuditAction::PurchaseRequestAuthorized`, `PurchaseRequestRejected`, and
  `PurchaseRequestDecisionUnconfirmed`.
- Added `EsbException::mayHaveBeenApplied()`. It returns true for timeout, connection,
  upstream, malformed, and unknown. `EsbConnectionException`'s docblock no longer claims
  that nothing was delivered.
- Added `App\Domain\Canonical\PurchaseRequestDecision` and
  `PurchaseRequestSource::decidePurchaseRequest()`, implemented by
  `PurchaseRequestClient` as a single PATCH with no body.
- Added `App\Actions\PurchaseRequests\DecidePurchaseRequest`: a per-document cache lock,
  an audit row on success, and an audit row with the correlation id on an unknown
  outcome. `DecisionAlreadyInFlight` is thrown when the lock is held.
- Added `DecidePurchaseRequestRequest` and `PurchaseRequestDecisionController`. The
  route is `POST purchase-requests/{number}/decisions`. Every outcome redirects to the
  detail page with an Inertia flash toast (success, warning, or error). A not-found
  renders 404.
- `purchase-requests/show.tsx` has Authorize/Reject buttons with a confirm dialog. The
  buttons appear only for users holding both permissions.
- Tests written first: `PurchaseRequestDecisionTest` (13 tests) and `WriteOutcomeTest`
  (10 cases).

**Assumptions / TBD**

- Which role approves is for the business to decide.
- Which statuses accept a decision is not documented. The buttons are always shown.
- Reject takes no reason. The vendor documents no body.

**Checks dijalankan**

- `php artisan test`: pass, 375 tests, 1286 assertions.
- `pint --test`: pass. `phpstan`: pass. `tsc --noEmit`: pass. `vp check`: pass.
  `npm run build`: pass.

## Update 2026-09-29 — ESB connections: active flag, visible Test result (ADR-020)

- **ADR-020** amends ADR-018: an inactive `esb_connections` row now **disables** its
  family (calls refused, no fallback to override/config). The edit form exposes an Active
  checkbox and keeps the row's auth mechanism; the table shows Active/Disabled.
- Fixed: the Test button showed nothing. The probe result was flashed to the session but
  never shared with Inertia. `HandleInertiaRequests` now shares `flash.status` and
  `flash.probe`.
- Fixed: `EsbClient` wrapped `EsbConfigurationException` as an upstream error and retried
  it. It now rethrows it unchanged.
- Local staging finding: `ESB_STATIC_TOKEN` is rejected (`EC03100001`) on
  `stg7.esb.co.id/core-stg` and `core-int`. The login credentials work on `core-stg`.
  `stg-erp.esb.co.id` answers HTTP 500 to everything. Waiting on the ESB team for a
  valid staging token.

## Update 2026-09-30 — Purchase Request slice 3 (create / update / delete) and import

The project owner asked to start slice 3 and add purchase request import. Contracts
come from the vendor's OpenAPI description. `[ESB-005]` covers create/update/delete,
`[ESB-006]` covers import, and `[ESB-007]` covers the form lookups, all in
`docs/05-api-integration.md`. The decisions are in ADR-021.

**Selesai**

- Adapter: `PurchaseRequestClient` gains `createPurchaseRequest()`,
  `updatePurchaseRequest()`, `deletePurchaseRequest()`, `importPurchaseRequests()`,
  `listBranches()`, `listRequestTemplates()`, and `listTemplateLines()`. The
  `PurchaseRequestSource` contract is extended to match.
- `EsbClient::upload()` sends multipart/form-data. It is a write, so it gets exactly one
  attempt.
- `ErrorClassifier` now carries the envelope's `errors[]` onto every `EsbException` as
  `fieldErrors` (`App\Integrations\ESB\Responses\EsbFieldError`), bounded and filtered.
- New canonical DTOs: `Branch`, `RequestTemplate`, `PurchaseRequestTemplateLine`,
  `PurchaseRequestDraft`, and `PurchaseRequestDraftLine`. `PurchaseRequestLine` gains
  `requestProcessId`.
- `PurchaseRequestMapper` gains `branchesFrom()`, `templatesFrom()`,
  `templateLinesFrom()`, and `toRequestBody()`.
- `App\Actions\Support\GuardedEsbWrite` and `WriteAlreadyInFlight` are new.
  `DecidePurchaseRequest` was refactored onto them, and `DecisionAlreadyInFlight` was
  removed. New Actions: `CreatePurchaseRequest`, `UpdatePurchaseRequest`,
  `DeletePurchaseRequest`, and `ImportPurchaseRequests`, plus `PurchaseRequestLocks` and
  `UnconfirmedWrite`.
- `Permission::ManagePurchaseRequests` (`purchase-requests.manage`) is new and mirrored
  in `permissions.ts`. No role holds it. New audit actions:
  `purchase-request.created`, `.updated`, `.deleted`, `.imported`, and
  `.write-unconfirmed`.
- Form requests: `PurchaseRequestFormQuery`, `SavePurchaseRequestRequest`, and
  `ImportPurchaseRequestsRequest`. The import request checks size, extension, and the
  file's leading bytes. `BaseFormRequest::userHoldsAll()` was added, and the base
  `Controller::toast()` was moved up.
- Controllers: `PurchaseRequestFormController` (create, store, edit, update, destroy)
  and `PurchaseRequestImportController` (create, store). The new routes sit under
  `throttle:esb` plus `can:esb.invoke` and `can:purchase-requests.manage`.
- Pages: `purchase-requests/form.tsx` (branch, then template, then quantities; edit
  mode) and `purchase-requests/import.tsx` (upload plus a row-error table). `index.tsx`
  has New and Import buttons. `show.tsx` has Edit and Delete with a confirm dialog.
- Tests were written first: `PurchaseRequestFormTest`, `PurchaseRequestWriteTest`,
  `PurchaseRequestImportTest`, and new cases in `ErrorClassifierTest`,
  `PurchaseRequestMapperTest`, and `EsbClientTest`. `PurchaseRequestDecisionTest` now
  uses the shared lock key.

**Tersisa**

- Business: which role holds `purchase-requests.manage` (and `.approve`).
- Not built: cost center and project pickers, the `qty-suggestion` helper, attachment
  upload/delete, and porting the catalog generator (the import and lookups are not in
  `docs/esb/` yet).
- Run all of the above against a real ESB. It has only been tested against
  `Http::fake()`.

**Known issues**

- `vendor/bin/pint --test` fails on `app/Http/Middleware/HandleInertiaRequests.php`, and
  `phpstan` reports two `nullsafe.neverNull` errors in
  `app/Services/EsbConnectionInspector.php`. Both come from changes that were already
  uncommitted before this session (ADR-020 work). They were left untouched here rather
  than mixed into this slice.

**Assumptions / TBD**

- Create/update dates are sent as `YYYY-MM-DD`. The vendor only says "string".
  Documented as TBD in `docs/05-api-integration.md` and
  `SavePurchaseRequestRequest::rules()`.
- `templateTypeID` is not sent to `/document-template/list`, because which id means
  "purchase request" is TBD. The template list may include other types.
- Which statuses accept update/delete is TBD, so the buttons are always shown.
- The import sheet layout is undocumented. The gateway forwards the file without parsing
  it, and the import screen tells users to use the ERP's own template.

**Checks dijalankan**

- `php artisan test`: pass, 441 tests.
- `tsc --noEmit`: pass. `vp check`: pass. `npm run build`: pass.
- `pint --test`: fails only on `HandleInertiaRequests.php` (pre-existing, see Known
  issues).
- `phpstan analyse`: fails only on `EsbConnectionInspector.php` (pre-existing).
- Not run against a real ESB. Blocked on a valid staging token (see the 2026-09-29
  entry).

## Update 2026-09-30 — ESB catalog resynced (Core now from OpenAPI)

The project owner asked for the endpoint documentation to be updated from the vendor.
ESB Core's apiDoc source has returned 404 since 2026-09-25, so the generator was ported to
OpenAPI. The full change list is "Catalog sync 2026-09-30" in
`docs/05-api-integration.md`.

**Selesai**

- `docs/esb/generate-catalog.py` reads ESB Core from `openapi.json` and ESB OMS from
  apiDoc as before. The per-operation docs link is Stoplight's `#/paths/{slug}/{method}`,
  with the slug rule transcribed from the vendor's `stoplight-elements.js`. The OMS output
  is byte-identical apart from the date, which checks the refactor.
- The catalogs were regenerated. Core has 1224 operations in 116 groups (was 363). OMS is
  unchanged at 38.
- `esb:import-catalog`: an OpenAPI row is identified as `core:{METHOD} {path}`. apiDoc
  rows keep `{product}:{Group}:{name}@{version}`. `group` now comes from the section
  heading for both formats, so it is spaced ("Master Menu"), not "Master_Menu".
- Tests were updated first: `ImportEndpointCatalogTest` (counts, OpenAPI identity, apiDoc
  identity, families) and `EndpointCatalogTest` (totals, group/method/family filters).
- Docs: `docs/05` (status table, sync findings, base URLs, refresh note), README,
  `docs/04`, `docs/07`, TODO, the `EsbFamily::Voucher` docblock, and the esb-catalog-sync
  skill.

**Findings (vendor, not fixed in code)**

- 22 old Core operations are no longer in the Core document, including Online Voucher
  (the voucher family now has no documented operations) and `/corev1/*`. None is used by
  the gateway.
- The Core intro says the envelope `code` mirrors the HTTP status and that pagination
  uses `dataCount`. Every operation example says `EC03100000` / `count`. The gateway is
  unchanged; TBD with the ESB team.
- `POST /auth/refresh-token {token}` is a new alternative to `GET /auth/refresh`. The
  gateway still uses the GET; TBD which one is intended.
- ADR-010's family table assumed the vendor spreads Core groups over several hosts. The
  new Core document declares only Core hosts. No ADR change yet; revisit when a non-Core
  family is first wired.

**Tersisa**

- After deploy, run `php artisan esb:import-catalog`. The Core codes changed format, so
  it replaces the old Core rows rather than updating them.

**Checks dijalankan**

- `php artisan test`: pass, 442 tests. `DatabaseSeederTest` now expects 1262.
- `python docs/esb/generate-catalog.py`: Core 1224, OMS 38.
- `pint --test` and `phpstan` still fail only on the earlier uncommitted files
  (`HandleInertiaRequests.php`, `EsbConnectionInspector.php`), the same as the previous
  entry.

## Update 2026-09-30 — Purchase Invoice module, slice 1

Built test-first on the Purchase Request pattern. Documented as `[ESB-008]`–`[ESB-010]`
in `docs/05-api-integration.md`, with field names transcribed from the vendor's OpenAPI
description.

**Selesai**

- Read: `GET /purchase/purchase-invoices` (list with number, supplier invoice number,
  date range, branches, and free-text filters) and
  `GET /purchase/purchase-invoices/{purchaseInvoiceNum}` (header, lines, costs, totals).
- Write: authorize / reject (`PATCH .../authorize|reject`) and delete. Each goes through
  `GuardedEsbWrite` under one per-document lock, `purchase-invoice-write:{number}`.
- `PurchaseInvoiceSource` contract bound to `PurchaseInvoiceClient` in
  `AppServiceProvider`. Canonical DTOs: `PurchaseInvoice`, `PurchaseInvoiceSummary`,
  `PurchaseInvoiceLine`, `PurchaseInvoiceCost`, `PurchaseInvoicePage`,
  `PurchaseInvoiceQuery`, `PurchaseInvoiceDecision`.
- New permissions `purchase-invoices.approve` and `purchase-invoices.manage`, mirrored in
  `resources/js/types/permissions.ts`. No role holds them, so only Super Admin can use
  them.
- New audit actions `purchase-invoice.authorized` / `.rejected` / `.deleted` /
  `.write-unconfirmed`.
- Inertia pages `purchase-invoices/index` and `purchase-invoices/show`, and a sidebar
  entry under `esb.invoke`.
- Tests: `tests/Feature/PurchaseInvoices/` (List, Detail, Decision, Delete) and
  `tests/Unit/Integrations/ESB/PurchaseInvoiceMapperTest.php`, 56 tests.

**Assumptions / TBD**

- The detail's status label is read from `printData.statusName`, because the detail
  documents no top-level `statusName`.
- Line tax amounts are not shown, because the vendor's `vat` / `vatValue` / `vatTotal`
  naming is contradictory. Only `taxName` is carried.
- Amounts are floats, display-only. The gateway never computes totals.
- Create/update were left out on purpose. See TODO "Module — Purchase Invoice".
- `PurchaseInvoiceDecision` duplicates the two-case `PurchaseRequestDecision`. Merge them
  into one approval-decision enum when a third document type needs one.

**Checks dijalankan**

- `php artisan test` — **pass**, 498 tests (1943 assertions).
- `tsc --noEmit`, `vp check`, `npm run build`, `pint` — pass.
- `phpstan analyse` — 2 errors, both in `app/Services/EsbConnectionInspector.php`
  (`nullsafe.neverNull`, lines 60 and 94). That file was already modified before this
  slice and was not touched by it. None in the new code.
- Not run against a real ESB — faked HTTP only, like every other slice.

## Update 2026-10-01 — Sales push module: POS -> Push Sales Data V2 (ADR-022)

Built test-first. Documented as `[ESB-011]` in `docs/05-api-integration.md`. The first
inbound flow: a POS calls the gateway, and the gateway pushes to the ESB from the queue.

**Selesai**

- ESB layer: `ErrorCode::isSuccess()` accepts `EC03100000` and the FnB backend's `"200"`.
  `ErrorCode` maps the FnB backend's `400` (validation) and `401` (authentication).
  `ErrorClassifier` reads `data[]` strings into `EsbFieldError::fromMessage()`.
- Adapter: `SalesDataSink` contract bound to `SalesDataClient` (family FnB backend,
  `POST /extv1/push/sales-data`), canonical `SalesDataReceipt`.
- API: `routes/api.php` (`/api/v1/sales-data` POST + GET status), `AuthenticateApiClient`
  middleware (`api-client`), limiter `sales-push-api`, `PushSalesDataRequest`,
  `SalesPushResource`, `Api\SalesDataController`. `/api/*` skips `TrimStrings` and
  `ConvertEmptyStringsToNull` (`bootstrap/app.php`).
- Tables `api_clients` and `sales_pushes` (payload `encrypted:array`). Models `ApiClient`,
  `SalesPush`. Commands `api-client:create` / `api-client:revoke`. Config
  `config/sales_push.php`.
- Queue: `App\Jobs\PushSalesData` (bounded tries/backoff, `WithoutOverlapping` per
  `salesNum`), Actions `ReceiveSalesData`, `SendSalesPush`, `RetrySalesPush`.
- Web: `sales-pushes/index` and `sales-pushes/show` pages, "Send again" button, sidebar
  group "Sales". Permissions `sales-pushes.view` (IT Admin, Integration Operator) and
  `sales-pushes.retry` (Integration Operator), mirrored in `permissions.ts`.
- Audit actions `api-client.issued` / `.revoked`, `sales-push.retried`.
- Sidebar: `NavMain` supports collapsible groups (`NavGroup` in `types/navigation.ts`);
  purchase requests and invoices now sit under "Purchase".
- Tests: `tests/Feature/SalesPushes/` (ApiClientCommand, SalesDataApi, PushSalesDataJob,
  SalesPushViewer), `tests/Feature/Integrations/ESB/SalesDataClientTest.php`, new cases in
  `EsbEnvelopeTest` and `ErrorClassifierTest`. All failed before the implementation,
  except two job tests (401 and unconfigured family) added after `SendSalesPush` was
  written; they passed on first run.

**Tersisa**

- Run on ESB staging with the Core login credential and confirm the FnB backend accepts
  that token (vendor documents a static token). Blocked on the ESB environment, not code.
- Issue real POS tokens (`php artisan api-client:create <name>`) and run a queue worker
  in every environment that should push sales.
- Shift Data V2, a UI to manage API clients, retention/pruning for `sales_pushes`.

**Known issues**

- `WithoutOverlapping` releases a job when another push of the same `salesNum` is
  running, and each release counts against `tries`. Many rapid pushes of one `salesNum`
  can use up attempts; the push then shows `failed` and can be sent again by hand.
- `pint` also reformatted `app/Http/Middleware/HandleInertiaRequests.php` (operator
  spacing only). That file had uncommitted changes before this session.

**Assumptions / TBD**

- FnB backend accepts the Core login token — TBD, `[ESB-011]` and ADR-022 Open.
- Meaning of `result.status` other than `"00"` — TBD, `[ESB-011]`.
- Gateway validates only identity and structure; the ESB validates the rest — ADR-022.
- Retention of `sales_pushes.payload` — TBD, `docs/07-database-design.md`.

**Checks dijalankan**

- `php artisan test` — **pass**, 545 tests.
- `tsc --noEmit`, `vp check`, `npm run build`, `pint` — pass.
- `phpstan analyse` — same 2 pre-existing errors in `EsbConnectionInspector.php`; none in
  new code.
- Not run against a real ESB — faked HTTP only.

## Update 2026-10-01 — Master Menu module, slice 1 (read-only, ESB Core)

Built test-first. Documented as `[ESB-012]` in `docs/05-api-integration.md`. First built
on the OMS `GET /corev1/master/get-menu` (FnB backend), then switched the same day, at
the owner's request, to ESB Core `GET /menu/browse`.

**Selesai**

- `MenuSource` contract bound to `MenuClient` (Core, `GET /menu/browse`), `MenuMapper`,
  canonical `Menu`, `MenuQuery`, `MenuPage`.
- `ListMenusRequest` (page, limit, menuName, menuCode, flagActive), `MenuController`
  (`GET /menus`), permission `esb.invoke`, throttled with the other ESB routes.
- Inertia page `menus/index`, TS type `resources/js/types/menu.ts`.
- Sidebar: new group "Master data" holding Products and Menus.
- Removed with the switch: the menu detail page and route, the OMS-only canonical DTOs
  (`MenuTemplatePrice`, `MenuPackageGroup`, `MenuPackageItem`, `MenuExtra`,
  `MenuReference`), and the `EC011200` success code. Core `/menu/browse` returns none of
  that data.
- Tests: `tests/Feature/Menus/MenuTest.php`, `tests/Unit/Integrations/ESB/MenuMapperTest.php`.
  Rewritten for Core first; they failed before the switch was implemented.

**Tersisa**

- Nothing for the list. If prices / packages / extras are needed, the OMS operation is
  the documented source (see `[ESB-012]`).

**Assumptions / TBD**

- No vendor limit cap is documented for `/menu/browse`; the gateway caps at 100.
- The browse row has no active flag; `flagActive` is a filter only.

**Checks dijalankan**

- `php artisan test` — **pass**, 554 tests.
- `tsc --noEmit`, `vp check`, `npm run build`, `pint` — pass.
- `phpstan analyse` — same 2 pre-existing errors in `EsbConnectionInspector.php`.
- Not run against a real ESB — faked HTTP only.

## Update 2026-10-01 — Master Menu (ESB OMS) added beside the Core menu list

`[ESB-013]`. At the owner's request the OMS version (removed earlier the same day) is
back as its own screen, next to the Core `/menus` list (`[ESB-012]`).

**Selesai**

- `MasterMenuSource` bound to `MasterMenuClient` (FnB backend, `GET
  /corev1/master/get-menu`), `MasterMenuMapper`, canonical `MasterMenu*` DTOs.
- `ListMasterMenusRequest`, `MasterMenuController` (`/master-menus`,
  `/master-menus/{code}`), permission `esb.invoke`, throttled with the ESB routes.
- Pages `master-menus/index` and `master-menus/show`, TS types
  `resources/js/types/master-menu.ts`, sidebar entry "Master menu" under "Master data".
- `ErrorCode::isSuccess()` accepts `EC011200` again.
- Restored from the earlier OMS implementation with every class renamed to
  `MasterMenu*` so it does not clash with the Core `Menu*` classes.
- Tests: `tests/Feature/MasterMenus/MasterMenuTest.php`,
  `tests/Unit/Integrations/ESB/MasterMenuMapperTest.php`, the `EC011200` case in
  `EsbEnvelopeTest`. Restored and run first; they failed before the classes existed.

**Assumptions / TBD**

- Same as `[ESB-013]`: active menus only, exact-code detail, code pattern, static-token
  question for the FnB backend.
- Two canonical menu shapes exist (`Menu`, `MasterMenu`) until the business picks one
  source.

**Checks dijalankan**

- `php artisan test` — **pass**, 569 tests.
- `tsc --noEmit`, `vp check`, `npm run build`, `pint` — pass.
- `phpstan analyse` — same 2 pre-existing errors in `EsbConnectionInspector.php`.
- Not run against a real ESB — faked HTTP only.

## Update 2026-10-13 — Bill of Material module, slice 1 (read-only, ESB Core)

`[ESB-014]`, built test-first on ESB Core `GET /product/bom` and
`GET /product/bom/{bomID}`.

**Selesai**

- `BillOfMaterialSource` bound to `BillOfMaterialClient` (Core),
  `BillOfMaterialMapper`, canonical `BillOfMaterial*` DTOs (summary, detail, line,
  substitute, cost, replacement, query, page).
- `ListBillsOfMaterialRequest` (page, limit, bomName, bomCode, productName,
  flagActive), `BillOfMaterialController` (`/bills-of-material`,
  `/bills-of-material/{bomId}`), permission `esb.invoke`, throttled with the ESB routes.
  Integration-log operations `bom.list`, `bom.view`.
- Pages `bills-of-material/index` and `bills-of-material/show`, TS types
  `resources/js/types/bill-of-material.ts`, sidebar entry "Bill of material" under
  "Master data".
- Tests: `tests/Feature/BillsOfMaterial/BillOfMaterialTest.php`,
  `tests/Unit/Integrations/ESB/BillOfMaterialMapperTest.php`, fixture
  `tests/Fixtures/CoreBom.php`. Written and run first; they failed before the classes
  existed.

**Tersisa**

- Writes (create / update / delete / restore), upload/export, push/pull, BOM type
  filter, location-scoped detail. See `TODO.md` "Module — Bill of Material".

**Assumptions / TBD**

- An unknown BOM id is assumed to come back as `EC03100004` (rendered 404); the vendor
  documents only 400/401/500 for the path. TBD in `[ESB-014]`.
- A successful detail without a `bomID` is treated as not found (vendor example is
  zero-valued).

**Checks dijalankan**

- `php artisan test` — **pass**, 586 tests.
- `tsc --noEmit`, `vp check`, `npm run build`, `pint` — pass.
- `phpstan analyse` — same 2 pre-existing errors in `EsbConnectionInspector.php`.
- Not run against a real ESB — faked HTTP only.

## Update 2026-10-15 — Document Template module, slice 1 (read-only, ESB Core)

`[ESB-016]`, built test-first on ESB Core `GET /document-template` and
`GET /document-template/{requestTemplateID}`.

**Selesai**

- `DocumentTemplateSource` bound to `DocumentTemplateClient` (Core),
  `DocumentTemplateMapper`, canonical `DocumentTemplate*` DTOs (summary, detail, line,
  query, page) and enum `DocumentTemplateType` (1 Purchase Request, 2 Usage Management,
  3 Item Journal).
- `ListDocumentTemplatesRequest` (page, limit, requestTemplateName,
  requestTemplateTypeID, flagActive), `DocumentTemplateController`
  (`/document-templates`, `/document-templates/{templateId}`), permission `esb.invoke`.
  Integration-log operations `document-template.list`, `document-template.view`.
- Pages `document-templates/index` and `document-templates/show`, TS types
  `resources/js/types/document-template.ts`, sidebar entry "Document templates" under
  "Master data".
- Tests: `tests/Feature/DocumentTemplates/DocumentTemplateTest.php`,
  `tests/Unit/Integrations/ESB/DocumentTemplateMapperTest.php`, fixture
  `tests/Fixtures/CoreDocumentTemplate.php`. Written and run first; they failed before
  the classes existed.

**Tersisa**

- Branch / cost-center names, branch filter, writes, upload/export, push/pull. See
  `TODO.md` "Module — Document Template".
- The Purpose module (`[ESB-015]`, uncommitted in the working tree) has no contract
  section in `docs/05-api-integration.md` yet.

**Assumptions / TBD**

- An unknown template id is assumed to come back as `EC03100004` (rendered 404).
- Line fields `mode` and `requestProcessID` are undocumented and not carried.

**Checks dijalankan**

- `php artisan test` — **pass**, 614 tests.
- `tsc --noEmit`, `vp check`, `npm run build`, `pint` — pass.
- `phpstan analyse` — same 2 pre-existing errors in `EsbConnectionInspector.php`.
- Not run against a real ESB — faked HTTP only.

## Update 2026-10-15 — Document Template module, slice 2 (create)

`[ESB-016]`, built test-first on ESB Core `POST /document-template`, with
`GET /product-detail/browse` as the product picker.

**Selesai**

- New permission `document-templates.manage` (PHP enum + `permissions.ts`); no role
  holds it, so only Super Admin can create. Audit actions `document-template.created`,
  `document-template.write-unconfirmed`.
- `DocumentTemplateSource::createDocumentTemplate()` / `browseProductDetails()`,
  canonical `DocumentTemplateDraft`, `DocumentTemplateDraftLine`, `ProductDetailOption`;
  mapper `toRequestBody`, `createdIdFrom`, `productDetailsFromRows`.
- `CreateDocumentTemplate` action on `GuardedEsbWrite` (per-person lock
  `document-template-create:{userId}`, one attempt, unconfirmed audit), lock keys in
  `DocumentTemplateLocks`.
- `DocumentTemplateFormController` (`GET /document-templates/create`,
  `POST /document-templates`), `DocumentTemplateFormQuery` (productSearch),
  `SaveDocumentTemplateRequest` (name, type, active, branchIds, lines; emoji and
  duplicate checks). ESB field errors mapped onto the form.
- Page `document-templates/create` (branches checklist, product search + add, line name
  and purpose per line); "New template" button on the list for holders of the permission.
- Tests: `tests/Feature/DocumentTemplates/DocumentTemplateCreateTest.php`, new cases in
  `DocumentTemplateMapperTest.php`. Written and run first; they failed before the code
  existed.

**Assumptions / TBD**

- The create result's id key is assumed `requestTemplateID`; without it the user lands
  on the list (never a guessed link).
- The `field` format of nested ESB validation errors is undocumented; mapped by prefix.
- Branches come from `PurchaseRequestSource::listBranches()` -- reused, not duplicated.
  If a third screen needs branches, move it to its own `BranchSource`.

**Checks dijalankan**

- `php artisan test` — **pass**, 643 tests.
- `tsc --noEmit`, `vp check`, `npm run build`, `pint` — pass.
- `phpstan analyse` — same 2 pre-existing errors in `EsbConnectionInspector.php`.
- Not run against a real ESB — faked HTTP only.

## Update 2026-10-15 — Document Template spreadsheet import (ADR-023, new decision)

**New architectural decision: ADR-023** — read it before touching imports. New
dependency `openspout/openspout` ^4.

**Selesai**

- Upload → validate → sync, each started from the page by a holder of `esb.invoke` +
  `document-templates.manage`:
  - `StoreDocumentTemplateImport` (reader + parser, no ESB call) stores into
    `document_template_imports` / `_entries` / `_lines` (migration
    `2026_10_15_090000_…`).
  - `ValidateDocumentTemplateImport` (job + action, reads only) resolves branch codes,
    product code + unit, and checks the name in the ERP → `ready` / `blocked`, with
    reasons.
  - `SyncDocumentTemplateImport` (job + action) sends `ready` templates via
    `CreateDocumentTemplate` (now takes an actor id + audit context); refused →
    `failed`, unconfirmed → stops the run.
- Owner's rules: merge branches across a template's rows; Kategori = line name; a name
  already in the ERP blocks that template with the reason.
- Pages `document-template-imports/index` (upload + list) and `/show` (status per
  template, reasons, lines; refreshes every 3 s while a job runs). "Import" button on
  the document template list.
- `DocumentTemplateRules` holds the name rules for both the form and the import.
- Tests: `tests/Unit/Imports/DocumentTemplateSheetParserTest.php`,
  `tests/Feature/DocumentTemplates/DocumentTemplateImportTest.php`, fixture
  `tests/Fixtures/DocumentTemplateSheet.php` (builds real .xlsx files). Written and run
  first; they failed before the code existed.
- The real file parses as 22 templates / 178 lines with no sheet problems (checked
  locally, no ESB).

**Assumptions / TBD**

- `productCode` on `/product-detail/browse` may match partially; the code is compared
  exactly in the gateway.
- Unit names in the sheet (`JRC @ 5 LT`, `BTL @0.946 LT`, …) must equal the ERP's
  `uomName` ignoring case/spacing. Not yet seen against a real ESB.
- Retention of the import tables.
- `composer audit` reports 2 advisories in `league/commonmark` (existing dependency,
  not touched here).

**Checks dijalankan**

- `php artisan test` — **pass**, 675 tests.
- `tsc --noEmit`, `vp check`, `npm run build`, `pint` — pass.
- `phpstan analyse` — same 2 pre-existing errors in `EsbConnectionInspector.php`.
- Not run against a real ESB — faked HTTP only. Needs a queue worker in production.

## Update 2026-10-15 — Import fixed after the first run on staging

The first validate on staging failed: `GET /product-detail/browse` → `EC03100003`.

**Diperbaiki**

- `/product-detail/browse` requires `productDetailID(s)` on the real ESB (the vendor
  documents them as optional). Product lookup is now `GET /product/list` (exact code
  kept; the ESB matches partially) + `GET /product/{productID}` for the units
  (`DocumentTemplateSource::findProductsByCode()` returns `ProductUnits`). The create
  form's product picker had the same bug and uses the same lookup now.
- Inactive products are reported as such; inactive units are not offered.
- The reader returned formula text (`=VLOOKUP(…)`) instead of the saved result; it now
  reads `FormulaCell::getComputedValue()` (`DocumentTemplateSheetReaderTest`).
  **Imports uploaded before this fix stored formula text as product codes: upload the
  file again.**
- "Validation/Sync stopped" printed the reference twice.

**Dry run of the real file against staging (read only)**

- All 126 product codes are found.
- Branch codes STHO, STJAWA, STFB are not in the ERP (it has HOF, STJWB, STFMB, STCKB,
  STPVJ, STTSM, STP23, TSPVJ, TSSMB, TSTSM). Every template but one is blocked by this.
  Waiting on the owner.
- Units: 77 of 126 code+unit pairs match exactly. 36 more would match on the part
  before "@" (`PACK` → `PACK @ 0.3 KG`). That rule is **not implemented**; it waits on
  the owner. 13 differ outright (`PAIL` vs `PL @ 8 LT`, `KG` vs `PACK @ 0.25 KG`, …).

**Checks dijalankan**

- `php artisan test` — **pass**, 681 tests. `pint` pass. `phpstan` — same 2
  pre-existing errors.

## Update 2026-10-19 — Purchase Order module, slice 1 (ESB Core)

`[ESB-017]`, built test-first on the Purchase Invoice pattern. Contract transcribed from
the vendor's `openapi.json` on 2026-10-19.

**Selesai**

- Read: `GET /purchase/purchase-order` (number, order-date range, required-date range,
  branches, free-text) and `GET /purchase/purchase-order/{purchaseNum}` (header, lines,
  linked goods receipts and purchase invoices, the ERP's `access` flags).
- Write: authorize, reject (with the reason the vendor marks as required), and delete,
  each through `GuardedEsbWrite` under one lock, `purchase-order-write:{number}`.
- `PurchaseOrderSource` bound to `PurchaseOrderClient` in `AppServiceProvider`;
  `PurchaseOrderMapper`; canonical `PurchaseOrder`, `PurchaseOrderSummary`,
  `PurchaseOrderLine`, `PurchaseOrderLinkedDocument`, `PurchaseOrderPage`,
  `PurchaseOrderQuery`, `PurchaseOrderDecision`.
- `ListPurchaseOrdersRequest`, `DecidePurchaseOrderRequest` (reason required for reject,
  max 500), `PurchaseOrderController` (index, show, destroy),
  `PurchaseOrderDecisionController`; routes `purchase-orders.*` under the ESB group.
- New permissions `purchase-orders.approve` and `purchase-orders.manage` (PHP enum +
  `permissions.ts`); no role holds them, so only Super Admin. Audit actions
  `purchase-order.authorized` / `.rejected` (with the reason) / `.deleted` /
  `.write-unconfirmed`.
- Pages `purchase-orders/index` and `purchase-orders/show` (reject dialog with a reason
  field; Delete hidden only when the ERP says `canDelete: false`; invoice numbers link to
  the purchase invoice page), TS types `resources/js/types/purchase-order.ts`, sidebar
  entry "Purchase orders" under "Purchase".
- Tests: `tests/Feature/PurchaseOrders/` (List, Detail, Decision, Delete),
  `tests/Unit/Integrations/ESB/PurchaseOrderMapperTest.php`, fixture
  `tests/Fixtures/CorePurchaseOrder.php` — 61 tests. Written and run first: 58 failed
  before the code existed (the 3 path-escape tests passed already, as a missing route is
  a 404 too).
- Docs: `[ESB-017]` in `docs/05`, permissions in `docs/06`, "Module — Purchase Order" in
  `TODO.md`.

**Tersisa**

- See `TODO.md` "Module — Purchase Order": roles for the two permissions, close/unclose,
  status filter (`GET /common/status`), create/update, print/email, uploads.
- Refactor, deliberately not mixed into this change: merge the three decision enums.

**Assumptions / TBD**

- Unknown `purchaseNum` assumed to answer `EC03100004` (rendered 404), as for invoices.
- Reject reason cap of 500 is the gateway's own; the vendor documents none.
- `purchaseRequestNums` carried as one string (separator undocumented).
- Line tax figures (`vat`/`vatValue`/`dppValue`) not shown, as for invoices.

**Checks dijalankan**

- `php artisan test` — **pass**, 748 tests (3514 assertions).
- `tsc --noEmit`, `vp check`, `npm run build`, `pint --test` — pass.
- `phpstan analyse` — same 2 pre-existing errors in `EsbConnectionInspector.php`; none
  in new code.
- Not run against a real ESB — faked HTTP only. Not checked in a browser.

## Update 2026-10-19 — Simple Purchase module, slice 1 (ESB Core)

`[ESB-018]`, built test-first on the Purchase Order pattern from the same day.

**Selesai**

- Read: `GET /purchase/simple-purchase` (number, supplier invoice number, date range,
  single `branchID`, free-text) and `GET /purchase/simple-purchase/{cashPurchaseNum}`
  (header, lines, costs, payments).
- Write: authorize, reject (required reason), delete — `GuardedEsbWrite`, lock
  `simple-purchase-write:{number}`.
- `SimplePurchaseSource` bound to `SimplePurchaseClient`; `SimplePurchaseMapper`;
  canonical `SimplePurchase`, `SimplePurchaseSummary`, `SimplePurchaseLine`,
  `SimplePurchaseCost`, `SimplePurchasePayment`, `SimplePurchasePage`,
  `SimplePurchaseQuery`, `SimplePurchaseDecision`.
- `ListSimplePurchasesRequest`, `DecideSimplePurchaseRequest`,
  `SimplePurchaseController`, `SimplePurchaseDecisionController`, routes
  `simple-purchases.*`. Actions `DecideSimplePurchase`, `DeleteSimplePurchase`,
  `SimplePurchaseLocks`, `UnconfirmedWrite`.
- Permissions `simple-purchases.approve` / `.manage` (enum + `permissions.ts`, no role);
  audit actions `simple-purchase.authorized` / `.rejected` / `.deleted` /
  `.write-unconfirmed`.
- Pages `simple-purchases/index` and `/show`, TS types
  `resources/js/types/simple-purchase.ts`, sidebar entry "Simple purchases".
- Tests: `tests/Feature/SimplePurchases/` (List, Detail, Decision, Delete),
  `tests/Unit/Integrations/ESB/SimplePurchaseMapperTest.php`, fixture
  `tests/Fixtures/CoreSimplePurchase.php` — 59 tests; 56 failed before the code existed
  (3 path-escape tests already 404).
- Docs: `[ESB-018]` in `docs/05`, `docs/06`, `TODO.md` "Module — Simple Purchase".

**Tersisa**

- `validate` pre-check, branch/status filters, create/update, uploads — see TODO.
- Duplication with the Purchase Order slice is now real (decision actions, controllers,
  page controls). Extraction recorded as one refactor in TODO, not mixed in here.

**Assumptions / TBD**

- Unknown number → `EC03100004` → 404 (assumed). Reason cap 500 (gateway's own).
- Integer `dueDate`, header `coaNo` / `description` not carried (undocumented).

**Checks dijalankan**

- `php artisan test` — **pass**, 807 tests (3821 assertions).
- `tsc --noEmit`, `vp check` (after `vp fmt` on the two new pages), `npm run build`,
  `pint --test` — pass.
- `phpstan analyse` — same 2 pre-existing errors in `EsbConnectionInspector.php`.
- Not run against a real ESB — faked HTTP only. Not checked in a browser.

## Update 2026-10-19 — Approval decision enums merged (`TODO-modules.md` §1)

**Selesai**

- Added `App\Domain\Canonical\ApprovalDecision` (`authorize` / `reject`), test-first
  (`tests/Unit/Domain/Canonical/ApprovalDecisionTest.php`).
- Removed `PurchaseRequestDecision`, `PurchaseInvoiceDecision`, `PurchaseOrderDecision`,
  `SimplePurchaseDecision`. Their contracts, clients, actions, FormRequests and decision
  controllers now take `ApprovalDecision`. Enum values are unchanged, so the pages and
  routes are untouched. DTO lists of the purchase order and simple purchase entries
  in `docs/05-api-integration.md` updated. Pure rename — no behaviour change.

**Checks dijalankan**

- `php artisan test` — **pass**, 809 tests (3825 assertions); existing tests unchanged.
- `pint --test` — pass.
- `phpstan analyse --memory-limit=1G` — same 2 pre-existing errors in
  `EsbConnectionInspector.php` (default 128M limit crashes the worker).
- No frontend change, so `tsc` / `vp check` / `npm run build` not re-run.

## Update 2026-10-19 — Shared decide/delete for approvable documents (`TODO-modules.md` §1)

**Selesai**

- `App\Actions\Support\ApprovableDocument` — lock prefix + authorized / rejected /
  deleted / write-unconfirmed audit actions; `lock($number)` keeps the existing
  `<prefix>-write:<number>` keys.
- `App\Actions\Support\DocumentWrites` — `decide()` (drops the reason on authorize,
  audits it on reject) and `delete()` on top of `GuardedEsbWrite`, with the shared
  unconfirmed-write audit.
- `App\Http\Controllers\Concerns\HandlesDocumentWrites` — `decisionOutcome()` /
  `deleteOutcome()`: the toast + redirect rules, same message text as before.
- Migrated purchase order, simple purchase, purchase invoice: `Decide*` / `Delete*`
  are now thin wrappers; `<X>Document::approvable()` replaces `<X>Locks` and the
  per-module `UnconfirmedWrite` (both removed for those three modules).
- Tests written first: `tests/Feature/Actions/Support/DocumentWritesTest.php` (10),
  `tests/Feature/Http/HandlesDocumentWritesTest.php` (11, dummy controller + test
  routes, asserts exact toast text). Existing module tests unchanged and green.

**Tersisa**

- Purchase request not migrated: its unconfirmed decision audit is
  `purchase-request.decision-unconfirmed` with a `decision` key, not
  `*.write-unconfirmed` / `operation`. Unifying changes the audit trail — needs a
  decision. Its create/update/import still use `PurchaseRequests\UnconfirmedWrite`.
- Decision/delete page controls not extracted yet (UI-2 `ApprovalActions`).

**Checks dijalankan**

- `php artisan test` — **pass**, 830 tests (3877 assertions).
- `pint --test` — pass.
- `phpstan analyse --memory-limit=1G` — same 2 pre-existing errors in
  `EsbConnectionInspector.php`.
- No frontend change; `tsc` / `vp check` / `npm run build` not re-run.

## Update 2026-10-19 — Shared list types in the frontend (`TODO-modules.md` P2)

**Selesai**

- `resources/js/types/pagination.ts`: `Pagination` (mirrors
  `Pagination::jsonSerialize()`, shape locked by `PaginationTest`), `PagingFilters`
  (`page` / `limit`), `DocumentFilters` (`search` / `dateFrom` / `dateTo`). Re-exported
  from `@/types`.
- Ten list pages under `resources/js/pages/*/index.tsx` drop their local `Pagination`
  copy; their `Filters` now extend `PagingFilters` (bills-of-material,
  document-templates, menus, purposes) or `DocumentFilters` (purchase requests, orders,
  invoices, simple purchases). Module-specific fields and index signatures stay local.
- Types only — no runtime change. No frontend test runner yet (P3), so `tsc` is the
  gate.

**Commit**

- User commits manually per phase (`TODO-modules.md` §0.4). P1 and P2 are both in the
  working tree, uncommitted; suggested messages are in §0.4.

**Checks dijalankan**

- `tsc --noEmit` — pass. `vp check` — pass. `npm run build` — pass.
- PHP untouched by this phase; last `php artisan test` run 830 pass (P1).

## Update 2026-10-19 — Frontend component tests (ADR-024, `TODO-modules.md` P3)

**New decision:** ADR-024 in `DECISIONS.md`.

**Selesai**

- `vp test` (Vitest 4.1.11 bundled in vite-plus 0.3.0) configured in the `test` block
  of `vite.config.ts`: `jsdom`, `resources/js/**/*.test.{ts,tsx}`, setup
  `resources/js/test/setup.ts` (jest-dom matchers + cleanup).
- devDependencies (exact): `@testing-library/react` 16.3.3, `@testing-library/dom`
  10.4.2, `@testing-library/user-event` 14.6.7, `@testing-library/jest-dom` 6.9.1,
  `jsdom` 29.1.1. Newest that run on Node 20.19 (local) and 22 (CI).
- `npm run test:ui`; added to `composer ci:check`, so CI runs it.
- First test, written before the config: `resources/js/components/heading.test.tsx`
  failed with `document is not defined`, passed once jsdom was configured.
- Docs synced: `docs/12-testing.md` (Frontend components), `docs/03-technology-stack.md`,
  `AGENT.md` testing rules, CI workflow comment.

**Known issues**

- `npm install` with jsdom 30 failed locally (`Cannot read properties of null (reading
  'edgesOut')`, npm 10.8.2) and jsdom 30 needs Node 22 anyway — hence 29.1.1.
- `npm audit`: advisories in existing deps (`@rolldown/plugin-babel`, `concurrently`,
  `@tailwindcss/vite`, `vite-plus`), not introduced here, not fixed. Tracked in
  `TODO-modules.md` UI-0.
- A jsdom cold start takes ~20 s on this Windows machine; the suite itself is fast.

**Checks dijalankan**

- `npm run test:ui` — pass, 1 file, 2 tests.
- `tsc --noEmit`, `vp check`, `npm run build` — pass. `composer validate` — valid.
- `composer ci:check` as a whole not run locally (needs PostgreSQL/Redis per CI env).

## Update 2026-10-19 — Design foundation: formatting + theme tokens (`TODO-modules.md` P4)

**Selesai**

- `resources/js/lib/format.ts`: `formatAmount(value, sign?)` (id-ID, 2 decimals),
  `formatQuantity` (≤ 4 decimals), `formatDate` (`dd MMM yyyy`), `formatDateTime`
  (`dd MMM yyyy HH:mm`). `—` for no value. Dates: zoneless ERP value keeps its wall
  clock; zoned timestamp → Asia/Jakarta; unrecognised shape returned unchanged.
  Test-first: `resources/js/lib/format.test.ts` (14 tests, failed on missing module).
- 19 pages migrated off per-page `Intl.NumberFormat` / `toLocaleString`. Master menu and
  BOM now split prices (`formatAmount`) from quantities/weights (`formatQuantity`);
  `formatAmount` removed from `types/master-menu.ts`.
- **Visible change:** numbers follow `id-ID` (`1.234,56`) instead of the browser locale;
  ERP dates on purchase pages show `20 Mar 2026` instead of the raw string; master menu
  prices now always show two decimals.
- Theme tokens (`resources/css/app.css`): added `success` / `warning` / `info` (light +
  dark, `--color-*` in `@theme`); fixed dark `sidebar-primary-foreground` (1:1 on
  `sidebar-primary`, masked by a `text-white dark:text-black` override in `app-logo`,
  now removed) and light `muted-foreground` (0.556 → 0.54, was 4.34:1 on `muted`).
  `esb-connections` status notice uses the `success` token.
- `resources/js/test/theme-tokens.test.ts`: dark defines every light token; 15 text /
  surface pairs meet WCAG AA in both modes (written first: 9 failures, now 32 pass).
- `docs/08-frontend-guidelines.md`: tokens, type scale, spacing/radius, formatting rules.

**Tersisa / blocked (business)**

- Brand accent colour and UI language — `[!]` in `TODO-modules.md` UI-1.
- Hard-coded palette colours left in starter-kit pages (auth, `input-error`,
  `delete-user`) → UI-6.

**Checks dijalankan**

- `npm run test:ui` — pass, 3 files, 48 tests.
- `tsc --noEmit`, `vp check`, `npm run build` — pass.
- PHP untouched; `php artisan test` not re-run (last: 830 pass, P1).
- Not checked in a browser.

## Known Unknowns / TBD

Resolved by the 2026-09-09 contract import:

- ~~Exact ESB endpoint list~~ — see `docs/esb/`
- ~~Exact ESB authentication/token lifecycle~~ — access token 1 hour, refresh token
  24 hours, single active session per credential
- ~~ESB error/status mapping~~ — 29 codes catalogued in `docs/05-api-integration.md`

Still open:

- Which base URL family and environment this project is entitled to
- Which ESB credentials this project will use, and whether a static API key is available
  instead of the login flow
- ESB rate limits and throttling behavior
- Server-side timeout budgets per operation
- Whether any ESB operations are asynchronous or eventually consistent
- Whether the ESB accepts a caller-supplied correlation/request ID header
- Idempotency support for writes (none is documented — assume writes are not idempotent)
- Whether `companyID`/`companyCode` scoping is implied by the credential
- Base URLs for the operations where the vendor declares none (four Core "Flow" pages,
  some OMS `Push_Sales_Data` and `Report` entries)
- Required ERP modules — which of the 401 operations are actually in scope
- Production infrastructure topology
- Retention period for integration logs

## Important Constraints

- No Livewire.
- No separate React project.
- No direct browser-to-ESB calls.
- No secrets in frontend or source code.
- Do not invent ESB contracts.
- Never log in to the ESB per request or per job — the ESB allows one active session per
  credential (ADR-009).
- Classify ESB outcomes on the `code` field, never on `message` or `status`.
- Never regenerate `docs/esb/*-endpoints.md` by hand; use the generator.
- Store timestamps in UTC; parse ESB timestamps as Asia/Jakarta on ingest.
- Write tests before implementing a module.
- A user holds exactly one role; nobody changes their own role.
- Audit rows are immutable — never add an update or delete path.
- Classify ESB responses on `code` alone; never on `message` or `status`.
- Reads may retry; writes never retry automatically (no idempotency key is documented).
- Never send an undocumented header to the ESB.
- Never pass the ESB's absolute prev/next URLs to the browser.
- Reading ERP data through the gateway requires `esb.invoke`, not a view permission.
- The endpoint catalog is read-only; it mirrors `docs/esb/`, which lives outside the
  database.
- ESB connections are editable (ADR-018). Credentials are encrypted at rest and
  write-only: never returned after storage, never logged, never in an audit row. Only
  `esb-connections.manage` may edit, and every change is audited.
- Connection precedence runs one way only: an active `esb_connections` row for the
  configured environment, then `ESB_BASE_URL_*`, then `config/esb.php`. Nothing writes
  back into config, and precedence is resolved in exactly one class
  (`EsbConnectionResolver`). Do not add a second place that applies it.
- `app:check-production-readiness` must keep resolving the **effective** base URL. Making
  it read `config/esb.php` alone would turn an `esb_connections` row into a way around
  the one check that stops a staging install writing to real ERP data.
- `/health` must never call the ESB.
- A zero retention window keeps logs; it must never be read as "delete everything".
- Never point a non-production install at the production ESB — the readiness check fails
  on this deliberately.
- Project documentation is excluded from the frontend formatter — do not re-enable it.

## Handoff Notes

When changing phase or completing a major feature, update this file with:

- completed items;
- current work;
- next tasks;
- known issues;
- relevant decisions.
