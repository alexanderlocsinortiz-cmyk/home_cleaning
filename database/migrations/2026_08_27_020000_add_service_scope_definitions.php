<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            foreach ([
                'scope_included_areas',
                'scope_included_tasks',
                'scope_excluded_tasks',
                'scope_condition_limits',
                'scope_equipment_policy',
                'scope_access_limits',
                'scope_extra_work_policy',
                'scope_acceptance_criteria',
            ] as $column) {
                if (! Schema::hasColumn('services', $column)) {
                    $table->text($column)->nullable();
                }
            }
        });

        $definitions = [
            'basic' => [
                'scope_included_areas' => 'Accessible living areas, bedrooms, kitchen, and one bathroom within the approved area limit. The customer identifies priority rooms at booking.',
                'scope_included_tasks' => 'Dusting and wiping reachable surfaces; sweeping and mopping hard floors; cleaning reachable bathroom fixtures; wiping kitchen counters, sink, and exterior surfaces; removing ordinary bagged trash.',
                'scope_excluded_tasks' => 'Appliance or cabinet interiors, windows, walls or ceilings, heavy grout or grease removal, mold, pests, biohazards, chemical spills, repairs, high-access work, heavy furniture moving, and bulky-waste removal.',
                'scope_condition_limits' => 'Routine-maintained property with light dust and dirt, reasonable clutter, and no hazardous or specialist condition. Heavy buildup requires Deep Clean, inspection, or re-quote.',
                'scope_equipment_policy' => 'Company brings standard cleaning tools and chemicals. Customer provides safe water, power, access, and property instructions. Specialty equipment, ladders, and PPE require separate approval.',
                'scope_access_limits' => 'Only safe, unlocked, and reasonably accessible areas are included. Staff do not handle fragile valuables, confidential materials, electronics beyond exterior wiping, or heavy items without written approval.',
                'scope_extra_work_policy' => 'Work is limited to the booked duration and approved area. Extra tasks, severe condition, blocked areas, or unfinished priorities require an add-on, manual quote, rebooking, or second visit.',
                'scope_acceptance_criteria' => 'Customer reviews the completed checklist at the end of the visit. Inaccessible, deferred, or customer-declined areas are recorded before completion.',
            ],
            'deep' => [
                'scope_included_areas' => 'Accessible living areas, bedrooms, kitchen, bathrooms, and reachable corners within the approved area and condition limit. Priority areas are confirmed before work starts.',
                'scope_included_tasks' => 'Detailed bathroom and kitchen scrubbing; reachable grime and buildup removal; expanded surface, edge, and corner detailing; floor cleaning; and ordinary bagged-trash removal.',
                'scope_excluded_tasks' => 'Mold remediation, pest or biohazard cleanup, appliance or cabinet interiors, windows, wall or ceiling washing, paint, adhesive or cement removal, high-access work, repairs, heavy lifting, and bulky-waste removal.',
                'scope_condition_limits' => 'Moderate household buildup that standard cleaning chemicals and tools can safely remove. Severe, contaminated, damaged, or specialist conditions require inspection or referral.',
                'scope_equipment_policy' => 'Company brings standard deep-cleaning tools and chemicals. Customer provides safe water, power, access, and instructions. Specialty equipment, ladders, and PPE require separate approval.',
                'scope_access_limits' => 'Areas must be safe, unlocked, and reasonably accessible. Furniture is not moved unless approved; fragile objects, electronics, and personal documents remain the customer\'s responsibility.',
                'scope_extra_work_policy' => 'If condition, area, access, or requested tasks exceed the booked scope or duration, stop and obtain an add-on, inspection, re-quote, rebooking, or second-visit approval.',
                'scope_acceptance_criteria' => 'Customer confirms the priority areas and reviews the deep-clean checklist. Deferred, inaccessible, or excluded work is documented before completion.',
            ],
            'moveinout' => [
                'scope_included_areas' => 'The full accessible interior of an empty or nearly empty property within the approved area limit, including living spaces, bedrooms, kitchen, bathrooms, fixtures, and reachable surfaces.',
                'scope_included_tasks' => 'Turnover dusting and wiping; sweeping and mopping; bathroom and kitchen cleaning; reachable fixture and wall-surface attention; cabinet exteriors; and ordinary bagged-trash removal.',
                'scope_excluded_tasks' => 'Bulk construction or abandoned-item removal, repairs, painting, restoration, exterior high-access work, hazardous materials, damage correction, and appliance or cabinet interiors unless expressly listed in the booking.',
                'scope_condition_limits' => 'Property is empty or reasonably cleared, safe, and ready for cleaning. Furnished, debris-heavy, severely stained, or occupied properties require inspection or re-quote.',
                'scope_equipment_policy' => 'Company brings standard turnover-cleaning tools and chemicals. Customer or property representative provides water, power, access, keys, and handover instructions. Specialty equipment requires approval.',
                'scope_access_limits' => 'All included areas must be unlocked and accessible. Staff do not move heavy furniture, remove fixtures, handle fragile property, or enter unsafe or unfinished areas without written approval.',
                'scope_extra_work_policy' => 'Debris removal, interiors, windows, severe staining, occupied/furnished areas, or landlord-specific standards beyond the checklist require an add-on, inspection, re-quote, or second visit.',
                'scope_acceptance_criteria' => 'The customer or authorized representative reviews the turnover checklist, records excluded or inaccessible areas, and confirms the handover condition before completion.',
            ],
            'postconstruction' => [
                'scope_included_areas' => 'Recently renovated, safe, and substantially finished rooms within the approved area limit, including reachable floors, fixtures, ledges, and surfaces.',
                'scope_included_tasks' => 'Removal of fine construction dust and light debris traces; wiping reachable fixtures, ledges, and surfaces; sweeping and mopping; and final cleanup of recently renovated rooms.',
                'scope_excluded_tasks' => 'Bulk debris hauling, wet cement, cured paint, grout, adhesive or sealant removal, hazardous dust or materials, active construction, repairs, contractor punch-list work, and unsafe or unfinished areas.',
                'scope_condition_limits' => 'Light residue only, with construction complete enough for safe cleaning. Any unknown material, hazardous dust, active work, or heavy residue requires a site-safety inspection and possible specialist referral.',
                'scope_equipment_policy' => 'Company brings standard cleaning tools and chemicals. Customer or contractor provides safe water, power, access, site instructions, and confirmation that the area is safe. Required construction PPE or specialty equipment must be approved first.',
                'scope_access_limits' => 'Only safe, dry, finished, and accessible areas are included. Staff do not enter active work zones, handle unknown substances, climb without approved equipment, or move construction materials.',
                'scope_extra_work_policy' => 'Material removal, disposal, specialist PPE, unsafe access, or work beyond the light-residue scope requires inspection, add-on pricing, re-quote, specialist referral, or a second visit.',
                'scope_acceptance_criteria' => 'An authorized site representative reviews the cleaned areas and checklist, records remaining contractor work or exclusions, and confirms acceptance before completion.',
            ],
            'office-basic' => [
                'scope_included_areas' => 'Accessible reception, work areas, floors, common touchpoints, and one light restroom area within the approved office area and routine-occupancy limit.',
                'scope_included_tasks' => 'Visible-surface wiping; sweeping and mopping; ordinary trash collection; exterior wiping of reachable work surfaces; and light restroom refresh.',
                'scope_excluded_tasks' => 'Confidential-file handling, electronics disassembly, furniture or equipment relocation, high-access work, heavy grease or buildup, exterior windows, specialist sanitation, and bulky or hazardous waste.',
                'scope_condition_limits' => 'Small office with routine occupancy, ordinary trash volume, safe access, and light buildup. Excessive trash, clutter, or sanitation needs require inspection or re-quote.',
                'scope_equipment_policy' => 'Company brings standard cleaning tools and chemicals. The customer provides water, power, access credentials, security instructions, and required consumables unless separately agreed.',
                'scope_access_limits' => 'An authorized office contact identifies areas that may be cleaned. Staff do not open confidential files, handle electronics beyond exterior wiping, move equipment, or enter restricted areas.',
                'scope_extra_work_policy' => 'Extra restrooms, pantry detail, excessive trash, blocked work zones, equipment movement, or work beyond the booked time requires an add-on, re-quote, or second visit.',
                'scope_acceptance_criteria' => 'The authorized office contact reviews the checklist and records locked, restricted, deferred, or customer-declined areas before completion.',
            ],
            'commercial' => [
                'scope_included_areas' => 'Accessible reception, workstations, common areas, pantry, restroom, and customer-facing zones within the approved office area and occupancy limit.',
                'scope_included_tasks' => 'Reception and workstation exterior cleaning; high-touch surface wiping; floor sweeping and mopping; restroom and pantry sanitation; and ordinary trash collection.',
                'scope_excluded_tasks' => 'Confidential-record handling, electronics disassembly, furniture or equipment relocation, high-access work, specialist sanitation, exterior windows, bulky or hazardous waste, and after-hours security duties unless agreed.',
                'scope_condition_limits' => 'Routine office occupancy and buildup with a confirmed access window. Heavy buildup, unusual restroom or pantry conditions, high occupancy, or large trash volume requires inspection or re-quote.',
                'scope_equipment_policy' => 'Company brings standard cleaning tools and chemicals. The customer provides water, power, access credentials, security instructions, and agreed consumables.',
                'scope_access_limits' => 'An authorized office contact identifies cleanable zones and protects confidential materials. Staff do not handle electronics beyond exteriors or enter locked/restricted areas.',
                'scope_extra_work_policy' => 'Additional rooms, restrooms, pantry workload, blocked workstations, equipment movement, special sanitation, or work beyond the access window requires an add-on, re-quote, or second visit.',
                'scope_acceptance_criteria' => 'The authorized office contact reviews the office checklist during the agreed access window and records completed, restricted, or deferred areas.',
            ],
            'office-deep' => [
                'scope_included_areas' => 'Accessible workstations, common areas, reception, pantry, restrooms, floors, fixtures, and high-touch zones within the approved area and moderate-condition limit.',
                'scope_included_tasks' => 'Detailed workstation exterior and high-touch cleaning; restroom and pantry deep sanitation; heavier floor and surface detailing; and reachable edge and fixture cleaning.',
                'scope_excluded_tasks' => 'Electronics disassembly, confidential-file handling, furniture or equipment relocation, high-access work, mold, biohazard, pest or specialist sanitation, exterior windows, and hazardous or bulky waste.',
                'scope_condition_limits' => 'Moderate office buildup that can be handled with standard tools and safe chemicals. Severe buildup, specialist sanitation, heavy clutter, or unsafe access requires inspection or referral.',
                'scope_equipment_policy' => 'Company brings standard deep-cleaning tools and chemicals. The customer provides water, power, access, security instructions, and protection for sensitive equipment. Specialty equipment or PPE requires approval.',
                'scope_access_limits' => 'Work areas must be safe and accessible. Staff do not move heavy equipment, open confidential materials, disassemble electronics, or enter restricted areas.',
                'scope_extra_work_policy' => 'Severe condition, equipment movement, specialist sanitation, blocked zones, or work beyond the booked duration requires an add-on, inspection, re-quote, rebooking, or second visit.',
                'scope_acceptance_criteria' => 'The authorized office contact reviews the deep-clean checklist, records deferred or restricted zones, and confirms acceptance before completion.',
            ],
            'weeklymaintenance' => [
                'scope_included_areas' => 'Accessible routine-use rooms selected by the customer, normally living areas, bedrooms, kitchen, and bathrooms, within the approved area and four-hour workload limit.',
                'scope_included_tasks' => 'General dusting, sweeping, and mopping; routine kitchen and bathroom refresh; reachable surface wiping; and ordinary bagged-trash removal during the booked session.',
                'scope_excluded_tasks' => 'Deep-cleaning buildup, appliance or cabinet interiors, windows, walls and ceilings, heavy lifting, bulky-waste removal, mold, pests, biohazards, chemical spills, repairs, and specialist work.',
                'scope_condition_limits' => 'Routine-maintained property with safe access and a workload reasonably achievable in one standard session of up to four hours. Customers must agree on priorities at arrival.',
                'scope_equipment_policy' => 'Company brings standard cleaning tools and chemicals. Customer provides safe water, power, access, and property instructions. Specialty equipment and PPE require separate approval.',
                'scope_access_limits' => 'Only safe, unlocked, and reasonably accessible areas are included. Staff do not move heavy furniture, handle fragile valuables, or work around unsafe, contaminated, or restricted areas.',
                'scope_extra_work_policy' => 'The session ends at the booked time. Unfinished lower-priority tasks are deferred; excluded tasks, extra time, severe condition, or additional visits require an add-on, overage approval, re-quote, or rebooking.',
                'scope_acceptance_criteria' => 'Customer agrees on priority tasks at the start, reviews the checklist at the end, and records deferred, inaccessible, or customer-declined work before completion.',
            ],
        ];

        foreach ($definitions as $slug => $definition) {
            $service = DB::table('services')->where('slug', $slug)->first();

            if (! $service) {
                continue;
            }

            $updates = [];

            foreach ($definition as $column => $value) {
                if ($service->{$column} === null) {
                    $updates[$column] = $value;
                }
            }

            if ($updates !== []) {
                $updates['updated_at'] = now();
                DB::table('services')->where('id', $service->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            foreach ([
                'scope_acceptance_criteria',
                'scope_extra_work_policy',
                'scope_access_limits',
                'scope_equipment_policy',
                'scope_condition_limits',
                'scope_excluded_tasks',
                'scope_included_tasks',
                'scope_included_areas',
            ] as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
