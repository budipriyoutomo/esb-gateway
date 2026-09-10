# Deployment

## Target Model

The application is a Laravel monolith containing the React frontend (ADR-001), so there
is one artifact and one deploy.

Production components:

- reverse proxy / web server terminating TLS;
- PHP-FPM application;
- queue worker(s);
- scheduler (one instance);
- PostgreSQL;
- Redis.

**Exact topology is still TBD** — instance counts, hosting, and network boundaries have
not been decided. What follows is what the application requires of whatever topology is
chosen.

## Requirements the application imposes

| Requirement | Why |
| --- | --- |
| PHP 8.3+ with `pdo_pgsql` | `composer.json`; CI pins 8.3 as the floor |
| Redis reachable from web **and** worker processes | The ESB token cache and its lock are shared across both (ADR-009). A worker with its own cache would log in separately and invalidate the web token. |
| TLS terminated in front, with `X-Forwarded-Proto` trusted | `URL::forceScheme('https')` is set in production; the app must know the request was secure |
| Exactly one scheduler instance | `integration-logs:prune` is guarded with `onOneServer()`, which needs a shared cache to work |
| Writable `storage/` | Logs, sessions if file-driven, framework caches |

### Redis is not optional

It backs the ESB token cache and the lock that serializes login and refresh. The ESB
permits only one active session per credential, so running web and workers against
separate caches would make them invalidate each other's tokens — producing intermittent
`Unauthorized` failures with no obvious cause. See ADR-009.

## Build

```
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`composer setup` covers the development equivalent, including `.env` creation and
migration.

## Deploy sequence

1. Put the previous release's workers into a stopped or drained state.
2. Deploy the new code and run the build above.
3. Run `php artisan app:check-production-readiness`. **Stop if it fails** — see below.
4. Run `php artisan migrate --force`.
5. Restart PHP-FPM.
6. Restart queue workers so they load the new code (`php artisan queue:restart`).
7. Verify `GET /health` returns `200` with every check `ok`.
8. Probe ESB connectivity from the ESB Connections screen — deliberately a human action,
   not part of the automated check (see below).

Do not assume zero-downtime deployment until the infrastructure supports it.

## Pre-release check

`php artisan app:check-production-readiness` verifies the checklist in
`docs/10-security.md` that is checkable from configuration: debug disabled, HTTPS
`APP_URL`, secure and http-only session cookies, `same_site` set, `APP_KEY` present, an
ESB credential present for the configured mechanism, and — most importantly — that a
non-production install is not pointed at the **production ESB**.

That last check is the one worth having. A staging or local install wired to the
production ESB writes to real ERP data, and nothing in the application would look wrong
until it did.

It runs as part of `composer ci:check`, so a misconfiguration fails CI rather than a
release. Running it locally is safe: checks that are environment-specific (credentials)
degrade to warnings outside production, so a developer machine without ESB credentials
still gets a useful answer.

## Queue workers

Workers must be supervised and restarted on deploy. A supervisor program, as a starting
point:

```ini
[program:erp-gateway-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/erp-gateway/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopwaitsecs=3600
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/erp-gateway/worker.log
```

`stopwaitsecs` must exceed the longest job so a deploy does not kill work mid-flight.
`--max-time` recycles workers so a long-lived process does not hold stale configuration.

**Never run more workers than the ESB tolerates.** Its rate limits are undocumented
(TBD), and the gateway's own per-user limit does not constrain queued work. Scale worker
count deliberately.

## Scheduler

One instance only:

```
* * * * * cd /var/www/erp-gateway && php artisan schedule:run >> /dev/null 2>&1
```

Currently schedules `integration-logs:prune` daily at 02:30.

## Environment variables

See `.env.example` for the full list. Production must set, at minimum:

- `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, an `https://` `APP_URL`
- `SESSION_SECURE_COOKIE=true`
- database connection
- Redis connection
- `ESB_ENV` and the credential for the configured `ESB_AUTH_MECHANISM`
- `ESB_INTEGRATION_LOG_RETENTION_DAYS` once retention is decided

Never commit a production `.env`.

## Database

- Back up before any risky migration.
- Test migrations in staging first.
- `DB::prohibitDestructiveCommands()` is enabled in production, so `migrate:fresh`,
  `db:wipe`, and similar are blocked there.
- Monitor long-running migrations; `integration_logs` and `integration_endpoints` are the
  tables that will grow.
- **A backup now carries ESB credential material**, encrypted (ADR-018). It inherits the
  same handling requirements as the application's own secrets: restricted access,
  encrypted transport and storage, and no copies on developer machines.

### `APP_KEY` and stored ESB credentials

`esb_connections.credentials` is encrypted with `APP_KEY`. Two consequences follow, and
both are operational rather than theoretical:

- **Losing `APP_KEY` loses every stored ESB credential.** They cannot be recovered from a
  backup of the database alone. Back up the key with the same care as the database, and
  separately from it.
- **Rotating `APP_KEY` requires re-encrypting first.** The rotation sequence is:

  1. put the application in maintenance mode, so nothing writes a credential mid-rotation;
  2. with the **old** key still active, read every `esb_connections` row and hold its
     decrypted credentials;
  3. set the new key as `APP_KEY`, keeping the old one in `APP_PREVIOUS_KEYS` so existing
     sessions and cookies still decrypt;
  4. re-save each row, which re-encrypts it under the new key;
  5. run `php artisan app:check-production-readiness`, then take the application out of
     maintenance mode;
  6. run one ESB probe per family from the connections screen before removing the old key
     from `APP_PREVIOUS_KEYS`.

  Skipping step 2 does not fail loudly. The rows stay readable as ciphertext and only
  fail when something tries to decrypt them, which is the next ESB call — in other words,
  in production, under load, rather than during the rotation.

## Health checks

`GET /health` — unauthenticated, returns `200` when healthy and `503` when degraded, with
a per-dependency breakdown for application, database, cache, and queue. The body says
what is broken, never where or why, because it is public.

It deliberately **does not** probe the ESB. A load balancer polling every few seconds
would hammer it, and a third party being down would pull our own instances out of
rotation. ESB reachability belongs on the connections screen, where a person asks for it
(ADR-014).

Point the load balancer at `/health`. Laravel's own `/up` remains available as a bare
liveness check that touches no dependencies.

## Rollback

**Not yet verified against real infrastructure.** The intended procedure:

1. Redeploy the previous release artifact.
2. Restart PHP-FPM and queue workers.
3. Verify `/health`.

Migrations are the constraint: a release that added a destructive migration cannot be
rolled back by redeploying code alone. Prefer additive migrations, and for anything
destructive, split it across two releases so the intermediate state works with both
versions of the code.

## Security

- HTTPS enforced; HSTS sent in production over TLS.
- Secure headers applied to every response, including `/health`
  (`App\Http\Middleware\SecurityHeaders`).
- Production debug disabled — verified by the pre-release check.
- Restrict network access to PostgreSQL and Redis to the application hosts.
- Secrets in environment or secret management, never in the database (ADR-013).

## Still TBD

- Instance counts, hosting, and network topology.
- Backup schedule, retention, and a verified restore.
- A verified rollback rehearsal.
- Integration log retention period (`docs/11-logging-monitoring.md`).
- Alert thresholds and where alerts are delivered.
