# ClearStay 2.0 Enterprise Platform PowerShell Launcher
$env:Path = "D:\php;" + $env:Path
Set-Location "d:\CAD Revision & Manufacturing Change Management System\ClearStay"

Write-Host "==============================================================================" -ForegroundColor Cyan
Write-Host "      CLEARSTAY 2.0 - ENTERPRISE HOTEL OPERATIONS & MULTI-CHANNEL PLATFORM" -ForegroundColor Cyan
Write-Host "==============================================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Branch: feature/clearstay-2-enterprise-platform" -ForegroundColor Green
Write-Host "  URL:    http://127.0.0.1:8000" -ForegroundColor Green
Write-Host ""
Write-Host "  DEMO CREDENTIALS (All password: Password123!):" -ForegroundColor Yellow
Write-Host "  ----------------------------------------------------------------------------"
Write-Host "  [Owner]            owner@clearstay.jp          (Global Property Access)"
Write-Host "  [General Manager]  manager.tokyo@clearstay.jp  (Tokyo Central Hotel)"
Write-Host "  [Revenue Manager]  revenue.tokyo@clearstay.jp  (Rates, Parity & Bulk Edit)"
Write-Host "  [Integration QA]   integration.qa@clearstay.jp (Partner Assurance & Scenarios)"
Write-Host "  [Front Desk]       frontdesk.tokyo@clearstay.jp(Reservations & Simple Mode)"
Write-Host "  [Housekeeping]     cleaner.tokyo@clearstay.jp  (Rooms & Tasks)"
Write-Host "  [Security Auditor] auditor@clearstay.jp        (Audit Logs & Trace Hub)"
Write-Host "==============================================================================" -ForegroundColor Cyan
Write-Host "Starting local development server on http://127.0.0.1:8000 ..." -ForegroundColor White
Write-Host ""

php artisan serve --host=127.0.0.1 --port=8000
