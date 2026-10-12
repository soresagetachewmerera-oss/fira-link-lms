@echo off
title Fira Link - Launch Android Emulator
echo ========================================================
echo          LAUNCHING ANDROID EMULATOR (Pixel_7_API_34)
echo ========================================================
echo.

set ANDROID_HOME=D:\tools\android-sdk
set ANDROID_USER_HOME=D:\tools\.android
set ANDROID_AVD_HOME=D:\tools\.android\avd
set PATH=D:\tools\android-sdk\emulator;D:\tools\android-sdk\platform-tools;%PATH%

echo Checking CPU Virtualization status...
systeminfo | findstr /i "Virtualization"

echo.
echo Launching Pixel_7_API_34...
"D:\tools\android-sdk\emulator\emulator.exe" -avd Pixel_7_API_34

if %errorlevel% neq 0 (
    echo.
    echo ========================================================
    echo [NOTE] If the emulator failed to start, Intel Virtualization
    echo        Technology (VT-x) must be enabled in your HP OMEN BIOS.
    echo        1. Restart and press F10 during boot.
    echo        2. Go to Configuration -> Virtualization Technology -> Enabled.
    echo        3. Press F10 to Save and Exit.
    echo ========================================================
)

pause
