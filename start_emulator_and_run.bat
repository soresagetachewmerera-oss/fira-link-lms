@echo off
setlocal
echo =======================================================
echo   Starting Android Emulator (Pixel_7_API_34)...
echo =======================================================
echo.

start "" emulator -avd Pixel_7_API_34

echo Waiting for emulator device to boot...
adb wait-for-device

echo Waiting for system boot completion...
:wait_boot
for /f "tokens=*" %%a in ('adb shell getprop sys.boot_completed 2^>nul') do set BOOT=%%a
if not "%BOOT%"=="1" (
    timeout /t 2 /nobreak >nul
    goto wait_boot
)

echo.
echo Emulator booted! Installing Fira Link APK...
adb install -r "%~dp0fira-link-release.apk"

echo.
echo Launching Fira Link...
adb shell monkey -p com.firalink.firalink 1 >nul 2>&1

echo Done!
pause

