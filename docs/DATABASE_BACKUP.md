# Database Backup and Disaster Recovery

CleanFlow now supports two backup paths:

- **Download database backup** creates a temporary local backup for an administrator.
- **`php artisan database:backup-cloud`** runs the upload path without the browser and is suitable for a daily cron, Render Cron Job, or external scheduler.
- **Upload database to private cloud storage** creates the backup, uploads it to the configured private storage disk under `DATABASE_BACKUP_PREFIX`, confirms the object exists, removes the temporary local copy, and prunes older backup files beyond `DATABASE_BACKUP_RETENTION_COUNT`.

## Production configuration

Configure these values on the web service:

```dotenv
DATABASE_BACKUP_DISK=s3
DATABASE_BACKUP_PREFIX=database-backups
DATABASE_BACKUP_RETENTION_COUNT=30
```

The backup disk must point to the private bucket. With the R2 setup, `s3` uses `AWS_PRIVATE_BUCKET`, while `s3_public` must never be used for database backups. Enable provider-side encryption at rest and restrict the storage credentials to the private bucket/prefix. Do not put backup credentials or passwords in source control.

## Verification procedure

1. Configure the same private S3-compatible storage credentials used by the web service.
2. Run `php artisan storage:verify --probe` to verify basic object-storage access.
3. In Admin → Settings → Database Backup, use **Upload Backup to Cloud**.
4. Confirm the success message and verify the object exists under the private backup prefix.
5. Confirm an unauthenticated request cannot read the object.
6. Download a copy through the provider or CLI and restore it into an isolated staging database.
7. Verify users, bookings, services, payments, messages, and migrations in the restored database.
8. Record the restore duration and define the acceptable recovery point (RPO) and recovery time (RTO).

For automatic protection, schedule `php artisan database:backup-cloud` at least daily. A scheduler is not created automatically by this repository because it must be configured with the production database and private-storage secrets by the deployment operator.

The application tests prove that the upload path writes to the configured disk, confirms the object, and cleans up the temporary local file. They cannot prove production credentials, provider access policy, scheduled execution, or a successful restore of your production database.
