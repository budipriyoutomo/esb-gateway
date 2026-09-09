# Deployment

## Target Model

The application is designed as a Laravel monolith containing the React frontend.

Typical production components:

- reverse proxy/web server;
- PHP-FPM application;
- queue worker(s);
- PostgreSQL;
- Redis.

Exact topology is TBD.

## Build

Typical flow:

1. install PHP dependencies;
2. install Node dependencies;
3. build frontend assets;
4. run migrations in a controlled deployment step;
5. restart/reload queue workers;
6. verify application health.

Do not assume zero-downtime deployment until infrastructure supports it.

## Environment Variables

Production secrets/configuration should include, as applicable:

- application key;
- database connection;
- Redis connection;
- ESB base URL;
- ESB credentials/token configuration;
- mail configuration;
- queue configuration.

Never commit production `.env`.

## Queue Workers

Workers must be monitored and restarted during deployments according to the deployment
strategy chosen.

## Database

- backup before risky migrations;
- test migrations in staging;
- verify rollback strategy;
- monitor long-running migrations.

## Health Checks

Production should provide safe health checks for:

- application;
- database connectivity;
- Redis/queue where appropriate;
- ESB connectivity if a non-destructive health endpoint exists.

## Security

- HTTPS;
- secure headers where appropriate;
- production debug disabled;
- restricted network access to PostgreSQL/Redis;
- secrets managed securely.
