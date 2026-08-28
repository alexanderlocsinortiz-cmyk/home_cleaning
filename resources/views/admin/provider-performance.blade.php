@extends('layouts.admin')

@section('title', 'Provider Performance')
@section('page-title', 'Provider Performance')
@section('page-subtitle', 'Compare marketplace provider reliability, quality, and payout performance')

@section('content')
<div class="admin-page-content cleanflow-page-shell space-y-6 p-6">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-black uppercase text-blue-700">
                    <i class="fas fa-chart-line"></i>
                    Marketplace operations
                </span>
                <h2 class="mt-3 text-2xl font-black text-slate-950">Provider Performance</h2>
                <p class="mt-2 text-sm leading-7 text-slate-500">Use raw operating metrics before deciding which providers get more bookings, need coaching, or should be paused.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.cleaner-applications.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700 transition hover:bg-blue-100">
                    <i class="fas fa-clipboard-check"></i>
                    Applications
                </a>
                <a href="{{ route('admin.provider-payouts') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100">
                    <i class="fas fa-wallet"></i>
                    Payouts
                </a>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-black uppercase text-slate-400">Providers</div>
            <div class="mt-3 text-3xl font-black text-slate-950">{{ number_format($summary['providers']) }}</div>
        </div>
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
            <div class="text-xs font-black uppercase text-blue-700">Assigned</div>
            <div class="mt-3 text-3xl font-black text-slate-950">{{ number_format($summary['assigned']) }}</div>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <div class="text-xs font-black uppercase text-emerald-700">Completed</div>
            <div class="mt-3 text-3xl font-black text-slate-950">{{ number_format($summary['completed']) }}</div>
        </div>
        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm">
            <div class="text-xs font-black uppercase text-red-700">Open Disputes</div>
            <div class="mt-3 text-3xl font-black text-slate-950">{{ number_format($summary['open_disputes']) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-black uppercase text-slate-400">Commission</div>
            <div class="mt-3 text-3xl font-black text-slate-950">&#8369;{{ number_format($summary['commission'], 2) }}</div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.provider-performance') }}" class="grid gap-4 lg:grid-cols-[minmax(220px,1fr)_180px_180px_auto] lg:items-end">
            <div>
                <label for="provider_id" class="text-xs font-bold uppercase text-slate-500">Provider</label>
                <select id="provider_id" name="provider_id" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden">
                    <option value="0">All approved providers</option>
                    @foreach($providers as $provider)
                        <option value="{{ $provider->id }}" {{ (int) $filters['provider_id'] === (int) $provider->id ? 'selected' : '' }}>{{ $provider->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="date_from" class="text-xs font-bold uppercase text-slate-500">From</label>
                <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden">
            </div>
            <div>
                <label for="date_to" class="text-xs font-bold uppercase text-slate-500">To</label>
                <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden">
            </div>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-2 text-sm font-bold text-white transition hover:bg-blue-800">
                <i class="fas fa-filter"></i>
                Apply
            </button>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[1280px] w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-black uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Provider</th>
                        <th class="px-5 py-3">Availability</th>
                        <th class="px-5 py-3 text-right">Assignments</th>
                        <th class="px-5 py-3 text-right">Completion</th>
                        <th class="px-5 py-3 text-right">Quality</th>
                        <th class="px-5 py-3 text-right">Issues</th>
                        <th class="px-5 py-3 text-right">Money</th>
                        <th class="px-5 py-3">Payouts</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($providerRows as $provider)
                        @php($metrics = $provider->performance)
                        <tr class="align-top transition hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900">{{ $provider->business_name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $provider->isTeam() ? 'Cleaning team' : 'Individual cleaner' }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $provider->coverageLabel() }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $provider->availabilityBadgeClass() }}">
                                    {{ $provider->availabilityLabel() }}
                                </span>
                                <div class="mt-2 text-xs text-slate-500">Daily limit: {{ $provider->max_daily_bookings ?: 'No limit set' }}</div>
                                <div class="mt-1 text-xs text-slate-500">Payout: {{ $provider->payoutVerificationStatusLabel() }}</div>
                                @if($provider->payoutSetupWarnings() !== [])
                                    <div class="mt-2 space-y-1">
                                        @foreach($provider->payoutSetupWarnings() as $warning)
                                            <div class="inline-flex rounded-full bg-amber-50 px-2 py-1 text-[11px] font-bold text-amber-700 ring-1 ring-amber-200">{{ $warning }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="font-black text-slate-950">{{ number_format($metrics['assigned']) }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ number_format($metrics['accepted']) }} accepted</div>
                                <div class="mt-1 text-xs text-slate-500">{{ number_format($metrics['declined']) }} declined</div>
                                <div class="mt-1 text-xs font-bold text-blue-700">{{ number_format($metrics['acceptance_rate'], 1) }}% acceptance</div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="font-black text-emerald-700">{{ number_format($metrics['completed']) }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ number_format($metrics['active']) }} active</div>
                                <div class="mt-1 text-xs text-slate-500">{{ number_format($metrics['cancelled']) }} cancelled</div>
                                <div class="mt-1 text-xs font-bold text-emerald-700">{{ number_format($metrics['completion_rate'], 1) }}% completion</div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="font-black text-slate-950">{{ $metrics['avg_rating'] !== null ? number_format($metrics['avg_rating'], 1) : 'N/A' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ number_format($metrics['rating_count']) }} rating{{ $metrics['rating_count'] === 1 ? '' : 's' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ number_format($metrics['on_time']) }} on time</div>
                                <div class="mt-1 text-xs font-bold text-amber-700">{{ number_format($metrics['late_rate'], 1) }}% late</div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="font-black {{ $metrics['open_disputes'] > 0 ? 'text-red-700' : 'text-slate-950' }}">{{ number_format($metrics['open_disputes']) }}</div>
                                <div class="mt-1 text-xs text-slate-500">open disputes</div>
                                <div class="mt-1 text-xs text-slate-500">{{ number_format($metrics['disputed']) }} total disputed</div>
                                <div class="mt-1 text-xs font-bold text-red-700">{{ number_format($metrics['dispute_rate'], 1) }}% dispute rate</div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="font-black text-slate-950">&#8369;{{ number_format($metrics['gross'], 2) }}</div>
                                <div class="mt-1 text-xs text-blue-700">Commission &#8369;{{ number_format($metrics['commission'], 2) }}</div>
                                <div class="mt-1 text-xs text-emerald-700">Payout &#8369;{{ number_format($metrics['payout'], 2) }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    @foreach(\App\Models\Booking::providerPayoutStatuses() as $status)
                                        <div class="rounded-lg bg-slate-50 px-2 py-1.5">
                                            <div class="font-bold text-slate-500">{{ \App\Models\Booking::providerPayoutStatusLabel($status) }}</div>
                                            <div class="mt-1 font-black text-slate-950">{{ number_format($metrics['payout_status_counts'][$status] ?? 0) }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <i class="fas fa-chart-line text-2xl"></i>
                                </div>
                                <div class="mt-4 text-lg font-black text-slate-900">No provider performance records</div>
                                <p class="mt-2 text-sm text-slate-500">Approved providers with assigned marketplace bookings will appear here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
