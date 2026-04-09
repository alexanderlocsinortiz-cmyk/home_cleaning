@extends('layouts.staff')
@section('title', 'My Bookings - Home Cleaning Service')
@section('page-title', 'My Bookings')
@section('page-subtitle', 'All your assigned bookings')

@section('content')
<style>
@media (max-width: 767px) {
    .sb-page-wrap { padding: 0.875rem !important; }
    .sb-header { flex-direction: column !important; align-items: flex-start !important; gap: 10px !important; }
    .sb-stat-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 0.875rem !important; }
    .sb-table-wrap { overflow-x: auto !important; -webkit-overflow-scrolling: touch !important; }
    .sb-table-wrap table { min-width: 600px !important; }
    .sb-table-wrap th:nth-child(3),
    .sb-table-wrap td:nth-child(3) { display: none !important; }
    .sb-header h1 { font-size: 20px !important; }
}
</style>
<style>
  .sf-page { max-width: 1100px; margin: 0 auto; padding: 1rem 1.5rem 2rem; font-family: 'DM Sans', sans-serif; }
  .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 1.5rem; }
  .filter-tab { padding: 7px 16px; border-radius: 20px; font-size: 13px; font-weight: 500; text-decoration: none; border: 1px solid #e5e7eb; background: white; color: #6b7280; transition: all 0.15s; }
  .filter-tab:hover { border-color: #185FA5; color: #185FA5; }
  .filter-tab.active { background: #185FA5; color: white; border-color: #185FA5; }
  .filter-tab span { background: rgba(0,0,0,0.1); border-radius: 10px; padding: 1px 7px; font-size: 11px; margin-left: 4px; }
  .filter-tab.active span { background: rgba(255,255,255,0.25); }
  .sb-stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
  .sb-stat-card { background: white; border-radius: 14px; padding: 1rem 1.25rem; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
  .sb-stat-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 4px; }
  .sb-stat-value { font-size: 24px; font-weight: 700; color: #1a1a2e; line-height: 1; }
  .sf-card { background: white; border-radius: 14px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
  .sf-card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: space-between; }
  .sf-card-title { font-size: 15px; font-weight: 600; color: #1a1a2e; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  thead th { text-align: left; padding: 0.75rem 1rem; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: 500; background: #f5f7fa; border-bottom: 1px solid rgba(0,0,0,0.08); }
  tbody td { padding: 1rem; border-bottom: 1px solid rgba(0,0,0,0.06); vertical-align: middle; }
  tbody tr:last-child td { border-bottom: none; }
  tbody tr:hover td { background: #fafbfc; }
  .booking-num { font-weight: 600; color: #185FA5; font-family: monospace; }
  .cf-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; }
  .badge-confirmed { background: #E6F1FB; color: #185FA5; }
  .badge-in_progress { background: #f3e8ff; color: #9333ea; }
  .badge-completed { background: #EAF3DE; color: #3B6D11; }
  .badge-cancelled { background: #FCEBEB; color: #A32D2D; }
  .badge-pending { background: #FAEEDA; color: #BA7517; }
  .stars { color: #f59e0b; font-size: 12px; }
  .status-select { font-size: 12px; border: 1px solid #e5e7eb; border-radius: 8px; padding: 5px 10px; background: #f5f7fa; cursor: pointer; font-family: 'DM Sans', sans-serif; }
  .status-select:focus { outline: none; border-color: #378ADD; }
</style>

<div class="sf-page sb-page-wrap">
  <div class="sb-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
    <div>
      <h1 style="font-size:24px;font-weight:700;color:#1a1a2e;margin-bottom:4px;">Assigned Bookings</h1>
      <p style="font-size:13px;color:#6b7280;">Review and update your assigned jobs.</p>
    </div>
  </div>

  @if(session('success'))
  <div style="background:#dcfce7;border:1px solid #86efac;color:#16a34a;border-radius:10px;padding:12px 16px;margin-bottom:1rem;font-size:14px;">
    &#10004; {{ session('success') }}
  </div>
  @endif

  @if($errors->any())
  <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;border-radius:10px;padding:12px 16px;margin-bottom:1rem;font-size:14px;">
    <div style="font-weight:700;margin-bottom:6px;">Please fix the following:</div>
    @foreach($errors->all() as $error)
    <div>&bull; {{ $error }}</div>
    @endforeach
  </div>
  @endif

  <div class="sb-stat-grid">
    <div class="sb-stat-card">
      <div class="sb-stat-label">All</div>
      <div class="sb-stat-value">{{ $counts['all'] }}</div>
    </div>
    <div class="sb-stat-card">
      <div class="sb-stat-label">Confirmed</div>
      <div class="sb-stat-value" style="color:#185FA5;">{{ $counts['confirmed'] }}</div>
    </div>
    <div class="sb-stat-card">
      <div class="sb-stat-label">In Progress</div>
      <div class="sb-stat-value" style="color:#9333ea;">{{ $counts['in_progress'] }}</div>
    </div>
    <div class="sb-stat-card">
      <div class="sb-stat-label">Completed</div>
      <div class="sb-stat-value" style="color:#16a34a;">{{ $counts['completed'] }}</div>
    </div>
  </div>

  <div class="filter-tabs">
    <a href="{{ route('staff.bookings') }}" class="filter-tab {{ $status === 'all' ? 'active' : '' }}">
      All <span>{{ $counts['all'] }}</span>
    </a>
    <a href="{{ route('staff.bookings', ['status' => 'confirmed']) }}" class="filter-tab {{ $status === 'confirmed' ? 'active' : '' }}">
      Confirmed <span>{{ $counts['confirmed'] }}</span>
    </a>
    <a href="{{ route('staff.bookings', ['status' => 'in_progress']) }}" class="filter-tab {{ $status === 'in_progress' ? 'active' : '' }}">
      In Progress <span>{{ $counts['in_progress'] }}</span>
    </a>
    <a href="{{ route('staff.bookings', ['status' => 'completed']) }}" class="filter-tab {{ $status === 'completed' ? 'active' : '' }}">
      Completed <span>{{ $counts['completed'] }}</span>
    </a>
    <a href="{{ route('staff.bookings', ['status' => 'cancelled']) }}" class="filter-tab {{ $status === 'cancelled' ? 'active' : '' }}">
      Cancelled <span>{{ $counts['cancelled'] }}</span>
    </a>
  </div>

  <div class="sf-card">
    <div class="sf-card-header">
      <p class="sf-card-title">
        {{ $status === 'all' ? 'All Bookings' : ucfirst(str_replace('_', ' ', $status)) . ' Bookings' }}
      </p>
      <span style="font-size:12px;color:#6b7280;">{{ $bookings->total() }} booking(s)</span>
    </div>
    @if($bookings->count())
    <div class="sb-table-wrap" style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>Booking #</th>
            <th>Client</th>
            <th>Service</th>
            <th>Address</th>
            <th>Scheduled</th>
            <th>Price</th>
            <th>Status</th>
            <th>Rating</th>
            @if($status !== 'completed' && $status !== 'cancelled')
            <th>Update</th>
            @endif
          </tr>
        </thead>
        <tbody>
          @foreach($bookings as $booking)
          <tr>
            <td class="booking-num">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</td>
            <td>
              <div style="font-weight:500;">{{ $booking->user->first_name }} {{ $booking->user->last_name }}</div>
              <div style="font-size:12px;color:#6b7280;">{{ $booking->user->phone ?? '' }}</div>
            </td>
            <td style="font-weight:500;">{{ $booking->service_label }}</td>
            <td>
              <div>{{ $booking->street_address }}</div>
              <div style="font-size:12px;color:#6b7280;">{{ ucfirst($booking->barangay) }}</div>
            </td>
            <td>
              <div style="font-weight:500;">{{ \Carbon\Carbon::parse($booking->scheduled_date)->format('M d, Y') }}</div>
              <div style="font-size:12px;color:#6b7280;">{{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}</div>
            </td>
            <td style="font-weight:600;color:#16a34a;">&#8369;{{ number_format($booking->price, 2) }}</td>
            <td>
              <span class="cf-badge badge-{{ $booking->status }}">
                {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
              </span>
            </td>
            <td>
              @if($booking->rating)
                <div class="stars">
                  @for($i = 1; $i <= 5; $i++)
                    {!! $i <= $booking->rating->stars ? '&#9733;' : '&#9734;' !!}
                  @endfor
                </div>
                @if($booking->rating->comment)
                <div style="font-size:11px;color:#6b7280;margin-top:2px;">{{ \Illuminate\Support\Str::limit($booking->rating->comment, 30) }}</div>
                @endif
              @else
                <span style="color:#9ca3af;font-size:12px;">No rating</span>
              @endif
            </td>
            @if($status !== 'completed' && $status !== 'cancelled')
            <td>
              @if(in_array($booking->status, ['confirmed', 'in_progress']))
              <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-start;">
                @if($booking->status === 'confirmed')
                <form action="{{ route('staff.bookings.status', $booking->id) }}" method="POST">
                  @csrf @method('PATCH')
                  <input type="hidden" name="status" value="in_progress">
                  <button type="submit" class="status-select" style="background:#E6F1FB;color:#185FA5;border-color:#bfdbfe;font-weight:600;">
                    Start Job
                  </button>
                </form>
                @elseif($booking->status === 'in_progress')
                <button onclick="shareLocation({{ $booking->id }})"
                    style="background:#1D9E75;color:white;border:none;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:600;cursor:pointer;">
                    &#x1F4CD; Share Location
                </button>
                <form action="{{ route('staff.bookings.status', $booking->id) }}" method="POST">
                  @csrf @method('PATCH')
                  <input type="hidden" name="status" value="completed">
                  <button type="submit" class="status-select" style="background:#E1F5EE;color:#0F6E56;border-color:#bbf7d0;font-weight:600;">
                    Complete Job
                  </button>
                </form>
                @endif
              </div>
              @else
                <span style="color:#9ca3af;font-size:12px;">-</span>
              @endif
            </td>
            @endif
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div style="padding:1rem 1.5rem;border-top:1px solid rgba(0,0,0,0.08);">
      {{ $bookings->appends(['status' => $status])->links('pagination::tailwind') }}
    </div>
    @else
    <div style="text-align:center;padding:3rem 1rem;color:#6b7280;">
      <div style="font-size:48px;margin-bottom:12px;opacity:0.3;">&#128203;</div>
      <p style="font-size:15px;font-weight:500;margin-bottom:6px;">No bookings to display</p>
      <p style="font-size:13px;">{{ $status === 'all' ? 'Assigned bookings will appear here once jobs are given to you.' : 'There are no ' . str_replace('_', ' ', $status) . ' bookings to show right now.' }}</p>
    </div>
    @endif
  </div>

</div>
@endsection

@push('scripts')
<script>
async function shareLocation(bookingId) {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }

    navigator.geolocation.getCurrentPosition(async function (position) {
        try {
            const response = await fetch('/bookings/' + bookingId + '/location/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                })
            });

            if (!response.ok) {
                const payload = await response.json().catch(function () {
                    return null;
                });

                alert(payload && payload.message ? payload.message : 'Location sharing failed.');
                return;
            }

            alert('Location shared successfully!');
        } catch (error) {
            alert('Location sharing failed. Please try again.');
        }
    }, function () {
        alert('Could not get your location. Please allow location access.');
    });
}
</script>
@endpush

