# CleanFlow System Inventory

Inventory date: 2026-08-26.

## Application stack

| Layer | Current implementation |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12 |
| Web UI | Blade templates, Tailwind CSS 4, Vite 7 |
| Browser libraries | Axios, Chart.js, Leaflet, Font Awesome |
| Database | PostgreSQL by default; migrations and tests also support SQLite |
| Sessions/cache/queues | Database drivers in `.env.example`; production requires shared session/cache storage and a running queue worker |
| Authentication | Laravel session authentication for web; bearer `MobileApiToken` authentication for mobile |
| Files | Separate private/public disks for proof media, documents, payout evidence, and public catalog media |
| Testing | PHPUnit 11 feature/unit tests; Laravel Pint for formatting |

## Product surfaces

- Public landing page and service-area map.
- Client web portal for profiles, bookings, payments, subscriptions, tracking, messaging, ratings, and proof review.
- Staff web portal for assigned jobs, status changes, proof uploads, attendance consent, schedules, and location sharing.
- Admin portal for customers, staff, services, bookings, reviews, attendance, analytics, reports, logs, and payouts.
- Provider portal for marketplace availability, assignment responses, bookings, and payouts.
- Mobile API under `/api/mobile` for client and staff workflows.
- Installable PWA with service worker and offline fallback.
- Android WebView wrapper in `android-webview/` targeting Android API 36 with minimum API 23.

## External services

| Service | Purpose | Configuration source |
| --- | --- | --- |
| PayMongo | Digital checkout and payment webhooks | `PAYMONGO_*` environment variables |
| Daily.co | Live booking video rooms and meeting tokens | `DAILY_*` environment variables |
| Google Maps | Address/location map and geocoding features when configured | `GOOGLE_MAPS_API_KEY` |
| OSRM public router | Browser-side route estimates on booking tracking maps | hard-coded public routing URL in views |
| SMTP/mail provider | Production email delivery | `MAIL_*` environment variables |
| Deployment platform | Laravel Cloud or self-managed container infrastructure | `Dockerfile` and deployment environment settings |

## Attendance hardware

- ESP32 development board with Wi-Fi.
- AS608 fingerprint sensor over UART.
- OLED display over I2C.
- Wiring documented in `docs/esp32-attendance-setup.md`:
  - AS608 UART: GPIO16/GPIO17
  - OLED I2C: GPIO21/GPIO22
- The ESP32 sends signed requests to the Laravel IoT API for punches, heartbeat, and enrollment.
- Laravel stores devices, tokens, enrollment requests, fingerprint template IDs, and attendance logs.

## Deployment/runtime facts

- The Docker image builds frontend assets and runs the Laravel application through the checked-in container configuration.
- The default local queue driver is `database`; email jobs require a running queue worker to be processed asynchronously.
- The web service needs a separate managed or self-managed queue worker; live worker health and shared-secret configuration still require deployment verification.
- Production mail is not configured by source code alone; a real `MAIL_*` transport and delivery test are required.
- PostgreSQL is the intended production database. The `.env` file must never be committed or copied into documentation because it may contain secrets.

## Hard risks and next checks

1. Verify the dedicated production queue worker and its shared secrets; otherwise queued mail/jobs may remain in the database.
2. Configure a real mail transport before relying on booking and payment notifications.
3. Replace the public OSRM dependency with a managed/owned routing service if route availability or privacy matters.
4. Configure and verify the S3-compatible storage bucket for uploaded proofs and documents; local container storage is not durable.
5. Keep PayMongo and Daily.co credentials, webhook secrets, and device tokens in environment secrets only.
