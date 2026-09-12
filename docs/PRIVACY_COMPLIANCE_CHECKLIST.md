# Privacy and Compliance Launch Checklist

This is an implementation checklist, not legal advice or a compliance
certificate. The owner must complete it with qualified privacy and legal
advice for the jurisdictions in which CleanFlow operates.

## Business decisions required before launch

- [ ] Record the legal name, address, and contact details of the data
      controller/operator.
- [ ] Identify whether a data protection officer or privacy officer is
      required, and publish a monitored contact address.
- [ ] Identify every country/state where users, cleaners, providers, and
      infrastructure are located.
- [ ] Document the purpose and lawful basis/authority for each data category.
- [ ] Confirm whether Philippine Data Privacy Act, GDPR, employment/privacy,
      consumer, tax, or other sector obligations apply.
- [ ] Obtain written processor/vendor details for payment, email, maps,
      video, object storage, push notifications, hosting, and monitoring.
- [ ] Confirm data locations, cross-border transfers, contracts, subprocessors,
      breach obligations, and deletion support for each vendor.
- [ ] Approve retention periods with legal, tax, employment, and operational
      owners. Do not use the sample periods in old documents as legal rules.
- [ ] Define the process, identity checks, deadlines, and owner for access,
      correction, deletion, objection, portability, and restriction requests.
- [ ] Define the incident response owner, escalation path, evidence handling,
      and notification decision process.
- [ ] Confirm payment-card scope and responsibility with each payment
      processor. Do not store card numbers, CVV/CVC, or equivalent secrets.

## Data inventory to verify against the running system

| Data | Where it appears | Owner decision |
| --- | --- | --- |
| Account identity and contact data | `users` and profiles | Purpose, access, retention |
| Date of birth and address | `users` | Necessity, visibility, retention |
| Government IDs and clearance documents | Cleaner applications/team-member documents and private storage | Legal basis, access, deletion |
| Fingerprint enrollment identifiers | Staff/device enrollment records; the biometric template remains on the device | Biometric policy, consent/authority, retention |
| Booking, payment references, ratings, disputes | Booking/payment/rating tables | Accounting, dispute, and deletion rules |
| Service proof photos/videos and location metadata | Booking proof media/private storage | Notice, access, retention, deletion |
| Device, security, and audit events | `devices`, `security_events`, application logs | Retention, monitoring, access |
| Push tokens and mobile sessions | Mobile token/push-token tables | Purpose, revocation, deletion |

## Technical evidence to retain

- [ ] Production configuration review proves `APP_DEBUG=false`, HTTPS `APP_URL`,
      encrypted/secure/strict session cookies, durable private storage, and
      `IOT_REQUIRE_SIGNED_REQUESTS=true`.
- [ ] Database migration history and backup-restore test are recorded.
- [ ] `composer audit`, `npm audit --omit=dev --audit-level=moderate`, and the
      full test suite pass for the release commit.
- [ ] Access-control tests cover admin, staff, client, provider, ownership,
      private media, and government-document boundaries.
- [ ] Security events and infrastructure logs are access-restricted, rotated,
      monitored, and retained according to the approved schedule.
- [ ] Deletion/revocation procedures are tested for accounts, mobile tokens,
      device credentials, private documents, proof media, and backups.
- [ ] Vendor agreements, privacy notices, cookie notice, consent records, and
      incident runbooks are stored outside the code repository where appropriate.

## Release gate

Do not describe CleanFlow as “GDPR compliant,” “DPA compliant,” or “PCI
compliant” based only on this repository. Those statements require the owner’s
completed assessment, evidence, contracts, and operating procedures.
