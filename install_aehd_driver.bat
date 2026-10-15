@echo off
title Install Android Emulator Hypervisor Driver (AEHD)
echo ========================================================
echo       INSTALLING ANDROID EMULATOR HYPERVISOR DRIVER
echo ========================================================
echo.

cd /d "D:\tools\android-sdk\extras\google\Android_Emulator_Hypervisor_Driver"

echo Checking Administrative permissions...
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] This script must be run as Administrator!
    echo Please right-click install_aehd_driver.bat and select "Run as administrator".
    pause
    exit /b 1
)

echo [1/3] Removing old driver services if present...
sc query gvm >nul 2>&1 && (sc stop gvm & sc delete gvm)
sc query aehd >nul 2>&1 && (sc stop aehd & sc delete aehd)

echo [2/3] Installing aehd.Inf driver...
RUNDLL32.EXE SETUPAPI.DLL,InstallHinfSection DefaultInstall 132 .\aehd.Inf

echo [3/3] Starting AEHD service...
sc start aehd

if %errorlevel% equ 0 (
    echo.
    echo ========================================================
    echo [SUCCESS] Android Emulator Hypervisor Driver installed!
    echo ========================================================
) else (
    echo.
    echo ========================================================
    echo [NOTE] Driver could not start because CPU Virtualization
    echo        (Intel VT-x) is disabled in your HP OMEN BIOS.
    echo.
    echo        To fix this:
    echo        1. Restart your laptop and press F10 during boot.
    echo        2. Go to Configuration -> Virtualization Technology -> Enabled.
    echo        3. Press F10 to Save & Exit.
    echo ========================================================
)

pause
