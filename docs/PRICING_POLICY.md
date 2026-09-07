# Pricing Policy and Rate Baseline

Baseline date: 2026-08-27.

This document defines the pricing currently configured in CleanFlow. It separates a service **rate** (the unit used by the calculator) from a customer-facing **price** (the resulting amount for a booking). These values are an implementation baseline and still require business and market sign-off before production launch.

## Service rates

| Service | Rate type | Configured rate |
| --- | --- | ---: |
| Basic Clean | Per square meter | PHP 35/sqm |
| Deep Clean | Per square meter | PHP 95/sqm |
| Move-in/Move-out Clean | Per square meter | PHP 80/sqm |
| Post Construction Cleaning | Per square meter | PHP 105/sqm |
| Office Cleaning (Basic) | Per square meter | PHP 30/sqm |
| Office Cleaning (Standard) | Per square meter | PHP 35/sqm |
| Office Cleaning (Deep) | Per square meter | PHP 60/sqm |
| General/Regular Cleaning | Flat session range | PHP 500–800/session |

## Add-on prices

| Add-on | Configured price |
| --- | ---: |
| Window Glass Cleaning | PHP 200 |
| Refrigerator Cleaning | PHP 350 |
| Inside Cabinet Cleaning | PHP 300 |
| Sofa Vacuuming | PHP 400 |
| Pet Hair Removal | PHP 300 |
| Yard Sweeping | PHP 250 |

Each add-on is currently selected at most once and charged once per booking. The application does not support quantity-based billing such as per window, panel, room, or appliance.

## Calculation rules

- Per-sqm service quotes use the matching database service rate. If the database row is unavailable, the package catalog is the fallback.
- Per-sqm quotes charge the complete submitted floor area; there is currently no free-area deduction.
- General/Regular Cleaning is flat-rate. The backend uses the persisted service price as the current session quote; the PHP 500–800 range is informational until an owner-approved rule selects a price based on scope or condition.
- Selected add-ons are summed and added to the service amount.
- Property, room, and bathroom surcharges are currently PHP 0.
- The booking stores the calculated pricing breakdown as a snapshot. Future rate changes do not rewrite existing bookings.

## Example customer prices

Before add-ons, property surcharges, discounts, taxes, or manual adjustments:

| Example | Calculation | Result |
| --- | --- | ---: |
| 40 sqm Basic Clean | 40 × PHP 35 | PHP 1,400 |
| 40 sqm Deep Clean | 40 × PHP 95 | PHP 3,800 |
| 40 sqm Move-in/Move-out Clean | 40 × PHP 80 | PHP 3,200 |
| 40 sqm Office Cleaning (Basic) | 40 × PHP 30 | PHP 1,200 |
| General/Regular Cleaning | Flat starting amount | PHP 500 |

## Approval and launch gate

The code and database migration now use one effective service-rate path, so an approved database rate controls both the customer display and the quote calculation. The following still require owner evidence before production:

- Confirm that each rate covers labor, supplies, travel, overhead, and target margin.
- Confirm whether the General/Regular PHP 500–800 range needs a rule for selecting an amount above the minimum.
- Confirm whether any add-on needs quantity-based billing; implementing that requires explicit units, quantity limits, and revised booking/API fields.
- Validate the rates against local competitors and documented operating costs.
- Record the approver, approval date, effective date, and any geographic or property-size limits.

Until those checks are complete, treat this as a configured pricing baseline, not a validated commercial price list.

## Source files

- `app/Models/Service.php` — canonical package rates and metadata.
- `app/Models/Booking.php` — effective rate lookup and quote calculation.
- `database/migrations/2026_08_27_000000_synchronize_service_catalog.php` — database synchronization.
