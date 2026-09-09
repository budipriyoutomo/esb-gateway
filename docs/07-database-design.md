# Database Design

## Database

PostgreSQL.

## Initial Domains

### users

Application users and authentication data.

### roles / permissions

Authorization model if a role-permission package/model is adopted.

### esb_connections

Logical configuration for ESB connections.

Potential fields:

- id
- name
- base_url
- environment
- auth configuration reference
- is_active
- created_at
- updated_at

Do not store plaintext secrets.

### integration_endpoints

Catalog of supported ESB operations.

Potential fields:

- id
- code
- name
- module
- method
- path
- description
- is_active
- created_at
- updated_at

### integration_logs

Operational integration history.

Potential fields:

- id
- correlation_id
- endpoint_id
- direction
- status
- upstream_status
- duration_ms
- error_code
- safe_error_message
- request_metadata
- response_metadata
- created_at

Payload storage must be carefully evaluated for privacy, volume, and retention.

### audit_logs

Security/business audit events.

Potential fields:

- id
- actor/user reference
- action
- resource type/id
- metadata
- created_at

## Timezone Convention

`APP_TIMEZONE` is set explicitly to `UTC`. All timestamps are stored in UTC.

The ESB is the reason this needs stating rather than being left to the framework
default. ESB responses carry a `timestamp` formatted `Y-m-d H:i:s` with **no offset**,
and the vendor's `logInfo.loginTime` examples carry `+07:00` — the ESB reports
Asia/Jakarta wall-clock time. A bare ESB timestamp parsed without an explicit timezone
would silently be interpreted as UTC and land seven hours off.

Rules:

- Parse every ESB timestamp as `Asia/Jakarta`, then convert to UTC before persisting.
- Never let an ESB timestamp adopt the application default implicitly.
- Date-only parameters sent to the ESB use `Y-m-d`; the ESB rejects other formats with
  `EC03100036`.
- Present times to users in Asia/Jakarta.

See `docs/05-api-integration.md` for the envelope details.

## General Rules

- Use UUIDs only if the project confirms that choice; otherwise use Laravel's standard
  auto-incrementing keys.
- Use timestamps consistently.
- Index correlation IDs and frequently filtered operational fields.
- Consider partitioning/retention strategy if integration logs grow significantly.
- Never create a migration that silently destroys production data.

## Schema Evolution

All changes go through migrations and tests.
