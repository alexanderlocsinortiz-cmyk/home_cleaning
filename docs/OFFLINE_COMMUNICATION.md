# Offline Communication Options

Implementation date: 2026-08-27.

CleanFlow now exposes fallback contact options in the public footer. The goal is to keep customers able to reach the business when the booking site or client portal is unavailable.

## Available channels

| Channel | How customers use it | Configuration |
| --- | --- | --- |
| Phone call | Tap the configured phone number from a mobile device | Admin Settings → Contact phone |
| SMS | Text the same configured phone number during office hours | Admin Settings → Contact phone |
| In-person visit | Visit the configured office address during the configured office hours | Admin Settings → Contact address and Office hours |
| Email | Use the configured email link when web access works but portal actions do not | Admin Settings → Contact email |

The footer does not invent a phone number. If no contact phone is configured, it displays a warning that the call/SMS fallback is unavailable. A real phone number must be entered before launch.

## Outage handling procedure

1. Customer keeps the booking reference, if one exists.
2. Customer calls or texts the configured contact phone, or visits the configured office.
3. Staff records the request in the booking or admin system once access returns.
4. Staff confirms any change to schedule, price, payment, or assignment through the normal booking record.

Do not accept an offline booking as confirmed solely from a text message or verbal conversation. Record it in CleanFlow and run the normal conflict and pricing checks before confirming it.

## Verification

- `resources/views/layouts/app.blade.php` renders `tel:` and `mailto:` links when configured.
- `tests/Feature/OfflineCommunicationTest.php` verifies the public fallback copy and phone link.
- The default local phone is intentionally blank; production setup must supply the real business number.
