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
- [x] Verify `php artisan migrate` runs — **done 2026-09-10** against the local
      PostgreSQL 16 (port **5433**, not the default 5432). All migrations applied and
      `db:seed` verified: 17 tables, 4 roles, 14 permissions, 401 catalog endpoints.
      Note: had to run with `CACHE_STORE=array`, because spatie's permission migration
      calls `forgetCachedPermissions()` and Redis is not available on this machine. The
      cache store is not part of the schema, so this is a local workaround, not a
      deployment requirement.
- [x] Configure Redis for cache, queue, and locks. `predis/predis` is installed because
      the `phpredis` PHP extension is not present locally; CI uses `phpredis`.
- [ ] **Blocked** — verify a queued job round-trips. No Redis server on this machine and
      no Docker. This is now the only outstanding Phase 1 item; CI exercises Redis, but
      the ADR-009 lock's distributed behaviour is still untested (see the Phase 3
      follow-ups).
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

**Done when:** a clean checkout can install, build, migrate, and pass CI. — Met except
for the queued-job round-trip, which needs a local Redis.

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

Built test-first.

- [x] Implement `GET /product/list` (Core family) — the vendor's own tutorial endpoint.
      Documented as `[ESB-001]` in `docs/05-api-integration.md`.
- [x] Add the response DTO and mapper — `ProductSummary`, `ProductListQuery`,
      `ProductListResult`, `ProductMapper`. Fields transcribed for this operation only.
      The mapper reads every field defensively: ESB responses are untrusted input, and a
      row missing a documented field must not take down a page render.
- [x] Add a thin controller and the Inertia page with a table, search, and paging.
      **No Service or Action layer** — this read applies no business rule of its own, and
      `ProductClient` already satisfies AGENT.md's "isolate ESB integration behind a
      dedicated class". Adding another pass-through would be the empty abstraction
      `docs/02-architecture.md` warns against. Add one when a product operation gains
      logic worth separating.
- [x] Handle pagination: `page` / `limit` (default 20, maximum 100), re-derived on our
      side. `EsbPagination` deliberately drops the ESB's absolute `prev` / `next` URLs,
      and a test asserts the rendered page contains no ESB host.
- [x] Validate before calling. The ESB answers an unsupported `sort` and a non-numeric
      `page` with `EC03100032` "Unknown Error" — one of them carrying a raw Go
      `strconv.ParseInt` message. `ListProductsRequest` turns those into field errors and
      saves the round trip; tests assert nothing is sent to the ESB.
- [x] Map ESB failures onto HTTP statuses (`EsbExceptionRenderer`): 404 not found,
      422 validation and business rejection, 504 timeout, 502 for everything else.
      An ESB authorization failure is **not** a 403 — the gateway's credential lacking a
      privilege is an upstream configuration problem, and telling the signed-in user they
      are forbidden would send them to request a role that would not help.
- [x] Cover happy path, validation failure, authorization failure, upstream failure, and
      timeout.
- [x] Review the slice as the reference pattern every later adapter copies.

**Done when:** a logged-in user with the right permission sees live-shaped product data,
and the same pattern is obviously reusable. — Met against faked HTTP. 171 tests passing.

Note on access: reading products requires `esb.invoke`, so only Integration Operator and
Super Admin can reach the page. IT Admin and Viewer are refused, which is the separation
`docs/06-authentication-authorization.md` describes.

Still open from this phase:

- [ ] Run this slice against a real ESB. It has only ever spoken to `Http::fake()`.
- [ ] Surface the envelope's `errors[]` detail (attribute, code, message) on validation
      failures. The ESB returns per-field detail that we currently discard.

---

## Phase 5 — Operational Modules

Built test-first.

- [x] ESB Connections — **read-only plus a probe action (ADR-013).**
      **Superseded 2026-09-10 by ADR-018**, which makes connections editable with
      credentials encrypted at rest. What shipped in this phase is still what runs today;
      the editable version is Phase 8. The reasoning below is preserved because ADR-018
      has to keep answering it.
 Base URLs and
      credentials stay in config and the environment; there is no `esb_connections`
      table and no edit route. `docs/10-security.md` is explicit that secrets belong in
      environment or secret management rather than database plaintext, and a second
      editable source of truth would let it disagree with `config/esb.php` — with traffic
      going somewhere nobody intended as the failure. This supersedes the
      `esb_connections` sketch in `docs/07-database-design.md`. A test asserts no
      `esb-connections.update` route exists.
- [x] Connectivity probing (ADR-014) — a documented, read-only, cheap `GET` per family;
      Core uses `/product/list?limit=1`. The vendor documents no health endpoint, so
      families without a known-safe read report that connectivity cannot be probed
      rather than showing a green light nobody verified.
      A probe that fails on authorization still reports **reachable**: the ESB answered,
      so the host is up. Conflating that with "unreachable" would send whoever is
      diagnosing it to check firewalls instead of ESB user permissions.
- [x] Endpoint catalog — `php artisan esb:import-catalog` parses the generated files in
      `docs/esb/` into `integration_endpoints`, and a read-only UI makes all 401
      operations searchable by name, path, group, method, and family. Idempotent, and it
      prunes operations the vendor withdrew. No write routes: the catalog mirrors
      `docs/esb/`, and editing a row would create a source of truth that silently
      disagrees with it.
- [x] Integration log viewer — filters on correlation ID, operation, outcome, family, and
      date range, all validated. Answers the question an error page creates: a user
      reports the reference printed on it, and this is where it gets found.
- [x] Dashboard — calls, success rate, average duration, queue depth, failures by kind,
      availability per base URL family, and recent failures.
      An empty window reports a **null** success rate rather than 0% or 100%; either
      number would be a claim the data does not support, and a red 0% on a quiet morning
      would send someone chasing an outage that is not happening.
- [x] Health check endpoints — `GET /health` covers application, database, cache, and
      queue, unauthenticated because a load balancer cannot log in. The body says what is
      broken, never where or why: a database error can carry a host, port, and username,
      and the endpoint is public. Detail goes to the application log.
      It deliberately does **not** probe the ESB. A load balancer polling every few
      seconds would hammer it, and a third party being down would pull our own instances
      out of rotation.
- [x] `IntegrationOutcome` gives the outcome list one home, guarded by a reflection test
      that fails in both directions — an exception kind missing from the list, or a
      listed outcome nothing can produce.

**Done when:** an operator can see what is configured, what the ESB offers, what the
gateway has been doing, and what is currently broken. — Met. 241 tests passing.

Two bugs in the generated catalogs were found and fixed while building the importer:

- Four Core "Flow" entries carry a literal `|` as their vendor URL, which broke the
  markdown table row. They now render as an em dash, and pipes are escaped.
- The vendor ships **two distinct operations** at `Report`/`get-sales-information` that
  differ only by version, method, and host. The catalogs now carry a Version column, and
  version is part of an endpoint's identity.

Still open from this phase:

- [ ] Alert thresholds (`docs/11-logging-monitoring.md` leaves them `TBD`).
- [ ] Latency percentiles. Only the average is reported, and an average hides the tail
      that actually annoys users.

---

## Phase 6 — Hardening and Deployment

Built test-first.

- [x] Apply rate limiting on ESB-invoking routes — a per-user budget
      (`config/esb.rate_limit`), keyed per user so one runaway script does not lock out
      the team. The limit is **ours, not the vendor's**: the ESB's own limits are
      undocumented, so the gateway must not let its users discover them the hard way.
      Gateway-owned screens are outside the budget, so being over it does not stop
      someone diagnosing why.
- [x] Work through the security checklist in `docs/10-security.md`. The configuration
      half is now automated by `php artisan app:check-production-readiness`, which runs
      in `composer ci:check`; the rest is covered by tests. Both are marked up in that
      document.
      The check that earns its keep: a non-production install pointed at the
      **production ESB** fails. A staging install wired to production writes to real ERP
      data, and nothing in the application would look wrong until it did.
- [x] Secure headers on every response including `/health`, HSTS in production over TLS
      only, `URL::forceScheme('https')` in production, and no `X-Powered-By` or `Server`
      header.
- [x] Define integration log retention and archival — `php artisan integration-logs:prune`
      with a configurable window, scheduled daily, deleting in batches.
      A zero or unset window **keeps** everything rather than deleting it: a
      misconfiguration must not read as "delete everything", and the period is still an
      open business decision. Audit logs are deliberately out of scope — immutable by
      design (ADR-012), and their retention is a compliance question, not a volume one.
- [x] Document deployment concretely (`docs/13-deployment.md`): what the application
      requires of any topology, build and deploy sequence, queue worker supervision with
      `stopwaitsecs` above the longest job, the single-instance scheduler, and why Redis
      is not optional (web and workers must share the ESB token cache, or they invalidate
      each other's tokens — ADR-009).

**Done when:** the application is safe to point at a real ESB and a release can be
verified rather than hoped about. — Met for everything checkable in code. 272 tests
passing (verified 2026-09-10).

Still open, and **not** claimable from this machine:

- [ ] Finalize deployment topology — instance counts, hosting, and network boundaries are
      business/infrastructure decisions, not code ones.
- [ ] Verify backup and recovery against real infrastructure.
- [ ] Rehearse a rollback. Migrations are the real constraint: a release with a
      destructive migration cannot be rolled back by redeploying code alone.
- [ ] Decide the integration log retention period. The mechanism exists; 90 days is a
      placeholder, not a decision.
- [ ] Alert thresholds and delivery (`docs/11-logging-monitoring.md` leaves them `TBD`).
- [ ] `composer audit` / `npm audit` review pass.

---

## Phase 7 — Documentation Consistency — **done 2026-09-10**

Raised by cross-checking the 2026-09-10 documentation update (`51e803c`) against the
code, and resolved with the author the same day. Each item was a place where a document
and the repository disagreed.

- [x] **Fixed the ADR cross-references — they were off by one.** `DECISIONS.md` numbers
      the multi-provider ADR **016** and the do-not-scaffold ADR **017**, but ADR-015 is
      "Base Classes on Proven Duplication", so every "ADR-015/ADR-016" reference pointed
      at the wrong decision. Corrected in `AGENT.md` (2), `DECISIONS.md` (3 self-
      references), `docs/01`, `docs/02`, `docs/04` (2), `docs/project-state.md`, and
      `README.md` (2).
- [x] **ESB Connections — resolved as a deliberate scope change, not a revert.** The
      2026-09-10 wording in `docs/04-functional-requirements.md` §2 was intentional: the
      business wants connections editable at runtime. ADR-013 rejected that, so
      **ADR-018 was written to supersede it**, and ADR-013 is now marked
      `Superseded by ADR-018`. ADR-013's own "Revisit if" named this exact condition.
      §2 now marks which capabilities ship today and which are planned;
      `docs/07-database-design.md` carries the table specification. The build itself is
      Phase 8 below.
- [x] **Restored the lowercase `resources/js` convention in `docs/02-architecture.md`,**
      together with the warning against capitalized duplicates that had been deleted. The
      real tree is lowercase, and `docs/08-frontend-guidelines.md` still carried the same
      warning.
- [x] **Corrected the `README.md` file list.** It described
      `docs/06-authentication-authorization.md` as covering machine-to-machine provider
      auth; that section does not exist. It now says user auth only, with M2M noted as
      arriving with the first inbound provider (ADR-017).
- [x] **Aligned the ESB namespace casing.** Documents said `App\Integrations\Esb\...`;
      the directory is `app/Integrations/ESB`. The documents now match the code.
- [x] **Marked `routes/api.php` as not yet created** in `docs/02-architecture.md`, and
      listed the files that actually exist alongside it.
- [x] **Refreshed the test count.** `docs/project-state.md` said 268; the suite is at
      **272 passing, 949 assertions** (2026-09-10).

**Done when:** no document in this repository asserts something the code contradicts. —
Met.

---

## Phase 8 — Editable ESB Connections (ADR-018) — **done 2026-09-10**

ADR-018 supersedes ADR-013: connections are editable records, so an ESB environment can
be added or repointed without a deploy. Built test-first, as every phase before it.

The security posture is the whole point of this phase, not a detail bolted on at the end.
ADR-013 refused this feature for three concrete reasons, and each one stayed refused in
the implementation.

- [x] `esb_connections` migration — `family` + `environment` unique, `base_url`,
      `auth_mechanism`, `credentials`, `is_active`, timestamps. Verified against the
      local PostgreSQL 16 as well as the SQLite suite.
- [x] Credentials encrypted at rest with Laravel's `encrypted:array` cast. The test reads
      the raw column back through the query builder: asserting through the model would
      only prove the cast round-trips, which is true of a plaintext column too.
- [x] Credentials are **write-only**. `credentials` is in the model's `$hidden`, so no
      array, JSON, log line, job payload, or Inertia prop can carry one by accident.
      Asserted on the rendered props, on `toJson()`, and on the audit row.
- [x] One resolver, one place — `EsbConnectionResolver` applies precedence: active row,
      then `ESB_BASE_URL_*` override, then the family table. Nothing writes back into
      config. A row whose environment is not the configured one is a record, not a route,
      and is ignored.
- [x] `app:check-production-readiness` evaluates the **effective** base URL per family.
      Written before the edit routes existed. Both levers are covered: a database row and
      an environment override pointing a non-production install at the production ESB
      each fail the check, and an inactive row does not.
- [x] Edit and revert routes gated by `esb-connections.manage`, which until now granted
      nothing beyond `view`. This is what it was reserved for.
- [x] **Inverted** the old "no update route exists" test into
      `editing_a_connection_requires_the_manage_permission`. The route is gone as a
      guarantee; the authorization it was really protecting is not.
- [x] An immutable `audit_logs` row per update and revert, written in the same
      transaction as the change (ADR-012), naming which fields moved and never their
      values.
- [x] Inertia page: per-family edit dialog, masked credential fields, a "revert to
      configuration" action, and a Source column naming which layer won.
- [x] `APP_KEY` re-encryption path documented in `docs/13-deployment.md` before this
      shipped, along with the fact that losing the key loses every stored credential.
- [x] `docs/10-security.md` records ADR-018 as the "explicitly required and protected"
      exception its Secrets section allows, with the four protections that make it one.
- [x] `docs/13-deployment.md` notes that a database backup now carries credential
      material, encrypted, and inherits the application's protection requirements.

**Done when:** an operator with `esb-connections.manage` can add and repoint an ESB
connection through the UI, no credential is readable anywhere after storage, every change
is audited, and a non-production install still cannot reach the production ESB. — Met.
302 tests passing, 1027 assertions.

Still open from this phase:

- [ ] Rehearse an `APP_KEY` rotation against real infrastructure. The path is written;
      it has never been walked.
- [ ] Decide a rotation and retention policy for stored ESB credentials (ADR-018 leaves
      this open).
- [ ] Revisit storage if a secret manager is ever provisioned — references to managed
      keys would supersede encryption-in-column, without changing that connections are
      editable.

---

## Phase 9 — Multi-Provider Generalization (deferred — do not start yet)

ADR-016 sets the target architecture: every external system, ESB included, is a
**provider** integrated through an adapter pair against a canonical model. ADR-017 is
equally binding: **none of the generic infrastructure below is built until a real second
provider is contracted.** These items are recorded so the first second-provider
integration follows the shape from day one, not so anyone starts them now.

**Gate:** a second external system is named and its contract is documented. Until then
every box here stays unchecked deliberately, and that is the correct state.

Ordered, once the gate opens:

- [ ] Name the second provider and document its contract the way
      `docs/05-api-integration.md` documents the ESB's — auth, envelope, error catalog,
      pagination, rate limits. Do not start code before this exists.
- [ ] Extract `Integrations/Contracts/OutboundAdapter.php` and `InboundAdapter.php`
      **from the existing ESB client**, not from a guess. ADR-017 is explicit that
      generalizing the working implementation comes before writing the new provider's
      adapter.
- [ ] Introduce `App\Domain\Canonical\...` DTOs for the entities that actually move
      between the two systems, and only those. `ProductSummary` is the candidate to
      promote first, since it already exists.
- [ ] Add the `integration_providers` registry table per `docs/07-database-design.md`.
      Metadata only — credentials stay in config/environment/secret management, per
      ADR-013.
- [ ] Add the `sync_transactions` table per `docs/07-database-design.md`, with the
      `idempotency_key` unique constraint. This is a business-record-level trace and
      must stay distinct from `integration_logs`, which remains a per-HTTP-call log.
- [ ] Add machine-to-machine authentication for providers calling **into** the gateway,
      separate from user authentication, and write the missing section in
      `docs/06-authentication-authorization.md`.
- [ ] Create `routes/api.php` for inbound provider endpoints, kept separate from
      user-facing web routes, with per-provider credential or signature verification.
- [ ] Add `SyncOrchestrator` under `app/Services/Sync/`. Orchestration lives in a
      Service (ADR-007); an adapter must never call another adapter.
- [ ] Give each provider its own queue so a degraded provider cannot block work destined
      for another (`docs/02-architecture.md`).
- [ ] Extend the dashboard and the ESB Connections screen to per-provider health rather
      than assuming ESB is the only source of activity
      (`docs/04-functional-requirements.md` §1).
- [ ] Add per-provider rate limiting and circuit-breaker state, mirroring the per-user
      ESB rate limit already in place.
- [ ] Build the replay/dead-letter path for `sync_transactions` rows that need manual
      intervention.

Every item is built test-first, like the six phases before it.

**Done when:** a second provider is integrated end to end, and the ESB integration fits
the same contracts without being a special case.

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
