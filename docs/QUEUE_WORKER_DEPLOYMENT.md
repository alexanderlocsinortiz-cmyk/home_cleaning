# Queue Worker Deployment

The Render blueprint now defines a separate `cleanflow-worker` service running:

```bash
php artisan queue:work --tries=3 --timeout=120
```

Before deploying the worker, configure the same runtime secrets and database connection values used by `cleanflow-app`. The worker manifest declares the required values as dashboard-supplied inputs:

- `APP_KEY` — must be exactly the same value as the web service.
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SCHEMA`, and `DB_SSLMODE`.
- `MAIL_*` values for the real mail provider.
- Any `PAYMONGO_*`, `DAILY_*`, or other application settings needed by queued jobs.

The worker uses the `starter` plan because Render does not provide the `free` plan for background workers. The web and worker services must also share the same S3 bucket settings when queued jobs need to access uploaded files.

The blueprint leaves `MAIL_*` values dashboard-supplied for both services. Configure the same real mail provider, sender address, and `APP_URL` in the web and worker environments. Do not use the Laravel `log` mailer as evidence that a customer email was delivered.

For an existing Render service, `sync: false` does not re-prompt for values during a Blueprint sync. Open the `cleanflow-worker` Environment page and manually confirm that `APP_KEY`, all `DB_*` values, and the required `MAIL_*` values match the web service.

Do not generate a separate `APP_KEY` for the worker. A different key would make encrypted sessions and data incompatible between services.

After deployment, verify the worker is running and inspect failed jobs with:

```bash
php artisan queue:failed
```

Acceptance checks:

1. The worker status is `Live` in Render.
2. Its start command is `php artisan queue:work --tries=3 --timeout=120`.
3. `APP_KEY` and the database connection values match `cleanflow-app` exactly.
4. A test booking notification is processed and no unexpected row remains in `failed_jobs`.
