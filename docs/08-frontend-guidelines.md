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

Shared building blocks (each has a `*.test.tsx` beside it). New pages use these
instead of hand-rolled equivalents; `pages/purchase-orders/` is the reference:

| Need | Use |
| --- | --- |
| Page title (the page's one `h1`), back link, actions | `PageHeader` |
| List filters + query string | `FilterBar` + `useFilterForm` (fields, chips, reset), `DateRangeField`, `SelectFilterField`, `ActiveStatusField`; `useListQuery` underneath |
| List table | `DataTable` (+ `rowHref` for clickable rows; a card per row below 640px, cells labelled from the header or `label`) |
| Paging | `ListPagination` (a Laravel paginator through `fromPaginator()`) |
| Chip × button | `ChipRemoveButton` |
| ERP status | `StatusBadge` |
| Detail header | `DetailSummary`, `DetailFields` / `DetailField` |
| Authorize / reject / delete | `ApprovalActions` |
| Write outcome unknown | `UnconfirmedWriteNotice` |
| Form buttons, unsaved changes | `FormActions`, `useUnsavedChangesWarning` |
| Searchable picker | `LookupCombobox` (ERP lists via `/lookups/{kind}`), `Combobox` (any async source) |

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

Checked across the whole frontend by `resources/js/test/accessibility-audit.test.tsx`
(TODO-modules.md UI-5): every icon-only `Button` has an accessible name, every
application page has a `PageHeader` (one `h1`), colours come from the theme tokens (no
raw `red-600` / `neutral-200` outside `components/ui`), and buttons / text inputs reach
44px on touch screens (`pointer-coarse:`). Links and other focusable elements without a
ring of their own get a 2px `--foreground` outline (`app.css`).

## Data Handling

- Never expose secrets.
- Do not duplicate backend authorization logic as the security mechanism.
- Handle loading/error/empty states explicitly.
- Avoid unnecessary global state.

## Visual Direction

Use a modern, professional enterprise dashboard style. Prefer the established
Tailwind/shadcn-ui design tokens and components consistently across the app rather than
introducing one-off styles per page.

### Design tokens (`resources/css/app.css`)

- Colours come from tokens only (`bg-background`, `text-muted-foreground`, …). No raw
  palette classes (`text-green-600`) in new code.
- Status colours: `text-success` / `text-warning` / `text-info` / `text-destructive-foreground`
  for text and icons; tint surfaces with the same token, e.g.
  `bg-success/10 border-success/30`.
- Every token is defined for both `:root` and `.dark`, and every text-on-surface pair
  meets WCAG AA (4.5:1). `resources/js/test/theme-tokens.test.ts` enforces both — add
  a pair there when adding a token.
- Accent colour: still the neutral shadcn palette. A brand accent is a business
  decision (`TODO-modules.md` UI-1); do not invent one.

### Type scale

| Role | Classes |
| --- | --- |
| Page title | `text-xl font-semibold tracking-tight` (`Heading`) |
| Section title | `text-base font-medium` (`Heading variant="small"`) |
| Body, table cells, form fields | `text-sm` |
| Caption, metadata, timestamps | `text-xs text-muted-foreground` |
| Numbers in tables | `text-right tabular-nums` |

### Spacing and radius

- Page body: `flex h-full flex-1 flex-col gap-6 p-4`.
- Radius as the shadcn components already use it: `rounded-xl` (cards, notices),
  `rounded-lg` (dialogs), `rounded-md` (inputs, buttons, badges). `--radius` drives
  `rounded-lg` / `-md` / `-sm`.

### Formatting (`resources/js/lib/format.ts`)

Never call `Intl.*` or `toLocaleString()` in a page. Use:

- `formatAmount(value, sign?)` — money, `id-ID`, two decimals (`Rp 1.500,00`).
- `formatQuantity(value)` — up to four decimals (`2,5`).
- `formatDate(value)` — `dd MMM yyyy` (`20 Mar 2026`).
- `formatDateTime(value)` — `dd MMM yyyy HH:mm`, 24-hour.

All return `—` for no value. Dates: a zoneless ERP value keeps its wall clock; a zoned
timestamp (the gateway's own, `toIso8601String()`) is shown in Asia/Jakarta; an
unrecognised value is shown unchanged, never hidden. Display only — the ERP is the
authority on every amount and date.
