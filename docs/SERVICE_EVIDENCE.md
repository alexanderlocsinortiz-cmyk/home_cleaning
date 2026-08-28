# Service Evidence Pack

Evidence date: 2026-08-27.

This pack records what can be verified from the application today. It does not treat a marketing image or a code description as proof that a cleaning job was physically completed.

The generated catalog snapshot is attached at `docs/SERVICE_EVIDENCE_DATA.json`. It is application evidence only and deliberately marks physical completion evidence as missing.

## Evidence status

| Service | Application evidence | Automated evidence | Real-world evidence status |
| --- | --- | --- | --- |
| Basic Clean | Catalog features and customer booking card | Catalog consistency test; booking-flow test | Service photos or completed-job proof still needed |
| Deep Clean | Catalog features, 180-minute duration, and per-sqm quote path | Deep-clean pricing test | Service photos or completed-job proof still needed |
| Move-in/Move-out Clean | Turnover scope, 240-minute duration, and per-sqm quote path | Move-in/move-out pricing test | Turnover before/after proof still needed |
| Post Construction Cleaning | Construction-dust and renovation-residue scope | Post-construction pricing test | Renovation before/after proof still needed |
| Office Cleaning (Basic) | Office package features and per-sqm rate | Office pricing test | Office-site proof still needed |
| Office Cleaning (Standard) | Reception, workstation, restroom, and pantry scope | Office pricing test | Office-site proof still needed |
| Office Cleaning (Deep) | High-touch, restroom, pantry, and heavy-detail scope | Office pricing test | Office-site proof still needed |
| General/Regular Cleaning | Four-hour session scope and flat-rate range | General/regular pricing test | Routine-session proof still needed |

## What is verifiable now

- The canonical package catalog defines the name, description, features, pricing unit, and recommended duration for all eight services in `app/Models/Service.php`.
- The customer booking page renders service cards and their features from that catalog in `resources/views/bookings/create.blade.php`.
- The mobile service endpoint exposes active services, features, durations, pricing units, and price ranges through `app/Http/Controllers/Api/MobileServiceController.php`.
- The booking calculator has automated coverage for per-sqm services, General/Regular Cleaning, add-ons, and the persisted-rate path.
- The full test suite passed on 2026-08-27: 343 tests and 1,752 assertions.

## Existing images

The repository contains `public/images/landing-cleaning-hero.png` and `public/images/logo.png`. These are branding/marketing assets, not evidence that any individual service was delivered. No service-specific before/after photographs, property checklists, signed completion records, or customer acceptance records were found.

## Evidence still required per service

For each package, attach at least one dated, consented evidence set:

1. Before and after photos covering the areas promised by the package.
2. A completed-work checklist tied to the booking and service type.
3. Date, property type, floor area, and assigned cleaner/team.
4. Customer acceptance, rating, or signed completion confirmation.
5. Any exclusions, inaccessible areas, materials, or condition limits.

Do not reuse one generic photo for all packages. That would prove only that the brand has a cleaning image, not that each advertised service is complete and inclusive.

## Source references

- `app/Models/Service.php:18` — canonical service packages and features.
- `resources/views/bookings/create.blade.php:218` — service cards and feature rendering.
- `app/Http/Controllers/Api/MobileServiceController.php:71` — mobile service evidence payload.
- `tests/Feature/BookingCreationTest.php:224` — service pricing coverage.
- `tests/Feature/CanonicalServiceCatalogTest.php:13` — catalog synchronization coverage.
