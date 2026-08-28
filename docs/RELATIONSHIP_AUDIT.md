# Parent/Child Relationship Audit

Audit date: 2026-08-26.

## Result

The booking graph has no orphaned child records in the inspected database. The application and migrations consistently use `bookings` as the parent for service proofs, activity logs, messages, locations, ratings, and provider payout transactions. Those child rows use foreign keys with cascade deletion because they have no meaning without the booking.

The following checks returned zero orphaned rows:

- booking users
- non-null booking services, staff, preferred staff, and marketplace providers
- service proofs, messages, locations, ratings, and payout transactions without bookings
- cleaner application documents without applications
- notifications without users or referenced bookings
- cleaner applications with missing linked users

There are 10 legacy bookings with no `service_id`. They all have a valid historical `service_type`, so this is an intentional compatibility case rather than an orphan. New catalog-backed bookings should populate both fields.

## Integrity fixes

The code models `Booking::rating()` as `hasOne`, and `User::cleanerApplication()` as `hasOne`, but the database previously allowed duplicates. Migration `2026_08_26_000001_add_relationship_integrity_constraints` adds:

- a unique `ratings.booking_id` index, preventing duplicate ratings during concurrent submissions;
- a unique nullable `cleaner_applications.user_id` index, preserving the one-application-per-account relationship while allowing anonymous applications.

The migration fails with the duplicate IDs if existing data violates either rule, so bad data cannot be hidden by a partial constraint rollout.

## Data type and schema findings

- Foreign-key IDs use `bigint` consistently across the booking graph.
- Money fields use fixed-point decimal values rather than floating-point storage.
- Coordinates use decimal precision suitable for latitude and longitude.
- Rating `stars` remains an integer with request validation from 1 through 5. A database check constraint is not added because the project supports SQLite for tests and PostgreSQL/MySQL in deployments; the application validation remains the portable invariant.
- The live database contained an older `staff` table shape without `user_id` and an older `attendance_logs` shape without `staff_id`, although the current migration files described those columns. The active application uses `users` with `role = staff`, not the legacy `Staff` model.
- The consolidation migration archives old `staff` rows in `legacy_staff_records`, migrates any available staff identity into `attendance_logs.user_id`, and removes the obsolete staff foreign key/table. It also removes duplicate attendance timestamps and fingerprint ownership from the log; attendance now has one canonical owner and timestamp: `users.id` and `logged_at`. The archive is retained so the migration does not silently destroy legacy data.

## Deletion policy risk

Admin controllers already block deleting users with booking history. Keep that protection: cascading deletion from a user can otherwise erase operational history, ratings, messages, and financial records. A future retention policy should prefer anonymization or deactivation over deleting users and their history.
