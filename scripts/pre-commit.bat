@echo off
setlocal

REM Clean Flow - Pre-Commit Quality Checks for Windows
REM Run this before committing code to catch issues early.

echo.
echo ========================================
echo   CLEANFLOW PRE-COMMIT QUALITY CHECKS
echo ========================================
echo.

set "ERRORS=0"

echo [1/5] Checking for security vulnerabilities...
call composer audit --no-interaction
if errorlevel 1 (
    echo [ERROR] Security audit failed or vulnerabilities were found.
    set /a ERRORS+=1
) else (
    echo [OK] No security vulnerabilities reported.
)
echo.

echo [2/5] Checking code style with Pint...
php vendor/bin/pint --test --dirty
if errorlevel 1 (
    echo [ERROR] Code style check failed. Run: php vendor/bin/pint
    set /a ERRORS+=1
) else (
    echo [OK] Code style is compliant.
)
echo.

echo [3/5] Running unit tests...
php vendor/bin/phpunit tests/Unit/ --colors=never
if errorlevel 1 (
    echo [ERROR] Unit tests failed.
    set /a ERRORS+=1
) else (
    echo [OK] All unit tests passed.
)
echo.

echo [4/5] Running feature tests...
php vendor/bin/phpunit tests/Feature/ --colors=never
if errorlevel 1 (
    echo [ERROR] Feature tests failed.
    set /a ERRORS+=1
) else (
    echo [OK] All feature tests passed.
)
echo.

echo [5/5] Building frontend assets...
call npm run build
if errorlevel 1 (
    echo [ERROR] Frontend build failed.
    set /a ERRORS+=1
) else (
    echo [OK] Frontend assets built successfully.
)
echo.

echo ========================================
if %ERRORS% equ 0 (
    echo [SUCCESS] All checks passed. Safe to commit.
    echo ========================================
    exit /b 0
)

echo [FAILURE] %ERRORS% check(s) failed. Please fix them before committing.
echo ========================================
exit /b 1
