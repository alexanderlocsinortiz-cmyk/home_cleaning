@extends('layouts.admin')
@section('title', 'Attendance History - Home Cleaning Service Admin')
@section('page-title', 'Attendance Logs')
@section('page-subtitle', 'Complete attendance records of all staff members')

@push('styles')
<style>
@media (max-width: 767px) {
    .attendance-history-page {
        padding: 1rem !important;
    }

    .attendance-history-stat-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
    }

    .attendance-history-filter-grid {
        grid-template-columns: 1fr !important;
        gap: 0.875rem !important;
    }

    .attendance-history-table-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.75rem !important;
    }

    .attendance-history-summary-table {
        min-width: 720px;
    }

    .attendance-history-log-table {
        min-width: 920px;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    .attendance-history-page {
        padding: 1.25rem !important;
    }

    .attendance-history-filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
}
</style>
@endpush

@section('content')
<div class="attendance-history-page" style="padding: 1.75rem 2rem; background: #f1f5f9; min-height: calc(100vh - 73px); font-family: 'DM Sans', sans-serif;">
    <div style="max-width: 1100px; margin: 0 auto;">

        <div class="attendance-history-stat-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 1.5rem;">
            <div style="background: white; border-radius: 16px; padding: 1.5rem; border: 1px solid #f1f5f9; border-top: 4px solid #1D9E75; box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
                <div style="font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Total Records</div>
                <div style="font-size: 36px; font-weight: 800; color: #1D9E75;">{{ $totalLogs }}</div>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">All time punches</div>
            </div>
            <div style="background: white; border-radius: 16px; padding: 1.5rem; border: 1px solid #f1f5f9; border-top: 4px solid #f59e0b; box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
                <div style="font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Total Late</div>
                <div style="font-size: 36px; font-weight: 800; color: #f59e0b;">{{ $totalLate }}</div>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Late arrivals recorded</div>
            </div>
            <div style="background: white; border-radius: 16px; padding: 1.5rem; border: 1px solid #f1f5f9; border-top: 4px solid #16a34a; box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
                <div style="font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Staff Tracked</div>
                <div style="font-size: 36px; font-weight: 800; color: #16a34a;">{{ $staffList->count() }}</div>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Total staff members</div>
            </div>
        </div>

        <div style="background: white; border-radius: 16px; padding: 1.25rem 1.5rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 1rem;">
            <div style="font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">Date Shortcuts</div>
            <div style="font-size: 12px; color: #64748b; margin-bottom: 10px;">Use a quick range to narrow the attendance view before applying detailed filters.</div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="{{ route('admin.attendance.history') }}?period=today"
                   style="padding: 7px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1.5px solid {{ request('period') == 'today' ? '#1D9E75' : '#e2e8f0' }}; background: {{ request('period') == 'today' ? '#1D9E75' : 'white' }}; color: {{ request('period') == 'today' ? 'white' : '#64748b' }};">
                    Today
                </a>
                <a href="{{ route('admin.attendance.history') }}?period=yesterday"
                   style="padding: 7px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1.5px solid {{ request('period') == 'yesterday' ? '#1D9E75' : '#e2e8f0' }}; background: {{ request('period') == 'yesterday' ? '#1D9E75' : 'white' }}; color: {{ request('period') == 'yesterday' ? 'white' : '#64748b' }};">
                    Yesterday
                </a>
                <a href="{{ route('admin.attendance.history') }}?period=this_week"
                   style="padding: 7px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1.5px solid {{ request('period') == 'this_week' ? '#1D9E75' : '#e2e8f0' }}; background: {{ request('period') == 'this_week' ? '#1D9E75' : 'white' }}; color: {{ request('period') == 'this_week' ? 'white' : '#64748b' }};">
                    This Week
                </a>
                <a href="{{ route('admin.attendance.history') }}?period=last_week"
                   style="padding: 7px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1.5px solid {{ request('period') == 'last_week' ? '#1D9E75' : '#e2e8f0' }}; background: {{ request('period') == 'last_week' ? '#1D9E75' : 'white' }}; color: {{ request('period') == 'last_week' ? 'white' : '#64748b' }};">
                    Last Week
                </a>
                <a href="{{ route('admin.attendance.history') }}?period=this_month"
                   style="padding: 7px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1.5px solid {{ request('period') == 'this_month' ? '#1D9E75' : '#e2e8f0' }}; background: {{ request('period') == 'this_month' ? '#1D9E75' : 'white' }}; color: {{ request('period') == 'this_month' ? 'white' : '#64748b' }};">
                    This Month
                </a>
                <a href="{{ route('admin.attendance.history') }}?period=last_month"
                   style="padding: 7px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1.5px solid {{ request('period') == 'last_month' ? '#1D9E75' : '#e2e8f0' }}; background: {{ request('period') == 'last_month' ? '#1D9E75' : 'white' }}; color: {{ request('period') == 'last_month' ? 'white' : '#64748b' }};">
                    Last Month
                </a>
                <a href="{{ route('admin.attendance.history') }}"
                   style="padding: 7px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #64748b;">
                    All Time
                </a>
            </div>
        </div>

        <div style="background: white; border-radius: 16px; padding: 1.25rem 1.5rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 1.5rem;">
            <div style="font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">Search and Filter Attendance Records</div>
            <div style="font-size: 12px; color: #64748b; margin-bottom: 1rem;">Refine the log view by staff member, date range, and attendance status.</div>
            <form method="GET" action="{{ route('admin.attendance.history') }}">
                <div class="attendance-history-filter-grid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; align-items: end;">
                    <div>
                        <label style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 4px;">Staff Member</label>
                        <select name="staff_id" style="width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; font-size: 13px; outline: none;">
                            <option value="">All staff</option>
                            @foreach($staffList as $staff)
                            <option value="{{ $staff->id }}" {{ request('staff_id') == $staff->id ? 'selected' : '' }}>
                                {{ $staff->first_name }} {{ $staff->last_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 4px;">Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" style="width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; font-size: 13px; outline: none;">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 4px;">Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" style="width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; font-size: 13px; outline: none;">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 4px;">Status</label>
                        <select name="status" style="width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; font-size: 13px; outline: none;">
                            <option value="">Any attendance status</option>
                            <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                            <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" style="flex: 1; background: #1D9E75; color: white; border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer;">
                            Apply Filters
                        </button>
                        <a href="{{ route('admin.attendance.history') }}" style="flex: 1; background: #f1f5f9; color: #64748b; border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                            Clear Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div style="background: white; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 1.5rem;">
            <div class="attendance-history-table-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f8fafc; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 15px; font-weight: 700; color: #1e293b;">Daily Attendance Summary</div>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">Time in and time out per day per staff</div>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="attendance-history-summary-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Staff</th>
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Date</th>
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Time In</th>
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Time Out</th>
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Hours Worked</th>
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summaries as $summary)
                        <tr style="border-top: 1px solid #f8fafc;">
                            <td style="padding: 14px 20px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #1D9E75, #1D9E75); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 13px; flex-shrink: 0;">
                                        {{ strtoupper(substr($summary->user->first_name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div style="font-size: 13px; font-weight: 600; color: #1e293b;">
                                        {{ $summary->user->first_name ?? 'Unknown' }} {{ $summary->user->last_name ?? '' }}
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 20px;">
                                <div style="font-size: 13px; font-weight: 600; color: #1e293b;">{{ $summary->display_date->format('M d, Y') }}</div>
                                <div style="font-size: 11px; color: #94a3b8;">{{ $summary->display_date->format('l') }}</div>
                            </td>
                            <td style="padding: 14px 20px;">
                                <span style="font-size: 13px; font-weight: 600; color: {{ $summary->display_time_in ? '#16a34a' : '#94a3b8' }};">
                                    {{ $summary->display_time_in ?? '-' }}
                                </span>
                            </td>
                            <td style="padding: 14px 20px;">
                                <span style="font-size: 13px; font-weight: 600; color: {{ $summary->display_time_out ? '#dc2626' : '#94a3b8' }};">
                                    {{ $summary->display_time_out ?? '-' }}
                                </span>
                            </td>
                            <td style="padding: 14px 20px;">
                                <span style="font-size: 13px; font-weight: 600; color: {{ $summary->hours_worked ? '#1e293b' : '#94a3b8' }};">
                                    {{ $summary->hours_worked ?? '-' }}
                                </span>
                            </td>
                            <td style="padding: 14px 20px;">
                                @if($summary->display_status === 'present')
                                <span style="background: #f0fdf4; color: #16a34a; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">Present</span>
                                @elseif($summary->display_status === 'late')
                                <span style="background: #fefce8; color: #d97706; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">Late</span>
                                @else
                                <span style="background: #f8fafc; color: #94a3b8; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">Unknown</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="padding: 3rem; text-align: center; color: #94a3b8;">
                                <div style="font-size: 14px; font-weight: 600;">No attendance records match your current filters</div>
                                <div style="font-size: 12px; margin-top: 4px;">Try adjusting the date range or staff filters.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="padding: 12px 20px; border-top: 1px solid #f8fafc;">
                {{ $summaries->links('pagination::tailwind') }}
            </div>
        </div>

        <div style="background: white; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden;">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f8fafc;">
                <div style="font-size: 15px; font-weight: 700; color: #1e293b;">Raw Punch Logs</div>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">Every individual fingerprint scan recorded</div>
            </div>
            <div style="overflow-x: auto;">
                <table class="attendance-history-log-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">#</th>
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Staff</th>
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Punch Type</th>
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Date and Time</th>
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Device</th>
                            <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr style="border-top: 1px solid #f8fafc;">
                            <td style="padding: 12px 20px; font-size: 12px; color: #94a3b8;">{{ $log->id }}</td>
                            <td style="padding: 12px 20px;">
                                <div style="font-size: 13px; font-weight: 600; color: #1e293b;">{{ $log->user->first_name ?? 'Unknown' }} {{ $log->user->last_name ?? '' }}</div>
                                <div style="font-size: 11px; color: #94a3b8;">{{ $log->user->email ?? '' }}</div>
                            </td>
                            <td style="padding: 12px 20px;">
                                @if($log->punch_type === 'in')
                                <span style="background: #f0fdf4; color: #16a34a; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">Time In</span>
                                @else
                                <span style="background: #fef2f2; color: #dc2626; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">Time Out</span>
                                @endif
                            </td>
                            <td style="padding: 12px 20px;">
                                <div style="font-size: 13px; font-weight: 600; color: #1e293b;">{{ $log->display_logged_at_date }}</div>
                                <div style="font-size: 12px; color: #64748b;">{{ $log->display_logged_at_time }}</div>
                            </td>
                            <td style="padding: 12px 20px;">
                                @if($log->display_status === 'present')
                                <span style="background: #f0fdf4; color: #16a34a; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">Present</span>
                                @elseif($log->display_status === 'late')
                                <span style="background: #fefce8; color: #d97706; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">Late</span>
                                @else
                                <span style="background: #f8fafc; color: #94a3b8; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">N/A</span>
                                @endif
                            </td>
                            <td style="padding: 12px 20px;">
                                <div style="font-size: 12px; color: #64748b;">{{ $log->device->name ?? 'Unknown Device' }}</div>
                                <div style="font-size: 11px; color: #94a3b8;">{{ $log->device->serial_number ?? '-' }}</div>
                            </td>
                            <td style="padding: 12px 20px;">
                                <span style="background: #E1F5EE; color: #1D9E75; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                                    {{ ucfirst($log->source) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="padding: 3rem; text-align: center; color: #94a3b8;">
                                <div style="font-size: 14px; font-weight: 600;">No punch logs match your current filters</div>
                                <div style="font-size: 12px; margin-top: 4px;">Recorded scans will appear here once attendance activity is available.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="padding: 12px 20px; border-top: 1px solid #f8fafc;">
                {{ $logs->links('pagination::tailwind') }}
            </div>
        </div>

    </div>
</div>
@endsection
