@echo off
REM Pull darurat tanpa PowerShell - pakai x-access-token
REM Usage:
REM   scripts\git-pull-token.cmd github_pat_xxxx

setlocal
if "%~1"=="" (
    echo.
    echo Pakai:
    echo   scripts\git-pull-token.cmd github_pat_xxxx
    echo.
    exit /b 1
)

set "GH_TOKEN=%~1"
set "APP_DIR=C:\webserver\www\bonusku"
set "CLEAN_URL=https://github.com/ypgscto/bonusku.git"
set "AUTH_URL=https://x-access-token:%GH_TOKEN%@github.com/ypgscto/bonusku.git"

if not exist "%APP_DIR%\artisan" (
    echo Error: Project tidak ditemukan di %APP_DIR%
    exit /b 1
)

cd /d "%APP_DIR%"
set GIT_TERMINAL_PROMPT=0
set GCM_INTERACTIVE=Never

echo ==> Bersihkan salinan manual script yang menghalangi pull
for %%F in (GitPrivateRepo.ps1 pull-production.ps1 test-github-token.ps1 git-pull-token.cmd pull-production.bat test-github-token.bat) do (
    if exist "scripts\%%F" del /f /q "scripts\%%F" 2>nul
)

echo ==> Set remote sementara dengan token
git remote set-url origin "%AUTH_URL%"
if errorlevel 1 exit /b 1

echo ==> Git fetch origin main
git -c credential.helper= -c credential.interactive=false fetch origin main
if errorlevel 1 goto FAIL

echo ==> Git pull origin main
git -c credential.helper= -c credential.interactive=false pull origin main
if errorlevel 1 goto FAIL

echo.
echo Pull selesai.
git remote set-url origin "%CLEAN_URL%"
endlocal
exit /b 0

:FAIL
echo.
echo Pull gagal. Kemungkinan:
echo   - Token tidak punya akses repo ypgscto/bonusku
echo   - Hapus kredensial lama di Credential Manager Windows
echo   - Buat PAT baru: Contents Read + Metadata Read
git remote set-url origin "%CLEAN_URL%"
endlocal
exit /b 1
