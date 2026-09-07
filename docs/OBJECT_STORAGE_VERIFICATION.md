# Object Storage Verification Report

Review date: 2026-08-27.

## Result

The application is ready to use durable object storage, but production storage is not configured or cut over. The repository cannot create the bucket, set its access policy, or migrate production files without deployment credentials.

## Latest configured-environment verification

Run on 2026-09-05 against the S3-compatible configuration available to this workspace:

- `php artisan storage:verify --probe` passed for private uploads, public uploads, and booking proof uploads.
- A temporary public object was readable anonymously with matching content.
- A temporary private object was readable with a signed URL; the same URL without its signature was denied with HTTP 400. The provider did not return the object.
- Private and public buckets are distinct, and private/proof visibility is private. The currently selected runtime proof disk is `s3`, so proof media currently shares the private disk and `private` prefix; the separately defined `s3_proof` disk and `proofs` prefix are not active yet.
- Probe cleanup passed: zero temporary health-check objects remained on either bucket.
- Migration dry-runs found 216 private local files, 8 public local files, and 4 legacy booking-media records. No files were copied or deleted.

These checks prove the currently configured endpoint and credentials, not Render production state. Selecting `s3_proof` (or an equivalent private proof disk) and migrating existing proof media is still required for the intended proof-prefix isolation. Render service variables, bucket policy, and the production cutover remain manual verification gates.

## Static and local checks

| Check | Result | Evidence |
| --- | --- | --- |
| Private and public disk configuration exists | Pass | `config/filesystems.php` defines `s3` and `s3_public` |
| Sensitive documents use the private disk | Pass | Cleaner identity and payout documents are stored through `private_uploads_disk` |
| Booking proof media uses a private disk | Pass | New service proof uploads use `proof_uploads_disk` and an authorization-checked inline endpoint |
| Catalog and rating media use the public disk | Pass | Service images, rating photos, and the site logo use `public_uploads_disk` |
| Migration preserves source files | Pass | `storage:migrate-local` copies and never deletes source files; automated test covers this |
| Local dry-run completed | Pass | 216 private and 8 public files detected; 4 legacy booking-media records were reviewed without copying or deleting |
| Render storage variables are declared | Pass | `render.yaml` declares separate private/public bucket and URL settings, endpoint, path-style, and prefix settings for web and worker |
| Disk visibility contract is covered by an automated test | Pass | `tests/Unit/FilesystemConfigurationTest.php` asserts that `s3` has no public visibility while `s3_public` is public |
| Private download endpoint denies guests | Pass | `tests/Feature/CleanerApplicationTest.php` verifies an unauthenticated request is redirected before a sensitive document download |
| Sensitive application uploads honor the configured private disk | Pass | `tests/Feature/CleanerApplicationTest.php` stores uploaded identity files on a configured private test disk |
| Production bucket exists | Not verified | Requires provider dashboard or CLI access |
| Bucket policy protects private files | Not verified | Requires a real unauthenticated access test |
| Existing production files migrated | Not verified | Requires production credentials and a cutover window |

The repository also provides `php artisan storage:verify --probe`. It writes, reads, and deletes one uniquely named temporary object on each configured upload disk. Run it from the production shell after setting the storage variables; it does not inspect or delete business files.

## Security boundary

Use a private bucket for documents, backups, and booking proof media, plus a separate public bucket for catalog and rating media. The private bucket must deny public reads and be reachable only through authorized application downloads. Separate prefixes remain an additional organization boundary.

Do not make the entire bucket public. That would risk exposing government IDs, selfies, payout documents, and commission evidence.

## Required cutover

1. Create separate private and public buckets, with separate prefixes inside them.
2. Create least-privilege credentials limited to the required bucket and prefixes.
3. Configure `FILESYSTEM_PRIVATE_DISK=s3`, `FILESYSTEM_PROOF_DISK=s3_proof`, and `FILESYSTEM_PUBLIC_DISK=s3_public` on web, worker, and backup-cron services, pointing them at the matching buckets.
4. Configure different `FILESYSTEM_PRIVATE_PREFIX`, `FILESYSTEM_PROOF_PREFIX`, and `FILESYSTEM_PUBLIC_PREFIX` values, then configure the same AWS region, bucket, endpoint, URL, and path-style setting on all services.
5. In staging, run `php artisan storage:migrate-local --dry-run` and `php artisan storage:migrate-proof-media --include-ratings --dry-run`, then copy the files and record source/destination counts.
6. Verify an admin can download a private document while an unauthenticated request receives no private file.
7. Verify client, staff, and provider pages load proof media through `/bookings/{booking}/proof/{proof}` while direct public-bucket reads fail.
8. Spot-check file names, sizes, content types, and representative images/videos after migration.
9. Keep local source files until the destination is verified and backed up.

## Release decision

The storage implementation and migration tooling pass local checks. “Configure durable object storage for uploaded files” remains open until the production bucket, access policy, environment variables, migration, and privacy tests are complete.

## Source references

- `config/filesystems.php` — disk definitions and environment switches.
- `routes/console.php` — non-destructive migration command.
- `docs/OBJECT_STORAGE.md` — production setup procedure.
- `tests/Feature/StorageMigrationCommandTest.php` — copy-without-delete coverage.
