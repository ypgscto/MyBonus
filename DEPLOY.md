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

### Port MySQL

Server production ini memakai MySQL di port **3307** (default script sudah diset ke `3307`). Jika port berbeda:

```bat
scripts\install-production.bat -DbPort "3307"
```

Pastikan `DB_PORT` di `.env` sama dengan port MySQL Laragon (Menu Laragon → MySQL → my.ini atau pengaturan port).

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

## Login GitHub (repo privat)

Repo `ypgscto/bonusku` bersifat **private**, server harus login dulu sebelum `git clone` / `git pull`.

Pilih **satu** metode di bawah.

### Metode 1 — Personal Access Token (PAT) — paling mudah di Windows

**A. Buat token di GitHub (lakukan di browser, sekali saja)**

1. Login GitHub → [https://github.com/settings/tokens](https://github.com/settings/tokens)
2. **Generate new token** → **Generate new token (classic)**
3. Note: `bonusku-server`
4. Centang scope: **`repo`** (full control of private repositories)
5. Generate → **salin token** (hanya muncul sekali, simpan aman)

**B. Clone / pull di server (Terminal Laragon)**

```bat
cd C:\webserver\www
git clone https://github.com/ypgscto/bonusku.git bonusku
```

Saat diminta:

| Field | Isi |
|-------|-----|
| Username | Username GitHub Anda (pemilik akses ke org `ypgscto`) |
| Password | **Token PAT** (bukan password login GitHub) |

Windows **Git Credential Manager** akan menyimpan kredensial — `git pull` berikutnya tidak perlu login lagi.

**C. Cek pull berhasil**

```bat
cd C:\webserver\www\bonusku
git pull origin main
```

---

### Metode 2 — SSH key (disarankan untuk server jangka panjang)

**A. Buat SSH key di server**

```bat
ssh-keygen -t ed25519 -C "bonusku-server" -f C:\Users\%USERNAME%\.ssh\bonusku_deploy
```

Tekan Enter untuk passphrase kosong (atau isi jika ingin lebih aman).

**B. Salin public key**

```bat
type C:\Users\%USERNAME%\.ssh\bonusku_deploy.pub
```

**C. Tambahkan ke GitHub**

- **Opsi deploy key (hanya repo bonusku):**  
  Repo → **Settings** → **Deploy keys** → **Add deploy key** → paste public key → centang **Allow read access**
- **Opsi akun:**  
  GitHub → **Settings** → **SSH and GPG keys** → **New SSH key**

**D. Konfigurasi SSH**

Buat/edit file `C:\Users\<user>\.ssh\config`:

```
Host github.com
  HostName github.com
  User git
  IdentityFile C:/Users/<user>/.ssh/bonusku_deploy
  IdentitiesOnly yes
```

Ganti `<user>` dengan username Windows server.

**E. Clone dengan SSH**

```bat
cd C:\webserver\www
git clone git@github.com:ypgscto/bonusku.git bonusku
```

Tes koneksi:

```bat
ssh -T git@github.com
```

---

### Metode 3 — Simpan token di URL remote (hati-hati)

Hanya jika metode lain gagal. **Jangan commit token ke Git.**

```bat
cd C:\webserver\www\bonusku
git remote set-url origin https://<USERNAME>:<TOKEN>@github.com/ypgscto/bonusku.git
git pull origin main
```

Ganti `<USERNAME>` dan `<TOKEN>`. Token bisa dicabut kapan saja dari GitHub Settings.

---

### Troubleshooting GitHub

| Masalah | Solusi |
|---------|--------|
| `Repository not found` | Akun/token tidak punya akses ke repo privat `ypgscto/bonusku` |
| `Authentication failed` / `Invalid username or token` | Hapus kredensial lama di **Credential Manager** → `git:https://github.com`. Cek token: `scripts\test-github-token.bat -GitToken "..."`. Pull darurat: `scripts\git-pull-token.cmd "..."` |
| `Permission denied (publickey)` | SSH key belum ditambahkan ke GitHub / path `config` salah |
| Pull minta login terus | Jalankan: `git config --global credential.helper manager` |
| Sudah salah simpan password | **Windows** → Credential Manager → Windows Credentials → hapus entri `git:https://github.com` |

Setelah GitHub berhasil login, lanjutkan instalasi:

```bat
cd C:\webserver\www\bonusku
scripts\install-production.bat
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

## Pull cepat (hanya Git, repo privat)

**Opsi A — token langsung (disarankan pertama kali):**

```bat
cd C:\webserver\www\bonusku
scripts\pull-production.bat -GitToken "github_pat_xxxx"
```

Token bisa **classic** (`ghp_...`) atau **fine-grained** (`github_pat_...`). Username **tidak perlu**.

**Cek token dulu:**

```bat
scripts\test-github-token.bat -GitToken "github_pat_xxxx"
```

**Fine-grained PAT** — pastikan:
- Repository access: `ypgscto/bonusku`
- Permissions: **Contents** = Read, **Metadata** = Read
- Jika organisasi pakai SSO: di halaman token klik **Configure SSO** → **Authorize** untuk `ypgscto`

**Pull darurat (tanpa PowerShell, copy file ini manual jika perlu):**

```bat
scripts\git-pull-token.cmd github_pat_xxxx
```

**Opsi B — simpan token di server (sekali setup):**

```bat
cd C:\webserver\www\bonusku\scripts
copy .github-token.example .github-token
notepad .github-token
```

Isi file `.github-token` dengan PAT GitHub (scope **repo**), satu baris saja. Lalu:

```bat
cd C:\webserver\www\bonusku
scripts\pull-production.bat
```

**Deploy lengkap dengan token:**

```bat
scripts\deploy-production.bat -GitToken "github_pat_xxxx"
```

Token **tidak** disimpan di URL remote Git setelah pull selesai.

---

## Ringkasan script

| Script | Kapan dipakai |
|--------|----------------|
| `install-production.bat` | Instalasi pertama (folder kosong + DB baru) |
| `deploy-production.bat` | Update rutin setelah live |
| `pull-production.bat` | Hanya `git pull` (dukung PAT untuk repo privat) |

---

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| `mysql` tidak dikenali | Buka Terminal dari Laragon, bukan CMD biasa |
| Database gagal dibuat | Start All di Laragon, cek user/password MySQL |
| `git` tidak dikenali | Install Git atau gunakan Terminal Laragon |
| 404 setelah install | Document root harus ke folder `public` |
| Session error | Pastikan migrate sudah jalan (tabel sessions dibuat) |
| `Repository not found` / pull gagal | Lihat bagian **Login GitHub (repo privat)** di atas |
