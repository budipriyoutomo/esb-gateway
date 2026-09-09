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
