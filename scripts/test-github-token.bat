@echo off
REM Cek token GitHub sebelum pull
REM Usage:
REM   scripts\test-github-token.bat -GitToken "github_pat_xxxx"

setlocal
set "SCRIPT_DIR=%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%SCRIPT_DIR%test-github-token.ps1" %*
set "EXIT_CODE=%ERRORLEVEL%"
endlocal & exit /b %EXIT_CODE%
