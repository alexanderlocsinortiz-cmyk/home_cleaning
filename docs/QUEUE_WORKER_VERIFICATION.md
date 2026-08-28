# Queue Worker Verification Report

Review date: 2026-08-27.

## Result

The repository contains the expected `cleanflow-worker` definition, but production verification cannot be completed from source code. Render dashboard access and a real test booking are required to prove that queued notifications are actually being processed.

## Latest verification attempt

- Local `php artisan queue:failed`: no failed jobs found.
- Render CLI/API credentials: unavailable in this workspace.
- Configured web URL (`https://cleanflow-app.onrender.com`): timed out during two read-only requests. This does not prove the worker is down, but it prevents an external deployment sanity check.
- Production worker status, secret equality, and queued-email processing: not verified.

## Static checks

| Check | Result | Evidence |
| --- | --- | --- |
| Dedicated worker exists | Pass | `render.yaml` defines `cleanflow-worker` |
| Queue command | Pass | `php artisan queue:work --tries=3 --timeout=120` |
| Database queue | Pass | Web and worker declare `QUEUE_CONNECTION=database` |
| Shared application key | Not verified | Worker uses `sync: false`; equality with the web secret must be checked in Render |
| Shared database connection | Not verified | Web and worker now declare the same dashboard-supplied PostgreSQL connection inputs; values must still match exactly in Render |
| Worker is live | Not verified | Requires Render service status |
| Queued email is processed | Not verified | Requires a test booking and inspection of `jobs`/`failed_jobs` |

The local `php artisan queue:failed` check reported no failed jobs. This is only a local sanity check: the local environment is not production and currently uses a synchronous queue, so it does not exercise `cleanflow-worker`.

## Configuration risks

1. Mail delivery is now explicitly dashboard-supplied for both services. Until matching real-provider `MAIL_*` values are configured, queued email delivery is not ready; the application default of `log` must not be treated as delivery.
2. The worker’s `APP_KEY`, database values, mail values, payment settings, and storage settings must be compared with the web service manually. A worker can show as running while still using the wrong database or key.
3. The worker and web service must share the same database queue and storage configuration. Otherwise jobs may be created in one environment and processed against another.

## Required Render verification

1. Open the `cleanflow-worker` service and confirm status is `Live`.
2. Confirm the start command exactly matches the declared queue command.
3. Compare `APP_KEY`, all `DB_*`, `QUEUE_*`, `MAIL_*`, `PAYMONGO_*`, `DAILY_*`, and storage variables with `cleanflow-app` without exposing their values in tickets or screenshots.
4. Confirm the production mail provider is intentional. If `log` is intentional for a pilot, label email as unavailable; otherwise configure SMTP/API delivery.
5. Create one controlled test booking and confirm the queued notification is delivered.
6. Run `php artisan queue:failed` and investigate every unexpected failed job.
7. Confirm the queue worker can read the same object-storage paths when queued jobs access uploaded files.

## Decision

Static configuration is ready for dashboard verification. “Verify the Render worker is running with shared production secrets” remains open until all acceptance checks pass in the production environment.

## Source references

- `render.yaml` — web and worker definitions.
- `docs/QUEUE_WORKER_DEPLOYMENT.md` — deployment procedure and acceptance checks.
- `docs/OBJECT_STORAGE.md` — shared storage requirement.
- `docs/ARCHITECTURE_AUDIT.md` — queue and email risk assessment.
