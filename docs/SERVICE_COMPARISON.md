# Service Options Comparison

Audit date: 2026-08-27.

This table compares the service options defined by the application catalog. The customer booking form displays active rows from the `services` database table, while package descriptions and pricing rules come from `App\\Models\\Service::PACKAGE_CATALOG` and `App\\Models\\Booking::calculatePrice()`.

## Service packages

| Option | Best fit | Core scope | Catalog duration | Catalog rate / pricing basis |
| --- | --- | --- | ---: | --- |
| Basic Clean | Weekly or bi-weekly upkeep in regularly maintained homes | Dusting, surface wipe-down, sweeping, mopping, bathroom and kitchen touch-up | 60 minutes | PHP 35/sqm |
| Deep Clean | Seasonal resets, buildup, neglected areas, and harder-to-reach spaces | Detailed bathroom and kitchen scrubbing, grime removal, expanded surface and corner detailing | 180 minutes | PHP 95/sqm |
| Move-in/Move-out Clean | Empty-property turnovers, move preparation, and handovers | Whole-property turnover cleaning, cabinets, fixtures, wall surfaces, move-ready presentation | 240 minutes | PHP 80/sqm |
| Post Construction Cleaning | Recently renovated or newly constructed spaces | Fine construction dust and debris traces, fixtures, ledges, and renovation residue | 240 minutes | PHP 105/sqm |
| Office Cleaning (Basic) | Small offices needing routine maintenance | Visible surfaces, floors, trash collection, and light restroom refresh | 120 minutes | PHP 30/sqm |
| Office Cleaning (Standard) | Offices, storefronts, and business-ready workspaces | Reception and workstation routines, restroom and pantry sanitation | 180 minutes | PHP 35/sqm |
| Office Cleaning (Deep) | Periodic office resets and heavier buildup | Workstations, high-touch zones, restrooms, pantry areas, fixtures, and heavier detailing | 240 minutes | PHP 60/sqm; catalog baseline |
| General/Regular Cleaning | One-time or recurring routine home upkeep | Dusting, sweeping, mopping, routine kitchen and bathroom refresh | 240 minutes, up to 4 hours | PHP 500–800 per session; flat-rate range |

## Optional add-ons

Add-ons are extra tasks attached to a service; they are not standalone service packages.

| Add-on | Purpose | Catalog price fallback |
| --- | --- | ---: |
| Window Glass Cleaning | Interior glass panels and reachable windows | PHP 200 |
| Refrigerator Cleaning | Interior refrigerator wipe-down | PHP 350 |
| Inside Cabinet Cleaning | Interior shelves and cabinet surfaces | PHP 300 |
| Sofa Vacuuming | Dust and crumb removal from fabric seating | PHP 400 |
| Pet Hair Removal | Extra fur removal from floors, rugs, and furniture | PHP 300 |
| Yard Sweeping | Walkways, patios, and accessible yard areas | PHP 250 |

## How the quote is calculated

- Per-square-meter services use the active database service rate when a matching service row exists. If it does not, the package catalog rate is used as a fallback.
- General/Regular Cleaning uses the low end of the PHP 500–800 session range for the initial calculation. Floor area does not add a charge to this flat-rate service.
- For per-square-meter services, the calculation currently charges the full submitted floor area. The 30 sqm `included_floor_area` setting does not reduce these services.
- Selected add-ons are added to the quote individually.
- Property, room, and bathroom fees are currently zero in the pricing configuration.
- The saved booking stores a pricing snapshot, so later catalog changes should not silently rewrite an existing booking.

## Data problems this comparison exposed

The comparison is useful only if one source of truth is selected. Before applying `2026_08_27_000000_synchronize_service_catalog`, the local database inspected on 2026-08-27 did not match the canonical catalog:

| Item | Canonical catalog | Current local database/runtime concern |
| --- | --- | --- |
| Basic Clean | PHP 35/sqm; 60 minutes | Database row is PHP 30/sqm; 120 minutes |
| Deep Clean | PHP 95/sqm; 180 minutes | Database row is PHP 55/sqm; 240 minutes |
| General/Regular Cleaning | Present; PHP 500–800 flat range | No active `services` row was found, even though validation accepts the catalog slug |
| Add-on prices | PHP 200/350/300/400/300/250 | Database overrides currently return PHP 70/150/100/350/—/100; Pet Hair Removal is missing |

Because the booking form reads active database service rows and the pricing code prefers database prices, these differences can change the customer-facing quote. The synchronization migration aligns standard rows with the catalog but does not establish commercial approval. Approve the rates before production; that is separate from merely displaying a comparison table.

## Source files

- `app/Models/Service.php` — package names, descriptions, features, durations, and catalog pricing metadata.
- `app/Models/Booking.php` — pricing units, calculation rules, property fees, and fallback add-ons.
- `app/Models/ServiceAddOn.php` — database-backed add-on overrides.
- `resources/views/bookings/create.blade.php` — customer-facing service and rate display.
