# Functional Requirements

## Initial Modules

### 1. Dashboard

**Implemented**, from `integration_logs` over a 24-hour window.

Show operational summaries such as:

- ESB availability/health;
- recent requests;
- success/failure counts;
- queued jobs;
- recent errors;
- integration activity.

### 2. ESB Connections

Capabilities:

- configure logical ESB connection settings;
- test connectivity;
- display health/status;
- protect secrets;
- maintain audit trail.

Secret values must never be displayed in plaintext after storage.

### 3. API/Endpoint Catalog

Maintain metadata for supported integrations:

- name;
- module;
- endpoint identifier;
- HTTP method;
- description;
- status;
- authentication requirement;
- request/response documentation reference.

**Implemented.** `php artisan esb:import-catalog` parses the generated files in
`docs/esb/` into `integration_endpoints`, and the UI makes all 401 operations searchable
by name, path, group, method, and family. The import is idempotent and prunes operations
the vendor has withdrawn.

The catalog remains documentation/configuration: nothing dispatches from the table, and
it has no write routes. `docs/esb/` stays the source of truth, so editing a row would
create a second one that silently disagrees.

Version is part of an operation's identity, not decoration — the vendor ships two
distinct operations at `Report`/`get-sales-information` that differ only by version,
method, and host.

### 4. Integration Logs

**Implemented.** Written by `EsbClient` on every call, with a viewer filtered by
correlation ID, operation, outcome, family, and date range.

Record useful metadata:

- correlation ID;
- operation;
- endpoint identifier;
- request time;
- duration;
- status;
- response status;
- error classification.

Sensitive payload fields must be redacted.

### 5. User & Access Management

Initial capabilities:

- users;
- roles;
- permissions;
- authorization policies;
- audit trail for privileged changes.

### 6. System Configuration

Configuration should distinguish:

- application configuration;
- environment secrets;
- business configuration;
- ESB integration configuration.

## Future Integrations

Potential integration areas include:

- POS;
- purchasing;
- inventory;
- sales;
- master data;
- other internal applications.

Do not implement future modules until their requirements and ESB contracts are
documented.
