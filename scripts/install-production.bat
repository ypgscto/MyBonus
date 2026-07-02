@echo off
REM Instalasi pertama BONUSKU - folder kosong, database belum ada
REM Buka dari Terminal Laragon, lalu jalankan:
REM   scripts\install-production.bat
REM
REM Dengan password MySQL (jika root pakai password):
REM   scripts\install-production.bat -DbPassword "123456"
REM
REM Dengan URL kustom:
REM   scripts\install-production.bat -AppUrl "http://localhost/bonusku/public"

setlocal
set "SCRIPT_DIR=%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%SCRIPT_DIR%install-production.ps1" %*
set "EXIT_CODE=%ERRORLEVEL%"
endlocal & exit /b %EXIT_CODE%
