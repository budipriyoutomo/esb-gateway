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
middleware layer between internal applications/users and the ERP ESB (Enterprise Service
Bus) REST APIs.

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

## Testing Rules

Every meaningful feature should include appropriate automated tests:

- Unit tests for isolated business logic.
- Feature tests for Laravel application behavior.
- Integration tests for ESB adapters using mocks/fakes where real ESB access is
  unavailable.
- Frontend tests for complex interactions when the project test setup supports them.

Before considering a task complete:

1. Run relevant tests.
2. Run formatting/linting checks.
3. Inspect the diff.
4. Update documentation/state if architecture or project progress changed.

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
