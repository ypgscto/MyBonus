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

function Get-GitAuthConfigArgs {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Token
    )

    return @(
        "-c", "credential.helper="
        "-c", "http.https://github.com/.extraheader=AUTHORIZATION: bearer $Token"
    )
}

function Invoke-GitWithOptionalAuth {
    param(
        [string[]]$GitArguments,
        [string]$Token = ""
    )

    $command = @("git")
    if ($Token -ne "") {
        $command += Get-GitAuthConfigArgs -Token $Token
    }
    $command += $GitArguments

    $previousPreference = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    try {
        & $command[0] $command[1..($command.Length - 1)] 2>&1 | Out-Host
        return $LASTEXITCODE
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
        $authToken = ""
        if ($token) {
            $authToken = $token
        }

        $fetchExitCode = Invoke-GitWithOptionalAuth -GitArguments @("fetch", "origin", $Branch) -Token $authToken

        if ($fetchExitCode -ne 0 -and -not $token) {
            throw @"
Git fetch gagal. Repo privat memerlukan token GitHub.

Jalankan:
  scripts\pull-production.bat -GitToken "github_pat_xxxx"

Atau simpan token di scripts\.github-token (satu baris token saja)
"@
        }

        if ($fetchExitCode -ne 0) {
            throw @"
Git fetch gagal. Periksa token dan akses repo ${RepoOwner}/${RepoName}.

Untuk fine-grained PAT pastikan:
  - Repository access: hanya bonusku (atau All repositories)
  - Permissions: Contents = Read, Metadata = Read

Buat token: https://github.com/settings/tokens?type=beta
"@
        }

        $pullExitCode = Invoke-GitWithOptionalAuth -GitArguments @("pull", "origin", $Branch) -Token $authToken
        if ($pullExitCode -ne 0) {
            throw "Git pull gagal."
        }
    } finally {
        Pop-Location
    }
}
