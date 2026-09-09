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

- [ ] `git init`, set the default branch, create `.gitignore` covering `.env`,
      `/vendor`, `/node_modules`, `/public/build`, `/storage/*.key`, `__pycache__`.
- [ ] Commit the current documentation as the first commit before any code lands.
- [ ] Scaffold Laravel 13 into this repository.
- [ ] Add the React + TypeScript + Inertia starter (verify the current installer flags
      against the Laravel 13 installer — do not assume flags from an older version).
      Confirm the result satisfies ADR-001/002/003: React under `resources/js`, no
      Livewire, no separate frontend project.
- [ ] Confirm Vite builds and the welcome page renders through Inertia.
- [ ] Initialize Tailwind CSS and shadcn/ui.
- [ ] Configure PostgreSQL as the default connection; verify `php artisan migrate` runs.
- [ ] Configure Redis for cache, queue, and locks; verify a queued job round-trips.
- [ ] Set `APP_TIMEZONE` explicitly and decide the storage timezone convention. ESB
      timestamps are Asia/Jakarta with no offset — record the decision in
      `docs/07-database-design.md`.
- [ ] Write `.env.example` with every key the application reads, using placeholders only.
- [ ] Set up Pint, ESLint, Prettier, and TypeScript in strict mode.
- [ ] Set up Pest with a passing smoke test.
- [ ] Add a CI workflow running: install, Pint, ESLint, `tsc --noEmit`, Vite build, Pest.

**Done when:** a clean checkout can install, build, migrate, and pass CI.

---

## Phase 2 — Application Foundations

- [ ] Implement application authentication (session-based) and confirm the login,
      logout, and password flows work through Inertia.
- [ ] Define roles and permissions. The four roles in
      `docs/06-authentication-authorization.md` (Super Admin, IT Admin, Integration
      Operator, Viewer) are unconfirmed starting points — confirm with the business
      before treating them as final.
- [ ] Add the `roles` / `permissions` schema and seeders.
- [ ] Add Policies/Gates and a feature test proving an unauthorized user is rejected
      server-side, not merely hidden in the UI.
- [ ] Build the authenticated application shell: navigation, layout, and the shared
      component set (tables, forms, dialogs, alerts, status badges, loading and empty
      states) per `docs/08-frontend-guidelines.md`.
- [ ] Add `audit_logs` and record privileged changes.

**Done when:** a user can log in, is authorized server-side, and lands on an empty but
navigable dashboard.

---

## Phase 3 — ESB Integration Foundation

This phase does **not** need real ESB credentials. Build and test the entire layer
against `Http::fake()` using the response examples in the vendor documentation. Wire the
real key into `.env` when it arrives.

- [ ] `config/esb.php` — model base URLs as **named families** per ADR-010, not a single
      URL. Families: `core`, `voucher`, `fnb_backend`, `pos`; each with per-environment
      hosts from `docs/05-api-integration.md`. Read `env()` only inside this config file.
- [ ] Add `.env.example` keys for the ESB. Make the auth mechanism switchable so the
      static-key decision is a configuration change, not a rewrite:
      `ESB_ENV`, `ESB_AUTH_MECHANISM` (`static_token` or `login`), `ESB_STATIC_TOKEN`,
      `ESB_USERNAME`, `ESB_PASSWORD`, plus per-family base URL overrides and timeouts.
      Placeholders only.
- [ ] Implement the envelope parser: `path`, `timestamp`, `status`, `code`, `message`,
      `result`, `errors`. Treat a missing or non-JSON body as malformed.
- [ ] Implement the error classifier. **Branch on `code` only** — never on `message` or
      `status`. Use the 29-code table in `docs/05-api-integration.md`. An unrecognised
      code becomes `EsbUnknownException` and logs at warning level so new vendor codes
      surface instead of being swallowed.
- [ ] Implement the exception hierarchy: validation, authentication, authorization,
      not-found, business rejection, timeout, connection, upstream, malformed, unknown.
      Each carries correlation ID, operation, ESB `code`, and duration.
- [ ] Implement the token manager (ADR-009):
      - static-token mode reads the key from config and does nothing else;
      - login mode calls `POST /auth/login`, caches access and refresh tokens in Redis,
        and refreshes via `GET /auth/refresh` (token in the `Authorization` header, no
        body);
      - login and refresh are serialized behind a Redis lock — concurrent callers wait
        for the result rather than logging in themselves;
      - the cached token is invalidated immediately on `EC03100001`;
      - `exp` from the JWT may schedule proactive refresh but is advisory only.
- [ ] Write the test that matters most for ADR-009: **N concurrent callers with an
      expired token trigger exactly one login/refresh call.** This is the failure mode
      the single-session constraint creates, and it will not show up in manual testing.
- [ ] Implement the ESB HTTP client: explicit connect and overall timeouts, family-aware
      base URL resolution, correlation ID generation, and structured logging.
- [ ] Implement log redaction. The login and refresh **response bodies** contain
      `accessToken` and `refreshToken` — redact bodies, not just the `Authorization`
      header. Add a test asserting no token string reaches the log.
- [ ] Implement retry policy per `docs/05-api-integration.md`: `GET` retries with
      backoff; writes never retry automatically; `EC03100001` refreshes once then retries
      once. Add tests for each branch.
- [ ] Add `integration_logs` migration and model with the fields in
      `docs/11-logging-monitoring.md`; index `correlation_id`.
- [ ] Record integration outcomes from the client, redacted.

**Done when:** the full ESB layer is covered by tests using faked HTTP, with no
credentials present.

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
