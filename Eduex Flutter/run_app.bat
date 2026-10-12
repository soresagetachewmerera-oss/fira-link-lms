@echo off
echo ========================================================
echo          STARTING FIRA LINK LMS (EMULATOR + APP)
echo ========================================================
echo.

echo [1/3] Launching Android Emulator (Pixel_7_API_34)...
start "" "D:\tools\android-sdk\emulator\emulator.exe" -avd Pixel_7_API_34

echo [2/3] Waiting for emulator device to be ready...
"D:\tools\android-sdk\platform-tools\adb.exe" wait-for-device

echo Forwarding backend port 8000 to emulator...
"D:\tools\android-sdk\platform-tools\adb.exe" reverse tcp:8000 tcp:8000

echo.
echo [3/3] Starting Flutter application...
cd /d "%~dp0"
flutter run -d emulator-5554

pause