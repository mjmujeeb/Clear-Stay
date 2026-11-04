@echo off
title Push ClearStay to GitHub
echo ===================================================
echo   ClearStay - Push to GitHub Repository
echo   Target: https://github.com/mjmujeeb/Clear-Stay
echo ===================================================
echo.

cd /d "%~dp0"

echo [1/3] Checking Git Status and Staging Latest Files...
git add .
git commit -m "feat: complete ClearStay hotel management system with cyber security and bot defense" 2>nul

echo [2/3] Setting Remote Origin...
git remote set-url origin https://github.com/mjmujeeb/Clear-Stay.git 2>nul || git remote add origin https://github.com/mjmujeeb/Clear-Stay.git

echo [3/3] Pushing to GitHub (main branch)...
echo.
echo NOTE: If a browser window opens, please click "Sign in with your browser"
echo       to authorize Git Credential Manager for GitHub.
echo.
git push -u origin main --force

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ===================================================
    echo [SUCCESS] ClearStay has been successfully pushed!
    echo View your code at: https://github.com/mjmujeeb/Clear-Stay
    echo ===================================================
) else (
    echo.
    echo ===================================================
    echo [FAILED] Push failed. If authentication was requested:
    echo 1. Generate a GitHub Personal Access Token at:
    echo    https://github.com/settings/tokens (select 'repo' scope)
    echo 2. Run this command in PowerShell or Command Prompt:
    echo    git push https://<YOUR_TOKEN>@github.com/mjmujeeb/Clear-Stay.git main --force
    echo ===================================================
)

echo.
pause
