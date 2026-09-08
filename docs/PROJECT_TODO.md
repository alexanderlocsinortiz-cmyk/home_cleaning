# CleanFlow Project TODO

## Priority 1 — Data and system review

- [x] Review and organize data from the past 10 days.
- [x] Resolve booking #18 after admin review. Booking was cancelled with payment left pending because no cash receipt evidence exists.
- [x] Map the current data flow.
- [x] Investigate backend performance and resolve overload issues.
- [x] Audit API limits and document required changes.
- [x] Add authenticated mobile API rate limits.
- [x] Add rate limits to expensive admin exports.
- [x] Add IP rate limiting for IoT requests without credentials.
- [x] Add IP rate limiting for IoT requests with rotating or invalid headers.
- [x] Add rate limiting for live-location read requests.
- [x] Reduce mobile-token `last_used_at` database writes.
- [x] Make repeated PayMongo paid webhooks idempotent.
- [x] Document the current system, software, and hardware.
- [x] Replace all-booking hydration in admin dashboard charts with database aggregates.
- [x] Reduce analytics-page booking hydration to fields used by its calculations.
- [x] Refactor the analytics page to use database-side aggregates.

## Priority 2 — Database and architecture

- [x] Identify architecture and process problems.
- [x] Fix production `APP_KEY` handling and deployment secret management.
- [x] Add a production queue worker configuration.
- [x] Align deployment database environment declarations and correct the cache variable name. Live values still require dashboard verification.
- [x] Audit queue-worker verification requirements. See `docs/QUEUE_WORKER_VERIFICATION.md`; dashboard and production test verification remain open.
- [x] Remove the deployment mailer default that could silently log queued emails. Delivery verification remains open.
- [ ] Verify the queue worker is running with shared production secrets. Static configuration exists, but live status and secret equality are unverified.
- [x] Move synchronous email delivery behind queued jobs.
- [x] Queue booking lifecycle emails (submission, status, and assignment).
- [x] Audit durable object-storage readiness. See `docs/OBJECT_STORAGE_VERIFICATION.md`; production bucket configuration and migration remain open.
- [x] Add a runtime object-storage probe. Run `php artisan storage:verify --probe` after production credentials are configured.
- [x] Add explicit S3-compatible storage variables to the deployment configuration. Production values still require deployment configuration.
- [x] Prepare Cloudflare R2 two-bucket configuration and setup instructions. Cloudflare bucket creation, deployment secrets, and live probes remain open.
- [x] Add separate configurable private/public object-storage prefixes so bucket policies can isolate sensitive documents from public media. Production bucket policy and cutover remain open.
- [x] Add a tested private cloud database-backup upload and scheduler-friendly command with retention pruning and temporary-file cleanup. Production bucket configuration and restore-drill evidence remain open.
- [x] Add an automated private/public disk visibility contract test. Real bucket privacy testing remains open.
- [x] Add a guest-denial test for private application downloads. Real object-storage access testing remains open.
- [x] Verify sensitive application uploads honor the configured private disk. Real bucket access testing remains open.
- [x] Add runtime enforcement, encrypted verification numbers, expiring applicant tracking, retention purge, and private-storage checks for uploaded files. Production bucket access policy, environment variables, migration, and live privacy tests remain required.
- [ ] Configure and test off-site database disaster recovery. A cloud-upload workflow exists, but production backup storage, retention, restore testing, and recovery targets are still required.
- [x] Separate processes and actions where necessary. See `docs/PROCESS_BOUNDARIES.md`; further queueing should be driven by production measurements.
- [x] Remove duplicate or redundant data.
- [x] Review parent/child data relationships and data types. See `docs/RELATIONSHIP_AUDIT.md`.
- [x] Separate unrelated data structures. Legacy staff records are archived and attendance now uses `users.id`.
- [x] Improve the database for scalability. See `docs/SCALABILITY_AUDIT.md`.
- [x] Create a comparison table for the available options or types. See `docs/SERVICE_COMPARISON.md`.

## Priority 3 — Services, pricing, and evidence

- [x] Synchronize the database service and add-on catalogs with the canonical pricing migration. Business approval is still required before production.
- [x] Define the implementation baseline for service rates and pricing. See `docs/PRICING_POLICY.md`.
- [x] Prepare application-based evidence for each service. See `docs/SERVICE_EVIDENCE.md`; real-world photos and completion records are still required.
- [x] Attach application evidence and generated catalog data. See `docs/SERVICE_EVIDENCE.md` and `docs/SERVICE_EVIDENCE_DATA.json`; real-world attachments remain outstanding.
- [x] Include offline communication options. See `docs/OFFLINE_COMMUNICATION.md`; a real production phone number still must be configured.
- [x] Audit service scope completeness and inclusivity. See `docs/SERVICE_SCOPE_AUDIT.md`; owner sign-off, scope sheets, and real-world evidence remain open.
- [x] Prepare a service-scope approval matrix. See `docs/SERVICE_SCOPE_APPROVAL.md`; all eight services remain pending owner approval.
- [x] Draft scope sheets and provisional planning assumptions for all eight services. See `docs/SERVICE_SCOPE_SHEETS.md`; the approval matrix records each sheet as Provisional draft, while owner approval and real-world completion evidence remain open.
- [x] Add measurable, manually editable service scope controls for area, cleaner count, and scope status. Approved area limits block oversized bookings; provisional limits route them for manual review.
- [x] Require service approval integrity. Admin approval now requires measurable limits and all eight scope-definition fields, while incomplete legacy approvals fail closed as provisional at runtime.
- [x] Store complete editable scope definitions for all eight services, including included areas/tasks, exclusions, condition limits, supplies, access/safety, extra-work rules, and acceptance criteria. Owner approval and real-world completion evidence remain open.
- [ ] Confirm that each service is complete and inclusive. Editable baseline definitions now exist for all eight packages, but owner approval, capacity confirmation, and real-world evidence are still required before this is a complete/inclusive promise.
- [x] Audit pricing calculations and validation requirements. See `docs/PRICING_VALIDATION.md`; commercial approval remains open.
- [x] Run a pricing revenue and wage sanity check. See `docs/PRICING_APPROVAL_WORKSHEET.md`; this identified General/Regular Cleaning as high risk and does not replace owner approval.
- [x] Make the pricing approval worksheet manually editable with a General/Regular first-entry form. Actual cost inputs and owner approval remain open.
- [x] Add a structured form for collecting three comparable local pricing quotes. Comparable evidence and owner approval remain open.
- [x] Align the Office Deep customer-facing rate with the backend at PHP 60/sqm. Condition-based re-quoting and commercial approval remain open.
- [ ] Validate that pricing is legitimate and reasonable. Cost-model, competitor, capacity, and margin evidence are still required.
- [x] Show the budget clearly. See `docs/BUDGET_TRANSPARENCY.md`; commercial price validation remains a separate open item.
- [x] Separate prices from rates. See `docs/PRICING_POLICY.md`.

## Priority 4 — Positioning and proposal

- [x] Identify the service’s advantages over competitors. See `docs/COMPETITIVE_POSITIONING.md`; superiority claims still require market and operating evidence.
- [x] Create a market comparison table. See `docs/MARKET_COMPARISON.md`; local primary research remains required.
- [x] Complete the desk research and proposal comparison. See `docs/RESEARCH_PROPOSAL_COMPARISON.md`; field interviews, competitor quotes, and pilot results remain required.
- [x] Review the final plan for clarity and completeness. See `docs/FINAL_PLAN_REVIEW.md`; the plan supports controlled pilot preparation but not unrestricted public launch.

## Priority 5 - Advisor recommendations

These are advisor-requested product improvements. Implement and test each item separately.

- [x] Add a homepage advertising slideshow. The slideshow is placed below the original hero, uses approved local service imagery, supports controls/dots/automatic rotation, pauses for reduced motion, and keeps the original hero text unchanged.
- [x] Make the ERD background white. The printable ERD already uses a white background; table names, keys, and relationships remain unchanged.
- [x] Strengthen password security. Password creation, staff setup, profile changes, admin changes, provider activation, and web/mobile resets require at least 8 characters with letters and numbers; symbols such as `@` are allowed. Existing hashes remain intact and the policy applies when a password is next set or changed; login rate limiting remains enabled.
- [x] Add service-related images. Every catalog service has a real raster fallback image; custom images use public storage, private documents remain separate, service images include generated alt text and responsive sizing, and uploads are restricted to JPEG/PNG/WebP images up to 5 MB.
- [x] Improve staff pin-location tracking similar to delivery apps. Staff can open a saved client pin, use current GPS, view a driving route with a direct-line fallback, and share live location only for their own active bookings. Client/admin access is authorization-checked; location history remains admin-only.
- [x] Calculate and display required cleaners by service area. CleanFlow uses documented service-specific capacities, rounds up with ceiling division, enforces a minimum of one cleaner, flags requirements above 20 for manual review, snapshots the requirement on bookings, and documents the ISSA/APPA basis in docs/STAFFING_BASIS.md. The current schema still assigns one primary staff member, so multi-cleaner jobs require admin review.
- [x] Add client cash-payment receipt upload and admin confirmation. Proofs are stored on the private disk, ownership/type/size are validated, admins are notified, approve/reject notes are supported, payment is marked paid only after approval, reviewer/time are recorded, duplicate pending submissions are blocked, and cross-client access is denied.
