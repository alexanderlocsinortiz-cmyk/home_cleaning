# Data Model Audit

Audit date: 2026-08-26.

## Redundant fields removed

The booking model had fields from two pricing schemas. The canonical schema is now:

- `base_price`
- `property_fee`
- `rooms_fee`
- `bathrooms_fee`
- `floor_area_fee`
- `add_ons_fee`
- `price` as the stored total snapshot

The migration `2026_08_26_000000_remove_redundant_booking_columns` backfills canonical fields where needed, copies a legacy `address` into `street_address` only when the canonical field is empty, and then removes these unused columns:

- `address`
- `property_adjustment`
- `room_bathroom_fees`
- `floor_area_fees`
- `add_on_fees`

The migration is guarded for environments where some legacy columns have already disappeared. Take a database backup before applying it because rollback recreates the columns but cannot reconstruct discarded legacy values.

## Fields intentionally retained

- `service_id` and `service_type`: the relationship supports current catalog joins while `service_type` preserves the booking’s historical service slug.
- `price` and its components: the total is a booking-time price snapshot; components explain that total even if catalog pricing changes later.
- `payment_reference` and `payment_checkout_session_id`: one is the customer/payment reference and the other identifies the PayMongo checkout session.
- Current booking coordinates and `booking_locations`: the booking row is the latest-location snapshot; the child table is the tracking history.
- Current payout/commission fields and payout transaction tables: the booking stores current state while transaction rows preserve the audit trail.
