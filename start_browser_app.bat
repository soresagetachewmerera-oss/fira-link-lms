@echo off
title Fira Link LMS - Web + Browser App Launcher
echo ========================================================
echo        STARTING FIRA LINK LMS (WEB + BROWSER APP)
echo ========================================================
echo.

echo [1/2] Starting Laravel Web Server (Backend + Web Portal)...
start "Fira Link - Laravel Web Server" cmd /k "cd /d "%~dp0Eduex Laravel\Eduex Laravel" && php artisan serve --port=8000"

echo [2/2] Launching Flutter App on Microsoft Edge...
start "Fira Link - Flutter Web App" cmd /k "cd /d "%~dp0Eduex Flutter" && flutter run -d edge"

echo.
echo Services launched!
echo Web: http://127.0.0.1:8000
echo.
pause
