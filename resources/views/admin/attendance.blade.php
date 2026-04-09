@extends('layouts.admin')
@section('title', 'Attendance - Home Cleaning Service Admin')
@section('page-title', 'Staff Attendance')
@section('page-subtitle', 'Today\'s attendance overview - ' . $attendanceDate->format('F d, Y'))

@push('styles')
<style>
.attendance-enrollment-queue-panel {
    padding: 1.25rem 1.5rem 1.5rem;
}

.attendance-enrollment-queue-shell {
    display: flex;
    flex-direction: column;
    height: 320px;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #f8fafc;
    overflow: hidden;
}

.attendance-enrollment-queue-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1rem 0.875rem;
    border-bottom: 1px solid #e2e8f0;
    background: #ffffff;
}

.attendance-enrollment-queue-title {
    font-size: 13px;
    font-weight: 700;
    color: #334155;
}

.attendance-enrollment-queue-meta {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 0.3rem;
}

.attendance-enrollment-queue-count {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    border: 1px solid #dbeafe;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.2px;
    white-space: nowrap;
}

.attendance-enrollment-queue-list {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 0 1rem;
    scrollbar-gutter: stable;
    overscroll-behavior: contain;
}

.attendance-enrollment-queue-list::-webkit-scrollbar {
    width: 8px;
}

.attendance-enrollment-queue-list::-webkit-scrollbar-track {
    background: transparent;
}

.attendance-enrollment-queue-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}

.attendance-enrollment-queue-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.95rem 0;
    border-bottom: 1px solid #e2e8f0;
}

.attendance-enrollment-queue-item:last-child {
    border-bottom: none;
}

.attendance-enrollment-queue-item-copy {
    flex: 1;
    min-width: 0;
}

.attendance-enrollment-status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    text-transform: capitalize;
    white-space: nowrap;
}

.attendance-enrollment-queue-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100%;
    padding: 1.5rem;
    font-size: 13px;
    color: #94a3b8;
    text-align: center;
}

@media (max-width: 767px) {
    .attendance-page {
        padding: 1rem !important;
    }

    .attendance-stat-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
    }

    .attendance-panel-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.75rem !important;
    }

    .attendance-table {
        min-width: 620px;
    }

    .attendance-device-row {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.5rem !important;
    }

    .attendance-device-setup-grid {
        grid-template-columns: 1fr !important;
    }

    .attendance-enrollment-queue-panel {
        padding: 1rem !important;
    }

    .attendance-enrollment-queue-shell {
        height: 280px;
    }

    .attendance-enrollment-queue-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
@endpush

@section('content')
<div class="attendance-page" style="padding: 1.75rem 2rem; background: #f1f5f9; min-height: calc(100vh - 73px); font-family: 'DM Sans', sans-serif;">
    <div style="max-width: 1100px; margin: 0 auto;">

        @if(session('success'))
        <div style="margin-bottom: 1rem; background: #ecfdf5; border: 1px solid #a7f3d0; color: #166534; padding: 14px 16px; border-radius: 14px; font-size: 14px; font-weight: 600;">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div style="margin-bottom: 1rem; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 16px; border-radius: 14px;">
            <div style="font-size: 14px; font-weight: 700; margin-bottom: 6px;">Please fix the device form errors.</div>
            @foreach($errors->all() as $error)
            <div style="font-size: 13px;">&bull; {{ $error }}</div>
            @endforeach
        </div>
        @endif

        @if(session('generated_device_token'))
        <div style="margin-bottom: 1.25rem; background: #0f172a; border-radius: 16px; padding: 1.25rem 1.5rem; color: white; box-shadow: 0 10px 30px rgba(15,23,42,0.16);">
            <div style="font-size: 13px; font-weight: 700; color: #86efac; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Generated Device Token</div>
            <div style="font-size: 14px; color: #cbd5e1; margin-bottom: 10px;">
                {{ session('generated_device_name') }} &middot; Serial: {{ session('generated_device_serial') }}
            </div>
            <div style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); border-radius: 12px; padding: 12px 14px; font-family: monospace; font-size: 13px; word-break: break-all;">
                {{ session('generated_device_token') }}
            </div>
            <div style="font-size: 12px; color: #94a3b8; margin-top: 10px;">
                Copy this token into <code>DEVICE_TOKEN</code> in your ESP32 sketch. It is only shown in full right after generation or rotation.
            </div>
        </div>
        @endif

        <div class="attendance-stat-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 1.5rem;">
            <div style="background: white; border-radius: 16px; padding: 1.5rem; border: 1px solid #f1f5f9; border-top: 4px solid #16a34a; box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
                <div style="font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Present Today</div>
                <div style="font-size: 36px; font-weight: 800; color: #16a34a;">{{ $presentCount }}</div>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Staff clocked in</div>
            </div>
            <div style="background: white; border-radius: 16px; padding: 1.5rem; border: 1px solid #f1f5f9; border-top: 4px solid #dc2626; box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
                <div style="font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Absent Today</div>
                <div style="font-size: 36px; font-weight: 800; color: #dc2626;">{{ $absentCount }}</div>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Not yet scanned</div>
            </div>
            <div style="background: white; border-radius: 16px; padding: 1.5rem; border: 1px solid #f1f5f9; border-top: 4px solid #f59e0b; box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
                <div style="font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Late Today</div>
                <div style="font-size: 36px; font-weight: 800; color: #f59e0b;">{{ $lateCount }}</div>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Arrived after 8:00 AM</div>
            </div>
        </div>

        <div style="margin-bottom: 1.25rem; background: white; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden;">
            <div class="attendance-panel-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f8fafc; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 15px; font-weight: 700; color: #1e293b; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-key" style="color: #1D9E75;"></i>
                        Attendance Device Token Generator
                    </div>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">Create an ESP32 device and generate the token to paste into <code>DEVICE_TOKEN</code>.</div>
                </div>
                <div style="font-size: 12px; color: #64748b; background: #f8fafc; padding: 6px 14px; border-radius: 20px; border: 1px solid #e2e8f0;">
                    {{ $devices->count() }} Registered Devices
                </div>
            </div>

            <form method="POST" action="{{ route('admin.attendance.devices.store') }}" style="padding: 1.25rem 1.5rem;">
                @csrf
                <div class="attendance-device-setup-grid" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
                    <div>
                        <label for="name" style="display: block; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px;">Device Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Front Desk Device" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; font-size: 14px; color: #1e293b;" required>
                    </div>
                    <div>
                        <label for="serial_number" style="display: block; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px;">Serial Number</label>
                        <input id="serial_number" name="serial_number" type="text" value="{{ old('serial_number') }}" placeholder="ESP32-FRONT-01" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; font-size: 14px; color: #1e293b;" required>
                    </div>
                    <div>
                        <label for="location" style="display: block; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px;">Location</label>
                        <input id="location" name="location" type="text" value="{{ old('location') }}" placeholder="Main Office" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; font-size: 14px; color: #1e293b;">
                    </div>
                </div>

                <div style="margin-top: 1rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                    <div style="font-size: 12px; color: #64748b;">
                        The generated token becomes the value of <code>DEVICE_TOKEN</code> in the ESP32 sketch.
                    </div>
                    <button type="submit" style="border: none; border-radius: 10px; background: #1D9E75; color: white; padding: 10px 16px; font-size: 14px; font-weight: 700; cursor: pointer;">
                        Generate Device Token
                    </button>
                </div>
            </form>
        </div>

        <div style="margin-bottom: 1.25rem; background: white; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden;">
            <div class="attendance-panel-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f8fafc; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 15px; font-weight: 700; color: #1e293b;">Fingerprint Enrollment From Website</div>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">Create the request here, then ask the staff member to place the same finger twice on the selected device.</div>
                </div>
                <div style="font-size: 12px; color: #64748b; background: #f8fafc; padding: 6px 14px; border-radius: 20px; border: 1px solid #e2e8f0;">
                    {{ $recentEnrollmentRequests->count() }} Recent Requests
                </div>
            </div>

            <form method="POST" action="{{ route('admin.attendance.enrollments.store') }}" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f8fafc;">
                @csrf
                <div class="attendance-device-setup-grid" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
                    <div>
                        <label for="device_id" style="display: block; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px;">Device</label>
                        <select id="device_id" name="device_id" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; font-size: 14px; color: #1e293b;" required>
                            <option value="">Select a device</option>
                            @foreach($devices as $device)
                            <option value="{{ $device->id }}" @selected((string) old('device_id') === (string) $device->id)>
                                {{ $device->name }} ({{ $device->serial_number }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="user_id" style="display: block; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px;">Staff Member</label>
                        <select id="user_id" name="user_id" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; font-size: 14px; color: #1e293b;" required>
                            <option value="">Select staff</option>
                            @foreach($staff as $staffMember)
                            @if($staffMember->fingerprint_template_id === null)
                            <option value="{{ $staffMember->id }}" @selected((string) old('user_id') === (string) $staffMember->id)>
                                {{ $staffMember->full_name }} ({{ $staffMember->username }})
                            </option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="template_id" style="display: block; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px;">Fingerprint Slot</label>
                        <input id="template_id" name="template_id" type="number" min="1" max="162" value="{{ old('template_id') }}" placeholder="1" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; font-size: 14px; color: #1e293b;" required>
                    </div>
                </div>

                <div style="margin-top: 1rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                    <div style="font-size: 12px; color: #64748b;">
                        The website sends the request to the ESP32. The browser does not talk to the AS608 directly.
                    </div>
                    <button type="submit" style="border: none; border-radius: 10px; background: #0f766e; color: white; padding: 10px 16px; font-size: 14px; font-weight: 700; cursor: pointer;">
                        Start Fingerprint Enrollment
                    </button>
                </div>
            </form>

            <div class="attendance-enrollment-queue-panel">
                <div class="attendance-enrollment-queue-shell">
                    <div class="attendance-enrollment-queue-header">
                        <div>
                            <div class="attendance-enrollment-queue-title">Recent Enrollment Queue</div>
                            <div class="attendance-enrollment-queue-meta">
                                Newest requests stay on top. Scroll inside this panel to review older recent records.
                            </div>
                        </div>
                        <div class="attendance-enrollment-queue-count">
                            Latest {{ $recentEnrollmentRequests->count() }}
                        </div>
                    </div>

                    <div class="attendance-enrollment-queue-list">
                        @forelse($recentEnrollmentRequests as $request)
                        <div class="attendance-enrollment-queue-item attendance-device-row">
                            <div class="attendance-enrollment-queue-item-copy">
                                <div style="font-size: 14px; font-weight: 600; color: #1e293b;">
                                    {{ $request->user->full_name }} &middot; Slot #{{ $request->template_id }}
                                </div>
                                <div style="font-size: 12px; color: #94a3b8; margin-top: 4px; line-height: 1.55;">
                                    Device: {{ $request->device->name }} &middot; Requested by {{ $request->requestedBy?->full_name ?? 'Admin' }} &middot; {{ $request->created_at->diffForHumans() }}
                                </div>
                                @if($request->error_message)
                                <div style="font-size: 12px; color: #b91c1c; margin-top: 6px; line-height: 1.5;">
                                    {{ $request->error_message }}
                                </div>
                                @endif
                            </div>
                            <div style="flex-shrink: 0;">
                                @php
                                    $statusColor = match ($request->status) {
                                        'completed' => ['#ecfdf5', '#166534'],
                                        'failed' => ['#fef2f2', '#991b1b'],
                                        'in_progress' => ['#eff6ff', '#1d4ed8'],
                                        default => ['#fff7ed', '#c2410c'],
                                    };
                                @endphp
                                <span class="attendance-enrollment-status-badge" style="background: {{ $statusColor[0] }}; color: {{ $statusColor[1] }};">
                                    {{ str_replace('_', ' ', $request->status) }}
                                </span>
                            </div>
                        </div>
                        @empty
                        <div class="attendance-enrollment-queue-empty">No fingerprint enrollment requests yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div style="background: white; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden;">
            <div class="attendance-panel-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f8fafc; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 15px; font-weight: 700; color: #1e293b; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-clipboard-list" style="color: #185FA5;"></i>
                        Today's Attendance
                    </div>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">{{ $attendanceDate->format('l, F d Y') }}</div>
                </div>
                <div style="font-size: 12px; color: #64748b; background: #f8fafc; padding: 6px 14px; border-radius: 20px; border: 1px solid #e2e8f0;">
                    {{ $attendance->count() }} Total Staff
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="attendance-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Staff</th>
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Time In</th>
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Time Out</th>
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Availability</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendance as $a)
                        <tr style="border-top: 1px solid #f8fafc;">
                            <td style="padding: 14px 20px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #0F6E56, #1D9E75); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px; flex-shrink: 0;">
                                        {{ strtoupper(substr($a['name'], 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-size: 14px; font-weight: 600; color: #1e293b;">{{ $a['name'] }}</div>
                                        <div style="font-size: 12px; color: #94a3b8;">{{ $a['email'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 20px;">
                                @if($a['status'] === 'present')
                                <span style="display: inline-flex; align-items: center; gap: 6px; background: #f0fdf4; color: #16a34a; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                                    <i class="fas fa-circle-check"></i>
                                    Present
                                </span>
                                @elseif($a['status'] === 'late')
                                <span style="display: inline-flex; align-items: center; gap: 6px; background: #fefce8; color: #d97706; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                                    <i class="fas fa-clock"></i>
                                    Late
                                </span>
                                @else
                                <span style="display: inline-flex; align-items: center; gap: 6px; background: #fef2f2; color: #dc2626; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                                    <i class="fas fa-circle-xmark"></i>
                                    Absent
                                </span>
                                @endif
                            </td>
                            <td style="padding: 14px 20px;">
                                <span style="font-size: 14px; font-weight: 600; color: {{ $a['time_in'] ? '#1e293b' : '#94a3b8' }};">
                                    {{ $a['time_in'] ?? '--' }}
                                </span>
                            </td>
                            <td style="padding: 14px 20px;">
                                <span style="font-size: 14px; font-weight: 600; color: {{ $a['time_out'] ? '#1e293b' : '#94a3b8' }};">
                                    {{ $a['time_out'] ?? '--' }}
                                </span>
                            </td>
                            <td style="padding: 14px 20px;">
                                @if($a['is_present'])
                                <span style="display: inline-flex; align-items: center; gap: 6px; background: #E1F5EE; color: #1D9E75; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                                    <i class="fas fa-user-check"></i>
                                    Available for assignment
                                </span>
                                @else
                                <span style="display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; color: #94a3b8; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                                    <i class="fas fa-user-slash"></i>
                                    Not available
                                </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="padding: 3rem 1rem; text-align: center; color: #94a3b8;">
                                <div style="font-size: 32px; margin-bottom: 8px;">&#128203;</div>
                                <div style="font-size: 14px; font-weight: 600;">No attendance records are available today</div>
                                <div style="font-size: 12px; margin-top: 4px;">Staff attendance entries will appear here after time-in activity is recorded.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div style="margin-top: 1.25rem; background: white; border-radius: 16px; padding: 1.25rem 1.5rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <div style="font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 1rem; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-microchip" style="color: #1D9E75;"></i>
                Biometric Device Status
            </div>
            @forelse($devices as $device)
            <div class="attendance-device-row" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8fafc;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: {{ $device->last_seen_at && $device->last_seen_at->diffInMinutes(now()) < 30 ? '#16a34a' : '#94a3b8' }};"></div>
                    <div>
                        <div style="font-size: 14px; font-weight: 600; color: #1e293b;">{{ $device->name }}</div>
                        <div style="font-size: 12px; color: #94a3b8;">
                            {{ $device->location ?: 'No location set' }} &middot; Serial: {{ $device->serial_number }}
                        </div>
                        <div style="font-size: 12px; color: #64748b; font-family: monospace; margin-top: 4px;">
                            Token: {{ str_repeat('*', 12) }}{{ substr($device->api_token, -8) }}
                        </div>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                    <div style="font-size: 12px; color: #94a3b8;">
                        Last seen: {{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Never' }}
                    </div>
                    <form method="POST" action="{{ route('admin.attendance.devices.rotate-token', $device) }}">
                        @csrf
                        <button type="submit" style="border: 1px solid #cbd5e1; border-radius: 999px; background: white; color: #334155; padding: 6px 12px; font-size: 12px; font-weight: 700; cursor: pointer;">
                            Rotate Token
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div style="padding: 8px 0; color: #94a3b8; font-size: 13px;">No biometric devices are connected yet.</div>
            @endforelse
        </div>

    </div>
</div>
@endsection
