# Backend Guidelines

## Controllers

Controllers should:

1. receive the request;
2. authorize;
3. validate;
4. call a Service/Action;
5. return the appropriate response.

Avoid business logic in controllers.

## Services/Actions

Use Services/Actions for:

- orchestration;
- business rules;
- ESB calls;
- multi-step operations.

Keep responsibilities focused — one Service/Action per coherent operation.

## Form Requests

Use Form Requests for complex validation and authorization-related request preparation.

## Models

Models should represent persistence and relationships. Avoid putting large integration
work or business orchestration logic directly in models.

## Jobs

Use Jobs for:

- long-running ESB operations;
- retryable background work;
- batch operations;
- tasks that should not block a web request.

Jobs must be designed with idempotency and retry behavior in mind.

## Exceptions

Use domain/integration exceptions where they improve clarity. Do not leak raw upstream
exception details to end users.

## Transactions

Use transactions when multiple database writes must succeed or fail together. Do not
hold transactions open across external HTTP calls (e.g. ESB requests).

## HTTP Integration

For ESB calls:

- explicit connect/overall timeouts;
- controlled retries;
- request IDs;
- safe logging;
- response validation;
- normalized errors.

## Configuration

Read environment-dependent values through Laravel configuration (`config/*.php`). Do not
call `env()` throughout the codebase outside of config files.
