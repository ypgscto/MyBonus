# Deploy BONUSKU — Production (Windows + Laragon)

Repository: https://github.com/ypgscto/bonusku  
Path web server: `C:\webserver\www\bonusku`

---

## Instalasi pertama (folder kosong, belum ada database)

### Persiapan

1. Install **Laragon** di server Windows
2. Buka Laragon → klik **Start All** (Apache + MySQL harus hijau)
3. Buka **Terminal Laragon** (klik kanan Laragon → Terminal)
4. Pastikan folder `C:\webserver\www` ada (Laragon biasanya sudah membuatnya)

### Cara termudah (disarankan)

```bat
cd C:\webserver\www
git clone https://github.com/ypgscto/bonusku.git bonusku
cd bonusku
scripts\install-production.bat
```

Jika folder `C:\webserver\www\bonusku` sudah dibuat kosong:

```bat
mkdir C:\webserver\www\bonusku
cd C:\webserver\www\bonusku
git clone https://github.com/ypgscto/bonusku.git .
scripts\install-production.bat
```

Atau satu baris PowerShell (clone otomatis jika folder belum ada):

```powershell
powershell -ExecutionPolicy Bypass -File C:\webserver\www\bonusku\scripts\install-production.ps1
```

*(Jalankan baris di atas **setelah** clone pertama kali, atau jika `bonusku` belum ada script akan clone sendiri.)*

Script akan otomatis:

1. Clone `https://github.com/ypgscto/bonusku.git` → `C:\webserver\www\bonusku`
2. Buat file `.env` dari `.env.example`
3. Buat database MySQL `bonusku`
4. `composer install`, `php artisan key:generate`
5. `php artisan migrate --force`
6. `php artisan db:seed --force` (super admin)
7. `npm ci` + `npm run build`
8. `php artisan storage:link` + cache

### Jika MySQL root pakai password

Laragon default sering **tanpa password**. Jika pakai password:

```bat
scripts\install-production.bat -DbPassword "123456"
```

### Jika URL berbeda

```bat
scripts\install-production.bat -AppUrl "http://localhost/bonusku/public"
```

Default script: `http://bonusku.test` (sesuai virtual host Laragon).

### Akun super admin (dari seeder)

| Email | Password sementara |
|-------|-------------------|
| bashar.ypgs@gmail.com | 12345678 |

**Ganti password segera setelah login pertama.**

### Setelah instalasi — Virtual host Laragon

1. Menu Laragon → **www** → pastikan ada site `bonusku`
2. Document root: `C:\webserver\www\bonusku\public`
3. Buka browser: `http://bonusku.test` (atau URL yang Anda set)

### Edit konfigurasi tambahan (opsional)

Edit `C:\webserver\www\bonusku\.env` untuk:

- `MAIL_*` — SMTP email
- `KIRIMI_*` — notifikasi WhatsApp

Lalu jalankan:

```bat
cd C:\webserver\www\bonusku
php artisan config:cache
```

---

## Update rutin (sudah terinstall)

```bat
cd C:\webserver\www\bonusku
scripts\deploy-production.bat
```

Isi script deploy:

1. `git pull origin main`
2. `composer install --no-dev`
3. `php artisan migrate --force`
4. `npm ci` + `npm run build`
5. Cache optimize

**Jangan** jalankan `db:seed` lagi saat update rutin.

---

## Pull cepat (hanya Git)

```bat
cd C:\webserver\www\bonusku
scripts\pull-production.bat
```

---

## Ringkasan script

| Script | Kapan dipakai |
|--------|----------------|
| `install-production.bat` | Instalasi pertama (folder kosong + DB baru) |
| `deploy-production.bat` | Update rutin setelah live |
| `pull-production.bat` | Hanya `git pull` |

---

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| `mysql` tidak dikenali | Buka Terminal dari Laragon, bukan CMD biasa |
| Database gagal dibuat | Start All di Laragon, cek user/password MySQL |
| `git` tidak dikenali | Install Git atau gunakan Terminal Laragon |
| 404 setelah install | Document root harus ke folder `public` |
| Session error | Pastikan migrate sudah jalan (tabel sessions dibuat) |
