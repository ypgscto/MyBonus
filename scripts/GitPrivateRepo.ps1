function Resolve-GitHubToken {
    param(
        [string]$GitToken = "",
        [string]$TokenFile = ""
    )

    if ($GitToken -ne "") {
        return $GitToken.Trim()
    }

    if ($env:GITHUB_TOKEN) {
        return $env:GITHUB_TOKEN.Trim()
    }

    if ($TokenFile -eq "") {
        $TokenFile = Join-Path $PSScriptRoot ".github-token"
    }

    if (Test-Path $TokenFile) {
        $line = (Get-Content $TokenFile -TotalCount 1 -ErrorAction SilentlyContinue).Trim()

        if ($line -match "^[^:]+:(.+)$") {
            return $Matches[1].Trim()
        }

        if ($line -ne "") {
            return $line
        }
    }

    return $null
}

function Get-CleanGitHubRemoteUrl {
    param(
        [string]$RepoOwner = "ypgscto",
        [string]$RepoName = "bonusku"
    )

    return "https://github.com/${RepoOwner}/${RepoName}.git"
}

function Reset-GitRemoteToClean {
    param(
        [string]$RepoOwner = "ypgscto",
        [string]$RepoName = "bonusku"
    )

    $cleanUrl = Get-CleanGitHubRemoteUrl -RepoOwner $RepoOwner -RepoName $RepoName
    git remote set-url origin $cleanUrl | Out-Null
    return $cleanUrl
}

function Test-GitHubTokenAccess {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Token,
        [string]$RepoOwner = "ypgscto",
        [string]$RepoName = "bonusku"
    )

    try {
        $headers = @{
            Authorization = "Bearer $Token"
            Accept = "application/vnd.github+json"
            "X-GitHub-Api-Version" = "2022-11-28"
        }

        $response = Invoke-RestMethod `
            -Uri "https://api.github.com/repos/$RepoOwner/$RepoName" `
            -Headers $headers `
            -ErrorAction Stop

        return @{
            Ok = $true
            Message = "Token valid. Akses repo: $($response.full_name)"
        }
    } catch {
        $statusCode = $null
        if ($_.Exception.Response) {
            $statusCode = [int]$_.Exception.Response.StatusCode
        }

        if ($statusCode -eq 401) {
            return @{
                Ok = $false
                Message = "Token tidak valid atau sudah expired."
            }
        }

        if ($statusCode -eq 404) {
            return @{
                Ok = $false
                Message = @"
Token tidak punya akses ke ${RepoOwner}/${RepoName}.

Periksa PAT fine-grained:
  - Repository access: pilih ypgscto/bonusku
  - Permissions: Contents = Read, Metadata = Read
  - Jika org pakai SSO: buka token di GitHub, klik Authorize untuk org ypgscto
"@
            }
        }

        return @{
            Ok = $false
            Message = "Cek token gagal: $($_.Exception.Message)"
        }
    }
}

function Invoke-GitCommand {
    param(
        [Parameter(Mandatory = $true)]
        [string[]]$Arguments
    )

    $previousPreference = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    $env:GIT_TERMINAL_PROMPT = "0"
    $env:GCM_INTERACTIVE = "Never"

    try {
        $gitArgs = @(
            "-c", "credential.helper="
            "-c", "credential.interactive=false"
        ) + $Arguments

        $output = & git @gitArgs 2>&1
        $text = ($output | Out-String).Trim()
        $output | ForEach-Object { Write-Host $_ }

        return @{
            ExitCode = $LASTEXITCODE
            Output = $text
        }
    } finally {
        $ErrorActionPreference = $previousPreference
    }
}

function Invoke-PrivateGitPull {
    param(
        [Parameter(Mandatory = $true)]
        [string]$AppDir,
        [string]$Branch = "main",
        [string]$RepoOwner = "ypgscto",
        [string]$RepoName = "bonusku",
        [string]$GitToken = "",
        [string]$TokenFile = ""
    )

    if (-not (Test-Path $AppDir)) {
        throw "Folder tidak ditemukan: $AppDir"
    }

    if (-not (Test-Path (Join-Path $AppDir ".git"))) {
        throw "Bukan folder Git: $AppDir"
    }

    $token = Resolve-GitHubToken -GitToken $GitToken -TokenFile $TokenFile

    Push-Location $AppDir
    try {
        $cleanUrl = Reset-GitRemoteToClean -RepoOwner $RepoOwner -RepoName $RepoName

        if (-not $token) {
            $result = Invoke-GitCommand -Arguments @("fetch", "origin", $Branch)
            if ($result.ExitCode -ne 0) {
                throw @"
Git fetch gagal. Repo privat memerlukan token GitHub.

Jalankan:
  scripts\pull-production.bat -GitToken "github_pat_xxxx"
  scripts\test-github-token.bat -GitToken "github_pat_xxxx"
"@
            }

            $pull = Invoke-GitCommand -Arguments @("pull", "origin", $Branch)
            if ($pull.ExitCode -ne 0) {
                throw "Git pull gagal."
            }

            return
        }

        Write-Host "==> Mengecek token ke GitHub API..." -ForegroundColor Cyan
        $tokenCheck = Test-GitHubTokenAccess -Token $token -RepoOwner $RepoOwner -RepoName $RepoName
        if (-not $tokenCheck.Ok) {
            throw $tokenCheck.Message
        }
        Write-Host $tokenCheck.Message -ForegroundColor Green

        $authUrl = "https://x-access-token:${token}@github.com/${RepoOwner}/${RepoName}.git"
        git remote set-url origin $authUrl | Out-Null

        try {
            Write-Host "==> Git fetch origin $Branch" -ForegroundColor Cyan
            $fetch = Invoke-GitCommand -Arguments @("fetch", "origin", $Branch)
            if ($fetch.ExitCode -ne 0) {
                if ($fetch.Output -match "Invalid username or token|Authentication failed") {
                    throw @"
Autentikasi Git gagal meskipun token valid di API.

Hapus kredensial lama Windows:
  1. Control Panel - Credential Manager - Windows Credentials
  2. Hapus semua entri git:https://github.com
  3. Jalankan ulang script ini

Atau coba file darurat:
  scripts\git-pull-token.cmd "github_pat_xxxx"
"@
                }

                throw "Git fetch gagal.`n$($fetch.Output)"
            }

            Write-Host "==> Git pull origin $Branch" -ForegroundColor Cyan
            $pull = Invoke-GitCommand -Arguments @("pull", "origin", $Branch)
            if ($pull.ExitCode -ne 0) {
                throw "Git pull gagal.`n$($pull.Output)"
            }
        } finally {
            git remote set-url origin $cleanUrl | Out-Null
        }
    } finally {
        Pop-Location
    }
}
