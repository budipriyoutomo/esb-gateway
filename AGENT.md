# AGENT.md

## Purpose

This repository contains the ERP Gateway & Middleware application. This document is the
primary, agent-agnostic set of instructions for any AI coding agent (Claude Code, Codex,
Cursor, Gemini, or any other) working on this codebase. It must be read before making any
non-trivial change.

## Source of Truth

Read these in order before making non-trivial changes:

1. `AGENT.md`
2. `docs/project-state.md`
3. Relevant documents in `docs/`
4. `DECISIONS.md`
5. Existing source code, migrations, tests, and configuration

If documentation conflicts with the actual code, inspect the code and tests first, then
update the documentation to match reality (or flag the conflict if it looks like a bug).
 
## Project Summary

ERP Gateway is a single Laravel application that provides a secure web interface and
middleware/integration-hub layer between internal applications/users and multiple
external systems. The ERP ESB (Enterprise Service Bus) REST APIs are the first and
currently only connected system, but the project's foundational concept — recorded in
`docs/01-project-overview.md` and ADR-016/ADR-017 — is a gateway that can connect
additional external systems (as data sources, destinations, or both) without rewriting
ESB-specific code. The application has its own database and its own
authentication/authorization; it is not a thin proxy.

Do not build speculative infrastructure for a second provider before one is actually
contracted (ADR-017). What this concept changes today is naming and structure: new
integration code should assume "a provider" rather than hard-coding "the ESB" where the
distinction matters (see Mandatory Architecture Rules below).

The application is intentionally a single repository and deployment unit:

- Laravel 13 backend
- Inertia.js
- React + TypeScript frontend
- Vite
- Tailwind CSS
- shadcn/ui
- PostgreSQL
- Redis
- ERP ESB REST APIs (external)

## Engineering Standard

Work this codebase the way a senior full-stack Laravel + React engineer would —
whether the work is done by a human or an AI agent. Concretely:

- Understand the business reason for a change before writing code, not just the
  literal request. If the reason is unclear, ask or mark it `TBD` (see "Handling
  Ambiguity") rather than guessing.
- Favor boring, explicit, testable code over clever code. Optimize for the next
  person — human or agent — who reads this without any memory of this conversation.
- Think about failure modes, not just the happy path: timeouts, duplicate
  submissions, concurrent requests, malformed upstream responses, a user with the
  wrong permission.
- Leave the codebase slightly more consistent than you found it — but never as a
  side-effect of an unrelated task (see "Git Rules": no mixing refactors with
  feature work).
- A task is not complete because it compiles and the happy-path test passes. It is
  complete when a senior reviewer would approve the diff without asking "but what
  about X?" for an X that was foreseeable.

## Mandatory Architecture Rules

- React lives inside the Laravel repository under `resources/js`.
- Do not create a separate frontend repository/application.
- Do not use Livewire.
- Do not build the application UI as a Blade application.
- Use Inertia.js for Laravel-to-React page delivery.
- Laravel remains responsible for authentication, authorization, validation, business
  logic, and all ESB communication.
- React must never call ERP ESB endpoints directly.
- ESB credentials, secrets, and tokens must remain server-side.
- Controllers must be thin; business logic belongs in Services/Actions.
- External ESB integration must be isolated behind dedicated integration classes/services
  (not scattered across controllers or models).
- Do not introduce a new framework or major dependency without documenting the reason.
- Prefer existing project patterns over introducing a new pattern for a single feature.
- Any new external system integration is a **provider**: it gets its own adapter
  (`App\Integrations\<Provider>\...`) implementing the shared `OutboundAdapter` and/or
  `InboundAdapter` contracts (`docs/02-architecture.md`). It must not be wired directly
  into ESB-specific classes.
- Data that needs to move between two providers is translated through the canonical
  model (`App\Domain\Canonical\...`), never mapped directly from one provider's shape to
  another's.
- An adapter knows exactly one external system. It must never call another adapter.
  Cross-provider orchestration belongs in a Service/Action (ADR-007), consistent with
  the existing rule that controllers stay thin.
- Do not scaffold `Integrations/Contracts`, `Domain/Canonical`, `integration_providers`,
  or `sync_transactions` speculatively. Build them when a second provider is actually
  being integrated, using the first provider (ESB) as the pattern to generalize from.

## Backend Rules

- Follow Laravel conventions.
- Use Form Requests for non-trivial validation.
- Use Policies/Gates for authorization.
- Use Services/Actions for business operations.
- Use Jobs for long-running, retryable, or asynchronous work.
- Use API Resources when a stable JSON API representation is required.
- Use database transactions around multi-write business operations where appropriate.
- Never log secrets, passwords, authorization headers, access tokens, or sensitive
  payloads.
- External calls must have timeouts, controlled retries, and useful error handling.
- Never assume an external API is always available.
- Store timestamps consistently and handle timezone explicitly.

## Frontend Rules

- TypeScript is required for new React code.
- Keep pages focused on presentation and user interaction.
- Put reusable UI in `components/`.
- Put reusable business-facing hooks in `hooks/`.
- Put HTTP/data access logic behind a small number of well-defined client utilities when
  needed (not scattered ad-hoc fetch calls).
- Prefer server-driven Inertia props for page data where appropriate.
- Avoid duplicating backend business rules in React.
- UI must be responsive and accessible.
- Use the established component system (shadcn/ui + Tailwind) consistently.

## Database Rules

- All schema changes use migrations.
- Never modify production schema manually when a migration can represent the change.
- Add indexes based on query patterns, not automatically on every column.
- Foreign keys and constraints should reflect actual business rules.
- Avoid storing derived values unless there is a documented performance or audit reason.
- Never delete production data as part of development/testing without explicit
  confirmation.

## ESB Integration Rules

- Keep endpoint definitions, authentication behavior, request mapping, response mapping,
  and error handling documented in `docs/05-api-integration.md`.
- Do not invent undocumented ESB fields or status meanings.
- If the ESB contract is unknown, mark it as `TBD` and isolate the assumption.
- Normalize ESB failures into application-level errors without losing diagnostic context.
- Preserve correlation/request IDs when available.
- Log metadata needed for troubleshooting while redacting secrets and sensitive data.
- External API changes must be reflected in documentation and tests.

## Base Class Conventions

Shared base classes remove *proven* duplication — they are not pre-built for
hypothetical future use. This still follows `docs/02-architecture.md`: "do not
create empty abstraction layers 'just in case.'" A base class earns its place when
either (a) at least two real consumers already share the behavior, or (b) the
behavior is a cross-cutting concern every consumer of that type must get right
(authorization, transaction safety, response shape, redaction).

Default expectation for new work — check whether one of these already fits before
writing a one-off class:

- `App\Http\Controllers\Controller` — base for all controllers. Add a shared helper
  here (e.g. a standard Inertia-props pagination shape) once two controllers have
  hand-rolled the same shape; retrofit the first when the second is built.
- `App\Http\Requests\BaseFormRequest` — shared query-to-DTO helpers
  (`validatedString()`, `validatedInt()`, etc.), currently duplicated in
  `ListProductsRequest`. Extract this the moment a second Form Request needs the
  same helpers — the duplication is already real, not speculative.
- `App\Services\BaseService` / the Service-Action pattern from ADR-007 — a thin base
  providing a DB-transaction helper and a consistent logging hook, for business
  operations that coordinate more than one model or wrap an ESB write. **Do not**
  wrap a pure pass-through read behind a Service just to extend this base —
  `ProductClient` deliberately has none, and that reasoning still holds. Extend the
  base only when the operation has an actual business rule to enforce.
- `App\Integrations\ESB\Clients\EsbClient` — already the shared transport for every
  ESB adapter (ADR-006). New adapters build on this; they do not reimplement
  retry/auth/logging.
- React: shared hooks in `resources/js/hooks`, shared UI in `resources/js/components`
  (`docs/08-frontend-guidelines.md`). Extract a hook/component once a second page
  needs the same behavior, not preemptively.

When a new base class changes how future features are structured project-wide, it
is ADR-worthy — record it in `DECISIONS.md`. A small extraction like
`BaseFormRequest` is a normal refactor and only needs a note in
`docs/project-state.md`.

## Security Rules

- Follow least privilege.
- Validate all external and user input.
- Use authorization checks for every protected operation.
- Keep secrets in environment/configuration, never source code.
- Do not commit `.env` files or credentials.
- Do not expose internal ESB URLs or credentials to the browser unless explicitly
  intended and reviewed.
- Apply rate limiting where appropriate.
- Treat ESB responses as untrusted external data.
## Testing Rules — Test-Driven Development (Mandatory)

This project is built test-first (`TODO.md` — "Built test-first throughout").
Continue that discipline for every new class, method, or bug fix:

1. **Red** — write a failing test that expresses the requirement or reproduces the
   bug, before writing the implementation. For a bug fix, the failing test must
   reproduce the bug against the current code first.
2. **Green** — write the smallest implementation that makes the test pass. Resist
   adding behavior the test doesn't demand yet.
3. **Refactor** — clean up implementation and test alike once green, without
   changing behavior. Re-run tests after every refactor step.

Rules:

- Do not write production code for which no test failed first. If a test genuinely
  cannot precede the implementation (rare — e.g. pure visual layout), say so
  explicitly in the task plan instead of silently skipping TDD.
- A diff that adds behavior with no corresponding new or updated test is incomplete,
  full stop.
- Follow the test pyramid already established: Unit for isolated logic (mappers,
  classifiers, DTOs), Feature for HTTP/Inertia behavior, ESB integration tests
  against `Http::fake()` — see `docs/12-testing.md`.
- A regression test for a fixed bug stays in the suite permanently, even after the
  fix — do not delete it once green.

Test types:

- Unit tests for isolated business logic.
- Feature tests for Laravel application behavior.
- Integration tests for ESB adapters using mocks/fakes where real ESB access is
  unavailable.
- Frontend tests for complex interactions when the project test setup supports them.

Before considering a task complete:

1. Confirm the test existed and failed before the implementation (state this in the
   task plan/handoff, don't just assert it).
2. Run relevant tests.
3. Run formatting/linting checks.
4. Inspect the diff.
5. Update documentation/state if architecture or project progress changed.

## Git Rules

- Make small, focused commits.
- Do not mix unrelated refactors with feature work.
- Never rewrite shared history unless explicitly requested.
- Never commit secrets.
- Use clear commit messages.
- Before committing, inspect `git diff` and `git status`.

## Development Workflow

For each task:

1. Read the relevant documentation.
2. Inspect existing implementation.
3. State or record the implementation plan when the task is non-trivial.
4. Implement the smallest coherent change.
5. Add/update tests.
6. Run checks.
7. Review the diff.
8. Update `docs/project-state.md` and relevant docs when needed.

## Handling Ambiguity

Do not guess silently. If a requirement materially affects architecture, data integrity,
security, or the ESB contract:

- identify the uncertainty;
- choose the safest temporary assumption if implementation must proceed;
- mark it as `TBD`;
- avoid hard-coding the assumption into irreversible structures;
- document what needs confirmation.

## Documentation Maintenance

Documentation is part of the product. Update it when:

- architecture changes;
- an ESB endpoint contract changes;
- a database model changes materially;
- authentication/authorization behavior changes;
- deployment changes;
- a significant architectural decision is made;
- a known limitation is discovered or resolved.

## Forbidden by Default

- Livewire for application UI
- Separate React/Next.js frontend project
- Direct browser-to-ESB requests
- Credentials in source code
- Unreviewed destructive migrations
- Large speculative refactors
- Dependency additions without justification
- Silent changes to architecture

## Completion Standard

A task is complete only when the implementation, tests, documentation, and
`docs/project-state.md` are all consistent with each other.
