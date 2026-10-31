@echo off
echo ===================================================
echo Running Fira Link LMS Bilingual Quality Gates
echo ===================================================

echo [1/3] Checking Key Parity & Amharic Integrity...
python scripts\check_i18n.py
if %ERRORLEVEL% NEQ 0 (
    echo [FAIL] I18n Key Parity Check Failed!
    exit /b 1
)

echo [2/3] Checking for Hardcoded Strings in UI...
python scripts\lint_hardcoded.py
if %ERRORLEVEL% NEQ 0 (
    echo [FAIL] Hardcoded String Lint Failed!
    exit /b 1
)

echo [3/3] Running Flutter Localization Tests...
pushd "Eduex Flutter"
flutter test test\localization_test.dart
if %ERRORLEVEL% NEQ 0 (
    popd
    echo [FAIL] Flutter Localization Unit Tests Failed!
    exit /b 1
)
popd

echo ===================================================
echo [SUCCESS] All Bilingual Localization Quality Gates Passed!
echo ===================================================
exit /b 0
