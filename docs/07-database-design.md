# Database Design

## Database

PostgreSQL.

## Initial Domains

### users

Application users and authentication data.

### roles / permissions

**Implemented** via `spatie/laravel-permission` (ADR-011), with its published schema.

The definitions live in `app/Authorization/Role.php` and
`app/Authorization/Permission.php`; `RolePermissionSeeder` syncs the tables from those
enums and prunes anything no longer in them, so a withdrawn ability cannot linger and
keep granting access. A user holds exactly one role.

### esb_connections

**Implemented** (ADR-018). ADR-013 previously rejected this table;
ADR-018 supersedes it, because the business needs ESB environments manageable at runtime
without a deploy — the exact condition ADR-013 named for revisiting itself.

Editable through the UI under `esb-connections.manage`. Credential columns are encrypted
at rest and write-only: after storage nothing returns the value, only a masked presence
indicator. Every change writes an immutable `audit_logs` row in the same transaction
(ADR-012), recording which field changed, never the value.

| Column | Notes |
| --- | --- |
| id | |
| family | Base URL family per ADR-010, e.g. `core`, `oms`. Unique with `environment`. |
| environment | e.g. `sandbox`, `production`. |
| base_url | |
| auth_mechanism | `static_token` or `login`, mirroring `ESB_AUTH_MECHANISM`. |
| credentials | Encrypted at rest (Laravel `encrypted` cast, keyed by `APP_KEY`). Never plaintext, never returned after storage. |
| is_active | An active row overrides configuration. An inactive row disables the family: calls are refused, with no fallback (ADR-020). |
| timestamps | |

`config/esb.php` stays the bootstrap source and the fallback. An active row for a family
wins over configuration; precedence is one-directional and resolved in exactly one place,
and nothing writes back into config. `app:check-production-readiness` evaluates the
**effective** connection after precedence, so a database row cannot be used to point a
non-production install at the production ESB.

Encrypted columns cannot be searched or indexed by value, and a database backup now
carries credential material — protected, but present.

### integration_endpoints

**Implemented.** Catalog of ESB operations, imported from `docs/esb/` by
`php artisan esb:import-catalog`. Nothing dispatches from this table; it exists so
operators can search 1262 operations without reading markdown.

| Column | Notes |
| --- | --- |
| id | |
| code | Unique, `{product}:{group}:{vendor name}@{version}`. Version is part of the identity: the vendor ships two distinct operations sharing a group and name. |
| product | `core` or `oms`. |
| group | Vendor group, e.g. `Master_Product`. |
| name | Vendor operation title. |
| version | Vendor apiDoc version. |
| method | Null for the vendor's narrative "Flow" pages, which document a process rather than a callable operation. |
| path | Null for the same reason. |
| is_operation | False for those documentation pages, so they cannot look invocable. |
| family | Base URL family (ADR-010), derived from the production base URL each group declares. |
| docs_url | Anchor into the vendor documentation. |

Indexed on `product`, `group`, `method`, `is_operation`, and `family`.

### integration_logs

**Implemented.** Operational history of every ESB call, written by `EsbClient`.

| Column | Notes |
| --- | --- |
| id | |
| correlation_id | Indexed. The reference printed on an error page, so a user's report can be traced. |
| operation | Indexed, e.g. `product.list`. |
| family | Base URL family (ADR-010). |
| environment | Which ESB environment the call went to. |
| method / path | Path only, never the full URL — recording the host on every row would make the log a way to discover the ESB (ADR-006). |
| outcome | `success` or an exception kind; see `App\Support\IntegrationOutcome`. |
| esb_code | The ESB status code, which is the only reliable discriminator. |
| safe_error_message | The safe summary shown to users, never the upstream message. |
| upstream_status | HTTP status. |
| duration_ms, retry_count | |
| user_id | Nullable, `nullOnDelete`. Null for queued or scheduled work. |
| started_at, created_at, updated_at | |

Indexed on `correlation_id`, `operation`, `outcome`, `esb_code`, plus
`(outcome, created_at)` and `(operation, created_at)` for the log viewer.

**No request or response payloads are stored.** The vendor's payloads carry personal and
financial data, and retention for this table is still `TBD`. Revisit payload storage only
with a documented retention and privacy decision.

### integration_providers

**Not yet implemented — build when the first real second provider is contracted**
(ADR-016). Registry of every external system the gateway connects to, including ESB.

| Column | Notes |
| --- | --- |
| id | |
| code | unique, e.g. `esb`, `pos`, `marketplace_x` |
| type | e.g. `rest_api`, `webhook_source` |
| direction | `inbound`, `outbound`, or `bidirectional` |
| auth_strategy | e.g. `oauth_password`, `api_key`, `hmac_signature` |
| status | active/inactive per environment |

Credentials themselves stay in config/environment/secret management, consistent with
ADR-013 — this table is metadata/registry only, never a place credentials are written.

### sync_transactions

**Not yet implemented — build when the first real second provider is contracted**
(ADR-016). Business-record-level trace of data moving between providers, distinct from
`integration_logs` (which is a per-HTTP-call log and stays that way).

| Column | Notes |
| --- | --- |
| id | |
| correlation_id | same pattern as `integration_logs.correlation_id` |
| entity_type | e.g. `product`, `order` |
| source_provider_id / destination_provider_id | FK to `integration_providers` |
| source_ref_id / destination_ref_id | the record's ID in each system |
| idempotency_key | unique; prevents duplicate writes on retry |
| status | `received`, `transformed`, `pushed`, `failed`, `dead_letter` |
| canonical_payload | snapshot, redacted per `docs/10-security.md` |
| attempt_count | |

### api_clients

**Implemented** (ADR-022). Machines allowed to call `/api/v1`, such as a POS.

| Column | Notes |
| --- | --- |
| id | |
| name | 100 chars, unique. Given to `api-client:create`. |
| token_hash | SHA-256 of the token, unique. The token itself is never stored. |
| is_active | `api-client:revoke` sets it false. Rows are kept so history still names them. |
| last_used_at | Written at most once a minute per client. |
| created_at / updated_at | |

### sales_pushes

**Implemented** (ADR-022). One row per sales transaction a POS sent to
`POST /api/v1/sales-data`, and what the ESB said about it (`[ESB-011]`).

| Column | Notes |
| --- | --- |
| id | Auto-increment. Orders pushes of one `sales_num`. |
| public_id | ULID, unique. Used by the API and the URLs, so ids do not reveal volume. |
| api_client_id | Nullable FK to `api_clients`, `nullOnDelete`. |
| sales_num / branch_code | Copied from `salesHead` (max 20 each, the vendor's sizes). |
| sales_date | From `salesHead.salesDate`; null when the POS sent `""`. |
| payload | `{"salesHead": …}` as received, `encrypted:array`. Holds customer names and payment details. Never shown or returned. |
| status | `queued`, `succeeded`, `rejected`, `failed`, `superseded` (`SalesPush::STATUSES`). |
| attempts / last_attempted_at | Attempts the job made. |
| failure / correlation_id / errors | Last failure: `EsbException::kind()`, the integration log correlation id, and the ESB's `[{field, message}]`. |
| esb_status / esb_sync_date / pushed_at | From the accepted response (`result.status`, `result.syncDate`) and when it was accepted. |
| created_at / updated_at | |

Indexed on `(sales_num, id)`, `(status, created_at)`, and `(branch_code, created_at)`.

Retention for this table, and especially for `payload`, is `TBD`.

### document_template_imports / _entries / _lines

**Implemented** (ADR-023). Document templates read from an uploaded spreadsheet, held
here before and after they are sent to the ESB (`[ESB-016]`). The file itself is not
stored.

`document_template_imports`, one per uploaded file:

| Column | Notes |
| --- | --- |
| id | |
| uploaded_by | Nullable FK to `users`, `nullOnDelete`. |
| filename / file_sha256 | As uploaded; the hash identifies the exact file. |
| status | `idle`, `validating`, `syncing`. Claimed with a conditional update before a job is queued, so one job runs per import. |
| problems | JSON list: rows that belong to no template. |
| last_error | Why the last validate or sync run stopped early. |
| validated_at / synced_at / timestamps | |

`document_template_import_entries`, one per template (rows sharing a name):

| Column | Notes |
| --- | --- |
| id / document_template_import_id | FK, `cascadeOnDelete`. |
| position | Order in the file; sync sends in this order. |
| name / type | `type` is `DocumentTemplateType`; null when the sheet's text matched none. |
| branch_codes / branch_ids | JSON. Codes merged across the template's rows; ids once validated. |
| status | `invalid`, `pending`, `ready`, `blocked`, `syncing`, `synced`, `failed`, `unconfirmed` (`DocumentTemplateImportEntry::STATUSES`). |
| problems | JSON list of reasons, shown on the page. |
| esb_template_id / correlation_id / synced_at | The ERP id once created; the integration log id of the last create attempt. |

`document_template_import_lines`, one per product row: `row_number` (as in Excel),
`product_code`, `product_name`, `unit`, `category` (sent as the line name), and
`product_detail_id` once validated.

Indexed on `(import, position)`, `(import, status)`, and `(entry, row_number)`.
Retention is `TBD`.

### audit_logs

**Implemented** (ADR-012). Security/business audit events, immutable.

| Column | Notes |
| --- | --- |
| id | |
| actor_id | Nullable FK to `users`, `nullOnDelete`. Null for console-initiated changes; nulled rather than cascaded so deleting a user never destroys the record of what they did. |
| action | Stable identifier from `App\Audit\AuditAction`. Renaming a value rewrites history — add a new case instead. |
| auditable_type / auditable_id | Nullable morph to the affected record. |
| metadata | JSON describing the change itself. Never credentials, tokens, or full payloads. |
| ip_address | 45 chars, IPv6-capable. |
| created_at | Indexed. There is no `updated_at` — rows are immutable. |

Indexed on `created_at` and on `(action, created_at)`.

The model throws on `updating` and `deleting`. The change and its audit entry are written
in one transaction, so a privileged change cannot succeed without leaving a trace.

Retention and archival for this table are `TBD`.

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
