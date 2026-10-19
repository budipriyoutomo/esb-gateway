# Authentication & Authorization

## Application Authentication

The Laravel application authenticates users through the application's chosen
authentication mechanism (to be confirmed during scaffolding — e.g. Laravel's standard
session-based authentication).

## Authorization

Use:

- Roles for broad responsibility grouping.
- Permissions for specific actions.
- Policies/Gates for resource-level authorization.

Never rely on frontend visibility as the security boundary. Every protected operation
must be checked server-side.

## Implemented Roles

Implemented via `spatie/laravel-permission` (ADR-011). The definitions live in
`app/Authorization/Role.php` and `app/Authorization/Permission.php`; the database is
synced from those enums by `RolePermissionSeeder`.

**These four roles still require business confirmation. Do not assume they are final.**

| Role | Grants |
| --- | --- |
| Super Admin | Every ability, via a `Gate::before` hook |
| IT Admin | Administers the gateway: users, roles, connections, catalog, settings, audit log |
| Integration Operator | Runs ERP transactions: invoke ESB operations, test connections, read logs, view and retry sales pushes |
| Viewer | Read-only: dashboard, connections, catalog, integration logs |

Rules that the implementation enforces:

- A user holds **exactly one** role.
- A user with no role can do nothing — authentication alone grants no access.
- Nobody may change their own role, Super Admin included. `assignRole` is deliberately
  excluded from the Super Admin `Gate::before` short-circuit, so the no-self-promotion
  rule holds for everyone. Without that exclusion an IT Admin could quietly promote
  themselves and erase the separation between administering the gateway and running ERP
  transactions through it.
- Administering the gateway and invoking ESB operations are separate responsibilities:
  IT Admin cannot invoke ESB operations, and Integration Operator cannot manage users.
- Approving purchasing is separate again. Authorizing or rejecting a purchase request
  needs `purchase-requests.approve` **and** `esb.invoke`. No role is granted
  `purchase-requests.approve` yet, so only Super Admin can do it (2026-09-25). Which role
  approves purchase requests is for the business to confirm. Granting it is a one-line
  change in `Role::permissions()` followed by re-running `RolePermissionSeeder`.
- The ERP records the gateway's shared service credential (ADR-009) as the approver.
  The gateway's audit log (`purchase-request.authorized` / `.rejected` /
  `.decision-unconfirmed`) is the only record of which person made the decision.
- Raising purchasing documents is separate from approving them. Creating, editing,
  deleting, and importing purchase requests needs `purchase-requests.manage` **and**
  `esb.invoke` (2026-09-30), so the person who raises a request need not be the one who
  approves it. No role holds `purchase-requests.manage` yet; only Super Admin can use it
  until the business says which role raises purchase requests. These writes are audited
  as `purchase-request.created` / `.updated` / `.deleted` / `.imported`, and as
  `.write-unconfirmed` when the ESB gave no definite answer.
- Purchase invoices have their own permissions (2026-09-30). Authorizing or rejecting
  one needs `purchase-invoices.approve` **and** `esb.invoke`. Deleting one needs
  `purchase-invoices.manage` **and** `esb.invoke`. Neither is implied by the purchase
  request permissions: approving a supplier invoice commits the company to paying it,
  which is a finance decision. No role holds either yet, so only Super Admin can use them
  until the business decides. Audited as `purchase-invoice.authorized` / `.rejected` /
  `.deleted`, and `.write-unconfirmed`.
- Purchase orders have their own permissions (2026-10-19, `[ESB-017]`). Authorizing or
  rejecting one needs `purchase-orders.approve` **and** `esb.invoke`; a rejection must
  carry a reason. Deleting one needs `purchase-orders.manage` **and** `esb.invoke`.
  Neither is implied by the purchase request or invoice permissions: authorizing an
  order commits the company to a supplier. No role holds either yet, so only Super Admin
  can use them until the business decides. Audited as `purchase-order.authorized` /
  `.rejected` (with the reason) / `.deleted`, and `.write-unconfirmed`.
- Simple purchases have their own permissions (2026-10-19, `[ESB-018]`), on the same
  rules as purchase orders: `simple-purchases.approve` + `esb.invoke` to authorize or
  reject (with a reason), `simple-purchases.manage` + `esb.invoke` to delete. Not
  implied by any other purchasing permission. No role holds either yet. Audited as
  `simple-purchase.authorized` / `.rejected` / `.deleted` / `.write-unconfirmed`.
- Document templates (2026-10-15, `[ESB-016]`). Reading them needs `esb.invoke`.
  Creating one needs `document-templates.manage` **and** `esb.invoke`: a template
  decides which products every branch it is assigned to can request. No role holds
  `document-templates.manage` yet, so only Super Admin can create templates until the
  business decides. Audited as `document-template.created`, and
  `document-template.write-unconfirmed` when the ESB gave no definite answer.
  Importing templates from a spreadsheet (ADR-023) needs the same two permissions.
  It is audited as `document-template-import.uploaded`, `.validation-requested`, and
  `.sync-requested`. Each template the sync creates is audited as
  `document-template.created`, with the person who started the sync as the actor.
- Sales pushes (2026-10-01, ADR-022). `sales-pushes.view` shows the history of sales
  transactions POS clients sent; IT Admin and Integration Operator hold it.
  `sales-pushes.retry` queues a failed or rejected push again; only Integration Operator
  holds it. Neither shows payment card details. Retries are audited as
  `sales-push.retried`.

### API clients

`/api/v1` is for machines, not people (ADR-022). A caller sends
`Authorization: Bearer <token>`, where the token was issued by
`php artisan api-client:create <name>` and is shown once. Only its SHA-256 is stored.
`php artisan api-client:revoke <name>` stops it working. Both are audited
(`api-client.issued` / `.revoked`). A logged-in web session is not accepted on `/api`,
and an API client token gives no access to the web UI. Requests are rate limited per
client (`SALES_PUSH_RATE_LIMIT_PER_MINUTE`, default 120).

### Bootstrapping the first administrator

Granting a role through the UI requires already holding `users.manage`, so a fresh
installation has no way to promote anyone. Use the console:

```
php artisan user:assign-role someone@example.com "Super Admin"
```

The resulting audit entry has a null actor, which is how console-initiated changes are
distinguished from changes made by a signed-in administrator.

Note that `user:assign-role` promotes an account that already exists; it does not create
one. Because Fortify's registration feature was removed at scaffold time, a production
installation's first account has to be created deliberately — with `php artisan tinker`
or an equivalent — and then promoted with the command above.

### Seeded accounts for local and CI work

A production installation must not contain accounts with known passwords, so
`DatabaseSeeder` creates no users and `DatabaseSeederTest` keeps it that way. That leaves
local development with nothing to sign in as, which `DevUserSeeder` fills:

```
php artisan db:seed --class=DevUserSeeder
```

It creates one account per role — `superadmin@`, `itadmin@`, `operator@`, and `viewer@`
`maharasa.test` — all sharing the password `password`. Re-running it repairs the existing
accounts rather than duplicating them.

Two properties keep this from becoming a production weakness, and neither should be
relaxed to make the seeder more reusable:

- It is never called from `DatabaseSeeder`, so `php artisan db:seed` still creates zero
  accounts.
- It throws in the `production` environment rather than seeding.

Roles are assigned through `AssignUserRole`, the same action the UI and
`user:assign-role` use, so the one-role-per-user rule and the audit trail hold here too.
Assigning a role matters as much as creating the account: `/dashboard` is gated on
`dashboard.view`, so an account seeded without a role would authenticate and then be
refused, which reads as a broken login rather than as missing authorization.

### Frontend

`HandleInertiaRequests` shares `auth.role` and `auth.permissions`, and the
`usePermissions()` hook reads them so the UI can hide controls a user cannot act on.
This is a usability affordance only — every route and action is authorized server-side.
`resources/js/types/permissions.ts` mirrors the PHP enums and is kept honest by
`PermissionTypesInSyncTest`, which fails the build if the two drift.

## ESB Authentication

ESB authentication is separate from application user authentication.

The user session must not be used as a substitute for ESB credentials unless the ESB
explicitly supports that model (TBD).

ESB credentials/tokens:

- server-side only;
- encrypted/protected according to deployment requirements;
- never returned to React;
- never written to logs.

## Session Security

Use secure cookies and appropriate:

- CSRF protection;
- session expiration;
- secure transport;
- same-site policy;
- logout behavior.

Exact settings depend on deployment topology.
