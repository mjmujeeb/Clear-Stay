@echo off
title ClearStay Hotel Management System
echo ===================================================
echo   ClearStay - Hotel Stay & Operations Management
echo   Starting local server at http://127.0.0.1:8000
echo ===================================================
echo.
set "PATH=D:\php;%PATH%"
cd /d "%~dp0"
echo Login Credentials:
echo   - Super Admin:   admin@clearstay.jp / password
echo   - Hotel Manager: manager.tokyo@clearstay.jp / password
echo   - Front Desk:    frontdesk.tokyo@clearstay.jp / password
echo   - Housekeeping:  cleaner.tokyo@clearstay.jp / password
echo.
D:\php\php.exe artisan serve --host=127.0.0.1 --port=8000
pause
