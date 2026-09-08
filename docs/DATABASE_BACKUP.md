# Database Backup and Disaster Recovery

CleanFlow now supports two backup paths:

- **Download database backup** creates a temporary local backup for an administrator.
- **`php artisan database:backup-cloud`** runs the upload path without the browser and is suitable for the Laravel Cloud scheduler, a self-managed cron job, or another external scheduler.
- **Upload database to private cloud storage** creates the backup, uploads it to the configured private storage disk under `DATABASE_BACKUP_PREFIX`, confirms the object exists, removes the temporary local copy, and prunes older backup files beyond `DATABASE_BACKUP_RETENTION_COUNT`.

The production Docker image includes PostgreSQL client tools, so PostgreSQL backups use the standard `pg_dump` custom format. The application-level SQL generator remains an emergency fallback when the dump binary is unavailable; it should not replace a restore drill.

## Production configuration

Configure these values on the web service:

```dotenv
DATABASE_BACKUP_DISK=s3
DATABASE_BACKUP_PREFIX=database-backups
DATABASE_BACKUP_RETENTION_COUNT=30
# Optional absolute paths when dump tools are not available on PATH.
# DB_BACKUP_PG_DUMP_PATH=/usr/bin/pg_dump
# DB_BACKUP_MYSQLDUMP_PATH=/usr/bin/mysqldump
```

The backup disk must point to the primary private disk (`s3` by default), never `s3_public`. Keep database backups separate from public media and do not put backup credentials or passwords in source control.

PostgreSQL uses `pg_dump` custom format when the executable is available. The SQL exporter is an emergency fallback only when `pg_dump` cannot be found. If the executable runs but fails, the backup fails instead of silently producing a weaker artifact.

## Verification procedure

1. Configure the same private S3-compatible storage credentials used by the web service.
2. Run `php artisan storage:verify --probe` to verify basic object-storage access.
3. In Admin → Settings → Database Backup, use **Upload Backup to Cloud**.
4. Confirm the success message and verify the object exists under the private backup prefix.
5. Confirm an unauthenticated request cannot read the object.
6. Download a copy through the provider or CLI and restore it into an isolated staging database.
7. Verify users, bookings, services, payments, messages, and migrations in the restored database.
8. Record the restore duration and define the acceptable recovery point (RPO) and recovery time (RTO).

The application schedules `php artisan database:backup-cloud` daily at 02:00 and prevents overlapping runs for 30 minutes. On Laravel Cloud, confirm the environment scheduler is enabled and uses the same database, application key, and private-storage configuration as the web service. On self-managed infrastructure, run the Laravel scheduler from cron. For local testing, run `php artisan schedule:work`. The task only works when the production database and private-storage secrets are configured.

The application tests prove that the upload path writes to the configured disk, confirms the object, and cleans up the temporary local file. They cannot prove production credentials, provider access policy, scheduled execution, or a successful restore of your production database.
