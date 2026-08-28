# Pricing Approval Worksheet

Prepared: 2026-08-27.

This worksheet is for owner approval. It does not turn incomplete cost data into a guessed price.

## Formula

For each service, calculate:

```text
minimum sustainable price =
(direct labor + supplies + equipment + travel + payment fees + allocated overhead + risk allowance)
/ (1 - target gross margin)
```

Use actual productive cleaner-hours, not only the appointment duration. For a team, multiply the booked duration by the number of cleaners.

## How to fill this manually

Edit the blank fields in this Markdown file using a text editor. Enter costs in PHP and enter the target gross margin as a decimal, such as `0.30` for 30%. Do not replace the current revenue sanity-check figures; those are the existing app baseline used for comparison.

Start with General/Regular Cleaning because its current PHP 500 starting quote is the highest-risk case. Record one representative job, calculate its full cost, and then repeat the same process for the other services.

### General/Regular Cleaning - first entry

| Field | Your entry |
| --- | --- |
| Sample property / date | ______________________________ |
| Cleaner count | ______________________________ |
| Productive cleaner-hours | ______________________________ |
| Direct labor cost | PHP __________________________ |
| Supplies and equipment cost | PHP __________________________ |
| Travel cost | PHP __________________________ |
| Payment/platform fees | PHP __________________________ |
| Allocated overhead | PHP __________________________ |
| Risk/refund allowance | PHP __________________________ |
| Total job cost | PHP __________________________ |
| Target gross margin | ______________________________ |
| Minimum sustainable price | PHP __________________________ |
| Proposed customer price | PHP __________________________ |
| Decision and reason | ______________________________ |

Calculate `Total job cost` by adding the seven cost rows above it. Then calculate:

```text
minimum sustainable price = total job cost / (1 - target gross margin)
```

Example only: PHP 800 total cost at a 30% target margin gives `PHP 800 / 0.70 = PHP 1,142.86`. Replace this example with your actual numbers before approval.

Keep the decision as `Pending` until the cost evidence, service scope, and owner approval are recorded. Completing this form does not automatically change the app price.

## Local competitor evidence form

Record at least three dated comparisons. Use providers serving the same area where possible, and compare the same scope, property condition, cleaner-hours, and pricing unit. A screenshot or URL alone is weak evidence if the competitor’s inclusions and time capacity are different.

| Field | Comparable 1 | Comparable 2 | Comparable 3 |
| --- | --- | --- | --- |
| Provider and service name | __________________ | __________________ | __________________ |
| Date checked | __________________ | __________________ | __________________ |
| Service area | __________________ | __________________ | __________________ |
| Property type / condition | __________________ | __________________ | __________________ |
| Cleaner count | __________________ | __________________ | __________________ |
| Cleaner-hours | __________________ | __________________ | __________________ |
| Advertised price | PHP __________ | PHP __________ | PHP __________ |
| Pricing unit | __________________ | __________________ | __________________ |
| Included tasks | __________________ | __________________ | __________________ |
| Excluded tasks / extras | __________________ | __________________ | __________________ |
| Travel, tax, or fee treatment | __________________ | __________________ | __________________ |
| Source URL or evidence file | __________________ | __________________ | __________________ |
| Comparable to CleanFlow? Why? | __________________ | __________________ | __________________ |

Do not average prices mechanically. First normalize the comparison to a similar scope and cleaner-hour basis, then record why a difference is justified. Competitor advertising is market evidence, not proof that CleanFlow can cover its own costs.

### Public research leads (not yet comparable quotes)

The following dated web checks are useful starting points, but they do **not** satisfy the requirement for three comparable Valencia City, Bukidnon quotes:

| Provider | Published information | Why it is not yet a valid CleanFlow comparison |
| --- | --- | --- |
| [Claro's Cleaning](https://www.claroscleaning.ph/cleaning_prices_dumaguete.html) | Focused Clean: PHP 1,499 for 1 cleaner for 4 hours; supplies and equipment included; standard inclusions and extras are listed. Checked 2026-08-27. | The site lists Valencia, but its service area is Valencia, Negros Oriental—not confirmed Valencia City, Bukidnon. Confirm location and request a matching quote before using it. |
| [PrimeShineCDO Cleaning Services](https://primeshinecdocleaning.services/services) | Residential cleaning is listed at PHP 95 and post-construction cleaning at PHP 120; the page also lists Cagayan de Oro as the service area. Checked 2026-08-27. | The pricing unit, cleaner count, duration, inclusions, and fees are not stated on the public service list. It is also outside CleanFlow's primary service city. Request clarification before comparing. |
| Local Valencia City provider #3 | No reliable public comparable price found in the desk search. | Contact a local provider directly and record the written quote, scope, cleaner-hours, fees, and evidence date in the form above. |

Do not enter either published price as a CleanFlow recommendation. The minimum evidence still required is three direct or written quotes from providers serving Valencia City, Bukidnon, with matching scope and cleaner-hour details.

## Current revenue sanity check

These figures use the current catalog price and duration. They are gross revenue per booked service hour, not worker pay or profit.

| Service | Current example | Booked duration | Gross revenue per booked hour | Approval status |
| --- | ---: | ---: | ---: | --- |
| Basic Clean | PHP 1,400 at 40 sqm | 1 hour | PHP 1,400 | Needs labor/capacity validation |
| Deep Clean | PHP 3,800 at 40 sqm | 3 hours | PHP 1,266.67 | Needs labor/capacity validation |
| Move-in/Move-out Clean | PHP 3,200 at 40 sqm | 4 hours | PHP 800 | Needs labor/capacity validation |
| Post Construction Cleaning | PHP 4,200 at 40 sqm | 4 hours | PHP 1,050 | Needs safety/equipment validation |
| Office Cleaning (Basic) | PHP 1,200 at 40 sqm | 2 hours | PHP 600 | Needs labor/capacity validation |
| Office Cleaning (Standard) | PHP 1,400 at 40 sqm | 3 hours | PHP 466.67 | Needs labor/capacity validation |
| Office Cleaning (Deep) | PHP 2,400 at 40 sqm | 4 hours | PHP 600 | Needs labor/capacity validation |
| General/Regular Cleaning | PHP 500 starting quote | 4 hours | PHP 125 | High risk; do not approve without a cost model |

Per-sqm services become more exposed to over- or under-pricing as floor area changes because the catalog duration does not currently scale with area. Revenue-per-hour is therefore only a warning signal, not a profitability calculation.

## Official wage sanity check

The [National Wages and Productivity Commission Region X page](https://nwpc.dole.gov.ph/region-x/) lists, under Wage Order No. RX-24 effective 16 January 2026, PHP 485–500 daily minimum rates for covered private-sector categories in Valencia and a PHP 6,500 monthly minimum for domestic workers in Region X. These legal baselines do not determine CleanFlow’s selling price, and worker classification, benefits, travel time, and employment arrangement still require professional review.

The PHP 500 four-hour General/Regular starting quote is not proof of non-compliance by itself, but it is too thin to call commercially safe without knowing how many cleaners are paid and which cost model applies.

## Required owner inputs

| Input | Basic | Deep | Move/Out | Post-Construction | Office | General/Regular |
| --- | --- | --- | --- | --- | --- | --- |
| Cleaner count | ______ | ______ | ______ | ______ | ______ | ______ |
| Productive cleaner-hours | ______ | ______ | ______ | ______ | ______ | ______ |
| Direct labor cost | ______ | ______ | ______ | ______ | ______ | ______ |
| Supplies and equipment cost | ______ | ______ | ______ | ______ | ______ | ______ |
| Average travel cost/time | ______ | ______ | ______ | ______ | ______ | ______ |
| Payment/platform fees | ______ | ______ | ______ | ______ | ______ | ______ |
| Allocated overhead | ______ | ______ | ______ | ______ | ______ | ______ |
| Risk/refund allowance | ______ | ______ | ______ | ______ | ______ | ______ |
| Target gross margin | ______ | ______ | ______ | ______ | ______ | ______ |
| Minimum sustainable price | ______ | ______ | ______ | ______ | ______ | ______ |

## Decisions required before approval

1. Set minimum charges and maximum floor areas by service.
2. Define whether prices include supplies, equipment, travel, taxes, and payment fees.
3. Define whether add-ons are priced per item, area, room, appliance, or visit.
4. Confirm that Office Deep should remain a fixed PHP 60/sqm rate, or define an approved condition-based re-quote rule.
5. Define how the General/Regular PHP 500–800 range selects a price above PHP 500.
6. Define overage, inspection, condition, and second-visit rules.
7. Collect at least three comparable local quotes with the same scope and pricing unit.

## Approval record

- Cost model reviewed by: ____________________
- Market comparisons reviewed by: ____________________
- Operations/capacity reviewed by: ____________________
- Legal/payroll review completed by: ____________________
- Approved rate schedule version: ____________________
- Approval date and effective date: ____________________

## Decision

Pricing is not approved by this worksheet. It becomes approvable only after the blanks are completed, the service scope is fixed, and the owner signs the resulting rate schedule.

## Source references

- `docs/PRICING_VALIDATION.md` — risks and current catalog examples.
- `docs/PRICING_POLICY.md` — configured implementation baseline.
- `app/Models/Service.php` — catalog prices and durations.
- `app/Models/Booking.php` — quote calculation rules.
