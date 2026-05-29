@extends('layouts.admin')

@section('title', 'Logs')
@section('page-title', 'Logs')
@section('page-subtitle', 'Booking activity audit trail')

@section('content')
@php
    $actionClasses = [
        'status_updated' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'staff_assigned' => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
        'payment_updated' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'review_updated' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'proof_uploaded' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'rescheduled' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
    ];

    $statusClasses = [
        'pending' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'confirmed' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'in_progress' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'late' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'blocked' => 'bg-rose-50 text-rose-700 ring-rose-200',
    ];

    $labelFor = fn ($value) => str($value)->replace('_', ' ')->title();
@endphp

<div class="admin-page-content cleanflow-page-shell space-y-6">
    <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
        <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <span class="cleanflow-kicker">
                    <i class="fas fa-clipboard-list"></i>
                    Audit Trail
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Track booking changes and staff actions.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                    Review status updates, cleaner assignments, payment changes, manual review decisions, and staff workflow activity.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3 xl:min-w-[420px]">
                <div class="rounded-2xl border border-white/18 bg-white/10 p-4 text-center shadow-[0_18px_40px_rgba(15,23,42,0.15)] backdrop-blur">
                    <div class="text-2xl font-black leading-none">{{ number_format($stats['booking_total']) }}</div>
                    <div class="mt-1 text-[11px] font-bold uppercase tracking-[0.16em] text-white/65">Booking Logs</div>
                </div>
                <div class="rounded-2xl border border-white/18 bg-white/10 p-4 text-center shadow-[0_18px_40px_rgba(15,23,42,0.15)] backdrop-blur">
                    <div class="text-2xl font-black leading-none">{{ number_format($stats['attendance_total']) }}</div>
                    <div class="mt-1 text-[11px] font-bold uppercase tracking-[0.16em] text-white/65">Attendance Logs</div>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 border-l-4 border-l-blue-600 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-400">Booking Today</div>
                    <div class="mt-2 text-4xl font-black leading-none text-slate-900">{{ number_format($stats['booking_today']) }}</div>
                    <div class="mt-2 text-sm text-slate-500">Booking changes recorded today</div>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 border-l-4 border-l-teal-600 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-400">Attendance Today</div>
                    <div class="mt-2 text-4xl font-black leading-none text-slate-900">{{ number_format($stats['attendance_today']) }}</div>
                    <div class="mt-2 text-sm text-slate-500">Staff punches recorded today</div>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-teal-600 text-white">
                    <i class="fas fa-fingerprint"></i>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 border-l-4 border-l-indigo-600 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-400">Booking Shown</div>
                    <div class="mt-2 text-4xl font-black leading-none text-slate-900">{{ number_format($stats['booking_filtered']) }}</div>
                    <div class="mt-2 text-sm text-slate-500">Booking logs in this view</div>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white">
                    <i class="fas fa-list-check"></i>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 border-l-4 border-l-amber-400 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-400">Attendance Shown</div>
                    <div class="mt-2 text-4xl font-black leading-none text-slate-900">{{ number_format($stats['attendance_filtered']) }}</div>
                    <div class="mt-2 text-sm text-slate-500">Attendance logs in this view</div>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-400 text-white">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach(['all' => 'All Logs', 'bookings' => 'Booking Logs', 'attendance' => 'Attendance Logs'] as $source => $label)
                <a href="{{ route('admin.logs', array_merge(request()->except(['source', 'booking_page', 'attendance_page']), ['source' => $source])) }}"
                   class="rounded-full border px-4 py-2 text-sm font-bold transition {{ $filters['source'] === $source ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('admin.logs') }}" class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_220px_180px_auto] xl:items-end">
            <input type="hidden" name="source" value="{{ $filters['source'] }}">
            <div>
                <label for="search" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Search</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                    <input
                        id="search"
                        name="search"
                        value="{{ $filters['search'] }}"
                        placeholder="Search actor, booking, client, barangay, or action..."
                        class="w-full rounded-xl border border-slate-200 py-3 pl-10 pr-4 text-sm text-slate-700 outline-hidden transition focus:border-accent-500 focus:ring-4 focus:ring-accent-100"
                    >
                </div>
            </div>

            <div>
                <label for="action" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Action</label>
                <select id="action" name="action" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-hidden transition focus:border-accent-500 focus:ring-4 focus:ring-accent-100">
                    <option value="">All actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ str_replace('_', ' ', ucfirst($action)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="actor_role" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Actor</label>
                <select id="actor_role" name="actor_role" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-hidden transition focus:border-accent-500 focus:ring-4 focus:ring-accent-100">
                    <option value="">All roles</option>
                    @foreach($actorRoles as $role)
                        <option value="{{ $role }}" @selected($filters['actor_role'] === $role)>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700">
                    <i class="fas fa-filter"></i>
                    Filter
                </button>
                <a href="{{ route('admin.logs') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50">
                    Clear
                </a>
            </div>
        </form>
    </section>

    @if($filters['source'] !== 'attendance')
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-xl font-extrabold text-slate-950">Booking Activity Logs</h3>
                <p class="mt-1 text-sm text-slate-500">Status, payment, assignment, review, and booking workflow changes.</p>
            </div>
            <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-slate-500">
                {{ number_format($bookingLogs->total()) }} records
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="w-[150px] px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Time</th>
                        <th class="w-[170px] px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Action</th>
                        <th class="min-w-[420px] px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Details</th>
                        <th class="w-[180px] px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Actor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($bookingLogs as $log)
                        @php
                            $metadata = collect($log->metadata ?? [])->reject(fn ($value) => is_array($value));
                            $bookingCode = $log->booking_id ? 'CF-'.str_pad($log->booking_id, 5, '0', STR_PAD_LEFT) : null;
                            $contextParts = collect([
                                $bookingCode,
                                $log->booking?->user?->display_name,
                                $log->booking?->barangay,
                            ])->filter();
                            $statusFrom = $metadata->get('from_status') ?? $metadata->get('from_payment_status');
                            $statusTo = $metadata->get('to_status') ?? $metadata->get('to_payment_status') ?? $metadata->get('review_status');
                        @endphp
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-6 py-4 align-top text-sm">
                                <div class="font-semibold text-slate-900">{{ $log->created_at->format('M d, Y') }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $log->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 align-top">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $actionClasses[$log->action] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                    {{ $labelFor($log->action) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="text-sm font-semibold leading-6 text-slate-900">{{ $log->description }}</div>
                                @if($contextParts->isNotEmpty())
                                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs font-medium text-slate-500">
                                        @foreach($contextParts as $part)
                                            <span>{{ $part }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                @if($statusFrom || $statusTo)
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        @if($statusFrom)
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 {{ $statusClasses[$statusFrom] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">{{ $labelFor($statusFrom) }}</span>
                                        @endif
                                        <span class="text-xs text-slate-300">to</span>
                                        @if($statusTo)
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 {{ $statusClasses[$statusTo] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">{{ $labelFor($statusTo) }}</span>
                                        @endif
                                    </div>
                                @endif
                                @if($metadata->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach($metadata->except(['from_status', 'to_status', 'from_payment_status', 'to_payment_status', 'review_status']) as $key => $value)
                                            <span class="rounded-md bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-500 ring-1 ring-slate-100">
                                                {{ $labelFor($key) }}: {{ blank($value) ? 'none' : str_replace('_', ' ', (string) $value) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 align-top text-sm">
                                <div class="font-semibold text-slate-900">{{ $log->actor_name ?? $log->actor?->display_name ?? 'System' }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $log->actor_role ? ucfirst($log->actor_role) : 'Automated' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-14 text-center">
                                <div class="text-sm font-bold text-slate-700">No booking logs found</div>
                                <div class="mt-1 text-sm text-slate-500">Booking activity will appear here after status, payment, staff, or review changes are recorded.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bookingLogs->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">
                {{ $bookingLogs->links('pagination::tailwind') }}
            </div>
        @endif
    </section>
    @endif

    @if($filters['source'] !== 'bookings')
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-xl font-extrabold text-slate-950">Attendance Logs</h3>
                <p class="mt-1 text-sm text-slate-500">Biometric and manual punch activity from staff attendance.</p>
            </div>
            <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-slate-500">
                {{ number_format($attendanceLogs->total()) }} records
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="w-[150px] px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Time</th>
                        <th class="w-[150px] px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Action</th>
                        <th class="min-w-[420px] px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Details</th>
                        <th class="w-[220px] px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Staff</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($attendanceLogs as $log)
                        @php
                            $attendanceStatus = $log->status ?: 'present';
                            $loggedAt = $log->logged_at ?? $log->created_at;
                            $deviceLabel = $log->device
                                ? trim(($log->device->name ?? 'Unknown device').' '.($log->device->serial_number ? '('.$log->device->serial_number.')' : ''))
                                : 'Unknown device';
                            $statusClass = $statusClasses[$attendanceStatus] ?? 'bg-emerald-50 text-emerald-700 ring-emerald-200';
                            $source = $log->source ?? 'device';
                        @endphp
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-6 py-4 align-top text-sm">
                                <div class="font-semibold text-slate-900">{{ optional($loggedAt)->format('M d, Y') }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ optional($loggedAt)->format('h:i A') }}</div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 align-top">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $log->punch_type === 'in' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                    Time {{ ucfirst($log->punch_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="text-sm font-semibold leading-6 text-slate-900">
                                    Staff timed {{ $log->punch_type === 'in' ? 'in' : 'out' }} successfully.
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 {{ $statusClass }}">{{ $labelFor($attendanceStatus) }}</span>
                                    <span class="rounded-md bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-500 ring-1 ring-slate-100">Source: {{ $labelFor($source) }}</span>
                                    <span class="rounded-md bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-500 ring-1 ring-slate-100">Device: {{ $deviceLabel }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 align-top text-sm">
                                <div class="font-semibold text-slate-900">{{ $log->user?->display_name ?? 'Unknown staff' }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $log->user?->email ?? '-' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-14 text-center">
                                <div class="text-sm font-bold text-slate-700">No attendance logs found</div>
                                <div class="mt-1 text-sm text-slate-500">Punch records will appear after staff use the attendance device or manual logs are added.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendanceLogs->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">
                {{ $attendanceLogs->links('pagination::tailwind') }}
            </div>
        @endif
    </section>
    @endif
</div>
@endsection
