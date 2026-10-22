# Pertanyaan untuk Tim ESB — Integrasi ERP Gateway

Tanggal: 22 Oktober 2026
Dari: Tim ERP Gateway (Maharasa)

Dokumen ini berisi pertanyaan tentang API ESB Core dan ESB OMS yang belum terjawab di
dokumentasi. Setiap pertanyaan menyebut endpoint, apa yang kami lihat di dokumentasi, dan
jawaban yang kami butuhkan. Mohon jawaban dicantumkan langsung di bawah nomor pertanyaan
(contoh payload request/response sangat membantu).

Prioritas: **A** = memblokir fitur yang sedang dikerjakan, **B** = memblokir fitur
berikutnya, **C** = konfirmasi asumsi yang sudah kami pakai.

---

## 1. Umum (berlaku untuk banyak endpoint)

**1.1 [A] Format parameter query multi-value.**
Beberapa endpoint list menerima filter berupa array (mis. `branchIDs`, akun, cost center,
project di `/accounting/general-journal`). Format mana yang diterima:
`branchIDs=1&branchIDs=2`, `branchIDs[]=1&branchIDs[]=2`, atau `branchIDs=1,2`?

**1.2 [A] Arti `/common/status/mapping`.**
Status mana yang berlaku untuk dokumen mana? Secara khusus: untuk tiap dokumen di bawah,
status apa saja yang menerima **authorize**, **reject**, dan **delete**, dan status apa yang
berarti "menunggu approval":
Purchase Request, Purchase Order, Purchase Invoice, Simple Purchase, Goods Transfer Request,
Goods Delivery, Goods Receipt, Item Journal, Simple Transfer, Disbursement, Memorial Journal.
Apakah list dokumen-dokumen tersebut bisa difilter berdasarkan status?

**1.3 [C] Upload attachment — field `existing`.**
Upload attachment Goods Delivery, Item Journal, Simple Transfer, dan Disbursement
mendokumentasikan field `existing` (URL attachment lama yang dipertahankan).
- Format nilainya apa (URL penuh, path, atau ID)? Dikirim sebagai `existing[]`?
- Bila `existing` tidak dikirim, apakah attachment lama dihapus?
- Upload Goods Transfer Request tidak punya `existing`: apakah upload **menambah** atau
  **mengganti** attachment lama?

**1.4 [C] URL file export/attachment.**
Respons export dan attachment mengembalikan URL file dengan key dan host yang tidak konsisten
antar endpoint. Apakah ada format baku? (Catatan: untuk export kami akan membuat CSV sendiri di
gateway, jadi ini hanya untuk menampilkan attachment.)

---

## 2. Master Data

**2.1 [B] Product — pending vs temp.**
Apa beda produk "pending" (`GET /product/pending/list`) dengan produk "temp"
(`GET /product/temp`)? `PATCH /product/authorize` dan `/product/reject` berlaku untuk yang
mana? Siapa yang biasanya mengotorisasi?

**2.2 [C] Sub Category — filter category dan status.**
- Apakah `/product/sub-category` bisa difilter berdasarkan category? Row list tidak membawa
  `categoryID`.
- `PUT` sub-category tanpa `flagActive`: apakah status aktif direset?

**2.3 [C] Unit — `refTaxID`.**
Bila `PUT /units/{id}` dikirim tanpa `refTaxID`, apakah referensi pajak yang ada dihapus?

**2.4 [B] Pricelist — ID list vs detail.**
Row list pricelist aktif membawa `ID` ("pricelist detail row ID"), sedangkan detail dan
`PUT /pricelist/{pricelistID}` meminta `pricelistID` ("master pricelist ID"). Apakah nilainya
sama? Jika tidak, bagaimana mendapatkan `pricelistID` dari row list?
Juga: apakah `POST /pricelist` **selalu** menghasilkan pricelist temp (perlu authorize)?

**2.5 [B] Food Cost Calculator — `POST /food-cost-calculator`.**
Dokumentasi menyebutnya "Create a bill of materials". Apakah ini hanya kalkulasi (tidak
menyimpan apa pun) atau membuat/mengubah BOM? Mohon contoh request/response dan efek
sampingnya.

**2.6 [B] Chart of Account.**
- List `GET /accounting/coa` hanya akun aktif. Bagaimana membaca akun nonaktif? Apakah
  `/accounting/coa/index` untuk itu?
- Bagaimana menentukan parent akun? Field parent tidak ada di skema.
- Create: `coaNo` "ignored on create" — bagaimana nomor akun ditentukan?
- Detail tidak membawa `currencyID`: apakah update tanpa field ini mengubah currency?

**2.7 [B] Payment Method.**
- Apakah ada endpoint create/update payment method (Core atau OMS)?
- Endpoint list memakai HTTP Basic `backend_username:backend_password`. Mohon kredensial untuk
  staging dan production, serta host staging untuk family `pos`.

**2.8 [B] Supplier — create/update.**
- `greetingID` (PIC) wajib, tetapi tidak ada endpoint daftar greeting. Dari mana nilainya?
- `PATCH /supplier/{id}` tanpa `supplierBank[]`: apakah data rekening bank lama dihapus?

---

## 3. Inventory

**3.1 [B] Goods Delivery — create dari referensi.**
`GET /inventory/goods-delivery/create/{refNum}` lalu `POST /{refNum}`:
- Dokumen apa saja yang valid sebagai `refNum` (Goods Transfer Request? Sales Order?)?
- Cara mengisi batch/tanggal kedaluwarsa, aset, dan multi-address per baris?

**3.2 [B] Goods Receipt — create dari referensi.**
`initialize` lalu `POST /{refNum}`: `refNum` yang valid (Purchase Order? Goods Delivery?
Product Sales Return?) dan cara mengisi kedaluwarsa serta aset. Apakah ada endpoint
attachment untuk Goods Receipt?

**3.3 [C] Nomor dokumen di respons create.**
Dokumentasi create Goods Transfer Request dan Simple Transfer menulis respons sebagai "map
string". Key apa yang berisi nomor dokumen baru?

---

## 4. Finance & Accounting

**4.1 [C] Disbursement create.**
Dokumentasi menyebut "returns the new disbursementNum" tetapi contoh respons `{}`. Apakah
`result.disbursementNum` selalu dikirim?

**4.2 [B] Memorial Journal — `GET /access`.**
Apa nama parameter query untuk nomor jurnal? "Current user" di sini adalah user API gateway
(satu kredensial bersama). Apakah hasilnya tetap bermakna?

**4.3 [B] Memorial Journal — `form-upload` / `upload`.**
Dokumentasi menulis body sebagai JSON satu baris Excel, bukan file multipart. Mohon nama
field, apakah file dikirim sebagai multipart, dan arti `typeID` (debit/kredit?).

**4.4 [C] Memorial Journal — baris & gambar.**
- Apakah satu baris boleh berisi debit **dan** kredit sekaligus?
- `asset-images`: upload menambah atau mengganti gambar lama? Batas ukuran dan tipe file?

**4.5 [B] Cost center & project.**
Apakah ada endpoint list cost center dan project (untuk picker di Disbursement dan Memorial
Journal)?

---

## 5. POS (OMS)

**5.1 [B] Menu Extra.**
Dokumentasi `create-menu` / `update-menu` memuat `menuExtras[]` secara nested. Mohon
konfirmasi bahwa tidak ada endpoint terpisah untuk menu extra, dan bahwa extra yang tidak
dikirim saat `update-menu` akan dihapus.

**5.1a [B] Create / update menu (`create-menu`, `update-menu`).**
- `flagSeparatePrintPackage`, `flagSeparateTaxCalculation`, dan `checkerList` wajib di body,
  tetapi tidak ada di respons `get-menu`. Bagaimana membaca nilai saat ini sebelum update,
  agar update tidak menimpanya?
- `get-menu` mengembalikan `categoryDetail` (nama), sedangkan body meminta
  `menuCategoryDetailID`. Bagaimana mendapatkan ID-nya dari data menu?
- `salesAccount`, `cogsAccount`, `discountAccount` didokumentasikan sebagai "Account
  Description" (String(20)). Nilai apa yang valid, dan dari endpoint mana daftarnya?
- `updateCheckerAndStation: false` — apakah checker/station yang ada tetap dipertahankan?
- `menuPackages[]`, `menuIcons[]`, `menuTags[]`, `relatedMenus[]` yang tidak dikirim saat
  update: dihapus atau dipertahankan?

**5.2 [C] Menu Category — update.**
- Field yang tidak ada di respons list (alias ESB Order, image URL,
  `themeOptionCategoryOnPos`) dan karena itu tidak kami kirim saat update: apakah nilai lama
  dipertahankan?
- Category detail yang tidak dikirim saat update: dihapus?
- Apakah `get-menu-category` bisa difilter per ID?

**5.3 [B] Promotion.**
- Apakah ada endpoint update dan nonaktifkan promosi?
- Apa arti "ESO" (mis. "Discount (%) ESO")?
- Tipe data: boolean dikirim `"Yes"/"No"` atau `true/false`? `applyTo` label atau integer?
  `visitPurposeID` integer atau string?
- `GET /extv1/promotion` mengembalikan array tanpa envelope, dan 401 dengan `code: 0`. Apakah
  ini perilaku tetap?
- Apakah ada endpoint list untuk payment method ESB Order, visit purpose, employee group, dan
  BIN (saat ini diketik manual)?

---

Terima kasih. Kontak teknis: Budi Priyo Utomo.
