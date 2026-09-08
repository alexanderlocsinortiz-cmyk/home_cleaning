# Laravel Cloud queue and OTP mail setup

This application sends password-reset OTPs, account-verification OTPs, and
booking notifications asynchronously on the `emails` queue. A web deployment
alone does not process those jobs.

## Repository readiness

- Laravel is constrained to `^12.63`, which supports Laravel Cloud managed
  queues.
- `aws/aws-sdk-php` is declared directly because Laravel Cloud requires it for
  managed queues.
- OTP notifications and booking email jobs use the `emails` queue.
- Local development can continue using `QUEUE_CONNECTION=database` and the
  `scripts/run-queue-worker.ps1` worker.

## Laravel Cloud staging

1. Open the `staging` environment.
2. On the infrastructure canvas, choose **Add compute → Managed queue**.
3. Name the queue exactly `emails`.
4. Choose **Standard**, Flex, and start with 256 or 512 MiB and one worker.
5. Save the resource and deploy the pending changes.
6. Do not manually override `QUEUE_CONNECTION=database` after attaching the
   managed queue. Cloud sets `QUEUE_CONNECTION=cloud` for the environment.
7. Open **Monitoring → Queues** after testing and confirm the jobs are
   processed and there are no failed jobs.

The first managed queue becomes the environment default, so jobs that do not
explicitly select a queue are also handled by it. This application explicitly
places its email jobs on `emails`.

If managed queues are not available on the current plan, use an app-cluster
background process instead:

```text
php artisan queue:work database --queue=emails,default --sleep=3 --tries=3 --timeout=120
```

That fallback shares resources with web traffic and is acceptable for staging,
but managed queues are the safer production choice.

## Mailtrap live SMTP

Mailtrap **Email Sandbox** is for capturing test mail inside Mailtrap. It is not
the setting to use when OTPs must arrive in a customer's Gmail or Outlook
inbox.

For live sending, verify a sending domain in Mailtrap, then open its
**Integrations → Transactional Stream → SMTP** page and add these values to the
Laravel Cloud environment. Use the values shown by Mailtrap; never commit the
password or token.

```text
MAIL_MAILER=smtp
MAIL_HOST=live.smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=api
MAIL_PASSWORD=<Mailtrap SMTP/API token>
MAIL_SCHEME=null
MAIL_FROM_ADDRESS=<address on your verified domain>
MAIL_FROM_NAME=CleanFlow
```

Remove or correct any stale `MAIL_ENCRYPTION` line if Cloud shows one. The
application accepts it as a compatibility fallback, but `MAIL_SCHEME` is the
current setting. Port 587 is the recommended starting point.

## Acceptance test

After the queue and SMTP settings are deployed:

1. Register a new test client with an inbox you can access.
2. Confirm the verification OTP arrives within a few seconds.
3. Request a password reset for the same account.
4. Confirm the reset OTP arrives and successfully changes the password.
5. In Cloud, verify both jobs appear as completed in **Monitoring → Queues**.
6. If the email does not arrive, inspect the queue failure and application log
   before retrying. Do not purge the queue; purging permanently deletes waiting
   jobs.
