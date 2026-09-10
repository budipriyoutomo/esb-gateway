---
name: project-state-handoff
description: >
  Gunakan skill ini di AKHIR setiap sesi kerja non-trivial pada project ERP Gateway ini,
  atau ketika diminta "update status project", "catat progress", "siapkan handoff untuk
  agent berikutnya". Skill ini memastikan `docs/project-state.md` selalu mencerminkan
  state repository yang sebenarnya, karena project ini sengaja berpindah-pindah antar AI
  coding agent (Claude Code, Codex, Cursor, Gemini) tanpa membawa riwayat percakapan.
---

# Project State Handoff

Tujuan skill ini: agent berikutnya — siapa pun itu, dan tanpa akses ke percakapan ini —
harus bisa melanjutkan pekerjaan hanya dari state repository (`docs/project-state.md` +
git log + kode).

## Kapan dijalankan

- Di akhir sesi kerja non-trivial (fitur, bugfix signifikan, perubahan arsitektur).
- Sebelum berhenti kerja karena blocked oleh sesuatu di luar kendali (mis. menunggu
  kredensial ESB, menunggu keputusan bisnis).
- Setiap kali berpindah dari satu AI coding agent ke agent lain.

## Isi wajib update di `docs/project-state.md`

1. **Apa yang selesai** — daftar konkret, bukan "sudah dikerjakan sebagian". Sebutkan
   file/module yang berubah.
2. **Apa yang masih tersisa** — termasuk task yang sengaja belum disentuh.
3. **Known issues** — bug yang diketahui tapi belum diperbaiki, termasuk kenapa belum.
4. **Assumptions** — asumsi apa pun yang diambil karena requirement belum jelas, dan
   ditandai `TBD` di mana lokasinya di kode/dokumentasi.
5. **Commands/checks yang sudah dijalankan** — test, linter, build — dan hasilnya
   (pass/fail), termasuk yang **tidak bisa** dijalankan (mis. "Blocked — tidak ada Redis
   server lokal, lihat TODO.md Phase 1").

## Aturan

- Jangan tulis ulang seluruh file — cukup update bagian yang relevan, pertahankan format
  yang sudah ada.
- Kalau ada keputusan arsitektur baru selama sesi ini, itu masuk ke `DECISIONS.md`
  (lihat skill `adr-writer`), bukan ke `project-state.md` — `project-state.md` hanya
  soal *state*, bukan *keputusan durabel*.
- Kalau ada task baru yang muncul dari temuan selama kerja (bukan bug langsung, tapi
  technical debt atau follow-up), tambahkan ke `TODO.md`, jangan biarkan hanya
  tersembunyi di commit message.
- Bedakan jelas antara "blocked karena keterbatasan lingkungan lokal" (misalnya tidak ada
  Redis di mesin dev) vs "blocked karena keputusan bisnis/vendor belum ada" (misalnya
  menunggu kredensial ESB) — keduanya butuh penanganan berbeda oleh agent berikutnya.
- Sebelum menulis, `git diff` dan `git status` harus sudah bersih/diperiksa — jangan
  laporkan state yang tidak sesuai dengan working tree sebenarnya.

## Template entri progress

```markdown
## Update <tanggal> — <ringkasan singkat>

**Selesai**
- <item konkret 1>
- <item konkret 2>

**Tersisa**
- <item>

**Known issues**
- <issue> — <kenapa belum diperbaiki>

**Assumptions / TBD**
- <asumsi> — ditandai TBD di `<file:baris atau bagian>`

**Checks dijalankan**
- `php artisan test` — pass/fail
- `npm run build` — pass/fail
- Blocked: <apa yang tidak bisa dijalankan dan kenapa>
```