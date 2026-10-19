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
   `docs/05-api-integration.md` (nomor berikutnya: **`[ESB-024]`**). Jangan mengarang
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
| P8 | Master Data S1: Supplier, COA, Product (perluasan), Pricelist, Payment Method | `[ ]` |
| P9 | Master Data S2–S4 | `[ ]` |
| P10 | Inventory S1 (GTR, Goods Delivery, Goods Receipt, Simple Transfer, Item Journal) | `[ ]` |
| P11 | Inventory S2–S4 | `[ ]` |
| P12 | Finance: Disbursement S1–S4 | `[ ]` |
| P13 | Accounting S1 (General Journal, General Ledger, Memorial Journal) | `[ ]` |
| P14 | Accounting S2–S4 (Memorial Journal) | `[ ]` |
| P15 | POS: Menu Category, Promotion (+ Menu setelah keputusan sumber) | `[ ]` |
| P16 | UI-3 navigasi + UI-4 dashboard | `[ ]` |
| P17 | UI-5 responsif/aksesibilitas + UI-6 migrasi halaman lama | `[ ]` |

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
  - [ ] Purchase Request **belum** dimigrasi: audit unconfirmed decision-nya
        (`purchase-request.decision-unconfirmed`, key `decision`) beda dengan modul
        lain (`*.write-unconfirmed`, key `operation`). Menyeragamkan = perubahan
        perilaku audit → butuh keputusan dulu.
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
- [ ] Selesaikan **Fase UI-1 dan UI-2** (§7) dulu agar modul baru langsung memakai
      komponen baru, bukan menambah halaman gaya lama.

---

## 2. Kelompok Master Data

Dikerjakan sebelum Inventory/Finance karena menyuplai picker.

### 2.1 Product — perluasan modul existing (`[ESB-001]`, read-only `/product/list`)

- [ ] S1: detail `GET /product/{productID}`; list paginated `GET /product` (bandingkan
      dengan `/product/list`, putuskan satu).
- [ ] S2: pending/temp product authorize/reject (`PATCH /product/authorize`,
      `/product/reject`, `GET /product/pending/list`, `/product/temp`); delete/restore.
- [ ] S3: create/update (`POST /product`, `PUT /product/{productID}`). Butuh picker
      Category, Sub Category, Unit, COA.
- [ ] S4: import/export (`POST|PUT /product/import`, `POST /product/export`).
- [!] Tanya ESB: beda produk "pending" vs "temp"; siapa yang mengotorisasi.

### 2.2 Category (`/product/category`)

- [x] S1 (2026-10-19, `[ESB-021]`): list (search, tipe, status) + detail (akun COA,
      depresiasi). `/all` untuk picker belum — picker category belum dibutuhkan.
- [ ] S2: inactive / restore / delete.
- [ ] S3: create / update.
- [ ] S4: upload / export Excel.

### 2.3 Sub Category (`/product/sub-category`)

- [x] S1 (2026-10-19, `[ESB-022]`): list (search, status) + detail.
- [!] Filter berdasarkan category: vendor tidak mendokumentasikan filter category dan
      row sub-category tidak membawa `categoryID`. Tanya ESB.
- [ ] S2–S4 sama seperti Category.

### 2.4 Unit (`/units`)

- [x] S1 (2026-10-19, `[ESB-020]`): list (search, status) + detail. `/units/list` untuk
      picker belum.
- [ ] S2: delete / restore.
- [ ] S3: create / update.
- [ ] S4: import / export.

### 2.5 Pricelist (`/pricelist`)

- [ ] S1: list + detail pricelist, list temp pricelist.
- [ ] S2: temp pricelist authorize / reject / delete.
- [ ] S3: create / update (pricelist & temp).
- [ ] S4: import / export, `last-price`, history purchase invoice.
- [!] Tanya ESB: alur temp → aktif; apakah create selalu menghasilkan temp.

### 2.6 Food Cost Calculator (`POST /food-cost-calculator`)

- [!] Satu-satunya operasi didokumentasikan sebagai "Create a bill of materials" —
      tidak jelas apakah ini kalkulator (read) atau write BOM. **Jangan dibangun**
      sebelum ESB menjelaskan request/response dan efek sampingnya.
- [ ] Setelah jelas: tentukan hubungan dengan modul Bill of Material (`[ESB-014]`).

### 2.7 Chart of Account (`/accounting/coa`)

- [ ] S1: list (tampilan pohon per level: `/by-level/{coaLevel}`), detail `/{coaNo}`.
- [ ] S2: delete / restore.
- [ ] S3: create / update; reorder (`PATCH /reorder`) dipisah sebagai slice sendiri.
- [ ] S4: upload / export.
- [ ] Permission khusus `coa.manage` — data akuntansi sensitif, audit wajib.

### 2.8 Payment Method

- [ ] S1 read-only: OMS Master POS `POST /external/general/get-payment-method`
      (base URL family `esbcore.co.id` — pastikan family sudah ada di `config/esb.php`).
- [!] Tidak ada create/update di katalog Core maupun OMS. Tanya ESB.
- [ ] Catatan: POST dipakai untuk read → tentukan apakah boleh di-retry (lihat §5.1).

### 2.9 Supplier (`/supplier`)

- [ ] S1: list, detail, banks, contacts.
- [ ] S2: deactivate (`DELETE`) / restore.
- [ ] S3: create / update (`PATCH`). Butuh picker Supplier Category.
- [ ] S4: upload create/update Excel, export, attachment.

### 2.10 Supplier Category (`/supplier/category`)

- [x] S1 (2026-10-19, `[ESB-023]`): list (`/supplier/category/list`, search nama,
      status) + detail.
- [ ] S2: delete (pakai `/check-delete` sebagai pre-check) / restore.
- [ ] S3: create / update.

---

## 3. Kelompok Inventory

Semua dokumen transaksional: pola PO/Simple Purchase berlaku penuh (S1–S4).

### 3.1 Goods Transfer Request (`/inventory/goods-transfer-request`)

- [ ] S1: list, detail `/{transferNum}`, product details (`POST /detail`).
- [ ] S2: authorize / reject / delete; **finish / unfinished** sebagai aksi tambahan.
- [ ] S3: create / update — picker branch asal/tujuan, product browse-transfer.
- [ ] S4: attachment upload / delete.

### 3.2 Goods Delivery (`/inventory/goods-delivery`)

- [ ] S1: list, detail `/{goodsDeliveryNum}`, outstanding qty.
- [ ] S2: authorize / reject / delete.
- [ ] S3: create **dari referensi** (`GET /create/{refNum}` → `POST /{refNum}`), update.
- [ ] S4: attachment, multi-address upload.
- [!] Tanya ESB: dokumen apa saja yang valid sebagai `refNum` (GTR? sales order?).

### 3.3 Goods Receipt (`/inventory/goods-receipt`)

- [ ] S1: list, detail, `stock-check/{num}`.
- [ ] S2: authorize / reject / delete.
- [ ] S3: create dari referensi (`initialize`, `POST /{refNum}`), update.
- [ ] S4: asset images; return browse/details (read).
- [!] Tanya ESB: `refNum` valid (PO? Goods Delivery?).

### 3.4 Item Journal (`/inventory/item-journal`)

- [ ] S1: list, detail.
- [ ] S2: authorize / reject / delete.
- [ ] S3: create / update — picker location & product.
- [ ] S4: attachment.

### 3.5 Simple Transfer (`/simple-transfer`)

- [ ] S1: list, detail, stock-check.
- [ ] S2: authorize / reject / delete.
- [ ] S3: create / update.
- [ ] S4: import file, attachment.

---

## 4. Kelompok Finance

### 4.1 Disbursement (`/finance/disbursement`)

- [ ] S1: list, detail.
- [ ] S2: authorize / reject / delete.
- [ ] S3: create / update; pakai `PUT /{num}/validate` sebagai pre-check sebelum update.
      Picker COA cash/bank, supplier, cost center.
- [ ] S4: import, import form, attachment.
- [ ] Permission `disbursements.approve` / `.manage`; pertimbangkan pemisahan tugas
      (pembuat ≠ pengotorisasi) — keputusan bisnis.

---

## 5. Kelompok Accounting

### 5.1 Keputusan lintas modul

- [x] **ADR-025** (2026-10-19): `POST` yang oleh vendor didokumentasikan sebagai read
      dikirim lewat `EsbClient::postRead()` dan di-retry seperti `GET`. Opt-in per
      operasi, tidak pernah ditebak dari path. Dikunci di `EsbClientTest`.

### 5.2 General Ledger (`POST /accounting/general-ledger`, `/summary`)

- [ ] S1 read-only: laporan ledger per COA + periode, dan summary. Filter wajib periode
      dan COA (picker dari §2.7).
- [ ] Ekspor CSV di sisi gateway? → putuskan; jangan menghitung saldo sendiri.

### 5.3 General Journal (`/accounting/general-journal`)

- [ ] S1 read-only: search, detail `/{journalHeadID}/detail`.
- [ ] S4: export.

### 5.4 Memorial Journal (`/accounting/memorial-journal`)

- [ ] S1: list, detail; `GET /access` untuk menyembunyikan aksi yang tidak diizinkan ERP.
- [ ] S2: authorize / reject / delete.
- [ ] S3: create / update — baris debit/kredit dengan picker COA; validasi seimbang
      hanya sebagai UX hint, ERP tetap otoritas.
- [ ] S4: upload bulk (`form-upload` → `upload`, pola ADR-021/ADR-023), export,
      asset images.

---

## 6. Kelompok POS (ESB OMS)

Semua di base URL OMS `core-api.esb.co.id`. Lihat juga item terbuka di `TODO.md` ›
Master Menu (token FnB, parsing `EC0110`).

### 6.1 Menu Category (OMS `get/create/update-menu-category`)

- [ ] S1: list.
- [ ] S3: create / update; parse error per-field `EC0110` dari JSON di `message`.

### 6.2 Menu (perluasan `[ESB-012]` / `[ESB-013]`)

- [!] **Keputusan bisnis dulu**: sumber menu Core (`/menu/browse`) atau OMS
      (`get-menu`) — lalu gabungkan `Menu` & `MasterMenu` atau hapus satu.
- [ ] S3: create / update OMS (`create-menu`, `update-menu`) — butuh picker Menu
      Category (§6.1) dan Menu Template.

### 6.3 Menu Extra

- [!] Tidak ada endpoint khusus di katalog. Extra saat ini hanya terbaca dari detail
      OMS master menu. Tanya ESB: apakah extra dikelola lewat `create/update-menu`
      (nested) atau endpoint lain yang belum terdokumentasi.
- [ ] Sementara: tampilan read-only extra lintas menu dari data menu yang sudah ada.

### 6.4 Notes

- [!] Tidak ada operasi "notes" di katalog Core maupun OMS. Klarifikasi dengan bisnis:
      notes menu POS (catatan pesanan) di ERP, atau fitur lokal gateway? **Jangan
      dibangun** sampai jelas — bila lokal, butuh ADR karena gateway jadi pemilik data.

### 6.5 Promotion (OMS `Master Promotion`)

- [ ] S1: list `GET /extv1/promotion`.
- [ ] S3: create — lima varian di path yang sama `POST /corev1/promotion/`
      (Discount Rp ESO, Discount Limit %, Discount %, Discount % ESO, Free Item).
      Form dengan pemilih tipe; satu FormRequest per varian; test per varian.
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
- [ ] Follow-up (di luar P3): `npm audit` melaporkan advisory di dependency lama
      (`@rolldown/plugin-babel`, `concurrently`, `@tailwindcss/vite`, `vite-plus`);
      upgrade Node lokal ke 22 agar bisa naik ke jsdom 30 / jest-dom 7.
- [ ] (Opsional, nanti) Playwright smoke test untuk alur login → list → detail.

### UI-1 Design foundation

- [x] Audit token (2026-10-19): token status `success` / `warning` / `info` ditambah
      (light + dark); bug dark `sidebar-primary-foreground` (kontras 1:1) dan light
      `muted-foreground` di atas `muted` (4,34:1) diperbaiki. Skala tipografi, radius,
      spacing didokumentasikan di `docs/08-frontend-guidelines.md`.
- [!] Warna aksen brand: masih palet netral shadcn. Butuh keputusan bisnis (warna brand).
- [x] Mode gelap setara mode terang — dijaga `resources/js/test/theme-tokens.test.ts`
      (paritas token + kontras WCAG AA semua pasangan teks/surface).
- [x] Format standar (2026-10-19): `resources/js/lib/format.ts` — `formatAmount`,
      `formatQuantity`, `formatDate`, `formatDateTime` (`id-ID`, `dd MMM yyyy`,
      Asia/Jakarta). Semua `Intl` / `toLocaleString` di page diganti (19 halaman).
- [!] Putuskan bahasa UI (Indonesia / Inggris) — keputusan bisnis. Locale format sudah
      satu konstanta (`DISPLAY_LOCALE`); string UI belum dipusatkan.
- [ ] Sisa warna hardcode di halaman starter kit (`auth/login`, `auth/forgot-password`,
      `input-error`, `delete-user`) → pindah ke token saat UI-6.

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
- [~] `DetailLayout` — `DetailSummary` (kartu ringkasan) + `DetailFields`/`DetailField`
      selesai. **Tab belum**: butuh `@radix-ui/react-tabs` → ADR dulu.
- [x] `ApprovalActions` — authorize/reject/delete + dialog konfirmasi, alasan reject
      opsional (`rejectReason`), semua kontrol terkunci selama write berjalan.
- [~] `UnconfirmedWriteNotice` — komponen selesai. **Belum di-wire**: server baru
      mengirim toast `warning` berisi teks; banner butuh flash terstruktur
      (`operation`, `number`, `correlation_id`) dari `HandlesDocumentWrites`.
- [x] Toast (`sonner`) untuk hasil write — sudah ada (`use-flash-toast`).
- [x] Picker searchable (P6): `Combobox` dibuat sendiri (pola WAI-ARIA 1.2 combobox +
      listbox, keyboard, debounce, load more, respons basi diabaikan) — **tanpa
      dependency baru**, jadi tidak perlu ADR. `LookupCombobox` untuk `/lookups`.
      Date range tetap `DateRangeField` (input date native).
- [x] Form: `FormActions` (bar tombol, opsi sticky, hint) + hook
      `useUnsavedChangesWarning` (konfirmasi di navigasi GET + `beforeunload`, submit
      tidak diblok). Label di atas / error di bawah = pola existing (`InputError`).
### UI-3 Navigasi

- [ ] Sidebar dikelompokkan: Dashboard, Master Data, Purchase, Inventory, Finance,
      Accounting, POS, Sales, Sistem (logs, endpoint, koneksi, users). Grup bisa
      collapse, ingat state, ikon per grup.
- [ ] Command palette `Ctrl+K` — lompat ke modul/dokumen berdasarkan nomor.
- [ ] Breadcrumb konsisten di semua halaman.

### UI-4 Dashboard

- [ ] Kartu "Menunggu persetujuan" lintas modul (yang user boleh setujui).
- [ ] Status koneksi ESB + error integrasi terbaru.
- [ ] Shortcut ke aksi yang sering dipakai.
- [ ] Pakai skill `dataviz` bila menambah grafik.

### UI-5 Responsif & aksesibilitas

- [ ] Tabel jadi daftar kartu di layar < 640px.
- [ ] Navigasi keyboard penuh, focus ring terlihat, `aria-label` pada tombol ikon,
      kontras minimal WCAG AA.
- [ ] Target sentuh ≥ 44px di mobile.

### UI-6 Migrasi halaman lama (satu modul per commit)

- [ ] products, menus, master-menus, bills-of-material, purposes, document-templates,
      document-template-imports
- [~] purchase-orders (index + show, 2026-10-19, halaman referensi P5);
      purchase-requests, purchase-invoices, simple-purchases belum
- [ ] sales-pushes, integration-logs, endpoints, esb-connections, users, dashboard
- [ ] Setiap migrasi: test PHP existing tetap hijau; tidak ada perubahan perilaku.

---

## 8. Urutan pengerjaan yang disarankan

1. §1 Fondasi (refactor decision + lookup) dan UI-0 → UI-2.
2. Master Data: Unit → Category → Sub Category → Supplier Category → Supplier →
   COA → Product (perluasan) → Pricelist → Payment Method.
3. Inventory: Goods Transfer Request → Goods Delivery → Goods Receipt → Simple
   Transfer → Item Journal.
4. Finance: Disbursement.
5. Accounting: General Journal → General Ledger → Memorial Journal.
6. POS: Menu Category → Promotion → Menu (setelah keputusan sumber) → Menu Extra /
   Notes (setelah klarifikasi).
7. UI-3 → UI-6 berjalan paralel setelah komponen bersama stabil.

Setiap modul: S1 dulu untuk semua modul dalam kelompok baru, baru S2/S3 — agar
nilai read-only cepat terlihat dan kontrak ESB tervalidasi lebih awal.

---

## 9. Pertanyaan terbuka (kumpulkan untuk ESB / bisnis)

- [ ] ESB: Food Cost Calculator — kontrak dan efek samping.
- [ ] ESB: Menu Extra & Notes — endpoint yang benar.
- [ ] ESB: Payment Method — ada create/update?
- [ ] ESB: Promotion — update/nonaktifkan; arti "ESO".
- [ ] ESB: `refNum` valid untuk Goods Delivery & Goods Receipt.
- [ ] ESB: status mana yang menerima authorize/reject/delete untuk tiap dokumen baru.
- [ ] ESB: Product pending vs temp.
- [ ] ESB: format parameter query multi-value (`branchIDs=1&branchIDs=2` atau `branchIDs[]=1`).
- [ ] ESB: arti `/common/status/mapping` (status mana untuk dokumen mana).
- [ ] Bisnis: role pemegang `.approve` / `.manage` per modul baru.
- [ ] Bisnis: sumber menu (Core vs OMS).
- [ ] Bisnis: bahasa UI.
- [ ] Bisnis: pemisahan tugas pembuat vs pengotorisasi (Finance/Accounting).
