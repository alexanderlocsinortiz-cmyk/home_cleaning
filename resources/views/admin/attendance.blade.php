@extends('layouts.admin')
@section('title', 'Attendance - Home Cleaning Service Admin')
@section('page-title', 'Staff Attendance')
@section('page-subtitle', 'Today\'s attendance overview - ' . $attendanceDate->format('F d, Y'))

@section('content')
@php
    $staffWithoutFingerprint = $staff->filter(fn ($member) => $member->fingerprint_template_id === null)->count();
    $activeAttendanceTab = request('tab') === 'history' ? 'history' : 'today';
@endphp

<div class="admin-page-content cleanflow-page-shell space-y-6 p-6">
    @if(session('success'))
        <div class="cleanflow-alert cleanflow-alert--success flex items-start gap-3">
            <i class="fas fa-check-circle mt-0.5"></i>
            <div>
                <div class="text-sm font-bold">Attendance updated</div>
                <div class="text-sm">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="cleanflow-alert cleanflow-alert--error">
            <div class="text-sm font-bold">Please review the attendance forms.</div>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li class="flex items-start gap-2">
                        <i class="fas fa-circle text-[7px] mt-2"></i>
                        <span>{{ $error }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
        <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <span class="cleanflow-kicker">
                    <i class="fas fa-fingerprint"></i>
                    Attendance Control Center
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Monitor attendance, device readiness, and fingerprint enrollment from one cleaner workspace.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                    Generate device tokens, launch enrollment requests, and review today&apos;s staffing availability without bouncing between separate admin tools.
                </p>
            </div>
            <div class="flex flex-col gap-2 rounded-3xl border border-white/18 bg-white/10 px-5 py-4 shadow-[0_18px_40px_rgba(15,23,42,0.15)] backdrop-blur xl:min-w-[280px]">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Attendance Snapshot</div>
                <div class="text-4xl font-black leading-none">{{ $attendanceDate->format('d') }}</div>
                <div class="text-sm text-white/72">{{ $attendanceDate->format('l, F Y') }}</div>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-bold text-white/85">
                        <i class="fas fa-microchip"></i>
                        {{ $devices->count() }} device{{ $devices->count() === 1 ? '' : 's' }}
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-bold text-white/85">
                        <i class="fas fa-users"></i>
                        {{ $attendance->count() }} staff tracked
                    </span>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap gap-3">
            <button
                type="button"
                data-attendance-tab-target="today"
                class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition {{ $activeAttendanceTab === 'today' ? 'border-accent-200 bg-accent-50 text-accent-700' : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100' }}"
            >
                <i class="fas fa-clipboard-check"></i>
                Today&apos;s Attendance
            </button>
            <button
                type="button"
                data-attendance-tab-target="history"
                class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition {{ $activeAttendanceTab === 'history' ? 'border-accent-200 bg-accent-50 text-accent-700' : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100' }}"
            >
                <i class="fas fa-clock-rotate-left"></i>
                History and Logs
            </button>
        </div>
    </section>

    <div data-attendance-tab-panel="today" class="space-y-6 {{ $activeAttendanceTab === 'today' ? '' : 'hidden' }}">

    @if(session('generated_device_token'))
        <section class="rounded-[30px] bg-slate-950 px-6 py-6 text-white shadow-[0_24px_60px_rgba(15,23,42,0.18)]">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-emerald-300">Generated Device Token</div>
                    <p class="mt-3 text-sm leading-7 text-slate-300">
                        Copy this token into <code class="rounded bg-white/10 px-1.5 py-0.5 text-white">DEVICE_TOKEN</code> in your ESP32 sketch.
                        The full value is only shown right after generation or token rotation.
                    </p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
                    <div class="font-bold">{{ session('generated_device_name') }}</div>
                    <div class="mt-1 text-xs text-slate-400">Serial: {{ session('generated_device_serial') }}</div>
                </div>
            </div>
            <div class="mt-5 rounded-3xl border border-white/10 bg-white/5 px-4 py-4 font-mono text-sm break-all text-emerald-100">
                {{ session('generated_device_token') }}
            </div>
        </section>
    @endif

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-1">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Present Today</div>
                    <div class="mt-2 text-4xl font-black leading-none text-slate-900">{{ $presentCount }}</div>
                    <div class="mt-2 text-sm text-slate-500">Staff already clocked in.</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-1">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Absent Today</div>
                    <div class="mt-2 text-4xl font-black leading-none text-slate-900">{{ $absentCount }}</div>
                    <div class="mt-2 text-sm text-slate-500">No time-in record yet.</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-danger-50 text-danger-700">
                    <i class="fas fa-user-slash"></i>
                </div>
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-1">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Late Today</div>
                    <div class="mt-2 text-4xl font-black leading-none text-slate-900">{{ $lateCount }}</div>
                    <div class="mt-2 text-sm text-slate-500">Arrived after 8:00 AM.</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-1">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Ready For Enrollment</div>
                    <div class="mt-2 text-4xl font-black leading-none text-slate-900">{{ $staffWithoutFingerprint }}</div>
                    <div class="mt-2 text-sm text-slate-500">Staff without fingerprint templates.</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-700">
                    <i class="fas fa-fingerprint"></i>
                </div>
            </div>
        </div>
    </div>

    <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-slate-900">Attendance Device Token Generator</h3>
                <p class="mt-1 text-sm text-slate-500">Register an ESP32-based biometric device and generate the token that will be pasted into the device sketch.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-600">
                <i class="fas fa-microchip text-slate-400"></i>
                {{ $devices->count() }} registered device{{ $devices->count() === 1 ? '' : 's' }}
            </div>
        </div>

        <form method="POST" action="{{ route('admin.attendance.devices.store') }}" class="space-y-6 px-6 py-6">
            @csrf
            <div class="grid gap-4 lg:grid-cols-3">
                <div>
                    <label for="name" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Device Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Front Desk Device" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100" required>
                </div>
                <div>
                    <label for="serial_number" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Serial Number</label>
                    <input id="serial_number" name="serial_number" type="text" value="{{ old('serial_number') }}" placeholder="ESP32-FRONT-01" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100" required>
                </div>
                <div>
                    <label for="location" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Location</label>
                    <input id="location" name="location" type="text" value="{{ old('location') }}" placeholder="Main Office" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                </div>
            </div>

            <div class="flex flex-col gap-3 rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <p class="text-sm leading-7 text-slate-500">
                    The generated token becomes the value of <code class="rounded bg-white px-1.5 py-0.5 text-slate-700">DEVICE_TOKEN</code> in the ESP32 sketch.
                </p>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                    <i class="fas fa-key"></i>
                    Generate Device Token
                </button>
            </div>
        </form>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
        <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Fingerprint Enrollment</h3>
                    <p class="mt-1 text-sm text-slate-500">Create the enrollment request from the website, then ask the staff member to place the same finger twice on the selected device.</p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-600">
                    <i class="fas fa-user-plus text-slate-400"></i>
                    {{ $staffWithoutFingerprint }} staff ready
                </div>
            </div>

            <form method="POST" action="{{ route('admin.attendance.enrollments.store') }}" class="space-y-6 px-6 py-6">
                @csrf
                <div class="grid gap-4 lg:grid-cols-3">
                    <div>
                        <label for="device_id" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Device</label>
                        <select id="device_id" name="device_id" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100" required>
                            <option value="">Select a device</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}" @selected((string) old('device_id') === (string) $device->id)>
                                    {{ $device->name }} ({{ $device->serial_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="user_id" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Staff Member</label>
                        <select id="user_id" name="user_id" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100" required>
                            <option value="">Select staff</option>
                            @foreach($staff as $staffMember)
                                @if($staffMember->fingerprint_template_id === null)
                                    <option value="{{ $staffMember->id }}" @selected((string) old('user_id') === (string) $staffMember->id)>
                                        {{ $staffMember->display_name }} ({{ $staffMember->username }})
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="template_id" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Fingerprint Slot</label>
                        <input id="template_id" name="template_id" type="number" min="1" max="162" value="{{ old('template_id') }}" placeholder="1" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100" required>
                    </div>
                </div>

                <div class="flex flex-col gap-3 rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <p class="text-sm leading-7 text-slate-500">
                        The website submits the request to the ESP32 queue. The browser does not communicate with the AS608 device directly.
                    </p>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full bg-cyan-700 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-cyan-800">
                        <i class="fas fa-fingerprint"></i>
                        Start Enrollment
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Recent Enrollment Queue</h3>
                    <p class="mt-1 text-sm text-slate-500">Newest requests stay on top so the admin team can monitor progress quickly.</p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full border border-secondary-200 bg-secondary-50 px-3 py-1 text-xs font-bold text-secondary-700">
                    <i class="fas fa-layer-group"></i>
                    Latest {{ $recentEnrollmentRequests->count() }}
                </span>
            </div>

            <div class="max-h-[430px] space-y-4 overflow-y-auto px-6 py-6">
                @forelse($recentEnrollmentRequests as $request)
                    @php
                        $queueBadgeClasses = match ($request->status) {
                            'completed' => 'border-accent-300 bg-accent-100 text-accent-800',
                            'failed' => 'border-danger-200 bg-danger-50 text-danger-700',
                            'in_progress' => 'border-primary-200 bg-primary-50 text-primary-700',
                            default => 'border-amber-200 bg-amber-50 text-amber-700',
                        };
                    @endphp
                    <article class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="font-bold text-slate-900">{{ $request->user->display_name }} &bull; Slot #{{ $request->template_id }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ $request->device->name }} &bull; Requested by {{ $request->requestedBy?->display_name ?? 'Admin' }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $request->created_at->diffForHumans() }}</div>
                            </div>
                            <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold capitalize {{ $queueBadgeClasses }}">
                                <i class="fas fa-wave-square"></i>
                                {{ str_replace('_', ' ', $request->status) }}
                            </span>
                        </div>
                        @if($request->error_message)
                            <div class="mt-3 rounded-2xl border border-red-200 bg-red-50 px-3 py-3 text-sm text-red-700">
                                {{ $request->error_message }}
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="flex min-h-[220px] items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 text-center">
                        <div>
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                                <i class="fas fa-fingerprint text-xl"></i>
                            </div>
                            <h4 class="mt-4 text-base font-extrabold text-slate-900">No enrollment requests yet</h4>
                            <p class="mt-2 text-sm leading-7 text-slate-500">New fingerprint enrollment requests will appear here after they are submitted from the form.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-slate-900">Today&apos;s Attendance</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $attendanceDate->format('l, F d, Y') }} staffing availability and punch activity.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-600">
                <i class="fas fa-clipboard-list text-slate-400"></i>
                {{ $attendance->count() }} total staff
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50/90">
                    <tr>
                        <th class="px-6 py-4 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Staff</th>
                        <th class="px-6 py-4 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Status</th>
                        <th class="px-6 py-4 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Time In</th>
                        <th class="px-6 py-4 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Time Out</th>
                        <th class="px-6 py-4 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Availability</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($attendance as $a)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-600 text-sm font-black text-white shadow-sm">
                                        {{ strtoupper(substr($a['name'], 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $a['name'] }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $a['email'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                @if($a['status'] === 'present')
                                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                        <i class="fas fa-circle-check"></i>
                                        Present
                                    </span>
                                @elseif($a['status'] === 'late')
                                    <span class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">
                                        <i class="fas fa-clock"></i>
                                        Late
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-2 rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-bold text-red-700">
                                        <i class="fas fa-circle-xmark"></i>
                                        Absent
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-5">
                                <span class="font-semibold {{ $a['time_in'] ? 'text-slate-900' : 'text-slate-400' }}">
                                    {{ $a['time_in'] ?? '--' }}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="font-semibold {{ $a['time_out'] ? 'text-slate-900' : 'text-slate-400' }}">
                                    {{ $a['time_out'] ?? '--' }}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                @if($a['is_present'])
                                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                        <i class="fas fa-user-check"></i>
                                        Available for assignment
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">
                                        <i class="fas fa-user-slash"></i>
                                        Not available
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                                    <i class="fas fa-clipboard-list text-2xl"></i>
                                </div>
                                <h4 class="mt-5 text-lg font-extrabold text-slate-900">No attendance records today</h4>
                                <p class="mx-auto mt-2 max-w-xl text-sm leading-7 text-slate-500">Staff attendance entries will appear here after time-in activity is recorded by the biometric devices.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-slate-900">Biometric Device Status</h3>
                <p class="mt-1 text-sm text-slate-500">Review device heartbeat, token visibility, and rotation controls from one list.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-600">
                <i class="fas fa-satellite-dish text-slate-400"></i>
                {{ $devices->whereNotNull('last_seen_at')->count() }} device{{ $devices->whereNotNull('last_seen_at')->count() === 1 ? '' : 's' }} seen before
            </div>
        </div>

        <div class="space-y-4 px-6 py-6">
            @forelse($devices as $device)
                @php
                    $isOnline = $device->last_seen_at && $device->last_seen_at->diffInMinutes(now()) < 30;
                @endphp
                <article class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="font-bold text-slate-900">{{ $device->name }}</div>
                                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold {{ $isOnline ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-500' }}">
                                    <span class="h-2 w-2 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $isOnline ? 'Recently active' : 'Offline / idle' }}
                                </span>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                <div class="rounded-2xl border border-white bg-white px-4 py-3">
                                    <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Location</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-800">{{ $device->location ?: 'No location set' }}</div>
                                </div>
                                <div class="rounded-2xl border border-white bg-white px-4 py-3">
                                    <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Serial Number</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-800">{{ $device->serial_number }}</div>
                                </div>
                                <div class="rounded-2xl border border-white bg-white px-4 py-3">
                                    <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Token Preview</div>
                                    <div class="mt-2 font-mono text-sm font-semibold text-slate-800">{{ str_repeat('*', 12) }}{{ substr($device->api_token, -8) }}</div>
                                </div>
                            </div>
                            <div class="text-sm text-slate-500">
                                Last seen: {{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Never connected' }}
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.attendance.devices.rotate-token', $device) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                                <i class="fas fa-rotate"></i>
                                Rotate Token
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-slate-400 shadow-sm">
                        <i class="fas fa-microchip text-2xl"></i>
                    </div>
                    <h4 class="mt-5 text-lg font-extrabold text-slate-900">No biometric devices connected yet</h4>
                    <p class="mx-auto mt-2 max-w-xl text-sm leading-7 text-slate-500">Generate a device token first so your ESP32 attendance hardware can register and begin reporting attendance events.</p>
                </div>
            @endforelse
        </div>
    </section>

    </div>

    <div data-attendance-tab-panel="history" class="space-y-6 {{ $activeAttendanceTab === 'history' ? '' : 'hidden' }}">
        <section class="grid gap-5 md:grid-cols-3">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Total Records</div>
                <div class="mt-2 text-4xl font-black leading-none text-accent-700">{{ number_format($totalLogs) }}</div>
                <div class="mt-2 text-sm text-slate-500">All attendance punch records captured so far.</div>
            </article>
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Late Logs</div>
                <div class="mt-2 text-4xl font-black leading-none text-amber-600">{{ number_format($totalLate) }}</div>
                <div class="mt-2 text-sm text-slate-500">Time-in records flagged as late arrivals.</div>
            </article>
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Staff Tracked</div>
                <div class="mt-2 text-4xl font-black leading-none text-emerald-700">{{ number_format($staffList->count()) }}</div>
                <div class="mt-2 text-sm text-slate-500">Staff members included in attendance logs.</div>
            </article>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white px-6 py-5 shadow-sm">
            <h3 class="text-lg font-extrabold text-slate-900">Date Shortcuts</h3>
            <p class="mt-1 text-sm text-slate-500">Quickly jump to common history windows.</p>
            @php
                $periodLinkClasses = fn (bool $active) => $active
                    ? 'border-accent-200 bg-accent-50 text-accent-700'
                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50';
            @endphp
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('admin.attendance', array_merge(request()->except(['period', 'logs_page', 'summaries_page']), ['tab' => 'history', 'period' => 'today'])) }}" class="rounded-full border px-4 py-2 text-sm font-semibold transition {{ $periodLinkClasses(request('period') === 'today') }}">Today</a>
                <a href="{{ route('admin.attendance', array_merge(request()->except(['period', 'logs_page', 'summaries_page']), ['tab' => 'history', 'period' => 'yesterday'])) }}" class="rounded-full border px-4 py-2 text-sm font-semibold transition {{ $periodLinkClasses(request('period') === 'yesterday') }}">Yesterday</a>
                <a href="{{ route('admin.attendance', array_merge(request()->except(['period', 'logs_page', 'summaries_page']), ['tab' => 'history', 'period' => 'this_week'])) }}" class="rounded-full border px-4 py-2 text-sm font-semibold transition {{ $periodLinkClasses(request('period') === 'this_week') }}">This Week</a>
                <a href="{{ route('admin.attendance', array_merge(request()->except(['period', 'logs_page', 'summaries_page']), ['tab' => 'history', 'period' => 'last_week'])) }}" class="rounded-full border px-4 py-2 text-sm font-semibold transition {{ $periodLinkClasses(request('period') === 'last_week') }}">Last Week</a>
                <a href="{{ route('admin.attendance', array_merge(request()->except(['period', 'logs_page', 'summaries_page']), ['tab' => 'history', 'period' => 'this_month'])) }}" class="rounded-full border px-4 py-2 text-sm font-semibold transition {{ $periodLinkClasses(request('period') === 'this_month') }}">This Month</a>
                <a href="{{ route('admin.attendance', array_merge(request()->except(['period', 'logs_page', 'summaries_page']), ['tab' => 'history', 'period' => 'last_month'])) }}" class="rounded-full border px-4 py-2 text-sm font-semibold transition {{ $periodLinkClasses(request('period') === 'last_month') }}">Last Month</a>
                <a href="{{ route('admin.attendance', array_merge(request()->except(['period', 'logs_page', 'summaries_page']), ['tab' => 'history'])) }}" class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">All Time</a>
            </div>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-lg font-extrabold text-slate-900">Search and Filter Attendance Records</h3>
                <p class="mt-1 text-sm text-slate-500">Filter by staff, date range, attendance status, and punch type.</p>
            </div>
            <form method="GET" action="{{ route('admin.attendance') }}" class="space-y-5 px-6 py-6">
                <input type="hidden" name="tab" value="history">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6 xl:items-end">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Staff Member</label>
                        <select name="staff_id" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-primary-500 focus:outline-hidden focus:ring-4 focus:ring-primary-100">
                            <option value="">All staff</option>
                            @foreach($staffList as $staffMember)
                                <option value="{{ $staffMember->id }}" {{ request('staff_id') == $staffMember->id ? 'selected' : '' }}>
                                    {{ $staffMember->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-primary-500 focus:outline-hidden focus:ring-4 focus:ring-primary-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-primary-500 focus:outline-hidden focus:ring-4 focus:ring-primary-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Status</label>
                        <select name="status" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-primary-500 focus:outline-hidden focus:ring-4 focus:ring-primary-100">
                            <option value="">Any status</option>
                            <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>Present</option>
                            <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>Late</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Punch Type</label>
                        <select name="punch_type" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-primary-500 focus:outline-hidden focus:ring-4 focus:ring-primary-100">
                            <option value="">Any punch</option>
                            <option value="in" {{ request('punch_type') === 'in' ? 'selected' : '' }}>Time In</option>
                            <option value="out" {{ request('punch_type') === 'out' ? 'selected' : '' }}>Time Out</option>
                        </select>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-full bg-primary-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-primary-700">
                            <i class="fas fa-filter"></i>
                            Apply
                        </button>
                        <a href="{{ route('admin.attendance', ['tab' => 'history']) }}" class="inline-flex flex-1 items-center justify-center gap-2 rounded-full border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
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
                    <p class="mt-1 text-sm text-slate-500">Time-in, time-out, and hours worked by day.</p>
                </div>
                <div class="text-xs text-slate-400">{{ number_format($summaries->total()) }} day records</div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[760px] w-full text-sm">
                    <thead class="bg-slate-50/90">
                        <tr>
                            <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Staff</th>
                            <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Date</th>
                            <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Time In</th>
                            <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Time Out</th>
                            <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Hours</th>
                            <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summaries as $summary)
                            <tr class="border-t border-slate-100 transition hover:bg-slate-50/80">
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ $summary->user?->display_name ?? 'Unknown staff' }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-900">{{ $summary->display_date->format('M d, Y') }}</div>
                                    <div class="text-xs text-slate-400">{{ $summary->display_date->format('l') }}</div>
                                </td>
                                <td class="px-5 py-4">{{ $summary->display_time_in ?? '-' }}</td>
                                <td class="px-5 py-4">{{ $summary->display_time_out ?? '-' }}</td>
                                <td class="px-5 py-4">{{ $summary->hours_worked ?? '-' }}</td>
                                <td class="px-5 py-4">
                                    @if($summary->display_status === 'present')
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Present</span>
                                    @elseif($summary->display_status === 'late')
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">Late</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Unknown</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center text-sm text-slate-500">No summary data found for the current filters.</td>
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
            <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Raw Punch Logs</h3>
                    <p class="mt-1 text-sm text-slate-500">Every captured scan from the biometric devices.</p>
                </div>
                <div class="text-xs text-slate-400">{{ number_format($logs->total()) }} logs</div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[980px] w-full text-sm">
                    <thead class="bg-slate-50/90">
                        <tr>
                            <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">#</th>
                            <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Staff</th>
                            <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Punch</th>
                            <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Date and Time</th>
                            <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Status</th>
                            <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Device</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr class="border-t border-slate-100 transition hover:bg-slate-50/80">
                                <td class="px-5 py-4 text-slate-400">{{ $log->id }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-900">{{ $log->user?->display_name ?? 'Unknown staff' }}</div>
                                    <div class="text-xs text-slate-500">{{ $log->user?->email }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    @if($log->punch_type === 'in')
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Time In</span>
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
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Present</span>
                                    @elseif($log->display_status === 'late')
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">Late</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">N/A</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-slate-700">{{ $log->device?->name ?? 'Unknown device' }}</div>
                                    <div class="text-xs text-slate-500">{{ $log->device?->serial_number ?? '-' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center text-sm text-slate-500">No punch logs found for the current filters.</td>
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
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const buttons = document.querySelectorAll('[data-attendance-tab-target]');
        const panels = document.querySelectorAll('[data-attendance-tab-panel]');
        const baseUrl = '{{ route('admin.attendance') }}';
        let activeTab = @json($activeAttendanceTab);

        const setTab = (targetTab, syncUrl = true) => {
            activeTab = targetTab;

            buttons.forEach((button) => {
                const isActive = button.dataset.attendanceTabTarget === targetTab;
                button.classList.toggle('border-accent-200', isActive);
                button.classList.toggle('bg-accent-50', isActive);
                button.classList.toggle('text-accent-700', isActive);
                button.classList.toggle('border-slate-200', !isActive);
                button.classList.toggle('bg-slate-50', !isActive);
                button.classList.toggle('text-slate-600', !isActive);
            });

            panels.forEach((panel) => {
                const isActive = panel.dataset.attendanceTabPanel === targetTab;
                panel.classList.toggle('hidden', !isActive);
            });

            if (syncUrl) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', targetTab);
                if (targetTab === 'today') {
                    url.searchParams.delete('period');
                    url.searchParams.delete('staff_id');
                    url.searchParams.delete('date_from');
                    url.searchParams.delete('date_to');
                    url.searchParams.delete('status');
                    url.searchParams.delete('punch_type');
                    url.searchParams.delete('logs_page');
                    url.searchParams.delete('summaries_page');
                }
                window.history.replaceState({}, '', `${baseUrl}${url.search}`);
            }
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => setTab(button.dataset.attendanceTabTarget));
        });

        setTab(activeTab, false);
    });
</script>
@endsection
