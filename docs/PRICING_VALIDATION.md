# Pricing Validation Audit

Audit date: 2026-08-27.

## Result

The application pricing is technically traceable, but it is not yet proven legitimate or reasonable as a commercial price list. The current figures are a configured baseline. They must not be presented as market-validated until operating costs, local competitor quotes, service capacity, and target margin are documented.

## What is technically verified

- Eight canonical service packages and six add-ons have one synchronized catalog price source.
- Per-square-meter quotes use the effective database service rate and save a pricing snapshot on the booking.
- General/Regular Cleaning is treated as a flat session price.
- Add-ons are added to the service amount.
- Current property, room, and bathroom surcharges are explicitly zero.
- Automated tests cover catalog synchronization and representative quote calculations.

## Commercial risks that remain open

| Risk | Why it matters | Required decision |
| --- | --- | --- |
| No minimum charge or area cap | A tiny job may be underpriced, while a large area may be impossible within the listed duration | Set a minimum price, minimum area, maximum area, or manual-quote threshold |
| Fixed duration with variable floor area | A 60-minute Basic Clean and a 60-minute quote for any submitted area do not prove the crew can deliver the promised scope | Define capacity by area, condition, crew size, and travel time |
| General/Regular PHP 500–800 range | The backend currently starts every calculated quote at PHP 500; no rule selects PHP 600–800 | Define the selection rule or remove the range and publish one price |
| Office Deep condition handling | Customer-facing text and backend now consistently use PHP 60/sqm; no condition-based exception or re-quote flow is configured | Confirm that the fixed rate covers the intended conditions, or approve a separate inspection/re-quote workflow |
| Add-on units are unspecified | “PHP 200” could mean per window, panel, room, appliance, or visit | Define the unit, quantity limit, and whether the add-on is per booking or per item |
| No documented cost model | Revenue alone does not show whether labor, supplies, transport, taxes, overhead, refunds, and target margin are covered | Record actual cost per service and the approved margin formula |
| No local market evidence | A price can be mathematically correct and still be too high or too low for the service area | Collect dated competitor quotes for comparable scope and conditions |

## Current quote examples

These are calculation examples, not recommendations. They exclude add-ons, discounts, taxes, travel, and manual adjustments.

| Service | 40 sqm quote | 100 sqm quote |
| --- | ---: | ---: |
| Basic Clean at PHP 35/sqm | PHP 1,400 | PHP 3,500 |
| Deep Clean at PHP 95/sqm | PHP 3,800 | PHP 9,500 |
| Move-in/Move-out at PHP 80/sqm | PHP 3,200 | PHP 8,000 |
| Post Construction at PHP 105/sqm | PHP 4,200 | PHP 10,500 |
| Office Basic at PHP 30/sqm | PHP 1,200 | PHP 3,000 |
| Office Standard at PHP 35/sqm | PHP 1,400 | PHP 3,500 |
| Office Deep at PHP 60/sqm | PHP 2,400 | PHP 6,000 |
| General/Regular Cleaning | PHP 500 starting quote | PHP 500 starting quote |

## Sanity-check result

The General/Regular Cleaning starting quote is PHP 500 for up to four hours, or PHP 125 gross revenue per booked hour before any labor, supplies, travel, overhead, payment fees, refunds, or profit. This is a high-risk price signal and requires a completed cost model before approval. See `docs/PRICING_APPROVAL_WORKSHEET.md`.

The current per-sqm catalog durations also do not scale with floor area. A larger submitted area can increase the quote without increasing the scheduled duration, so each service needs a capacity rule before the rate can be called reasonable.

## Approval evidence required

For each package and add-on, retain:

1. Labor cost, expected crew size, productive hours, and service capacity.
2. Supplies, equipment, transport, payment fees, taxes, refunds, and overhead assumptions.
3. Target gross margin and the formula used to derive the price.
4. At least three comparable local competitor quotes with comparable scope and units.
5. Minimum charge, maximum area, overage, inspection, and re-quote rules.
6. Approver, approval date, effective date, and geographic/property limits.

## Release decision

The implementation baseline is ready for review, but “Validate that pricing is legitimate and reasonable” stays open until the owner approves the cost model and market evidence. Do not silently change the code to make the numbers look validated; the missing proof is a business decision, not a testing problem.

## Source references

- `app/Models/Service.php` — canonical service rates and price range metadata.
- `app/Models/Booking.php` — quote calculation and effective rate lookup.
- `docs/PRICING_POLICY.md` — current pricing baseline and launch gate.
- `tests/Feature/CanonicalServiceCatalogTest.php` — catalog and persisted-rate coverage.
- `docs/PRICING_APPROVAL_WORKSHEET.md` — cost-model inputs and revenue sanity checks.
