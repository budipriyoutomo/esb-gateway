# Project Overview

## Purpose

ERP Gateway is a web-based **integration hub**: a gateway and middleware application
sitting between internal applications/users and multiple external systems — the ERP ESB
(Enterprise Service Bus) and, going forward, other external APIs that act as additional
data sources or destinations. The ESB is the first and currently only connected system,
but it is treated as **one provider among potentially many**, not as the sole reason the
gateway exists.

The application has its own database and its own authentication/authorization — it is
not a thin proxy. It provides a controlled place to:

- authenticate users, and authenticate external systems that call into the gateway;
- manage access to integration capabilities, per user and per external system;
- invoke or orchestrate operations against any connected provider (ESB or otherwise);
- receive data pushed in from external systems, and push data out to them;
- validate, transform, and normalize data between systems that do not share a data
  model;
- validate and transform data;
- monitor integration activity across all providers, not just one;
- record audit and technical logs;
- expose operational dashboards.

## Primary Goals

1. Centralize integration across every external system this gateway connects to,
   starting with the ERP ESB.
2. Protect credentials and technical details of every connected system, not only ESB.
3. Provide a modern internal web interface.
4. Standardize error handling and observability across providers.
5. Make integrations maintainable and testable.
6. Support two-way data flow: pulling/receiving from a source system and pushing to a
   destination system, in either direction, without hard-coding one system's shape into
   another's.
7. Allow adding a new external system (POS, marketplace, other internal systems) as a
   bounded, additive unit of work — a new provider and its adapters — rather than a
   change that touches ESB-specific code.

## Non-Goals

- Replacing the ERP ESB itself.
- Replacing any other connected system.
- Rebuilding ERP (or any provider's) business functionality unnecessarily.
- Allowing browsers to access any external system directly.
- Creating a separate frontend deployment unless a future architectural decision
  explicitly changes this.
- Building a generic, fully dynamic "any API, any mapping" platform. Each new provider
  is still integrated deliberately, with a documented contract and explicit adapters —
  not through runtime-configurable, no-code mapping.

## High-Level Flow

Synchronous, user-initiated:

```
User/Client
    ↓
Laravel Web Application
    ↓
Application Services
    ↓
Integration Layer (per-provider adapter)
    ↓
External Provider API (ESB, or another system)
```

Asynchronous, queued work:

```
Laravel
    ↓
Redis Queue (per-provider queue)
    ↓
Worker
    ↓
Integration Layer (per-provider adapter)
    ↓
External Provider API
```

Inbound, another system pushing data into the gateway:

```
External Provider (webhook / API call)
    ↓
Gateway inbound endpoint (authenticated per provider)
    ↓
Inbound Adapter → Canonical representation
    ↓
Sync Transaction recorded
    ↓
Outbound Adapter (if the data is destined for another connected system)
    ↓
Destination Provider API
```

See `docs/02-architecture.md` for how the integration layer is structured to support
more than one provider, and `DECISIONS.md` (ADR-016, ADR-017) for why this shape was
chosen over integrating each new system directly against ESB-specific code.