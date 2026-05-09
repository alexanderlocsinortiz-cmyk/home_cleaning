#!/bin/bash

# Clean Flow - Pre-Commit Quality Checks
# Run this before committing code to catch issues early

echo "🔍 Running pre-commit quality checks..."
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

ERRORS=0

# 1. Security audit
echo "📦 Checking for security vulnerabilities..."
if ! composer audit --no-interaction 2>&1 | grep -q "No security vulnerability"; then
    echo -e "${RED}❌ Security vulnerabilities found!${NC}"
    ERRORS=$((ERRORS + 1))
else
    echo -e "${GREEN}✓ No security vulnerabilities${NC}"
fi
echo ""

# 2. Code style
echo "🎨 Checking code style with Pint..."
if php vendor/bin/pint --test 2>&1 | grep -q "has style issues"; then
    echo -e "${YELLOW}⚠ Code style issues found. Running auto-fix...${NC}"
    php vendor/bin/pint
    echo -e "${GREEN}✓ Code style fixed automatically${NC}"
fi
echo ""

# 3. Run tests
echo "🧪 Running unit tests..."
if ! php vendor/bin/phpunit tests/Unit/ --colors=never 2>&1 | grep -q "OK"; then
    echo -e "${RED}❌ Some unit tests failed!${NC}"
    ERRORS=$((ERRORS + 1))
else
    echo -e "${GREEN}✓ All unit tests passed${NC}"
fi
echo ""

# 4. Run feature tests
echo "🧪 Running feature tests..."
if ! php vendor/bin/phpunit tests/Feature/ --colors=never 2>&1 | grep -q "OK"; then
    echo -e "${RED}❌ Some feature tests failed!${NC}"
    ERRORS=$((ERRORS + 1))
else
    echo -e "${GREEN}✓ All feature tests passed${NC}"
fi
echo ""

# Summary
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✅ All checks passed! Safe to commit.${NC}"
    exit 0
else
    echo -e "${RED}❌ $ERRORS check(s) failed. Please fix and try again.${NC}"
    exit 1
fi
