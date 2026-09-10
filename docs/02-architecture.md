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

Dedicated ESB clients/adapters responsible for:

- authentication;
- HTTP transport;
- request mapping;
- response mapping;
- retry behavior;
- timeout handling;
- error normalization.

### Persistence Layer

- PostgreSQL
- Eloquent models
- migrations
- repositories only when they solve a real abstraction problem

### Infrastructure

- Redis
- queue workers
- web server/reverse proxy
- application logging

## Request Flow

### Synchronous

```
React/Inertia
  → Laravel route/controller
  → validation/authorization
  → service/action
  → ESB client
  → ERP ESB
  → normalize result
  → persist audit/log if required
  → response/page props
```

### Asynchronous

```
React/Inertia
  → Laravel
  → dispatch Job
  → Redis
  → Worker
  → ESB client
  → persist result/log
  → expose status to UI
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
- coordinate models and integrations.

**ESB clients:**
- know external ESB transport/contracts;
- do not contain UI concerns.

**React:**
- presentation and interaction;
- does not implement ERP integration rules.

## Suggested Directory Structure

```
app/
  Actions/
  DTOs/
  Http/
    Controllers/
    Requests/
  Integrations/
    ESB/
      Clients/
      DTOs/
      Exceptions/
      Mappers/
  Jobs/
  Models/
  Policies/
  Services/
  Support/

resources/js/
  components/
  layouts/
  pages/
  hooks/
  lib/
  types/
  app.tsx

Lowercase — matches the Laravel/Inertia React starter kit this project scaffolded from
and the actual `resources/js` tree. Do not create capitalized duplicates (`Pages/`,
`Components/`, etc.); Linux filesystems are case-sensitive, and a duplicate folder would
silently diverge from the real one instead of erroring.

routes/
  web.php
  api.php

tests/
  Feature/
  Unit/
```

Use the exact project conventions once the scaffold is created; do not create empty
abstraction layers "just in case."
