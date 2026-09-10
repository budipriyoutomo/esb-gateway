# Architecture

## Logical Architecture

### Presentation Layer

- Inertia.js
- React
- TypeScript
- Tailwind CSS
- shadcn/ui

### Application Layer

- Laravel routes/controllers
- Form Requests
- Services/Actions
- Policies
- Jobs
- Events/listeners where justified

### Integration Layer

The gateway connects to more than one external system over its lifetime — the ERP ESB
today, others later. The integration layer is therefore structured around **providers**
and **adapters**, not around ESB specifically. ESB's own client/adapter code
(`App\Integrations\Esb\...`) is the first, reference implementation of this shape — it is
not special-cased in the layer's design.

**Provider**
A registered external system the gateway talks to (see `integration_providers` in
`docs/07-database-design.md`). Each provider has its own auth strategy, base URL(s),
rate limit, and direction (inbound, outbound, or both).

**Adapter**
A dedicated class per provider responsible for that provider's side of an integration:

- `OutboundAdapter` — the gateway calls out to the provider. Responsible for
  authentication, HTTP transport, request mapping, response mapping, retry behavior,
  timeout handling, and error normalization for that provider only.
- `InboundAdapter` — the provider calls into the gateway, or the gateway pulls from it.
  Responsible for verifying/authenticating the inbound call and converting the
  provider's payload into the gateway's canonical representation.

An adapter never calls another adapter directly, and never contains another provider's
contract. Cross-provider translation happens through the canonical model below, not
adapter-to-adapter.

**Canonical model**
A small set of internal DTOs (`App\Domain\Canonical\...`) representing the business
entities that actually move between systems (e.g. `Product`, `Order`, `InventoryItem`).
Every adapter converts *to* or *from* this shape; nothing converts directly between two
providers' formats. This keeps the cost of adding a new provider linear (one adapter
pair) instead of quadratic (a mapping to every other connected system).

**Orchestration**
A `SyncOrchestrator`-style service coordinates a full flow: receive/pull from a source
adapter → normalize to canonical → apply business rules → hand off to a destination
adapter → record the outcome as a `sync_transaction` (`docs/07-database-design.md`).
Orchestration logic belongs in Services/Actions like any other business operation
(ADR-007); it is not embedded in an adapter.

### Persistence Layer

- PostgreSQL
- Eloquent models
- migrations
- repositories only when they solve a real abstraction problem

### Infrastructure

- Redis — cache, queues, and per-provider coordination locks (ADR-009 established this
  pattern for ESB's token; the same pattern applies to any provider needing coordinated
  auth state)
- queue workers, one queue per provider so a slow or degraded provider cannot block work
  destined for another
- web server/reverse proxy
- application logging

## Request Flow

### Synchronous (gateway calls out to a provider)

```
React/Inertia
  → Laravel route/controller
  → validation/authorization
  → service/action
  → provider's outbound adapter
  → external provider API
  → normalize result (to canonical, if it will be reused elsewhere)
  → persist audit/log if required
  → response/page props
```

### Asynchronous (queued work against a provider)

```
React/Inertia
  → Laravel
  → dispatch Job (on that provider's queue)
  → Redis
  → Worker
  → provider's outbound adapter
  → persist result/log
  → expose status to UI
```

### Inbound (a provider pushes data into the gateway)

```
External provider
  → authenticated inbound endpoint (per-provider credential/signature)
  → provider's inbound adapter → canonical representation
  → sync_transaction recorded (received)
  → orchestrator applies business rules
  → destination adapter (if the data is routed onward), e.g. to ESB
  → sync_transaction updated (pushed / failed / dead_letter)
```

## Separation of Concerns

**Controllers:**
- accept request;
- authorize;
- validate;
- call service/action;
- return response/Inertia page.

**Services/Actions:**
- implement business operations;
- coordinate models and integrations;
- own orchestration between providers — an adapter never decides to call another
  adapter on its own.

**Provider adapters (Outbound/Inbound):**
- know one external system's transport/contract, and only that one;
- convert to/from the canonical model;
- do not contain UI concerns;
- do not know about any other provider.

**React:**
- presentation and interaction;
- does not implement integration or ERP business rules.

## Suggested Directory Structure

```
app/
  Actions/
  Domain/
    Canonical/            # Product, Order, InventoryItem, ... internal DTOs
  DTOs/
  Http/
    Controllers/
    Requests/
  Integrations/
    Contracts/
      OutboundAdapter.php
      InboundAdapter.php
    Esb/                   # first provider, reference implementation
      Clients/
      DTOs/
      Exceptions/
      Mappers/
    <NewProvider>/          # same shape, added per new provider
      Clients/
      DTOs/
      Exceptions/
      Mappers/
  Jobs/
  Models/
  Policies/
  Services/
    Sync/
      SyncOrchestrator.php
  Support/

resources/js/
  Components/
  Layouts/
  Pages/
  Hooks/
  Lib/
  Types/
  app.tsx

routes/
  web.php
  api.php               # inbound endpoints for external providers live here,
                         # separate from user-facing web routes

tests/
  Feature/
  Unit/
```

Use the exact project conventions once a given piece is implemented; do not create empty
abstraction layers "just in case." A second provider is what proves the
`Integrations/Contracts` abstraction earns its place — do not build it speculatively
ahead of a real second provider if the team decides to defer that (see ADR-015).