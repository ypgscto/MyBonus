# Deploy BONUSKU ke server production (Windows + Laragon)
#
# Usage (PowerShell):
#   cd C:\webserver\www\bonusku
#   powershell -ExecutionPolicy Bypass -File scripts\deploy-production.ps1
#
# Instalasi pertama (seed super admin):
#   powershell -ExecutionPolicy Bypass -File scripts\deploy-production.ps1 -Seed
#
# Parameter:
#   -AppDir  Path root Laravel (default: C:\webserver\www\bonusku)
#   -Branch  Branch Git (default: main)
#   -Seed    Jalankan php artisan db:seed --force (hanya instalasi pertama)

param(
    [string]$AppDir = "C:\webserver\www\bonusku",
    [string]$Branch = "main",
    [switch]$Seed
)

$ErrorActionPreference = "Stop"

function Write-Step([string]$Message) {
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Assert-Command([string]$Name) {
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Perintah '$Name' tidak ditemukan. Buka terminal dari Laragon (Right click > Terminal) agar PHP/Composer/Git ada di PATH."
    }
}

if (-not (Test-Path $AppDir)) {
    throw "Folder tidak ditemukan: $AppDir`nClone repo dulu: git clone https://github.com/ypgscto/bonusku.git $AppDir"
}

Set-Location $AppDir

if (-not (Test-Path "artisan")) {
    throw "File artisan tidak ditemukan di $AppDir. Pastikan path mengarah ke root project Laravel."
}

Write-Host "========================================" -ForegroundColor Yellow
Write-Host " BONUSKU - Deploy Production (Windows)" -ForegroundColor Yellow
Write-Host " Path : $AppDir" -ForegroundColor Yellow
Write-Host " Branch: $Branch" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Yellow

Assert-Command git
Assert-Command php
Assert-Command composer

Write-Step "Git pull origin $Branch"
git fetch origin $Branch
git pull origin $Branch

Write-Step "Composer install (production)"
composer install --no-dev --optimize-autoloader --no-interaction

Write-Step "Migrate database"
php artisan migrate --force

if ($Seed) {
    Write-Step "Seed database (instalasi pertama)"
    php artisan db:seed --force
}

if (Test-Path "package.json") {
    Assert-Command npm
    Write-Step "Build frontend (npm)"
    npm ci
    npm run build
}

Write-Step "Storage link"
try {
    php artisan storage:link 2>$null
} catch {
    # Link mungkin sudah ada
}

Write-Step "Cache optimize"
php artisan config:cache
php artisan route:cache
php artisan view:cache

Write-Host ""
Write-Host "Deploy selesai." -ForegroundColor Green
if ($Seed) {
    Write-Host "Login super admin: bashar.ypgs@gmail.com / 12345678" -ForegroundColor Green
    Write-Host "Ganti password segera setelah login pertama." -ForegroundColor Yellow
}
