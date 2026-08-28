# API Rate-Limit Audit

Audited route middleware and controller behavior on 2026-08-26.

## Current limits

| Surface | Current protection | Assessment |
| --- | --- | --- |
| Web registration, login, password reset | 6–10 requests/minute | Good baseline |
| Web booking mutations | Mostly 5–10 requests/minute | Good; booking creation should also be limited |
| Web location updates | 30 requests/minute | Appropriate with server-side coalescing |
| Web location reads | 60 requests/user/minute | Covers normal 10-second polling with abuse headroom |
| Admin/staff/provider mutations | Mostly 10–30 requests/minute | Good baseline |
| Mobile login and password flows | 6–10 requests/minute, plus controller login lockout | Good baseline |
| Authenticated mobile API | 60/minute baseline; booking creation 10/minute; staff start/complete 20/minute | Protected; operation-specific limits cover costly writes |
| IoT device endpoints | 120 requests/device/minute plus 300 requests/IP/minute when a device header exists | Limits both normal devices and rotated fake identities |
| IoT requests without credentials | 30 requests/IP/minute before controller authentication | Missing-credential spam is blocked early |
| PayMongo webhook | No explicit rate limit | Do not throttle blindly; rely on signature validation and idempotency |
| Report/analytics exports | 5/minute per admin | Protected against repeated expensive generation |

## Required changes

1. Add authenticated mobile limits by operation, keyed by user/token rather than only IP:
   - reads: 60/minute;
   - price calculation and notifications: 60/minute;
   - booking creation: 10/minute;
   - staff start/complete: 20/minute.
2. Location reads are now limited to 60/minute per authenticated user. The existing 30/minute write limit remains active.
3. Protect report and analytics exports at 5/minute per admin. These are expensive and should not be freely repeatable.
4. Missing-credential and invalid-header IoT requests are now rate-limited before authentication.
5. Mobile token `last_used_at` is now updated only when the stored value is older than five minutes.
6. PayMongo paid webhooks now no-op when the matched bookings already contain the same provider reference. Keep validating provider event/reference idempotency before adding any throttle.

## Recommended implementation order

1. Authenticated mobile mutation limits.
2. Export limits.
3. Location-read limit.
4. Token `last_used_at` write coalescing.
5. Webhook idempotency verification.
