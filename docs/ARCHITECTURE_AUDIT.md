# Architecture and Process Audit

Audit date: 2026-08-26.

## Findings

| Area | Finding | Risk | Recommended action |
| --- | --- | --- | --- |
| Queue | `QUEUE_CONNECTION=database`; the worker is now defined, but live health and shared secrets are unverified | High | Verify the dedicated worker service and its runtime configuration |
| Email | Booking, provider, application, verification, and password-reset emails are queued; worker health remains unverified | High | Verify the production worker and failed-job monitoring |
| Payments | PayMongo checkout creation must run during the booking request to return the checkout URL; the service now bounds timeouts and retries provider failures | Medium | Monitor provider latency and failed checkout attempts |
| File storage | Upload paths are now configurable as private/public S3-compatible disks; production bucket configuration and migration remain unverified | High | Configure the bucket and migrate any existing local files; see `OBJECT_STORAGE.md` |
| Sessions/cache | Render uses file drivers, which are local to a container instance | Medium | Use shared Redis/database-backed sessions and cache before horizontal scaling |
| Routing | Browser views call the public OSRM router directly | Medium | Add a service boundary or managed routing provider if reliability/privacy matters |
| Deployment | The image must use the runtime `APP_KEY`; generating one during build would invalidate encrypted state | High | Supply a stable production secret through deployment configuration |

## Process boundaries

- Synchronous request work: validation, authorization, conflict checks, core database writes, and the user-facing response.
- Asynchronous work: email delivery, large exports, video/provider retries, and slow external calls.
- Durable data: bookings, payments, status history, proofs, messages, locations, and payouts require persistent database/object storage.

## Priority order

1. Production `APP_KEY` is now supplied at runtime; confirm the deployment secret is stable.
2. Verify the Render queue worker before relying on queued notifications. The worker configuration is present; deployment verification remains.
3. Keep all email delivery behind queued jobs and monitor failed jobs.
4. Configure the production S3-compatible bucket and migrate existing proof/application files.
5. Replace local file sessions/cache before adding multiple web instances.
6. Decide whether the public OSRM dependency is acceptable.

This audit identifies risks only. No deployment secrets, production records, or infrastructure settings were changed.
