# Deploy BONUSKU — Production

Repository: https://github.com/ypgscto/bonusku

## Instalasi pertama di server

```bash
cd /var/www/bonusku   # sesuaikan path project
git clone https://github.com/ypgscto/bonusku.git .
cp .env.example .env
# Edit .env: APP_URL, DB_*, MAIL_*, KIRIMI_*, APP_KEY (php artisan key:generate)

composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
npm ci && npm run build
php artisan storage:link
```

**Akun super admin awal** (dari seeder):

| Email | Password sementara |
|-------|-------------------|
| bashar.ypgs@gmail.com | 12345678 |

Ganti password segera setelah login pertama.

## Update rutin (pull production)

```bash
chmod +x scripts/deploy-production.sh
./scripts/deploy-production.sh
```

Atau manual:

```bash
cd /var/www/bonusku
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Instalasi pertama via script deploy

```bash
RUN_SEED=1 ./scripts/deploy-production.sh
```

## Catatan

- File `.env` tidak ada di Git — konfigurasi production disetel di server.
- `php artisan db:seed` hanya dijalankan sekali saat instalasi awal, bukan setiap deploy.
- Pastikan `storage/` dan `bootstrap/cache/` writable oleh web server.
