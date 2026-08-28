# Budget Transparency

Implementation date: 2026-08-27.

## Customer-facing budget

The booking form presents a live estimate before submission. It shows:

- the selected service, property, schedule, plan, and payment method;
- the service amount or the per-square-meter calculation;
- property adjustment, floor-area charge, and each selected add-on;
- the estimated total; and
- a clear note that the current calculator does not add travel, tax, discount, or manual-adjustment amounts unless they appear in the saved booking breakdown.

The booking details page shows the saved pricing snapshot, including the calculation basis and selected add-ons. This prevents a later catalog change from silently changing an existing booking.

## Important limitation

The displayed total is a calculated estimate, not proof that the commercial price is reasonable. Pricing approval, scope limits, site-condition changes, and any manual re-quote remain business decisions. Staff must communicate and record any change before collecting payment.

## Release check

Budget presentation is implemented and covered by booking-flow tests. The separate pricing-validation TODO remains open until cost, market, capacity, and margin evidence is approved.

## Source files

- `resources/views/bookings/create.blade.php` — live estimate and itemized customer quote.
- `resources/views/bookings/show.blade.php` — saved booking pricing snapshot.
- `app/Models/Booking.php` — server-side calculation and stored breakdown.
- `tests/Feature/BookingCreationTest.php` — quote and saved-breakdown coverage.

