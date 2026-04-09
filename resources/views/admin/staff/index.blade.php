@extends('layouts.admin')
@section('title', 'Staff')
@section('page-title', 'Staff Directory')
@section('page-subtitle', 'Manage staff profiles, access details, and service coverage assignments')

@section('content')
<div class="space-y-6" style="font-family: 'DM Sans', sans-serif;">

    @if(session('success'))
        <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;border-radius:14px;padding:14px 16px;display:flex;align-items:flex-start;gap:10px;">
            <i class="fas fa-check-circle" style="margin-top:2px;"></i>
            <div>
                <div style="font-size:14px;font-weight:700;">Action completed</div>
                <div style="font-size:13px;margin-top:2px;">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:14px;padding:14px 16px;display:flex;align-items:flex-start;gap:10px;">
            <i class="fas fa-exclamation-triangle" style="margin-top:2px;"></i>
            <div>
                <div style="font-size:14px;font-weight:700;">Action blocked</div>
                <div style="font-size:13px;margin-top:2px;">{{ session('error') }}</div>
            </div>
        </div>
    @endif

    <div style="background:white;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 1px 4px rgba(0,0,0,0.05);overflow:hidden;">
        <div style="padding:20px 22px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
            <div>
                <div style="font-size:18px;font-weight:800;color:#1e293b;">Staff Members</div>
                <div style="font-size:13px;color:#64748b;margin-top:4px;">Operational directory for active staff across your service coverage areas.</div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <div style="font-size:12px;color:#94a3b8;">
                    @if($staff->count())
                        Showing {{ number_format($staff->firstItem()) }}-{{ number_format($staff->lastItem()) }} of {{ number_format($staff->total()) }}
                    @else
                        No records to display
                    @endif
                </div>
                <a href="{{ route('admin.staff.create') }}" style="display:inline-flex;align-items:center;gap:8px;background:#1D9E75;color:white;border-radius:12px;padding:11px 16px;font-size:13px;font-weight:700;text-decoration:none;">
                    <i class="fas fa-plus"></i>
                    Add Staff Member
                </a>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:separate;border-spacing:0;font-size:13px;min-width:960px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Staff Member</th>
                        <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Email</th>
                        <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Phone</th>
                        <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Barangay</th>
                        <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Username</th>
                        <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Average Rating</th>
                        <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Reviews</th>
                        <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Joined</th>
                        <th style="padding:12px 18px;text-align:right;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $member)
                        <tr style="border-top:1px solid #f8fafc;transition:background 0.15s ease;" onmouseover="this.style.background='#fbfdff'" onmouseout="this.style.background='white'">
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;">
                                <div style="font-size:13px;font-weight:800;color:#1e293b;">{{ $member->full_name }}</div>
                            </td>
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;">
                                <div style="font-size:12px;font-weight:700;color:#1e293b;">{{ $member->email }}</div>
                            </td>
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;">
                                <div style="font-size:12px;color:#64748b;">{{ $member->phone ?? '--' }}</div>
                            </td>
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;">
                                <div style="font-size:12px;color:#64748b;">{{ $barangays[$member->barangay] ?? $member->barangay }}</div>
                            </td>
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;">
                                <div style="font-size:12px;color:#64748b;">&#64;{{ $member->username }}</div>
                            </td>
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;">
                                @if($member->avg_rating)
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <i class="fas fa-star" style="color:#f59e0b;"></i>
                                        <span style="font-size:12px;font-weight:800;color:#1e293b;">{{ $member->avg_rating }}</span>
                                    </div>
                                @else
                                    <span style="font-size:12px;color:#94a3b8;">No ratings yet</span>
                                @endif
                            </td>
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;">
                                <div style="font-size:12px;color:#64748b;">{{ $member->total_ratings ?? 0 }}</div>
                            </td>
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;">
                                <div style="font-size:12px;color:#64748b;">{{ optional($member->created_at)->format('M d, Y') }}</div>
                            </td>
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;text-align:right;">
                                <div style="display:inline-flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
                                    <a href="{{ route('admin.staff.edit', $member) }}" style="display:inline-flex;align-items:center;gap:6px;background:#eff6ff;color:#185FA5;border:1px solid #bfdbfe;border-radius:10px;padding:8px 12px;font-size:12px;font-weight:700;text-decoration:none;">
                                        <i class="fas fa-pen"></i>
                                        Edit Staff
                                    </a>
                                    <form action="{{ route('admin.staff.destroy', $member) }}" method="POST" onsubmit="return confirm('Remove this staff member from the directory?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="display:inline-flex;align-items:center;gap:6px;background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:10px;padding:8px 12px;font-size:12px;font-weight:700;cursor:pointer;">
                                            <i class="fas fa-trash"></i>
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding:56px 24px;text-align:center;color:#94a3b8;">
                                <div style="width:68px;height:68px;border-radius:20px;background:#f8fafc;color:#94a3b8;display:inline-flex;align-items:center;justify-content:center;font-size:28px;">
                                    <i class="fas fa-user-group"></i>
                                </div>
                                <div style="font-size:18px;font-weight:800;color:#1e293b;margin-top:18px;">No staff members have been added yet</div>
                                <div style="font-size:13px;color:#64748b;line-height:1.7;max-width:420px;margin:8px auto 0;">
                                    Add your first staff account to begin assignment, attendance tracking, and operational scheduling.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="padding:14px 18px;border-top:1px solid #f1f5f9;">
            {{ $staff->links('pagination::tailwind') }}
        </div>
    </div>
</div>
@endsection
