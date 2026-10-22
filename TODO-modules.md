# TODO — Ekspansi Modul (Inventory, Finance, Accounting, Master Data, POS) + UI

Rencana kerja untuk menambah 24 modul sekaligus dan memodernisasi UI. Progres per phase
ada di §0.4. Baca `AGENT.md`, `docs/project-state.md`, dan `TODO.md`
(terutama "Module — Purchase Order" dan "Module — Simple Purchase" sebagai pola referensi)
sebelum mengambil item apa pun.

Status: `[ ]` belum, `[~]` sedang dikerjakan, `[x]` selesai, `[!]` terblokir (butuh
jawaban ESB/bisnis).

---

## 0. Aturan main (berlaku untuk SETIAP modul)

### 0.1 Alur TDD per slice — wajib, tidak boleh dibalik

1. **Dokumentasi kontrak dulu.** Baca operasi di `docs/esb/esb-core-endpoints.md` /
   `esb-oms-endpoints.md` + halaman vendor. Tulis entri `[ESB-0xx]` di
   `docs/05-api-integration.md` (nomor berikutnya: **`[ESB-051]`**). Jangan mengarang
   field yang tidak terdokumentasi — tandai sebagai asumsi/TBD.
2. **Fixture** `tests/Fixtures/Core<Module>.php` dari contoh respons vendor.
3. **Tulis test yang gagal (RED)**, jalankan, pastikan gagal karena alasan yang benar:
   - `tests/Unit/Integrations/ESB/<Module>MapperTest.php` — mapping field, null/0
     sebagai "tidak ada", tanggal Asia/Jakarta → UTC.
   - `tests/Feature/<Modules>/List…Test.php` — butuh `esb.invoke`, filter diteruskan
     ke ESB, filter kosong dibuang, paging, URL prev/next ESB tidak bocor ke browser.
   - `…/Detail…Test.php` — 404 untuk `EC03100004`, path-escape ditolak.
   - `…/Decision…Test.php` / `…/Delete…Test.php` — permission `.approve` / `.manage`,
     lock per dokumen, satu kali percobaan (tanpa retry), audit row, outcome
     "unconfirmed" saat `mayHaveBeenApplied()`.
   - `…/Create…Test.php` / `…/Update…Test.php` — validasi FormRequest, error per-field
     ESB tampil (ADR-021), audit.
4. **Implementasi minimal (GREEN)**: contract `Integrations/Contracts/<X>Source.php` →
   DTO `Domain/Canonical/<X>*.php` → `ESB/Clients/<X>Client.php` →
   `ESB/Mappers/<X>Mapper.php` → FormRequest (extends `BaseFormRequest`) → Controller
   tipis → Action di atas `GuardedEsbWrite` untuk write → route → `Permission` enum +
   `resources/js/types/permissions.ts` → `AuditAction` → halaman Inertia → sidebar.
5. **Refactor** dengan test tetap hijau.
6. **Checks**: `php artisan test`, `pint --test`, `phpstan analyse`, `tsc --noEmit`,
   `vp check`, `npm run build`.
7. **Handoff**: update `docs/project-state.md` (skill `project-state-handoff`), centang
   item di file ini. Commit dilakukan user per phase (§0.4).

### 0.2 Template slice per modul

| Slice | Isi | Permission |
| --- | --- | --- |
| S1 | List + detail (read-only) | `esb.invoke` |
| S2 | Authorize / reject (+reason bila wajib) / delete | `<module>.approve`, `<module>.manage` |
| S3 | Create / update (+ lookup/picker yang dibutuhkan) | `<module>.manage` |
| S4 | Import / export / attachment / fitur sekunder | `<module>.manage` |

Permission baru selalu **Super Admin saja** sampai bisnis memutuskan role lain.

### 0.3 Batasan yang tidak boleh dilanggar

- Write ke ESB **tidak pernah di-retry otomatis**; read boleh retry.
- Klasifikasi respons ESB hanya dari field `code`.
- Gateway tidak menghitung ulang total/pajak/stok — ERP adalah otoritas.
- Push/pull antar child company (`/push`, `/pull`) **di luar scope** sampai bisnis meminta.
- Dependency baru (frontend/backend) → ADR dulu (skill `adr-writer`).

### 0.4 Phase commit

Pengerjaan tetap per poin (TDD, checks hijau per poin). User meng-commit **manual satu
commit per phase**: jangan commit sendiri. Agent menandai phase selesai di tabel ini dan
memberi usulan pesan commit.

| Phase | Isi (poin di file ini) | Status |
| --- | --- | --- |
| P1 | §1 refactor `ApprovalDecision` + `DocumentWrites` / `HandlesDocumentWrites` | `[x]` siap commit |
| P2 | §1 tipe TS `Pagination` / `Filters` bersama | `[x]` siap commit |
| P3 | UI-0 tooling test frontend (ADR Vitest + RTL, setup, CI) | `[x]` siap commit |
| P4 | UI-1 design foundation (token, helper `formatAmount` / `formatDate`) | `[x]` siap commit |
| P5 | UI-2 komponen bersama | `[x]` siap commit (tab + combobox ditunda, lihat UI-2) |
| P6 | §1 lookup bersama (picker + endpoint JSON) + §5.1 ADR POST-as-read | `[x]` siap commit |
| P7 | Master Data S1: Unit, Category, Sub Category, Supplier Category | `[x]` siap commit |
| P8 | Master Data S1: Supplier, COA, Product (perluasan), Pricelist, Payment Method | `[x]` siap commit (Payment Method `[!]`) |
| P9a | Master Data S2: delete / deactivate / restore / pricelist decisions | `[x]` siap commit |
| P9b | Master Data S3 batch 1: Unit, Sub Category, Supplier Category, Category | `[x]` siap commit |
| P9c | Master Data S3 batch 2: Product, Pricelist, COA reorder (Supplier & COA create/update `[!]`) | `[x]` siap commit |
| P9d | Master Data S4: import, harga pricelist, attachment supplier (export `[!]`) | `[x]` siap commit |
| P10 | Inventory S1 (GTR, Goods Delivery, Goods Receipt, Simple Transfer, Item Journal) | `[x]` siap commit |
| P11 | Inventory S2–S4 | `[x]` siap commit (GD/GR create-from-reference & aset `[!]`) |
| P12 | Finance: Disbursement S1–S4 | `[x]` siap commit (export `[!]`) |
| P13 | Accounting S1 (General Journal, General Ledger, Memorial Journal) | `[x]` siap commit (filter array, `/access`, export `[!]`) |
| P14 | Accounting S2–S4 (Memorial Journal) | `[x]` siap commit (upload/form-upload, export `[!]`) |
| P15 | POS: Menu Category, Promotion (+ Menu setelah keputusan sumber) | `[x]` siap commit (Menu `[!]` keputusan sumber; promosi update/nonaktif `[!]`) |
| P16 | UI-3 navigasi + UI-4 dashboard | `[x]` siap commit (approval dokumen purchasing/GTR/GD/GR/ST `[!]`) |
| P17 | UI-5 responsif/aksesibilitas + UI-6 migrasi halaman lama | `[x]` siap commit (kontrol keputusan PR/PI/SP tetap lokal, lihat UI-6) |
| P18 | Sisa tanpa blocker: wiring `UnconfirmedWriteNotice`, Menu Extras read-only, npm audit (lockfile) | `[x]` siap commit (`vite-plus` 1.x / Node 22 `[!]`) |
| P19 | ADR-028 `vite-plus` 1.1.0 (Node 22, alias `vite`), jsdom 30 / jest-dom 7, ADR-029 Radix Tabs + `DetailTabs` (master menu, BOM, supplier), SSR off di PHPUnit | `[x]` siap commit (moderate Vitest server `[!]` → `vite-plus` 1.3.0 setelah 2026-10-30) |
| P20 | §1 migrasi Purchase Request ke `DocumentWrites` (audit `write-unconfirmed`, banner) + `ApprovalActions` di detail PR; keputusan bisnis 2026-10-22 + `docs/esb/open-questions.md` | `[x]` siap commit |
| P21 | Nomor rekening supplier & disbursement tampil utuh (keputusan bisnis) | `[x]` siap commit |
| P22 | PI / SP detail ke `ApprovalActions` | `[x]` siap commit |
| P23 | Sapu string UI ke Inggris — tidak ada perubahan kode (sudah Inggris) | `[x]` (tanpa commit) |
| P24 | ADR-030 satu modul Menu (Core + OMS) | `[x]` siap commit |
| P25 | ADR-031 export CSV dari gateway (12 list) | `[x]` siap commit |

Item `[!]` (butuh jawaban ESB/bisnis) dilewati dalam phase-nya dan tidak memblokir
commit phase tersebut.

Usulan pesan commit P1:

```
refactor: share approval decision and decide/delete writes

Merge the four two-case decision enums into ApprovalDecision, and extract
DocumentWrites / ApprovableDocument / HandlesDocumentWrites so purchase
order, simple purchase and purchase invoice decide/delete share one
implementation. Lock keys, audit rows and toast text are unchanged.

Also adds TODO-modules.md, the plan for the next module batch.
```

Usulan pesan commit P2:

```
refactor(ui): share Pagination and list filter types

Move the Pagination type duplicated in ten list pages to
resources/js/types/pagination.ts, with PagingFilters and DocumentFilters
as the shared base of each page's Filters. Types only; no runtime change.
```

Usulan pesan commit P3:

```
build(ui): add component tests with vp test and Testing Library

ADR-024. Run Vitest through vite-plus (vp test, config in the test block
of vite.config.ts) with React Testing Library and jsdom, pinned to
versions that run on Node 20.19 and 22. Add npm run test:ui, run it in
composer ci:check, and a first test for Heading.
```

Usulan pesan commit P4:

```
feat(ui): shared display formatting and checked theme tokens

Add resources/js/lib/format.ts (formatAmount, formatQuantity, formatDate,
formatDateTime: id-ID, dd MMM yyyy, Asia/Jakarta for zoned timestamps,
unknown ERP date shapes shown as-is) and use it on every page instead of
per-page Intl / toLocaleString.

Add success/warning/info tokens, fix the dark sidebar-primary foreground
(1:1 contrast) and light muted-foreground on muted (4.34:1), and guard
token parity and WCAG AA contrast with theme-tokens.test.ts.

Visible change: numbers now use id-ID grouping (1.234,56) and ERP dates
show as 20 Mar 2026 instead of the raw string.
```

Usulan pesan commit P5:

```
feat(ui): shared list, detail and approval components

Add PageHeader, FilterBar (+ useListQuery), DateRangeField, DataTable,
ListPagination, StatusBadge, DetailSummary/DetailFields, ApprovalActions,
UnconfirmedWriteNotice, FormActions and useUnsavedChangesWarning, each
with component tests. Rebuild the purchase order list and detail pages on
them as the reference. Mock Inertia's Head in the test setup so pages can
be rendered in tests.

No new dependencies: tabs and a popover combobox are deferred until an
ADR adds them.
```

Usulan pesan commit P6:

```
feat: shared lookups for pickers, and retry POST reads (ADR-025)

ADR-025: EsbClient::postRead() sends a POST the vendor documents as a read
with the GET retry policy; post() and every other write keep one attempt.

[ESB-019]: LookupSource / LookupClient over ESB Core branch, supplier,
location, COA (active non-module, cash and bank), status and transfer
product lists, served as JSON from GET /lookups/{kind} (esb.invoke,
throttle:esb). Whole lists are searched and paged in the gateway; no
undocumented array query parameters are sent.

Frontend: fetchLookup, an accessible Combobox (no new dependency) and
LookupCombobox, with component tests.
```

Usulan pesan commit P7:

```
feat: read-only units, product categories, sub-categories, supplier categories

[ESB-020]–[ESB-023]: list (search, active filter, paging; product
categories also by type) and detail for four ESB Core master lists,
behind esb.invoke. Shared pieces for this kind of module:
ListReferenceDataRequest, the ReadsEsbValues mapper trait,
ReferenceDataIndex / ReferenceDataShow pages, and a
ReadOnlyMasterDataTestCase every module's feature test extends.

Also makes LookupSource extend OutboundAdapter like the other contracts.
```

Usulan pesan commit P8:

```
feat: read-only suppliers, chart of accounts, product detail, pricelists

[ESB-024] suppliers: list and detail with contacts, bank accounts (masked
to the last four digits in the adapter) and supplier product codes.
[ESB-025] chart of accounts: active accounts as an outline by level,
searched and paged in the gateway; account detail with mappings. The
account number must start with a digit so it cannot reach literal vendor
paths such as /accounting/coa/export.
[ESB-026] product detail with its units; the list stays /product/list.
[ESB-027] pricelists: active prices, proposed (temporary) pricelists and
their detail; a pricelist number must contain a digit
(/pricelist/temp/export).

[ESB-028] payment method is documented but not built: it needs a POS
backend credential (HTTP Basic) and a non-envelope response, both
decisions still open.
```

Usulan pesan commit P9a:

```
feat: master data state changes (delete / deactivate / restore)

[ESB-029] S2 for units, product categories, product sub-categories,
supplier categories, suppliers, COA accounts and products, plus
authorize / reject / delete of proposed (temporary) pricelists.

Shared: RecordWrites (+ MasterRecord, RecordOperation) on GuardedEsbWrite
-- one write per record, one attempt, audited, unconfirmed outcomes with
their correlation id -- and HandlesRecordWrites for the toast/redirect,
showing the ERP's own reasons for a refusal. RecordActions on the detail
pages. One manage permission per module (Super Admin only); coa.manage
kept apart. The vendor's DELETE on suppliers and COA deactivates, so the
gateway calls it that. HandlesDocumentWrites learns that an authorize can
consume the document (temporary pricelists).
```

Usulan pesan commit P9b:

```
feat: create and edit units and categories

[ESB-030] S3 batch 1: create / update for units, product categories,
product sub-categories and supplier categories, validated against the
vendor schema before anything reaches the ESB.

Shared: RecordWrites::create() (one create per person at a time, the new
id audited when the ERP returns one) and RecordOperation::Create/Update;
HandlesRecordForms maps the ERP's field errors back onto the form and
lands an unconfirmed write where the person can check it. RecordForm on
the frontend; Edit / New buttons for whoever holds the manage permission.
New lookups for the pickers: metrics, category types, active taxes.
```

Usulan pesan commit P9c:

```
feat: create and edit products and pricelists, move COA accounts

[ESB-031] S3 batch 2. Products: create / update with their units; the
update reads the product first and carries over what the form does not
edit (BOM, CoreTax code, custom fields, menus). A product the ERP holds
for approval says so. Pricelists: propose, and edit a proposal under the
same lock as authorize / reject / delete. COA accounts: move up / down.

CreatedRecord tells HandlesRecordForms about a new record's id and
whether it is waiting for approval. New lookups: product categories,
sub-categories, units, currencies, product details.

Supplier and COA create / update are not built: their schemas need data
the ERP does not expose (greetings, payment methods, unmasked bank
accounts; COA numbering, parent, currency id).
```

Usulan pesan commit P9d:

```
feat: master data imports, pricelist helper prices, supplier attachments

[ESB-032] S4. One import screen for units, product categories and
sub-categories, new and changed products, pricelists, new and changed
suppliers, and COA accounts: the file goes to the ERP once, under a lock
per person and kind, audited by name, size and SHA-256; the toast says
when the ERP only queued it. Excel checks move to ValidatesExcelUpload,
shared with the purchase request import. EsbClient uploads with the
vendor's verb and several files under one field.

The pricelist form shows each product's active price and its recent
purchase invoice prices. Suppliers: attach files, remove all of them,
and see how many there are.

ERP exports are not built: they answer with a URL in the ERP's own file
storage, and whether to proxy or replace them needs a decision.
```

Usulan pesan commit P11:

```
feat: inventory writes (decide, delete, create, edit, attachments)

[ESB-034] S2 for all five inventory documents: authorize / reject (only
the item journal's reject takes a reason) / delete, and finish / reopen a
goods transfer request -- one write per document at a time, one attempt,
audited with the person, unconfirmed outcomes with their correlation id.
Ten new permissions (<kind>.approve / .manage), Super Admin only.
DocumentWrites::transition() for one-shot state changes.

[ESB-035] S3: create and edit goods transfer requests, item journals and
simple transfers on one shared form, checked against the vendor schema;
an edit reads the document first and sends back what the form does not
show (purchase request links, cost center, project, request template).
Documents that move assets, and transfer requests the ERP marks as not
editable, are left to the ERP. New "purposes" lookup.

[ESB-036] S4: attach files and remove them; where the vendor's upload
keeps only the "existing" URLs it is sent, files are attached only while
there are none. Simple transfer sheets go through the shared import
screen. ForwardsAttachments is shared with supplier attachments.

Goods deliveries and goods receipts are not created here: they start from
a reference document with expirations and assets the vendor does not
fully document.
```

Usulan pesan commit P12:

```
feat: disbursements (read, decide, create, edit, attachments, import)

[ESB-037] list with number, status, branch, supplier, payment, reference,
overdue and date filters, and detail with the account lines and the
purchase payments / supplier settlements against it. The bank account
number is masked in the adapter (ReadsEsbValues::mask(), now shared with
suppliers); the whole number stays on the server.

[ESB-038] authorize / reject (optional reason) / delete on DocumentWrites:
one write per disbursement at a time, one attempt, audited with the
person. disbursements.approve / .manage, Super Admin only.

[ESB-039] create and edit cash and credit disbursements, checked against
the vendor schema; an edit first asks the ERP (PUT .../validate) whether
the disbursement may change, and sends back the cost center, project and
bank details it does not show.

[ESB-040] attach files (only while there are none), the queued Excel
import on the shared import screen, and lines read from the ERP's Excel
disbursement form into the create / edit form (nothing saved by the ERP).
Finance group in the sidebar.
```

Usulan pesan commit P13:

```
feat: general journal, general ledger and memorial journals (read)

[ESB-041] general journal entries by date and free text, and one
journal's lines page by page. The vendor's array filters (branch,
account, cost center, project) are not sent: their format is
undocumented.

[ESB-042] an account's general ledger over a period and the period's
summary per branch and account -- POST reads through postRead()
(ADR-025). Nothing is read until the account and period are given; the
period is at most 366 days and the unpaged entries are paged by the
gateway. No balance is computed. The ERP's refusal shows on the page.

[ESB-043] memorial journal list and detail; numbers must contain a digit
(/access, /export, /upload share the segment). JournalLine and
JournalLinesTable are shared by both journals. Accounting group in the
sidebar.
```

Usulan pesan commit P14:

```
feat: memorial journal writes, edit form and images

[ESB-044] authorize / reject / delete under memorial-journals.approve /
.manage (Super Admin only): one attempt, one write per journal, audited
with the person. The page asks for a reject reason only while the
journal waits for approval; the ERP enforces it.

[ESB-045] create / edit with debit / credit lines (branch, account,
currency, rate; exactly one side per line, at least two lines). The
totals are a hint; the ERP judges the balance. An edit sends back each
kept line's cost center and project, read from the ERP by line id.

[ESB-046] one image per upload to the journal's asset images
(AttachmentActions gains a single-file mode). The spreadsheet upload,
form-upload and export are not built: the vendor documents them as a
JSON row, not a file field.
```

Usulan pesan commit P15:

```
feat: POS menu categories and promotions (ESB OMS)

[ESB-047] / [ESB-048] menu categories: list page by page (no search, no
detail operation: a category is found on the page it was listed on, then
the others, at most 20 pages) and create / edit with category details
under menu-categories.manage. An edit keeps every current detail. The
accounts are sent as COA descriptions, as the vendor documents.

[ESB-049] / [ESB-050] promotions: the list (a bare JSON array, read via
EsbClient::getBareList(), ADR-027; paged by the gateway) and create for
the five documented types under promotions.manage. Each type sends only
its own fields; booleans as "Yes" / "No".

ErrorClassifier reads the OMS field errors: EC0110's JSON message and
`data` keyed by field. New OMS lookups (menu categories, category
details, POS menus) and a LookupChips multi-picker. POS group in the
sidebar.
```

Usulan pesan commit P16:

```
feat(ui): grouped navigation, command palette, breadcrumbs, dashboard cards

The menu moves to resources/js/lib/navigation.ts, shared by the sidebar,
a new Ctrl+K command palette and the breadcrumbs. Gateway pages join a
System group; groups remember being opened or closed and always open on
the page they hold. The palette jumps to any page the person may open, or
opens a typed document number in the twelve modules that read by number
(no new dependency: Radix Dialog plus its own listbox).

Breadcrumbs are derived from the URL and the menu on every page inside
it (module, number, New / Edit / Import); the per-page static
breadcrumbs are removed.

Dashboard: a deferred "Waiting for approval" card (PendingApprovals:
status 38 for disbursements, memorial journals, item journals and
proposed prices, only where the person approves; a failed read shows as
unknown), the last call per ESB family, and permission-gated shortcuts.
```

Usulan pesan commit P17:

```
feat(ui): responsive, accessible lists and the older pages migrated

UI-5. DataTable becomes a card per row below 640px (visually hidden
header, cells labelled from their column, explicit table roles).
Buttons, inputs and chip remove buttons reach 44px on touch screens
(pointer-coarse). PageHeader renders the page's h1. Links and other
focusables without their own ring get a visible outline. Raw palette
colours in the starter kit pages move to theme tokens. An audit test
checks the whole frontend: named icon buttons, a PageHeader on every
page, theme colours only, touch target sizes.

UI-6. Products, menus, master menus, bills of material, purposes,
document templates (+ imports), purchase requests, purchase invoices,
simple purchases, sales pushes, integration logs, endpoints and users
now use PageHeader, FilterBar, DataTable, ListPagination and
StatusBadge, like the purchase order list; their detail, form and
import pages use PageHeader. New shared pieces: useFilterForm,
SelectFilterField / ActiveStatusField, fromPaginator, ChipRemoveButton.
Filters, paging and server behaviour are unchanged.
```

Usulan pesan commit P18:

```
feat: unconfirmed write banner, menu extras page, dependency fixes

An unconfirmed decision, transition or upload that lands on a document's
detail page is now flashed as unconfirmedWrite (action, reference) and
shown by UnconfirmedWriteBanner in both app layouts, instead of a toast
that disappears while the person checks the status. Deletes and
authorizations that consume the document still toast on the list.

GET /master-menus/extras lists the extras of one page of OMS Get Menu
with their menu ([ESB-013]); there is no extra endpoint to read.

Lockfile only: concurrently 10.0.6 (shell-quote 1.12.0), vite 8.3.4,
source-map-js 1.2.2. tinypool (via vite-plus 0.3.0) remains.
```

Usulan pesan commit P25:

```
feat: CSV exports built by the gateway

ADR-031 (business decision 2026-10-22). GET /<list>/export takes the
list's own filters, FormRequest and permission, and answers a CSV
(UTF-8 BOM) for units, product categories and sub-categories, products,
pricelists, chart of accounts, suppliers, disbursements, general and
memorial journals, and the general ledger with its summary.

CsvExport reads every page before writing, so an ESB failure is an
error, never a truncated file; it stops on the row count, not the
provider's page number. Over 5,000 rows is refused on the list. The
ledger and the chart of accounts are read once. Formula-looking text is
prefixed with '. Each export is audited as list.exported. The ERP's own
export files are not used.
```

Usulan pesan commit P24:

```
feat(menus): one menu list with a Core | OMS source tab

ADR-030 (business decision 2026-10-22). GET /menus?source=core|oms reads
ESB Core [ESB-012] or ESB OMS [ESB-013]; filters are validated per
source and only that source is called. The tabs are links. GET
/master-menus redirects (301) to the OMS tab with its page and code; the
OMS detail and extras keep their URLs. One "Menus" navigation entry.
```

Usulan pesan commit P22:

```
refactor(ui): purchase invoice and simple purchase detail use ApprovalActions

The local authorize / reject / delete controls are replaced by the
shared component. A simple purchase rejection still requires a reason
(max 500); a purchase invoice rejection takes none.
```

Usulan pesan commit P21:

```
feat: show supplier bank account numbers in full

Business decision 2026-10-22. Supplier and disbursement details carry
the account number as the ERP sent it (accountNumber /
bankAccountNumber) instead of the last four digits. The integration log
stores no response bodies, so the number is not logged.
```

Usulan pesan commit P20:

```
refactor: purchase request decisions through DocumentWrites

Business decision 2026-10-22: an unconfirmed purchase request
authorize/reject is now audited like every other document, as
purchase-request.write-unconfirmed with an `operation` key. Rows written
before keep purchase-request.decision-unconfirmed / `decision`.

Decide and delete go through DocumentWrites (PurchaseRequestDocument),
same lock key as before. An unconfirmed decision shows the
unconfirmed-write banner instead of a toast. The detail page uses the
shared ApprovalActions; a rejection still sends no reason.

Records the business answers to the open questions in TODO-modules.md
and lists the questions for ESB in docs/esb/open-questions.md.
```

Usulan pesan commit P19:

```
build: vite-plus 1.1.0 on Node 22; feat(ui): detail page tabs

ADR-028. vite-plus 0.3.0 pulled tinypool <=2.1.1 (critical RCE
advisory) through vitest 4. vite-plus 1.1.0 bundles vitest 5.0.3 and
needs Node 22.18+. As its README documents, vite is aliased to
@voidzero-dev/vite-plus-core@1.1.0 (direct dependency and overrides) and
vitest is overridden to 5.0.3. @babel/core goes to 8.0.7. A moderate
advisory remains for Vitest servers (UI / browser mode, not used) until
vite-plus 1.3.0 is two weeks old.

jsdom 30.1.2 and @testing-library/jest-dom 7.0.1, still pinned exactly.

ADR-029. @radix-ui/react-tabs 1.1.22 behind ui/tabs.tsx, and a shared
DetailTabs (named tab list, row count per tab). Master menu, bill of
material and supplier details keep their header fields above and move
their line lists into tabs.

Inertia SSR follows INERTIA_SSR_ENABLED and phpunit.xml turns it off: a
local bootstrap/ssr/ bundle made every render POST through the faked
HTTP client.
```

Usulan pesan commit P10:

```
feat: inventory documents (read)

[ESB-033] S1 for goods transfer requests, goods deliveries, goods
receipts, item journals and simple transfers: list with number, status,
branch and date filters, and detail with where the goods came from and
went to, the reference, lines and linked documents.

ADR-026: one canonical shape (InventoryKind, InventoryDocument,
InventoryLine, InventoryParty) with one mapper, controller and pair of
pages, routed per kind. A delivery also reads its outstanding quantity
per line; a receipt and a simple transfer read the stock check. Those
extra reads never fail the page. Numbers must contain a digit: literal
vendor paths share the detail segment.

phpunit.xml raises memory_limit to 1G: the suite outgrew 128M in one
process.
```

---

## 1. Fondasi sebelum batch modul (kerjakan PERTAMA)

Tanpa ini, 24 modul akan menggandakan kode yang sama ±24 kali.

- [x] **Refactor approval decision** (2026-10-19): `PurchaseRequestDecision`,
      `PurchaseInvoiceDecision`, `PurchaseOrderDecision`, `SimplePurchaseDecision`
      digabung menjadi satu enum `App\Domain\Canonical\ApprovalDecision`
      (`ApprovalDecisionTest`). Test existing hijau tanpa diubah.
- [x] Ekstrak bagian bersama dari Decide/Delete action, decision controller, dan locks
      (2026-10-19): `Actions\Support\ApprovableDocument` (prefix lock + audit actions),
      `Actions\Support\DocumentWrites` (`decide()` / `delete()`), trait
      `Http\Controllers\Concerns\HandlesDocumentWrites` (toast + redirect). PO, Simple
      Purchase, dan Purchase Invoice dimigrasi. Modul baru cukup bikin
      `<X>Document::approvable()` + action tipis.
  - [x] Purchase Request dimigrasi (P20, 2026-10-22): audit unconfirmed decision-nya
        (`purchase-request.decision-unconfirmed`, key `decision`) beda dengan modul
        lain (`*.write-unconfirmed`, key `operation`). **Diputuskan 2026-10-22:
        seragamkan** — kejadian baru `purchase-request.write-unconfirmed` + key
        `operation`; log lama tidak diubah. Setelah itu PR pakai `ApprovalActions` +
        `UnconfirmedWriteNotice` seperti modul lain.
- [x] Ekstrak tipe `Pagination`/`Filters` TS (2026-10-19): `resources/js/types/pagination.ts`
      (`Pagination`, `PagingFilters`, `DocumentFilters`), dipakai 10 halaman list. Field
      filter khusus modul tetap di page masing-masing.
- [x] **Lookup bersama** (2026-10-19, `[ESB-019]`): kontrak `LookupSource`,
      `LookupClient` + `LookupMapper`, DTO `LookupKind` / `LookupQuery` / `LookupOption`
      / `LookupPage`; endpoint `GET /lookups/{kind}` (JSON, `esb.invoke`,
      `throttle:esb`); frontend `fetchLookup` + `Combobox` + `LookupCombobox`.
      - [x] Branch (`GET /branch`). Form purchase request masih memakai
            `listBranches()` `[ESB-007]` — pindah ke lookup = refactor nanti.
      - [x] Location (`GET /location/list`, filter branch di gateway).
      - [~] Product untuk transfer (`GET /product/browse-transfer`). Picker product
            untuk purchase belum: endpoint browse purchase belum dipilih.
      - [x] Supplier (`GET /supplier/list`).
      - [x] COA (`active-non-module` paginated, `cash-bank` filter branch di gateway).
      - [x] Status (`GET /common/status`, global — mapping per dokumen `TBD`).
      - [x] Endpoint JSON internal (searchable, paginated), rate limit per user.
      - [!] Format parameter array ESB (`a=1&a=2` vs `a[]=1`) tidak terdokumentasi —
            tanya ESB agar filter branch bisa dikirim ke upstream.
- [x] Selesaikan **Fase UI-1 dan UI-2** (§7) dulu agar modul baru langsung memakai
      komponen baru, bukan menambah halaman gaya lama. Selesai di P4/P5 (sisa `[~]`
      tab `DetailLayout` dan wiring `UnconfirmedWriteNotice` dicatat di UI-2).

---

## 2. Kelompok Master Data

Dikerjakan sebelum Inventory/Finance karena menyuplai picker.

### 2.1 Product — perluasan modul existing (`[ESB-001]`, read-only `/product/list`)

- [x] S1 (2026-10-19, `[ESB-026]`): detail `GET /product/{productID}` dengan unit
      (product details). List tetap `/product/list` — `GET /product` hanya subset
      field-nya.
- [x] S2 (2026-10-19, `[ESB-029]`): delete / restore produk (`products.manage`).
- [!] S2: pending/temp product authorize/reject (`PATCH /product/authorize`,
      `/product/reject`, `GET /product/pending/list`, `/product/temp`) — menunggu
      jawaban ESB (pending vs temp, siapa mengotorisasi).
- [x] S3 (2026-10-19, `[ESB-031]`): create/update + unit. Update = baca dulu, field yang tidak
      diedit (BOM, CoreTax, custom fields, menu/cubication unit) dibawa. `isTemp` → toast
      "menunggu approval". Tepat satu base unit (asumsi). `pushToGoApp` tidak dikirim (`TBD`).
- [x] S4 (2026-10-19, `[ESB-032]`): import create (`POST`) / update (`PUT /product/import`).
- [x] S4 export (P25, ADR-031): CSV dibuat gateway, `GET /<list>/export` — file ERP
      `[ESB-032]` tidak dipakai.
- [!] Tanya ESB: beda produk "pending" vs "temp"; siapa yang mengotorisasi.

### 2.2 Category (`/product/category`)

- [x] S1 (2026-10-19, `[ESB-021]`): list (search, tipe, status) + detail (akun COA,
      depresiasi). `/all` untuk picker belum — picker category belum dibutuhkan.
- [x] S2 (2026-10-19, `[ESB-029]`): deactivate / restore / delete (`product-categories.manage`).
- [x] S3 (2026-10-19, `[ESB-030]`): create / update; semua field dikirim (PUT = ganti
      penuh), kosong sebagai `""`/`0`. Picker COA + category type.
- [x] S4 (2026-10-19, `[ESB-032]`): upload Excel (di-queue ERP).
- [x] S4 export (P25, ADR-031): CSV dibuat gateway, `GET /<list>/export` — file ERP
      `[ESB-032]` tidak dipakai.

### 2.3 Sub Category (`/product/sub-category`)

- [x] S1 (2026-10-19, `[ESB-022]`): list (search, status) + detail.
- [!] Filter berdasarkan category: vendor tidak mendokumentasikan filter category dan
      row sub-category tidak membawa `categoryID`. Tanya ESB.
- [x] S2 (2026-10-19, `[ESB-029]`): deactivate / restore / delete (`product-sub-categories.manage`).
- [x] S3 (2026-10-19, `[ESB-030]`): create / update. `flagActive` tidak dikirim — `TBD`
      apakah PUT tanpa flag mereset status.
- [x] S4 (2026-10-19, `[ESB-032]`): upload Excel (di-queue ERP).
- [x] S4 export (P25, ADR-031): CSV dibuat gateway, `GET /<list>/export` — file ERP
      `[ESB-032]` tidak dipakai.

### 2.4 Unit (`/units`)

- [x] S1 (2026-10-19, `[ESB-020]`): list (search, status) + detail. `/units/list` untuk
      picker belum.
- [x] S2 (2026-10-19, `[ESB-029]`): delete (bisa jadi hanya dinonaktifkan ERP — `hardDelete`
      diaudit) / restore (`units.manage`).
- [x] S3 (2026-10-19, `[ESB-030]`): create / update, picker metric + tax. Tanpa tax →
      `refTaxID` tidak dikirim (`TBD`: apakah update tanpa field ini menghapus referensi).
- [x] S4 (2026-10-19, `[ESB-032]`): import (di-queue ERP).
- [x] S4 export (P25, ADR-031): CSV dibuat gateway, `GET /<list>/export` — file ERP
      `[ESB-032]` tidak dipakai.

### 2.5 Pricelist (`/pricelist`)

- [x] S1 (2026-10-19, `[ESB-027]`): list pricelist aktif, list temp (filter status
      38/2), detail temp per nomor.
- [!] Detail pricelist aktif: row list membawa `ID` ("pricelist detail row ID"),
      detail meminta `pricelistID` ("master pricelist ID") — sama atau tidak tidak
      terdokumentasi. Tanya ESB.
- [x] S2 (2026-10-19, `[ESB-029]`): temp pricelist authorize / reject (status 38 saja) / delete
      (`pricelists.approve`, `pricelists.manage`). Authorize menyalin ke master pricelist dan
      menghapus temp (sudah didokumentasikan vendor).
- [x] S3 (2026-10-19, `[ESB-031]`): create (→ daftar temp) + edit temp; lock sama dengan
      authorize/reject/delete.
- [!] S3: edit pricelist aktif (`PUT /pricelist/{pricelistID}`) — ambigu ID (lihat S1).
- [x] S4 (2026-10-19, `[ESB-032]`): import; harga aktif per baris + riwayat 5 purchase
      invoice terakhir di form (harga 0 = tidak ada).
- [x] S4 export (P25, ADR-031): CSV dibuat gateway, `GET /<list>/export` — file ERP
      `[ESB-032]` tidak dipakai.
- [!] Tanya ESB: apakah create selalu menghasilkan temp (alur temp → aktif sudah jelas).

### 2.6 Food Cost Calculator (`POST /food-cost-calculator`)

- [!] Satu-satunya operasi didokumentasikan sebagai "Create a bill of materials" —
      tidak jelas apakah ini kalkulator (read) atau write BOM. **Jangan dibangun**
      sebelum ESB menjelaskan request/response dan efek sampingnya.
- [ ] Setelah jelas: tentukan hubungan dengan modul Bill of Material (`[ESB-014]`).

### 2.7 Chart of Account (`/accounting/coa`)

- [x] S1 (2026-10-19, `[ESB-025]`): outline akun aktif (`GET /accounting/coa`, urut
      `orderList`, indentasi per level; search + filter level + paging di gateway),
      detail `/{coaNo}` dengan mapping. `coaNo` wajib diawali digit (path vendor
      literal `/export`, `/index`, `/tax`).
- [!] Akun nonaktif tidak terlihat (list vendor "all active"); parent akun tidak
      terdokumentasi. Tanya ESB soal `/accounting/coa/index`.
- [x] S2 (2026-10-19, `[ESB-029]`): deactivate (DELETE vendor = soft delete beserta sub-akun
      aktif) / restore (`coa.manage`).
- [x] S3 (2026-10-19, `[ESB-031]`): reorder naik/turun (`PATCH /reorder`, `coa.manage`).
- [!] S3: create / update — `coaNo` "ignored on create" (penomoran tidak terdokumentasi),
      parent tidak ada di skema, detail tidak membawa `currencyID`. Tanya ESB.
- [x] S4 (2026-10-19, `[ESB-032]`): upload COA + account mapping (sinkron, jumlah tersimpan
      ditampilkan).
- [x] S4 export (P25, ADR-031): CSV dibuat gateway, `GET /<list>/export` — file ERP
      `[ESB-032]` tidak dipakai.
- [x] Permission khusus `coa.manage` — data akuntansi sensitif, audit wajib.
      `Permission::ManageCoa` menjaga deactivate/restore/reorder/upload; audit
      `coa.deactivated` / `coa.restored` / `coa.moved` / `coa.write-unconfirmed` dan
      `master-data.imported` untuk upload.

### 2.8 Payment Method

- [!] S1 **tidak dibangun** (2026-10-19), kontrak dicatat di `[ESB-028]`:
      - auth HTTP Basic `backend_username:backend_password`, bukan bearer token
        bersama (ADR-009) → butuh kredensial POS dari ESB/bisnis + ADR penyimpanannya;
      - respons bukan envelope ESB (objek ber-key "1", "2", tanpa `code`) → butuh ADR
        penanganan respons non-envelope;
      - `filterBranchCode` wajib; host staging-int family `pos` belum ada.
- [!] Tidak ada create/update di katalog Core maupun OMS. Tanya ESB.
- [x] POST-as-read diputuskan ADR-025 (`postRead()`), dipakai saat endpoint ini dibangun.

### 2.9 Supplier (`/supplier`)

- [x] S1 (2026-10-19, `[ESB-024]`): list + detail (kontak, rekening bank, kode produk
      supplier) dari satu call detail. Nomor rekening awalnya dimask (4 digit
      terakhir), utuh sejak P21.
- [x] Nomor rekening supplier **ditampilkan utuh** (keputusan bisnis 2026-10-22, P21) —
      masking dihapus di adapter supplier dan disbursement (§4.1); `accountNumber` /
      `bankAccountNumber`. Log integrasi tidak menyimpan body.
- [x] S2 (2026-10-19, `[ESB-029]`): deactivate (`DELETE` vendor) / restore (`suppliers.manage`).
- [!] S3: create / update — `greetingID` PIC wajib tanpa endpoint daftar greeting; `paymentID`
      = Payment Method (blocked); efek PATCH tanpa `supplierBank[]` tidak terdokumentasi dan
      Tanya ESB.
- [x] S4 (2026-10-19, `[ESB-032]`): upload create/update Excel; attachment upload / hapus
      semua (jumlah saja yang ditampilkan — URL file = soal yang sama dengan export).
- [x] S4 export (P25, ADR-031): CSV dibuat gateway, `GET /<list>/export` — file ERP
      `[ESB-032]` tidak dipakai.

### 2.10 Supplier Category (`/supplier/category`)

- [x] S1 (2026-10-19, `[ESB-023]`): list (`/supplier/category/list`, search nama,
      status) + detail.
- [x] S2 (2026-10-19, `[ESB-029]`): delete (pre-check `/check-delete` di luar lock) / restore
      (`supplier-categories.manage`).
- [x] S3 (2026-10-19, `[ESB-030]`): create (mengembalikan id → halaman record) / update;
      nama ASCII saja.

---

## 3. Kelompok Inventory

Semua dokumen transaksional: pola PO/Simple Purchase berlaku penuh (S1–S4).

### 3.1 Goods Transfer Request (`/inventory/goods-transfer-request`)

- [x] S1 (2026-10-20, `[ESB-033]`, ADR-026): list (nomor, status, branch asal, tanggal, search
      bebas) + detail. `POST /detail` (product details) ditunda ke S3 — dipakai saat create.
- [x] S2 (2026-10-20, `[ESB-034]`): authorize / reject (hanya saat New) / delete
      (disembunyikan bila `canDelete: false`); **finish / reopen** (`.manage`).
- [x] S3 (2026-10-20, `[ESB-035]`): create / update (PATCH penuh) — picker branch asal/tujuan,
      lokasi asal (opsional), product browse-transfer. Link purchase request dibawa saat
      edit. Request aset / `canEdit: false` tidak diedit di gateway. `POST /detail` tidak
      dipakai (stok tampil dari picker).
- [x] S4 (2026-10-20, `[ESB-036]`): attachment upload (gambar) / hapus semua.

### 3.2 Goods Delivery (`/inventory/goods-delivery`)

- [x] S1 (2026-10-20, `[ESB-033]`): list + detail + outstanding qty per baris (hanya bila ada
      referensi; gagal → kolom kosong). Multi-address belum dibaca.
- [x] S2 (2026-10-20, `[ESB-034]`): authorize / reject / delete.
- [!] S3: create **dari referensi** (`GET /create/{refNum}` → `POST /{refNum}`), update —
      refNum valid, batch expirasi, aset, multi-address tidak terdokumentasi cukup
      (`[ESB-035]`). Tanya ESB.
- [x] S4 (2026-10-20, `[ESB-036]`): attachment upload (hanya saat belum ada — upload vendor
      menyimpan hanya URL `existing` yang dikirim) / hapus semua.
- [!] S4: multi-address upload — bagian dari alur create-from-reference (di atas).
- [!] Tanya ESB: dokumen apa saja yang valid sebagai `refNum` (GTR? sales order?).

### 3.3 Goods Receipt (`/inventory/goods-receipt`)

- [x] S1 (2026-10-20, `[ESB-033]`): list + detail (invoice & delivery terkait) + stock-check
      (peringatan bila stok sudah dipakai). Expiration & asset belum dibaca.
- [x] S2 (2026-10-20, `[ESB-034]`): authorize / reject / delete.
- [!] S3: create dari referensi (`initialize`, `POST /{refNum}`), update — expirasi & aset
      tidak terdokumentasi cukup (`[ESB-035]`). `initialize` menyebut transType PO, Goods
      Delivery, Product Sales Return.
- [!] S4: asset images (aset tidak ditangani gateway); return browse/details = modul
      Goods Receipt Return sendiri. Tidak ada endpoint attachment untuk GR.
- [!] Tanya ESB: `refNum` valid (PO? Goods Delivery?).

### 3.4 Item Journal (`/inventory/item-journal`)

- [x] S1 (2026-10-20, `[ESB-033]`): list + detail (purpose & HPP per baris). Approval flow
      (`approval[]`, `approvalFlow[]`) belum dibaca — arti level tidak terdokumentasi.
- [x] S2 (2026-10-20, `[ESB-034]`): authorize / reject (alasan opsional) / delete.
- [x] S3 (2026-10-20, `[ESB-035]`): create / update (PUT) — picker branch, location, product,
      purpose (lookup baru), HPP per baris; qty boleh negatif; tanggal tidak di masa depan.
      Request template dibawa saat edit, tidak bisa dipilih.
- [x] S4 (2026-10-20, `[ESB-036]`): attachment (hanya saat belum ada) / hapus semua.

### 3.5 Simple Transfer (`/simple-transfer`)

- [x] S1 (2026-10-20, `[ESB-033]`): list (tanpa filter branch — vendor filter per lokasi) +
      detail + stock-check.
- [x] S2 (2026-10-20, `[ESB-034]`): authorize / reject / delete.
- [x] S3 (2026-10-20, `[ESB-035]`): create / update (PUT) — lokasi asal/tujuan; cost center &
      project dibawa saat edit (tanpa picker). Transfer beraset tidak diedit di gateway.
- [x] S4 (2026-10-20, `[ESB-036]`): import Excel (layar import bersama, di-queue ERP);
      attachment (hanya saat belum ada; vendor tidak punya hapus).

---

## 4. Kelompok Finance

### 4.1 Disbursement (`/finance/disbursement`)

- [x] S1 (2026-10-20, `[ESB-037]`): list (nomor, status, branch, supplier, cash/credit,
      referensi, overdue, tanggal) + detail (baris akun, purchase payment & supplier
      settlement terkait). Nomor rekening bank tampil utuh sejak P21 (sama dengan supplier).
- [x] S2 (2026-10-20, `[ESB-038]`): authorize / reject (alasan opsional) / delete; tombol
      mengikuti status terdokumentasi (decide: New / Waiting For Approval).
- [x] S3 (2026-10-20, `[ESB-039]`): create / update cash & credit; `PUT /{num}/validate`
      dipanggil saat membuka edit dan sebelum update (ditolak / tak terjawab → kembali ke
      detail, tanpa update). Picker branch, supplier, currency, COA cash/bank, COA per baris.
      Cost center, project, data bank dibawa saat edit (dari ERP, bukan browser).
- [!] S3: picker cost center / project (tidak ada lookup); data bank pada create baru.
- [x] S4 (2026-10-20, `[ESB-040]`): attachment (hanya saat belum ada; vendor tidak punya
      hapus), import Excel (layar import bersama, di-queue ERP), import form → mengisi baris
      form (ERP tidak menyimpan apa pun).
- [x] S4 export: CSV dari gateway (P25, ADR-031).
- [x] Permission `disbursements.approve` / `.manage` (Super Admin saja — dipertahankan,
      keputusan 2026-10-22).
- [x] Pemisahan tugas: **pembuat boleh mengotorisasi** (keputusan bisnis 2026-10-22). Cukup
      permission `.approve`; audit gateway mencatat orangnya.

---

## 5. Kelompok Accounting

### 5.1 Keputusan lintas modul

- [x] **ADR-025** (2026-10-19): `POST` yang oleh vendor didokumentasikan sebagai read
      dikirim lewat `EsbClient::postRead()` dan di-retry seperti `GET`. Opt-in per
      operasi, tidak pernah ditebak dari path. Dikunci di `EsbClientTest`.

### 5.2 General Ledger (`POST /accounting/general-ledger`, `/summary`)

- [x] S1 (2026-10-20, `[ESB-042]`): ledger per COA + periode (wajib, maks. 366 hari, paging
      di gateway karena vendor tidak mem-page) dan summary per branch/akun (paging vendor).
      `postRead()` (ADR-025). Tanpa saldo berjalan (vendor tidak memberi saldo awal).
- [x] Ekspor **CSV dari gateway** (P25, ADR-031): ledger (satu kali baca ESB) dan summary;
      export report ERP tidak dipakai. Filter cost center / project / rekonsiliasi belum ditawarkan.

### 5.3 General Journal (`/accounting/general-journal`)

- [x] S1 (2026-10-20, `[ESB-041]`): list (tanggal, teks bebas) + baris jurnal per halaman.
      Detail vendor hanya berisi baris (tanpa head).
- [!] Filter branch / akun / cost center / project: parameter array tidak terdokumentasi.
- [x] S4: export — CSV dari gateway (P25, ADR-031).

### 5.4 Memorial Journal (`/accounting/memorial-journal`)

- [x] S1 (2026-10-20, `[ESB-043]`): list (nomor, status, tanggal) + detail dengan baris
      debit/kredit (komponen `JournalLinesTable` bersama general journal).
- [!] `GET /access`: nama parameter query tidak terdokumentasi, dan "current user" = kredensial
      bersama gateway. Aksi S2 memakai permission gateway + status terdokumentasi.
- [x] S2 (2026-10-20, `[ESB-044]`): authorize / reject / delete. Alasan reject wajib di
      halaman hanya saat Waiting For Approval (ERP yang menegakkan); delete ditawarkan di
      semua status (ERP menolak jurnal yang sudah direkonsiliasi).
- [x] S3 (2026-10-20, `[ESB-045]`): create / update — baris debit/kredit (branch, COA,
      currency, rate; tepat satu sisi per baris, min. 2 baris). Total seimbang hanya hint;
      ERP otoritas. Update mengirim ulang cost center / project tiap baris lama (dibaca dari
      ERP per ID baris).
- [x] S4 (2026-10-20, `[ESB-046]`): asset images — satu gambar per upload (jpg/png, 2 MB,
      batas gateway), ditambahkan bukan mengganti (asumsi, `TBD`).
- [!] S4: `form-upload` / `upload` — didokumentasikan sebagai body JSON satu baris Excel,
      bukan field file multipart; nama field dan arti `typeID` tidak terdokumentasi.
- [x] S4: export — CSV dari gateway (P25, ADR-031).
- [!] Picker cost center / project — tidak ada lookup.

---

## 6. Kelompok POS (ESB OMS)

Semua di base URL OMS `core-api.esb.co.id`. Lihat juga item terbuka di `TODO.md` ›
Master Menu (token FnB, parsing `EC0110`).

### 6.1 Menu Category (OMS `get/create/update-menu-category`)

- [x] S1 (2026-10-20, `[ESB-047]`): list per halaman vendor (tanpa search); halaman satu
      kategori dicari di halaman asal lalu halaman lain, maks. 20 halaman (tidak ada operasi detail).
- [x] S3 (2026-10-20, `[ESB-048]`): create / update + category details; error per-field `EC0110`
      dari JSON di `message` (ErrorClassifier). Update wajib mempertahankan semua detail lama.
- [!] Alias ESB Order, image URL, `themeOptionCategoryOnPos`: tidak ada di list, jadi tidak dikirim
      (apakah ERP mempertahankannya `TBD`). Hapus detail: hanya di ERP.

### 6.2 Menu (perluasan `[ESB-012]` / `[ESB-013]`)

- [x] Keputusan bisnis (2026-10-22): **dua sumber tetap, satu modul**. `Menu` (Core) dan
      `MasterMenu` (OMS) digabung jadi satu modul "Menu": list dengan tab sumber
      Core | OMS, detail sesuai sumber, create/edit hanya di tab OMS.
- [x] Gabung (P24, 2026-10-22, ADR-030): `/menus?source=core|oms` (tab = link, filter per
      sumber), `/master-menus` → 301 ke tab OMS; detail & extras OMS tetap di URL lamanya;
      nav satu entri "Menus" (+ "Menu extras"); `pages/master-menus/index.tsx` dihapus.
- [!] S3: create / update OMS (`create-menu`, `update-menu`) — body terdokumentasi
      (dicek 2026-10-22 dari `api_data.json` vendor), tetapi field wajib
      `flagSeparatePrintPackage` / `flagSeparateTaxCalculation` / `checkerList` /
      `menuCategoryDetailID` tidak terbaca dari `get-menu` (update akan menebak dan menimpa),
      dan akun penjualan/HPP/diskon hanya "Account Description" tanpa daftar. Tanya ESB
      (`docs/esb/open-questions.md` 5.1a). Saat dibangun: di tab OMS (ADR-030).

### 6.3 Menu Extra

- [!] Tidak ada endpoint khusus di katalog. Body `create-menu` / `update-menu` memuat
      `menuExtras[]` nested (dicek 2026-10-22) — extra dikelola lewat create/update menu
      (§6.2 S3, blocked). Tanya ESB konfirmasi + efek extra yang tidak dikirim (5.1).
- [x] Sementara: tampilan read-only extra lintas menu dari data menu yang sudah ada
      (2026-10-21): `GET /master-menus/extras` meratakan extras satu halaman Get Menu
      (`[ESB-013]`, paging per menu, filter kode menu), menu nav "Menu extras".

### 6.4 Notes

- [x] **Tidak dibutuhkan** (keputusan bisnis 2026-10-22) — modul Notes tidak dibangun.

### 6.5 Promotion (OMS `Master Promotion`)

- [x] S1 (2026-10-20, `[ESB-049]`): list `GET /extv1/promotion` (array mentah tanpa envelope,
      ADR-027 `getBareList()`), filter branch code / promotion code, paging di gateway.
- [x] S3 (2026-10-20, `[ESB-050]`): create lima varian, satu form dengan pemilih tipe. Aturan per
      varian di `PromotionType` (satu FormRequest yang memilih aturan per tipe, bukan lima kelas);
      test per varian. Lookup OMS baru: menu categories, category details, POS menus.
- [!] Kode ESB Order payment method, visit purpose, employee group, BIN: diketik (tidak ada lookup).
- [!] Tanya ESB: update/nonaktifkan promosi; arti "ESO".

---

## 7. Modernisasi UI — modern, minimalis, user friendly

Dikerjakan bertahap. UI-1 dan UI-2 **sebelum** modul baru; UI-3 dst. paralel.

### UI-0 Tooling test frontend (TDD untuk UI)

- [x] ADR-024 (2026-10-19): `vp test` (Vitest 4.1.11 bawaan vite-plus) + React Testing
      Library + jsdom. Versi dipin exact, kompatibel Node 20.19 (lokal) dan 22 (CI).
- [x] Setup: blok `test` di `vite.config.ts` (bukan `vitest.config.ts`, sesuai
      rekomendasi vite-plus), `resources/js/test/setup.ts`, script `npm run test:ui`,
      masuk `composer ci:check` → CI. Test pertama: `components/heading.test.tsx`.
- [x] Setiap komponen bersama UI-2: test perilaku ditulis dulu (render, empty state,
      keyboard, aksesibilitas dasar) — P5, 2026-10-19.
- [x] Follow-up (di luar P3): `npm audit` melaporkan advisory di dependency lama
      (`@rolldown/plugin-babel`, `concurrently`, `@tailwindcss/vite`, `vite-plus`);
      upgrade Node lokal ke 22 agar bisa naik ke jsdom 30 / jest-dom 7.
      - [x] 2026-10-21: lockfile saja (range `package.json` tidak berubah) —
            `concurrently` 10.0.6 (`shell-quote` 1.12.0), `vite` 8.3.4, `source-map-js`
            1.2.2. Vite 8.3.5 sengaja dilewati (rilis hari itu; `--before=2026-10-09`).
      - [x] 2026-10-22 (ADR-028): `vite-plus` 1.1.0 (Vitest 5.0.3), `vite` di-alias ke
            `@voidzero-dev/vite-plus-core@1.1.0` + `overrides`; `tinypool` 2.2.0 — critical
            hilang. `@babel/core` 8.0.7 (low hilang).
      - [x] Node lokal 22.23.4 (npm 10.9.9) — dilakukan developer 2026-10-22.
      - [!] Sisa 5 advisory moderate: Vitest < 5.0.5 server `fs.deny` (hanya UI/browser
            mode, tidak dipakai). Fix = `vite-plus` 1.3.0; naikkan setelah ≥ 2026-10-30
            (umur dua minggu). Jangan pakai `vp test --ui` sebelum itu.
      - [x] jsdom 30.1.2 / jest-dom 7.0.1 (2026-10-22, pin exact; catatan di ADR-028).
- [ ] (Opsional, nanti) Playwright smoke test untuk alur login → list → detail.

### UI-1 Design foundation

- [x] Audit token (2026-10-19): token status `success` / `warning` / `info` ditambah
      (light + dark); bug dark `sidebar-primary-foreground` (kontras 1:1) dan light
      `muted-foreground` di atas `muted` (4,34:1) diperbaiki. Skala tipografi, radius,
      spacing didokumentasikan di `docs/08-frontend-guidelines.md`.
- [x] Warna aksen brand: **tetap palet netral** (keputusan 2026-10-22) sampai ada brand
      guideline.
- [x] Mode gelap setara mode terang — dijaga `resources/js/test/theme-tokens.test.ts`
      (paritas token + kontras WCAG AA semua pasangan teks/surface).
- [x] Format standar (2026-10-19): `resources/js/lib/format.ts` — `formatAmount`,
      `formatQuantity`, `formatDate`, `formatDateTime` (`id-ID`, `dd MMM yyyy`,
      Asia/Jakarta). Semua `Intl` / `toLocaleString` di page diganti (19 halaman).
- [x] Bahasa UI: **Inggris** (keputusan bisnis 2026-10-22). Tanpa i18n; format angka/tanggal
      tetap `DISPLAY_LOCALE` (`id-ID`).
- [x] Sapu string UI (P23, 2026-10-22): tidak ada string Indonesia tersisa; `APP_LOCALE=en`,
      `<html lang>` dari locale. Kata Indonesia di `StatusBadge` disengaja (mencocokkan teks
      status ERP).
- [x] Sisa warna hardcode di halaman starter kit (`auth/login`, `auth/forgot-password`,
      `input-error`, `delete-user`, juga `app-header`, `user-info`, `nav-footer`,
      `appearance-tabs`, `auth-split-layout`) → token (P17). Dijaga audit warna.

### UI-2 Komponen bersama (test dulu)

Semua di `resources/js/components/` / `resources/js/hooks/`, test di sebelah file-nya
(2026-10-19). Referensi pemakaian: `pages/purchase-orders/index.tsx` + `show.tsx`.

- [x] `PageHeader` — judul, deskripsi, link kembali, slot aksi (`role=group`).
      Breadcrumb tetap di layout (UI-3).
- [x] `FilterBar` — search selalu tampil, filter lanjutan di balik "More filters"
      (Collapsible, tanpa dependency baru), chip filter aktif + "Reset filters", Enter
      submit. Hook `useListQuery` (buang nilai kosong, paging, reset).
- [x] `DateRangeField` — dua input date native, `min` di ujung akhir.
- [x] `DataTable` — header sticky (container scroll sendiri), angka rata kanan,
      baris klik ke detail, skeleton + `aria-busy`, empty state, error `role=alert`.
- [x] `ListPagination` — satu komponen untuk semua list.
- [x] `StatusBadge` — tone dari kata kunci (EN/ID), fallback netral; teks tetap dari
      ERP. Asumsi: kosakata status ERP tidak terdokumentasi (baru terlihat `New`,
      `Authorized`).
- [x] `DetailLayout` — `DetailSummary` (kartu ringkasan) + `DetailFields`/`DetailField`
      selesai. Tab (2026-10-22, ADR-029): `ui/tabs.tsx` (Radix) + `DetailTabs` (label, jumlah
      baris per tab, keyboard). Pemakai pertama: `master-menus/show` (General di atas;
      Prices / Package / Extras / Related menus sebagai tab). Juga `bills-of-material/show`
      (Materials / Other costs / Replacements) dan `suppliers/show` (Contacts / Bank
      accounts / Supplier product codes). Halaman dokumen (PO, inventory, …) tidak perlu:
      satu tabel baris saja.
- [x] `ApprovalActions` — authorize/reject/delete + dialog konfirmasi, alasan reject
      opsional (`rejectReason`), semua kontrol terkunci selama write berjalan.
- [x] `UnconfirmedWriteNotice` — di-wire (2026-10-21): `HandlesDocumentWrites` mem-flash
      `unconfirmedWrite` (`action`, `reference`) untuk decision/transition/upload yang
      mendarat di halaman detail (ganti toast `warning`); `UnconfirmedWriteBanner` di
      kedua app layout. Delete dan authorize yang menghapus dokumen (temp pricelist)
      tetap toast karena mendarat di list. Purchase Request ikut banner sejak P20
      (migrasi ke `DocumentWrites`, lihat §1).
- [x] Toast (`sonner`) untuk hasil write — sudah ada (`use-flash-toast`).
- [x] Picker searchable (P6): `Combobox` dibuat sendiri (pola WAI-ARIA 1.2 combobox +
      listbox, keyboard, debounce, load more, respons basi diabaikan) — **tanpa
      dependency baru**, jadi tidak perlu ADR. `LookupCombobox` untuk `/lookups`.
      Date range tetap `DateRangeField` (input date native).
- [x] Form: `FormActions` (bar tombol, opsi sticky, hint) + hook
      `useUnsavedChangesWarning` (konfirmasi di navigasi GET + `beforeunload`, submit
      tidak diblok). Label di atas / error di bawah = pola existing (`InputError`).
### UI-3 Navigasi

- [x] Sidebar dikelompokkan: Dashboard, Master Data, Purchase, Inventory, Finance,
      Accounting, POS, Sales, System (logs, endpoint, koneksi, users). Grup bisa
      collapse, ingat state (`localStorage` `nav-groups`), ikon per grup. Konfigurasi
      menu di `lib/navigation.ts` (P16, 2026-10-21).
- [x] Command palette `Ctrl+K` — lompat ke halaman / buka dokumen berdasarkan nomor
      (`components/command-palette.tsx`, tanpa dependency baru).
- [x] Breadcrumb konsisten: diturunkan dari URL + menu (`lib/breadcrumbs.ts`); blok
      breadcrumb statis per halaman dihapus. Halaman di luar menu (settings, import)
      menamai sendiri.

### UI-4 Dashboard

- [x] Kartu "Waiting for approval" (deferred, `PendingApprovals`): status 38 untuk
      disbursement, memorial journal, item journal, proposed prices — hanya modul yang
      user boleh setujui (+ `esb.invoke`); hitungan gagal = "Unknown".
- [!] Dokumen purchasing (PR/PO/PI/SP) dan GTR/GD/GR/ST: list tanpa filter status /
      status "waiting" tidak terdokumentasi (§9 `/common/status/mapping`).
- [x] Status koneksi ESB per family (panggilan terakhir + waktunya) + error integrasi
      terbaru.
- [x] Shortcut ke aksi yang sering dipakai (digate permission).
- [ ] Pakai skill `dataviz` bila menambah grafik (belum ada grafik).

### UI-5 Responsif & aksesibilitas

- [x] Tabel jadi daftar kartu di layar < 640px: `DataTable` (header disembunyikan secara
      visual, sel berlabel `data-label`, role tabel eksplisit) — P17, 2026-10-21.
- [x] Focus ring terlihat (outline `--foreground` untuk link/elemen fokus tanpa ring
      sendiri), `aria-label` pada tombol ikon (2 di `app-header` diperbaiki; select role
      di Users diberi nama), satu `h1` per halaman (`PageHeader` → `Heading level={1}`).
      Kontras tetap dijaga `theme-tokens.test.ts`. Audit lintas frontend:
      `resources/js/test/accessibility-audit.test.tsx`.
- [x] Target sentuh ≥ 44px di perangkat sentuh (`pointer-coarse:`): `Button`, `Input`,
      `ChipRemoveButton` (hit area lewat pseudo-element).
- [ ] (Opsional) Uji manual di browser/perangkat sentuh dan pembaca layar — belum.

### UI-6 Migrasi halaman lama (satu modul per commit)

- [x] products, menus, master-menus, bills-of-material, purposes, document-templates,
      document-template-imports — list ke `PageHeader` + `FilterBar` (`useFilterForm`) +
      `DataTable` + `ListPagination` + `StatusBadge`; show ke `PageHeader` (P17).
- [x] purchase-orders (referensi P5); purchase-requests, purchase-invoices,
      simple-purchases — list penuh; show/form/import ke `PageHeader` + `StatusBadge`.
      Kontrol authorize/reject/delete di show PR/PI/SP pakai `ApprovalActions` (PR P20,
      PI/SP P22; alasan reject hanya SP, wajib, maks. 500).
- [x] sales-pushes, integration-logs, endpoints, users (list), esb-connections, dashboard
      (header). Status gateway (push, outcome log) tetap `Badge` warnanya sendiri, bukan
      tebakan `StatusBadge`.
- [x] Test PHP existing tetap hijau; filter, paging, select "apply langsung" sama seperti
      sebelumnya. Perubahan terlihat: filter sekunder pindah ke "More filters" + chip,
      baris list bisa diklik ke detail, kartu di layar sempit.

---

## 8. Urutan pengerjaan yang disarankan

1. §1 Fondasi (refactor decision + lookup) dan UI-0 → UI-2.
2. Master Data: Unit → Category → Sub Category → Supplier Category → Supplier →
   COA → Product (perluasan) → Pricelist → Payment Method.
3. Inventory: Goods Transfer Request → Goods Delivery → Goods Receipt → Simple
   Transfer → Item Journal.
4. Finance: Disbursement.
5. Accounting: General Journal → General Ledger → Memorial Journal.
6. POS: Menu Category → Promotion → Menu (gabung Core + OMS) → Menu Extra (setelah
   klarifikasi ESB). Notes tidak dibangun.
7. UI-3 → UI-6 berjalan paralel setelah komponen bersama stabil.

Setiap modul: S1 dulu untuk semua modul dalam kelompok baru, baru S2/S3 — agar
nilai read-only cepat terlihat dan kontrak ESB tervalidasi lebih awal.

---

## 9. Pertanyaan terbuka (kumpulkan untuk ESB / bisnis)

- [ ] ESB: Food Cost Calculator — kontrak dan efek samping.
- [ ] ESB: Menu Extra & Notes — endpoint yang benar.
- [ ] ESB: Payment Method — ada create/update? Kredensial backend POS (Basic auth) dan
      host staging-int family `pos`.
- [ ] ESB: pricelist — `ID` di list = `pricelistID` di detail?
- [ ] ESB: COA — akun nonaktif / parent akun (`/accounting/coa/index`?).
- [x] Bisnis: nomor rekening supplier — **utuh** (2026-10-22).
- [ ] ESB: Promotion — update/nonaktifkan; arti "ESO".
- [ ] ESB: `refNum` valid untuk Goods Delivery & Goods Receipt; cara mengisi batch expirasi.
- [ ] ESB: memorial journal `/access` — nama parameter query nomor jurnal.
- [ ] ESB: memorial journal `form-upload` / `upload` — nama field file multipart (dokumentasi
      menulis body JSON satu baris) dan arti `typeID` (debit/kredit?).
- [ ] ESB: menu category — field yang tidak dikirim saat update (alias ESO, image, theme option)
      dipertahankan? Detail yang tidak dikirim dihapus? Filter id untuk get-menu-category.
- [ ] ESB: promotion — boolean "Yes"/"No" vs Boolean; `applyTo` label vs Integer; `visitPurposeID`
      integer vs string; respons list tanpa envelope dan 401 `code: 0` (ADR-027).
- [ ] ESB: memorial journal — baris dengan debit **dan** kredit diterima? `asset-images`
      menambah atau mengganti? Batas ukuran/tipe gambar?
- [ ] ESB: disbursement create — apakah `result.disbursementNum` dikirim (contoh `{}`).
- [x] Bisnis: ekspor general ledger — **CSV dari gateway**, juga untuk semua export lain
      (2026-10-22).
- [ ] ESB: upload attachment inventory — format field `existing`, dan apakah upload GTR
      menambah atau mengganti.
- [ ] ESB: nomor dokumen di respons create GTR / Simple Transfer (dokumentasi: map string).
- [x] Bisnis: finish / reopen GTR — **`.manage`** (2026-10-22, sesuai implementasi).
- [ ] ESB: status mana yang menerima authorize/reject/delete untuk tiap dokumen baru.
- [ ] ESB: Product pending vs temp.
- [ ] ESB: format parameter query multi-value (`branchIDs=1&branchIDs=2` atau `branchIDs[]=1`).
- [ ] ESB: arti `/common/status/mapping` (status mana untuk dokumen mana).
- [x] Bisnis: role pemegang `.approve` / `.manage` — **Super Admin saja dulu** (2026-10-22).
- [x] Bisnis: sumber menu — **Core + OMS dalam satu modul** (2026-10-22, §6.2).
- [x] Bisnis: bahasa UI — **Inggris** (2026-10-22).
- [x] Bisnis: pemisahan tugas — **pembuat boleh mengotorisasi** (2026-10-22).
- [x] Bisnis: Notes — **tidak dibutuhkan** (2026-10-22).
- [x] Bisnis: warna brand — **tetap netral** (2026-10-22).
- [ ] ESB: semua pertanyaan di atas dikirim lewat `docs/esb/open-questions.md`.
