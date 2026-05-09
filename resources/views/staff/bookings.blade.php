@extends('layouts.staff')
@section('title', 'My Bookings - Home Cleaning Service')
@section('page-title', 'My Bookings')
@section('page-subtitle', 'All your assigned bookings')

@section('content')
<div class="staff-bookings-page cleanflow-page-shell">
  <div class="cleanflow-hero mb-6 px-7 py-6 text-white">
    <div class="cleanflow-hero-content sb-header sb-hero-header">
      <div>
        <span class="cleanflow-kicker">
          <i class="fas fa-briefcase"></i>
          Staff Operations
        </span>
        <h1 class="sb-hero-title">Assigned Bookings</h1>
        <p class="sb-hero-copy">Review your assigned jobs, upload proof of service, and keep client updates moving on time.</p>
      </div>
      <div class="sb-workload-card">
        <div class="sb-workload-label">Current workload</div>
        <div class="sb-workload-value">{{ $counts['all'] }}</div>
        <div class="sb-workload-copy">{{ ucfirst(str_replace('_', ' ', $status)) }} booking view</div>
      </div>
    </div>
  </div>

  @if(session('success'))
  <div class="cleanflow-alert cleanflow-alert--success mb-4 text-sm">
    &#10004; {{ session('success') }}
  </div>
  @endif

  @if($errors->any())
  <div class="cleanflow-alert cleanflow-alert--error mb-4 text-sm">
    <div class="mb-1.5 font-bold">Finish these details before updating the booking:</div>
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
      <div class="sb-stat-value sb-stat-value--confirmed">{{ $counts['confirmed'] }}</div>
    </div>
    <div class="sb-stat-card">
      <div class="sb-stat-label">In Progress</div>
      <div class="sb-stat-value sb-stat-value--progress">{{ $counts['in_progress'] }}</div>
    </div>
    <div class="sb-stat-card">
      <div class="sb-stat-label">Completed</div>
      <div class="sb-stat-value sb-stat-value--completed">{{ $counts['completed'] }}</div>
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
      <span class="sb-count-meta">{{ $bookings->total() }} booking(s)</span>
    </div>
    @if($bookings->count())
    <div class="sb-table-wrap">
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
            <th>Proof</th>
            <th>Rating</th>
            @if($status !== 'completed' && $status !== 'cancelled')
            <th>Update</th>
            @endif
          </tr>
        </thead>
        <tbody>
          @foreach($bookings as $booking)
          <tr>
            <td>
              <div class="booking-num">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</div>
              <a href="{{ route('bookings.show', $booking->id) }}" class="details-link">View details</a>
            </td>
            <td>
              <div class="sb-client-name">{{ $booking->user->display_name }}</div>
              <div class="sb-client-phone">{{ $booking->user->phone ?? '' }}</div>
            </td>
            <td class="sb-service-name">{{ $booking->service_label }}</td>
            <td>
              <div>{{ $booking->street_address }}</div>
              <div class="sb-address-meta">{{ ucfirst($booking->barangay) }}</div>
            </td>
            <td>
              <div class="sb-schedule-date">{{ \Carbon\Carbon::parse($booking->scheduled_date)->format('M d, Y') }}</div>
              <div class="sb-schedule-time">{{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}</div>
            </td>
            <td class="sb-price">&#8369;{{ number_format($booking->price, 2) }}</td>
            <td>
              <span class="cf-badge badge-{{ $booking->status }}">
                {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
              </span>
            </td>
            <td>
              <div class="sb-proof-stack">
                <span class="proof-stat">Before: {{ $booking->before_photo_count }}</span>
                <span class="proof-stat">After: {{ $booking->after_photo_count }}</span>
                <span class="proof-stat">Video: {{ $booking->completion_video_count }}</span>
              </div>
            </td>
            <td>
              @if($booking->rating)
                <div class="stars">
                  @for($i = 1; $i <= 5; $i++)
                    {!! $i <= $booking->rating->stars ? '&#9733;' : '&#9734;' !!}
                  @endfor
                </div>
                @if($booking->rating->comment)
                <div class="sb-rating-note">{{ \Illuminate\Support\Str::limit($booking->rating->comment, 30) }}</div>
                @endif
              @else
                <span class="sb-rating-empty">No client rating yet</span>
              @endif
            </td>
            @if($status !== 'completed' && $status !== 'cancelled')
            <td>
              @if(in_array($booking->status, ['confirmed', 'in_progress']))
              <div class="sb-update-stack">
                @if($booking->status === 'confirmed')
                <form action="{{ route('staff.bookings.status', $booking->id) }}" method="POST" enctype="multipart/form-data" class="status-form-stack">
                  @csrf @method('PATCH')
                  <input type="hidden" name="status" value="in_progress">
                  <input type="file" name="before_photos[]" accept="image/*" multiple required class="status-file-input">
                  <div class="status-hint">Add at least one before-service photo before starting this booking.</div>
                  <button type="submit" class="status-submit-btn status-submit-btn--start">
                    Start Service
                  </button>
                </form>
                @elseif($booking->status === 'in_progress')
                <button onclick="shareLocation({{ $booking->id }})" class="share-location-btn">
                    <i class="fas fa-location-arrow"></i>
                    <span>Share Live Location</span>
                </button>
                <form action="{{ route('staff.bookings.status', $booking->id) }}" method="POST" enctype="multipart/form-data" class="status-form-stack">
                  @csrf @method('PATCH')
                  <input type="hidden" name="status" value="completed">
                  <input type="file" name="after_photos[]" accept="image/*" multiple required class="status-file-input">
                  <input type="file" name="completion_video" accept="video/mp4,video/quicktime,video/webm,video/x-msvideo" class="status-file-input">
                  <div class="status-hint">Add after-service photos before finishing the booking. A completion video is optional.</div>
                  <button type="submit" class="status-submit-btn status-submit-btn--complete">
                    Complete Service
                  </button>
                </form>
                @endif
              </div>
              @else
                <span class="sb-update-empty">-</span>
              @endif
            </td>
            @endif
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="sb-pagination">
      {{ $bookings->appends(['status' => $status])->links('pagination::tailwind') }}
    </div>
    @else
    <div class="sb-empty-state">
      <div class="sb-empty-icon"><i class="far fa-clipboard"></i></div>
      <p class="sb-empty-title">No bookings in this view</p>
      <p class="sb-empty-copy">{{ $status === 'all' ? 'Bookings assigned to you will appear here once the admin team dispatches work.' : 'You do not have any ' . str_replace('_', ' ', $status) . ' bookings right now.' }}</p>
    </div>
    @endif
  </div>

</div>
@endsection

@push('scripts')
<script>
async function shareLocation(bookingId) {
    if (!navigator.geolocation) {
        alert('This device does not support location sharing.');
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

                alert(payload && payload.message ? payload.message : 'We could not share your location. Please try again.');
                return;
            }

            alert('Live location shared with the client.');
        } catch (error) {
            alert('We could not share your location. Please try again.');
        }
    }, function () {
        alert('We could not access your location. Please allow location sharing and try again.');
    });
}
</script>
@endpush

