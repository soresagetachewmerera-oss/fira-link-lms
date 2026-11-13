@echo off
setlocal
echo =======================================================
echo   Fira Link - Android App Installer (Release Build)
echo =======================================================
echo.

set APK_PATH=%~dp0fira-link-release.apk

if not exist "%APK_PATH%" (
    echo [ERROR] APK not found at %APK_PATH%
    pause
    exit /b 1
)

echo Target APK: %APK_PATH%
echo.

echo Checking ADB devices...
adb devices
echo.

echo Installing Fira Link APK to connected device/emulator...
adb install -r "%APK_PATH%"

if %ERRORLEVEL% equ 0 (
    echo.
    echo =======================================================
    echo   Installation Successful! Launching Fira Link...
    echo =======================================================
    adb shell monkey -p com.firalink.firalink 1 >nul 2>&1
) else (
    echo.
    echo [NOTE] If no device was detected:
    echo 1. Connect your Android phone via USB with USB Debugging enabled, OR
    echo 2. Start Android Studio Emulator (Pixel_7_API_34).
)

echo.
pause

