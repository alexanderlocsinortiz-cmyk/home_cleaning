@extends('layouts.provider')

@section('title', 'Cleaner Payouts')
@section('page-title', 'Cleaner Payouts')
@section('page-subtitle', 'Track payout records for assigned marketplace work')

@section('content')
<section class="min-h-screen bg-slate-50 px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white shadow-lg shadow-blue-950/10 sm:px-8">
            <div class="cleanflow-hero-content flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-wallet"></i>
                        Cleaner Earnings
                    </span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Payout History</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82">
                        Read-only payout records for <strong>{{ $application->business_name }}</strong>. CleanFlow admin controls payout status and release timing.
                    </p>
                </div>
                <a href="{{ route('provider.dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/15">
                    <i class="fas fa-arrow-left"></i>
                    Dashboard
                </a>
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-black uppercase text-slate-400">Gross</div>
                        <div class="mt-3 text-3xl font-black text-slate-950">&#8369;{{ number_format($payoutStats['gross'], 2) }}</div>
                    </div>
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                        <i class="fas fa-receipt"></i>
                    </span>
                </div>
            </div>
            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-black uppercase text-blue-700">Commission</div>
                        <div class="mt-3 text-3xl font-black text-slate-950">&#8369;{{ number_format($payoutStats['commission'], 2) }}</div>
                    </div>
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-700">
                        <i class="fas fa-percent"></i>
                    </span>
                </div>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-black uppercase text-emerald-700">Cleaner Payout</div>
                        <div class="mt-3 text-3xl font-black text-slate-950">&#8369;{{ number_format($payoutStats['payout'], 2) }}</div>
                    </div>
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-emerald-700">
                        <i class="fas fa-money-bill-transfer"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-4">
            @foreach([
                'pending' => ['label' => 'Pending', 'icon' => 'fa-clock', 'class' => 'bg-amber-50 text-amber-700 border-amber-100'],
                'ready' => ['label' => 'Ready', 'icon' => 'fa-circle-check', 'class' => 'bg-blue-50 text-blue-700 border-blue-100'],
                'paid' => ['label' => 'Paid', 'icon' => 'fa-wallet', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-100'],
                'held' => ['label' => 'Held', 'icon' => 'fa-pause-circle', 'class' => 'bg-rose-50 text-rose-700 border-rose-100'],
            ] as $key => $meta)
                <div class="rounded-2xl border {{ $meta['class'] }} p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-xs font-black uppercase tracking-wide">{{ $meta['label'] }}</div>
                        <i class="fas {{ $meta['icon'] }}"></i>
                    </div>
                    <div class="mt-2 text-xl font-black text-slate-950">&#8369;{{ number_format($payoutStats[$key], 2) }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-black uppercase text-slate-500">Cash Collected</div>
                <div class="mt-2 text-xl font-black text-slate-950">&#8369;{{ number_format($payoutStats['cash_collected'], 2) }}</div>
            </div>
            <div class="rounded-2xl border border-orange-100 bg-orange-50 p-4 shadow-sm">
                <div class="text-xs font-black uppercase text-orange-700">Commission Due</div>
                <div class="mt-2 text-xl font-black text-orange-700">&#8369;{{ number_format($payoutStats['commission_due'], 2) }}</div>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 shadow-sm">
                <div class="text-xs font-black uppercase text-emerald-700">Commission Paid</div>
                <div class="mt-2 text-xl font-black text-emerald-700">&#8369;{{ number_format($payoutStats['commission_paid'], 2) }}</div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-950">Payout Records</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $payouts->total() }} payout record{{ $payouts->total() === 1 ? '' : 's' }} connected to assigned marketplace bookings.</p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-blue-700">
                    <i class="fas fa-shield-halved"></i>
                    Admin controlled
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[920px] w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-black uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Booking</th>
                            <th class="px-5 py-3">Service Date</th>
                            <th class="px-5 py-3 text-right">Gross</th>
                            <th class="px-5 py-3 text-right">Commission</th>
                            <th class="px-5 py-3 text-right">Payout</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($payouts as $booking)
                            <tr class="align-top transition hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <div class="font-mono text-sm font-bold text-blue-700">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $booking->service_label }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-900">{{ $booking->scheduled_date->format('M d, Y') }}</div>
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
                                        <div class="mt-2 text-xs text-slate-500">
                                            Cash collected: &#8369;{{ number_format((float) $booking->cash_collected_amount, 2) }}<br>
                                            Remit: &#8369;{{ number_format((float) $booking->provider_commission_due, 2) }}
                                        </div>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                                            {{ \App\Models\Booking::providerPayoutStatusLabel($booking->provider_payout_status) }}
                                        </span>
                                    @endif
                                    @if($booking->provider_payout_reference || $booking->provider_payout_paid_at)
                                        <div class="mt-2 space-y-1 text-xs text-slate-500">
                                            @if($booking->provider_payout_reference)
                                                <div><span class="font-bold text-slate-700">Ref:</span> {{ $booking->provider_payout_reference }}</div>
                                            @endif
                                            @if($booking->provider_payout_paid_at)
                                                <div><span class="font-bold text-slate-700">Paid:</span> {{ $booking->provider_payout_paid_at->format('M d, Y h:i A') }}</div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('provider.bookings.show', $booking) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition hover:bg-blue-100">
                                        <i class="fas fa-eye"></i>
                                        View booking
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-blue-50 text-3xl text-blue-700 ring-1 ring-blue-100">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <h2 class="mt-4 text-xl font-black text-slate-950">No payout records</h2>
                                    <p class="mx-auto mt-2 max-w-lg text-sm leading-7 text-slate-500">Assigned marketplace bookings with completed payout snapshots will appear here after CleanFlow admin prepares payout details.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $payouts->links('pagination::tailwind') }}
            </div>
        </div>
    </div>
</section>
@endsection
