@extends('layouts.staff')
@section('title', 'Staff Dashboard')
@section('page-title', 'Staff Dashboard')
@section('page-subtitle', 'Your assignments and tasks')

@section('content')
<style>
@media (max-width: 767px) {

    /* Page padding */
    .sf-page-wrap { padding: 0.875rem !important; }

    /* Welcome banner - fix stacked stats */
    .welcome-banner-inner {
        flex-direction: column !important;
        gap: 1rem !important;
        align-items: flex-start !important;
    }
    .welcome-banner-stats {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 8px !important;
        width: 100% !important;
    }
    .welcome-banner-stats > div {
        flex: unset !important;
    }
    .welcome-banner-name {
        font-size: 20px !important;
    }

    /* Stat cards - 2 columns */
    .staff-stat-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 0.875rem !important;
    }
    .staff-stat-grid > div {
        padding: 1rem !important;
    }
    .staff-stat-grid .stat-number {
        font-size: 26px !important;
    }

    /* Info + assignments grid - single column */
    .staff-info-grid {
        grid-template-columns: 1fr !important;
    }

    /* Active assignments table - scrollable */
    .staff-table-wrap {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
        border-radius: 0 0 14px 14px !important;
    }
    .staff-table-wrap table {
        min-width: 620px !important;
    }

    /* Quick actions - horizontal row */
    .staff-quick-actions {
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
    }
}
</style>
<div class="sf-page-wrap" style="padding: 1.5rem 2rem; font-family: DM Sans, sans-serif;">
    @if(session('success'))
    <div style="background: #dcfce7; border: 1px solid #86efac; color: #16a34a; border-radius: 10px; padding: 12px 16px; margin-bottom: 1.25rem; font-size: 14px; display: flex; align-items: center; gap: 8px;">
        <span>&#x2705;</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; border-radius: 10px; padding: 12px 16px; margin-bottom: 1.25rem; font-size: 14px;">
        <div style="font-weight: 700; margin-bottom: 6px;">Please fix the following:</div>
        @foreach($errors->all() as $error)
        <div>&bull; {{ $error }}</div>
        @endforeach
    </div>
    @endif

    <div class="welcome-banner-inner" style="background: linear-gradient(135deg, #0F6E56 0%, #1D9E75 50%, #06b6d4 100%); border-radius: 16px; padding: 2rem 2.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; position: relative; overflow: hidden; gap: 1rem; flex-wrap: wrap;">
        <div style="position: absolute; right: -20px; top: -40px; width: 200px; height: 200px; border-radius: 50%; background: rgba(255, 255, 255, 0.07);"></div>
        <div style="position: absolute; right: 120px; bottom: -60px; width: 150px; height: 150px; border-radius: 50%; background: rgba(255, 255, 255, 0.05);"></div>
        <div style="display: flex; align-items: center; gap: 1.25rem; position: relative; z-index: 1;">
            <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(255, 255, 255, 0.2); border: 2px solid rgba(255, 255, 255, 0.3); display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; color: white;">{{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}</div>
            <div>
                <p style="color: rgba(255, 255, 255, 0.75); font-size: 13px; margin-bottom: 4px;">{{ now()->format('l, F d Y') }}</p>
                <h1 class="welcome-banner-name" style="color: white; font-size: 24px; font-weight: 700; margin-bottom: 4px;">
                    Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 17 ? 'Afternoon' : 'Evening') }}, {{ $user->first_name }}!
                </h1>
                <p style="color: rgba(255, 255, 255, 0.75); font-size: 13px;">{{ ucfirst($user->barangay) }} &middot; {{ $user->email }}</p>
            </div>
        </div>
        <div class="welcome-banner-stats" style="display: flex; gap: 10px; position: relative; z-index: 1; flex-wrap: wrap;">
            <div style="background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.25); border-radius: 12px; padding: 10px 18px; color: white; font-size: 13px; font-weight: 500; text-align: center;">
                <div style="font-size: 20px; font-weight: 700;">{{ $assignedBookings->count() }}</div>
                <div style="font-size: 11px; opacity: 0.8;">Active Jobs</div>
            </div>
            <div style="background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.25); border-radius: 12px; padding: 10px 18px; color: white; font-size: 13px; font-weight: 500; text-align: center;">
                <div style="font-size: 20px; font-weight: 700;">&#8369;{{ number_format($totalEarnings, 0) }}</div>
                <div style="font-size: 11px; opacity: 0.8;">Earnings</div>
            </div>
        </div>
    </div>

    <div class="staff-stat-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 1.25rem;">
        <div style="background: white; border-radius: 14px; padding: 1.5rem; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07); border: 1px solid #f1f5f9; display: flex; align-items: center; gap: 1rem;">
            <div style="width: 50px; height: 50px; border-radius: 12px; background: #E1F5EE; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">&#x1F4CB;</div>
            <div>
                <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 4px;">Total Assigned</div>
                <div class="stat-number" style="font-size: 30px; font-weight: 700; color: #1e293b; line-height: 1;">{{ $totalBookings }}</div>
            </div>
        </div>
        <div style="background: white; border-radius: 14px; padding: 1.5rem; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07); border: 1px solid #f1f5f9; display: flex; align-items: center; gap: 1rem;">
            <div style="width: 50px; height: 50px; border-radius: 12px; background: #f0fdf4; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">&#x2705;</div>
            <div>
                <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 4px;">Completed</div>
                <div class="stat-number" style="font-size: 30px; font-weight: 700; color: #1e293b; line-height: 1;">{{ $completedBookings }}</div>
            </div>
        </div>
        <div style="background: white; border-radius: 14px; padding: 1.5rem; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07); border: 1px solid #f1f5f9; display: flex; align-items: center; gap: 1rem;">
            <div style="width: 50px; height: 50px; border-radius: 12px; background: #fdf4ff; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">&#9881;</div>
            <div>
                <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 4px;">In Progress</div>
                <div class="stat-number" style="font-size: 30px; font-weight: 700; color: #1e293b; line-height: 1;">{{ $inProgress }}</div>
            </div>
        </div>
        <div style="background: white; border-radius: 14px; padding: 1.5rem; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07); border: 1px solid #f1f5f9; display: flex; align-items: center; gap: 1rem;">
            <div style="width: 50px; height: 50px; border-radius: 12px; background: #f0fdf4; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">&#x1F4B0;</div>
            <div>
                <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 4px;">Total Earnings</div>
                <div class="stat-number" style="font-size: 22px; font-weight: 700; color: #16a34a; line-height: 1;">&#8369;{{ number_format($totalEarnings, 0) }}</div>
            </div>
        </div>
    </div>

    <div class="staff-info-grid" style="display: grid; grid-template-columns: 340px 1fr; gap: 1.25rem;">
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div style="background: white; border-radius: 14px; padding: 1.5rem; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07); border: 1px solid #f1f5f9;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                    <div style="font-size: 15px; font-weight: 700; color: #1e293b;">My Information</div>
                    <a href="{{ route('staff.profile') }}" style="font-size: 12px; color: #1D9E75; text-decoration: none; background: #E1F5EE; padding: 5px 12px; border-radius: 8px; font-weight: 500;">Edit</a>
                </div>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div style="display: flex; align-items: center; gap: 12px; padding-bottom: 12px; border-bottom: 1px solid #f8fafc;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: #1D9E75; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 16px; flex-shrink: 0;">{{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}</div>
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 15px;">{{ $user->first_name }} {{ $user->last_name }}</div>
                            <div style="font-size: 12px; color: #94a3b8;">{{ $user->email }}</div>
                        </div>
                    </div>
                    @php $rate = $totalBookings > 0 ? round(($completedBookings / $totalBookings) * 100, 1) : 0; @endphp
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div style="background: #f8fafc; border-radius: 10px; padding: 12px; text-align: center;">
                            <div style="font-size: 20px; font-weight: 700; color: {{ $rate >= 70 ? '#16a34a' : ($rate >= 40 ? '#d97706' : '#dc2626') }};">{{ $rate }}%</div>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Completion Rate</div>
                        </div>
                        <div style="background: #f8fafc; border-radius: 10px; padding: 12px; text-align: center;">
                            <div style="font-size: 20px; font-weight: 700; color: #f59e0b;">{{ $avgRating ?? '-' }}</div>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Avg Rating</div>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Phone</div>
                        <div style="font-size: 14px; color: #1e293b; font-weight: 500;">{{ $user->phone ?? 'Not set' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Assigned Barangay</div>
                        <div style="font-size: 14px; color: #1e293b; font-weight: 500;">{{ ucfirst($user->barangay ?? 'N/A') }}</div>
                    </div>
                </div>
            </div>

            <div style="background: white; border-radius: 14px; padding: 1.5rem; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07); border: 1px solid #f1f5f9;">
                <div style="font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 1rem;">Quick Actions</div>
                <div class="staff-quick-actions" style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="{{ route('staff.bookings') }}" style="display: flex; align-items: center; gap: 10px; background: #E1F5EE; border-radius: 10px; padding: 12px; text-decoration: none;">
                        <span style="font-size: 18px;">&#x1F4CB;</span>
                        <span style="font-size: 13px; font-weight: 600; color: #0F6E56;">My Bookings</span>
                    </a>
                    <a href="{{ route('staff.performance') }}" style="display: flex; align-items: center; gap: 10px; background: #fefce8; border-radius: 10px; padding: 12px; text-decoration: none;">
                        <span style="font-size: 18px;">&#x1F4CA;</span>
                        <span style="font-size: 13px; font-weight: 600; color: #a16207;">My Performance</span>
                    </a>
                    <a href="{{ route('staff.schedule') }}" style="display: flex; align-items: center; gap: 10px; background: #f0fdf4; border-radius: 10px; padding: 12px; text-decoration: none;">
                        <span style="font-size: 18px;">&#x1F4C6;</span>
                        <span style="font-size: 13px; font-weight: 600; color: #15803d;">My Schedule</span>
                    </a>
                </div>
            </div>
        </div>

        <div style="background: white; border-radius: 14px; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07); border: 1px solid #f1f5f9; overflow: hidden;">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 15px; font-weight: 700; color: #1e293b;">Active Assignments</div>
                <span style="font-size: 12px; color: #94a3b8; background: #f8fafc; padding: 4px 10px; border-radius: 20px;">{{ $assignedBookings->count() }} booking(s)</span>
            </div>
            @if($assignedBookings->count())
            <div class="staff-table-wrap" style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th style="text-align: left; padding: 10px 16px; color: #94a3b8; font-weight: 600; font-size: 11px; text-transform: uppercase;">Booking</th>
                            <th style="text-align: left; padding: 10px 16px; color: #94a3b8; font-weight: 600; font-size: 11px; text-transform: uppercase;">Client</th>
                            <th style="text-align: left; padding: 10px 16px; color: #94a3b8; font-weight: 600; font-size: 11px; text-transform: uppercase;">Service</th>
                            <th style="text-align: left; padding: 10px 16px; color: #94a3b8; font-weight: 600; font-size: 11px; text-transform: uppercase;">Schedule</th>
                            <th style="text-align: left; padding: 10px 16px; color: #94a3b8; font-weight: 600; font-size: 11px; text-transform: uppercase;">Status</th>
                            <th style="text-align: left; padding: 10px 16px; color: #94a3b8; font-weight: 600; font-size: 11px; text-transform: uppercase;">Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignedBookings as $booking)
                        @php
                            $isToday = \Carbon\Carbon::parse($booking->scheduled_date)->isToday();
                            $colors = [
                                'confirmed' => '#E1F5EE|#1D9E75',
                                'in_progress' => '#f3e8ff|#9333ea',
                            ];
                            $c = explode('|', $colors[$booking->status] ?? '#f1f5f9|#64748b');
                        @endphp
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 14px 16px;">
                                <span style="font-weight: 700; color: #1D9E75; font-family: monospace;">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</span>
                                @if($isToday)
                                <span style="background: #dcfce7; color: #16a34a; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px; margin-left: 4px;">Today</span>
                                @endif
                            </td>
                            <td style="padding: 14px 16px;">
                                <div style="font-weight: 600; color: #1e293b;">{{ $booking->user->first_name }} {{ $booking->user->last_name }}</div>
                                <div style="font-size: 11px; color: #94a3b8;">{{ substr($booking->user->phone ?? '', 0, 4) }}****</div>
                            </td>
                            <td style="padding: 14px 16px; color: #374151;">{{ $booking->service_label }}</td>
                            <td style="padding: 14px 16px;">
                                <div style="font-weight: 600; color: #1e293b;">{{ \Carbon\Carbon::parse($booking->scheduled_date)->format('M d, Y') }}</div>
                                <div style="font-size: 11px; color: #94a3b8;">{{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}</div>
                            </td>
                            <td style="padding: 14px 16px;">
                                <span style="background: {{ $c[0] }}; color: {{ $c[1] }}; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                            </td>
                            <td style="padding: 14px 16px;">
                                @if($booking->status === 'confirmed')
                                <form action="{{ route('staff.bookings.status', $booking->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="in_progress">
                                    <button type="submit"
                                        style="background: #E1F5EE; color: #1D9E75; border: 1px solid #bfdbfe; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 700; cursor: pointer;">
                                        Start Job
                                    </button>
                                </form>
                                @elseif($booking->status === 'in_progress')
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <button onclick="startTracking({{ $booking->id }})"
                                        id="track-btn-{{ $booking->id }}"
                                        data-location-update-url="{{ route('booking.location.update', $booking->id) }}"
                                        style="background: #dcfce7; color: #16a34a; border: 1px solid #86efac; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 700; cursor: pointer; width: 100%;">
                                        Share Location
                                    </button>
                                    <form action="{{ route('staff.bookings.status', $booking->id) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit"
                                            onclick="stopTracking({{ $booking->id }})"
                                            style="background: #f0fdf4; color: #15803d; border: 1px solid #86efac; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 700; cursor: pointer; width: 100%;">
                                            Complete Job
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="text-align: center; padding: 3rem 1rem; color: #94a3b8;">
                <div style="font-size: 48px; margin-bottom: 12px; opacity: 0.3;">&#x1F4CB;</div>
                <p style="font-size: 15px; font-weight: 500; margin-bottom: 6px; color: #64748b;">No active assignments right now</p>
                <p style="font-size: 13px;">Confirmed and in-progress bookings will appear here when work is assigned to you.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
const watchIds = {};

function startTracking(bookingId) {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported on this device.');
        return;
    }

    const btn = document.getElementById('track-btn-' + bookingId);
    const locationUpdateUrl = btn?.dataset.locationUpdateUrl;

    if (!locationUpdateUrl) {
        alert('Location sharing is not configured for this booking yet.');
        return;
    }

    if (btn) {
        btn.innerHTML = 'Tracking Live...';
        btn.style.background = '#16a34a';
        btn.style.color = 'white';
        btn.disabled = true;
    }

    watchIds[bookingId] = navigator.geolocation.watchPosition(
        async (position) => {
            try {
                await fetch(locationUpdateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        speed: position.coords.speed,
                        heading: position.coords.heading
                    })
                });
                console.log('Location sent:', position.coords.latitude, position.coords.longitude);
            } catch (e) {
                console.error('Location update failed:', e);
            }
        },
        (error) => {
            console.error('GPS error:', error);
            alert('Could not get location. Please allow location access.');
            if (btn) {
                btn.innerHTML = 'Share Location';
                btn.style.background = '#dcfce7';
                btn.style.color = '#16a34a';
                btn.disabled = false;
            }
        },
        {
            enableHighAccuracy: true,
            maximumAge: 5000,
            timeout: 10000
        }
    );
}

function stopTracking(bookingId) {
    if (watchIds[bookingId] !== undefined) {
        navigator.geolocation.clearWatch(watchIds[bookingId]);
        delete watchIds[bookingId];
        console.log('Tracking stopped for booking:', bookingId);
    }
}
</script>
@endsection

