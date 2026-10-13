# ERP Gateway Agent Documentation

This package contains the agent-agnostic documentation for the ERP Gateway project
(Laravel 13 + Inertia.js + React + TypeScript + PostgreSQL + Redis). The gateway is an
**integration hub with its own database and its own authentication**, connecting
internal applications/users to the ERP ESB and, over time, to other external systems
that act as additional data sources or destinations. The ESB is the first connected
system and the reference implementation of how any provider is integrated — it is not
the only one this project is designed for. See `docs/01-project-overview.md` and
`DECISIONS.md` (ADR-016, ADR-017) for the multi-provider concept.

Copy the contents of this folder into the root of your Laravel project repository.

## Files

- `AGENT.md` — mandatory instructions for any AI coding agent. Read this first.
- `TODO.md` — ordered kickoff task list, from empty repository to running application.
- `DECISIONS.md` — durable architecture decisions (ADRs).
- `docs/project-state.md` — current project/handoff state. Update this as work
  progresses so any agent (or human) can pick up where the last one left off.
- `docs/01-project-overview.md` — project purpose and scope (multi-provider integration
  hub; ESB is the first provider).
- `docs/02-architecture.md` — architecture, request flows, and the provider/adapter/
  canonical-model shape used for every integration, ESB included.
- `docs/03-technology-stack.md` — technology choices.
- `docs/04-functional-requirements.md` — functional scope.
- `docs/05-api-integration.md` — ERP ESB contract and integration rules (the first
  provider's contract; a future provider gets its own equivalent document).
- `docs/esb/esb-core-endpoints.md` — generated catalog of all ESB Core operations.
- `docs/esb/esb-oms-endpoints.md` — generated catalog of all ESB OMS operations.
- `docs/esb/generate-catalog.py` — regenerates the two catalogs from the vendor sources
  (OpenAPI for ESB Core, apiDoc for ESB OMS).
- `docs/06-authentication-authorization.md` — application user authentication and
  authorization. Machine-to-machine auth for external providers calling into the gateway
  is not written yet; it arrives with the first inbound provider (ADR-017).
- `docs/07-database-design.md` — data model, including the provider-agnostic
  `integration_providers` and `sync_transactions` tables alongside the ESB-specific
  tables.
- `docs/08-frontend-guidelines.md` — React/Inertia UI rules.
- `docs/09-backend-guidelines.md` — Laravel implementation rules.
- `docs/10-security.md` — security requirements.
- `docs/11-logging-monitoring.md` — observability requirements.
- `docs/12-testing.md` — testing strategy.
- `docs/13-deployment.md` — deployment guidance.
- `docs/14-development-workflow.md` — agent workflow and handoff.

## Why AGENT.md instead of CLAUDE.md

This documentation is intentionally tool-agnostic so you can switch between Claude Code,
Codex, Cursor, Gemini, or any other AI coding agent mid-project. Every agent should read
`AGENT.md` → `docs/project-state.md` → relevant `docs/*.md` → `DECISIONS.md` before
writing code, and should update `docs/project-state.md` after finishing a unit of work.

## Important

The ESB API contract was imported on 2026-09-09 from
https://developers.esb.co.id/esb-core/ and https://developers.esb.co.id/esb-oms/, and the
catalogs were regenerated on 2026-09-30 — 1262 operations in total (1224 Core, 38 OMS). `docs/05-api-integration.md` records the authentication flow,
response envelope, pagination, error-code catalog, and retry/idempotency rules.

Two things still need answers from the ESB team before ESB integration work can be
finalized: which environment and credentials this project is entitled to, and whether a
static API key is available instead of the login flow. The ESB permits only one active
session per credential, so this choice materially affects the design — see ADR-009.

Regenerate the endpoint catalogs with `docs/esb/generate-catalog.py` when the vendor
documentation changes; do not edit them by hand.

**Scope note (2026-09-10):** the project's foundational concept was widened from
"ESB gateway" to "multi-provider integration hub" — see ADR-016 and ADR-017. This is a
documentation/architecture decision recorded ahead of any second provider being named;
nothing about the existing ESB implementation changes as a result, and no new provider
should be scaffolded speculatively until one is actually contracted.