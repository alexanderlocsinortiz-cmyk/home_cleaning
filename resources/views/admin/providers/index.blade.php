@extends('layouts.admin')

@section('title', 'Providers')
@section('page-title', 'Providers')
@section('page-subtitle', 'Manage approved individual cleaners and business teams')

@section('content')
<div class="admin-page-content cleanflow-page-shell space-y-6 p-6">
    @php
        $statusLabels = [
            'approved' => 'Approved',
            'pending' => 'Pending',
            'needs_changes' => 'Needs changes',
            'rejected' => 'Rejected',
            'all' => 'All applications',
        ];
    @endphp

    <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white shadow-lg shadow-blue-950/10 sm:px-8">
        <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <span class="cleanflow-kicker">
                    <i class="fas fa-user-shield"></i>
                    Provider directory
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Find the right provider for every booking.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                    Browse individual cleaners and business teams, check their operating status, and open their performance or payout records. Paused or unavailable providers will not receive new assignments.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.cleaner-applications.index', ['status' => 'pending']) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-black text-blue-800 transition hover:bg-blue-50">
                    <i class="fas fa-clipboard-check"></i>
                    Review applications
                </a>
                <a href="{{ route('admin.bookings') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 py-2.5 text-sm font-black text-white transition hover:bg-white/20">
                    <i class="fas fa-calendar-check"></i>
                    Assign a booking
                </a>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.providers', ['status' => 'approved', 'type' => 'all']) }}" class="group rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $status === 'approved' && $type === 'all' ? 'ring-2 ring-emerald-300' : '' }}">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-xs font-black uppercase tracking-wide text-emerald-700">Approved providers</div>
                    <div class="mt-3 text-3xl font-black text-slate-950">{{ number_format($providerStats['approved']) }}</div>
                    <div class="mt-2 text-xs font-bold text-emerald-700">View directory <i class="fas fa-arrow-right ml-1"></i></div>
                </div>
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-xl text-emerald-700 shadow-sm"><i class="fas fa-circle-check"></i></span>
            </div>
        </a>
        <a href="{{ route('admin.providers', ['status' => 'approved', 'type' => \App\Models\CleanerApplication::TYPE_INDIVIDUAL]) }}" class="group rounded-2xl border border-violet-200 bg-violet-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $status === 'approved' && $type === \App\Models\CleanerApplication::TYPE_INDIVIDUAL ? 'ring-2 ring-violet-300' : '' }}">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-xs font-black uppercase tracking-wide text-violet-700">Individual cleaners</div>
                    <div class="mt-3 text-3xl font-black text-slate-950">{{ number_format($providerStats['individual']) }}</div>
                    <div class="mt-2 text-xs font-bold text-violet-700">View individuals <i class="fas fa-arrow-right ml-1"></i></div>
                </div>
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-xl text-violet-700 shadow-sm"><i class="fas fa-user"></i></span>
            </div>
        </a>
        <a href="{{ route('admin.providers', ['status' => 'approved', 'type' => \App\Models\CleanerApplication::TYPE_TEAM]) }}" class="group rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $status === 'approved' && $type === \App\Models\CleanerApplication::TYPE_TEAM ? 'ring-2 ring-blue-300' : '' }}">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-xs font-black uppercase tracking-wide text-blue-700">Business teams</div>
                    <div class="mt-3 text-3xl font-black text-slate-950">{{ number_format($providerStats['team']) }}</div>
                    <div class="mt-2 text-xs font-bold text-blue-700">View teams <i class="fas fa-arrow-right ml-1"></i></div>
                </div>
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-xl text-blue-700 shadow-sm"><i class="fas fa-people-group"></i></span>
            </div>
        </a>
        <a href="{{ route('admin.providers', ['status' => 'pending', 'type' => 'all']) }}" class="group rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $status === 'pending' ? 'ring-2 ring-amber-300' : '' }}">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-xs font-black uppercase tracking-wide text-amber-700">Pending applications</div>
                    <div class="mt-3 text-3xl font-black text-slate-950">{{ number_format($providerStats['pending']) }}</div>
                    <div class="mt-2 text-xs font-bold text-amber-700">Review queue <i class="fas fa-arrow-right ml-1"></i></div>
                </div>
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-xl text-amber-700 shadow-sm"><i class="fas fa-hourglass-half"></i></span>
            </div>
        </a>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50 px-6 py-4">
            <div class="flex flex-wrap gap-2">
                @foreach([
                    'all' => 'All types',
                    \App\Models\CleanerApplication::TYPE_INDIVIDUAL => 'Individual cleaners',
                    \App\Models\CleanerApplication::TYPE_TEAM => 'Business teams',
                ] as $typeKey => $typeLabel)
                    <a href="{{ route('admin.providers', ['status' => $status, 'type' => $typeKey, 'search' => $search]) }}" class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-black transition {{ $type === $typeKey ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-blue-50 hover:text-blue-700' }}">
                        @if($typeKey === 'all')
                            <i class="fas fa-layer-group"></i>
                        @elseif($typeKey === \App\Models\CleanerApplication::TYPE_INDIVIDUAL)
                            <i class="fas fa-user"></i>
                        @else
                            <i class="fas fa-people-group"></i>
                        @endif
                        {{ $typeLabel }}
                    </a>
                @endforeach
            </div>
        </div>

        <form method="GET" action="{{ route('admin.providers') }}" class="grid gap-3 border-b border-slate-100 bg-white px-6 py-4 lg:grid-cols-[minmax(16rem,1fr)_12rem_auto] lg:items-end">
            <input type="hidden" name="type" value="{{ $type }}">
            <label class="block text-xs font-black uppercase tracking-wide text-slate-500">
                Search providers
                <input name="search" value="{{ $search }}" placeholder="Name, email, phone, area..." class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold normal-case tracking-normal outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
            </label>
            <label class="block text-xs font-black uppercase tracking-wide text-slate-500">
                Status
                <select name="status" class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold normal-case tracking-normal outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                    @foreach($statusLabels as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}" {{ $status === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-sm font-black text-white hover:bg-blue-700"><i class="fas fa-filter"></i> Filter</button>
                <a href="{{ route('admin.providers') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-black text-slate-600 hover:bg-slate-50">Clear</a>
            </div>
        </form>

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
            <div>
                <div class="text-xs font-black uppercase tracking-[0.14em] text-blue-700">{{ $statusLabels[$status] ?? 'Providers' }}</div>
                <div class="mt-1 text-sm font-semibold text-slate-500">{{ number_format($providers->total()) }} result{{ $providers->total() === 1 ? '' : 's' }}{{ $type !== 'all' ? ' · '.($type === \App\Models\CleanerApplication::TYPE_TEAM ? 'Business teams' : 'Individual cleaners') : '' }}</div>
            </div>
            <a href="{{ route('admin.cleaner-applications.index', ['status' => $status, 'search' => $search]) }}" class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-black text-blue-700 transition hover:bg-blue-100">
                <i class="fas fa-clipboard-check"></i>
                Open application records
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1150px] w-full text-sm">
                <thead class="border-y border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Provider</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Type and contact</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Coverage</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Operations</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Payout</th>
                        <th class="px-5 py-3 text-right text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Manage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($providers as $provider)
                        <tr class="align-top transition hover:bg-blue-50/40">
                            <td class="px-5 py-5">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $provider->isTeam() ? 'bg-blue-100 text-blue-700' : 'bg-violet-100 text-violet-700' }}">
                                        <i class="fas {{ $provider->isTeam() ? 'fa-people-group' : 'fa-user' }}"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-extrabold text-slate-900">{{ $provider->business_name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $provider->email }}</div>
                                        <div class="mt-2 inline-flex rounded-full px-2.5 py-1 text-[11px] font-black uppercase tracking-wide {{ $provider->status === \App\Models\CleanerApplication::STATUS_APPROVED ? 'bg-emerald-100 text-emerald-800' : ($provider->status === \App\Models\CleanerApplication::STATUS_PENDING ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">{{ str_replace('_', ' ', ucfirst($provider->status)) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-5">
                                <div class="font-bold text-slate-800">{{ $provider->isTeam() ? 'Business team' : 'Individual cleaner' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $provider->contact_person }}</div>
                                @if($provider->isTeam())
                                    <div class="mt-2 text-xs font-bold text-blue-700">{{ $provider->team_size ?: '—' }} team members</div>
                                @endif
                                <div class="mt-2 text-xs text-slate-400">{{ $provider->phone }}</div>
                            </td>
                            <td class="px-5 py-5">
                                <div class="max-w-[240px] font-semibold leading-5 text-slate-700">{{ $provider->coverageLabel() }}</div>
                                <div class="mt-2 text-xs text-slate-500">{{ $provider->years_experience ?: 0 }} year{{ $provider->years_experience == 1 ? '' : 's' }} experience</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $provider->services_offered ?: 'Services not listed' }}</div>
                            </td>
                            <td class="px-5 py-5">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $provider->availabilityBadgeClass() }}">{{ $provider->availabilityLabel() }}</span>
                                <div class="mt-2 text-xs text-slate-500">Daily limit: {{ $provider->max_daily_bookings ?: 'No limit' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ number_format($provider->active_bookings_count) }} active · {{ number_format($provider->bookings_count) }} total</div>
                                @if($provider->status === \App\Models\CleanerApplication::STATUS_APPROVED)
                                    <form action="{{ route('admin.providers.availability', $provider) }}" method="POST" class="mt-3 space-y-2">
                                        @csrf
                                        @method('PATCH')
                                        <label class="sr-only" for="availability_status_{{ $provider->id }}">Availability for {{ $provider->business_name }}</label>
                                        <select id="availability_status_{{ $provider->id }}" name="availability_status" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs font-bold text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                            @foreach(\App\Models\CleanerApplication::AVAILABILITY_LABELS as $availabilityStatus => $availabilityLabel)
                                                <option value="{{ $availabilityStatus }}" {{ ($provider->availability_status ?: \App\Models\CleanerApplication::AVAILABILITY_AVAILABLE) === $availabilityStatus ? 'selected' : '' }}>{{ $availabilityLabel }}</option>
                                            @endforeach
                                        </select>
                                        <input name="availability_notes" value="{{ $provider->availability_notes }}" maxlength="500" placeholder="Optional note" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-xs text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-black text-white hover:bg-blue-700"><i class="fas fa-save"></i> Save availability</button>
                                    </form>
                                @endif
                                @if(! $provider->user)
                                    <div class="mt-2 text-xs font-bold text-amber-700">Awaiting account activation</div>
                                @endif
                            </td>
                            <td class="px-5 py-5">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $provider->payoutVerificationBadgeClass() }}">{{ $provider->payoutVerificationStatusLabel() }}</span>
                                @foreach(array_slice($provider->payoutSetupWarnings(), 0, 2) as $warning)
                                    <div class="mt-2 text-xs font-semibold text-amber-700">{{ $warning }}</div>
                                @endforeach
                            </td>
                            <td class="px-5 py-5 text-right">
                                <div class="flex flex-col items-end gap-2">
                                    <a href="{{ route('admin.cleaner-applications.index', ['status' => $provider->status, 'search' => $provider->business_name]) }}" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 hover:bg-blue-100"><i class="fas fa-file-lines"></i> Application</a>
                                    @if($provider->status === \App\Models\CleanerApplication::STATUS_APPROVED)
                                        <a href="{{ route('admin.provider-performance', ['provider_id' => $provider->id]) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50"><i class="fas fa-chart-line"></i> Performance</a>
                                        <a href="{{ route('admin.provider-payouts', ['provider_id' => $provider->id]) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50"><i class="fas fa-wallet"></i> Payouts</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="bg-gradient-to-b from-white to-slate-50 px-6 py-20 text-center">
                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-blue-50 text-3xl text-blue-700 ring-1 ring-blue-100"><i class="fas fa-user-shield"></i></div>
                                <h3 class="mt-5 text-xl font-black text-slate-900">No providers found</h3>
                                <p class="mx-auto mt-2 max-w-xl text-sm leading-7 text-slate-500">Approved cleaners and business teams will appear here after their applications are approved.</p>
                                <a href="{{ route('admin.cleaner-applications.index', ['status' => 'pending']) }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700"><i class="fas fa-clipboard-check"></i> Open applications</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($providers->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">{{ $providers->links() }}</div>
        @endif
    </section>
</div>
@endsection
