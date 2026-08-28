@extends('layouts.admin')

@section('title', 'Cleaner Applications')
@section('page-title', 'Cleaner Applications')
@section('page-subtitle', 'Review independent cleaning teams before they can join the platform')

@section('content')
<div class="admin-page-content cleanflow-page-shell space-y-6 p-6">
    @php
        $statusMeta = [
            'pending' => [
                'label' => 'Pending',
                'description' => 'Awaiting review',
                'icon' => 'fa-hourglass-half',
                'labelColor' => 'text-blue-700',
                'card' => 'hover:border-blue-200 hover:shadow-md',
                'active' => 'border-blue-300 ring-2 ring-blue-100',
                'badge' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-200',
                'iconBox' => 'bg-blue-50 text-blue-700',
            ],
            'approved' => [
                'label' => 'Approved',
                'description' => 'Approved cleaners',
                'icon' => 'fa-circle-check',
                'labelColor' => 'text-emerald-700',
                'card' => 'hover:border-emerald-200 hover:shadow-md',
                'active' => 'border-emerald-300 ring-2 ring-emerald-100',
                'badge' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200',
                'iconBox' => 'bg-emerald-100 text-emerald-700',
            ],
            'rejected' => [
                'label' => 'Rejected',
                'description' => 'Rejected applications',
                'icon' => 'fa-circle-xmark',
                'labelColor' => 'text-rose-700',
                'card' => 'hover:border-rose-200 hover:shadow-md',
                'active' => 'border-rose-300 ring-2 ring-rose-100',
                'badge' => 'bg-rose-100 text-rose-800 ring-1 ring-rose-200',
                'iconBox' => 'bg-rose-100 text-rose-700',
            ],
            'all' => [
                'label' => 'All',
                'description' => 'Total applications',
                'icon' => 'fa-layer-group',
                'labelColor' => 'text-sky-700',
                'card' => 'hover:border-sky-200 hover:shadow-md',
                'active' => 'border-sky-300 ring-2 ring-sky-100',
                'badge' => 'bg-sky-100 text-sky-800 ring-1 ring-sky-200',
                'iconBox' => 'bg-sky-100 text-sky-700',
            ],
        ];
    @endphp

    @if(session('success'))
        <div class="cleanflow-alert cleanflow-alert--success flex items-start gap-3">
            <i class="fas fa-check-circle mt-0.5"></i>
            <div>
                <div class="text-sm font-bold">Action completed</div>
                <div class="text-sm">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="cleanflow-alert cleanflow-alert--error flex items-start gap-3">
            <i class="fas fa-exclamation-triangle mt-0.5"></i>
            <div>
                <div class="text-sm font-bold">Action blocked</div>
                <div class="text-sm">{{ session('error') }}</div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="cleanflow-alert cleanflow-alert--error flex items-start gap-3">
            <i class="fas fa-exclamation-triangle mt-0.5"></i>
            <div>
                <div class="text-sm font-bold">Validation error</div>
                <div class="text-sm">{{ $errors->first() }}</div>
            </div>
        </div>
    @endif

    <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white shadow-lg shadow-blue-950/10 sm:px-8">
        <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <span class="cleanflow-kicker">
                    <i class="fas fa-shield-halved"></i>
                    Marketplace Gatekeeping
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Approve qualified cleaning professionals.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                    Carefully review applications, service areas, and supporting documents to maintain a reliable and professional cleaning marketplace.
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-bold text-white/90">
                        <i class="fas fa-user-check"></i>
                        Individual cleaners
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-bold text-white/90">
                        <i class="fas fa-people-group"></i>
                        Cleaning teams
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-bold text-white/90">
                        <i class="fas fa-lock"></i>
                        Admin controlled
                    </span>
                </div>
            </div>
            <div class="rounded-2xl border border-white/25 bg-white/12 px-5 py-4 shadow-[0_18px_40px_rgba(15,23,42,0.15)] backdrop-blur xl:min-w-[280px]">
                <div class="flex items-center justify-between gap-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Pending Review</div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-300 text-blue-950">
                        <i class="fas fa-hourglass-half"></i>
                    </span>
                </div>
                <div class="mt-2 text-4xl font-black leading-none">{{ number_format($applicationStats['pending']) }}</div>
                <div class="mt-2 text-sm text-white/72">Cleaner teams waiting for admin decision</div>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-4">
        @foreach($statusMeta as $key => $meta)
            <a href="{{ route('admin.cleaner-applications.index', ['status' => $key]) }}" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition {{ $meta['card'] }} {{ $status === $key ? $meta['active'] : '' }}">
                <div class="flex items-start justify-between gap-5">
                    <div>
                        <div class="text-sm font-black uppercase tracking-wide {{ $meta['labelColor'] }}">{{ $meta['label'] }}</div>
                        <div class="mt-5 text-4xl font-black leading-none text-slate-950">{{ number_format($applicationStats[$key]) }}</div>
                        <div class="mt-4 text-sm font-semibold text-slate-600">{{ $meta['description'] }}</div>
                    </div>
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl text-2xl {{ $meta['iconBox'] }} transition group-hover:scale-105">
                        <i class="fas {{ $meta['icon'] }}"></i>
                    </span>
                </div>
            </a>
        @endforeach
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-blue-700">
                    <i class="fas {{ $statusMeta[$status]['icon'] ?? 'fa-layer-group' }}"></i>
                    {{ $statusMeta[$status]['label'] ?? 'All' }} queue
                </div>
                <h3 class="mt-3 text-lg font-extrabold text-slate-900">Applications</h3>
                <p class="mt-1 text-sm text-slate-500">Approve or reject pending cleaner teams. Reviewed applications are locked.</p>
            </div>
            <a href="{{ route('cleaner-applications.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700 transition hover:bg-blue-100">
                <i class="fas fa-external-link-alt"></i>
                Public form
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1100px] w-full text-sm">
                <thead class="border-y border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">
                            <span class="inline-flex items-center gap-2"><i class="fas fa-user-check text-blue-600"></i> Cleaner</span>
                        </th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">
                            <span class="inline-flex items-center gap-2"><i class="fas fa-map-location-dot text-sky-600"></i> Coverage</span>
                        </th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">
                            <span class="inline-flex items-center gap-2"><i class="fas fa-file-lines text-violet-600"></i> Application</span>
                        </th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">
                            <span class="inline-flex items-center gap-2"><i class="fas fa-signal text-amber-600"></i> Status</span>
                        </th>
                        <th class="px-5 py-3 text-right text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">
                            <span class="inline-flex items-center justify-end gap-2"><i class="fas fa-shield-halved text-emerald-600"></i> Review</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($applications as $application)
                        <tr class="align-top transition hover:bg-blue-50/40">
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $application->isTeam() ? 'bg-blue-100 text-blue-700' : 'bg-violet-100 text-violet-700' }}">
                                        <i class="fas {{ $application->isTeam() ? 'fa-people-group' : 'fa-user' }}"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-extrabold text-slate-900">{{ $application->business_name }}</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-600">{{ $application->contact_person }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 inline-flex rounded-full {{ $application->isTeam() ? 'bg-blue-50 text-blue-700 ring-blue-100' : 'bg-violet-50 text-violet-700 ring-violet-100' }} px-3 py-1 text-xs font-black uppercase tracking-wide ring-1">
                                    {{ $application->isTeam() ? 'Cleaning Team' : 'Individual Cleaner' }}
                                </div>
                                <div class="mt-2 space-y-1 text-xs text-slate-500">
                                    <div><i class="fas fa-envelope w-4 text-slate-400"></i>{{ $application->email }}</div>
                                    <div><i class="fas fa-phone w-4 text-slate-400"></i>{{ $application->phone }}</div>
                                    @if($application->current_address)
                                        <div><i class="fas fa-location-dot w-4 text-slate-400"></i>{{ $application->current_address }}</div>
                                    @endif
                                    @if(! $application->isTeam() && $application->date_of_birth)
                                        <div><i class="fas fa-cake-candles w-4 text-slate-400"></i>{{ $application->date_of_birth->format('M d, Y') }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-800">{{ $application->coverageLabel() }}</div>
                                @if(is_array($application->coverage_barangays) && $application->coverage_barangays !== [])
                                    <div class="mt-2 text-xs leading-5 text-slate-500">{{ count($application->coverage_barangays) }} covered area{{ count($application->coverage_barangays) === 1 ? '' : 's' }}</div>
                                @endif
                                <div class="mt-2 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 ring-1 ring-slate-200">
                                    {{ $application->years_experience }} year{{ $application->years_experience === 1 ? '' : 's' }} experience
                                </div>
                                @if($application->isTeam())
                                    <div class="mt-2 inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 ring-1 ring-blue-100">
                                        {{ $application->team_size }} cleaner{{ $application->team_size === 1 ? '' : 's' }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="max-w-sm text-sm leading-6 text-slate-600">{{ $application->services_offered }}</div>
                                @if($application->available_days)
                                    <div class="mt-2 text-xs font-semibold text-slate-500">
                                        <span class="font-bold text-slate-700">Availability:</span>
                                        {{ collect($application->available_days)->map(fn ($day) => \App\Models\CleanerApplication::AVAILABLE_DAY_LABELS[$day] ?? $day)->join(', ') }}
                                    </div>
                                @endif
                                @if($application->max_daily_bookings)
                                    <div class="mt-2 inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 ring-1 ring-indigo-100">
                                        {{ $application->max_daily_bookings >= 5 ? '5+' : $application->max_daily_bookings }} booking{{ $application->max_daily_bookings === 1 ? '' : 's' }} / day
                                    </div>
                                @endif
                                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs leading-5 text-slate-700">
                                    <div class="font-black uppercase tracking-wide text-slate-500">Verification</div>
                                    <div class="mt-1"><span class="font-bold text-slate-800">ID:</span> {{ \App\Models\CleanerApplication::GOVERNMENT_ID_LABELS[$application->government_id_type] ?? 'Not set' }} {{ $application->government_id_number ? '('.$application->government_id_number.')' : '' }}</div>
                                    @if($application->nbi_clearance_number)
                                        <div><span class="font-bold text-slate-800">Clearance:</span> {{ $application->nbi_clearance_number }}</div>
                                    @endif
                                    @php
                                        $applicationFiles = [
                                            'profile-photo' => ['Profile photo', $application->profile_photo_path],
                                            'business-logo' => ['Business logo', $application->business_logo_path],
                                            'government-id' => ['Government ID', $application->government_id_document_path],
                                            'clearance' => ['Clearance', $application->nbi_clearance_document_path],
                                            'selfie-with-id' => ['Selfie with ID', $application->selfie_with_id_path],
                                        ];
                                    @endphp
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($applicationFiles as $fileType => [$fileLabel, $filePath])
                                            @if($filePath)
                                                <a href="{{ route('admin.cleaner-applications.application-files.download', [$application, $fileType]) }}" class="inline-flex rounded-full bg-white px-2.5 py-1 font-bold text-blue-700 ring-1 ring-blue-100 hover:text-blue-900">
                                                    {{ $fileLabel }}
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mt-3 grid gap-2 text-xs text-slate-600 sm:grid-cols-2">
                                    <div class="rounded-lg bg-slate-50 px-3 py-2 ring-1 ring-slate-200">Cleaner before: <span class="font-bold">{{ $application->worked_as_cleaner_before ? 'Yes' : 'No' }}</span></div>
                                    <div class="rounded-lg bg-slate-50 px-3 py-2 ring-1 ring-slate-200">Company before: <span class="font-bold">{{ $application->worked_for_cleaning_company_before ? 'Yes' : 'No' }}</span></div>
                                    <div class="rounded-lg bg-slate-50 px-3 py-2 ring-1 ring-slate-200">Certified: <span class="font-bold">{{ $application->has_cleaning_certifications ? 'Yes' : 'No' }}</span></div>
                                    <div class="rounded-lg bg-slate-50 px-3 py-2 ring-1 ring-slate-200">Own equipment: <span class="font-bold">{{ $application->owns_cleaning_equipment ? 'Yes' : 'No' }}</span></div>
                                </div>
                                @if($application->verification_notes)
                                    <div class="mt-3 rounded-xl border border-blue-100 bg-blue-50/70 p-3 text-xs leading-5 text-slate-700">
                                        <span class="font-bold text-blue-800">Notes:</span> {{ $application->verification_notes }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-black uppercase tracking-wide {{ $statusMeta[$application->status]['badge'] ?? 'bg-slate-100 text-slate-600 ring-1 ring-slate-200' }}">
                                    {{ $application->status }}
                                </span>
                                <div class="mt-2 text-xs font-semibold text-slate-400">Submitted {{ $application->created_at->format('M d, Y') }}</div>
                                @if($application->reviewed_at)
                                    <div class="mt-2 text-xs text-slate-500">
                                        Reviewed {{ $application->reviewed_at->format('M d, Y') }}
                                        @if($application->reviewer)
                                            by {{ $application->reviewer->display_name }}
                                        @endif
                                    </div>
                                @endif
                                @if($application->admin_notes)
                                    <div class="mt-3 text-xs leading-5 text-slate-500">{{ $application->admin_notes }}</div>
                                @endif
                                @if($application->status === \App\Models\CleanerApplication::STATUS_APPROVED)
                                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 text-left">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="text-xs font-black uppercase text-slate-500">Payout setup</div>
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-black ring-1 {{ $application->payoutVerificationBadgeClass() }}">
                                                {{ $application->payoutVerificationStatusLabel() }}
                                            </span>
                                        </div>
                                        <div class="mt-2 space-y-1 text-xs text-slate-600">
                                            <div><span class="font-bold text-slate-800">Method:</span> {{ $application->payoutMethodLabel() }}</div>
                                            <div><span class="font-bold text-slate-800">Account:</span> {{ $application->payout_account_name ?: 'Not set' }}</div>
                                        </div>
                                        @if($application->payoutSetupWarnings() !== [])
                                            <div class="mt-2 text-xs font-semibold text-amber-700">{{ implode(' | ', $application->payoutSetupWarnings()) }}</div>
                                        @endif
                                        <div class="mt-3 space-y-2">
                                            @foreach($application->requiredPayoutDocumentTypes() as $documentType)
                                                @php($document = $application->latestPayoutDocument($documentType))
                                                <div class="flex items-center justify-between gap-2 rounded-lg bg-white px-3 py-2 text-xs ring-1 ring-slate-200">
                                                    <span class="font-bold text-slate-600">{{ \App\Models\CleanerApplicationDocument::TYPE_LABELS[$documentType] }}</span>
                                                    @if($document)
                                                        <a href="{{ route('admin.cleaner-applications.documents.download', [$application, $document]) }}" class="font-bold text-blue-700 hover:text-blue-900">
                                                            Download
                                                        </a>
                                                    @else
                                                        <span class="font-bold text-amber-700">Missing</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if($application->isPending())
                                    <form action="{{ route('admin.cleaner-applications.update', $application) }}" method="POST" class="ml-auto max-w-xs space-y-3">
                                        @csrf
                                        @method('PATCH')
                                        <textarea name="admin_notes" rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-left focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="Admin notes"></textarea>
                                        <div class="flex justify-end gap-2">
                                            <button name="status" value="rejected" type="submit" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-rose-700">
                                                <i class="fas fa-xmark"></i>
                                                Reject
                                            </button>
                                            <button name="status" value="approved" type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700">
                                                <i class="fas fa-check"></i>
                                                Approve
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <div class="space-y-3">
                                        <span class="text-xs font-bold text-slate-400">Decision locked</span>
                                        @if($application->status === \App\Models\CleanerApplication::STATUS_APPROVED)
                                            <form action="{{ route('admin.cleaner-applications.payout-verification', $application) }}" method="POST" class="ml-auto max-w-xs space-y-3">
                                                @csrf
                                                @method('PATCH')
                                                <select name="payout_verification_status" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-left focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100">
                                                    @foreach(\App\Models\CleanerApplication::PAYOUT_VERIFICATION_LABELS as $payoutStatus => $label)
                                                        <option value="{{ $payoutStatus }}" {{ ($application->payout_verification_status ?: \App\Models\CleanerApplication::PAYOUT_VERIFICATION_PENDING) === $payoutStatus ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <textarea name="admin_notes" rows="2" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-left focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="Payout verification notes">{{ $application->admin_notes }}</textarea>
                                                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700">
                                                    <i class="fas fa-shield-check"></i>
                                                    Save payout check
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="bg-gradient-to-b from-white to-slate-50 px-6 py-20 text-center">
                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-blue-50 text-3xl text-blue-700 ring-1 ring-blue-100">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <h4 class="mt-5 text-xl font-black text-slate-900">No cleaner applications here</h4>
                                <p class="mx-auto mt-2 max-w-xl text-sm leading-7 text-slate-500">New individual cleaner and team applications submitted through the public form will appear in this queue.</p>
                                <a href="{{ route('cleaner-applications.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">
                                    <i class="fas fa-plus"></i>
                                    Open application form
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-5 py-4">
            {{ $applications->links('pagination::tailwind') }}
        </div>
    </section>
</div>
@endsection
