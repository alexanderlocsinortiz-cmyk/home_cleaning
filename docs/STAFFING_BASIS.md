# CleanFlow Cleaner Staffing Basis

## Rule used by the system

For a booking, CleanFlow calculates:

    required cleaners = ceiling(total floor area / service capacity per cleaner)

The result is always at least one cleaner. Requests above the configured maximum of 20 cleaners are flagged for manual review.

## Why this basis was selected

The International Sanitary Supply Association (ISSA) explains that cleaning time can be estimated by dividing cleanable area by a production rate, then converting the result to minutes:

<https://www.issa.com/articles/how-to-calculate-cleaning-times/>

ISSA also warns that square-foot pricing alone does not account for the time and conditions needed to clean a space:

<https://canadashow.issa.com/wp-content/uploads/2020/05/ContractorCorner_Fall19_EN.pdf>

APPA's custodial guidance likewise treats square footage per worker as a baseline and says staffing must account for space type, appearance level, workloading, and local operating data:

<https://www.appa.org/cleaning-operations>

The ISSA example of 500 square feet per hour converts to approximately 46.45 square meters per hour. CleanFlow uses that as a reference point, then applies conservative service-specific capacities because deep, move-out, and post-construction work takes more time per square meter than routine cleaning.

## Initial CleanFlow planning capacities

| Service | Capacity per cleaner per visit |
| --- | ---: |
| Basic Clean | 40 sqm |
| Deep Clean | 25 sqm |
| Move-in/Move-out Clean | 40 sqm |
| Post Construction Cleaning | 25 sqm |
| Office Cleaning (Basic) | 60 sqm |
| Office Cleaning (Standard) | 50 sqm |
| Office Cleaning (Deep) | 35 sqm |
| General/Regular Cleaning | 40 sqm |

Examples:

- 100 sqm Basic Clean: ceiling(100 / 40) = 3 cleaners
- 100 sqm Deep Clean: ceiling(100 / 25) = 4 cleaners
- 100 sqm Office Cleaning (Basic): ceiling(100 / 60) = 2 cleaners

These are operational starting assumptions, not a legal or universal industry standard. The admin team should recalibrate them after recording actual time-on-task, property condition, room count, access constraints, equipment, and service quality results.

## Scope limitation

The current booking schema assigns one primary staff_id. Therefore, this feature calculates and displays the required staffing level; it does not silently create multiple staff assignments. Bookings requiring multiple cleaners must be reviewed and assigned by the admin team until a multi-cleaner assignment workflow is added.
