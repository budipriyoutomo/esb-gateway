# Development Workflow

## Start of Task

1. Read `AGENT.md`.
2. Read `docs/project-state.md`.
3. Read relevant domain/integration docs.
4. Inspect existing code and tests.
5. Identify assumptions.

## Planning

For non-trivial work, produce a concise plan before implementation:

- affected files;
- data changes;
- integration changes;
- tests;
- documentation.
## Implementation

- Write the failing test first (see `AGENT.md` — Testing Rules / TDD). Confirm it
  fails for the expected reason before writing implementation code.
- Prefer small changes.
- Reuse established patterns — check `AGENT.md` "Base Class Conventions" before
  writing a one-off class.
- Avoid unrelated refactoring.
- Keep architecture constraints intact.

## Verification

Run:

- relevant automated tests;
- formatter;
- linter/type checks where configured;
- frontend build when frontend code changes.

Then inspect:

- `git diff`;
- `git status`.

## Documentation

Update:

- `docs/project-state.md` for progress;
- relevant technical docs for changed behavior;
- `DECISIONS.md` for durable architecture decisions.

## Handoff

Before stopping work, record:

- what was completed;
- what remains;
- known issues;
- assumptions;
- commands/checks run.

The next agent must be able to continue from repository state without conversation
history.

## Rules

- Do not start coding if the requirement is unclear — clarify or mark it `TBD` first.
- Do not change established architecture without recording an ADR in `DECISIONS.md`.
- Every feature must have tests.
