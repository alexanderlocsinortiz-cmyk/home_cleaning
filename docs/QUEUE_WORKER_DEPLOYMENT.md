# Queue Worker Deployment

This application sends OTP, booking, and other customer notifications through
the `emails` queue. A web process alone does not process queued jobs.

## Laravel Cloud

1. Attach a managed queue to the target environment and name it `emails`.
2. Configure the queue according to the current Laravel Cloud environment
   settings. Do not set `QUEUE_CONNECTION` to a driver that is not available in
   `config/queue.php` or supplied by the platform integration.
3. Use the same `APP_KEY`, database connection, mail settings, and storage
   settings on every process that can create or process jobs.
4. Confirm the queue has a running worker and inspect **Monitoring → Queues**.

The queue must use the same database and application environment as the web
service. Otherwise the web process may write jobs that no worker can see, or a
worker may process jobs against the wrong application data.

## Self-managed infrastructure

Run a dedicated worker process with:

```bash
php artisan queue:work database --queue=emails,default --sleep=3 --tries=3 --timeout=120
```

Keep the worker under a process supervisor, restart it after deployments, and
run `php artisan queue:failed` during incident checks. Do not run a permanent
worker inside the web request process.

## Acceptance checks

1. Register a controlled test client and confirm the verification email arrives.
2. Request a password reset and confirm the OTP arrives.
3. Create a controlled booking and confirm its notification is delivered.
4. Verify the jobs are completed and no unexpected records remain in
   `failed_jobs`.
5. Run `php artisan cleanflow:verify --probe` in the deployed environment.

Never use the local `log` mailer as evidence that an email was delivered.
