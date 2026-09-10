# Security

## Threat Model

Primary risks:

- credential leakage;
- unauthorized ERP access;
- insecure direct ESB access;
- privilege escalation;
- injection;
- sensitive log exposure;
- replay/duplicate write operations;
- upstream compromise or malformed responses.

## Controls

Implemented unless noted.

- server-side ESB credentials, in environment/secret management rather than the database
  (ADR-013);
- authorization policies;
- input validation;
- CSRF protection for applicable web flows;
- secure cookies;
- TLS in production;
- rate limiting on every ESB-reaching route, keyed per user so one runaway script does
  not lock out the team. The limit is ours, not the vendor's: the ESB's own limits are
  undocumented, so the gateway must not let its users discover them the hard way;
- secret redaction;
- dependency updates;
- audit logging, immutable (ADR-012);
- least privilege — administering the gateway and invoking ESB operations are separate
  permissions, and nobody may change their own role;
- secure headers on every response, including the unauthenticated health check.

## Secrets

Secrets belong in environment/secret management infrastructure, never:

- Git;
- frontend bundles;
- database plaintext unless explicitly required and protected;
- logs;
- screenshots or documentation examples.

Use placeholders in documentation.

## Logging Redaction

Define a centralized redaction strategy for:

- Authorization headers;
- tokens;
- passwords;
- API keys;
- personal data;
- financial information.

## External Data

Treat all ESB responses as untrusted input. Validate structure before using data in
database writes or business logic.

## Security Review Checklist

`php artisan app:check-production-readiness` automates the configuration half of this
list and runs as part of `composer ci:check`. A checklist nobody executes is a checklist
that gets skipped on the release that needed it.

Automated:

- [x] production debug disabled — checked
- [x] HTTPS enforced — `APP_URL` scheme checked; `URL::forceScheme('https')` in
      production; HSTS sent over TLS in production
- [x] secure session cookies — `secure`, `http_only`, and `same_site` checked
- [x] `APP_KEY` present — checked
- [x] ESB credential present for the configured mechanism — checked
- [x] non-production install is not pointed at the production ESB — checked, and this is
      the one worth having: a staging install wired to the production ESB writes to real
      ERP data, and nothing would look wrong until it did

Covered by tests:

- [x] no secrets committed — `.env` is gitignored; staged changes are scanned before each
      commit
- [x] authorization tested — `RouteAuthorizationTest` posts directly to a privileged
      endpoint rather than going through the UI, so it proves the server rejects what the
      UI merely hides
- [x] error responses do not leak internals — `EsbExceptionRenderer` renders only the
      safe summary; tests assert upstream paths and document numbers never reach a
      response, and `/health` never reveals why a dependency failed
- [x] logs redact secrets — `TokenManagerTest` and `EsbClientTest` assert by reading the
      log file back that no token, password, or API key reaches it
- [x] rate limits reviewed — a per-user budget on every ESB-reaching route, because the
      vendor's own limits are undocumented
- [x] secure headers applied — `SecurityHeadersTest`

Still manual, before production:

- [ ] dependencies reviewed (`composer audit`, `npm audit`)
- [ ] backup and recovery process verified against real infrastructure
- [ ] rollback rehearsed (`docs/13-deployment.md`)
- [ ] penetration or review pass on the authenticated surface
