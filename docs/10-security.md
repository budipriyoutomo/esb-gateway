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

### The one explicit exception: stored ESB connection credentials

ADR-018 is the "explicitly required and protected" case above. ESB connections are
editable at runtime, so a credential can be stored in `esb_connections`. The protection
that makes this acceptable is not optional, and all of it is asserted by tests:

- **Encrypted at rest.** `EsbConnection` casts `credentials` with Laravel's
  `encrypted:array`, keyed by `APP_KEY`, which stays in environment/secret management.
  The test reads the raw column through the query builder rather than the model, because
  asserting through the model would pass against a plaintext column too.
- **Write-only.** `credentials` is in the model's `$hidden`, so no array, JSON, log line,
  job payload, or Inertia prop can carry one by accident. The screen shows whether a
  credential is stored, never what it is.
- **Audited.** Every change writes an immutable audit row naming which fields moved, and
  never their values.
- **Not a way around the environment guard.**
  `php artisan app:check-production-readiness` resolves the *effective* base URL per
  family, so a database row or an `ESB_BASE_URL_*` override pointing a non-production
  install at the production ESB fails the check.

Consequence to plan for: a database backup now carries credential material, encrypted,
and `APP_KEY` becomes a data-loss risk. See `docs/13-deployment.md`.

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
      ERP data, and nothing would look wrong until it did. Checked against the
      **effective** connection, so neither an `esb_connections` row nor an
      `ESB_BASE_URL_*` override can slip past it (ADR-018)

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
- [x] stored ESB credentials are encrypted at rest and never read back —
      `EsbConnectionManagementTest` asserts the raw column, the model's serialization,
      the page props, and the audit row
- [x] rate limits reviewed — a per-user budget on every ESB-reaching route, because the
      vendor's own limits are undocumented
- [x] secure headers applied — `SecurityHeadersTest`

Still manual, before production:

- [ ] `APP_KEY` rotation rehearsed with the re-encryption path in
      `docs/13-deployment.md`. Rotating the key without re-encrypting makes every stored
      ESB credential unrecoverable
- [ ] dependencies reviewed (`composer audit`, `npm audit`)
- [ ] backup and recovery process verified against real infrastructure
- [ ] rollback rehearsed (`docs/13-deployment.md`)
- [ ] penetration or review pass on the authenticated surface
