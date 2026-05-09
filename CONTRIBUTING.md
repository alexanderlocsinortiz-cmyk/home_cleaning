# Contributing Guide

We appreciate your interest in improving Clean Flow! This guide helps you understand the development process and how to contribute effectively.

---

## Getting Started

### Prerequisites
- PHP 8.2+
- PostgreSQL 13+
- Node.js 18+
- Composer
- Git

### Development Setup

```bash
# Clone repository
git clone https://github.com/your-org/cleanflow-app.git
cd cleanflow-app

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup database
createdb cleanflow_app
php artisan migrate --seed

# Start development server
composer run dev
```

### Verify Installation
```bash
# Run tests
php vendor/bin/phpunit

# Check code style
php vendor/bin/pint --test

# Visit application
open http://localhost:8000
```

---

## Development Workflow

### 1. Create Feature Branch
```bash
# Update main branch
git checkout main
git pull origin main

# Create feature branch (use descriptive names)
git checkout -b feature/booking-notifications
# or
git checkout -b fix/attendance-timezone-issue
```

### 2. Make Changes

#### Code Style
```bash
# Auto-fix code style issues
php vendor/bin/pint

# Verify style compliance
php vendor/bin/pint --test
```

#### Testing Requirements
```bash
# All tests must pass
php vendor/bin/phpunit

# Run specific test
php vendor/bin/phpunit tests/Feature/BookingCreationTest.php

# Run with coverage
php vendor/bin/phpunit --coverage-html coverage/
```

### 3. Commit Changes

**Commit Message Format:**
```
type(scope): subject

body (if applicable)

footer (if applicable)
```

**Examples:**
```
feat(booking): add preferred cleaner selection
fix(attendance): correct timezone handling for punch records
docs(readme): update deployment instructions
test(migration): add tests for booking status transitions
refactor(controllers): extract booking logic to service class
```

**Types:** `feat` `fix` `docs` `test` `refactor` `perf` `chore`

**Scopes:** `booking` `staff` `admin` `attendance` `auth` `ui` `api` `db` `config`

### 4. Push and Create Pull Request

```bash
# Push feature branch
git push origin feature/booking-notifications

# Create PR on GitHub with:
# - Clear title and description
# - Reference any related issues (#123)
# - Screenshots for UI changes
# - Test results
```

---

## Code Standards

### PHP Code Style
- PSR-12 coding standard
- 4-space indentation
- Max 120 characters per line
- Use type hints (strict types)

```php
<?php

declare(strict_types=1);

namespace App\Services;

class BookingService
{
    public function createBooking(array $data): Booking
    {
        // Implementation
    }
}
```

### Laravel Best Practices

**Models:**
```php
// Use eager loading to prevent N+1 queries
$bookings = Booking::with('client', 'staff', 'service')->get();

// Use scopes for common queries
$activeBookings = Booking::active()->get();
```

**Controllers:**
```php
// Keep controllers thin, move logic to services
public function store(StoreBookingRequest $request): RedirectResponse
{
    $booking = $this->bookingService->create($request->validated());
    return redirect()->route('bookings.show', $booking);
}
```

**Migrations:**
```php
// Always use explicit column constraints
Schema::create('bookings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
    $table->timestamps();
});
```

### Database & Query Optimization
- Use indexes for frequently queried columns
- Eager load relationships to prevent N+1 queries
- Use database constraints (unique, not null, check, foreign keys)
- Write migrations that are reversible

### Comments & Documentation
```php
/**
 * Calculate booking price based on service and property details
 *
 * @param Booking $booking
 * @return float Total price including all fees
 */
public function calculatePrice(Booking $booking): float
{
    // Implementation
}
```

---

## Testing Requirements

### Test Coverage Expectations
- Feature tests: All user journeys and workflows
- Unit tests: Business logic, calculations, validations
- Minimum target: 80% code coverage

### Writing Tests

**Feature Test Example:**
```php
namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Tests\TestCase;

class BookingCreationTest extends TestCase
{
    public function test_client_can_create_booking()
    {
        $client = User::factory()->create(['role' => 'client']);
        
        $response = $this->actingAs($client)
            ->post('/bookings', [
                'service_id' => 1,
                'scheduled_date' => '2026-05-01',
            ]);

        $response->assertRedirect('/bookings');
        $this->assertDatabaseHas('bookings', [
            'client_id' => $client->id,
        ]);
    }
}
```

**Unit Test Example:**
```php
namespace Tests\Unit;

use App\Models\Booking;
use Tests\TestCase;

class BookingTest extends TestCase
{
    public function test_booking_total_price_includes_all_fees()
    {
        $booking = Booking::factory()->create([
            'base_price' => 570.00,
            'add_on_fees' => 50.00,
        ]);

        $total = $booking->base_price + $booking->add_on_fees;
        $this->assertEquals(620.00, $total);
    }
}
```

### Running Tests
```bash
# All tests
php vendor/bin/phpunit

# Specific test file
php vendor/bin/phpunit tests/Feature/BookingCreationTest.php

# Specific test method
php vendor/bin/phpunit --filter test_client_can_create_booking

# With coverage report
php vendor/bin/phpunit --coverage-html coverage/
```

---

## Documentation Standards

### README Sections
- Keep clear and concise
- Include code examples
- Update with major changes
- Link to detailed docs

### API Documentation
- Document all public endpoints
- Include request/response examples
- Note authentication requirements
- List error codes

### Code Comments
- Explain "why," not "what" (code shows what)
- Use for complex algorithms
- Keep comments updated with code
- Don't over-comment obvious code

---

## Git Workflow

### Branch Protection Rules
- `main` branch requires:
  - Pull request review (1+ approval)
  - All status checks passing
  - No direct pushes

### Merge Strategy
- Use "Squash and Merge" for feature branches
- Use "Create a merge commit" for release branches
- Delete branch after merge

### Handling Merge Conflicts
```bash
# Update feature branch with latest main
git fetch origin
git rebase origin/main

# Resolve conflicts in your IDE
# After resolving:
git add .
git rebase --continue
git push -f origin feature/my-feature
```

---

## Performance Guidelines

### Database Optimization
```php
// ✅ GOOD: Use select to limit columns
User::select('id', 'name', 'email')->get();

// ❌ BAD: Fetch all columns unnecessarily
User::all();

// ✅ GOOD: Paginate large result sets
$bookings = Booking::paginate(15);

// ❌ BAD: Load entire table into memory
$bookings = Booking::all();
```

### Caching Strategy
```php
// Cache frequently accessed data
$services = Cache::remember('services', 3600, function () {
    return Service::all();
});

// Clear cache when data changes
Cache::forget('services');
```

### Query Analysis
```bash
# Use Laravel Query Log to debug N+1 queries
php artisan tinker
>>> DB::enableQueryLog();
>>> Booking::with('client')->get();
>>> DB::getQueryLog();
```

---

## Security Considerations

### Before Submitting Code
- [ ] No hardcoded secrets (API keys, tokens, passwords)
- [ ] No SQL injection vulnerabilities (use parameterized queries)
- [ ] Input validation on all user data
- [ ] Authorization checks for sensitive operations
- [ ] No exposure of sensitive information in error messages
- [ ] Use `.env` for configuration
- [ ] Validate file uploads
- [ ] Escape output to prevent XSS

### Security Review Checklist
```php
// ✅ GOOD: Validate and authorize
$this->authorize('update', $booking);
$validated = $request->validate(['status' => 'required|in:pending,confirmed']);

// ❌ BAD: Trust user input blindly
$booking->status = $request->input('status');
```

---

## Release Process

### Version Numbering
Uses Semantic Versioning: `MAJOR.MINOR.PATCH`
- `MAJOR`: Breaking changes
- `MINOR`: New features (backward compatible)
- `PATCH`: Bug fixes

### Release Checklist
- [ ] All tests passing
- [ ] Code style verified
- [ ] Dependencies updated and audited
- [ ] CHANGELOG updated
- [ ] Version bumped in `package.json` and `composer.json`
- [ ] Tag created: `git tag -a v1.0.0 -m "Release 1.0.0"`
- [ ] Push tags: `git push origin --tags`
- [ ] Release notes published
- [ ] Deployment documentation updated

---

## Common Issues & Solutions

### Tests Failing in CI but Passing Locally
```bash
# Clear caches
php artisan cache:clear
php artisan config:clear

# Recreate test database
php artisan migrate:fresh --env=testing

# Run tests with fresh database
php vendor/bin/phpunit --no-coverage
```

### Database Migration Won't Revert
```bash
# Check migration history
php artisan migrate:status

# Manually verify migration file syntax
# Fix any issues, then try again
php artisan migrate:rollback
```

### Style Checker Failing
```bash
# Auto-fix all style issues
php vendor/bin/pint

# Verify no issues remain
php vendor/bin/pint --test
```

---

## Getting Help

- **Questions?** Open a discussion on GitHub
- **Issues?** Check existing issues before creating new one
- **Chat?** Join our Discord/Slack community
- **Email?** Contact dev-team@cleanflow.local

---

## Code of Conduct

We are committed to providing a welcoming and inclusive environment. Please:

- Be respectful and constructive in all interactions
- Welcome diverse perspectives and experiences
- Report violations to conduct@cleanflow.local
- Focus on the code, not the person

---

## License

By contributing to Clean Flow, you agree that your contributions will be licensed under the same license as the project (see LICENSE file).

Thank you for contributing to Clean Flow! 🎉
