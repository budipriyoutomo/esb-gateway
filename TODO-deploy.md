# TODO — Deploy ke VPS dengan Docker Compose

Target: deploy ERP Gateway (Laravel 13 + Inertia React) ke VPS memakai Docker Compose,
memakai **PostgreSQL yang sudah berjalan di container lain** di VPS yang sama.
Keputusan: ADR-033. Acuan: `docs/13-deployment.md`.

Status: `[ ]` belum, `[~]` sedang dikerjakan, `[x]` selesai, `[!]` terblokir (butuh
jawaban/keputusan).

Topologi — **2 container** (`app` + `redis`):

```
Internet --TLS--> Nginx Proxy Manager --(network NPM)--> app :80
                                                          |
     app = supervisord: nginx + php-fpm + queue worker + scheduler
           (entrypoint: cache, readiness check, migrate, lalu supervisord)
                   |                                   |
 (posgresql_default) maharasa-postgres           redis (compose ini)
```

---

## 0. Keputusan (terjawab 2026-11-19)

- [x] **0.1 Postgres:** container `maharasa-postgres`, network `posgresql_default`
      (dipasang `external`, `DB_HOST=maharasa-postgres`).
- [ ] **0.2 Database & user** `erp_gateway` belum dibuat — lihat 5.2.
- [x] **0.3 Redis:** belum ada → service `redis` baru di compose ini (password, AOF,
      tidak di-publish).
- [x] **0.4 Reverse proxy:** Nginx Proxy Manager yang sudah ada, domain
      `esbgate.maharasa.id`. Container `app` join ke network NPM (alias
      `erp-gateway-app`), tidak publish port ke host.
- [ ] **0.4a Nama network NPM** masih perlu dicek → isi `PROXY_NETWORK` di `.env`.
- [x] **0.5 Build:** langsung di VPS (`docker compose up -d --build`).
- [x] **0.6 ESB:** `ESB_ENV=production` (tanpa staging).
- [ ] **0.7 Kredensial ESB production** (static token / login) — siapkan sebelum 6.x.
- [x] **0.8 Jumlah container:** hanya 2 (`app` + `redis`); nginx, php-fpm, worker,
      scheduler digabung di `app` via supervisord, migrate di entrypoint.

## 1. Persiapan kode (di repo)

- [x] **1.1 Trusted proxies** di `bootstrap/app.php` — hanya `X-Forwarded-For` dan
      `X-Forwarded-Proto` (Host tidak dipercaya). Test:
      `tests/Feature/Hardening/TrustedProxiesTest.php`.
- [x] **1.1a Anti-spoof IP** — nginx hanya meneruskan entri paling kanan
      `X-Forwarded-For` ke PHP (`docker/nginx/default.conf`).
- [x] **1.2 `.dockerignore`** — `docs/esb/` sengaja ikut (dipakai seeder
      `esb:import-catalog`).
- [x] **1.3 Healthcheck** — `HEALTHCHECK` di Dockerfile memakai `/up`; `/health` untuk
      verifikasi manual.
- [x] **1.4 SSR Inertia** tetap off → tidak ada proses Node SSR.
- [x] **1.5 `composer ci:check` dijalankan (2026-11-19)** — format/lint/tsc/vp test/
      Pint/PHPStan/PHPUnit lulus (1837 test, 0 gagal). Dua hal di luar fase ini:
      - [ ] readiness check lokal gagal: `esb_connections` lokal mengarah ke ESB
            production (`core`) dengan `APP_ENV=local`.
      - [ ] PHPUnit exit 1 karena warning lama `MasterDataImportTest::kinds` (5 argumen
            ke method 4 parameter, sejak `812ccbf`).

## 2. Dockerfile

- [x] **2.1–2.3** Multi-stage: `base` (PHP 8.3 FPM + ext), `build` (Composer + Node 22;
      Wayfinder butuh PHP saat `vite build`), `app` (runtime + nginx + supervisor).
- [x] **2.4** `docker/php/conf.d/zz-production.ini` (opcache, upload 20M/25M);
      `docker/php/fpm/zz-pool.conf` (FPM hanya di `127.0.0.1:9000`).
- [x] **2.5** supervisord + master nginx/FPM sebagai root; semua proses PHP aplikasi
      (FPM worker, queue worker, scheduler, artisan di entrypoint) sebagai `www-data`.
- [x] **2.6** `docker/entrypoint.sh` — cache config/route/event/view, lalu readiness
      check + `migrate --force` (gagal → container berhenti), lalu supervisord.
      `RUN_MIGRATIONS=false` untuk melewati.
- [x] **2.6a** `docker/supervisord.conf` — program `php-fpm`, `nginx`, `worker`
      (`stopwaitsecs=1860`), `scheduler`.
- [ ] **2.7 Uji build pertama di VPS** (belum diuji — Docker tidak ada di mesin dev).

## 3. docker-compose.yml

- [x] **3.1** `app` — satu-satunya container aplikasi; **jangan di-scale** (scheduler
      hanya boleh satu, migrate berjalan di entrypoint).
- [x] **3.2** `redis` — password, AOF, volume `redis`, tidak di-publish.
- [x] **3.3** Networks: `internal`, `postgres` (external), `proxy` (external).
- [x] **3.4** Volume `storage`.
- [x] **3.5** `stop_grace_period: 32m` — redeploy menunggu job import selesai
      (**situs down selama itu**; deploy saat tidak ada import berjalan).
- [x] **3.6** `restart: unless-stopped`, log json-file 10m × 5.

## 4. Environment production

- [x] **4.1** Template `.env.production.example`.
- [x] **4.2** `REDIS_QUEUE_RETRY_AFTER=1860` — default 90s akan membuat job import
      (timeout 1800s) diambil ulang di tengah jalan.
- [ ] **4.3** Isi `.env` di VPS: `APP_KEY`, `DB_PASSWORD`, `REDIS_PASSWORD`,
      `PROXY_NETWORK`, `ESB_STATIC_TOKEN`/login. Karakter `$` ditulis `$$`.
- [ ] **4.4** Backup `APP_KEY` terpisah dari backup DB (ADR-018).

## 5. Persiapan VPS

- [ ] **5.1** Docker Engine + Compose plugin ≥ v2.20.
- [ ] **5.2** Buat DB + user di `maharasa-postgres`:
      ```
      docker exec -it maharasa-postgres psql -U postgres -c "CREATE USER erp_gateway WITH PASSWORD '...';"
      docker exec -it maharasa-postgres psql -U postgres -c "CREATE DATABASE erp_gateway OWNER erp_gateway;"
      ```
- [ ] **5.3** DNS `esbgate.maharasa.id` → IP VPS.
- [ ] **5.4** NPM: Proxy Host `esbgate.maharasa.id` → `http://erp-gateway-app:80`,
      SSL Let's Encrypt + Force SSL + HTTP/2. Bila upload > 1MB gagal, tambahkan
      `client_max_body_size 25m;` di Advanced.
- [ ] **5.5** Firewall: hanya 22/80/443. (Compose ini tidak publish port apa pun.)

## 6. Deploy pertama

- [ ] **6.1** `git clone` ke VPS, `cp .env.production.example .env`, isi nilai.
- [ ] **6.2** `docker compose build`.
- [ ] **6.3** Generate key: `docker compose run --rm --no-deps app php artisan
      key:generate --show` → tempel ke `APP_KEY`.
- [ ] **6.4** `docker compose up -d` (readiness check + migrate otomatis; bila container
      `app` restart terus, lihat `docker compose logs app`).
- [ ] **6.5** Seeder: `docker compose exec -u www-data app php artisan db:seed --force`
      (roles/permission + katalog ESB; tidak membuat user).
- [ ] **6.6** Buat user pertama lalu
      `docker compose exec -u www-data app php artisan user:assign-role <email> "Super Admin"`.
- [ ] **6.7** `curl https://esbgate.maharasa.id/health` → 200, semua `ok`.
- [ ] **6.8** Login, probe koneksi ESB dari layar ESB Connections.
- [ ] **6.9** `docker compose exec app supervisorctl status` → `php-fpm`, `nginx`,
      `worker`, `scheduler` semua `RUNNING`; `docker compose exec -u www-data app php
      artisan schedule:list`.

## 7. Operasional

- [ ] **7.1 Deploy ulang:** pastikan tidak ada import berjalan, lalu
      `git pull && docker compose up -d --build`.
- [ ] **7.2 Backup** — `pg_dump` terjadwal DB `erp_gateway` + backup `APP_KEY`.
- [ ] **7.3 Rollback** — set `IMAGE_TAG` per release agar image lama tetap ada.
- [ ] **7.4 Log** — `docker compose logs -f app` (semua proses dalam satu stream).
- [x] **7.5** `docs/13-deployment.md` (bagian "Docker Compose deployment"), ADR-033 di
      `DECISIONS.md`, `docs/project-state.md` (update 2026-11-19).
- [ ] **7.6** Selalu jalankan artisan manual dengan `-u www-data` agar file di
      `storage/` tidak jadi milik root.
