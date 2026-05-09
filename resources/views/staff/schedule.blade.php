@extends('layouts.staff')
@section('title', 'My Schedule - Home Cleaning Service')
@section('page-title', 'My Schedule')
@section('page-subtitle', 'Your upcoming cleaning assignments')

@section('content')
@php
    $today = \Carbon\Carbon::today();
    $startOfMonth = $today->copy()->startOfMonth();
    $endOfMonth = $today->copy()->endOfMonth();
    $daysInMonth = $endOfMonth->day;
    $firstDayOfWeek = $startOfMonth->dayOfWeek;
    $todayKey = $today->format('Y-m-d');
    $jobsToday = ($bookingsByDate[$todayKey] ?? collect())->count();
@endphp

<div class="cleanflow-page-shell min-h-[calc(100vh-81px)] px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8 lg:px-10">
            <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl space-y-4">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-calendar-check text-[0.75rem]"></i>
                        Staff schedule
                    </span>
                    <div class="space-y-3">
                        <h1 class="max-w-2xl text-3xl font-black tracking-tight sm:text-4xl">
                            Plan your month with a clearer work calendar
                        </h1>
                        <p class="max-w-2xl text-sm leading-7 text-white/80 sm:text-base">
                            Review your confirmed and in-progress bookings, spot busy days quickly, and keep the next
                            assignments in view before you head out.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm text-white/85">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-calendar-day text-xs"></i>
                            {{ $today->format('F Y') }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-briefcase text-xs"></i>
                            {{ $bookings->count() }} upcoming job{{ $bookings->count() === 1 ? '' : 's' }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-bolt text-xs"></i>
                            {{ $jobsToday }} job{{ $jobsToday === 1 ? '' : 's' }} today
                        </span>
                    </div>
                </div>

                <div class="rounded-2xl border border-white/15 bg-white/10 px-5 py-4 text-center backdrop-blur-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Focus today</div>
                    <div class="mt-2 text-3xl font-black text-white">{{ $jobsToday }}</div>
                    <div class="mt-1 text-sm text-white/75">scheduled assignment{{ $jobsToday === 1 ? '' : 's' }}</div>
                </div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <section class="cleanflow-panel p-6">
                <div class="mb-5 flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ $today->format('F Y') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">A monthly calendar view of your active assignments.</p>
                    </div>
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                        {{ $bookings->count() }} booking{{ $bookings->count() === 1 ? '' : 's' }}
                    </span>
                </div>

                <div class="grid grid-cols-7 gap-2 text-center">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                        <div class="pb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $day }}</div>
                    @endforeach

                    @for ($i = 0; $i < $firstDayOfWeek; $i++)
                        <div class="aspect-square rounded-2xl border border-dashed border-slate-100 bg-transparent"></div>
                    @endfor

                    @for ($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $date = $today->copy()->startOfMonth()->addDays($day - 1);
                            $dateStr = $date->format('Y-m-d');
                            $isToday = $dateStr === $todayKey;
                            $dayBookings = $bookingsByDate[$dateStr] ?? collect();
                            $hasBooking = $dayBookings->isNotEmpty();
                        @endphp
                        <div class="aspect-square rounded-[1.15rem] border p-2 transition {{ $isToday ? 'border-accent-500 bg-accent-500 text-white shadow-lg shadow-accent-200/70' : ($hasBooking ? 'border-primary-200 bg-primary-50 text-primary-700 hover:border-primary-300 hover:bg-primary-100' : 'border-slate-100 bg-slate-50/65 text-slate-500') }}">
                            <div class="flex h-full flex-col items-center justify-center gap-1 text-center">
                                <span class="text-sm font-semibold">{{ $day }}</span>
                                @if ($hasBooking)
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $isToday ? 'bg-white/20 text-white' : 'bg-white text-primary-700 shadow-sm' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $isToday ? 'bg-white' : 'bg-primary-500' }}"></span>
                                        {{ $dayBookings->count() }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endfor
                </div>

                <div class="mt-5 flex flex-wrap gap-3 border-t border-slate-100 pt-5 text-sm text-slate-500">
                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5">
                        <span class="h-3 w-3 rounded-md bg-accent-500"></span>
                        Today
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5">
                        <span class="h-3 w-3 rounded-md bg-primary-200"></span>
                        Has booking
                    </span>
                </div>
            </section>

            <section class="cleanflow-panel overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Upcoming jobs</h2>
                        <p class="mt-1 text-sm text-slate-500">Your confirmed and in-progress assignments in schedule order.</p>
                    </div>
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                        {{ $bookings->count() }} total
                    </span>
                </div>

                <div class="max-h-[720px] overflow-y-auto px-6 py-6">
                    @if ($bookings->count())
                        <div class="space-y-4">
                            @foreach ($bookings as $booking)
                                @php $isToday = \Carbon\Carbon::parse($booking->scheduled_date)->isToday(); @endphp
                                <article class="rounded-[1.4rem] border border-slate-100 bg-slate-50/75 p-5 transition hover:border-slate-200 hover:bg-white hover:shadow-sm">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] {{ $isToday ? 'border border-accent-200 bg-accent-50 text-accent-700' : 'border border-slate-200 bg-white text-slate-500' }}">
                                                <i class="fas {{ $isToday ? 'fa-bolt' : 'fa-calendar-day' }} text-[10px]"></i>
                                                {{ $isToday ? 'Today' : \Carbon\Carbon::parse($booking->scheduled_date)->format('M d, Y') }}
                                            </span>
                                            <h3 class="mt-3 text-base font-bold text-slate-900">{{ $booking->service_label }}</h3>
                                            <p class="mt-1 text-sm text-slate-500">
                                                {{ $booking->user->display_name }}
                                                @if ($booking->user->phone)
                                                    &middot; {{ substr($booking->user->phone, 0, 4) . '****' . substr($booking->user->phone, -3) }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="text-left sm:text-right">
                                            <div class="text-sm font-semibold text-primary-700">
                                                {{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}
                                            </div>
                                            <div class="mt-2 text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400">
                                                CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Address</p>
                                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $booking->street_address }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ ucfirst($booking->barangay) }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Status</p>
                                            <div class="mt-2">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $booking->status === 'confirmed' ? 'border border-accent-200 bg-accent-50 text-accent-700' : 'border border-primary-200 bg-primary-50 text-primary-700' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="py-10 text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 text-primary-600">
                                <i class="fas fa-calendar-days text-xl"></i>
                            </div>
                            <h3 class="mt-4 text-lg font-bold text-slate-900">No upcoming jobs scheduled</h3>
                            <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">
                                New assignments will appear here as soon as they are confirmed.
                            </p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
