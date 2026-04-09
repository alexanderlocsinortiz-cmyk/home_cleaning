# Home Cleaning Service

Home Cleaning Service is a Laravel 12 home-cleaning service platform for Valencia City. It includes separate client, staff, and admin experiences for booking services, assigning cleaners, tracking active jobs, and reviewing operations.

## Highlights

- Public landing page and service-area map.
- Client booking flow with profile management, booking history, and booking details.
- Admin dashboard for customers, staff, services, bookings, reports, and service coverage.
- Staff dashboard for assigned jobs, schedule, notifications, profile updates, and performance metrics.
- Live booking location tracking for active jobs, including admin history views.
- Email notifications for booking submission, confirmation, in-progress updates, completion, and staff assignment.

## Tech Stack

- PHP 8.2+
- Laravel 12
- Blade templates
- Vite 7
- Tailwind CSS 4
- SQLite by default in `.env.example`

## Color Palette

The active product UI uses an emerald-led palette with slate neutrals.

### Core Brand Colors

| Token | Hex | Usage |
| --- | --- | --- |
| Primary | `#1D9E75` | Main CTA buttons, badges, links, key highlights |
| Primary Dark | `#0F6E56` | Hover states, darker gradient stop, strong emphasis |
| Secondary | `#E1F5EE` | Soft fills, pills, supporting backgrounds |

### Shared Neutrals

| Token | Hex | Usage |
| --- | --- | --- |
| Heading Slate | `#1E293B` | Main headings and strong text |
| Body Slate | `#64748B` | Secondary text and helper copy |
| Muted Slate | `#94A3B8` | Labels, meta text, low-emphasis UI |
| Surface 50 | `#F8FAFC` | Table headers, light panels, subtle fills |
| Surface 100 | `#F1F5F9` | Page backgrounds and card borders |
| Border 200 | `#E2E8F0` | Inputs, dividers, standard borders |

### Accent And Status Colors

| Token | Hex | Usage |
| --- | --- | --- |
| Cyan Accent | `#0891B2` | Hero and dashboard gradient accent |
| Cyan Bright | `#06B6D4` | Staff banner gradient accent |
| Staff Blue | `#185FA5` | Staff filters, badges, schedule indicators |
| Staff Blue Soft | `#E6F1FB` | Staff chips and light blue surfaces |
| Success | `#16A34A` | Completed, success, positive metrics |
| Warning | `#D97706` | Pending, caution, warning badges |
| Warning Bright | `#F59E0B` | Metric highlights and attention states |
| Error | `#DC2626` | Errors, destructive actions, cancelled status |
| In Progress | `#9333EA` | In-progress booking state |

### Common Gradients

| Area | Colors |
| --- | --- |
| Home hero | `#0F6E56 -> #16946D -> #0891B2` |
| Login and register | `#0F6E56 -> #1D9E75 -> #0891B2` |
| Admin banner | `#0F6E56 -> #1D9E75 -> #0891B2` |
| Staff banner | `#0F6E56 -> #1D9E75 -> #06B6D4` |

Note: `resources/views/welcome.blade.php` still contains orange illustration colors from the default Laravel welcome page, but the active `/` route uses `resources/views/home/index.blade.php`, so those orange tones are not part of the product palette.

## Default Local Setup

The checked-in `.env.example` is configured for local development with:

- `DB_CONNECTION=sqlite`
- `QUEUE_CONNECTION=database`
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- `MAIL_MAILER=log`

That means a fresh local setup works with SQLite out of the box, and emails are written to the Laravel log until you switch to a real mail transport.

## Quick Start

1. Install the app dependencies and build assets:

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

## Manual Setup

If you prefer to run each step yourself:

```bash
composer install
```

Copy `.env.example` to `.env`, then generate the app key:

```bash
php artisan key:generate
```

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

The seeders also create sample services and additional staff records for testing the admin views.

## Main Application Areas

### Public

- `/` - landing page
- `/map` - service-area map
- `/register` - client registration
- `/login` - login page

### Client

- `/client/dashboard` - client dashboard
- `/bookings` - booking list
- `/bookings/create` - create a booking
- `/bookings/{id}` - booking details
- `/profile` and `/profile/edit` - shared authenticated profile pages

### Staff

- `/staff/dashboard` - staff overview
- `/staff/bookings` - assigned jobs
- `/staff/schedule` - booking calendar view
- `/staff/performance` - ratings and earnings summary
- `/staff/notifications` - assignment and status notifications

### Admin

- `/admin/dashboard` - operations overview
- `/admin/customers` - client management
- `/admin/bookings` - booking management and staff assignment
- `/admin/services` - service catalog management
- `/admin/staff` - staff management
- `/admin/reports` - booking and revenue reporting
- `/admin/service-areas` - mapped coverage areas

## Seeded Services

The default database seeder inserts these active services:

- Basic Clean
- Deep Clean
- Move-in/Move-out Clean

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
- Live location updates are stored in `booking_locations` and exposed through authenticated booking tracking endpoints.
- If you switch away from SQLite, update the `DB_*` values in `.env` before running migrations.
- To send real emails, replace the default `MAIL_MAILER=log` settings with your SMTP or provider configuration.
