# Project State

> This file is the hand-off state for humans and AI coding agents. Keep it current.

## Last Updated

2026-10-22

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

## Update 2026-10-19 — Shared UI components (`TODO-modules.md` P5 / UI-2)

**Selesai** (each test-first; RED = missing module, then green)

- Components in `resources/js/components/`: `PageHeader`, `FilterBar`,
  `DateRangeField`, `DataTable`, `ListPagination`, `StatusBadge`, `DetailSummary` /
  `DetailFields` / `DetailField` (`detail-fields.tsx`), `ApprovalActions`,
  `UnconfirmedWriteNotice`, `FormActions`.
- Hooks in `resources/js/hooks/`: `useListQuery` (drops empty values, trims, keeps 0;
  paging keeps filters; reset), `useUnsavedChangesWarning` (confirm on GET visits and
  `beforeunload`; never blocks the form's own submit).
- Reference migration: `pages/purchase-orders/index.tsx` and `show.tsx` rebuilt on
  them (+ `index.test.tsx`, which caught a real bug — a removed chip / reset left the
  old value in its field because of `preserveState`; fixed). Props unchanged:
  `tests/Feature/PurchaseOrders` 49 pass.
- `resources/js/test/setup.ts` mocks Inertia's `<Head>` (needs a running app) so pages
  render in tests.
- `docs/08-frontend-guidelines.md`: table of the shared building blocks.

**Visible changes on purchase orders:** coloured status badges; date filters behind
"More filters"; active-filter chips with reset; whole row clickable; summary card
(supplier, dates, total) above the fields; lines table header sticks while scrolling.

**Tersisa**

- Tabs for `DetailLayout` need `@radix-ui/react-tabs`; combobox / popover date picker
  need `cmdk` + `@radix-ui/react-popover` — both need an ADR (combobox planned in P6).
- `UnconfirmedWriteNotice` is not wired: `HandlesDocumentWrites` only flashes a text
  toast; a banner needs structured flash data (operation, number, correlation id).
- PR / PI / SP and older pages still use hand-rolled versions → UI-6.

**Assumptions / TBD**

- `StatusBadge` tones are keyword guesses; the ERP status vocabulary is undocumented
  (only `New`, `Authorized` seen). Unknown → neutral; text always the ERP's.

**Checks dijalankan**

- `npm run test:ui` — pass, 16 files, 132 tests.
- `tsc --noEmit`, `vp check`, `npm run build` — pass.
- `php artisan test tests/Feature/PurchaseOrders` — pass, 49 tests.
- Not checked in a browser.

## Update 2026-10-19 — Shared lookups + POST reads (`TODO-modules.md` P6, ADR-025, `[ESB-019]`)

**New decision:** ADR-025 in `DECISIONS.md` — `EsbClient::postRead()` gives a POST the
vendor documents as a read the GET retry policy. Opt-in per operation; never inferred.

**Selesai** (test-first; RED = missing method / class / route)

- `EsbClient::postRead()` + `send(..., read: true)`. `EsbClientTest` +5 tests (POST
  with body, retried after a connection failure, gives up after 3, no retry on
  validation, logged as POST). No adapter uses it yet. `docs/05` retry table updated.
- `[ESB-019]` in `docs/05-api-integration.md`, transcribed from the vendor's
  `openapi.json` (downloaded 2026-10-19). `LookupSource` → `LookupClient`
  (`LookupMapper`), DTOs `LookupKind`, `LookupQuery`, `LookupOption`, `LookupPage`.
  Kinds: branches, suppliers, locations, coa-accounts (ESB-paged), cash-bank-accounts,
  statuses, transfer-products (ESB-paged, branch required). Bound in
  `AppServiceProvider`.
- `GET /lookups/{kind}` (`lookups.show`, JSON) inside `throttle:esb`; `LookupRequest`
  (esb.invoke, search ≤ 100, page ≤ 1000, branchId required for transfer products);
  unknown kind → 404 by enum binding; ESB failure → JSON via `EsbExceptionRenderer`.
- Tests: `LookupMapperTest` (8), `LookupPageTest` (4), `tests/Feature/Lookups/LookupTest`
  (13), fixture `tests/Fixtures/CoreLookup.php`.
- Frontend: `lib/lookups.ts` (`fetchLookup`, gateway message on failure),
  `components/combobox.tsx` (own WAI-ARIA combobox, no dependency → no ADR),
  `components/lookup-combobox.tsx` (aborts superseded requests). Tests: 4 + 12 + 2.
  No page uses the pickers yet; first consumers are the P7+ modules.

**Assumptions / TBD**

- Multi-value query format undocumented → no array parameter is sent; locations and
  cash/bank accounts are filtered by the row's `branchID` in the gateway.
- Whole lists (branch, supplier, location, cash-bank, status) are fetched in full on
  every picker call; no cache yet.
- COA search: leading digit → `coaNo`, else `description` (vendor has no combined
  search). `/common/status` is global; per-document mapping `TBD`.

**Tersisa**

- Product picker for purchase documents (no browse endpoint chosen).
- Moving the purchase request form's branch list onto `LookupSource`.

**Checks dijalankan**

- `php artisan test` — **pass**, 860 tests (3957 assertions).
- `pint --test` — pass. `phpstan analyse --memory-limit=1G` — same 2 pre-existing
  errors in `EsbConnectionInspector.php`.
- `npm run test:ui` — pass, 19 files, 150 tests. `tsc`, `vp check`, `npm run build` —
  pass.
- Not run against a real ESB; not checked in a browser.

## Update 2026-10-19 — Master data S1: units, product categories, sub-categories, supplier categories (`TODO-modules.md` P7)

**Selesai** (test-first; RED = missing classes/routes, then missing Inertia pages)

- `[ESB-020]`–`[ESB-023]` in `docs/05-api-integration.md`, from the vendor's
  `openapi.json`. Per module: `<X>Source` contract, `<X>Client`, `<X>Mapper`,
  canonical `<X>Summary` / `<X>` / `<X>Page` / `<X>Query`, `List<X>Request`,
  controller, routes, pages `resources/js/pages/<x>/index|show.tsx`, sidebar entry
  (Master data group). X = UnitOfMeasure (`/units`), ProductCategory
  (`/product-categories`), ProductSubCategory (`/product-sub-categories`),
  SupplierCategory (`/supplier-categories`). All read-only, `esb.invoke`.
- Shared pieces (ADR-015 — five read-only master lists now share the shape):
  `App\Http\Requests\ListReferenceDataRequest` (page, limit ≤ 100, search, flagActive),
  `App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues` (older mappers keep their
  private copies), `components/reference-data-index.tsx` /
  `reference-data-show.tsx`, `types/master-data.ts`, test base
  `tests/Feature/MasterData/ReadOnlyMasterDataTestCase.php` (access, list, filters,
  empty filters dropped, bounds, detail, 404, path-escape), fixture trait
  `tests/Fixtures/EsbEnvelope.php`.
- `LookupSource` now extends `OutboundAdapter` like every other contract.

**Assumptions / TBD**

- Unknown id → `EC03100004` → 404 (assumed, as for other Core details).
- Sub-categories: no category filter or `categoryID` documented — `[!]` in TODO §2.3.
- Category detail names its type from the vendor's legend (1–4); depreciation type ids
  have no legend and show as ids. `0` months / ids are carried as null.
- Supplier category search maps onto `supplierCategoryName` (no free-text search
  documented); the others use `indexSearch`.

**Checks dijalankan**

- `php artisan test` — **pass**, 925 tests (4397 assertions).
- `npm run test:ui` — pass, 21 files, 158 tests. `tsc`, `vp check`, `npm run build` —
  pass. `pint --test` — pass. `phpstan` — same 2 pre-existing errors.
- Not run against a real ESB; not checked in a browser.

## Update 2026-10-19 — Master data S1, part 2 (`TODO-modules.md` P8)

**Selesai** (test-first; RED = missing classes / routes / pages)

- `[ESB-024]` Suppliers (`/suppliers`): list + detail with contacts, bank accounts and
  supplier product codes from one detail call. `SupplierMapper` keeps only the last four
  digits of an account number; a test asserts the full number never reaches the page.
- `[ESB-025]` Chart of accounts (`/coa-accounts`): `GET /accounting/coa` (all active,
  unpaginated) sorted by `orderList`, searched / level-filtered / paged (50) in the
  gateway (`CoaAccountPage::fromChart`); detail with mapping rows. Route allows
  `[0-9][0-9A-Za-z._-]{0,31}` only — tested against `export`, `index`, `tax`.
- `[ESB-026]` Product detail (`/products/{id}`): `ProductSource::findProduct()`,
  `ProductDetail` + `ProductUnit`; product names in the list now link to it.
  `ProductMapper` now uses `ReadsEsbValues` (its identical private copies removed).
- `[ESB-027]` Pricelists (`/pricelists`, `/pricelists/temp`,
  `/pricelists/temp/{number}`): active prices, proposed pricelists (status 38/2),
  proposed detail with current vs proposed price. `BranchScope` for
  `applicableBranch`. The controller requires a digit in the number
  (`/pricelist/temp/export`).
- Sidebar (Master data): Suppliers, Pricelist, Chart of accounts.

**Not built (blocked)**

- `[ESB-028]` Payment method — documented only. HTTP Basic "backend" credential (not
  ADR-009's bearer token) and a response without the ESB envelope. Needs an ADR for a
  second credential + non-envelope handling, the credential from the ESB team, and a
  staging-int host for the `pos` family. `[!]` in TODO §2.8.
- Active pricelist detail: list `ID` vs detail `pricelistID` undocumented. `[!]` §2.5.

**Assumptions / TBD**

- Supplier bank numbers masked (business decision pending). COA: active only, no parent;
  `branchID` 0 = all branches. Unknown ids → `EC03100004` → 404 everywhere (assumed).

**Checks dijalankan**

- `php artisan test` — **pass**, 981 tests (4818 assertions).
- `npm run test:ui` — pass, 21 files, 158 tests. `tsc`, `vp check`, `npm run build`,
  `pint --test` — pass. `phpstan` — same 2 pre-existing errors.
- Not run against a real ESB; not checked in a browser.

## Update 2026-10-19 — Master data S2: state changes (`TODO-modules.md` P9a, `[ESB-029]`)

P9 was split: **P9a** S2 (this), **P9b** S3 create / update, **P9c** S4 import / export.

**Selesai** (test-first; RED = missing classes / routes / props)

- Shared: `App\Actions\Support\RecordWrites` + `MasterRecord` + `RecordOperation` +
  `RecordInUse` (one write per record, one attempt, audit, unconfirmed audit with
  correlation id); `App\Http\Controllers\Concerns\HandlesRecordWrites` (toast + redirect,
  up to three ERP `errors[]` reasons shown, delete → list, else → record);
  `components/record-actions.tsx` (+ `warnings`), `ReferenceDataShow` gains `actions`.
  Tests: `RecordWritesTest`, `HandlesRecordWritesTest`, base
  `tests/Feature/MasterData/MasterDataWriteTestCase.php` (permission, one write,
  audit, timeout not retried + unconfirmed, refusal reasons, lock, 404, route ids).
- Modules (each `<X>Writes` action + `<X>WriteController` + routes + page actions):
  units (delete / restore; `hardDelete` audited), product categories and
  sub-categories (delete / deactivate / restore), supplier categories (delete with the
  `check-delete` pre-check *outside* the lock / restore), suppliers and COA accounts
  (deactivate = vendor DELETE / restore; COA warns about sub-accounts), products
  (delete / restore).
- Temporary pricelists: authorize / reject / delete on `DocumentWrites` +
  `ApprovalActions`; decisions only for status 38. `HandlesDocumentWrites::decisionOutcome`
  takes `authorizeConsumesDocument` (authorize removes the proposal → land on the list).
- Permissions (Super Admin only): `units.manage`, `product-categories.manage`,
  `product-sub-categories.manage`, `supplier-categories.manage`, `suppliers.manage`,
  `coa.manage`, `products.manage`, `pricelists.approve`, `pricelists.manage` — PHP enum,
  `permissions.ts`, `docs/06`. New audit actions in `AuditAction`.

**Tersisa / blocked**

- Temporary-product authorize / reject (`[!]` §2.1). P9b (create / update) and P9c.

**Checks dijalankan**

- `php artisan test` — **pass**, 1108 passed, 25 skipped (operations a module does not
  offer, from the shared data provider), 5305 assertions.
- `npm run test:ui` — pass, 22 files, 169 tests. `tsc`, `vp check`, `npm run build`,
  `pint --test` — pass. `phpstan` — same 2 pre-existing errors.
- Not run against a real ESB; not checked in a browser.

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

## Update 2026-10-19 — Master data S3 batch 1: create / update (`TODO-modules.md` P9b, `[ESB-030]`)

P9 is now P9a (S2), **P9b** (S3 batch 1, this), P9c (S3 batch 2: supplier, COA,
product, pricelist), P9d (S4).

**Selesai** (test-first; RED = missing trait / methods / routes / pages)

- Lookups `[ESB-019]` gain `metrics`, `category-types`, `taxes` (active only, rate in
  `meta`).
- Shared: `RecordOperation::Create|Update`; `MasterRecord::createLock()` (per person);
  `RecordWrites::create()` (returns and audits the new id when the ERP gives one);
  `HandlesRecordForms::recordSaveOutcome()` (ERP field errors → form fields via a
  field map, else `form`; create → record or list, update → record; unconfirmed →
  warning with reference). Frontend `components/record-form.tsx` (`RecordForm`,
  `RecordFormField`, unsaved-changes warning); `RecordActions` gains `editUrl`;
  `ReferenceDataIndex` gains `actions` ("New …" button). Test setup stubs
  `ResizeObserver` for Radix Checkbox.
- Modules: units (metric + tax pickers), product sub-categories, supplier categories
  (create returns the id), product categories (type + seven COA pickers, every field
  sent). Each: `<X>Draft`, mapper `requestFrom()`, contract + client create/update,
  `Save<X>Request`, `<X>FormController`, routes, `pages/<module>/form.tsx`.
- Tests: `tests/Feature/MasterData/MasterDataFormTestCase.php` (permission, empty
  create page, edit prefill, 404, one POST/PUT with the vendor body + audit, invalid
  input never sent, ERP field errors on the form, timed-out create not retried +
  unconfirmed audit) + one per module; `HandlesRecordFormsTest`; `RecordWritesTest`
  additions; page tests per form.

**Tersisa / TBD**

- `TBD`: unit update without `refTaxID` — clears the reference or not; sub-category
  `PUT` without `flagActive` — resets it or not. P9c, P9d.

**Checks dijalankan**

- `php artisan test` — **pass**, 1163 passed, 25 skipped.
- `npm run test:ui` — pass, 27 files, 183 tests. `vp check`, `npm run build`,
  `pint` — pass. `phpstan` — same 2 pre-existing errors.
- Not run against a real ESB; not checked in a browser.

## Update 2026-10-19 — Master data S3 batch 2 (`TODO-modules.md` P9c, `[ESB-031]`)

**Selesai** (test-first; RED = missing kinds / routes / classes / pages)

- Lookups: `product-categories` (inactive dropped), `product-sub-categories`, `units`,
  `currencies`, `product-details` (paged, searched by name upstream).
- Shared: `App\Actions\Support\CreatedRecord` (id + `pendingApproval`); `RecordWrites`
  audits `pending_approval`; `HandlesRecordForms` toasts "waiting for approval" and lands
  on the list; `RecordOperation::Move`.
- Products: `ProductDraft` / `ProductUnitDraft`, `ProductMapper::requestFrom()` (carries
  over BOM, CoreTax id, custom fields, unit menu / cubication from the current
  product) and `createdFrom()` (`isTemp`); `ProductDetail` gains `coretaxCodeId`,
  `customFields`, `ProductUnit` gains `menuId`, `cubication`. `SaveProductRequest`
  (exactly one base unit, unique units), `ProductFormController` (reads first; a unit id
  must be the product's own), `pages/products/form.tsx`, "New product" / "Edit".
- Pricelists: `PricelistDraft(+Line)`, `PricelistWrites` (lock shared with
  `TempPricelistDocument`), `SavePricelistRequest`, `PricelistFormController`,
  `pages/pricelists/form.tsx` (supplier, currency, dates, branches or all, price lines);
  `TempPricelist.currencyId`, `BranchScope.ids`. Create → proposals list.
- COA: `PATCH /coa-accounts/{coaNo}/move` → `PATCH /accounting/coa/reorder`;
  `components/move-actions.tsx` on the COA detail.

**Tersisa / blocked**

- `[!]` Supplier create / update (greeting, payment method, masked banks), COA create /
  update (numbering, parent, currency id), active-pricelist edit (id ambiguity).
- `TBD`: product `pushToGoApp`; "exactly one base unit" is an assumption. P9d (S4).

**Checks dijalankan**

- `php artisan test` — **pass**, 1197 passed, 25 skipped.
- `npm run test:ui` — pass, 30 files, 191 tests. `vp check`, `tsc`, `npm run build`,
  `pint` — pass. `phpstan` — same 2 pre-existing errors.
- Not run against a real ESB; not checked in a browser.

## Update 2026-10-19 — Master data S4 (`TODO-modules.md` P9d, `[ESB-032]`)

**Selesai** (test-first; RED = missing methods / routes / pages / modules)

- `EsbClient::upload(..., method:)` and `uploadFiles()` (several files, one field, one
  attempt).
- Imports: `MasterDataImport` enum (9 kinds: label, extensions, queued),
  `MasterDataImportSource` / `MasterDataImportClient`, `ImportMasterData` (lock
  `master-data-import:{kind}:{user}`, audit with SHA-256 and COA counts),
  `ImportMasterDataRequest` (permission per kind), `MasterDataImportController`,
  `pages/imports/create.tsx`, `components/import-links.tsx` on the lists. The Excel checks
  were extracted to `Http\Requests\Concerns\ValidatesExcelUpload` (second consumer, ADR-015);
  the purchase request import uses it unchanged.
- Pricelist helper prices: `PricelistSource::lastPrices()` / `purchasePrices()`,
  `PurchasePrice`, JSON `GET /pricelists/last-prices` and `/pricelists/purchase-history`,
  `lib/pricelist-prices.ts`, an "Active price" column and `PurchaseHistoryDialog` in the
  pricelist form.
- Supplier attachments: `Supplier.attachmentCount`, `SupplierWrites::attach()` /
  `deleteAttachments()` (supplier write lock, `RecordOperation::Attach|Detach`),
  `AttachSupplierFilesRequest`, `SupplierAttachmentController`,
  `components/attachment-actions.tsx` on the detail.

**Tersisa / blocked**

- `[!]` Every ERP export (file URL in the ERP's storage; proxy vs gateway-made export
  needs an ADR and ESB answers). Attachment files are counted only, for the same reason.
- `TBD`: where users get the ERP import templates; attachment type / size limits.

**Checks dijalankan**

- `php artisan test` — **pass**, 1235 passed, 25 skipped.
- `npm run test:ui` — pass, 34 files, 204 tests. `vp check`, `tsc`, `npm run build`,
  `pint` — pass. `phpstan` — same 2 pre-existing errors.
- Not run against a real ESB; not checked in a browser.

## Update 2026-10-20 — Inventory S1 (`TODO-modules.md` P10, `[ESB-033]`, ADR-026)

**Selesai** (test-first; RED = missing classes / routes / pages)

- ADR-026: one canonical shape for the five inventory documents. `InventoryKind` (label,
  documented statuses, branch filter, free-text search), `InventoryDocumentSummary`,
  `InventoryDocument` (+ `withOutstanding()`, `withStockUsed()`), `InventoryLine`,
  `InventoryParty`, `InventoryLinkedDocument`, `InventoryDocumentQuery` / `Page`.
- `InventoryDocumentSource` / `InventoryDocumentClient` / `InventoryDocumentMapper` (field
  names per kind). Extra reads: goods delivery outstanding qty (only with a reference),
  goods receipt and simple transfer stock check; their failure leaves the field empty.
- `ListInventoryDocumentsRequest`, `InventoryDocumentController` (kind as route default;
  number must contain a digit), routes `/{kind}` and `/{kind}/{number}` for
  goods-transfer-requests, goods-deliveries, goods-receipts, item-journals,
  simple-transfers. Pages `inventory/index.tsx` (search by number or free text, status,
  branch, date range) and `inventory/show.tsx` (only the quantity columns the lines
  have, stock-used warning, linked documents); `lib/inventory.ts`; "Inventory" sidebar
  group.
- Tests: `InventoryDocumentMapperTest`, `tests/Feature/Inventory/InventoryDocumentTestCase.php`
  + one per kind, page tests; fixture `tests/Fixtures/CoreInventory.php`.
- `phpunit.xml` sets `memory_limit=1G`: the whole suite crashed ("Premature end of PHP
  process") at the 128M default in one process; Unit and Feature passed separately.

**Tersisa / blocked**

- Not read: GTR `POST /detail` (S3), item journal approval flow, receipt expirations /
  assets, delivery multi-address. `[!]` refNum validity for delivery / receipt creates.
  Next: P11 (Inventory S2–S4).

**Checks dijalankan**

- `php artisan test` — **pass**, 1287 passed, 25 skipped.
- `npm run test:ui` — pass, 36 files, 210 tests. `vp check`, `tsc`, `npm run build`,
  `pint` — pass. `phpstan` — same 2 pre-existing errors.
- Not run against a real ESB; not checked in a browser.

## Update 2026-10-20 — Inventory S2–S4 (`TODO-modules.md` P11, `[ESB-034]`–`[ESB-036]`)

**Selesai** (test-first; RED = missing routes / permissions / classes / pages)

- S2 `[ESB-034]`: authorize / reject / delete for all five inventory documents, finish /
  reopen for goods transfer requests. `App\Actions\Inventory\{DecideInventoryDocument,
  DeleteInventoryDocument, CompleteGoodsTransferRequest, InventoryDocuments}`,
  `DocumentWrites::transition()` (+ audited detail), `InventoryDocumentWriteController`,
  `DecideInventoryDocumentRequest`, `HandlesDocumentWrites::transitionOutcome()`.
  Ten permissions `<kind>.approve` / `.manage` (+ `App\Authorization\InventoryPermissions`,
  `permissions.ts`), Super Admin only; audit actions per document. Detail reads
  `canEdit` / `canDelete` (transfer requests). UI: `ApprovalActions` (optional reject
  reason), new `ConfirmAction`; transfer request buttons follow documented statuses.
- S3 `[ESB-035]`: create / edit transfer requests, item journals, simple transfers.
  `InventoryDraft` / `InventoryDraftLine` / `InventoryCarryOver` (on `InventoryDocument`),
  `InventoryLine::purposeId`, `InventoryKind::editable()`, client bodies per kind,
  `InventoryDocumentForms` (GuardedEsbWrite; create lock per person + kind),
  `SaveInventoryDocumentRequest`, `InventoryDocumentFormController` (HandlesRecordForms;
  edit refused for asset documents / `canEdit: false`). Lookup `purposes` (`GET /purpose`).
  Page `inventory/form.tsx`; New / Edit links. Create routes registered before the detail
  routes (`/{kind}/create`).
- S4 `[ESB-036]`: attachments (`InventoryAttachments`, `AttachInventoryFilesRequest`,
  `InventoryAttachmentController`, `Requests\Concerns\ForwardsAttachments` now shared with
  supplier attachments; `AttachmentActions` gained optional delete, accept list and a
  blocked reason). Simple transfer import = `MasterDataImport::SimpleTransfers` on the
  shared import screen.

**Tersisa / blocked**

- `[!]` (vendor): goods delivery / goods receipt create-from-reference (refNum validity,
  expirations, assets, multi-address), receipt asset images; `existing` field format of
  attachment uploads; number in GTR / ST create responses. Questions in
  `TODO-modules.md` §9. Next: P12 (Finance: Disbursement).

**Assumptions / TBD**

- Finish / reopen need `goods-transfer-requests.manage` (`[ESB-034]`, docs/06).
- Uploads with an "existing" list (GD, IJ, ST) are allowed only while there are no
  attachments; the GTR upload is taken to add (`[ESB-036]`).
- New transfer requests send `transferDetailAssets: []` and `categoryTypeID: 1`; item
  journal attachment types PDF/JPEG/PNG; 5 MB per file except simple transfers (2 MB).
- An S1 test changed on purpose: `GET /{kind}/create` is now the form (403 without
  `.manage`) for the three editable kinds, still never an ESB read.

**Checks dijalankan**

- `php artisan test` — **pass**, 1462 passed, 26 skipped (25 before + the item journal "no assets" case).
- `npm run test:ui` — pass, 38 files, 236 tests. `vp check`, `tsc --noEmit`, `npm run build`,
  `pint` — pass. `phpstan` — same 2 pre-existing errors in `app/Services/EsbConnectionInspector.php`
  (file untouched).
- Not run against a real ESB; not checked in a browser.

## Update 2026-10-20 — Finance: Disbursement S1–S4 (`TODO-modules.md` P12, `[ESB-037]`–`[ESB-040]`)

**Selesai** (test-first; RED = missing classes / routes / permissions, then missing pages)

- S1 `[ESB-037]`: `DisbursementSource` / `DisbursementClient` / `DisbursementMapper`,
  `Domain\Canonical\Disbursement*` (summary, detail, line, linked document, query, page,
  payment method). `DisbursementController` + `ListDisbursementsRequest`; pages
  `disbursements/{index,show}.tsx`. The bank account number is masked in the adapter:
  `mask()` moved from `SupplierMapper` into `ReadsEsbValues` (two consumers now); the whole
  number lives only in `DisbursementCarryOver`, which is never serialized.
- S2 `[ESB-038]`: `Actions\Disbursements\{Disbursements, DecideDisbursement,
  DeleteDisbursement}` on `DocumentWrites`, `DisbursementWriteController`,
  `DecideDisbursementRequest` (optional reason, 500 max). Permissions
  `disbursements.approve` / `.manage` (Super Admin only), audit actions `disbursement.*`.
- S3 `[ESB-039]`: `DisbursementDraft(Line)`, `DisbursementForms` (GuardedEsbWrite; create lock
  per person), `SaveDisbursementRequest` (cash vs credit rules), `DisbursementFormController`
  (HandlesRecordForms). Edit and update first read the document, then `PUT .../validate`;
  a refusal or no answer lands on the detail with a warning, no update sent. Page
  `disbursements/form.tsx`.
- S4 `[ESB-040]`: `DisbursementAttachments` + `AttachDisbursementFilesRequest` +
  `DisbursementAttachmentController` (attach only while there are none);
  `MasterDataImport::Disbursements` on the shared import screen;
  `DisbursementFormLinesController` + `ReadDisbursementFormRequest`: the ERP's Excel form is
  read and its lines flashed back (`disbursementLines`) into the form — no lock, no audit.
- Sidebar group "Finance".

**Tersisa / blocked**

- `[!]` vendor/business: export (same as `[ESB-032]`), cost center / project pickers (no
  lookup), bank details on a new disbursement, segregation of duties (creator ≠ approver),
  whether create returns `disbursementNum` (example `{}`).

**Assumptions / TBD**

- `isOverDue` sent as the string `true`, only when filtering overdue (`[ESB-037]`).
- Decide offered for New / Waiting For Approval, delete for the four documented statuses;
  edit offered always and decided by the ERP's validate (`[ESB-038]`, `[ESB-039]`).
- Line amounts may be negative (vendor gives no sign rule); attachments 2 MB each, 5 files.

## Update 2026-10-20 — Accounting S1 (`TODO-modules.md` P13, `[ESB-041]`–`[ESB-043]`)

**Selesai** (test-first; RED = missing classes / routes, then missing pages)

- General journal `[ESB-041]`: `GeneralJournalSource` / `GeneralJournalClient`,
  `GeneralJournalController` (`ListGeneralJournalsRequest`, `ListJournalLinesRequest`),
  pages `general-journals/{index,show}.tsx`. Detail = the vendor's paged lines (50 per page).
- General ledger `[ESB-042]`: `GeneralLedgerSource` / `GeneralLedgerClient` via `postRead()`
  (ADR-025), `GeneralLedgerController` (entries + summary; the ERP's validation refusal is
  shown on the page as `error`), `ListLedgerEntriesRequest`, `ListLedgerSummaryRequest`,
  `Requests\Accounting\Concerns\ValidatesLedgerPeriod`. `LedgerEntryPage::fromLedger()` pages
  the vendor's unpaged list. Pages `general-ledger/{index,summary}.tsx`.
- Memorial journal `[ESB-043]`: `MemorialJournalSource` / `MemorialJournalClient`,
  `MemorialJournalController`, `ListMemorialJournalsRequest`, pages
  `memorial-journals/{index,show}.tsx`.
- Shared: `JournalMapper`, canonical `JournalLine`, component `JournalLinesTable`. Sidebar
  group "Accounting".

**Tersisa / blocked**

- `[!]` vendor: array query format (branch / account / cost center / project filters of the
  general journal), memorial journal `/access` parameter name; exports.
- Business: general ledger export (gateway CSV vs ERP report export).
- Next: P14 (Memorial Journal S2–S4).

**Assumptions / TBD**

- Ledger period required with the account, at most 366 days (gateway bound) (`[ESB-042]`).
- No balance shown: the vendor gives no opening balance.
- 0 on the other side of a journal line is shown as "—" (display only).

**Checks dijalankan (P12 + P13)**

- `php artisan test` — **pass**, 1544 passed, 26 skipped (unchanged skips); 1570 tests, 8374 assertions.
- `npm run test:ui` — pass, 44 files, 257 tests. `vp check`, `tsc --noEmit`, `npm run build`,
  `pint --test` — pass. `phpstan` — the same 2 pre-existing errors in
  `app/Services/EsbConnectionInspector.php` (file untouched).
- Frontend page tests were written alongside the pages rather than strictly before them;
  the backend followed RED → GREEN.
- Not run against a real ESB; not checked in a browser.

## Update 2026-10-20 — Memorial Journal S2–S4 (`TODO-modules.md` P14, `[ESB-044]`–`[ESB-046]`)

**Selesai** (test-first; RED = missing permissions / routes, then the missing form page)

- S2 `[ESB-044]`: `Actions\MemorialJournals\{MemorialJournals, DecideMemorialJournal,
  DeleteMemorialJournal}` on `DocumentWrites` (lock `memorial-journal-write:{number}`),
  `MemorialJournalWriteController`, `DecideMemorialJournalRequest`. Permissions
  `memorial-journals.approve` / `.manage` (Super Admin only), audit actions
  `memorial-journal.*`.
- S3 `[ESB-045]`: `MemorialJournalDraft` / `MemorialJournalDraftLine` /
  `MemorialJournalCarryOver` (each line's cost center + project by line id, never
  serialized), `MemorialJournalSource::createJournal()` / `updateJournal()`,
  `MemorialJournalForms` (create lock `memorial-journal-create:{userId}`),
  `SaveMemorialJournalRequest`, `MemorialJournalFormController`, page
  `memorial-journals/form.tsx` (totals + "differ by" hint, no block). `JournalLine` gained
  `currencyId`.
- S4 `[ESB-046]`: `attachImage()` (multipart `file`), `MemorialJournalAttachments`,
  `AttachMemorialJournalImageRequest`, `MemorialJournalAttachmentController`
  (`POST /memorial-journals/{number}/images`). `ForwardsAttachments::attachment()` (single
  `file`); `AttachmentActions` gained `single`.
- Show page: Edit / Authorize / Reject / Delete / image upload by permission and status;
  index: "New memorial journal".

**Tersisa / blocked**

- `[!]` vendor: `form-upload` / `upload` (documented as a JSON row body, no file field;
  `typeID` undocumented), export (as `[ESB-032]`), cost center / project pickers (no lookup).
- Next: P15 (POS: Menu Category, Promotion).

**Assumptions / TBD**

- A line with both debit and credit is refused by the gateway (vendor silent) (`[ESB-045]`).
- Rate > 0 (vendor "min 0"), at most 500 lines (gateway bound).
- Reject reason required by the page only while Waiting For Approval; the server leaves it
  to the ERP (`[ESB-044]`).
- `asset-images` adds rather than replaces; jpg/jpeg/png, 2 MB, one per call (`[ESB-046]`).

**Checks dijalankan (P14)**

- `php artisan test` — **pass**, 1578 passed, 26 skipped (unchanged skips); 1604 tests, 8607
  assertions. (The JSON reporter says "passed"; the shell exit code was 1, as in P12/P13 —
  not investigated.)
- `npm run test:ui` — pass, 45 files, 267 tests. `vp check`, `tsc --noEmit`, `npm run build`,
  `pint --test` — pass. `phpstan` — the same 2 pre-existing errors in
  `app/Services/EsbConnectionInspector.php` (file untouched).
- Not run against a real ESB; not checked in a browser.

## Update 2026-10-20 — POS: Menu Category + Promotion (`TODO-modules.md` P15, `[ESB-047]`–`[ESB-050]`, ADR-027)

Working tree note: P14 was not yet committed when P15 started, so both phases' changes are
in the working tree together.

**Selesai** (test-first; RED = missing permissions / routes / classes, then missing pages)

- Infrastructure: `ErrorClassifier` reads OMS field errors -- `EC0110`'s JSON object in
  `message` (or the plain sentence, kept without a field) and `data` keyed by field
  (`EsbFieldError::forField()`, `ErrorCode::OMS_VALIDATION`). ADR-027:
  `EsbClient::getBareList()` accepts an opted-in GET whose documented success is a bare
  JSON array. `EsbClient` body docblocks allow a list (OMS array bodies).
- Menu category `[ESB-047]`/`[ESB-048]`: `MenuCategorySource` / `MenuCategoryClient`
  (`findCategory()` reads the hinted page, then the others, `MAX_PAGES` 20),
  `MenuCategoryMapper`, canonical `MenuCategory*`, `MenuCategoryWrites` (RecordWrites),
  `ListMenuCategoriesRequest`, `SaveMenuCategoryRequest`, `MenuCategoryController`,
  `MenuCategoryFormController` (refuses an update that drops a current detail or names
  another category's), pages `menu-categories/{index,show,form}.tsx`.
- Promotion `[ESB-049]`/`[ESB-050]`: `PromotionSource` / `PromotionClient`, `PromotionMapper`,
  canonical `Promotion`, `PromotionPage` (gateway paging), `PromotionQuery`, `PromotionType`
  (fields + rules per type), `PromotionDraft`, `PromotionWrites`, `ListPromotionsRequest`,
  `CreatePromotionRequest`, `PromotionController`, `PromotionFormController`, pages
  `promotions/{index,form}.tsx`.
- Lookups: `menu-categories`, `menu-category-details`, `pos-menus` (FnB backend, paged by
  the ESB, searched per page in the gateway). Component `LookupChips` (multi-pick).
- Permissions `menu-categories.manage`, `promotions.manage` (Super Admin only); audit
  `menu-category.*`, `promotion.*`. Sidebar group "POS".

**Tersisa / blocked**

- `[!]` business: menu source (Core vs OMS) before Menu S3 (§6.2); Notes (§6.4) not built.
- `[!]` vendor: promotion update / deactivate; menu category fields absent from the list
  (ESO aliases, image, theme option); lookups for ESB Order payment methods, visit
  purposes, employee groups.
- Next: P16 (UI-3 navigation + UI-4 dashboard).

**Assumptions / TBD**

- Promotion booleans sent as "Yes"/"No", `applyTo` as its label, `visitPurposeID` as
  integers (`[ESB-050]`).
- Menu category update omits fields the list does not return; a detail cannot be
  removed from the gateway (`[ESB-048]`).
- Promotion list's 401 (`code: 0`) is classified as unknown, not authentication
  (ADR-027).
- `CreatePromotionRequest` is one FormRequest choosing rules per type from
  `PromotionType`, not five classes as TODO §6.5 first suggested.

**Checks dijalankan (P15)**

- `php artisan test` — **pass**, 1625 passed, 26 skipped (unchanged skips); 1651 tests, 8964
  assertions. (JSON reporter "passed"; shell exit code 1, as in P12–P14 — not
  investigated.)
- `npm run test:ui` — pass, 48 files, 277 tests. `vp check`, `tsc --noEmit`, `npm run build`,
  `pint --test` — pass. `phpstan` — only the 2 pre-existing errors in
  `app/Services/EsbConnectionInspector.php` (4 new ones found during P15 were fixed).
- Frontend page tests were written just before their pages (they failed on the missing
  module), not run RED separately against a stub.
- Not run against a real ESB / OMS; not checked in a browser.


## Update 2026-10-21 — UI-3 navigation + UI-4 dashboard (`TODO-modules.md` P16)

**Selesai**

- Menu in one place: `resources/js/lib/navigation.ts` (`mainNavigation`,
  `visibleNavigation`, `flattenNavigation`, `documentTargets`), used by the sidebar, the
  command palette and the breadcrumbs. New "System" group (integration logs, endpoint
  catalog, ESB connections, users); every group has an icon.
- Sidebar groups (`nav-main.tsx`) remember open / closed in `localStorage`
  (`nav-groups`, wrapped in try/catch) and always open when the current page — including
  a detail / edit page — lives inside them.
- Command palette (`components/command-palette.tsx`): Ctrl+K / ⌘K or the header button;
  jumps to any page the person may open, or opens a typed document number in each module
  that reads by number (12 targets, `esb.invoke`). Only one-segment numbers with a digit
  (`[A-Za-z0-9][A-Za-z0-9._-]*`) are put in a URL. Built on the existing Radix Dialog with
  its own combobox/listbox — no new dependency, no ADR.
- Breadcrumbs (`lib/breadcrumbs.ts`, wired in `app-sidebar-header.tsx`): derived from the
  URL and the menu for every page inside it (module → number → New / Edit / Import;
  `temp` → "Proposed prices"). The static `.layout = { breadcrumbs }` blocks on 52 pages
  were removed (now dead). Pages outside the menu keep their own (settings) or set them
  with `setLayoutProps` (`imports/create.tsx`, trail from its `listUrl`).
- Dashboard: deferred `pendingApprovals` prop (`App\Services\PendingApprovals`,
  `Inertia::defer`, group `approvals`): one 1-row read per module with the documented
  status 38 filter — disbursements, memorial journals, item journals, proposed prices —
  only for modules the person approves and only with `esb.invoke`; a failing read gives
  `count: null` ("Unknown"). `IntegrationMetrics::byFamily()` adds `lastOutcome` /
  `lastCallAt` per family ("ESB connection status" card). Shortcuts card (New purchase
  request / GTR / disbursement / memorial journal, integration logs, ESB connections),
  gated by permission. Cards are `<section aria-labelledby>` regions.
- Test setup: `matchMedia` stub in `resources/js/test/setup.ts` (sidebar in tests).

**Tersisa**

- Purchasing documents (PR/PO/PI/SP), GTR/GD/GR/ST are not in "Waiting for approval":
  their lists take no status filter or document no waiting status (`[!]` vendor, §9
  `/common/status/mapping`).
- No grafik on the dashboard (none needed yet; use the `dataviz` skill when added).
- Next: P17 (UI-5 responsive/a11y + UI-6 migration of old pages).

**Known issues**

- `phpstan` — still only the 2 pre-existing errors in
  `app/Services/EsbConnectionInspector.php` (untouched).
- A detail page's crumb shows the raw path segment, so id-routed pages (units, products,
  categories) show the numeric id, not the name.

**Assumptions / TBD**

- "Waiting for approval" = status 38 only; New (1) documents that can also be authorized
  are not counted.
- Command palette does not check a number exists; the module's page answers 404.

**Checks dijalankan (P16)**

- `php artisan test` — pass, 1631 passed, 26 skipped (1657 tests, 9103 assertions).
- `npm run test:ui` — pass, 53 files, 307 tests. `vp check`, `tsc --noEmit`,
  `npm run build`, `pint --test` — pass. `phpstan` — 2 pre-existing errors only.
- RED run first for all new PHP and UI tests (failed on missing props / modules).
- Not checked in a browser; pending-approval counts not run against a real ESB.

## Update 2026-10-21 — UI-5 responsive / accessibility + UI-6 page migration (`TODO-modules.md` P17)

**Selesai**

- `DataTable`: below 640px each row is a card (header `max-sm:sr-only`, cells show
  `data-label` from the header or the new column `label`, explicit table roles).
- Touch targets (`pointer-coarse:`): `Button` sizes ≥ 44px, `Input` 44px tall,
  `ChipRemoveButton` (new, used by `FilterBar` and `LookupChips`) widens its hit area with a
  pseudo-element.
- `Heading` takes `level`; `PageHeader` renders `h1`. Global focus outline (`--foreground`,
  not the light `--ring`, which is under 3:1 on white) for links / `[tabindex]` in
  `resources/css/app.css`. Icon buttons in `app-header.tsx` named; Users role select named
  per row.
- Starter-kit palette colours replaced by tokens (`input-error`, `delete-user`, auth status
  messages → `text-success`, `user-info`, `nav-footer`, `appearance-tabs`, `app-header`,
  `auth-split-layout`).
- `resources/js/test/accessibility-audit.test.tsx`: whole-frontend checks (icon buttons
  named, every app page has `PageHeader`/`RecordForm`/`ReferenceData*`, no raw palette
  colours outside `components/ui`, touch target classes).
- Shared: `hooks/use-filter-form.ts` (fields from the applied filters, chips, remove,
  reset, `keep`), `components/select-filter-field.tsx` + `active-status-field.tsx`,
  `lib/pagination.ts` (`fromPaginator` for Laravel paginators).
- UI-6 list pages rebuilt on PageHeader / FilterBar / DataTable / ListPagination /
  StatusBadge: purchase requests, purchase invoices, simple purchases, products, menus,
  master menus, bills of material, purposes, document templates, document template imports,
  sales pushes, integration logs, endpoints, users. Show / form / import pages of these
  modules, `esb-connections` and `dashboard` moved to `PageHeader`
  (`pages/shared-list-pages.test.tsx` pins the list pages).
- Test setup: jsdom stubs for `hasPointerCapture` / `scrollIntoView` (Radix Select).

**Tersisa**

- Show pages of PR / PI / SP keep their page-local decision and delete controls (not
  `ApprovalActions`); their line tables are still `ui/table` (no card layout).
- `DetailLayout` tabs and the structured unconfirmed-write banner (UI-2 `[~]`) unchanged.
- Not checked in a real browser, on a touch device or with a screen reader.

**Known issues**

- `phpstan` — still only the 2 pre-existing errors in `app/Services/EsbConnectionInspector.php`.

**Assumptions / TBD**

- Visible changes accepted as "no behaviour change": secondary filters moved behind "More
  filters" with chips; list rows click through to the detail; `Active`/`Inactive` and ERP
  statuses coloured by `StatusBadge`. Gateway-owned states (sales push, log outcome) keep
  their own `Badge` variants.

**Checks dijalankan (P17)**

- `php artisan test` — pass, 1631 passed, 26 skipped (1657 tests, 9103 assertions).
- `npm run test:ui` — pass, 60 files, 346 tests. `vp check`, `tsc --noEmit`,
  `npm run build`, `pint --test` — pass. `phpstan` — 2 pre-existing errors only.
- RED first for the new component / hook / audit / page tests.

## Update 2026-10-22 — Unblocked leftovers: unconfirmed-write banner, menu extras, npm audit (`TODO-modules.md` P18)

**Selesai**

- Checklist tidy in `TODO-modules.md`: §1 "UI-1 dan UI-2 dulu" and §2.7 `coa.manage`
  ticked (both already true in code); a merged §9 line split.
- `UnconfirmedWriteNotice` wired. `HandlesDocumentWrites::flashUnconfirmedWrite()` flashes
  `unconfirmedWrite` (`action`, `reference`) instead of a warning toast when an unconfirmed
  decision / transition / upload lands on the detail page;
  `components/unconfirmed-write-banner.tsx` renders it in both app layouts (dismissible,
  reappears for a newer reference). Unconfirmed delete and consuming authorize (temp
  pricelist) still toast — they land on the list. Module feature tests now assert
  `inertia.flash_data.unconfirmedWrite.reference`.
- Menu extras read-only: `GET /master-menus/extras` (`MasterMenuController::extras`,
  `pages/master-menus/extras.tsx`, nav "Menu extras" under POS). Flattens the extras of one
  Get Menu page (`[ESB-013]`); paging and the `menuCode` filter are the menu list's.
- npm, lockfile only: `concurrently` 10.0.6 (`shell-quote` 1.12.0), `vite` 8.3.4,
  `source-map-js` 1.2.2. Vite 8.3.5 skipped on purpose (published that day; used
  `npm update vite --before=2026-10-09`).

**Tersisa**

- `tinypool` critical advisory via `vite-plus` 0.3.0 — fix needs `vite-plus` 1.x (major,
  ADR-024 revision). `@babel/core` 8.0.0-rc.4 (low) via `@rolldown/plugin-babel`.
- Purchase Request decisions still use a toast for unconfirmed writes (not migrated to
  `DocumentWrites`; audit-shape decision pending, `TODO-modules.md` §1).
- `DetailLayout` tabs (needs `@radix-ui/react-tabs` ADR). Playwright smoke test (optional).

**Known issues**

- Blocked (local environment): npm 10.8.2 (Node 20.19) crashes with
  `Cannot read properties of null (reading 'edgesOut')` in arborist `#loadPeerSet` on
  `npm install <pkg>` and `npm audit fix`; `npm update` works. Upgrading local Node to 22
  is the expected fix — not done (developer machine).
- `phpstan` — pre-existing errors only (see P17 entry).

**Assumptions / TBD**

- Banner wording is the component's ("check the status below"), so it is only flashed for
  redirects to a detail page.
- Menu extras page is paged by menu, not by extra: there is no extra endpoint (`[!]` in
  `TODO-modules.md` §6.3).

**Checks dijalankan (P18)**

- RED first: `HandlesDocumentWritesTest` (3 new, 1 changed), `MasterMenuExtrasTest`,
  `unconfirmed-write-banner.test.tsx`, `master-menus/extras.test.tsx`.
- `npm run test:ui` — pass, 62 files, 353 tests (after the vite 8.3.4 update).
  `vp check`, `tsc --noEmit`, `npm run build` — pass.
- `php artisan test` — pass, 1639 passed, 26 skipped (1665 tests, 9160 assertions). `pint --test` — pass. `phpstan` — 2 pre-existing errors only (`EsbConnectionInspector.php`).

## Update 2026-10-22 — vite-plus 1.1.0 on Node 22 (ADR-028), Radix Tabs + `DetailTabs` (ADR-029) (`TODO-modules.md` P19)

**Selesai**

- New ADRs: **ADR-028** (vite-plus 1.1.0, `vite` aliased to
  `@voidzero-dev/vite-plus-core@1.1.0` as direct dependency and in `overrides`, `vitest`
  overridden to 5.0.3, Node `^22.18` minimum; amends ADR-024) and **ADR-029**
  (`@radix-ui/react-tabs` ^1.1.22 for detail page sections).
- Local Node is 22.23.4 / npm 10.9.9 (developer upgrade). `tinypool` 2.2.0, `oxfmt`
  0.72.0, `@babel/core` 8.0.7: npm audit critical / high / low are gone.
- `components/ui/tabs.tsx`, `components/detail-tabs.tsx` (`DetailTabs`: named tab list,
  optional count per tab). `pages/master-menus/show.tsx`: General above, Prices / Package /
  Extras / Related menus as tabs. Same for `bills-of-material/show` (Materials / Other
  costs / Replacements) and `suppliers/show` (Contacts / Bank accounts / Supplier product
  codes).
- `jsdom` 30.1.2 and `@testing-library/jest-dom` 7.0.1 (exact pins; noted in ADR-028).
- Lockfile: only `vite`, `vite-plus`, `@radix-ui/react-tabs` (+ its six exact-pinned
  internals) and transitive fixes changed among direct deps; normalized by npm 10.9.

**Tersisa**

- 5 moderate advisories: Vitest < 5.0.5 servers misapply `server.fs.deny` (UI / browser
  mode only — not used). Fix is vite-plus 1.3.0; raise it after 2026-10-30.
- Document detail pages (PO, inventory, …) stay without tabs: one line table each.

**Known issues**

- Fixed in this phase: `config/inertia.php` hard-coded SSR on, so a local
  `bootstrap/ssr/` (from `npm run build:ssr`) made every Inertia render POST to the SSR
  server inside PHPUnit (62 failures). Now `env('INERTIA_SSR_ENABLED', true)`, `false` in
  `phpunit.xml`, pinned by `tests/Feature/Hardening/InertiaSsrInTestsTest.php`.
- npm 10.9 still crashes (`edgesOut`, arborist `#loadPeerSet`) when an install must
  re-resolve the vite-plus peer set; ADR-028 records the `npx npm@11.21.0 … --before=`
  workaround. Plain `npm install` on the committed lockfile works (CI path).
- `phpstan` — pre-existing errors only.

**Assumptions / TBD**

- Two-week minimum age for new toolchain releases (why 1.1.0, not 1.3.0; tabs 1.1.22, not
  1.2.x) — recorded in ADR-028 / ADR-029.
- The selected tab is not kept in the URL (ADR-029).

**Checks dijalankan (P19)**

- RED first: `detail-tabs.test.tsx`, `pages/master-menus/show.test.tsx`,
  `pages/bills-of-material/show.test.tsx`, `pages/suppliers/show.test.tsx`,
  `InertiaSsrInTestsTest`.
- `npm run test:ui` — pass, 66 files, 369 tests (Vitest 5.0.3, jsdom 30.1.2). `vp check`, `tsc --noEmit`,
  `npm run build`, `npm run build:ssr` — pass. `npm install` (npm 10.9.9) on the final
  lockfile — pass, tree unchanged. `npm audit` — 5 moderate, 0 critical/high/low.
- `php artisan test` — pass, 1641 passed, 26 skipped (1667 tests, 9163 assertions). `pint --test` — pass. `phpstan` — 2 pre-existing errors only.

## Update 2026-10-22 — Business decisions + purchase request on `DocumentWrites` (`TODO-modules.md` P20)

**Selesai**

- Business answers to `TODO-modules.md` §9 recorded (2026-10-22): menus Core + OMS in one
  module (tabs), every export as gateway CSV, supplier bank account shown in full,
  `.approve` / `.manage` Super Admin only, GTR finish/reopen `.manage`, maker may
  authorize, Notes not needed, UI English, neutral palette. The blocked `[!]` items they
  unblock became `[ ]` tasks.
- ESB questions for the vendor: `docs/esb/open-questions.md` (priority A/B/C).
- `PurchaseRequests\PurchaseRequestDocument::approvable()`; `DecidePurchaseRequest` and
  `DeletePurchaseRequest` go through `DocumentWrites` (lock key unchanged,
  `purchase-request-write:{number}`). `PurchaseRequestLocks::document()` delegates to it.
- Audit: an unconfirmed authorize/reject is now `purchase-request.write-unconfirmed` with
  `operation`; `AuditAction::PurchaseRequestDecisionUnconfirmed` removed. Old rows keep
  `purchase-request.decision-unconfirmed` / `decision` (noted on the enum case and in
  docs/05, docs/06).
- Controllers use `HandlesDocumentWrites` (`decisionOutcome`, `deleteOutcome`): an
  unconfirmed decision shows the banner, not a toast.
- `pages/purchase-requests/show.tsx` uses `ApprovalActions` (no reject reason — the ESB
  takes none).

**Tersisa**

- PI / SP detail pages still have local approval controls.
- Tasks from the decisions: menu module merge (ADR first), gateway CSV export (ADR),
  unmask bank accounts (supplier, disbursement), English UI string sweep.

**Known issues**

- `app:check-production-readiness` fails locally: an `esb_connections` row points the
  `core` connection at production ESB while `APP_ENV=local`. Environment, not code.
- `phpstan` — 2 pre-existing errors only (`EsbConnectionInspector`).

**Checks dijalankan (P20)**

- RED first: `PurchaseRequestDecisionTest` (2 unconfirmed tests), `pages/purchase-requests/show.test.tsx`.
- `vp check`, `tsc --noEmit`, `npm run test:ui` — pass, 67 files, 374 tests.
- `php artisan test` — pass, 1641 passed, 26 skipped. `pint` — pass.

## Update 2026-10-22 — Unblocked work: P21–P25 (`TODO-modules.md` P21–P25, ADR-030, ADR-031)

**Selesai**

- P21: supplier and disbursement bank account numbers shown in full (business decision):
  `SupplierBankAccount::$accountNumber`, `Disbursement::$bankAccountNumber`; masking removed
  from `ReadsEsbValues` (`accountNumber()`). The integration log stores no bodies.
- P22: purchase invoice and simple purchase detail pages use `ApprovalActions` (SP reject
  reason required, max 500; PI none). Page tests added.
- P23: UI string sweep — nothing to change (already English; `StatusBadge` Indonesian
  keywords match ERP status text on purpose).
- P24 (ADR-030): one menu list `/menus?source=core|oms`; `ListMenusRequest` validates per
  source; `/master-menus` → 301 to the OMS tab; OMS detail / extras keep their URLs and the
  detail names its own breadcrumb; nav entry "Master menu" removed;
  `pages/master-menus/index.tsx` deleted.
- P25 (ADR-031): CSV exports built by the gateway for 12 lists — `GET /<list>/export`
  (`ListExportController`, `App\Exports\CsvExport` / `ListColumns` / `PagedQuery`), audit
  `list.exported`, `ExportCsvButton` on each list page. Reads every page before writing,
  stops on the row count, max 5,000 rows; ledger and COA read once.
- OMS create/update menu checked against the vendor's `api_data.json`: body documented,
  but required fields cannot be read back from `get-menu` → stays `[!]`; questions added
  to `docs/esb/open-questions.md` (5.1, 5.1a).

**Tersisa**

- Everything else open in `TODO-modules.md` waits for ESB (`docs/esb/open-questions.md`)
  or is optional (Playwright smoke test, dashboard chart, manual device / screen-reader
  checks).
- vite-plus 1.3.0 after 2026-10-30 (moderate advisory).

**Known issues**

- Local `app:check-production-readiness` fails: an `esb_connections` row points `core` at
  production ESB with `APP_ENV=local`. Environment, not code.
- `phpstan` — 2 pre-existing errors only (`EsbConnectionInspector`).

**Assumptions / TBD**

- Disbursement bank account = the payee supplier's account, so the supplier decision
  applies to it too.
- Exports use the gateway's canonical fields, not the ERP's Excel layout; not re-importable.

**Checks dijalankan (P21–P25)**

- RED first for each phase (mapper/feature tests P21; page tests P22; `MenuTest` /
  `MasterMenuTest` / `menus/index.test.tsx` P24; `CsvExportTest`, `ListExportTest`,
  `export-csv-button.test.tsx`, `list-exports.test.tsx` P25). Four older tests that pinned
  "no /export route" were updated for ADR-031.
- `php artisan test` — pass, 1670 passed, 26 skipped (1696). `vp test` — pass, 72 files,
  399 tests. `vp check`, `tsc --noEmit`, `pint --test` — pass. `phpstan` — 2 pre-existing.
