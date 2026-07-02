# Instalasi pertama BONUSKU - Windows + Laragon
# Untuk folder kosong dan database belum ada.
#
# Usage (buka Terminal Laragon):
#   cd C:\webserver\www\bonusku
#   scripts\install-production.bat
#
# Parameter contoh:
#   scripts\install-production.bat -DbPassword "123456"
#   scripts\install-production.bat -DbPort "3307"

param(
    [string]$AppDir = "C:\webserver\www\bonusku",
    [string]$RepoUrl = "https://github.com/ypgscto/bonusku.git",
    [string]$Branch = "main",
    [string]$AppUrl = "http://bonusku.test",
    [string]$DbHost = "127.0.0.1",
    [string]$DbPort = "3307",
    [string]$DbName = "bonusku",
    [string]$DbUser = "root",
    [string]$DbPassword = ""
)

$ErrorActionPreference = "Stop"

function Write-Step([string]$Message) {
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Assert-Command([string]$Name) {
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Perintah '$Name' tidak ditemukan. Buka Terminal dari Laragon (Menu > Terminal)."
    }
}

function Set-EnvLine([string]$FilePath, [string]$Key, [string]$Value) {
    $lines = Get-Content $FilePath
    $found = $false
    $result = foreach ($line in $lines) {
        if ($line -match "^\s*$([regex]::Escape($Key))\s*=") {
            $found = $true
            "$Key=$Value"
        } else {
            $line
        }
    }
    if (-not $found) {
        $result += "$Key=$Value"
    }
    Set-Content -Path $FilePath -Value $result -Encoding UTF8
}

function Invoke-MySql([string]$Query) {
    $mysqlArgs = @("-h", $DbHost, "-P", $DbPort, "-u", $DbUser, "-e", $Query)
    if ($DbPassword -ne "") {
        $mysqlArgs = @("-h", $DbHost, "-P", $DbPort, "-u", $DbUser, "-p$DbPassword", "-e", $Query)
    }
    & mysql @mysqlArgs
    if ($LASTEXITCODE -ne 0) {
        throw "Perintah MySQL gagal. Pastikan MySQL Laragon sudah Start All dan kredensial DB benar."
    }
}

function Test-MySqlReady {
    $mysqlArgs = @("-h", $DbHost, "-P", $DbPort, "-u", $DbUser, "-e", "SELECT 1")
    if ($DbPassword -ne "") {
        $mysqlArgs = @("-h", $DbHost, "-P", $DbPort, "-u", $DbUser, "-p$DbPassword", "-e", "SELECT 1")
    }
    $output = & mysql @mysqlArgs 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host ""
        Write-Host "MySQL tidak bisa dihubungi di ${DbHost}:${DbPort}" -ForegroundColor Red
        Write-Host "Error: $output" -ForegroundColor Red
        Write-Host ""
        Write-Host "Langkah perbaikan:" -ForegroundColor Yellow
        Write-Host "  1. Buka aplikasi Laragon" -ForegroundColor White
        Write-Host "  2. Klik 'Start All' (Apache/Nginx + MySQL harus hijau)" -ForegroundColor White
        Write-Host "  3. Tunggu beberapa detik, lalu jalankan ulang script ini" -ForegroundColor White
        Write-Host ""
        Write-Host "Cek manual di terminal Laragon:" -ForegroundColor Yellow
        Write-Host "  mysql -u root -e `"SELECT 1`"" -ForegroundColor White
        Write-Host ""
        Write-Host "Jika root pakai password:" -ForegroundColor Yellow
        Write-Host "  scripts\install-production.bat -DbPassword `"password_anda`"" -ForegroundColor White
        throw "MySQL belum berjalan atau kredensial salah."
    }
}

Write-Host "========================================" -ForegroundColor Yellow
Write-Host " BONUSKU - Instalasi Pertama (Windows)" -ForegroundColor Yellow
Write-Host " Folder : $AppDir" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Yellow

Assert-Command git
Assert-Command php
Assert-Command composer
Assert-Command mysql
Assert-Command npm

$parentDir = Split-Path $AppDir -Parent
if (-not (Test-Path $parentDir)) {
    Write-Step "Membuat folder induk $parentDir"
    New-Item -ItemType Directory -Path $parentDir -Force | Out-Null
}

if (-not (Test-Path $AppDir)) {
    Write-Step "Clone repository ke $AppDir"
    git clone --branch $Branch $RepoUrl $AppDir
} elseif (-not (Test-Path (Join-Path $AppDir "artisan"))) {
    $itemCount = @(Get-ChildItem $AppDir -Force -ErrorAction SilentlyContinue).Count
    if ($itemCount -eq 0) {
        Write-Step "Folder kosong, clone repository ke $AppDir"
        Push-Location $AppDir
        git clone --branch $Branch $RepoUrl .
        Pop-Location
    } else {
        throw "Folder $AppDir sudah berisi file lain tetapi bukan project Laravel. Kosongkan folder atau pilih path lain."
    }
} else {
    Write-Step "Project sudah ada di $AppDir, lanjut setup database dan dependensi"
}

Set-Location $AppDir

if (-not (Test-Path ".env")) {
    Write-Step "Membuat file .env dari .env.example"
    Copy-Item ".env.example" ".env"
}

Write-Step "Mengatur .env production"
Set-EnvLine ".env" "APP_ENV" "production"
Set-EnvLine ".env" "APP_DEBUG" "false"
Set-EnvLine ".env" "APP_URL" $AppUrl
Set-EnvLine ".env" "DB_HOST" $DbHost
Set-EnvLine ".env" "DB_PORT" $DbPort
Set-EnvLine ".env" "DB_DATABASE" $DbName
Set-EnvLine ".env" "DB_USERNAME" $DbUser
Set-EnvLine ".env" "DB_PASSWORD" $DbPassword

Write-Step "Membuat database MySQL $DbName (jika belum ada)"
Test-MySqlReady
$sql = "CREATE DATABASE IF NOT EXISTS ``$DbName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
Invoke-MySql $sql

Write-Step "Composer install"
composer install --no-dev --optimize-autoloader --no-interaction

Write-Step "Generate APP_KEY"
php artisan key:generate --force

Write-Step "Migrate database"
php artisan migrate --force

Write-Step "Seed super admin"
php artisan db:seed --force

Write-Step "Build frontend"
npm ci
npm run build

Write-Step "Storage link dan cache"
php artisan storage:link 2>&1 | Out-Null

php artisan config:cache
php artisan route:cache
php artisan view:cache

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host " Instalasi selesai!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "URL aplikasi : $AppUrl" -ForegroundColor White
Write-Host "Folder       : $AppDir" -ForegroundColor White
Write-Host "Document root: $AppDir\public" -ForegroundColor White
Write-Host ""
Write-Host "Login super admin:" -ForegroundColor Yellow
Write-Host "  Email    : bashar.ypgs@gmail.com" -ForegroundColor White
Write-Host "  Password : 12345678" -ForegroundColor White
Write-Host ""
Write-Host "Langkah Laragon:" -ForegroundColor Yellow
Write-Host "  1. Menu Laragon - www - buat virtual host bonusku (jika belum)" -ForegroundColor White
Write-Host "  2. Document root arahkan ke: $AppDir\public" -ForegroundColor White
Write-Host "  3. Start All, buka $AppUrl di browser" -ForegroundColor White
Write-Host "  4. Ganti password super admin segera setelah login" -ForegroundColor White
Write-Host ""
Write-Host "Update berikutnya jalankan: scripts\deploy-production.bat" -ForegroundColor Cyan
