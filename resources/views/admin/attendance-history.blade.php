@extends('layouts.admin')
@section('title', 'Attendance History - Home Cleaning Service Admin')
@section('page-title', 'Attendance Logs')
@section('page-subtitle', 'Complete attendance records of all staff members')

@section('content')
<div class="admin-page-content cleanflow-page-shell space-y-6 p-6">
    <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
        <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <span class="cleanflow-kicker">
                    <i class="fas fa-fingerprint"></i>
                    Attendance Insight
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Review attendance records with clearer operational context.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                    Track staff punch history, lateness patterns, and daily attendance summaries without losing the audit-friendly
                    detail needed for operations and reporting.
                </p>
            </div>
            <div class="flex flex-col gap-2 rounded-3xl border border-white/18 bg-white/10 px-5 py-4 shadow-[0_18px_40px_rgba(15,23,42,0.15)] backdrop-blur xl:min-w-[260px]">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Records in Scope</div>
                <div class="text-4xl font-black leading-none">{{ number_format($logs->total()) }}</div>
                <div class="text-sm text-white/72">Raw punch entries matching the current filters</div>
            </div>
        </div>
    </section>

    <div class="grid gap-5 md:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Total Records</div>
                    <div class="mt-2 text-4xl font-black leading-none text-emerald-600">{{ $totalLogs }}</div>
                    <div class="mt-2 text-sm text-slate-500">All time punches</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                    <i class="fas fa-list-check"></i>
                </div>
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Total Late</div>
                    <div class="mt-2 text-4xl font-black leading-none text-amber-500">{{ $totalLate }}</div>
                    <div class="mt-2 text-sm text-slate-500">Late arrivals recorded</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Staff Tracked</div>
                    <div class="mt-2 text-4xl font-black leading-none text-green-600">{{ $staffList->count() }}</div>
                    <div class="mt-2 text-sm text-slate-500">Total staff members</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-50 text-green-700">
                    <i class="fas fa-user-group"></i>
                </div>
            </div>
        </div>
    </div>

    <section class="rounded-[28px] border border-slate-200 bg-white px-6 py-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-slate-900">Date Shortcuts</h3>
                <p class="mt-1 text-sm text-slate-500">Use a quick range to narrow the attendance view before applying detailed filters.</p>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            @php
                $periodLinkClasses = fn (bool $active) => $active
                    ? 'border-emerald-600 bg-emerald-600 text-white'
                    : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:bg-slate-50';
            @endphp
            <a href="{{ route('admin.attendance.history') }}?period=today" class="rounded-full border px-4 py-2 text-sm font-bold transition {{ $periodLinkClasses(request('period') == 'today') }}">Today</a>
            <a href="{{ route('admin.attendance.history') }}?period=yesterday" class="rounded-full border px-4 py-2 text-sm font-bold transition {{ $periodLinkClasses(request('period') == 'yesterday') }}">Yesterday</a>
            <a href="{{ route('admin.attendance.history') }}?period=this_week" class="rounded-full border px-4 py-2 text-sm font-bold transition {{ $periodLinkClasses(request('period') == 'this_week') }}">This Week</a>
            <a href="{{ route('admin.attendance.history') }}?period=last_week" class="rounded-full border px-4 py-2 text-sm font-bold transition {{ $periodLinkClasses(request('period') == 'last_week') }}">Last Week</a>
            <a href="{{ route('admin.attendance.history') }}?period=this_month" class="rounded-full border px-4 py-2 text-sm font-bold transition {{ $periodLinkClasses(request('period') == 'this_month') }}">This Month</a>
            <a href="{{ route('admin.attendance.history') }}?period=last_month" class="rounded-full border px-4 py-2 text-sm font-bold transition {{ $periodLinkClasses(request('period') == 'last_month') }}">Last Month</a>
            <a href="{{ route('admin.attendance.history') }}" class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-100">All Time</a>
        </div>
    </section>

    <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 px-6 py-5">
            <h3 class="text-lg font-extrabold text-slate-900">Search and Filter Attendance Records</h3>
            <p class="mt-1 text-sm text-slate-500">Refine the log view by staff member, date range, and attendance status.</p>
        </div>
        <form method="GET" action="{{ route('admin.attendance.history') }}" class="space-y-5 px-6 py-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5 xl:items-end">
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Staff Member</label>
                    <select name="staff_id" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                        <option value="">All staff</option>
                        @foreach($staffList as $staff)
                            <option value="{{ $staff->id }}" {{ request('staff_id') == $staff->id ? 'selected' : '' }}>
                                {{ $staff->first_name }} {{ $staff->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Status</label>
                    <select name="status" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                        <option value="">Any attendance status</option>
                        <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                        <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                    </select>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-full bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-700">
                        <i class="fas fa-filter"></i>
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.attendance.history') }}" class="inline-flex flex-1 items-center justify-center gap-2 rounded-full border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                        <i class="fas fa-rotate-left"></i>
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </section>

    <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-slate-900">Daily Attendance Summary</h3>
                <p class="mt-1 text-sm text-slate-500">Time in and time out per day per staff.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[720px] w-full text-sm">
                <thead class="bg-slate-50/90">
                    <tr>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Staff</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Date</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Time In</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Time Out</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Hours Worked</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summaries as $summary)
                        <tr class="border-t border-slate-100 transition hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-600 text-sm font-black text-white">
                                        {{ strtoupper(substr($summary->user->first_name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div class="font-semibold text-slate-900">{{ $summary->user->first_name ?? 'Unknown' }} {{ $summary->user->last_name ?? '' }}</div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900">{{ $summary->display_date->format('M d, Y') }}</div>
                                <div class="text-xs text-slate-400">{{ $summary->display_date->format('l') }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-semibold {{ $summary->display_time_in ? 'text-green-600' : 'text-slate-400' }}">{{ $summary->display_time_in ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-semibold {{ $summary->display_time_out ? 'text-red-600' : 'text-slate-400' }}">{{ $summary->display_time_out ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-semibold {{ $summary->hours_worked ? 'text-slate-900' : 'text-slate-400' }}">{{ $summary->hours_worked ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @if($summary->display_status === 'present')
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Present</span>
                                @elseif($summary->display_status === 'late')
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">Late</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Unknown</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
                                <div class="text-sm font-bold text-slate-700">No attendance records match your current filters</div>
                                <div class="mt-1 text-sm text-slate-400">Try adjusting the date range or staff filters.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $summaries->links('pagination::tailwind') }}
        </div>
    </section>

    <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 px-6 py-5">
            <h3 class="text-lg font-extrabold text-slate-900">Raw Punch Logs</h3>
            <p class="mt-1 text-sm text-slate-500">Every individual fingerprint scan recorded.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[920px] w-full text-sm">
                <thead class="bg-slate-50/90">
                    <tr>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">#</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Staff</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Punch Type</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Date and Time</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Status</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Device</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Source</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="border-t border-slate-100 transition hover:bg-slate-50/70">
                            <td class="px-5 py-4 text-sm font-semibold text-slate-400">{{ $log->id }}</td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900">{{ $log->user->first_name ?? 'Unknown' }} {{ $log->user->last_name ?? '' }}</div>
                                <div class="text-xs text-slate-400">{{ $log->user->email ?? '' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                @if($log->punch_type === 'in')
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Time In</span>
                                @else
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">Time Out</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900">{{ $log->display_logged_at_date }}</div>
                                <div class="text-xs text-slate-500">{{ $log->display_logged_at_time }}</div>
                            </td>
                            <td class="px-5 py-4">
                                @if($log->display_status === 'present')
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Present</span>
                                @elseif($log->display_status === 'late')
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">Late</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-sm text-slate-700">{{ $log->device->name ?? 'Unknown Device' }}</div>
                                <div class="text-xs text-slate-400">{{ $log->device->serial_number ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                                    {{ ucfirst($log->source) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <div class="text-sm font-bold text-slate-700">No punch logs match your current filters</div>
                                <div class="mt-1 text-sm text-slate-400">Recorded scans will appear here once attendance activity is available.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $logs->links('pagination::tailwind') }}
        </div>
    </section>
</div>
@endsection
