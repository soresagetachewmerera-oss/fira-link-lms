@echo off
title Fira Link LMS - All-in-One Launcher
echo ========================================================
echo             STARTING FIRA LINK LMS ECOSYSTEM
echo ========================================================
echo.

echo [1/3] Starting Laravel Web Server (Backend + Web Portal)...
start "Fira Link - Laravel Web Server" cmd /k "cd /d "%~dp0Eduex Laravel\Eduex Laravel" && php artisan serve --host=0.0.0.0 --port=8000"

echo [2/3] Waiting for backend to start...
timeout /t 3 /nobreak >nul

echo [3/3] Launching Android Emulator and Flutter Mobile App...
start "Fira Link - Android App" cmd /k "cd /d "%~dp0Eduex Flutter" && call run_app.bat"

echo.
echo ========================================================
echo  All services launched in their own command windows!
echo  - Web Portal: http://127.0.0.1:8000
echo  - API:        http://127.0.0.1:8000/api
echo ========================================================
echo.
pause
