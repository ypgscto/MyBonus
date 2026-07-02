# Deploy BONUSKU — Production (Windows + Laragon)

Repository: https://github.com/ypgscto/bonusku

Path web server: `C:\webserver\www\bonusku`

---

## Instalasi pertama

Buka **Terminal Laragon** (agar `php`, `composer`, `git`, `npm` tersedia di PATH).

```powershell
cd C:\webserver\www
git clone https://github.com/ypgscto/bonusku.git bonusku
cd bonusku
copy .env.example .env
```

Edit `.env` production:

- `APP_URL` — URL akses aplikasi (mis. `http://bonusku.domain.ac.id/public`)
- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_*` — database MySQL Laragon
- `MAIL_*`, `KIRIMI_*`

```powershell
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
npm ci
npm run build
php artisan storage:link
```

Atau gunakan script deploy + seed:

```powershell
cd C:\webserver\www\bonusku
powershell -ExecutionPolicy Bypass -File scripts\deploy-production.ps1 -Seed
```

### Akun super admin awal

| Email | Password sementara |
|-------|-------------------|
| bashar.ypgs@gmail.com | 12345678 |

Ganti password segera setelah login pertama.

---

## Update rutin (pull + deploy lengkap)

Dari folder project:

```bat
cd C:\webserver\www\bonusku
scripts\deploy-production.bat
```

Atau PowerShell:

```powershell
cd C:\webserver\www\bonusku
powershell -ExecutionPolicy Bypass -File scripts\deploy-production.ps1
```

Script akan menjalankan:

1. `git pull origin main`
2. `composer install --no-dev`
3. `php artisan migrate --force`
4. `npm ci` + `npm run build`
5. `php artisan storage:link`
6. `php artisan config:cache` / `route:cache` / `view:cache`

---

## Pull cepat (hanya Git)

Jika hanya ingin mengambil kode terbaru tanpa migrate/build:

```bat
scripts\pull-production.bat
```

---

## Virtual host Laragon

Arahkan document root ke folder `public`:

```
C:\webserver\www\bonusku\public
```

Pastikan `storage/` dan `bootstrap/cache/` dapat ditulis oleh web server.

---

## Catatan

- File `.env` tidak ada di Git — konfigurasi hanya di server.
- `php artisan db:seed` **hanya sekali** saat instalasi awal (`-Seed`), jangan setiap deploy.
- Script Linux tersedia di `scripts/deploy-production.sh` jika diperlukan.
