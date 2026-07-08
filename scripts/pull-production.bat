@echo off
REM Pull cepat dari GitHub repo privat (dengan PAT).
REM
REM Dengan token langsung:
REM   scripts\pull-production.bat -GitUsername ypgscto -GitToken "ghp_xxxx"
REM
REM Dengan file token (satu baris di scripts\.github-token):
REM   scripts\pull-production.bat
REM
REM Deploy lengkap setelah pull:
REM   scripts\deploy-production.bat -GitToken "ghp_xxxx"

setlocal
set "SCRIPT_DIR=%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%SCRIPT_DIR%pull-production.ps1" %*
set "EXIT_CODE=%ERRORLEVEL%"
endlocal & exit /b %EXIT_CODE%
