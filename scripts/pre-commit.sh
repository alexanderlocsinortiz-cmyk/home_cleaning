#!/usr/bin/env bash

# Clean Flow - Pre-Commit Quality Checks
# Run this before committing code to catch issues early.

set +e
errors=0

echo ""
echo "========================================"
echo "  CLEANFLOW PRE-COMMIT QUALITY CHECKS"
echo "========================================"
echo ""

echo "[1/5] Checking for security vulnerabilities..."
if composer audit --no-interaction; then
    echo "[OK] No security vulnerabilities reported."
else
    echo "[ERROR] Security audit failed or vulnerabilities were found."
    errors=$((errors + 1))
fi
echo ""

echo "[2/5] Checking code style with Pint..."
if php vendor/bin/pint --test --dirty; then
    echo "[OK] Code style is compliant."
else
    echo "[ERROR] Code style check failed. Run: php vendor/bin/pint"
    errors=$((errors + 1))
fi
echo ""

echo "[3/5] Running unit tests..."
if php vendor/bin/phpunit tests/Unit/ --colors=never; then
    echo "[OK] All unit tests passed."
else
    echo "[ERROR] Unit tests failed."
    errors=$((errors + 1))
fi
echo ""

echo "[4/5] Running feature tests..."
if php vendor/bin/phpunit tests/Feature/ --colors=never; then
    echo "[OK] All feature tests passed."
else
    echo "[ERROR] Feature tests failed."
    errors=$((errors + 1))
fi
echo ""

echo "[5/5] Building frontend assets..."
if npm run build; then
    echo "[OK] Frontend assets built successfully."
else
    echo "[ERROR] Frontend build failed."
    errors=$((errors + 1))
fi
echo ""

echo "========================================"
if [ "$errors" -eq 0 ]; then
    echo "[SUCCESS] All checks passed. Safe to commit."
    echo "========================================"
    exit 0
fi

echo "[FAILURE] $errors check(s) failed. Please fix them before committing."
echo "========================================"
exit 1
