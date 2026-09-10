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

**Superseded by ADR-013 — this module is read-only plus a probe action.**

There is no `esb_connections` table and no edit route. ESB base URLs and credentials
live in `config/esb.php` and the environment (ADR-010, ADR-013); changing one is a
deployment action, not a form submission.

Implemented capabilities:

- display which base URL families are configured, for the current environment;
- probe connectivity via a documented, read-only, cheap request per family (ADR-014),
  where one exists;
- report "cannot be probed" honestly for families with no documented health/read
  endpoint, rather than showing a fake status.

Explicitly out of scope:

- editing a base URL or credential through the UI;
- storing credentials in the database, in any form;
- a dedicated "esb_connections" audit trail — there is nothing to audit because there
  is nothing to change here. The probe action itself is covered by the existing
  permission check and `integration_logs`, not a separate audit trail.

See `docs/07-database-design.md` (`esb_connections` — superseded) and `DECISIONS.md`
(ADR-013, ADR-014).

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
