---
name: esb-catalog-sync
description: >
  Gunakan skill ini ketika dokumentasi vendor ESB berubah dan katalog endpoint di
  `docs/esb/esb-core-endpoints.md` atau `docs/esb/esb-oms-endpoints.md` perlu
  disinkronkan ulang, atau ketika ditanya "endpoint apa saja yang tersedia di ESB
  Core/OMS", "apakah endpoint X sudah terdaftar", atau "update katalog ESB". Skill ini
  memastikan katalog selalu di-generate ulang lewat script, bukan diedit manual.
---

# ESB Catalog Sync

## Aturan paling penting

**Jangan pernah mengedit `docs/esb/esb-core-endpoints.md` atau
`docs/esb/esb-oms-endpoints.md` secara manual.** Kedua file ini adalah hasil generate
otomatis dari `docs/esb/generate-catalog.py` berdasarkan apiDoc vendor. Edit manual akan
hilang begitu script dijalankan ulang, dan berisiko membuat katalog tidak sinkron dengan
sumber aslinya.

## Kapan katalog perlu di-sync ulang

- Vendor merilis versi baru dokumentasi apiDoc ESB Core/OMS.
- Ada laporan endpoint yang hilang/berbeda dari yang didokumentasikan di
  `developers.esb.co.id`.
- Sebelum mulai mengintegrasikan family endpoint yang belum pernah disentuh, untuk
  memastikan katalog masih akurat.

## Langkah

1. Konfirmasi sumber vendor terbaru (`https://developers.esb.co.id/esb-core/` dan
   `https://developers.esb.co.id/esb-oms/`, sesuai catatan di `README.md`).
2. Jalankan `docs/esb/generate-catalog.py` untuk regenerate kedua file katalog.
3. Diff hasilnya terhadap versi sebelumnya (`git diff docs/esb/`) — jangan asumsikan
   tidak ada perubahan.
4. Kalau ada endpoint baru/berubah yang relevan dengan integrasi yang sedang berjalan,
   update juga `docs/05-api-integration.md` (contoh: error code baru, field response
   baru) dan tandai di `docs/project-state.md`.
5. Kalau perubahan katalog berdampak ke keputusan arsitektur (mis. family/base URL baru),
   pertimbangkan ADR baru — lihat skill `adr-writer`.

## Yang harus dihindari

- Menambah entri endpoint "manual" ke dalam katalog karena "sepertinya ada" tapi belum
  dikonfirmasi dari sumber vendor resmi — ini melanggar aturan "jangan mengarang field
  yang tidak terdokumentasi" di `AGENT.md`.
- Menjalankan generator tanpa memeriksa diff — perubahan besar tak terduga di katalog
  (401 endpoint saat ini) bisa jadi sinyal bug di script, bukan perubahan vendor yang sah.