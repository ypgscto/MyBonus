@echo off
REM Deploy BONUSKU — Windows / Laragon
REM Jalankan dari folder project atau double-click file ini.
REM
REM Update rutin:
REM   scripts\deploy-production.bat
REM
REM Instalasi pertama (+ seed):
REM   scripts\deploy-production.bat -Seed

setlocal
set "SCRIPT_DIR=%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%SCRIPT_DIR%deploy-production.ps1" %*
set "EXIT_CODE=%ERRORLEVEL%"
endlocal & exit /b %EXIT_CODE%
