# Testing Strategy

## Test Pyramid

### Unit

Test:

- business rules;
- data transformation;
- ESB response mapping;
- error classification.

### Feature

Test:

- authenticated access;
- authorization;
- validation;
- database behavior;
- Inertia responses;
- queued jobs.

### Integration

Use mocked/fake HTTP responses for ESB contract behavior where live ESB access is
unavailable.

Live ESB tests should be isolated and explicitly marked because they depend on external
infrastructure.

### Frontend components (ADR-024)

Run with `npm run test:ui` (`vp test`, Vitest bundled in vite-plus; config in the
`test` block of `vite.config.ts`). Files: `resources/js/**/*.test.{ts,tsx}`, next to
the component. Import test APIs from `vite-plus/test`.

Test:

- what renders, including empty and error states;
- keyboard and pointer interaction (`@testing-library/user-event`);
- basic accessibility — query by role and accessible name (`getByRole`).

Do not assert class names or snapshot markup. Behaviour that depends on Laravel
(routes, permissions, ESB) stays in PHPUnit feature tests.

## Minimum Coverage Expectations

For each new business capability:

- happy path;
- validation failure;
- authorization failure;
- upstream failure;
- timeout/retry behavior where applicable;
- persistence behavior.

## Test Data

Use factories/fixtures. Never use real production credentials or sensitive production
data in tests.

## Completion Checks

Before merge:

- relevant tests pass;
- formatter/linter passes;
- no debug statements;
- no secrets;
- migration is safe;
- docs updated where needed.
