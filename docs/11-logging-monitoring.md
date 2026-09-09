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

**Implemented** as the `integration_logs` table and `App\Models\IntegrationLog`, written
by `EsbClient` on every call. Metadata only -- no request or response payloads, because
the vendor's payloads carry personal and financial data and retention is still TBD.

A failure to write the log is logged and swallowed: losing an audit-of-activity row must
not take down the business operation that produced it.

Recorded:

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

The ESB login and refresh **response bodies** contain `accessToken` and `refreshToken`.
Redaction therefore has to cover response bodies, not just the `Authorization` header.
`TokenManagerTest` and `EsbClientTest` both assert by reading the log file back that no
token, password, or API key reaches it.
