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

- server-side ESB credentials;
- authorization policies;
- input validation;
- CSRF protection for applicable web flows;
- secure cookies;
- TLS in production;
- rate limiting where appropriate;
- secret redaction;
- dependency updates;
- audit logging;
- least privilege.

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

Before production:

- [ ] no secrets committed
- [ ] authorization tested
- [ ] error responses do not leak internals
- [ ] logs redact secrets
- [ ] HTTPS enforced
- [ ] production debug disabled
- [ ] rate limits reviewed
- [ ] dependencies reviewed
- [ ] backup/recovery process verified
