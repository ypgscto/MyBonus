# Pull cepat dari GitHub repo privat (Windows + Laragon)
#
# Usage:
#   cd C:\webserver\www\bonusku
#   scripts\pull-production.bat -GitUsername ypgscto -GitToken "ghp_xxxx"
#
# Atau simpan token di scripts\.github-token (jangan di-commit):
#   scripts\pull-production.bat
#
# Parameter:
#   -AppDir       Path project (default: C:\webserver\www\bonusku)
#   -Branch       Branch Git (default: main)
#   -GitUsername  Username GitHub (default: ypgscto)
#   -GitToken     Personal Access Token (scope: repo)
#   -TokenFile    Path file berisi token atau username:token

param(
    [string]$AppDir = "C:\webserver\www\bonusku",
    [string]$Branch = "main",
    [string]$GitUsername = "ypgscto",
    [string]$GitToken = "",
    [string]$TokenFile = ""
)

$ErrorActionPreference = "Stop"

. "$PSScriptRoot\GitPrivateRepo.ps1"

if (-not (Test-Path (Join-Path $AppDir "artisan"))) {
    throw "Project tidak ditemukan di $AppDir. Clone dulu dengan token GitHub."
}

Write-Host "========================================" -ForegroundColor Yellow
Write-Host " BONUSKU - Git Pull (repo privat)" -ForegroundColor Yellow
Write-Host " Path   : $AppDir" -ForegroundColor Yellow
Write-Host " Branch : $Branch" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Yellow
Write-Host ""
Write-Host "==> Git pull origin $Branch" -ForegroundColor Cyan

Invoke-PrivateGitPull `
    -AppDir $AppDir `
    -Branch $Branch `
    -GitUsername $GitUsername `
    -GitToken $GitToken `
    -TokenFile $TokenFile

Write-Host ""
Write-Host "Pull selesai." -ForegroundColor Green
Write-Host "Deploy lengkap (migrate + build):" -ForegroundColor Cyan
Write-Host "  scripts\deploy-production.bat -GitToken `"ghp_xxxx`"" -ForegroundColor White
