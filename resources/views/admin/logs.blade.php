@extends('layouts.admin')

@section('title', 'Logs')
@section('page-title', 'Logs')
@section('page-subtitle', 'Dashboard > Logs')

@section('content')
@php
    $logTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
    $labelFor = fn ($value) => ucfirst(str_replace(['_', '-'], ' ', (string) $value));
    $tabUrl = fn ($source) => route('admin.logs', array_merge(request()->except(['source', 'booking_page', 'attendance_page', 'admin_page', 'security_page']), ['source' => $source]));
    $exportParams = request()->except(['source', 'booking_page', 'attendance_page', 'admin_page', 'security_page']);
    $activeExportSource = $filters['source'] === 'all' ? 'bookings' : $filters['source'];

    $tabs = [
        'bookings' => ['label' => 'Booking Logs', 'icon' => 'fa-clipboard-list', 'count' => $stats['booking_filtered']],
        'attendance' => ['label' => 'Attendance Logs', 'icon' => 'fa-fingerprint', 'count' => $stats['attendance_filtered']],
        'admin' => ['label' => 'Admin Logs', 'icon' => 'fa-user-shield', 'count' => $stats['admin_filtered']],
    ];

    $statusClasses = [
        'pending' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'confirmed' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'late' => 'bg-orange-50 text-orange-700 ring-orange-200',
        'present' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'account_restricted' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'account_restriction_cleared' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'staff_pages_updated' => 'bg-blue-50 text-blue-700 ring-blue-200',
    ];
@endphp

<div class="admin-page-content cleanflow-page-shell space-y-5">
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-700"><i class="fas fa-rectangle-list text-xl"></i></div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Total Logs</div>
                    <div class="text-2xl font-black text-slate-950">{{ number_format($stats['total']) }}</div>
                    <div class="text-xs text-slate-500">All recorded activities</div>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700"><i class="fas fa-user text-xl"></i></div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Attendance Today</div>
                    <div class="text-2xl font-black text-slate-950">{{ number_format($stats['attendance_today']) }}</div>
                    <div class="text-xs text-slate-500">Staff timed in</div>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-50 text-orange-600"><i class="fas fa-triangle-exclamation text-xl"></i></div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Late Arrivals</div>
                    <div class="text-2xl font-black text-slate-950">{{ number_format($stats['late_arrivals']) }}</div>
                    <div class="text-xs text-slate-500">Staff arrived late</div>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-600"><i class="fas fa-circle-xmark text-xl"></i></div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Cancelled Bookings</div>
                    <div class="text-2xl font-black text-slate-950">{{ number_format($stats['cancelled_bookings']) }}</div>
                    <div class="text-xs text-slate-500">This month</div>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-50 text-sky-700"><i class="fas fa-circle-check text-xl"></i></div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Completed Bookings</div>
                    <div class="text-2xl font-black text-slate-950">{{ number_format($stats['completed_bookings']) }}</div>
                    <div class="text-xs text-slate-500">This month</div>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <form method="GET" action="{{ route('admin.logs') }}" class="grid gap-3 lg:grid-cols-[minmax(15rem,1fr)_170px_170px_auto_auto] lg:items-center">
            <input type="hidden" name="source" value="{{ $filters['source'] }}">
            <div class="relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input name="search" value="{{ $filters['search'] }}" placeholder="Search logs..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
            </div>
            <select name="action" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                <option value="">All Actions</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $labelFor($action) }}</option>
                @endforeach
            </select>
            <select name="staff_id" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                <option value="0">All Staff</option>
                @foreach($staff as $member)
                    <option value="{{ $member->id }}" @selected($filters['staff_id'] === $member->id)>{{ $member->display_name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-black text-white transition hover:bg-blue-700">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="{{ route('admin.logs') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-600 transition hover:bg-slate-50">
                    <i class="fas fa-rotate-right"></i>
                </a>
            </div>
            <div class="flex gap-2 lg:justify-end">
                <a href="{{ route('admin.logs.export', array_merge(['source' => $activeExportSource, 'format' => 'pdf'], $exportParams)) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    <i class="fas fa-file-pdf text-red-600"></i> Export PDF
                </a>
                <a href="{{ route('admin.logs.export', array_merge(['source' => $activeExportSource, 'format' => 'excel'], $exportParams)) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    <i class="fas fa-file-excel text-emerald-600"></i> Export Excel
                </a>
            </div>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ $tabUrl('all') }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-black transition {{ $filters['source'] === 'all' ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-slate-50' }}">
                <i class="fas fa-layer-group"></i> All Logs
            </a>
            @foreach($tabs as $source => $tab)
                <a href="{{ $tabUrl($source) }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-black transition {{ $filters['source'] === $source ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-slate-50' }}">
                    <i class="fas {{ $tab['icon'] }}"></i>
                    {{ $tab['label'] }}
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-blue-700">{{ number_format($tab['count']) }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <div class="grid gap-5 {{ $filters['source'] === 'all' ? 'xl:grid-cols-2' : '' }}">
        @if($filters['source'] === 'all' || $filters['source'] === 'bookings')
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-6 py-5">
                    <div>
                        <h3 class="text-lg font-black text-slate-950">Booking Activity Logs</h3>
                        <p class="mt-1 text-sm text-slate-500">Status, payment, assignment, review, and workflow changes.</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ number_format($bookingLogs->total()) }} Records</span>
                        <a href="{{ route('admin.logs.export', array_merge(['source' => 'bookings', 'format' => 'pdf'], $exportParams)) }}" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600 hover:bg-slate-50">PDF</a>
                        <a href="{{ route('admin.logs.export', array_merge(['source' => 'bookings', 'format' => 'excel'], $exportParams)) }}" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600 hover:bg-slate-50">Excel</a>
                    </div>
                </div>
                <div class="relative divide-y divide-slate-100 md:before:absolute md:before:bottom-8 md:before:left-8 md:before:top-8 md:before:w-px md:before:bg-slate-200">
                    @forelse($bookingLogs as $log)
                        @php
                            $metadata = collect($log->metadata ?? [])->reject(fn ($value) => is_array($value));
                            $bookingCode = $log->booking_id ? 'CF-'.str_pad($log->booking_id, 5, '0', STR_PAD_LEFT) : null;
                            $statusFrom = $metadata->get('from_status') ?? $metadata->get('from_payment_status');
                            $statusTo = $metadata->get('to_status') ?? $metadata->get('to_payment_status') ?? $metadata->get('review_status');
                        @endphp
                        <article class="grid gap-4 px-6 py-5 md:grid-cols-[24px_120px_minmax(0,1fr)_150px]">
                            <div class="relative z-10 hidden pt-1 md:block">
                                <span class="block h-4 w-4 rounded-full bg-blue-600 ring-4 ring-blue-50"></span>
                            </div>
                            <div class="text-sm">
                                <div class="font-bold text-slate-900">{{ $log->created_at->copy()->timezone($logTimezone)->format('M d, Y') }}</div>
                                <div class="text-xs text-slate-500">{{ $log->created_at->copy()->timezone($logTimezone)->format('h:i A') }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $log->created_at->diffForHumans() }}</div>
                            </div>
                            <div>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $statusClasses[$log->action] ?? 'bg-blue-50 text-blue-700 ring-blue-200' }}">{{ $labelFor($log->action) }}</span>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $log->description }}</p>
                                <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-500">
                                    @if($bookingCode)<span>{{ $bookingCode }}</span>@endif
                                    @if($log->booking?->user)<span>{{ $log->booking->user->display_name }}</span>@endif
                                    @if($log->booking?->barangay)<span>{{ $log->booking->barangay }}</span>@endif
                                </div>
                                @if($statusFrom || $statusTo)
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        @if($statusFrom)<span class="rounded-md bg-slate-50 px-2 py-1 text-xs font-bold text-slate-600">{{ $labelFor($statusFrom) }}</span>@endif
                                        <span class="text-slate-300">-&gt;</span>
                                        @if($statusTo)<span class="rounded-md bg-rose-50 px-2 py-1 text-xs font-bold text-rose-700">{{ $labelFor($statusTo) }}</span>@endif
                                    </div>
                                @endif
                            </div>
                            <div class="text-sm">
                                <div class="font-bold text-slate-900">{{ $log->actor_name ?? $log->actor?->display_name ?? 'System' }}</div>
                                <div class="text-xs text-slate-500">{{ $log->actor_role ? ucfirst($log->actor_role) : 'Automated' }}</div>
                            </div>
                        </article>
                    @empty
                        <div class="px-6 py-14 text-center text-sm text-slate-500">No booking logs found.</div>
                    @endforelse
                </div>
                @if($bookingLogs->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4">{{ $bookingLogs->links('pagination::tailwind') }}</div>
                @endif
            </section>
        @endif

        @if($filters['source'] === 'all' || $filters['source'] === 'attendance')
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-6 py-5">
                    <div>
                        <h3 class="text-lg font-black text-slate-950">Attendance Logs</h3>
                        <p class="mt-1 text-sm text-slate-500">Biometric and manual punch activity from staff attendance.</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ number_format($attendanceLogs->total()) }} Records</span>
                        <a href="{{ route('admin.logs.export', array_merge(['source' => 'attendance', 'format' => 'pdf'], $exportParams)) }}" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600 hover:bg-slate-50">PDF</a>
                        <a href="{{ route('admin.logs.export', array_merge(['source' => 'attendance', 'format' => 'excel'], $exportParams)) }}" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600 hover:bg-slate-50">Excel</a>
                    </div>
                </div>
                <div class="relative divide-y divide-slate-100 md:before:absolute md:before:bottom-8 md:before:left-8 md:before:top-8 md:before:w-px md:before:bg-slate-200">
                    @forelse($attendanceLogs as $log)
                        @php
                            $loggedAt = $log->logged_at ?? $log->created_at;
                            $loggedAt = $loggedAt?->copy()->timezone(config('cleanflow.attendance_timezone', 'Asia/Manila'));
                            $deviceLabel = $log->device ? trim(($log->device->name ?? 'Device').' '.($log->device->serial_number ? '('.$log->device->serial_number.')' : '')) : 'Unknown device';
                            $attendanceStatus = $log->status ?: 'present';
                        @endphp
                        <article class="grid gap-4 px-6 py-5 md:grid-cols-[24px_120px_minmax(0,1fr)_170px]">
                            <div class="relative z-10 hidden pt-1 md:block">
                                <span class="block h-4 w-4 rounded-full {{ $attendanceStatus === 'late' ? 'bg-orange-500 ring-orange-50' : 'bg-emerald-600 ring-emerald-50' }} ring-4"></span>
                            </div>
                            <div class="text-sm">
                                <div class="font-bold text-slate-900">{{ optional($loggedAt)->format('M d, Y') }}</div>
                                <div class="text-xs text-slate-500">{{ optional($loggedAt)->format('h:i A') }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ optional($loggedAt)->diffForHumans() }}</div>
                            </div>
                            <div>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $log->punch_type === 'in' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-700 ring-slate-200' }}">Time {{ ucfirst($log->punch_type) }}</span>
                                <p class="mt-2 text-sm font-semibold text-slate-900">Staff timed {{ $log->punch_type === 'in' ? 'in' : 'out' }} successfully.</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="rounded-full px-2 py-1 text-xs font-bold ring-1 {{ $statusClasses[$attendanceStatus] ?? 'bg-emerald-50 text-emerald-700 ring-emerald-200' }}">{{ $labelFor($attendanceStatus) }}</span>
                                    <span class="rounded-md bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-500">Source: {{ $labelFor($log->source ?? 'device') }}</span>
                                    <span class="rounded-md bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-500">Device: {{ $deviceLabel }}</span>
                                </div>
                            </div>
                            <div class="text-sm">
                                <div class="font-bold text-slate-900">{{ $log->user?->display_name ?? 'Unknown staff' }}</div>
                                <div class="text-xs text-slate-500">{{ $log->user?->email ?? '-' }}</div>
                            </div>
                        </article>
                    @empty
                        <div class="px-6 py-14 text-center text-sm text-slate-500">No attendance logs found.</div>
                    @endforelse
                </div>
                @if($attendanceLogs->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4">{{ $attendanceLogs->links('pagination::tailwind') }}</div>
                @endif
            </section>
        @endif

        @if($filters['source'] === 'all' || $filters['source'] === 'admin')
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm {{ $filters['source'] === 'all' ? 'xl:col-span-2' : '' }}">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-6 py-5">
                    <div>
                        <h3 class="text-lg font-black text-slate-950">Admin Logs</h3>
                        <p class="mt-1 text-sm text-slate-500">Account restrictions and page-access changes made by administrators.</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ number_format($adminLogs->total()) }} Records</span>
                        <a href="{{ route('admin.logs.export', array_merge(['source' => 'admin', 'format' => 'pdf'], $exportParams)) }}" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600 hover:bg-slate-50">PDF</a>
                        <a href="{{ route('admin.logs.export', array_merge(['source' => 'admin', 'format' => 'excel'], $exportParams)) }}" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600 hover:bg-slate-50">Excel</a>
                    </div>
                </div>
                <div class="relative divide-y divide-slate-100 md:before:absolute md:before:bottom-8 md:before:left-8 md:before:top-8 md:before:w-px md:before:bg-slate-200">
                    @forelse($adminLogs as $log)
                        <article class="grid gap-4 px-6 py-5 md:grid-cols-[24px_120px_minmax(0,1fr)_170px]">
                            <div class="relative z-10 hidden pt-1 md:block">
                                <span class="block h-4 w-4 rounded-full bg-blue-600 ring-4 ring-blue-50"></span>
                            </div>
                            <div class="text-sm">
                                <div class="font-bold text-slate-900">{{ $log->created_at->copy()->timezone($logTimezone)->format('M d, Y') }}</div>
                                <div class="text-xs text-slate-500">{{ $log->created_at->copy()->timezone($logTimezone)->format('h:i A') }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $log->created_at->diffForHumans() }}</div>
                            </div>
                            <div>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $statusClasses[$log->action] ?? 'bg-blue-50 text-blue-700 ring-blue-200' }}">{{ $labelFor($log->action) }}</span>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $log->target_name }} account access changed.</p>
                                <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-500">
                                    <span>{{ $log->target_email }}</span>
                                    <span>{{ ucfirst($log->target_role) }}</span>
                                    @if($log->reason)<span>Reason: {{ $log->reason }}</span>@endif
                                </div>
                            </div>
                            <div class="text-sm">
                                <div class="font-bold text-slate-900">{{ $log->actorUser?->display_name ?? 'System' }}</div>
                                <div class="text-xs text-slate-500">Admin</div>
                            </div>
                        </article>
                    @empty
                        <div class="px-6 py-14 text-center text-sm text-slate-500">No admin logs found.</div>
                    @endforelse
                </div>
                @if($adminLogs->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4">{{ $adminLogs->links('pagination::tailwind') }}</div>
                @endif
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm {{ $filters['source'] === 'all' ? 'xl:col-span-2' : '' }}">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-6 py-5">
                    <div>
                        <h3 class="text-lg font-black text-slate-950">Security Events</h3>
                        <p class="mt-1 text-sm text-slate-500">Authentication, session, password, and device-credential activity.</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ number_format($securityLogs->total()) }} Records</span>
                </div>
                <div class="relative divide-y divide-slate-100 md:before:absolute md:before:bottom-8 md:before:left-8 md:before:top-8 md:before:w-px md:before:bg-slate-200">
                    @forelse($securityLogs as $log)
                        @php($metadata = collect($log->metadata ?? [])->reject(fn ($value) => is_array($value)))
                        <article class="grid gap-4 px-6 py-5 md:grid-cols-[24px_120px_minmax(0,1fr)_200px]">
                            <div class="relative z-10 hidden pt-1 md:block">
                                <span class="block h-4 w-4 rounded-full bg-rose-500 ring-4 ring-rose-50"></span>
                            </div>
                            <div class="text-sm">
                                <div class="font-bold text-slate-900">{{ $log->created_at->copy()->timezone($logTimezone)->format('M d, Y') }}</div>
                                <div class="text-xs text-slate-500">{{ $log->created_at->copy()->timezone($logTimezone)->format('h:i A') }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $log->created_at->diffForHumans() }}</div>
                            </div>
                            <div>
                                <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-200">{{ $labelFor($log->event) }}</span>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $log->user?->display_name ?? 'System or device' }}</p>
                                <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-500">
                                    @if($log->user?->email)<span>{{ $log->user->email }}</span>@endif
                                    @if($log->ip_address)<span>IP: {{ $log->ip_address }}</span>@endif
                                    @foreach($metadata as $key => $value)
                                        <span>{{ $labelFor($key) }}: {{ $value }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="text-sm">
                                <div class="font-bold text-slate-900">{{ $log->user?->role ? ucfirst($log->user->role) : 'Automated' }}</div>
                                <div class="text-xs text-slate-500">{{ $log->user_agent ? str($log->user_agent)->limit(80) : 'No user agent' }}</div>
                            </div>
                        </article>
                    @empty
                        <div class="px-6 py-14 text-center text-sm text-slate-500">No security events found.</div>
                    @endforelse
                </div>
                @if($securityLogs->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4">{{ $securityLogs->links('pagination::tailwind') }}</div>
                @endif
            </section>
        @endif
    </div>

    <div class="rounded-xl bg-blue-50 px-5 py-3 text-sm font-medium text-blue-700">
        <i class="fas fa-circle-info mr-2"></i>
        Security events are retained for {{ config('cleanflow.privacy.security_event_retention_days', 365) }} days. Older events are pruned automatically.
    </div>
</div>
@endsection
