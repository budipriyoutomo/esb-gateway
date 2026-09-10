---
name: esb-integration-guardian
description: >
  Gunakan skill ini SELALU ketika ada pekerjaan yang menyentuh integrasi ke ERP ESB pada
  project ERP Gateway ini: membuat/mengubah adapter, service, atau job yang memanggil
  endpoint ESB Core/OMS/Voucher/FnB/POS; menambah endpoint baru ke katalog; menangani
  autentikasi/refresh token ESB; menangani error code ESB; atau mendesain retry/timeout
  untuk panggilan eksternal. Trigger untuk permintaan seperti "buatkan adapter untuk
  endpoint ESB...", "tangani error ESB...", "integrasi ke OMS/Core/Voucher...",
  "refresh token ESB", "kenapa request ke ESB gagal", atau perubahan apapun di sekitar
  `app/Services/Esb*`, `docs/05-api-integration.md`, atau `docs/esb/`.
---

# ESB Integration Guardian

Kamu menjaga agar semua kode yang bicara ke ERP ESB konsisten dengan kontrak yang sudah
didokumentasikan, tidak menebak-nebak field yang belum jelas, dan tidak melanggar
keputusan arsitektur yang sudah diambil (ADR-009, ADR-010).

## Sebelum menulis kode apa pun

1. Baca `docs/05-api-integration.md` — ini sumber kebenaran untuk auth flow, response
   envelope, pagination, error-code catalog, dan aturan retry/idempotency.
2. Cek `docs/esb/esb-core-endpoints.md` atau `docs/esb/esb-oms-endpoints.md` untuk
   endpoint yang relevan. **Jangan edit file katalog ini secara manual** — itu hasil
   generate dari `docs/esb/generate-catalog.py`.
3. Cek `DECISIONS.md` untuk ADR-009 (single shared token) dan ADR-010 (base URL per
   family), karena keduanya membatasi bagaimana adapter boleh ditulis.

## Aturan wajib

**Family & base URL (ADR-010)**
- ESB tidak punya satu `ESB_BASE_URL`. Setiap grup endpoint (Core, OMS, Voucher, FnB
  backend, POS) punya base URL sendiri per environment.
- Adapter/service baru harus mendeklarasikan family-nya secara eksplisit, jangan
  hardcode host di dalam class business logic.

**Token & sesi (ADR-009)**
- ESB hanya mengizinkan satu sesi aktif per kredensial — login baru akan otomatis
  me-logout sesi lama dengan kredensial yang sama.
- Jangan pernah login per-request atau per-job. Token diambil dari cache Redis; kalau
  tidak ada/kedaluwarsa, proses refresh/login harus di-lock supaya hanya satu proses yang
  benar-benar memanggil ESB, proses lain menunggu hasilnya.
- Token dicabut segera ketika ESB mengembalikan kode error `EC03100001`.
- Kredensial ESB harus kredensial dedicated untuk service, bukan akun personal —
  seseorang login manual ke ESB Core akan meng-invalidate sesi gateway.

**Kontrak & data**
- Jangan pernah mengarang field atau makna status code ESB yang tidak terdokumentasi.
  Kalau kontraknya belum jelas, tandai eksplisit sebagai `TBD` dan isolasi asumsi itu di
  satu tempat (jangan disebar ke banyak file).
- Perlakukan semua response ESB sebagai data eksternal yang tidak tepercaya — validasi
  sebelum dipakai lebih jauh.
- Normalisasi error ESB menjadi error level aplikasi tanpa membuang informasi diagnostik
  (correlation/request ID kalau tersedia harus dipertahankan di log).

**Reliability**
- Setiap panggilan ke ESB wajib punya timeout eksplisit dan retry yang terkontrol
  (bounded, dengan backoff) — jangan retry tanpa batas.
- Jangan asumsikan ESB selalu tersedia; desain jalur kegagalan (fallback/queue/notify)
  secara sadar, bukan sebagai afterthought.

**Logging**
- Jangan pernah log secret, password, header Authorization, access/refresh token, atau
  payload sensitif — walaupun untuk debugging.

## Struktur yang diharapkan

Integrasi ESB harus terisolasi di dalam Services/Actions khusus integrasi, bukan
tersebar di controller atau model (lihat `AGENT.md` § Mandatory Architecture Rules). Pola
yang disarankan per family:

```
app/
└── Services/
    └── Esb/
        ├── EsbTokenManager.php      # ambil/refresh token, pakai lock Redis
        ├── Core/
        │   └── {Resource}Service.php
        ├── Oms/
        │   └── {Resource}Service.php
        └── Contracts/
            └── EsbFamily.php        # enum/const family + base URL resolver
```

## Checklist sebelum menganggap task integrasi selesai

- [ ] Base URL family diambil dari config per environment, bukan hardcode.
- [ ] Token diambil lewat `EsbTokenManager` (atau setara), tidak login langsung di
      service bisnis.
- [ ] Timeout dan retry terpasang eksplisit.
- [ ] Tidak ada field/status ESB yang diasumsikan tanpa disebut di
      `docs/05-api-integration.md`; kalau ada asumsi baru, dokumentasikan dan tandai TBD.
- [ ] Tidak ada secret/token di log.
- [ ] Ada test integrasi dengan mock/fake ESB response (`docs/12-testing.md`).
- [ ] Kalau kontrak ESB berubah atau ada field baru yang dipakai, `docs/05-api-integration.md`
      diperbarui di commit yang sama.

## Kalau menemukan ketidaksesuaian

Kalau kode yang ada melanggar salah satu aturan di atas, atau kontrak ESB di dokumentasi
tidak cocok dengan response asli — jangan diam-diam "memperbaiki" dengan asumsi sendiri.
Tandai sebagai temuan, sebutkan di `docs/project-state.md`, dan kalau menyangkut
keputusan arsitektur, ajukan sebagai kandidat ADR baru.