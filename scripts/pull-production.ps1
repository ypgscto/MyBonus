# Pull cepat dari GitHub repo privat (Windows + Laragon)
#
# Usage:
#   scripts\pull-production.bat -GitToken "github_pat_xxxx"
#
# Atau simpan token di scripts\.github-token (jangan di-commit):
#   scripts\pull-production.bat
#
# Parameter:
#   -AppDir     Path project (default: C:\webserver\www\bonusku)
#   -Branch     Branch Git (default: main)
#   -GitToken   Personal Access Token (classic ghp_ atau fine-grained github_pat_)
#   -TokenFile  Path file berisi token

param(
    [string]$AppDir = "C:\webserver\www\bonusku",
    [string]$Branch = "main",
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
    -GitToken $GitToken `
    -TokenFile $TokenFile

Write-Host ""
Write-Host "Pull selesai." -ForegroundColor Green
Write-Host "Deploy lengkap (migrate + build):" -ForegroundColor Cyan
Write-Host "  scripts\deploy-production.bat -GitToken `"github_pat_xxxx`"" -ForegroundColor White
