---
name: adr-writer
description: >
  Gunakan skill ini ketika agent atau developer akan mengambil/mencatat keputusan
  arsitektur pada project ERP Gateway ini — menambah dependency baru, mengubah pola
  autentikasi/integrasi, mengganti pendekatan yang sudah ada di `DECISIONS.md`, atau
  membuat trade-off signifikan yang perlu diingat agent lain di masa depan. Trigger untuk
  permintaan seperti "catat ini sebagai ADR", "kenapa kita pilih pendekatan ini",
  "update DECISIONS.md", atau setiap kali ada perubahan yang menyimpang dari arsitektur
  yang sudah didokumentasikan di `AGENT.md`/`DECISIONS.md`.
---

# ADR Writer

Kamu menulis Architecture Decision Record (ADR) baru ke `DECISIONS.md` mengikuti format
yang sudah dipakai konsisten di project ini (lihat ADR-008, ADR-009, ADR-010 sebagai
contoh).

## Kapan sebuah keputusan wajib jadi ADR

Menurut `docs/14-development-workflow.md`: **jangan mengubah arsitektur yang sudah
ditetapkan tanpa mencatat ADR**. Ini termasuk:

- Menambah dependency/package baru yang signifikan.
- Mengubah pola integrasi ESB (auth flow, base URL strategy, token handling).
- Mengubah pola arsitektur backend/frontend yang sudah baku (mis. dari Service/Action ke
  pola lain).
- Trade-off yang mempengaruhi data integrity, security, atau kontrak ESB.
- Keputusan yang kalau tidak dicatat, akan membuat agent berikutnya menebak-nebak "kenapa
  kok begini".

Perubahan kecil/lokal yang tidak menyimpang dari arsitektur yang ada **tidak perlu** ADR.

## Format wajib

Ikuti persis struktur ADR yang sudah ada di `DECISIONS.md`:

```markdown
## ADR-0XX — <Judul singkat, deskriptif>

**Status:** Accepted | Proposed | Superseded by ADR-0YY

**Context**
<Situasi/masalah yang memaksa keputusan ini. Sertakan fakta konkret — kutipan dari
dokumentasi vendor, batasan teknis, dsb — bukan opini.>

**Decision**
<Keputusan yang diambil, ditulis sebagai pernyataan tegas, bukan diskusi.>

**Reason**
- <Alasan 1>
- <Alasan 2>

**Consequences**
- <Konsekuensi positif/negatif yang harus disadari tim>

**Rejected** (opsional, kalau ada alternatif yang dipertimbangkan)
- <Alternatif> — <kenapa ditolak>

**Open** (opsional, kalau ada bagian yang masih TBD)
<Apa yang masih perlu dikonfirmasi, dan status sementara sambil menunggu>
```

## Aturan

- Nomor ADR lanjut dari nomor terakhir di `DECISIONS.md` — jangan pernah menomori ulang
  atau menimpa ADR lama. Kalau sebuah ADR digantikan, tandai status lama
  `Superseded by ADR-0YY`, jangan dihapus.
- Bagian **Context** harus berisi fakta, bukan preferensi ("saya lebih suka X") — kalau
  sumbernya dokumentasi vendor/eksternal, kutip bagian relevannya seperti pola ADR-009.
- Bagian **Decision** ditulis singkat dan dapat diverifikasi dari kode — kalau tidak bisa
  diverifikasi dari kode, kemungkinan itu belum keputusan final.
- Kalau ADR baru bertentangan dengan ADR lama, tulis eksplisit `Supersedes ADR-0YY` dan
  update status ADR lama.
- Setelah ADR ditulis, cek apakah `AGENT.md` atau `docs/*.md` lain perlu disinkronkan
  (mis. ADR tentang pola integrasi baru harus tercermin di
  `docs/09-backend-guidelines.md`).

## Setelah menulis ADR

Selalu tutup dengan mengingatkan (atau langsung mengupdate) `docs/project-state.md` bahwa
ada keputusan arsitektur baru — supaya agent berikutnya tidak melewatkannya.