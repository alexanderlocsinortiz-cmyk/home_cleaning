# Object Storage Deployment

CleanFlow now routes uploads through separate configurable disks and should use a private bucket plus a public media bucket:

- `FILESYSTEM_PRIVATE_DISK` stores application documents, payout evidence, and database backups. Use a private bucket.
- `FILESYSTEM_PROOF_DISK` stores booking proof media. Use a private disk/bucket or private prefix because proof photos can reveal a customer's home.
- `FILESYSTEM_PUBLIC_DISK` stores rating photos, service images, and the site logo. Use a separate public bucket for media intended to be public.

For production, configure:

```dotenv
FILESYSTEM_PRIVATE_DISK=s3
FILESYSTEM_PUBLIC_DISK=s3_public
FILESYSTEM_PROOF_DISK=s3_proof
FILESYSTEM_PRIVATE_PREFIX=private
FILESYSTEM_PUBLIC_PREFIX=public
FILESYSTEM_PROOF_PREFIX=proofs
AWS_PRIVATE_BUCKET=cleanflow-private
AWS_PUBLIC_BUCKET=cleanflow-public
AWS_PRIVATE_URL=
AWS_PUBLIC_URL=https://your-public-media-domain.example
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=auto
AWS_ENDPOINT=https://<ACCOUNT_ID>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

The `s3` disk remains private and points to `AWS_PRIVATE_BUCKET`. The `s3_public` disk points to the separate `AWS_PUBLIC_BUCKET`. Do not make the private bucket public; it contains identity documents, payout evidence, and database backups. R2 uses `auto` as the S3 region and the account endpoint format shown above. [R2 S3 compatibility](https://developers.cloudflare.com/r2/api/s3/api/)

## Cloudflare R2 setup

Complete these steps in Cloudflare before adding the values to Render:

1. Open **Cloudflare Dashboard → R2 Object Storage** and create two buckets, for example `cleanflow-private` and `cleanflow-public`.
2. Keep `cleanflow-private` private. It must contain documents and database backups.
3. Create an R2 API token with **Object Read & Write** permission, scoped only to these two buckets. Copy the **Access Key ID** and **Secret Access Key** immediately; the secret is shown only when it is created. See [Cloudflare’s R2 S3 setup](https://developers.cloudflare.com/r2/get-started/s3/).
4. Use this endpoint, replacing the placeholder with your Cloudflare account ID:

   ```text
   https://<ACCOUNT_ID>.r2.cloudflarestorage.com
   ```

5. Enable public access only for `cleanflow-public`. For a real deployment, attach a custom domain. Cloudflare’s `r2.dev` URL is intended for development and is rate-limited, so it should not be the permanent production URL. See [R2 public buckets](https://developers.cloudflare.com/r2/buckets/public-buckets/).
6. Add the matching variables to both the Render web service and the Render worker. Do not put the secret key in this repository or in a document.

For a small pilot, set `DATABASE_BACKUP_RETENTION_COUNT=7` rather than keeping 30 copies. The R2 free allowance is account-level and limited, so retaining unnecessary backups can consume it alongside uploaded media. Check the current allowance and pricing on [Cloudflare R2 pricing](https://developers.cloudflare.com/r2/pricing/).

Before production cutover:

1. Create a private bucket for documents, backups, and proof media, plus a separate public bucket for catalog and rating media. Keep `FILESYSTEM_PRIVATE_PREFIX` and `FILESYSTEM_PROOF_PREFIX` different as an additional boundary.
2. Configure the same storage variables on the web, queue-worker, and backup-cron services.
3. Migrate existing local/private files with `storage:migrate-local --dry-run`, then migrate legacy public booking proofs with `storage:migrate-proof-media --dry-run`.
4. Run the proof migration in staging, validate portal access, then use `storage:migrate-proof-media --include-ratings --delete-source` to remove the legacy public copies.
5. Verify an admin can download a private document, authorized booking participants can load proof media, and unauthenticated requests receive an authentication response.

The application changes are backward-compatible: local development and tests continue using the `local` and `public` disks unless the new environment variables are set. `AWS_BUCKET` and `AWS_URL` remain supported as legacy fallbacks, but new deployments should use the separate bucket variables.

The migration is non-destructive and idempotent. After configuring the production disks, preview the files with:

```bash
php artisan storage:migrate-local --dry-run
php artisan storage:migrate-proof-media --dry-run
php artisan storage:migrate-proof-media --include-ratings --dry-run
```

Then run the copy:

```bash
php artisan storage:migrate-local
php artisan storage:migrate-proof-media
php artisan storage:migrate-proof-media --include-ratings --delete-source
```

The command never deletes local files. It skips files already present at the destination and returns a failure exit code if any file cannot be read or copied.
