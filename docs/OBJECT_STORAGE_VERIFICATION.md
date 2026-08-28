# Object Storage Verification Report

Review date: 2026-08-27.

## Result

The application is ready to use durable object storage, but production storage is not configured or cut over. The repository cannot create the bucket, set its access policy, or migrate production files without deployment credentials.

## Static and local checks

| Check | Result | Evidence |
| --- | --- | --- |
| Private and public disk configuration exists | Pass | `config/filesystems.php` defines `s3` and `s3_public` |
| Sensitive documents use the private disk | Pass | Cleaner identity and payout documents are stored through `private_uploads_disk` |
| Proof media and site assets use the public disk | Pass | Service proofs, rating photos, and the site logo use `public_uploads_disk` |
| Migration preserves source files | Pass | `storage:migrate-local` copies and never deletes source files; automated test covers this |
| Local dry-run completed | Pass | 3 private and 8 public files detected; 11 skipped because local disks are currently configured |
| Render storage variables are declared | Pass | `render.yaml` declares separate private/public bucket and URL settings, endpoint, path-style, and prefix settings for web and worker |
| Disk visibility contract is covered by an automated test | Pass | `tests/Unit/FilesystemConfigurationTest.php` asserts that `s3` has no public visibility while `s3_public` is public |
| Private download endpoint denies guests | Pass | `tests/Feature/CleanerApplicationTest.php` verifies an unauthenticated request is redirected before a sensitive document download |
| Sensitive application uploads honor the configured private disk | Pass | `tests/Feature/CleanerApplicationTest.php` stores uploaded identity files on a configured private test disk |
| Production bucket exists | Not verified | Requires provider dashboard or CLI access |
| Bucket policy protects private files | Not verified | Requires a real unauthenticated access test |
| Existing production files migrated | Not verified | Requires production credentials and a cutover window |

The repository also provides `php artisan storage:verify --probe`. It writes, reads, and deletes one uniquely named temporary object on each configured upload disk. Run it from the production shell after setting the storage variables; it does not inspect or delete business files.

## Security boundary

Use separate buckets for private documents/backups and public media. The private bucket must deny public reads and be reachable only through authorized application downloads. The public bucket may serve proof media, rating photos, and the site logo. Separate prefixes remain an additional organization boundary.

Do not make the entire bucket public. That would risk exposing government IDs, selfies, payout documents, and commission evidence.

## Required cutover

1. Create separate private and public buckets, with separate prefixes inside them.
2. Create least-privilege credentials limited to the required bucket and prefixes.
3. Configure `FILESYSTEM_PRIVATE_DISK=s3` and `FILESYSTEM_PUBLIC_DISK=s3_public` on both web and worker services, pointing them at the matching buckets.
4. Configure different `FILESYSTEM_PRIVATE_PREFIX` and `FILESYSTEM_PUBLIC_PREFIX` values, then configure the same AWS region, bucket, endpoint, URL, and path-style setting on both services.
5. In staging, run `php artisan storage:migrate-local --dry-run`, then copy the files and record source/destination counts.
6. Verify an admin can download a private document while an unauthenticated request receives no private file.
7. Verify client, staff, and provider pages load public proof media and videos.
8. Spot-check file names, sizes, content types, and representative images/videos after migration.
9. Keep local source files until the destination is verified and backed up.

## Release decision

The storage implementation and migration tooling pass local checks. “Configure durable object storage for uploaded files” remains open until the production bucket, access policy, environment variables, migration, and privacy tests are complete.

## Source references

- `config/filesystems.php` — disk definitions and environment switches.
- `routes/console.php` — non-destructive migration command.
- `docs/OBJECT_STORAGE.md` — production setup procedure.
- `tests/Feature/StorageMigrationCommandTest.php` — copy-without-delete coverage.
