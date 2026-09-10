# Project State

> This file is the hand-off state for humans and AI coding agents. Keep it current.

## Last Updated

2026-09-10

## Current Phase

Phase 6 complete for everything checkable in code. Remaining work needs real
infrastructure or business decisions — see `TODO.md`.

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
ADR-018 (2026-09-10) supersedes ADR-013: ESB connections become editable records with
credentials encrypted at rest, write-only after storage, and every change audited. The
table and the edit path are specified but **not yet built** — see `TODO.md` Phase 8.
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

## In Progress

- Nothing in code. The build is feature-complete against `TODO.md` and waiting on
  external inputs: ESB credentials, business confirmation of the role set, and
  infrastructure decisions.
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
- ESB connections are read-only **as shipped today**. ADR-018 supersedes ADR-013 and
  makes them editable; until that is built, the screen has no edit route and there is no
  `esb_connections` table. When it is built, credentials are encrypted at rest and
  write-only — never returned after storage, never logged.
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
