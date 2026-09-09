# Logging & Monitoring

## Goals

The gateway must make integration failures diagnosable without exposing sensitive
information.

## Correlation ID

Each integration operation should have a correlation ID. Reuse an incoming trusted
correlation ID when available, otherwise generate one.

Propagate it to:

- application logs;
- integration logs;
- ESB request headers when supported;
- queue jobs.

## Integration Log Fields

Recommended:

- correlation_id
- operation
- endpoint
- environment
- started_at
- duration_ms
- outcome
- upstream_status
- retry_count
- safe_error_code
- actor/user ID when appropriate

## Metrics

Future monitoring should cover:

- request count;
- success rate;
- error rate;
- latency;
- timeout count;
- queue depth;
- failed jobs;
- ESB availability.

## Alerts

Potential alerts:

- sustained ESB failures;
- high timeout rate;
- queue backlog;
- repeated authentication failures;
- unusual error spikes.

Exact thresholds are TBD.

## Retention

Integration logs can become large. Define:

- retention duration;
- archival strategy;
- deletion policy;
- privacy requirements.

## Sensitive Data

Never treat "debug mode" as permission to log secrets.
