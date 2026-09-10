# Frontend Guidelines

## Stack

React + TypeScript inside Laravel, delivered through Inertia.js.

## Structure

```
resources/js/
  components/
  layouts/
  pages/
  hooks/
  lib/
  types/
```
Lowercase — matches the Laravel/Inertia React starter kit this project scaffolded from
and the actual `resources/js` tree. Do not create capitalized duplicates (`Pages/`,
`Components/`, etc.); Linux filesystems are case-sensitive, and a duplicate folder would
silently diverge from the real one instead of erroring.

## Page Rules

Pages represent screens and coordinate UI state. Avoid putting large business algorithms
directly inside page components — extract into hooks/lib when logic grows.

## Components

Build reusable components for:

- forms;
- tables;
- dialogs;
- alerts;
- status badges;
- loading states;
- empty states.

Prefer composition over giant configurable components.

## UX

ERP UI should prioritize:

- clarity;
- fast scanning;
- keyboard/mouse efficiency;
- responsive layouts;
- clear validation;
- clear success/error states;
- consistent terminology.

## Accessibility

- semantic HTML;
- keyboard navigation;
- visible focus;
- labels for controls;
- sufficient contrast;
- meaningful error messages.

## Data Handling

- Never expose secrets.
- Do not duplicate backend authorization logic as the security mechanism.
- Handle loading/error/empty states explicitly.
- Avoid unnecessary global state.

## Visual Direction

Use a modern, professional enterprise dashboard style. Prefer the established
Tailwind/shadcn-ui design tokens and components consistently across the app rather than
introducing one-off styles per page.
