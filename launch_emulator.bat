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

echo Checking CPU and Driver acceleration status...
sc query aehd >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Android Emulator Hypervisor Driver is ready!
    echo Launching Pixel_7_API_34 with hardware acceleration...
    "D:\tools\android-sdk\emulator\emulator.exe" -avd Pixel_7_API_34
) else (
    echo [INFO] AEHD Driver is not installed yet.
    echo.
    echo  RECOMMENDED: Right-click 'install_aehd_driver.bat' and choose
    echo  'Run as administrator' for fast 60fps hardware emulation.
    echo.
    echo  Launching Pixel_7_API_34 with software emulation mode...
    set ANDROID_I_WANT_MY_TCG=yes
    "D:\tools\android-sdk\emulator\emulator.exe" -avd Pixel_7_API_34 -accel off
)

if %errorlevel% neq 0 (
    echo.
    echo ========================================================
    echo If the emulator failed to start, please right-click
    echo 'install_aehd_driver.bat' and select 'Run as administrator'.
    echo ========================================================
)

pause
