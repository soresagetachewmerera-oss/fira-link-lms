@echo off
title Fira Link LMS - Mobile App Launcher
echo ========================================================
echo          STARTING FIRA LINK LMS (EMULATOR + APP)
echo ========================================================
echo.

set ANDROID_HOME=D:\tools\android-sdk
set ANDROID_USER_HOME=D:\tools\.android
set ANDROID_AVD_HOME=D:\tools\.android\avd
set PATH=C:\Users\sora\dev\flutter\bin;D:\tools\android-sdk\platform-tools;D:\tools\android-sdk\emulator;%PATH%

echo [1/3] Checking connected devices or launching Android Emulator (Pixel_7_API_34)...
adb devices | findstr /R "device$" >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] An Android device or emulator is already connected!
    goto ready_to_run
)

echo Starting emulator Pixel_7_API_34...
start "" "D:\tools\android-sdk\emulator\emulator.exe" -avd Pixel_7_API_34

echo [2/3] Waiting for Android device/emulator to boot...
echo (If this is the first boot or VT-x is disabled, please ensure Virtualization is Enabled in BIOS)
adb wait-for-device

:ready_to_run
echo.
echo Forwarding backend port 8000 to device...
adb reverse tcp:8000 tcp:8000 2>nul

echo.
echo [3/3] Starting Flutter application...
cd /d "%~dp0"
flutter run

pause

