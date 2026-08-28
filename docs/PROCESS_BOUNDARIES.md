# Process and Action Boundaries

Review date: 2026-08-27.

## Decision

CleanFlow’s current process split is appropriate for the workflow. Core booking state changes remain synchronous and atomic; email delivery is asynchronous; production deployment supplies a separate queue worker. PayMongo checkout creation and file/download responses remain synchronous because the caller needs an immediate URL or response body.

Moving everything into a queue would be a mistake: a booking could appear accepted before its conflict checks or database transaction completes, and a digital-payment booking could not redirect to a checkout URL that does not exist yet.

## Boundary map

| Work | Current boundary | Decision | Reason |
| --- | --- | --- | --- |
| Request validation, authorization, profile checks | Web/API request | Keep synchronous | The caller needs an immediate, authoritative result |
| Price calculation, risk detection, schedule locks, booking writes | Web/API request and database transaction | Keep synchronous and atomic | Prevents double-booking and avoids accepting incomplete records |
| Booking, assignment, status, application, verification, and payout emails | `emails` queue | Run asynchronously | Email-provider latency must not hold the user request open |
| PayMongo checkout-session creation | Web/API request | Keep synchronous with timeout/retry bounds | The response must redirect the customer to the returned checkout URL |
| PayMongo webhook processing | Web/API request | Keep synchronous and idempotent | The provider needs an immediate acknowledgment, and payment state must commit atomically |
| Proof and identity-document uploads | Web/API request to configured storage | Keep upload metadata atomic; use durable storage | The caller needs upload success, while files must survive container restarts |
| Admin CSV/PDF/Excel exports | Web request with per-admin rate limit | Keep for current report sizes; queue when measurements justify it | The caller expects a download response; current limits protect against repeated expensive work |
| Storage migration | Artisan deployment command | Run as an explicit operational command | It is a controlled cutover, not customer request work |
| Analytics and dashboard aggregates | Database-side queries in web request | Keep query-side; profile and add rollups only if needed | Aggregates avoid loading every booking into application memory |
| Live location reads/writes | Rate-limited web/API requests | Keep synchronous and coalesce redundant writes | Users need current location and updates are small, frequent events |
| Daily video room/token calls | Web/API request | Keep bounded and monitor | The user needs a room/token before joining; provider failures must be surfaced |
| Browser map routing | Client-side public OSRM request | No process change yet | Replace with a managed/owned boundary only if reliability or privacy requirements demand it |

## Separation already implemented

- Queued email jobs use the `emails` queue.
- A dedicated Render `cleanflow-worker` service runs the queue worker.
- Analytics charts use database-side aggregates.
- Storage migration is a separate, non-destructive Artisan command.
- High-frequency location writes are coalesced when the point has barely changed.
- Expensive admin exports have per-admin rate limits.
- Payment webhook handling is idempotent rather than duplicated in a background retry path.

## Triggers for further refactoring

Queue or split these actions only when production measurements show they need it:

1. Report exports exceed the web request timeout or memory budget.
2. Analytics queries exceed an agreed dashboard latency target.
3. PayMongo checkout latency causes measurable request saturation; then introduce a pending-payment workflow that does not promise an immediate checkout redirect.
4. Routing reliability, provider terms, or location privacy make the public OSRM request unacceptable.
5. Upload processing needs image/video transformation beyond storage and metadata persistence.

## Operational risks still open

The process design is not the same as production verification. The queue worker, shared secrets, durable object storage, and external-provider health still require deployment checks described in `docs/QUEUE_WORKER_VERIFICATION.md` and `docs/OBJECT_STORAGE_VERIFICATION.md`.

## Source references

- `docs/ARCHITECTURE_AUDIT.md` — architecture findings and process priority.
- `docs/DATA_FLOW.md` — booking flow and current asynchronous work.
- `app/Http/Controllers/BookingController.php` — atomic booking creation and digital checkout redirect.
- `app/Services/PaymongoCheckoutService.php` — bounded external payment requests.
- `docs/API_LIMITS.md` — request and export limits.

