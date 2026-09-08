# Queue Worker Verification Report

## Result

The application explicitly places OTP and booking notification jobs on the
`emails` queue. Source code cannot prove that a deployed worker is running or
that a real email was delivered; those checks require access to the deployed
environment and a controlled test account.

## Static checks

| Check | Result | Evidence |
| --- | --- | --- |
| Email queue is used | Pass | Notifications and booking email jobs select the `emails` queue |
| Dedicated worker command is documented | Pass | `php artisan queue:work database --queue=emails,default --tries=3 --timeout=120` |
| Shared application key is required | Pass | Web, worker, and scheduler must use the same `APP_KEY` |
| Shared database and storage are required | Pass | Jobs and uploaded files must be visible to the same environment |
| Worker is live | Not verified | Requires Laravel Cloud or process-supervisor status |
| Queued email is delivered | Not verified | Requires a controlled test and mailbox/queue inspection |

## Required deployment verification

1. Confirm the managed queue or self-managed worker is running.
2. Confirm the deployed queue connection is supported by the application or
   provided by the platform integration.
3. Compare `APP_KEY`, all `DB_*`, `MAIL_*`, payment, video, and storage settings
   across web, worker, and scheduler processes without exposing secret values.
4. Confirm the production mail transport is real. The `log` mailer only writes
   messages to logs.
5. Send verification, password-reset, and booking test emails.
6. Inspect the queue and `failed_jobs`; investigate every unexpected failure.
7. Run `php artisan cleanflow:verify --probe`.

The local `php artisan queue:failed` result is only a local sanity check. It does
not verify the production worker, credentials, provider policies, or delivery.
