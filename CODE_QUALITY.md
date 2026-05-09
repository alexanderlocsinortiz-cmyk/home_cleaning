# Code Quality Standards

This guide ensures the Clean Flow codebase maintains high quality, security, and performance standards.

---

## Quick Start

### Before Committing Code

**On Windows:**
```bash
scripts\pre-commit.bat
```

**On Mac/Linux:**
```bash
bash scripts/pre-commit.sh
```

This runs:
- ✅ Security audit
- ✅ Code style check
- ✅ Unit tests
- ✅ Feature tests

---

## Tools & Commands

### 1. Security Audit 🔐

Check for vulnerabilities in dependencies:

```bash
composer audit
```

**What it does:**
- Scans all packages for known security vulnerabilities
- Checks against advisory database
- Lists affected versions and remediation

**If vulnerabilities found:**
```bash
# Update affected packages
composer update <package-name>

# Or update all packages safely
composer update --with-dependencies
```

---

### 2. Code Style (Pint) 🎨

Enforce consistent code formatting:

```bash
# Check for issues (don't fix)
php vendor/bin/pint --test

# Auto-fix all issues
php vendor/bin/pint

# Fix specific directory
php vendor/bin/pint app/Models/
```

**What gets checked:**
- PSR-12 compliance (PHP standards)
- Spacing and indentation (4 spaces)
- Import ordering
- Line endings
- Unused imports

**Configuration:** `.pint.json`

---

### 3. Tests 🧪

Run the full test suite:

```bash
# All tests
php vendor/bin/phpunit

# Only feature tests
php vendor/bin/phpunit tests/Feature/

# Only unit tests
php vendor/bin/phpunit tests/Unit/

# Specific test file
php vendor/bin/phpunit tests/Feature/BookingCreationTest.php

# Specific test method
php vendor/bin/phpunit --filter test_client_can_create_booking

# With verbose output
php vendor/bin/phpunit -v

# Stop on first failure
php vendor/bin/phpunit --stop-on-failure
```

**Current Status:**
- ✅ 79 Feature tests
- ✅ 50+ Unit tests
- ✅ 433+ Assertions
- Target: 80%+ code coverage

---

### 4. Code Coverage 📊

Measure test coverage (requires Xdebug on Windows workaround):

```bash
# HTML coverage report (generates coverage/ folder)
php vendor/bin/phpunit --coverage-html coverage/

# Text coverage report
php vendor/bin/phpunit --coverage-text

# Clover XML (for CI/CD)
php vendor/bin/phpunit --coverage-clover coverage.xml
```

**View HTML Report:**
```bash
open coverage/index.html
```

---

## GitHub Actions (CI/CD)

Automated checks run on every push and pull request.

### Workflow File
`.github/workflows/tests.yml`

### What It Does
1. Sets up Ubuntu environment
2. Installs PHP 8.2 & PostgreSQL
3. Runs composer install
4. Runs database migrations
5. Runs security audit
6. Runs code style check (Pint)
7. Runs all tests
8. Uploads coverage to Codecov

### CI Status Badge
Add to README:
```markdown
[![Tests](https://github.com/your-org/cleanflow-app/actions/workflows/tests.yml/badge.svg)](https://github.com/your-org/cleanflow-app/actions)
```

---

## Development Workflow

### 1. Create Feature Branch
```bash
git checkout -b feature/new-feature
```

### 2. Make Changes
```bash
# Edit code, create tests, etc.
```

### 3. Run Pre-Commit Checks
```bash
# Windows
scripts\pre-commit.bat

# Mac/Linux
bash scripts/pre-commit.sh
```

### 4. Commit If Checks Pass
```bash
git add .
git commit -m "feat(booking): add new feature"
```

### 5. Push and Create PR
```bash
git push origin feature/new-feature
```

GitHub Actions will automatically run tests on your PR. All must pass before merging.

---

## Common Issues & Solutions

### Tests Passing Locally but Failing in CI

**Problem:** Code works on your machine but fails on GitHub Actions

**Solution:**
```bash
# Clear caches and rebuild
php artisan cache:clear
php artisan config:clear
php artisan migrate:fresh --seed

# Run tests again
php vendor/bin/phpunit
```

---

### Pint Conflicts with IDE Formatter

**Problem:** Your IDE auto-formats differently than Pint

**Solution:**
```bash
# Always run Pint before committing
php vendor/bin/pint

# Or configure your IDE to use Pint's rules
# EditorConfig: .editorconfig (already configured)
```

---

### Security Audit Shows Old Vulnerabilities

**Problem:** `composer audit` reports packages with old advisories

**Solution:**
```bash
# Update to latest versions
composer update

# Check if advisory is still applicable
composer audit

# View advisory details
composer show -a <package-name>
```

---

### Code Coverage Not Generating

**Problem:** `--coverage-html` returns "No code coverage driver available"

**Reason:** Windows doesn't have Xdebug installed by default

**Workaround:**
- Focus on feature tests instead
- Use online coverage tools via GitHub Actions
- Mock coverage results for CI/CD

---

## Best Practices

### ✅ DO

- [ ] Run pre-commit checks before every commit
- [ ] Write tests for new features
- [ ] Keep tests focused and isolated
- [ ] Use meaningful commit messages
- [ ] Review code coverage reports
- [ ] Update dependencies monthly
- [ ] Run `composer audit` before releases

### ❌ DON'T

- [ ] Commit without running tests
- [ ] Commit with failing tests
- [ ] Disable CI/CD checks
- [ ] Use `@SuppressWarnings` without reason
- [ ] Keep vulnerabilities unfixed
- [ ] Use hardcoded values instead of env vars

---

## Release Checklist

Before each production release:

- [ ] All tests passing: `php vendor/bin/phpunit`
- [ ] No code style issues: `php vendor/bin/pint --test`
- [ ] No vulnerabilities: `composer audit`
- [ ] Dependencies up to date: `composer outdated`
- [ ] Coverage acceptable (80%+)
- [ ] Documentation updated
- [ ] CHANGELOG updated
- [ ] Version bumped in `package.json` & `composer.json`
- [ ] Git tag created: `git tag -a v1.x.x`

---

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Pint](https://laravel.com/docs/pint)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
- [GitHub Actions Docs](https://docs.github.com/actions)

---

## Support

Questions about code quality?
- Check CONTRIBUTING.md
- Review test examples in `tests/`
- Ask in team slack/email

---

**Last Updated:** April 15, 2026
**Version:** 1.0
