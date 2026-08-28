@extends('layouts.admin')

@section('title', 'Provider Payouts')
@section('page-title', 'Provider Payouts')
@section('page-subtitle', 'Review marketplace gross amounts, commission, and provider payout status')

@section('content')
<div class="admin-page-content cleanflow-page-shell space-y-6 p-6">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-black uppercase text-emerald-700">
                    <i class="fas fa-wallet"></i>
                    Marketplace accounting
                </span>
                <h2 class="mt-3 text-2xl font-black text-slate-950">Provider Payouts</h2>
                <p class="mt-2 text-sm leading-7 text-slate-500">Use this page for payout reconciliation before marking provider payouts as paid.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.provider-payouts.export', request()->only(['provider_id', 'payout_status', 'date_from', 'date_to'])) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100">
                    <i class="fas fa-file-csv"></i>
                    Export CSV
                </a>
                <a href="{{ route('admin.bookings', ['tab' => 'completed']) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700 transition hover:bg-blue-100">
                    <i class="fas fa-calendar-check"></i>
                    Completed bookings
                </a>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-black uppercase text-slate-400">Gross</div>
            <div class="mt-3 text-3xl font-black text-slate-950">&#8369;{{ number_format($payoutSummary['gross'], 2) }}</div>
            <div class="mt-2 text-sm text-slate-500">{{ number_format($payoutSummary['count']) }} payout record{{ $payoutSummary['count'] === 1 ? '' : 's' }}</div>
        </div>
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
            <div class="text-xs font-black uppercase text-blue-700">Commission</div>
            <div class="mt-3 text-3xl font-black text-slate-950">&#8369;{{ number_format($payoutSummary['commission'], 2) }}</div>
            <div class="mt-2 text-sm text-blue-700">CleanFlow platform share</div>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <div class="text-xs font-black uppercase text-emerald-700">Provider Payout</div>
            <div class="mt-3 text-3xl font-black text-slate-950">&#8369;{{ number_format($payoutSummary['payout'], 2) }}</div>
            <div class="mt-2 text-sm text-emerald-700">Amount owed to providers</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-black uppercase text-slate-400">Status Counts</div>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                @foreach(\App\Models\Booking::providerPayoutStatuses() as $status)
                    <div class="rounded-lg bg-slate-50 px-3 py-2">
                        <div class="font-bold text-slate-500">{{ \App\Models\Booking::providerPayoutStatusLabel($status) }}</div>
                        <div class="mt-1 text-lg font-black text-slate-950">{{ number_format($payoutSummary['status_counts'][$status] ?? 0) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-black uppercase text-slate-500">Cash Collected By Providers</div>
            <div class="mt-3 text-2xl font-black text-slate-950">&#8369;{{ number_format($payoutSummary['cash_collected'], 2) }}</div>
        </div>
        <div class="rounded-2xl border border-orange-100 bg-orange-50 p-5 shadow-sm">
            <div class="text-xs font-black uppercase text-orange-700">Commission Due From Providers</div>
            <div class="mt-3 text-2xl font-black text-orange-700">&#8369;{{ number_format($payoutSummary['commission_due'], 2) }}</div>
        </div>
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
            <div class="text-xs font-black uppercase text-emerald-700">Commission Collected</div>
            <div class="mt-3 text-2xl font-black text-emerald-700">&#8369;{{ number_format($payoutSummary['commission_paid'], 2) }}</div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.provider-payouts') }}" class="grid gap-4 lg:grid-cols-[minmax(180px,1fr)_180px_160px_160px_auto] lg:items-end">
            <div>
                <label for="provider_id" class="text-xs font-bold uppercase text-slate-500">Provider</label>
                <select id="provider_id" name="provider_id" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden">
                    <option value="0">All providers</option>
                    @foreach($providers as $provider)
                        <option value="{{ $provider->id }}" {{ (int) $filters['provider_id'] === (int) $provider->id ? 'selected' : '' }}>{{ $provider->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="payout_status" class="text-xs font-bold uppercase text-slate-500">Payout status</label>
                <select id="payout_status" name="payout_status" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden">
                    <option value="">All statuses</option>
                    @foreach(\App\Models\Booking::providerPayoutStatuses() as $status)
                        <option value="{{ $status }}" {{ $filters['payout_status'] === $status ? 'selected' : '' }}>{{ \App\Models\Booking::providerPayoutStatusLabel($status) }}</option>
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
            <table class="min-w-[1100px] w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-black uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Booking</th>
                        <th class="px-5 py-3">Provider</th>
                        <th class="px-5 py-3">Service Date</th>
                        <th class="px-5 py-3 text-right">Gross</th>
                        <th class="px-5 py-3 text-right">Commission</th>
                        <th class="px-5 py-3 text-right">Payout</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payoutRows as $booking)
                        <tr class="align-top transition hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <div class="font-mono text-sm font-bold text-blue-700">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $booking->service_label }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $booking->user?->display_name ?? 'Unknown customer' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900">{{ $booking->cleanerApplication?->business_name ?? 'Unknown provider' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $booking->cleanerApplication?->isTeam() ? 'Cleaning team' : 'Individual cleaner' }}</div>
                                @if($booking->cleanerApplication && $booking->cleanerApplication->payoutSetupWarnings() !== [])
                                    <div class="mt-2 space-y-1">
                                        @foreach($booking->cleanerApplication->payoutSetupWarnings() as $warning)
                                            <div class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700 ring-1 ring-amber-200">
                                                {{ $warning }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900">{{ $booking->scheduled_date?->format('M d, Y') }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}</div>
                            </td>
                            <td class="px-5 py-4 text-right font-bold text-slate-900">&#8369;{{ number_format((float) $booking->provider_gross_amount, 2) }}</td>
                            <td class="px-5 py-4 text-right font-bold text-blue-700">&#8369;{{ number_format((float) $booking->platform_commission_amount, 2) }}</td>
                            <td class="px-5 py-4 text-right font-bold text-emerald-700">&#8369;{{ number_format((float) $booking->provider_payout_amount, 2) }}</td>
                            <td class="px-5 py-4">
                                @if($booking->payment_method === 'on_site_cash')
                                    <span class="inline-flex rounded-full bg-orange-100 px-3 py-1 text-xs font-bold text-orange-700">
                                        {{ \App\Models\Booking::providerCommissionStatusLabel($booking->provider_commission_status) }}
                                    </span>
                                    <div class="mt-2 space-y-1 text-xs text-slate-500">
                                        <div><span class="font-bold text-slate-700">Flow:</span> Cash commission collection</div>
                                        <div><span class="font-bold text-slate-700">Cash:</span> &#8369;{{ number_format((float) $booking->cash_collected_amount, 2) }}</div>
                                        <div><span class="font-bold text-slate-700">Due:</span> &#8369;{{ number_format((float) $booking->provider_commission_due, 2) }}</div>
                                        @if($booking->provider_commission_reference)
                                            <div><span class="font-bold text-slate-700">Ref:</span> {{ $booking->provider_commission_reference }}</div>
                                        @endif
                                        @if($booking->provider_commission_paid_at)
                                            <div><span class="font-bold text-slate-700">Paid:</span> {{ $booking->provider_commission_paid_at->format('M d, Y h:i A') }}</div>
                                        @endif
                                        @if($booking->provider_commission_proof_path)
                                            <a href="{{ route('admin.bookings.provider-commission-proof', $booking->id) }}" class="inline-flex items-center gap-1 font-bold text-orange-700 hover:text-orange-900">
                                                <i class="fas fa-paperclip"></i>
                                                Commission proof
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                                        {{ \App\Models\Booking::providerPayoutStatusLabel($booking->provider_payout_status) }}
                                    </span>
                                @endif
                                @if($booking->provider_payout_reference || $booking->provider_payout_paid_at || $booking->provider_payout_proof_path)
                                    <div class="mt-2 space-y-1 text-xs text-slate-500">
                                        @if($booking->provider_payout_reference)
                                            <div><span class="font-bold text-slate-700">Ref:</span> {{ $booking->provider_payout_reference }}</div>
                                        @endif
                                        @if($booking->provider_payout_paid_at)
                                            <div><span class="font-bold text-slate-700">Paid:</span> {{ $booking->provider_payout_paid_at->format('M d, Y h:i A') }}</div>
                                        @endif
                                        @if($booking->provider_payout_proof_path)
                                            <a href="{{ route('admin.bookings.payout-proof', $booking->id) }}" class="inline-flex items-center gap-1 font-bold text-emerald-700 hover:text-emerald-900">
                                                <i class="fas fa-paperclip"></i>
                                                Proof
                                            </a>
                                        @endif
                                    </div>
                                @endif
                                @if($booking->providerPayoutTransactions->isNotEmpty())
                                    <details class="mt-3 rounded-xl border border-slate-200 bg-white p-2 text-xs text-slate-600">
                                        <summary class="cursor-pointer font-bold text-slate-700">Audit trail ({{ $booking->providerPayoutTransactions->count() }})</summary>
                                        <div class="mt-2 space-y-2">
                                            @foreach($booking->providerPayoutTransactions as $transaction)
                                                <div class="rounded-lg bg-slate-50 p-2">
                                                    <div class="font-bold text-slate-800">
                                                        {{ \App\Models\Booking::providerPayoutStatusLabel($transaction->from_status) }}
                                                        &rarr;
                                                        {{ \App\Models\Booking::providerPayoutStatusLabel($transaction->to_status) }}
                                                    </div>
                                                    <div class="mt-1 text-slate-500">
                                                        {{ $transaction->created_at->format('M d, Y h:i A') }}
                                                        @if($transaction->processor)
                                                            by {{ $transaction->processor->full_name }}
                                                        @endif
                                                    </div>
                                                    @if($transaction->payout_reference)
                                                        <div class="mt-1"><span class="font-bold text-slate-700">Ref:</span> {{ $transaction->payout_reference }}</div>
                                                    @endif
                                                    @if($transaction->payout_paid_at)
                                                        <div class="mt-1"><span class="font-bold text-slate-700">Paid:</span> {{ $transaction->payout_paid_at->format('M d, Y h:i A') }}</div>
                                                    @endif
                                                    @if($transaction->payout_proof_path)
                                                        <a href="{{ route('admin.provider-payout-transactions.proof', $transaction) }}" class="mt-1 inline-flex items-center gap-1 font-bold text-emerald-700 hover:text-emerald-900">
                                                            <i class="fas fa-paperclip"></i>
                                                            Proof snapshot
                                                        </a>
                                                    @endif
                                                    @if($transaction->notes)
                                                        <div class="mt-1 text-amber-700">{{ $transaction->notes }}</div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.bookings', ['tab' => in_array($booking->status, ['completed', 'cancelled'], true) ? 'completed' : 'active']) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition hover:bg-blue-100">
                                    <i class="fas fa-eye"></i>
                                    View booking
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <i class="fas fa-wallet text-2xl"></i>
                                </div>
                                <div class="mt-4 text-lg font-black text-slate-900">No payout records found</div>
                                <p class="mt-2 text-sm text-slate-500">Marketplace bookings with provider commission snapshots will appear here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $payoutRows->links('pagination::tailwind') }}
        </div>
    </section>
</div>
@endsection
