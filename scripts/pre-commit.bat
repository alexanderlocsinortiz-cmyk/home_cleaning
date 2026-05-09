@echo off
REM Clean Flow - Pre-Commit Quality Checks for Windows
REM Run this before committing code to catch issues early

echo.
echo ========================================
echo   CLEANFLOW PRE-COMMIT QUALITY CHECKS
echo ========================================
echo.

setlocal enabledelayedexpansion
set "ERRORS=0"

REM 1. Security audit
echo [1/4] Checking for security vulnerabilities...
php vendor/bin/composer audit --no-interaction 2>&1 | findstr /M "No security vulnerability" >nul
if %errorlevel% neq 0 (
    echo [ERROR] Security vulnerabilities found!
    set /a ERRORS=!ERRORS!+1
) else (
    echo [OK] No security vulnerabilities
)
echo.

REM 2. Code style
echo [2/4] Checking code style with Pint...
php vendor/bin/pint --test >nul 2>&1
if %errorlevel% neq 0 (
    echo [WARNING] Code style issues found. Running auto-fix...
    php vendor/bin/pint
    echo [OK] Code style fixed
) else (
    echo [OK] Code style compliant
)
echo.

REM 3. Unit Tests
echo [3/4] Running unit tests...
php vendor/bin/phpunit tests/Unit/ --colors=never 2>&1 | findstr /M "OK" >nul
if %errorlevel% neq 0 (
    echo [ERROR] Some unit tests failed!
    set /a ERRORS=!ERRORS!+1
) else (
    echo [OK] All unit tests passed
)
echo.

REM 4. Feature Tests
echo [4/4] Running feature tests...
php vendor/bin/phpunit tests/Feature/ --colors=never 2>&1 | findstr /M "OK" >nul
if %errorlevel% neq 0 (
    echo [ERROR] Some feature tests failed!
    set /a ERRORS=!ERRORS!+1
) else (
    echo [OK] All feature tests passed
)
echo.

REM Summary
echo ========================================
if %ERRORS% equ 0 (
    echo [SUCCESS] All checks passed!
    echo Safe to commit.
    echo ========================================
    exit /b 0
) else (
    echo [FAILURE] %ERRORS% check(s) failed!
    echo Please fix and try again.
    echo ========================================
    exit /b 1
)
