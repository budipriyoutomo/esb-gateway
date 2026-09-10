# Architecture Decisions

This file records durable technical decisions. Do not reverse an accepted decision
casually — if a decision needs to change, add a new ADR that supersedes the old one and
explain why.

## ADR-001 — Single Laravel Repository with React Inside Laravel

**Status:** Accepted

**Decision**
Use one Laravel application/repository containing the React frontend under
`resources/js`, rather than separate frontend and backend projects.

**Reason**
- Single codebase
- Single deployment unit
- Shared authentication and authorization
- Simple operational model
- React provides a modern UI without requiring a separate frontend application

**Rejected**
- Separate React repository
- Next.js frontend as a separate application
- Livewire as the primary application UI

---

## ADR-002 — Use Inertia.js

**Status:** Accepted

**Decision**
Use Inertia.js as the bridge between Laravel routes/controllers and React pages.

**Reason**
- Keeps Laravel routing and server-side application conventions
- Avoids building a completely separate frontend API architecture for page delivery
- Allows React UI with Laravel backend in the same project

**Constraint**
Inertia does not replace Laravel business logic or authorization.

---

## ADR-003 — React + TypeScript

**Status:** Accepted

**Decision**
Use React with TypeScript for the application UI.

**Reason**
- Modern component model
- Strong typing
- Better maintainability for ERP-scale interfaces
- Rich ecosystem

---

## ADR-004 — PostgreSQL

**Status:** Accepted

**Decision**
Use PostgreSQL as the primary application database.

**Reason**
- Strong relational integrity
- Good support for JSON data where needed
- Mature indexing and transaction capabilities
- Suitable for enterprise application workloads

---

## ADR-005 — Redis

**Status:** Accepted

**Decision**
Use Redis for queue/cache capabilities where appropriate.

**Reason**
- Suitable for Laravel queues
- Useful for caching and short-lived coordination
- Keeps asynchronous integration work out of web requests

---

## ADR-006 — ESB Access Through Laravel Only

**Status:** Accepted

**Decision**
The browser must never call ERP ESB APIs directly. All ESB communication goes through
Laravel.

**Reason**
- Protects credentials
- Centralizes authentication/token handling
- Centralizes validation, retries, logging, monitoring, and error normalization
- Prevents coupling the browser to an external ERP contract

---

## ADR-007 — Service/Action Layer

**Status:** Accepted

**Decision**
Controllers remain thin. Business operations and ESB orchestration are implemented in
Services/Actions.

**Reason**
- Testability
- Separation of concerns
- Easier reuse
- Prevents controllers from becoming integration/business-logic containers

---

## ADR-008 — Agent-Agnostic Documentation

**Status:** Accepted

**Decision**
Use `AGENT.md` and project documentation as the shared instructions for any AI coding
agent, instead of a tool-specific file such as `CLAUDE.md`.

**Reason**
The project may switch between Claude Code, Codex, Cursor, Gemini, or other agents during
development. Documentation must remain a property of the project, not of any single tool.

---

## ADR-009 — Single Shared ESB Token, Cached in Redis Behind a Lock

**Status:** Accepted

**Context**
The ESB documentation states: "A successful API login will log you out of any existing
ESB Core session using the same credentials, vice versa." Access tokens expire after
1 hour, refresh tokens after 24 hours.

**Decision**
The gateway performs at most one ESB login per credential set. The resulting access and
refresh tokens are cached in Redis and shared by all web requests and queue workers.
Login and refresh are serialized behind a lock so only one process authenticates at a
time; others wait for the result.

**Reason**
Logging in per request or per job would make concurrent operations invalidate each
other's tokens, producing intermittent `Unauthorized` failures with no obvious cause.
The single-session constraint makes a shared, coordinated token the only workable model.

**Consequences**
- ESB credentials must be dedicated integration credentials, never a person's account —
  a human logging into ESB Core would otherwise invalidate the gateway's session.
- Redis becomes a hard dependency of the ESB integration path, not just of queueing.
- Cached tokens are invalidated immediately on `EC03100001`.

**Open**
If the ESB team can issue a static API key for this project, that mechanism avoids the
single-session problem entirely and should be preferred. Marked `TBD` in
`docs/05-api-integration.md`.

---

## ADR-010 — ESB Base URLs Modeled as Named Families

**Status:** Accepted

**Decision**
ESB base URLs are configured as a map of named families (Core, Voucher, FnB backend,
POS), each with its own per-environment host. Every endpoint group declares the family
it belongs to. There is no single `ESB_BASE_URL`.

**Reason**
The vendor documentation declares different base URLs for different endpoint groups, and
the same logical environment maps to different hosts per family — for example production
Core is `https://services.esb.co.id/core` while production FnB backend is
`https://core-api.esb.co.id`. A single base URL cannot represent the real contract.

**Rejected**
- One `ESB_BASE_URL` with per-call path prefixes — hides that these are different hosts
  with potentially different availability, credentials, and error behavior.

**Consequences**
- ESB connection configuration and the `esb_connections` model must accommodate multiple
  base URLs per environment.
- Health checks and availability metrics are per family, not global.

---

## ADR-011 — spatie/laravel-permission for Roles and Permissions

**Status:** Accepted

**Decision**
Use `spatie/laravel-permission` for the role and permission model, with
`App\Authorization\Permission` and `App\Authorization\Role` as typed enums defining
the application's abilities and role grants.

**Reason**
- Authorization is a place where subtle bugs are security bugs; the package is mature
  and widely audited.
- Permission caching, guard handling, and the pivot schema are solved problems we would
  otherwise reimplement.
- `AGENT.md` forbids unjustified dependencies — this ADR is the justification.

**Constraints we impose on top of the package**
- A user holds **exactly one** role. The package supports several; `AssignUserRole`
  enforces one so the effective permission set is readable from a single value.
- Permissions are declared as a PHP enum, not as free-form strings, and the database is
  synced from that enum by `RolePermissionSeeder`. The enum is the source of truth.
- Super Admin holds no explicit permissions. It is granted every ability by a
  `Gate::before` hook so it stays correct when permissions are added later.
- `assignRole` is excluded from that hook, so the "nobody changes their own role" rule
  holds for everyone including Super Admin.

**Note**
The four roles come from `docs/06-authentication-authorization.md` and are still
**unconfirmed by the business**. They are seeded data, so changing the set is a seeder
change — but the enum cases are referenced in code, so removing one is a code change.

---

## ADR-012 — Immutable Audit Log

**Status:** Accepted

**Decision**
Privileged changes are recorded in an `audit_logs` table whose rows cannot be updated or
deleted. The model throws on both, and there is no `updated_at` column.

**Reason**
An audit trail that can be edited is not an audit trail. Enforcing immutability in the
model means a stray `update()` in future code fails loudly rather than quietly rewriting
history.

**Consequences**
- The change and its audit entry are written in one transaction, so a privileged change
  cannot succeed without leaving a trace.
- `actor_id` is nullable and uses `nullOnDelete`: console-initiated changes have no
  authenticated actor, and deleting a user must not destroy the record of what they did.
- Metadata describes the change only. Credentials, tokens, and full payloads never go in
  it (`docs/10-security.md`).
- Retention and archival for this table are still `TBD`.

---

## ADR-013 — ESB Connections Are Configuration, Not Records

**Status:** Superseded by ADR-018

**Context**
`docs/04-functional-requirements.md` lists an "ESB Connections" module that can
"configure logical ESB connection settings", and `docs/07-database-design.md` sketches an
`esb_connections` table holding a base URL, environment, and an auth configuration
reference.

**Decision**
ESB base URLs and credentials live in `config/esb.php` and the environment. There is no
`esb_connections` table and no way to edit a connection through the UI. The screen shows
what is configured, reports which families have a documented base URL, and lets a
permitted user probe connectivity.

**Reason**
- `docs/10-security.md` is explicit that secrets belong in environment or secret
  management infrastructure, never in database plaintext. A connections table would push
  ESB credentials toward the database.
- ADR-010 already models base URLs as configuration. A second, editable source of truth
  would let the two disagree, and the failure would be traffic going somewhere nobody
  intended.
- Changing which ERP environment the gateway talks to is a deployment decision with real
  consequences. Making it a form submission puts a production/staging switch one
  mis-click away.

**Consequences**
- Changing a base URL or credential is a deployment action.
- The screen is read-only plus a probe action. `esb-connections.manage` currently grants
  nothing beyond what `esb-connections.view` does; it is kept because a future
  non-secret setting may need it.
- The `esb_connections` sketch in `docs/07-database-design.md` is superseded.

**Revisit if**
The business needs to add ESB environments at runtime without a deploy. That would need
encrypted-at-rest credential storage and a key management story, neither of which exists
today.

**Revisited 2026-09-10** — the business does need exactly that. See ADR-018, which
supersedes this decision and supplies the encryption and audit story this section says is
missing.

---

## ADR-014 — Connectivity Probes Use a Documented Read, or Report That None Exists

**Status:** Accepted

**Decision**
Each base URL family may name a documented, read-only, cheap `GET` in `esb.probes` to
stand in for a health check. Core uses `GET /product/list?limit=1`. Families with no such
endpoint are configured as `null`, and the UI reports that connectivity cannot be probed.

A probe that fails with an authorization or validation error still reports the family as
**reachable**: the ESB answered, so the host is up and the network is fine. What failed is
the gateway credential's privileges.

**Reason**
The vendor documents no health endpoint. Inventing one would mean guessing at a path,
which `AGENT.md` forbids. Showing a green light for a family we cannot actually probe
would be worse than showing nothing, because it would be believed.

Conflating "credential lacks a privilege" with "host unreachable" sends whoever is
diagnosing it to check firewalls instead of ESB user permissions.

**Consequences**
- Probe coverage is partial and honest about it.
- The Core probe requires the gateway credential to hold product-read access on the ESB.
  A probe failure there means one of two things, and the reported outcome says which.


---

## ADR-015 — Base Classes on Proven Duplication, Not Speculatively

**Status:** Accepted

**Context**
The project needs a consistent default so both AI agents and human developers reach for
the same shared building blocks (controller, request, service/action bases) instead of
reinventing them per feature — while `docs/02-architecture.md` already establishes that
empty, speculative abstraction layers are worse than none. `ProductClient` is the
existing example: it deliberately has no Service wrapper because the read it performs
enforces no business rule of its own.

**Decision**
Base classes (`BaseFormRequest`, a Service/Action base, controller helpers) are
introduced when either a second real consumer already shares the behavior, or the
behavior is a cross-cutting concern every consumer of that type must get right
(authorization, transaction safety, response shape, redaction). They are not
pre-built ahead of a second consumer.

**Reason**
- Matches the senior-engineer instinct: abstract from real duplication, not from
  anticipated duplication that may never arrive.
- Keeps `ProductClient`'s existing reasoning intact instead of forcing a retrofit.
- Still gives agents and developers a consistent place to put shared logic once it
  is warranted, rather than leaving every future feature to decide from scratch.

**Consequences**
- Introducing `App\Http\Requests\BaseFormRequest` is authorized now, since
  `ListProductsRequest`'s helpers are the first of at least two known upcoming
  consumers (Purchase Request, etc.).
- A Service/Action base is deferred until the first write adapter (e.g. Purchase
  Request) is built — that is the first operation with an actual business rule to
  enforce.
- Do not treat "the docs told me to use a base class" as license to wrap a
  pass-through read or a single-use controller action in an unnecessary layer.

  ---

## ADR-016 — Multi-Provider Integration Model (Adapters + Canonical DTOs)

**Status:** Accepted

**Context**
The gateway was originally scoped around a single upstream, the ERP ESB. The project's
scope has been widened: the gateway will also connect to other external APIs, acting as
a source, a destination, or both, with data flowing in either direction between them and
the ESB. Integrating each new system directly against ESB-specific classes would make
every additional system's cost grow with the number of systems already connected
(a mapping to every other system), not with the complexity of that one system.

**Decision**
Every external system the gateway connects to, including the ESB, is modeled as a
**provider**. Each provider is integrated through:

- an `OutboundAdapter` and/or `InboundAdapter` (per `docs/02-architecture.md`) that
  knows only that provider's contract;
- a small set of internal **canonical DTOs** (`App\Domain\Canonical\...`) representing
  the business entities that actually move between systems;
- orchestration in Services/Actions (ADR-007) that reads/writes canonical DTOs and never
  contains a specific provider's request/response shape.

No adapter calls another adapter directly, and no adapter contains another provider's
contract.

**Reason**
- Keeps the cost of adding a provider linear (one adapter pair) instead of quadratic
  (a mapping to every already-connected system).
- Matches the isolation principle already in AGENT.md ("external ESB integration must be
  isolated behind dedicated integration classes") — this generalizes that principle
  rather than replacing it.
- Keeps ESB-specific knowledge (envelope format, error codes, token lifecycle) out of
  code that has nothing to do with ESB.

**Rejected**
- Point-to-point mapping between each pair of systems as they are added — simplest for
  exactly two systems, but does not scale past that, and was the situation this ADR
  exists to avoid.
- A fully generic, runtime-configurable "any API" mapping engine — over-engineered for a
  small, deliberately integrated set of systems; each provider still gets an explicit,
  reviewed adapter (see ADR-017 on sequencing).

**Consequences**
- `docs/02-architecture.md`'s directory structure gains `Integrations/Contracts/` and
  `Domain/Canonical/`.
- The existing ESB integration code (`App\Integrations\ESB\...` or equivalent) becomes
  the reference implementation of this shape; it does not need to be renamed or
  restructured until it stops satisfying the contract.
- A new table, `integration_providers`, replaces "ESB families" as the top-level registry
  concept; ESB's existing family model (ADR-010) becomes one provider's internal detail,
  not the top-level concept. ADR-010 is not superseded — it still governs how ESB's own
  base URLs are structured.
- A new table, `sync_transactions`, tracks business-record-level flow between providers,
  distinct from `integration_logs` (which stays a per-HTTP-call log). See
  `docs/07-database-design.md`.

---

## ADR-017 — Do Not Scaffold a Second Provider Speculatively

**Status:** Accepted

**Context**
ADR-016 establishes the shape future integrations should take. Without a real second
provider under contract, building `integration_providers`, `sync_transactions`, the
`Integrations/Contracts` interfaces, and `Domain/Canonical` DTOs now would be
abstraction built against a guess of what a second provider will need.

**Decision**
The provider/adapter/canonical-model shape in ADR-016 is the target architecture and
governs how any new integration is built. The generic infrastructure itself
(`integration_providers` table, `sync_transactions` table, `Contracts` interfaces,
`Domain/Canonical` namespace) is built when the first real second provider is
integrated — extracted from that concrete need — not ahead of it.

**Reason**
- Matches the existing project norm ("do not create empty abstraction layers just in
  case", `docs/02-architecture.md`).
- A contract designed against one real example (ESB) and speculation is more likely to
  need rework than one designed against two real examples.

**Consequences**
- `docs/04-functional-requirements.md` module 7 ("Provider & Sync Management") is marked
  foundational/not yet implemented, not a current build target.
- `docs/project-state.md` and `TODO.md` should not carry tasks to build this
  infrastructure until a second provider is named.
- When a second provider is named, the first task is generalizing the ESB adapter to fit
  the `OutboundAdapter`/`InboundAdapter` contracts, not building the contracts in the
  abstract first.

---

## ADR-018 — ESB Connections Are Editable Records, With Credentials Encrypted at Rest

**Status:** Accepted — supersedes ADR-013

**Context**
ADR-013 decided that ESB connections are configuration only, and closed with an explicit
condition: *"Revisit if the business needs to add ESB environments at runtime without a
deploy. That would need encrypted-at-rest credential storage and a key management story,
neither of which exists today."* That condition has now been met — the business does need
connections to be manageable at runtime (confirmed 2026-09-10).

`docs/04-functional-requirements.md` §2 describes the intended module: configure logical
ESB connection settings, test connectivity, display health/status, protect secrets, and
maintain an audit trail, with secret values never displayed in plaintext after storage.

`docs/10-security.md` forbids secrets in "database plaintext **unless explicitly required
and protected**". This ADR is that explicit requirement, and it defines the protection.

**Decision**
An `esb_connections` table exists and is editable through the UI, under these rules:

- The table holds, per base URL family: environment, base URL, auth mechanism, credential
  material, and an active flag.
- Credential columns are **encrypted at rest** using Laravel's `encrypted` cast, keyed by
  `APP_KEY` which stays in environment/secret management. No credential is ever written
  to the database in plaintext.
- Credentials are **write-only through the interface**. After storage, no route, page
  prop, API response, log line, or export returns the value. Reads return a masked
  presence indicator ("configured" / "not configured") only.
- Editing requires `esb-connections.manage`. That permission already exists and, until
  now, granted nothing beyond `esb-connections.view`; this is what it was reserved for.
- Every create, update, and delete writes an immutable `audit_logs` row in the same
  transaction (ADR-012), recording **which** field changed and by whom — never the value.
- `config/esb.php` remains the bootstrap source and the fallback. Where an active
  database row exists for a family, it wins. Precedence is one-directional and resolved
  in exactly one place; nothing ever writes back into config.
- Connectivity probing is unchanged and still governed by ADR-014.
- The production guard in `app:check-production-readiness` stays, and is evaluated
  against the **effective** connection after precedence is applied, not against
  `config/esb.php` alone. A non-production install pointed at the production ESB must
  still fail the check — a database row must not be a way around it.

**Reason**
- ADR-013 anticipated this exact revisit and named its two preconditions. Encryption at
  rest plus `APP_KEY` in secret management satisfies both.
- Encryption, write-only reads, and an audit row answer the three objections ADR-013
  raised: credentials are not in plaintext, the two sources of truth cannot silently
  disagree because precedence is explicit and one-directional, and a production/staging
  switch is no longer one mis-click away because it is audited, permission-gated, and
  still caught by the readiness check.
- Runtime configuration is a real operational need: adding an ESB environment should not
  require a deploy window.

**Consequences**
- The `esb_connections` section in `docs/07-database-design.md` returns from "superseded"
  to a specification to build.
- The existing test asserting that no `esb-connections.update` route exists is
  **inverted**: it becomes a test that the route exists and rejects a user without
  `esb-connections.manage`. Deleting that test outright would lose the authorization
  guarantee it was really protecting.
- `APP_KEY` becomes a data-loss risk. Losing it, or rotating it without re-encrypting,
  makes every stored credential unrecoverable. Key rotation needs a documented
  re-encryption path before this ships.
- Encrypted columns cannot be searched, indexed, or compared by value in SQL.
- A database backup now carries credential material, encrypted. Backup handling inherits
  the same protection requirements as the application.

**Rejected**
- Keeping ADR-013 as-is — rejected because the business need it named as its revisit
  condition has actually arrived.
- Storing only *references* to keys in an external secret manager — architecturally
  cleaner and the preferred end state, but no secret manager is provisioned for this
  project. Revisit when one exists; it would supersede the encryption approach here
  rather than the editability decision.
- Plaintext columns protected by permissions alone — rejected. A database backup, a
  read-only replica, or a stray query log would expose every credential, and none of
  those paths pass through the permission check.

**Open**
- Rotation and retention policy for stored credentials.
- Whether an external secret manager will be provisioned, which would change *how*
  credentials are stored but not *that* connections are editable.

