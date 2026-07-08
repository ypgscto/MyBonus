function Resolve-GitHubCredentials {
    param(
        [string]$GitUsername = "ypgscto",
        [string]$GitToken = "",
        [string]$TokenFile = ""
    )

    if ($GitToken -ne "") {
        return @{
            Username = $GitUsername
            Token = $GitToken
        }
    }

    if ($env:GITHUB_TOKEN) {
        return @{
            Username = if ($env:GITHUB_USERNAME) { $env:GITHUB_USERNAME } else { $GitUsername }
            Token = $env:GITHUB_TOKEN
        }
    }

    if ($TokenFile -eq "") {
        $TokenFile = Join-Path $PSScriptRoot ".github-token"
    }

    if (Test-Path $TokenFile) {
        $line = (Get-Content $TokenFile -TotalCount 1 -ErrorAction SilentlyContinue).Trim()
        if ($line -match "^([^:]+):(.+)$") {
            return @{
                Username = $Matches[1].Trim()
                Token = $Matches[2].Trim()
            }
        }

        if ($line -ne "") {
            return @{
                Username = $GitUsername
                Token = $line
            }
        }
    }

    return $null
}

function Invoke-PrivateGitPull {
    param(
        [Parameter(Mandatory = $true)]
        [string]$AppDir,
        [string]$Branch = "main",
        [string]$RepoOwner = "ypgscto",
        [string]$RepoName = "bonusku",
        [string]$GitUsername = "ypgscto",
        [string]$GitToken = "",
        [string]$TokenFile = ""
    )

    if (-not (Test-Path $AppDir)) {
        throw "Folder tidak ditemukan: $AppDir"
    }

    if (-not (Test-Path (Join-Path $AppDir ".git"))) {
        throw "Bukan folder Git: $AppDir"
    }

    Push-Location $AppDir
    try {
        $originalRemote = (git remote get-url origin).Trim()
        $credentials = Resolve-GitHubCredentials -GitUsername $GitUsername -GitToken $GitToken -TokenFile $TokenFile
        $usedTemporaryRemote = $false

        if ($credentials) {
            $encodedUser = [uri]::EscapeDataString($credentials.Username)
            $encodedToken = [uri]::EscapeDataString($credentials.Token)
            $authRemote = "https://${encodedUser}:${encodedToken}@github.com/${RepoOwner}/${RepoName}.git"

            git remote set-url origin $authRemote | Out-Null
            $usedTemporaryRemote = $true
        }

        $previousPreference = $ErrorActionPreference
        $ErrorActionPreference = "Continue"
        try {
            git fetch origin $Branch 2>&1 | Out-Host
            $fetchExitCode = $LASTEXITCODE

            if ($fetchExitCode -ne 0 -and -not $credentials) {
                throw @"
Git fetch gagal. Repo privat memerlukan token GitHub.

Jalankan salah satu:
  scripts\pull-production.bat -GitUsername ypgscto -GitToken "ghp_xxxx"
  scripts\pull-production.bat -TokenFile C:\path\github-token.txt

Atau simpan token di scripts\.github-token (satu baris: ghp_xxxx)
"@
            }

            if ($fetchExitCode -ne 0) {
                throw "Git fetch gagal. Periksa username/token dan akses repo ${RepoOwner}/${RepoName}."
            }

            git pull origin $Branch 2>&1 | Out-Host
            if ($LASTEXITCODE -ne 0) {
                throw "Git pull gagal."
            }
        } finally {
            $ErrorActionPreference = $previousPreference

            if ($usedTemporaryRemote) {
                git remote set-url origin $originalRemote | Out-Null
            }
        }
    } finally {
        Pop-Location
    }
}
