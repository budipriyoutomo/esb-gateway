# Technology Stack

## Core

- PHP 8.3+ (exact supported version should follow Laravel 13 / project runtime
  requirements once confirmed)
- Laravel 13
- Node.js LTS compatible with the selected Vite toolchain
- PostgreSQL
- Redis

## Frontend

- React
- TypeScript
- Inertia.js
- Vite
- Tailwind CSS
- shadcn/ui

## Backend Capabilities

- Laravel authentication
- Authorization via Policies/Gates
- Validation via Form Requests
- Queue via Redis
- Laravel HTTP Client for ESB calls
- Database transactions where appropriate

## Testing

Use the project's Laravel testing framework (Pest/PHPUnit) and frontend test tooling
selected during scaffolding.

## Dependency Policy

Before adding a dependency:

1. Confirm the existing stack cannot solve the problem.
2. Check maintenance/community maturity.
3. Check compatibility with Laravel 13 and current Node/PHP versions.
4. Document significant architectural dependencies (add an ADR in `DECISIONS.md` if
   it affects architecture).
