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
