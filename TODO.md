# TODO — Project Kickoff

Ordered task list for bringing the ERP Gateway from documentation to a running
application. Read `AGENT.md` and `docs/project-state.md` before starting any item here.

Update `docs/project-state.md` when a phase completes — this file tracks tasks, that file
tracks state.

## Ground rules for every task

- Smallest coherent change, one focused commit.
- Tests before the task is considered done (`docs/12-testing.md`).
- Run formatter, linter, and type checks.
- Update documentation when behavior or architecture changes.
- Never commit `.env` or any real credential.

---

## Phase 1 — Repository and Scaffold

- [x] `git init`, set the default branch, create `.gitignore` covering `.env`,
      `/vendor`, `/node_modules`, `/public/build`, `/storage/*.key`, `__pycache__`.
- [x] Commit the current documentation as the first commit before any code lands.
- [x] Scaffold Laravel 13 into this repository.
      Used the `laravel/react-starter-kit` `main` branch, which targets Laravel 13.17,
      PHP 8.3+, Inertia 3, React 19, Tailwind 4, and Vite 8. The tagged v1.0.1 release
      on Packagist still requires Laravel 12 — take `main`, not the tag.
- [x] Add the React + TypeScript + Inertia starter. Verified against ADR-001/002/003:
      React lives in `resources/js`, no Livewire in `composer.json`/`package.json`, no
      separate frontend project.
      Auth features kept: **2FA only**. Registration, email verification, password
      confirmation, and passkeys were removed by `install:features` — that removal
      deletes code, so re-adding any of them later means rebuilding the Fortify routes
      and Inertia pages by hand.
- [x] Confirm Vite builds and the welcome page renders through Inertia.
- [x] Initialize Tailwind CSS and shadcn/ui — both ship with the starter kit
      (Tailwind 4 via `@tailwindcss/vite`, shadcn/ui via `components.json` and
      `resources/js/components/ui`).
- [x] Configure PostgreSQL as the default connection.
- [ ] **Blocked** — verify `php artisan migrate` runs. Needs the `postgres` password;
      `pg_hba.conf` requires `scram-sha-256`. Note this machine's PostgreSQL 16 listens
      on port **5433**, not the default 5432.
- [x] Configure Redis for cache, queue, and locks. `predis/predis` is installed because
      the `phpredis` PHP extension is not present locally; CI uses `phpredis`.
- [ ] **Blocked** — verify a queued job round-trips. No Redis server on this machine and
      no Docker.
- [x] Set `APP_TIMEZONE` explicitly and decide the storage timezone convention. Store
      UTC; parse ESB timestamps as Asia/Jakarta on ingest. Recorded in
      `docs/07-database-design.md`.
- [x] Write `.env.example` with every key the application reads, using placeholders only.
      Includes the ESB block, so the API key has a documented home when it arrives.
- [x] Set up Pint, ESLint, Prettier, and TypeScript in strict mode. The starter kit uses
      `vite-plus` (`vp check`) for formatting and linting rather than standalone ESLint
      and Prettier; Pint and PHPStan cover PHP. `tsconfig.json` already sets
      `strict: true`.
      Project documentation is excluded from the formatter in `vite.config.ts` — it
      collapsed nested lists in this file and rewrote the generated ESB catalogs, which
      would fight `docs/esb/generate-catalog.py` on every regeneration.
- [x] ~~Set up Pest~~ — **kept PHPUnit instead.** The starter kit ships PHPUnit 12 with
      26 passing tests covering auth, 2FA, dashboard, and settings.
      `docs/03-technology-stack.md` permits either, and converting working tests to Pest
      would be churn with no functional gain.
- [x] Add a CI workflow running: install, format/lint, `tsc --noEmit`, Pint, PHPStan,
      Vite build, and the test suite. CI runs the suite against **real PostgreSQL and
      Redis** service containers rather than the SQLite default in `phpunit.xml`, so
      driver differences surface in CI instead of production.

**Done when:** a clean checkout can install, build, migrate, and pass CI.
Currently blocked only on the two verification items above.

---

## Phase 2 — Application Foundations

Built test-first: each module's tests were written and watched fail before the
implementation was added.

- [x] Implement application authentication (session-based) and confirm the login,
      logout, and password flows work through Inertia. Provided by Fortify in the
      starter kit; covered by its existing tests.
- [x] Define roles and permissions — `app/Authorization/Permission.php` and
      `app/Authorization/Role.php`, using `spatie/laravel-permission` (ADR-011).
      **The four roles remain unconfirmed by the business.** Confirm before treating
      them as final.
- [x] Add the `roles` / `permissions` schema and seeders. `RolePermissionSeeder` syncs
      the database from the enums and is idempotent — it also deletes roles and
      permissions no longer in the enum, so a removed ability cannot linger and keep
      granting access.
- [x] Add Policies/Gates and a feature test proving an unauthorized user is rejected
      server-side, not merely hidden in the UI. `UserPolicy` plus
      `RouteAuthorizationTest`, which posts directly to the role-assignment endpoint
      rather than going through the UI.
- [x] Build the authenticated application shell: permission-gated sidebar navigation,
      a `usePermissions()` hook, a `table` UI primitive, and the users administration
      page with empty state.
- [x] Add `audit_logs` and record privileged changes (ADR-012). Immutable rows, written
      in the same transaction as the change.
- [x] Add `php artisan user:assign-role` — the bootstrap path for the first Super Admin,
      since granting a role through the UI requires already holding `users.manage`.
- [x] Add `PermissionTypesInSyncTest`, which fails the build when
      `resources/js/types/permissions.ts` drifts from the PHP enums. Two lists of the
      same strings in two languages drift silently, and the symptom would be a control
      that never renders rather than an error.

**Done when:** a user can log in, is authorized server-side, and lands on an empty but
navigable dashboard. — Met. 62 tests passing.

Still open from this phase:

- [ ] Confirm the role set and each role's grants with the business.
- [ ] Shared component set is partial — forms, dialogs, alerts, status badges, and
      loading states arrive with the screens that need them rather than up front.

---

## Phase 3 — ESB Integration Foundation

This phase does **not** need real ESB credentials. Build and test the entire layer
against `Http::fake()` using the response examples in the vendor documentation. Wire the
real key into `.env` when it arrives.

Built test-first throughout.

- [x] `config/esb.php` — base URLs modelled as named families per ADR-010, with an
      environment selector, switchable auth mechanism, timeouts, retry budget, and
      per-family overrides. The only place that reads ESB environment variables.
- [x] `.env.example` ESB keys (done in Phase 1). `ESB_AUTH_MECHANISM` switches between
      `static_token` and `login`, so the static-key decision stays a config change.
- [x] Envelope parser — `EsbEnvelope`, with pagination and Asia/Jakarta timestamp
      conversion. A body with no `code` is malformed, never guessed at.
- [x] Error classifier — branches on `code` only. All 29 documented codes are mapped
      (`ErrorCode`), and an unrecognised code becomes `EsbUnknownException` and logs at
      warning level so new vendor codes surface.
- [x] Exception hierarchy — validation, authentication, authorization, not-found,
      business rejection, timeout, connection, upstream, malformed, unknown. Each
      carries an `EsbContext` with correlation ID, operation, family, duration, and
      upstream status, plus a `safeMessage()` that keeps upstream detail away from
      users.
- [x] Token manager (ADR-009) — static-token and login modes, Redis-backed shared cache,
      login and refresh serialized behind a bounded lock, cache re-read after acquiring
      the lock, refresh via `GET /auth/refresh` with the token in the `Authorization`
      header, invalidation on `EC03100001`.
- [x] The ADR-009 test: ten callers with an empty cache produce exactly one login, and a
      separate test proves the manager re-reads the cache after acquiring the lock —
      the mechanism that makes that true under real concurrency.
      **Caveat:** the suite exercises this in one process against the array cache store.
      A genuine multi-process race against Redis is not covered; see the follow-up below.
- [x] ESB HTTP client — family-aware base URL resolution, explicit connect and request
      timeouts, correlation ID per call, structured logging, error normalization.
- [x] Log redaction — no token, password, or `Authorization` header reaches the
      application log or `integration_logs`. Asserted by reading the log file back.
- [x] Retry policy — reads retry with exponential backoff (three attempts); writes get
      exactly one attempt; `EC03100001` discards the token and retries once. Each branch
      has its own test.
- [x] `integration_logs` migration and model, indexed on `correlation_id`, plus
      `(outcome, created_at)` and `(operation, created_at)` for the log viewer.
- [x] Record integration outcomes from the client, redacted. A failure to write the log
      never takes down the business operation that produced it.

**Done when:** the full ESB layer is covered by tests using faked HTTP, with no
credentials present. — Met. 148 tests passing, no credentials in the repository.

Follow-ups from this phase:

- [ ] Run the token-manager tests against a real Redis in CI. `phpunit.xml` pins
      `CACHE_STORE=array`, whose locks are per-process, so the distributed behaviour the
      lock exists for is reasoned about rather than exercised.
- [ ] Revisit the timeout values once the ESB team confirms their server-side budgets.
- [ ] Send a correlation header if the ESB team confirms one is accepted. The client
      deliberately sends none today, and there is a test asserting that.

---

## Phase 4 — First Vertical Slice

Prove the whole pattern end to end on one read-only operation before building breadth.

- [ ] Implement `GET /product/list` (Core family) — the vendor's own tutorial endpoint.
      Document it with the template in `docs/05-api-integration.md`.
- [ ] Add the response DTO and mapper. Transcribe fields from the vendor documentation
      for this operation only; do not bulk-import schemas for unimplemented endpoints.
- [ ] Add the Service/Action, a thin controller, and the Inertia page with a table.
- [ ] Handle pagination: `page` / `limit` (default 20, **maximum 100**). Re-derive
      pagination on our side — never pass the ESB's absolute `prev` / `next` URLs to the
      browser, they leak the ESB host.
- [ ] Cover happy path, validation failure, authorization failure, upstream failure, and
      timeout (`docs/12-testing.md`).
- [ ] Review the slice as the reference pattern every later adapter copies.

**Done when:** a logged-in user with the right permission sees live-shaped product data,
and the same pattern is obviously reusable.

---

## Phase 5 — Operational Modules

- [ ] ESB Connections: configure per-family base URLs and environment, test
      connectivity, show health. Secrets are never displayed in plaintext after storage
      (`docs/04-functional-requirements.md`).
- [ ] Endpoint catalog UI, seeded from `docs/esb/`. Keep it documentation/configuration
      unless a requirement makes it runtime-driven.
- [ ] Integration log viewer with filters on correlation ID, operation, status, and date.
- [ ] Dashboard: success/failure counts, latency, queue depth, recent errors, per-family
      ESB availability (health is per family, not global — ADR-010).
- [ ] Health check endpoints for application, database, and Redis.

---

## Phase 6 — Hardening and Deployment

- [ ] Apply rate limiting on ESB-invoking routes.
- [ ] Work through the security checklist in `docs/10-security.md`.
- [ ] Define integration log retention and archival.
- [ ] Finalize deployment topology (`docs/13-deployment.md`); configure queue worker
      supervision and restart-on-deploy.
- [ ] Verify backup and rollback.

---

## Blocked — needs answers from the ESB team

These are unanswered in the vendor documentation. None of them block Phases 1–3, but
they must be resolved before pointing the gateway at a real environment.

- [ ] Which environment and base URL family is this project entitled to?
- [ ] Which credentials, and is a **static API key** available? A static key removes the
      single-session problem entirely and is the preferred mechanism (ADR-009).
      *Decision: to be set in `.env` later — build both paths in Phase 3.*
- [ ] Rate limits and throttling behavior.
- [ ] Server-side timeout budgets per operation.
- [ ] Does the ESB accept a caller-supplied correlation/request ID header? Until
      confirmed, do not send an undocumented header.
- [ ] Any idempotency support for writes? None is documented — assume none.
- [ ] Is `companyID` / `companyCode` scoping implied by the credential, or per request?
- [ ] Base URLs for the operations where the vendor declares none: four Core "Flow"
      pages, and some OMS `Push_Sales_Data` and `Report` entries.
- [ ] Which of the 401 operations are actually in scope for this project?
