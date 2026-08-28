# Database Scalability Audit

Audit date: 2026-08-27.

## What was missing

The database already had basic foreign-key and single-column indexes, but several frequently executed queries filtered on multiple columns together. Single-column indexes force the database to scan more candidate rows as bookings, attendance logs, notifications, and device requests grow.

Migration `2026_08_26_000003_add_scalability_indexes` adds only composite indexes that match current application queries:

| Query workload | Index |
| --- | --- |
| Check whether a staff member is busy for a schedule | `bookings(staff_id, status, scheduled_date, scheduled_time)` |
| Check whether a client already has a conflicting schedule | `bookings(user_id, status, scheduled_date, scheduled_time)` |
| Check all bookings competing for a slot | `bookings(status, scheduled_date, scheduled_time)` |
| Escalate old pending bookings | `bookings(status, created_at)` |
| Join catalog-backed bookings to services | `bookings(service_id)` |
| Reject duplicate punches from one device | `attendance_logs(user_id, device_id, logged_at)` |
| Load attendance by punch type and time range | `attendance_logs(punch_type, logged_at)` |
| Load location history for one booking | `booking_locations(booking_id, created_at)` |
| Load newest activity logs globally | `booking_activity_logs(created_at)` |
| Load a user's unread notifications in newest order | `notifications(user_id, read_at, created_at)` |
| Find an enrollment request for a fingerprint slot | `device_enrollment_requests(template_id, status)` |

The migration checks table, column, and index existence, so it is safe against the existing schema differences documented in the relationship audit. It does not add indexes to free-text search fields; ordinary B-tree indexes do not make `%search%` queries fast. If that search becomes a bottleneck, use PostgreSQL trigram indexes or a dedicated search service based on measured query latency.

## Deliberately not changed

- No indexes were added to every foreign key automatically. Each one has write and storage cost.
- No database partitioning was added. The current dataset is too small, and partitioning would add operational complexity without evidence of benefit.
- No cache was added for correctness-sensitive booking availability checks. Those checks must read current database state.
- Analytics still performs aggregation in SQL, but it should be moved to rollup tables only after production query timings show that aggregation is a bottleneck.

## Verification

- Fresh SQLite migrations and application tests pass with the new indexes.
- The local PostgreSQL database was inspected for existing indexes; the migration has not been applied to it yet because the previous pending migrations include an archive/table-consolidation change that requires a verified backup first.
