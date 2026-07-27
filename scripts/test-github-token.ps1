# Cek apakah token GitHub valid dan punya akses ke repo bonusku
#
# Usage:
#   scripts\test-github-token.bat -GitToken "github_pat_xxxx"

param(
    [string]$GitToken = "",
    [string]$TokenFile = "",
    [string]$RepoOwner = "ypgscto",
    [string]$RepoName = "MyBonus"
)

$ErrorActionPreference = "Stop"

. "$PSScriptRoot\GitPrivateRepo.ps1"

$token = Resolve-GitHubToken -GitToken $GitToken -TokenFile $TokenFile
if (-not $token) {
    throw "Token wajib diisi: scripts\test-github-token.bat -GitToken `"github_pat_xxxx`""
}

Write-Host "Mengecek token..." -ForegroundColor Cyan
$result = Test-GitHubTokenAccess -Token $token -RepoOwner $RepoOwner -RepoName $RepoName

if ($result.Ok) {
    Write-Host $result.Message -ForegroundColor Green
    exit 0
}

Write-Host $result.Message -ForegroundColor Red
exit 1
