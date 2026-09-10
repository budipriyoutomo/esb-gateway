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

As additional providers are connected, this should extend to per-provider health rather
than assuming ESB is the only source of activity.

### 2. ESB Connections

**Partly implemented.** The read-only display and the probe action exist today. Editable
connection settings are specified by ADR-018 and **not built yet** — the shipped screen
has no edit route, and there is currently no `esb_connections` table.

Capabilities:

- configure logical ESB connection settings — *planned, ADR-018*;
- test connectivity — *implemented, ADR-014*;
- display health/status — *implemented*;
- protect secrets — *planned, ADR-018*;
- maintain audit trail — *planned, ADR-018*.

Secret values must never be displayed in plaintext after storage. ADR-018 makes this
concrete: credentials are encrypted at rest and write-only, so after storage no route,
page prop, log line, or export returns the value — reads show a masked presence
indicator only. Editing requires `esb-connections.manage`, and every change writes an
immutable audit row recording which field changed, never the value.

Probing stays as ADR-014 defines it: a documented, read-only, cheap request per family
where one exists, and an honest "cannot be probed" where none does — never a green light
nobody verified.

This is the first, concrete instance of what ADR-016/ADR-017 generalize as
**provider connections**. A future provider gets the same shape, not a bespoke screen.

See `docs/07-database-design.md` (`esb_connections`) and `DECISIONS.md` (ADR-013 —
superseded, ADR-014, ADR-018).

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

This is per-HTTP-call operational history. It answers "did this call to the ESB
succeed?" It does not answer "did this business record get from system A to system B?"
— that is the `sync_transactions` concern, see item 7 below and
`docs/07-database-design.md`.

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
- integration configuration, per provider (ESB today, others later).

### 7. Provider & Sync Management (foundational, not yet implemented)

As soon as a second external system is contracted, this module governs it:

- registry of connected providers (`integration_providers`), their direction
  (inbound/outbound/bidirectional), and auth strategy;
- machine-to-machine credentials for providers that call into the gateway, separate
  from user authentication;
- visibility into in-flight and completed data flows between providers
  (`sync_transactions`): which record moved from where to where, its status, and
  whether it needs a manual replay;
- per-provider rate limiting and circuit-breaker state, mirroring the per-user ESB rate
  limit already in place.

Do not build the generic pieces of this module (registry table, canonical DTOs, adapter
contracts) speculatively before a second provider is actually contracted — see ADR-017.
Recording the concept now is so the *first* real second-provider integration follows this
shape from day one, instead of being bolted onto ESB-specific code.

## Future Integrations

Potential integration areas include:

- POS;
- purchasing;
- inventory;
- sales;
- master data;
- marketplaces or other external APIs that either supply data to be pushed into the ESB,
  or need data pushed to them from the ESB or other connected systems;
- other internal applications.

Do not implement future modules until their requirements and contracts are documented.
When one is, integrate it as a new provider (adapter pair + canonical mapping) per
`docs/02-architecture.md`, not as a special case inside the existing ESB integration
code.