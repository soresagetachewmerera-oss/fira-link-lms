@echo off
title Fira Link Mobile App - iPhone Access
echo ========================================================
echo       FIRA LINK MOBILE APP - IPHONE & LAN ACCESS
echo ========================================================
echo.
echo Ensure your iPhone is on the same Wi-Fi network.
echo.
echo Open Safari on your iPhone and navigate to:
echo   http://192.168.0.111:5000
echo   (or http://localhost:5000 if on this machine)
echo.
echo TIP FOR IPHONE:
echo   In Safari, tap the Share button (square with arrow up),
echo   then tap "Add to Home Screen".
echo   The app will install directly on your iPhone home screen!
echo ========================================================
echo.
cd /d "%~dp0Eduex Flutter\build\web"
php -S 0.0.0.0:5000 router.php
