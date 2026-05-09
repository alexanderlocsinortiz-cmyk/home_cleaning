@extends('layouts.admin')

@section('title', 'Logs')
@section('page-title', 'Logs')
@section('page-subtitle', 'Booking activity audit trail')

@section('content')
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

    <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Booking Today</div>
            <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format($stats['booking_today']) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Attendance Today</div>
            <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format($stats['attendance_today']) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Booking Shown</div>
            <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format($stats['booking_filtered']) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Attendance Shown</div>
            <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format($stats['attendance_filtered']) }}</div>
        </div>
    </section>

    <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach(['all' => 'All Logs', 'bookings' => 'Booking Logs', 'attendance' => 'Attendance Logs'] as $source => $label)
                <a href="{{ route('admin.logs', array_merge(request()->except(['source', 'booking_page', 'attendance_page']), ['source' => $source])) }}"
                   class="rounded-full border px-4 py-2 text-sm font-bold transition {{ $filters['source'] === $source ? 'border-accent-600 bg-accent-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
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
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-accent-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-accent-700">
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
    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-slate-900">Booking Activity Logs</h3>
                <p class="mt-1 text-sm text-slate-500">Status, payment, assignment, review, and booking workflow changes.</p>
            </div>
            <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ number_format($bookingLogs->total()) }} records</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Time</th>
                        <th class="px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Action</th>
                        <th class="px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Details</th>
                        <th class="px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Actor</th>
                        <th class="px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Booking</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($bookingLogs as $log)
                        <tr class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-6 py-4 align-top text-sm">
                                <div class="font-semibold text-slate-900">{{ $log->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-slate-500">{{ $log->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 align-top">
                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                                    {{ str_replace('_', ' ', ucfirst($log->action)) }}
                                </span>
                            </td>
                            <td class="min-w-[280px] px-6 py-4 align-top">
                                <div class="text-sm font-semibold text-slate-900">{{ $log->description }}</div>
                                @if(!empty($log->metadata))
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach($log->metadata as $key => $value)
                                            @if(!is_array($value))
                                                <span class="rounded-full bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-500">
                                                    {{ str_replace('_', ' ', $key) }}: {{ $value ?: 'none' }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 align-top text-sm">
                                <div class="font-semibold text-slate-900">{{ $log->actor_name ?? $log->actor?->display_name ?? 'System' }}</div>
                                <div class="text-xs text-slate-500">{{ $log->actor_role ? ucfirst($log->actor_role) : 'Automated' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 align-top text-sm">
                                @if($log->booking)
                                    <a href="{{ route('bookings.show', $log->booking_id) }}" class="font-bold text-accent-700 hover:text-accent-900">
                                        CF-{{ str_pad($log->booking_id, 5, '0', STR_PAD_LEFT) }}
                                    </a>
                                    <div class="text-xs text-slate-500">{{ $log->booking->user?->display_name ?? 'Unknown client' }}</div>
                                @else
                                    <span class="text-slate-400">Deleted booking</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center">
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
    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-slate-900">Attendance Logs</h3>
                <p class="mt-1 text-sm text-slate-500">Biometric and manual punch activity from staff attendance.</p>
            </div>
            <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ number_format($attendanceLogs->total()) }} records</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Time</th>
                        <th class="px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Punch</th>
                        <th class="px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Staff</th>
                        <th class="px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Device</th>
                        <th class="px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Status</th>
                        <th class="px-6 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Source</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($attendanceLogs as $log)
                        <tr class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-6 py-4 align-top text-sm">
                                <div class="font-semibold text-slate-900">{{ optional($log->logged_at ?? $log->created_at)->format('M d, Y') }}</div>
                                <div class="text-xs text-slate-500">{{ optional($log->logged_at ?? $log->created_at)->format('h:i A') }}</div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 align-top">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $log->punch_type === 'in' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                    Time {{ ucfirst($log->punch_type) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 align-top text-sm">
                                <div class="font-semibold text-slate-900">{{ $log->user?->display_name ?? 'Unknown staff' }}</div>
                                <div class="text-xs text-slate-500">{{ $log->user?->email ?? '-' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 align-top text-sm">
                                <div class="font-semibold text-slate-900">{{ $log->device?->name ?? 'Unknown device' }}</div>
                                <div class="text-xs text-slate-500">{{ $log->device?->serial_number ?? '-' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 align-top">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $log->status === 'late' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ ucfirst($log->status ?? 'present') }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 align-top text-sm font-semibold text-slate-700">
                                {{ ucfirst($log->source ?? 'device') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
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
