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
| Integration Operator | Runs ERP transactions: invoke ESB operations, test connections, read logs |
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

### Bootstrapping the first administrator

Granting a role through the UI requires already holding `users.manage`, so a fresh
installation has no way to promote anyone. Use the console:

```
php artisan user:assign-role someone@example.com "Super Admin"
```

The resulting audit entry has a null actor, which is how console-initiated changes are
distinguished from changes made by a signed-in administrator.

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
