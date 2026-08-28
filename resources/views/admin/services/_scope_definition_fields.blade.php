@php
    $scopeFields = [
        'scope_included_areas' => ['Included rooms and areas', 'Where the team will work. State the property/room assumptions.'],
        'scope_included_tasks' => ['Included tasks', 'Specific tasks and surfaces included in the package.'],
        'scope_excluded_tasks' => ['Excluded tasks and materials', 'What the customer must not assume is included.'],
        'scope_condition_limits' => ['Condition limits', 'The maximum condition this package handles before inspection or re-quoting.'],
        'scope_equipment_policy' => ['Supplies and equipment', 'What the company brings and what the customer must provide.'],
        'scope_access_limits' => ['Access and safety limits', 'Rules for locked areas, furniture, valuables, electronics, ladders, and unsafe work.'],
        'scope_extra_work_policy' => ['Extra work and re-quote rule', 'What happens when the request exceeds the package, area, condition, or booked time.'],
        'scope_acceptance_criteria' => ['Completion and acceptance', 'How the customer reviews the checklist and how deferred or inaccessible work is recorded.'],
    ];
@endphp

<section class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-4">
    <div class="text-sm font-extrabold text-slate-900">Service Scope Definition</div>
    <p class="mt-1 text-xs leading-5 text-slate-600">Write the actual package boundary here. These fields are customer-facing through the booking flow and are separate from the measurable area limit. Keep Scope Status provisional until the owner approves the wording and real job evidence exists.</p>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        @foreach($scopeFields as $field => [$label, $help])
            <div class="{{ in_array($field, ['scope_included_tasks', 'scope_excluded_tasks', 'scope_extra_work_policy', 'scope_acceptance_criteria'], true) ? 'md:col-span-2' : '' }}">
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">{{ $label }}</label>
                <textarea name="{{ $field }}" rows="{{ in_array($field, ['scope_included_tasks', 'scope_excluded_tasks', 'scope_extra_work_policy', 'scope_acceptance_criteria'], true) ? 4 : 3 }}" maxlength="5000" placeholder="{{ $help }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm leading-6 text-slate-800 focus:border-indigo-500 focus:outline-hidden focus:ring-4 focus:ring-indigo-100">{{ old($field, data_get($scopeService, $field)) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">{{ $help }}</p>
                @error($field)<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>
</section>
