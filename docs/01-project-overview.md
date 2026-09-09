# Project Overview

## Purpose

ERP Gateway is a web-based gateway and middleware application between internal
applications/users and the ERP ESB (Enterprise Service Bus).

The application provides a controlled place to:

- authenticate users;
- manage access to ERP integration capabilities;
- invoke or orchestrate ESB operations;
- validate and transform data;
- monitor integration activity;
- record audit and technical logs;
- expose operational dashboards.

## Primary Goals

1. Centralize ERP ESB integration.
2. Protect ESB credentials and technical details.
3. Provide a modern internal web interface.
4. Standardize error handling and observability.
5. Make integrations maintainable and testable.
6. Allow future integration with POS and other internal systems.

## Non-Goals

- Replacing the ERP ESB itself.
- Rebuilding ERP business functionality unnecessarily.
- Allowing browsers to access ESB directly.
- Creating a separate frontend deployment unless a future architectural decision
  explicitly changes this.

## High-Level Flow

```
User/Client
    ↓
Laravel Web Application
    ↓
Application Services
    ↓
ESB Integration Layer
    ↓
ERP ESB APIs
```

For asynchronous work:

```
Laravel
    ↓
Redis Queue
    ↓
Worker
    ↓
ESB Integration Layer
```
