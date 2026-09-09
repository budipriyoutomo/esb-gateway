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

**Status:** Accepted

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
