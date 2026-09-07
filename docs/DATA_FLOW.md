# CleanFlow Data Flow

This documents the current booking flow as implemented in the Laravel application.

## Booking lifecycle

```text
Client web/mobile request
        |
        v
Validation and profile checks
        |
        v
Price calculation + schedule conflict checks + risk detection
        |
        v
Transaction: create one or more pending bookings
        |
        +--> Notification records
        +--> BookingSubmitted email (queued on the emails queue)
        +--> PayMongo checkout session (synchronous for digital payment)
        |
        v
Admin review / staff or provider assignment
        |
        +--> booking_activity_logs
        +--> client/staff notifications
        +--> status email (queued on the emails queue)
        |
        v
Confirmed --> In progress --> Completed
                 |                  |
                 |                  +--> booking_service_proofs
                 |                  +--> payment record and payout state
                 |                  +--> rating and reports
                 |
                 +--> booking_locations during live tracking
                 +--> booking_messages during communication
```

## Data ownership

| Data | Primary table/model | Main writers | Main readers |
| --- | --- | --- | --- |
| Booking and schedule | `bookings` / `Booking` | `BookingController`, admin, staff, mobile API, PayMongo webhook | client, staff, provider, admin, reports |
| Service catalog | `services` / `Service` | admin service controller, seeders | booking forms, pricing, dashboards |
| Notifications | `notifications` / `Notification` | booking/admin/staff/mobile controllers | client, staff, mobile API, layouts |
| Status history | `booking_activity_logs` / `BookingActivityLog` | booking status workflows | booking detail, admin logs, reports |
| Service evidence | `booking_service_proofs` / `BookingServiceProof` | staff web/mobile workflows | client, staff, admin booking detail |
| Location tracking | `bookings` current coordinates plus `booking_locations` history | staff location endpoint | client/admin maps |
| Messages | `booking_messages` / `BookingMessage` | booking messaging controller | booking detail pages |
| Payment | `payments` / `Payment` plus PayMongo checkout/webhook | booking controller, webhook, admin | booking detail, payouts, reports |
| Provider payout | booking payout fields plus `provider_payout_transactions` | admin/provider workflows | provider portal, admin payout reports |

## Request entry points

- Web client: `POST /bookings`, `POST /bookings/calculate-price`.
- Mobile client: `POST /api/mobile/bookings`, `POST /api/mobile/calculate-price`.
- Admin: booking status, staff/provider assignment, payment, review, and payout endpoints.
- Staff web/mobile: start, complete, upload proof, and location update endpoints.
- External payment: `POST /api/paymongo/webhook`.

## Findings requiring follow-up

1. Admin dashboard and analytics use database-side aggregates for their chart metrics; remaining operational pages should still be profiled as booking volume grows.
2. Booking creation queues email delivery, but still creates PayMongo checkout sessions inside the request. Slow payment-provider responses can keep web workers occupied.
3. Several portal pages calculate summary counts with separate queries. These should be measured before being consolidated; not every separate query is an actual bottleneck.
4. Booking payment data is normalized in `payments`; `bookings.price` remains the immutable booking-time price snapshot used for service and payout calculations.

## Current protections

- Schedule conflict checks run again inside the transaction with schedule locks.
- Booking, attendance, location, and authentication endpoints have rate limits where appropriate.
- Booking activity, proofs, messages, locations, and payouts use separate tables rather than putting all history in `bookings`.
- High-frequency location writes are coalesced when the point is nearly unchanged.
