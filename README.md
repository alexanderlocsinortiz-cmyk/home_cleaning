# Home Cleaning Service

Home Cleaning Service is a Laravel 12 home-cleaning service platform for Valencia City. It includes separate client, staff, and admin experiences for booking services, assigning cleaners, tracking active jobs, managing payments and subscriptions, and reviewing operations.

## Highlights

- Public landing page with package pricing, FAQs, and service-area coverage.
- Client booking flow with profile management, schedule conflict protection, pricing breakdowns, preferred cleaner requests, payment selection, and subscription plans.
- Admin dashboard for customers, staff, services, bookings, reports, suspicious-booking review, payment management, and analytics trends.
- Staff portal for assigned jobs, schedule, notifications, before-and-after proof uploads, and performance metrics.
- Live booking location tracking for active jobs, including booking history and proof-of-service records.
- Email notifications for booking submission, confirmation, assignment, in-progress updates, completion, and preferred-cleaner outcomes.
- Installable PWA support with a browser install prompt and offline fallback screen.

## Implementation Status

### Week 1 Complete

- Prevents duplicate active bookings by the same client in the same date and time slot.
- Prevents assigning the same cleaner to overlapping active bookings.
- Checks time-slot staffing capacity before accepting a booking.
- Shows cleaner availability in the admin booking assignment workflow.

### Week 2 Complete

- Requires `phone` and `date_of_birth` during client registration.
- Enforces a minimum client age of 18 for registration and booking eligibility.
- Redirects legacy client accounts with missing trust data to complete their profile before booking.
- Flags suspicious bookings for manual review when:
  - another client requests the exact same address and schedule
  - one client creates multiple booking requests within the last 24 hours
- Adds admin approve/block actions for suspicious bookings before they move through the normal workflow.
- Stores booking review metadata through `risk_reasons`, `manual_review_status`, `reviewed_by`, and `reviewed_at`.

### Week 3 Complete

- Lets clients request a preferred cleaner during booking.
- Tracks whether the preferred cleaner request is `requested`, `unavailable`, `assigned`, or `alternate_assigned`.
- Shows preferred-cleaner outcomes in booking details, admin booking management, client dashboard, and booking emails.

### Week 4 Complete

- Adds `floor_area` and optional `add_ons` to the pricing flow.
- Computes transparent booking totals using service type, property type, rooms, bathrooms, excess floor area, and selected add-ons.
- Shows a clear pricing breakdown in the landing page, booking form, and booking details.

### Week 5 Complete

- Expands the service catalog into package-style offerings.
- Adds `Post Construction Cleaning`, `Office and Commercial Cleaning`, and `Weekly Maintenance Plan` to the seeded catalog.
- Supports eco-friendly cleaning as an optional add-on during booking.

### Week 6 Complete

- Adds digital payment tracking with `Cash on Service Day`, `GCash`, and `Maya`.
- Stores `payment_method`, `payment_status`, `payment_reference`, and `paid_at` booking metadata.
- Supports recurring subscription plans with weekly, bi-weekly, and monthly scheduling.

### Week 7 Complete

- Requires before-service photo uploads when staff starts an assigned booking.
- Requires after-service photo uploads and supports an optional completion video when staff marks a booking completed.
- Stores proof-of-service records and booking activity logs for audit-ready service history.

### Week 8 Complete

- Adds advanced admin analytics to the dashboard and reports pages.
- Tracks booking growth, peak booking slots, and busiest weekdays.
- Tracks top staff performance trends and customer satisfaction trends from ratings and reviews.

## Tech Stack

- PHP 8.2+
- Laravel 12
- Blade templates
- Vite 7
- Tailwind CSS 4
- PostgreSQL by default in `.env.example`

## Color Palette

The active product UI uses a professional Ocean Blue & Teal palette with modern slate neutrals.

### Core Brand Colors

| Token | Hex | Usage |
| --- | --- | --- |
| Primary | `#2563eb` | CTA buttons, primary booking actions, in-progress states |
| Primary 600 | `#1d4ed8` | Hover states and emphasis |
| Accent | `#14b8a6` | Success states, confirmed bookings, positive highlights |
| Accent 600 | `#0d9488` | Accent hover states |

### Shared Neutrals

| Token | Hex | Usage |
| --- | --- | --- |
| Secondary | `#475569` | Secondary actions, metadata, supporting UI |
| Slate 800 | `#1e293b` | Main headings and strong text |
| Slate 600 | `#475569` | Secondary text and helper copy |
| Slate 400 | `#94a3b8` | Labels, meta text, low-emphasis UI |
| Slate 50 | `#f8fafc` | Table headers, light panels, subtle fills |
| Slate 100 | `#f1f5f9` | Page backgrounds and card borders |
| Slate 200 | `#e2e8f0` | Inputs, dividers, standard borders |

### Status Colors

| Token | Hex | Usage |
| --- | --- | --- |
| Success (Teal) | `#14b8a6` | Completed bookings, success states, positive metrics |
| Warning (Amber) | `#f59e0b` | Pending states, caution, warning badges |
| Danger (Rose) | `#e11d48` | Errors, destructive actions, cancelled status |
| Primary (Blue) | `#2563eb` | In-progress booking state, active operations |

### Booking Status Color Map

| Status | Color | Hex |
| --- | --- | --- |
| Pending | Amber | `#f59e0b` |
| Confirmed | Accent (Teal) | `#14b8a6` |
| In Progress | Primary (Blue) | `#2563eb` |
| Completed | Accent Dark (Teal) | `#0d9488` |
| Cancelled | Danger (Rose) | `#e11d48` |

The color palette is defined in `tailwind.config.js` and `resources/css/app.css` with full shade scales (50-950) for each color. All UI components use these standardized colors for consistent visual hierarchy across admin, staff, and client portals.

## Default Local Setup

The checked-in `.env.example` is configured for local development with:

- `DB_CONNECTION=pgsql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=5432`
- `DB_DATABASE=cleanflow_app`
- `DB_USERNAME=postgres`
- `QUEUE_CONNECTION=database`
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- `MAIL_MAILER=log`

That means a fresh local setup targets PostgreSQL, and emails are written to the Laravel log until you switch to a real mail transport.

## Quick Start

1. Create a PostgreSQL database that matches your `.env` settings, then install the app dependencies and build assets:

```bash
composer run setup
```

2. Seed the demo data:

```bash
php artisan db:seed
```

3. Start the local development environment:

```bash
composer run dev
```

`composer run dev` starts the Laravel server, database queue listener, log tailing, and Vite in one command.

## PWA Install

The app now includes a web app manifest and service worker, so supported browsers can install it like an app.

- Open the site in Chrome, Edge, or another PWA-capable browser.
- Look for the browser install prompt or the in-app install banner.
- On iPhone Safari, use `Share > Add to Home Screen`.

## Manual Setup

If you prefer to run each step yourself:

```bash
composer install
```

Copy `.env.example` to `.env`, then generate the app key:

```bash
php artisan key:generate
```

Update the `DB_*` values in `.env` if your PostgreSQL host, database name, username, or password are different from the defaults.

Run the database migrations and seeders:

```bash
php artisan migrate --seed
```

Install frontend dependencies and build assets:

```bash
npm install
npm run build
```

For local development with hot reload:

```bash
php artisan serve
npm run dev
```

## Demo Accounts

After running `php artisan db:seed`, the following accounts are available:

| Role | Username | Email | Password |
| --- | --- | --- | --- |
| Admin | `testuser` | `tester@admin.com` | `password123` |
| Staff | `staffer` | `staff@Home Cleaning Service.local` | `password123` |
| Client | `clientuser` | `client@Home Cleaning Service.local` | `password123` |

These test accounts are skipped automatically in the `production` environment. The seeders also create sample services and additional staff records for testing the admin views.

## Main Application Areas

### Public

- `/` - landing page
- `/map` - service-area map
- `/register` - client registration
- `/login` - login page

### Client

- `/client/dashboard` - client dashboard
- `/bookings` - booking list
- `/bookings/create` - create a booking with pricing, add-ons, preferred cleaner, payment, and subscription options
- `/bookings/{id}` - booking details, pricing breakdown, proof-of-service history, and live tracking
- `/profile` and `/profile/edit` - shared authenticated profile pages
- `/client/profile` and `/client/profile/edit` - client portal profile pages used for booking trust data completion

### Staff

- `/staff/dashboard` - staff overview
- `/staff/bookings` - assigned jobs, status updates, and proof uploads
- `/staff/schedule` - booking calendar view
- `/staff/performance` - ratings and earnings summary
- `/staff/notifications` - assignment and status notifications

### Admin

- `/admin/dashboard` - operations overview with booking, staff, and satisfaction analytics
- `/admin/customers` - client management
- `/admin/bookings` - booking management, staff assignment, payment updates, and suspicious-booking review
- `/admin/services` - service catalog management
- `/admin/staff` - staff management
- `/admin/reports` - booking, revenue, staff, and customer satisfaction reporting
- `/admin/service-areas` - mapped coverage areas

## Seeded Services

The default database seeder inserts these active services:

- Basic Clean
- Deep Clean
- Move-in/Move-out Clean
- Post Construction Cleaning
- Office and Commercial Cleaning
- Weekly Maintenance Plan

## Useful Commands

```bash
composer run dev
composer run test
php artisan migrate:fresh --seed
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Notes

- Booking data uses status values such as `pending`, `confirmed`, `in_progress`, `completed`, and `cancelled`.
- Booking schedule protection is enforced before a request is accepted or a cleaner is assigned.
- Suspicious bookings can enter a manual review state before confirmation.
- Booking pricing stores base price, property adjustments, room and bathroom fees, floor-area fees, and selected add-on fees.
- Booking records can include a preferred cleaner request, payment metadata, and recurring subscription metadata.
- Staff service execution stores proof files in `booking_service_proofs` and audit entries in `booking_activity_logs`.
- Live location updates are stored in `booking_locations` and exposed through authenticated booking tracking endpoints.
- If you switch to a different database server, update the `DB_*` values in `.env` before running migrations.
- To send real emails, replace the default `MAIL_MAILER=log` settings with your SMTP or provider configuration.

## API Documentation

### IoT Device Endpoints (No Authentication Required)

#### Device Attendance Punch
- **POST** `/api/iot/attendance/punch`
- Requires device token for authentication
- Records staff attendance via biometric/fingerprint verification
- **Request:** Device token, punch timestamp, fingerprint template
- **Response:** Punch status, timestamp, verification result

#### Device Heartbeat
- **POST** `/api/iot/device/heartbeat`
- Monitors device health and connectivity
- **Request:** Device token, device status
- **Response:** Acknowledgment, server time

#### Enrollment Request Status
- **GET** `/api/iot/device/enrollment/next`
- Retrieves next pending enrollment request for device
- **POST** `/api/iot/device/enrollment/status`
- Updates fingerprint enrollment status
- **Response:** Enrollment status, template data

### Admin Endpoints (Requires Authentication)

#### Today's Attendance Status
- **GET** `/api/attendance/today` (middleware: `auth`)
- Retrieves staff attendance status for current day
- **Response:** Attendance records, punch times, presence status

## Architecture

### Directory Structure

- **app/Models/** - Eloquent models (User, Booking, Staff, Service, Rating, etc.)
- **app/Http/Controllers/** - Route controllers for web and API
- **app/Mail/** - Mailable classes for booking notifications
- **app/Notifications/** - Notification channels
- **database/migrations/** - Schema migrations
- **database/seeders/** - Test data seeders
- **resources/views/** - Blade templates
- **resources/js/** - Frontend JavaScript
- **routes/** - Route definitions (web.php, api.php, console.php)
- **tests/Feature/** - Feature/integration tests (79 tests)
- **config/** - Application configuration files

### Key Models

| Model | Purpose |
| --- | --- |
| `User` | Authenticated users (clients, staff, admin) |
| `Booking` | Service booking requests and history |
| `Staff` | Staff profiles and assignment tracking |
| `Service` | Available cleaning services |
| `Rating` | Customer ratings and reviews |
| `BookingLocation` | Real-time and historical location tracking |
| `BookingActivityLog` | Audit trail for booking state changes |
| `BookingServiceProof` | Before/after photos and completion videos |
| `Device` | IoT attendance devices |
| `AttendanceLog` | Staff punch records |
| `DeviceEnrollmentRequest` | Fingerprint enrollment workflow |

### Authentication & Authorization

- **Middleware:** `auth`, `guest`, `admin`, `staff`, `client`, `verified`
- **Email Verification:** Via custom email code or standard Laravel verification
- **Role-Based Access:** Controlled through middleware and gate policies

## Testing

Run all tests:

```bash
php vendor/bin/phpunit
```

**Test Coverage:** 79 tests, 433 assertions
- Feature tests for: Admin operations, booking workflows, authentication, attendance, reports, payment processing
- Tests verify suspicious booking detection, staff scheduling constraints, pricing calculations, and service proof uploads

## Code Quality

Fix code style issues automatically with Pint:

```bash
php vendor/bin/pint
```

Generate autoload files:

```bash
composer dump-autoload
```

## Security Considerations

### Authentication & Authorization
- All sensitive routes protected by middleware
- Email verification required for client bookings
- Role-based access control via middleware
- Admin functions guarded by `AdminMiddleware`

### Payment Processing
- Digital payment methods tracked: Cash, GCash, Maya
- Payment status stored separately from booking status
- Payment reference maintained for audit logs

### Data Privacy
- User phone and date of birth captured during registration
- Minimum age 18 required for client registration
- Personal data only accessible by authorized users

### API Security
- IoT device endpoints require device tokens and signed request headers (timestamp, nonce, and HMAC signature)
- Authenticated endpoints require login session
- CSRF protection enabled on web routes

### Device Security (IoT Attendance)
- Each device has unique token for API authentication
- Signed request validation blocks request tampering and replay attempts
- Device heartbeat monitoring for connectivity
- Enrollment request workflow for fingerprint template management

## Deployment

### Environment Setup

Required `.env` variables for production:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` (generated with `php artisan key:generate`)
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`
- `ATTENDANCE_TIMEZONE=Asia/Manila`

### Pre-Deployment Checklist

```bash
# 1. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 2. Run migrations
php artisan migrate --force

# 3. Build frontend assets
npm run build

# 4. Set permissions
chmod -R 775 storage bootstrap/cache

# 5. Verify queue is running
php artisan queue:work --daemon
```

### Production Webserver Configuration

Configure your web server (Nginx/Apache) to:
- Point to the `public/` directory as document root
- Set proper file/directory permissions
- Enable HTTPS
- Configure queue workers for async jobs
- Set up log rotation in `config/logging.php`

## Troubleshooting

### Page Looks Unstyled (Raw HTML / Huge Logo)
- Symptom: the page renders plain links/text with no Tailwind styles, and large images/icons.
- Cause: Laravel is reading a stale `public/hot` file that points to a Vite dev server URL that is no longer running.
- Fast fix:

```bash
rm public/hot
npm run build
php artisan optimize:clear
```

- Windows PowerShell equivalent:

```powershell
Remove-Item public\hot -ErrorAction SilentlyContinue
npm run build
php artisan optimize:clear
```

- If you actually want hot reload, run `npm run dev` and keep that terminal open.
- This project now includes a runtime safeguard that removes stale `public/hot` automatically when its Vite host is unreachable.

### Database Connection Issues
- Verify PostgreSQL is running
- Check `DB_*` settings in `.env`
- Run `php artisan migrate --seed` to initialize schema

### Email Not Sending
- Check `MAIL_MAILER` setting (use `log` for development)
- For Gmail: use app-specific password, enable "Less secure apps"
- For Mailtrap: configure in `.env` with provider credentials

### Queue Issues
- Verify `QUEUE_CONNECTION` setting
- Check Laravel queue is running: `php artisan queue:work`
- Monitor failed jobs: `php artisan queue:failed`

### File Upload Issues
- Verify `FILESYSTEM_DISK` is set correctly
- Check storage directory permissions: `chmod -R 775 storage/`
- Test with `php artisan storage:link` for public file access

## Support & Documentation

- [Laravel Framework](https://laravel.com/docs)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Blade Templates](https://laravel.com/docs/blade)
- [Vite Build Tool](https://vitejs.dev/)
- [Tailwind CSS](https://tailwindcss.com/docs)
