@echo off
set "ps_script=%~dp0backup.ps1"
powershell -NoProfile -ExecutionPolicy Bypass -File "%ps_script%"
pause
