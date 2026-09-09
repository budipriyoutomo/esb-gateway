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

## Suggested Initial Roles

These are starting points and require business confirmation:

- Super Admin
- IT Admin
- Integration Operator
- Viewer

Do not assume these roles are final.

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
