@echo off
title ClearStay 2.0 Enterprise Platform Launcher
set PATH=D:\php;%PATH%
cd /d "d:\CAD Revision & Manufacturing Change Management System\ClearStay"

echo ==============================================================================
echo       CLEARSTAY 2.0 - ENTERPRISE HOTEL OPERATIONS & MULTI-CHANNEL PLATFORM
echo ==============================================================================
echo.
echo   Branch: feature/clearstay-2-enterprise-platform
echo   URL:    http://127.0.0.1:8000
echo.
echo   DEMO CREDENTIALS (All password: Password123!):
echo   ----------------------------------------------------------------------------
echo   [Owner]            owner@clearstay.jp          (Global Property Access)
echo   [General Manager]  manager.tokyo@clearstay.jp  (Tokyo Central Hotel)
echo   [Revenue Manager]  revenue.tokyo@clearstay.jp  (Rates, Parity & Bulk Edit)
echo   [Integration QA]   integration.qa@clearstay.jp (Partner Assurance & Scenarios)
echo   [Front Desk]       frontdesk.tokyo@clearstay.jp(Reservations & Simple Mode)
echo   [Housekeeping]     cleaner.tokyo@clearstay.jp  (Rooms & Tasks)
echo   [Security Auditor] auditor@clearstay.jp        (Audit Logs & Trace Hub)
echo ==============================================================================
echo Starting local development server and opening your browser...
echo Press Ctrl+C to stop the server anytime.
echo.

start "" "http://127.0.0.1:8000"
php artisan serve --host=127.0.0.1 --port=8000
pause
