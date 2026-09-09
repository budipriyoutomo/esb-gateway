# Project State

> This file is the hand-off state for humans and AI coding agents. Keep it current.

## Last Updated

2026-09-09

## Current Phase

Phase 1 — Repository & Scaffold (nearly complete; see `TODO.md`)

## Current Objective

Prepare the foundation and documentation for an ERP Gateway & Middleware application
integrating with the ERP ESB.

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
- [ ] ESB credentials, environment, and auth mechanism confirmed with ESB team
- [ ] Authentication flow finalized against ESB
- [ ] Initial database schema finalized
- [ ] Deployment topology finalized
- [x] Laravel 13 + Inertia 3 + React 19 + TypeScript scaffolded
- [x] Tailwind 4 + shadcn/ui in place
- [x] Formatting, linting, static analysis, and CI configured

## Completed

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

## In Progress

- Phase 1 verification, blocked on local infrastructure (see below)
- Functional requirements
- Initial domain/data model
- Selecting the first ESB operations to implement

## Blocked

Phase 1 is complete except for two verification steps that need local services:

- `php artisan migrate` against PostgreSQL — needs the `postgres` role password
  (`pg_hba.conf` requires `scram-sha-256`). PostgreSQL 16 is installed and running on
  this machine, but on port **5433**, not 5432.
- Queued job round-trip against Redis — no Redis server and no Docker on this machine.

Neither blocks Phase 2 or Phase 3: the test suite runs on SQLite in-memory locally, and
CI exercises real PostgreSQL and Redis.

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
- Project documentation is excluded from the frontend formatter — do not re-enable it.

## Handoff Notes

When changing phase or completing a major feature, update this file with:

- completed items;
- current work;
- next tasks;
- known issues;
- relevant decisions.
