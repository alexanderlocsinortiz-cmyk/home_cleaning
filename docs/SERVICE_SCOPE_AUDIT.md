# Service Scope Audit

Audit date: 2026-08-27.

## Conclusion

The application has a useful service catalog, but the services are not yet confirmed as complete and inclusive. A name, description, three marketing features, price, and duration are not a sufficient scope contract. Promising more than the crew, equipment, time, or price can support will create disputes and unprofitable jobs. Draft scope sheets are now prepared in `docs/SERVICE_SCOPE_SHEETS.md`; they still require owner approval and real-world evidence.

The booking page now shows every catalog feature. That improves disclosure, but it does not replace owner approval of the exact inclusions, exclusions, and provisional capacity assumptions in `docs/SERVICE_SCOPE_SHEETS.md`.

## Package-by-package audit

| Service | Currently declared | Still needs written confirmation |
| --- | --- | --- |
| Basic Clean | Dusting and wipe-down; sweeping and mopping; bathroom and kitchen touch-up | Exact rooms and surfaces; appliance and cabinet coverage; supply/equipment responsibility; condition and time limits; what “touch-up” excludes |
| Deep Clean | Detailed bathroom and kitchen scrubbing; grime and buildup removal; expanded surface and corner detailing | Maximum buildup/soil level; inside-appliance and cabinet rules; high-access work; furniture moving; hazardous or specialist cleaning exclusions |
| Move-in/Move-out Clean | Whole-property turnover cleaning; cabinet, fixture, and wall-surface attention; move-ready presentation | Empty-property assumption; inside-cabinet/window/appliance coverage; wall-material limits; debris disposal; handover acceptance standard; re-clean policy |
| Post Construction Cleaning | Fine construction-dust and debris-trace removal; fixture, ledge, and surface wipe-down; renovated-room cleanup | What counts as construction debris; paint/cement/adhesive removal limits; disposal responsibility; required safety equipment; contractor cleanup boundary; hazardous-material exclusions |
| Office Cleaning (Standard) | Reception/workstation routines; restroom and pantry sanitation; business-hours workflow | Office size and occupancy assumptions; desk/equipment handling; consumables; access/security; frequency and duration; restroom and pantry detail level |
| Office Cleaning (Basic) | Visible-surface wiping; sweeping and mopping; trash collection and light restroom refresh | Trash type/volume; workstation handling; consumables; exact restroom scope; access and closing duties; time or area cap |
| Office Cleaning (Deep) | Detailed workstation/high-touch cleaning; restroom and pantry deep sanitation; heavier floor and surface detailing | Maximum condition and area; equipment/furniture movement; electronics and high-access limits; consumables; specialist sanitation exclusions |
| General/Regular Cleaning | Dusting, sweeping, and mopping; kitchen and bathroom refresh; up to four hours per session | Exact four-hour workload; room/area cap; tasks deferred when time expires; supplies/equipment; recurring-service expectations; overage/rebooking rule |

## Required scope decisions for every service

Before marking the TODO complete, the owner should approve a one-page scope sheet for each package covering:

1. Included rooms, surfaces, fixtures, and standard tasks.
2. Explicit exclusions, including windows, appliance interiors, cabinet interiors, walls, ceilings, stains, mold, pests, biohazards, chemical spills, and construction materials where applicable.
3. Whether the customer or the company supplies water, chemicals, tools, ladders, vacuum equipment, and protective equipment.
4. Expected crew size, maximum floor area or workload, and realistic service duration.
5. Rules for furniture moving, heavy lifting, waste removal, locked or inaccessible areas, and fragile/electronic items.
6. Condition limits, safety requirements, and when a site must be inspected or requoted.
7. Add-on dependencies and the rule for extra work, overage, or a second visit.
8. Customer acceptance criteria and the checklist or photo evidence attached to the completed booking.

## Evidence needed

Application tests prove that the catalog and pricing paths work. They do not prove that a physical job delivered every promised task. For each service, collect a dated and consented example containing:

- before and after photos covering the promised areas;
- a completed checklist tied to the booking and service type;
- property type, floor area, condition, assigned team, and actual duration;
- recorded exclusions or inaccessible areas; and
- customer acceptance, rating, or signed completion confirmation.

Do not reuse a generic marketing photo for every package. It cannot prove that the package is complete or inclusive.

## Release decision

Keep “Confirm that each service is complete and inclusive” open until the scope sheets are approved and at least one real-world evidence set is attached for each package. The current catalog should be treated as a draft service description, not a promise that every possible cleaning task is included.
