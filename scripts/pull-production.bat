@echo off
REM Pull cepat dari GitHub (tanpa migrate/build).
REM Untuk deploy lengkap gunakan: scripts\deploy-production.bat

setlocal
set "APP_DIR=C:\webserver\www\bonusku"

if not exist "%APP_DIR%\artisan" (
    echo Error: Project tidak ditemukan di %APP_DIR%
    echo Clone dulu: git clone https://github.com/ypgscto/bonusku.git %APP_DIR%
    exit /b 1
)

cd /d "%APP_DIR%"
echo ==> Git pull origin main
git pull origin main
if errorlevel 1 exit /b 1

echo.
echo Pull selesai. Jalankan deploy lengkap jika perlu:
echo   scripts\deploy-production.bat
endlocal
