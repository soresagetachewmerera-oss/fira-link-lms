@echo off
title Fira Link LMS - Mobile App Launcher
echo ========================================================
echo          STARTING FIRA LINK LMS (EMULATOR + APP)
echo ========================================================
echo.

set ANDROID_HOME=D:\tools\android-sdk
set ANDROID_USER_HOME=D:\tools\.android
set ANDROID_AVD_HOME=D:\tools\.android\avd
set GRADLE_USER_HOME=D:\tools\.gradle
set PATH=C:\Users\sora\dev\flutter\bin;D:\tools\android-sdk\platform-tools;D:\tools\android-sdk\emulator;%PATH%

adb start-server >nul 2>&1

echo [1/3] Checking connected devices or launching Android Emulator (Pixel_7_API_34)...
adb devices | findstr /R "device$" >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] An Android device or emulator is already connected!
    goto ready_to_run
)

echo Starting emulator Pixel_7_API_34...
del /q /f "%ANDROID_AVD_HOME%\Pixel_7_API_34.avd\multiinstance.lock" >nul 2>&1
rd /s /q "%ANDROID_AVD_HOME%\Pixel_7_API_34.avd\hardware-qemu.ini.lock" >nul 2>&1
sc query aehd >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] AEHD Hardware acceleration active.
    start "" "D:\tools\android-sdk\emulator\emulator.exe" -avd Pixel_7_API_34
) else (
    echo [INFO] AEHD Driver not yet registered. Using software emulation mode.
    echo (Tip: Run 'install_aehd_driver.bat' as Administrator for 60fps hardware speed)
    set ANDROID_I_WANT_MY_TCG=yes
    start "" "D:\tools\android-sdk\emulator\emulator.exe" -avd Pixel_7_API_34 -accel off
)

echo [2/3] Waiting for Android device/emulator to boot...
adb wait-for-device
echo Device detected! Waiting a few seconds for system stabilization...
powershell -NoProfile -Command "Start-Sleep -Seconds 4"
echo [OK] Android system ready!

:ready_to_run
echo.
echo Forwarding backend port 8000 to device...
adb reverse tcp:8000 tcp:8000 2>nul

echo.
echo [3/3] Starting Flutter application on Android...
cd /d "%~dp0"
flutter run -d android

pause

