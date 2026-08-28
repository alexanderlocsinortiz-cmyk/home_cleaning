@extends('layouts.staff')
@section('title', 'My Bookings - Home Cleaning Service')
@section('page-title', 'My Bookings')
@section('page-subtitle', 'All your assigned bookings')

@section('content')
@php
  $proofMaxVideoMb = (int) floor(config('cleanflow.proof_uploads.max_video_kb', 10240) / 1024);
  $proofMaxRequestMb = (int) floor(config('cleanflow.proof_uploads.max_request_kb', 32768) / 1024);
@endphp
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
    <div class="sb-stat-card sb-stat-card--all">
      <div class="sb-stat-label">All</div>
      <div class="sb-stat-value">{{ $counts['all'] }}</div>
    </div>
    <div class="sb-stat-card sb-stat-card--confirmed">
      <div class="sb-stat-label">Confirmed</div>
      <div class="sb-stat-value sb-stat-value--confirmed">{{ $counts['confirmed'] }}</div>
    </div>
    <div class="sb-stat-card sb-stat-card--progress">
      <div class="sb-stat-label">In Progress</div>
      <div class="sb-stat-value sb-stat-value--progress">{{ $counts['in_progress'] }}</div>
    </div>
    <div class="sb-stat-card sb-stat-card--completed">
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
    <div class="booking-card-list">
      @foreach($bookings as $booking)
      @php
        $hasClientPin = filled($booking->service_latitude) && filled($booking->service_longitude);
        $clientAddress = $booking->street_address . ', ' . ucfirst($booking->barangay) . ', Valencia City, Bukidnon';
        $beforeProofs = $booking->serviceProofs->where('stage', 'before')->where('media_type', 'image')->values();
        $afterProofs = $booking->serviceProofs->where('stage', 'after')->where('media_type', 'image')->values();
        $completionVideos = $booking->serviceProofs->where('stage', 'after')->where('media_type', 'video')->values();
      @endphp
      <article class="booking-card">
        <div class="booking-card-main">
          <div class="booking-card-head">
            <div>
              <div class="booking-num">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</div>
              <h3 class="booking-card-title">{{ $booking->service_label }}</h3>
              <div class="booking-card-meta">{{ \Carbon\Carbon::parse($booking->scheduled_date)->format('M d, Y') }} at {{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}</div>
            </div>
            <div class="booking-card-status">
              <span class="cf-badge badge-{{ $booking->status }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
              <span class="sb-price">&#8369;{{ number_format($booking->price, 2) }}</span>
            </div>
          </div>

          <div class="booking-info-grid">
            <section class="booking-info-block">
              <span class="booking-info-label">Client</span>
              <div class="sb-client-name">{{ $booking->user->display_name }}</div>
              <div class="sb-client-phone">{{ $booking->user->phone ?? 'No phone saved' }}</div>
            </section>
            <section class="booking-info-block booking-info-block--wide">
              <span class="booking-info-label">Address</span>
              <div>{{ $booking->street_address }}</div>
              <div class="sb-address-meta">{{ ucfirst($booking->barangay) }}, Valencia City</div>
              @if($hasClientPin)
              <div class="route-action-row">
                <button type="button" class="staff-route-btn" data-route-panel="staff-route-panel-{{ $booking->id }}" data-map-target="staff-booking-map-{{ $booking->id }}">
                  <i class="fas fa-route"></i>
                  Show route
                </button>
                <span id="staff-booking-map-{{ $booking->id }}-status" class="staff-route-status">Client pin saved</span>
              </div>
              @else
              <div class="route-warning">
                No client map pin was saved. Use the written address and ask admin to confirm the pinned location.
              </div>
              @endif
            </section>
            <section class="booking-info-block">
              <span class="booking-info-label">Rating</span>
              @if($booking->rating)
                <div class="stars">
                  @for($i = 1; $i <= 5; $i++)
                    {!! $i <= $booking->rating->stars ? '&#9733;' : '&#9734;' !!}
                  @endfor
                </div>
                @if($booking->rating->comment)
                <div class="sb-rating-note">{{ \Illuminate\Support\Str::limit($booking->rating->comment, 42) }}</div>
                @endif
              @else
                <span class="sb-rating-empty">No client rating yet</span>
              @endif
            </section>
          </div>

          @if($hasClientPin)
          <div id="staff-route-panel-{{ $booking->id }}" class="staff-route-panel hidden">
            <div class="staff-route-panel-header">
              <div>
                <div class="staff-route-panel-title">Route to {{ $booking->user->display_name }}</div>
                <div class="staff-route-panel-address">{{ $clientAddress }}</div>
              </div>
              <button type="button" class="staff-route-close" data-route-panel="staff-route-panel-{{ $booking->id }}">
                <i class="fas fa-xmark"></i>
                Close
              </button>
            </div>
            <div
              id="staff-booking-map-{{ $booking->id }}"
              class="staff-booking-map"
              data-booking-id="{{ $booking->id }}"
              data-destination-lat="{{ $booking->service_latitude }}"
              data-destination-lng="{{ $booking->service_longitude }}"
              data-address="{{ $clientAddress }}"
              data-client="{{ $booking->user->display_name }}"
            ></div>
          </div>
          @endif
        </div>

        <aside class="booking-card-side">
          <div class="booking-side-section">
            <div class="booking-side-title">Proof</div>
            <div class="proof-count-grid">
              <span class="proof-stat">Before: {{ $booking->before_photo_count }}</span>
              <span class="proof-stat">After: {{ $booking->after_photo_count }}</span>
              <span class="proof-stat">Video: {{ $booking->completion_video_count }}</span>
            </div>
            @if($beforeProofs->count() || $afterProofs->count() || $completionVideos->count())
              <div class="proof-preview-list">
                @foreach($beforeProofs->take(2) as $proof)
                  <a href="{{ \Illuminate\Support\Facades\Storage::disk(config('filesystems.public_uploads_disk'))->url($proof->file_path) }}" target="_blank" class="proof-preview proof-preview--before">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('filesystems.public_uploads_disk'))->url($proof->file_path) }}" alt="Before-service proof">
                    <span>Before {{ $loop->iteration }}</span>
                  </a>
                @endforeach
                @foreach($afterProofs->take(2) as $proof)
                  <a href="{{ \Illuminate\Support\Facades\Storage::disk(config('filesystems.public_uploads_disk'))->url($proof->file_path) }}" target="_blank" class="proof-preview proof-preview--after">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('filesystems.public_uploads_disk'))->url($proof->file_path) }}" alt="After-service proof">
                    <span>After {{ $loop->iteration }}</span>
                  </a>
                @endforeach
                @foreach($completionVideos->take(1) as $proof)
                  <a href="{{ \Illuminate\Support\Facades\Storage::disk(config('filesystems.public_uploads_disk'))->url($proof->file_path) }}" target="_blank" class="proof-preview proof-preview--video">
                    <i class="fas fa-video"></i>
                    <span>{{ $proof->original_name ?: 'Video proof' }}</span>
                  </a>
                @endforeach
              </div>
              <a href="{{ route('bookings.show', $booking->id) }}" class="proof-details-link">Open all proof files</a>
            @else
              <span class="proof-empty-note">No proof uploaded yet</span>
            @endif
          </div>

          @if($status !== 'completed' && $status !== 'cancelled')
          <div class="booking-side-section">
            <div class="booking-side-title">Update</div>
            @if(in_array($booking->status, ['confirmed', 'in_progress']))
            <div class="sb-update-stack">
              @if($booking->status === 'confirmed')
              <button type="button" onclick="shareLocation({{ $booking->id }})" id="share-location-btn-{{ $booking->id }}" class="share-location-btn">
                <i class="fas fa-location-arrow"></i>
                <span>Share Live Location</span>
              </button>
              <form action="{{ route('staff.bookings.status', $booking->id) }}" method="POST" enctype="multipart/form-data" class="status-form-stack" novalidate>
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="in_progress">
                <label class="proof-upload-card proof-upload-card--before">
                  <span class="proof-upload-icon"><i class="fas fa-camera"></i></span>
                  <span class="proof-upload-body">
                    <span class="proof-upload-title">Before-service photos</span>
                    <span class="proof-upload-copy">Required before starting. Upload 1 to 4 photos, max 5 MB each.</span>
                    <span class="proof-upload-selected" data-empty-label="No before photos selected">No before photos selected</span>
                    <span class="proof-upload-error"></span>
                  </span>
                  <input type="file" name="before_photos[]" accept="image/*" multiple data-required-message="Select at least one before-service photo before starting." class="status-file-input proof-upload-input">
                </label>
                <button type="submit" class="status-submit-btn status-submit-btn--start">
                  Start Service
                </button>
                <div class="upload-progress-panel" role="status" aria-live="polite">
                  <span class="upload-spinner"></span>
                  <span class="upload-progress-copy">
                    <strong>Uploading before-service proof...</strong>
                    <span>Please keep this page open.</span>
                  </span>
                </div>
              </form>
              @elseif($booking->status === 'in_progress')
              <a href="{{ route('bookings.live-video', $booking) }}" class="live-video-btn">
                <i class="fas fa-video"></i>
                <span>Start Live Video</span>
              </a>
              @if($booking->dailyRoomIsActive())
              <form action="{{ route('bookings.live-video.end', $booking) }}" method="POST" onsubmit="return confirm('End the live video for this booking?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="end-live-video-btn">
                  <i class="fas fa-circle-stop"></i>
                  <span>End Live Video</span>
                </button>
              </form>
              @endif
              <button type="button" onclick="shareLocation({{ $booking->id }})" id="share-location-btn-{{ $booking->id }}" class="share-location-btn">
                <i class="fas fa-location-arrow"></i>
                <span>Share Live Location</span>
              </button>
              <form action="{{ route('staff.bookings.status', $booking->id) }}" method="POST" enctype="multipart/form-data" class="status-form-stack" novalidate>
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="completed">
                <label class="proof-upload-card proof-upload-card--after">
                  <span class="proof-upload-icon"><i class="fas fa-images"></i></span>
                  <span class="proof-upload-body">
                    <span class="proof-upload-title">After-service photos</span>
                    <span class="proof-upload-copy">Required to complete. Upload 1 to 4 photos, max 5 MB each.</span>
                    <span class="proof-upload-selected" data-empty-label="No after photos selected">No after photos selected</span>
                    <span class="proof-upload-error"></span>
                  </span>
                  <input type="file" name="after_photos[]" accept="image/*" multiple data-required-message="Select at least one after-service photo before completing." class="status-file-input proof-upload-input">
                </label>
                <label class="proof-upload-card proof-upload-card--video">
                  <span class="proof-upload-icon"><i class="fas fa-video"></i></span>
                  <span class="proof-upload-body">
                    <span class="proof-upload-title">Completion video</span>
                    <span class="proof-upload-copy">Optional video proof. Max {{ $proofMaxVideoMb }} MB. Total upload max {{ $proofMaxRequestMb }} MB.</span>
                    <span class="proof-upload-selected" data-empty-label="No video selected">No video selected</span>
                    <span class="proof-upload-error"></span>
                  </span>
                  <input type="file" name="completion_video" accept="video/mp4,video/quicktime,video/webm,video/x-msvideo" data-max-file-mb="{{ $proofMaxVideoMb }}" class="status-file-input proof-upload-input">
                </label>
                <button type="submit" class="status-submit-btn status-submit-btn--complete">
                  Complete Service
                </button>
                <div class="upload-progress-panel" role="status" aria-live="polite">
                  <span class="upload-spinner"></span>
                  <span class="upload-progress-copy">
                    <strong>Uploading completion proof...</strong>
                    <span>Photos and video may take a moment. Please keep this page open.</span>
                  </span>
                </div>
              </form>
              @endif
            </div>
            @else
              <span class="sb-update-empty">No action available</span>
            @endif
          </div>
          @endif

          <a href="{{ route('bookings.show', $booking->id) }}" class="booking-card-link">
            View details
            <i class="fas fa-arrow-right"></i>
          </a>
        </aside>
      </article>
      @endforeach
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

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
<style>
.staff-bookings-page {
    --sb-blue-50: #eff6ff;
    --sb-blue-100: #dbeafe;
    --sb-blue-200: #bfdbfe;
    --sb-blue-600: #2563eb;
    --sb-blue-700: #1d4ed8;
    --sb-blue-800: #1e40af;
    --sb-slate-50: #f8fafc;
    --sb-slate-100: #f1f5f9;
    --sb-slate-200: #e2e8f0;
    --sb-slate-500: #64748b;
    --sb-slate-700: #334155;
    --sb-slate-900: #0f172a;
    --sb-success-50: #ecfdf5;
    --sb-success-700: #047857;
    --sb-warning-50: #fffbeb;
    --sb-warning-700: #b45309;
    --sb-danger-50: #fef2f2;
    --sb-danger-700: #b91c1c;
}

.staff-bookings-page .sb-stat-card,
.staff-bookings-page .sf-card {
    border-color: var(--sb-blue-100);
}

.staff-bookings-page .sb-stat-grid {
    gap: 1.25rem;
}

.staff-bookings-page .sb-stat-card {
    position: relative;
    overflow: hidden;
    border: 1px solid var(--sb-blue-100);
    background: linear-gradient(180deg, #ffffff 0%, var(--sb-slate-50) 100%);
    box-shadow: 0 14px 32px rgba(30, 64, 175, 0.08);
}

.staff-bookings-page .sb-stat-card::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 0.25rem;
    background: var(--sb-blue-700);
    opacity: 0.85;
}

.staff-bookings-page .sb-stat-card--all {
    background: linear-gradient(180deg, #ffffff 0%, var(--sb-blue-50) 100%);
}

.staff-bookings-page .sb-stat-card--confirmed {
    background: linear-gradient(180deg, #ffffff 0%, var(--sb-blue-50) 100%);
}

.staff-bookings-page .sb-stat-card--progress {
    background: linear-gradient(180deg, #ffffff 0%, var(--sb-warning-50) 100%);
}

.staff-bookings-page .sb-stat-card--completed {
    background: linear-gradient(180deg, #ffffff 0%, var(--sb-success-50) 100%);
}

.staff-bookings-page .sb-stat-card--progress::before {
    background: var(--sb-warning-700);
}

.staff-bookings-page .sb-stat-card--completed::before {
    background: var(--sb-success-700);
}

.staff-bookings-page .sb-stat-label,
.staff-bookings-page .sb-count-meta,
.booking-info-label,
.booking-side-title {
    color: var(--sb-blue-700);
}

.staff-bookings-page .sb-stat-card--progress .sb-stat-label {
    color: var(--sb-warning-700);
}

.staff-bookings-page .sb-stat-card--completed .sb-stat-label {
    color: var(--sb-success-700);
}

.staff-bookings-page .sb-stat-value,
.staff-bookings-page .sb-stat-value--confirmed,
.staff-bookings-page .sb-stat-value--progress,
.staff-bookings-page .sb-stat-value--completed {
    color: var(--sb-slate-900);
}

.staff-bookings-page .filter-tabs {
    gap: 0.65rem;
}

.staff-bookings-page .filter-tab {
    border-color: var(--sb-blue-100);
    background: #ffffff;
    color: var(--sb-blue-800);
    box-shadow: 0 8px 18px rgba(30, 64, 175, 0.06);
}

.staff-bookings-page .filter-tab:hover {
    border-color: var(--sb-blue-200);
    background: var(--sb-blue-50);
    color: var(--sb-blue-700);
}

.staff-bookings-page .filter-tab.active {
    border-color: var(--sb-blue-700);
    background: var(--sb-blue-700);
    color: #ffffff;
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.2);
}

.staff-bookings-page .filter-tab span {
    background: var(--sb-blue-50);
    color: var(--sb-blue-800);
    border: 1px solid var(--sb-blue-100);
}

.staff-bookings-page .filter-tab.active span {
    background: rgba(255, 255, 255, 0.18);
    border-color: rgba(255, 255, 255, 0.24);
    color: #ffffff;
}

.staff-bookings-page .cf-badge {
    border: 1px solid transparent;
    font-weight: 800;
}

.staff-bookings-page .badge-confirmed {
    border-color: var(--sb-blue-200);
    background: var(--sb-blue-50);
    color: var(--sb-blue-700);
}

.staff-bookings-page .badge-in_progress {
    border-color: #fed7aa;
    background: var(--sb-warning-50);
    color: var(--sb-warning-700);
}

.staff-bookings-page .badge-completed {
    border-color: #a7f3d0;
    background: var(--sb-success-50);
    color: var(--sb-success-700);
}

.staff-bookings-page .badge-cancelled {
    border-color: #fecaca;
    background: var(--sb-danger-50);
    color: var(--sb-danger-700);
}

.staff-bookings-page .proof-stat {
    border: 1px solid var(--sb-blue-100);
    background: var(--sb-blue-50);
    color: var(--sb-blue-800);
}

.staff-bookings-page .sb-price {
    color: var(--sb-blue-800);
}

.booking-card-list {
    display: grid;
    gap: 1rem;
    padding: 1rem;
}

.booking-card {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(18rem, 24rem);
    overflow: hidden;
    border: 1px solid var(--sb-blue-100);
    border-radius: 1.35rem;
    background: #ffffff;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
}

.booking-card-main,
.booking-card-side {
    min-width: 0;
    padding: 1.25rem;
}

.booking-card-main {
    background: #ffffff;
}

.booking-card-side {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    border-left: 1px solid var(--sb-blue-100);
    background: var(--sb-slate-50);
}

.booking-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.booking-card-title {
    margin-top: 0.25rem;
    color: var(--sb-slate-900);
    font-size: 1.1rem;
    font-weight: 900;
    line-height: 1.25;
}

.booking-card-meta {
    margin-top: 0.25rem;
    color: var(--sb-slate-500);
    font-size: 0.78rem;
    font-weight: 700;
}

.booking-card-status {
    display: flex;
    flex-shrink: 0;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.55rem;
}

.booking-info-grid {
    display: grid;
    grid-template-columns: minmax(11rem, 0.85fr) minmax(16rem, 1.4fr) minmax(10rem, 0.75fr);
    gap: 0.85rem;
    margin-top: 1rem;
}

.booking-info-block,
.booking-side-section {
    min-width: 0;
    border: 1px solid var(--sb-blue-100);
    border-radius: 1rem;
    background: #ffffff;
    padding: 0.9rem;
}

.booking-info-label,
.booking-side-title {
    display: block;
    margin-bottom: 0.35rem;
    color: var(--sb-blue-700);
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.route-action-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem;
    margin-top: 0.65rem;
}

.route-warning {
    margin-top: 0.65rem;
    border: 1px solid #fed7aa;
    border-radius: 0.85rem;
    background: var(--sb-warning-50);
    padding: 0.65rem 0.75rem;
    color: var(--sb-warning-700);
    font-size: 0.72rem;
    font-weight: 700;
    line-height: 1.5;
}

.proof-count-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.booking-card-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    border: 1px solid var(--sb-blue-200);
    border-radius: 0.9rem;
    background: var(--sb-blue-50);
    padding: 0.7rem 0.9rem;
    color: var(--sb-blue-700);
    font-size: 0.78rem;
    font-weight: 900;
    text-decoration: none;
}

.booking-card-link:hover {
    background: var(--sb-blue-100);
}

.proof-upload-card {
    display: grid;
    grid-template-columns: 2.25rem minmax(0, 1fr);
    gap: 0.65rem;
    width: 100%;
    cursor: pointer;
    border: 1px solid var(--sb-blue-100);
    border-radius: 0.9rem;
    background: #ffffff;
    padding: 0.75rem;
    transition: border-color 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
}

.proof-upload-card:hover,
.proof-upload-card:focus-within {
    border-color: var(--sb-blue-200);
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
}

.proof-upload-card--invalid {
    border-color: #fca5a5;
    background: var(--sb-danger-50);
}

.proof-upload-card--before {
    border-color: var(--sb-blue-200);
    background: var(--sb-blue-50);
}

.proof-upload-card--after {
    border-color: var(--sb-blue-200);
    background: #ffffff;
}

.proof-upload-card--video {
    border-color: var(--sb-blue-200);
    background: #ffffff;
}

.proof-upload-icon {
    display: inline-flex;
    height: 2.25rem;
    width: 2.25rem;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    background: #ffffff;
    color: var(--sb-blue-700);
    font-size: 0.95rem;
}

.proof-upload-card--after .proof-upload-icon {
    color: var(--sb-blue-700);
}

.proof-upload-card--video .proof-upload-icon {
    color: var(--sb-blue-700);
}

.proof-upload-body {
    min-width: 0;
}

.proof-upload-title {
    display: block;
    color: var(--sb-slate-900);
    font-size: 0.78rem;
    font-weight: 900;
    line-height: 1.25;
}

.proof-upload-copy {
    display: block;
    margin-top: 0.2rem;
    color: var(--sb-slate-500);
    font-size: 0.68rem;
    font-weight: 600;
    line-height: 1.45;
}

.proof-upload-selected {
    display: block;
    margin-top: 0.45rem;
    overflow: hidden;
    color: var(--sb-blue-800);
    font-size: 0.68rem;
    font-weight: 800;
    line-height: 1.35;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.proof-upload-error {
    display: none;
    margin-top: 0.45rem;
    color: var(--sb-danger-700);
    font-size: 0.68rem;
    font-weight: 900;
    line-height: 1.4;
}

.proof-upload-card--invalid .proof-upload-error {
    display: block;
}

.upload-progress-panel {
    display: none;
    align-items: center;
    gap: 0.75rem;
    overflow: hidden;
    border: 1px solid var(--sb-blue-200);
    border-radius: 0.9rem;
    background: linear-gradient(90deg, var(--sb-blue-50), #ffffff, var(--sb-blue-50));
    background-size: 220% 100%;
    padding: 0.75rem 0.85rem;
    color: var(--sb-blue-800);
    animation: upload-panel-shimmer 1.4s linear infinite;
}

.status-form-stack--uploading .upload-progress-panel {
    display: flex;
}

.status-form-stack--uploading .status-submit-btn {
    cursor: wait;
    opacity: 0.72;
}

.upload-spinner {
    display: inline-block;
    height: 1.25rem;
    width: 1.25rem;
    flex-shrink: 0;
    border: 3px solid var(--sb-blue-100);
    border-top-color: var(--sb-blue-700);
    border-radius: 9999px;
    animation: upload-spinner-spin 0.8s linear infinite;
}

.upload-progress-copy {
    display: grid;
    gap: 0.1rem;
    min-width: 0;
    font-size: 0.72rem;
    line-height: 1.35;
}

.upload-progress-copy strong {
    color: var(--sb-blue-800);
    font-size: 0.76rem;
    font-weight: 900;
}

.upload-progress-copy span {
    color: var(--sb-slate-500);
    font-weight: 700;
}

@keyframes upload-spinner-spin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes upload-panel-shimmer {
    from {
        background-position: 0% 50%;
    }

    to {
        background-position: 220% 50%;
    }
}

.proof-upload-card--after .proof-upload-selected {
    color: var(--sb-blue-800);
}

.proof-upload-card--video .proof-upload-selected {
    color: var(--sb-blue-800);
}

.proof-upload-input {
    position: absolute;
    height: 1px;
    width: 1px;
    opacity: 0;
    pointer-events: none;
}

.proof-preview-list {
    display: grid;
    width: 100%;
    gap: 0.45rem;
    margin-top: 0.35rem;
}

.proof-preview {
    display: grid;
    grid-template-columns: 2.25rem minmax(0, 1fr);
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    border: 1px solid var(--sb-blue-100);
    border-radius: 0.75rem;
    background: #ffffff;
    padding: 0.4rem;
    color: var(--sb-blue-800);
    font-size: 0.68rem;
    font-weight: 900;
    text-decoration: none;
}

.proof-preview:hover {
    border-color: var(--sb-blue-200);
    background: var(--sb-blue-50);
}

.proof-preview img,
.proof-preview i {
    height: 2.25rem;
    width: 2.25rem;
    border-radius: 0.55rem;
}

.proof-preview img {
    object-fit: cover;
}

.proof-preview i {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--sb-blue-50);
    color: var(--sb-blue-700);
}

.proof-preview span {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.proof-preview--after {
    border-color: var(--sb-blue-100);
    color: var(--sb-blue-800);
}

.proof-preview--video {
    border-color: var(--sb-blue-100);
    color: var(--sb-blue-800);
}

.proof-details-link,
.proof-empty-note {
    margin-top: 0.15rem;
    font-size: 0.68rem;
    font-weight: 800;
}

.proof-details-link {
    color: var(--sb-blue-700);
    text-decoration: none;
}

.proof-details-link:hover {
    text-decoration: underline;
}

.proof-empty-note {
    color: var(--sb-slate-500);
}

.staff-booking-map {
    position: relative;
    z-index: 1;
    height: min(58vh, 460px);
    min-height: 340px;
    width: 100%;
    overflow: hidden;
    border: 1px solid var(--sb-blue-200);
    border-radius: 18px;
    background: var(--sb-blue-50);
}

.staff-route-panel-row > td {
    padding: 0;
    border-top: 1px solid var(--sb-blue-100);
    background: var(--sb-slate-50);
}

.staff-route-panel {
    margin-top: 1rem;
    padding: 1rem;
    border: 1px solid var(--sb-blue-100);
    border-radius: 1.1rem;
    background: linear-gradient(180deg, var(--sb-blue-50) 0%, #ffffff 100%);
}

.staff-route-panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.875rem;
}

.staff-route-panel-title {
    color: var(--sb-slate-900);
    font-size: 0.95rem;
    font-weight: 800;
}

.staff-route-panel-address {
    margin-top: 0.25rem;
    color: var(--sb-slate-500);
    font-size: 0.75rem;
    line-height: 1.5;
}

.staff-route-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    border: 1px solid var(--sb-blue-200);
    border-radius: 9999px;
    background: var(--sb-blue-50);
    padding: 0.35rem 0.65rem;
    color: var(--sb-blue-700);
    font-size: 0.6875rem;
    font-weight: 800;
    transition: background-color 0.2s ease, border-color 0.2s ease;
}

.staff-route-btn:hover {
    border-color: var(--sb-blue-200);
    background: var(--sb-blue-100);
}

.staff-route-close {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    gap: 0.375rem;
    border: 1px solid var(--sb-slate-200);
    border-radius: 9999px;
    background: #ffffff;
    padding: 0.4rem 0.75rem;
    color: var(--sb-slate-500);
    font-size: 0.75rem;
    font-weight: 800;
}

.staff-route-close:hover {
    background: var(--sb-slate-100);
}

.staff-route-status {
    color: var(--sb-slate-500);
    font-size: 0.6875rem;
    line-height: 1.4;
}

.staff-map-marker {
    display: flex;
    height: 26px;
    width: 26px;
    align-items: center;
    justify-content: center;
    border: 2px solid #ffffff;
    border-radius: 9999px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.25);
    color: #ffffff;
    font-size: 11px;
    font-weight: 900;
}

.staff-map-marker--client {
    background: var(--sb-blue-700);
}

.staff-map-marker--staff {
    background: var(--sb-blue-800);
}

@media (max-width: 640px) {
    .booking-card-list {
        padding: 0.75rem;
    }

    .booking-card {
        grid-template-columns: 1fr;
    }

    .booking-card-side {
        border-left: 0;
        border-top: 1px solid var(--sb-blue-100);
    }

    .booking-card-head {
        flex-direction: column;
    }

    .booking-card-status {
        align-items: flex-start;
    }

    .booking-info-grid {
        grid-template-columns: 1fr;
    }

    .staff-booking-map {
        height: 360px;
        min-height: 320px;
    }

    .staff-route-panel-header {
        flex-direction: column;
    }
}

@media (min-width: 641px) and (max-width: 1180px) {
    .booking-card {
        grid-template-columns: 1fr;
    }

    .booking-card-side {
        border-left: 0;
        border-top: 1px solid var(--sb-blue-100);
    }

    .booking-info-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .booking-info-block--wide {
        grid-column: 1 / -1;
    }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
const staffBookingMaps = {};
let staffCurrentPosition = null;
let staffLocationPromise = null;
const staffTravelMinutesPerKm = 5;

function estimateStaffTravelMinutes(distanceKm) {
    const numericDistance = Number.parseFloat(distanceKm);

    if (!Number.isFinite(numericDistance) || numericDistance <= 0) {
        return 1;
    }

    return Math.max(1, Math.round(numericDistance * staffTravelMinutesPerKm));
}

function staffMapIcon(type) {
    return L.divIcon({
        html: `<div class="staff-map-marker staff-map-marker--${type}">${type === 'staff' ? 'S' : 'C'}</div>`,
        iconSize: [26, 26],
        iconAnchor: [13, 13],
        className: ''
    });
}

function staffMapPopup(title, subtitle) {
    const wrapper = document.createElement('div');
    const titleEl = document.createElement('strong');
    titleEl.textContent = title;
    wrapper.appendChild(titleEl);

    if (subtitle) {
        wrapper.appendChild(document.createElement('br'));
        wrapper.appendChild(document.createTextNode(subtitle));
    }

    return wrapper;
}

function setRouteStatus(mapId, message) {
    const status = document.getElementById(mapId + '-status');
    if (status) {
        status.textContent = message;
    }
}

function initStaffBookingMap(mapEl) {
    if (!mapEl || staffBookingMaps[mapEl.id] || typeof L === 'undefined') {
        return staffBookingMaps[mapEl?.id];
    }

    const destLat = Number(mapEl.dataset.destinationLat);
    const destLng = Number(mapEl.dataset.destinationLng);

    if (!Number.isFinite(destLat) || !Number.isFinite(destLng)) {
        setRouteStatus(mapEl.id, 'Client pin is invalid');
        return null;
    }

    const map = L.map(mapEl.id, {
        center: [destLat, destLng],
        zoom: 16,
        scrollWheelZoom: true,
        zoomControl: true
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    const clientMarker = L.marker([destLat, destLng], { icon: staffMapIcon('client') })
        .addTo(map)
        .bindPopup(staffMapPopup(mapEl.dataset.client || 'Client', mapEl.dataset.address || 'Pinned service address'));

    staffBookingMaps[mapEl.id] = {
        map,
        destLat,
        destLng,
        clientMarker,
        staffMarker: null,
        routeLayer: null
    };

    requestAnimationFrame(() => map.invalidateSize());

    return staffBookingMaps[mapEl.id];
}

function getStaffCurrentPosition() {
    if (staffCurrentPosition) {
        return Promise.resolve(staffCurrentPosition);
    }

    if (staffLocationPromise) {
        return staffLocationPromise;
    }

    if (!navigator.geolocation) {
        return Promise.reject(new Error('Location is not supported on this device.'));
    }

    staffLocationPromise = new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition((position) => {
            staffCurrentPosition = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            resolve(staffCurrentPosition);
        }, reject, {
            enableHighAccuracy: true,
            timeout: 12000,
            maximumAge: 30000
        });
    }).finally(() => {
        staffLocationPromise = null;
    });

    return staffLocationPromise;
}

async function drawStaffRoute(mapId) {
    const state = staffBookingMaps[mapId] || initStaffBookingMap(document.getElementById(mapId));

    if (!state) {
        return;
    }

    setRouteStatus(mapId, 'Getting your current location...');

    try {
        const origin = await getStaffCurrentPosition();

        if (state.staffMarker) {
            state.staffMarker.setLatLng([origin.lat, origin.lng]);
        } else {
            state.staffMarker = L.marker([origin.lat, origin.lng], { icon: staffMapIcon('staff') })
                .addTo(state.map)
                .bindPopup('Your current location');
        }

        const response = await fetch(`https://router.project-osrm.org/route/v1/driving/${origin.lng},${origin.lat};${state.destLng},${state.destLat}?overview=full&geometries=geojson`, {
            mode: 'cors',
            headers: { 'Accept': 'application/json' }
        });
        const routeData = await response.json();

        if (state.routeLayer) {
            state.map.removeLayer(state.routeLayer);
        }

        if (routeData.code === 'Ok' && routeData.routes && routeData.routes.length > 0) {
            const route = routeData.routes[0];
            const coords = route.geometry.coordinates.map((coord) => [coord[1], coord[0]]);
            const distanceKm = (route.distance / 1000).toFixed(1);
            const durationMin = estimateStaffTravelMinutes(distanceKm);

            state.routeLayer = L.polyline(coords, {
                color: '#2563eb',
                weight: 5,
                opacity: 0.82
            }).addTo(state.map);

            state.map.fitBounds(L.latLngBounds(coords), { padding: [24, 24] });
            setRouteStatus(mapId, `${distanceKm} km - about ${durationMin} min`);
        } else {
            state.routeLayer = L.polyline([[origin.lat, origin.lng], [state.destLat, state.destLng]], {
                color: '#2563eb',
                weight: 3,
                dashArray: '6, 8',
                opacity: 0.75
            }).addTo(state.map);
            state.map.fitBounds([[origin.lat, origin.lng], [state.destLat, state.destLng]], { padding: [24, 24] });
            setRouteStatus(mapId, 'Route service unavailable; showing direct line');
        }

        requestAnimationFrame(() => state.map.invalidateSize());
    } catch (error) {
        state.map.setView([state.destLat, state.destLng], 16);
        setRouteStatus(mapId, 'Allow location access to draw your route');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.proof-upload-input').forEach(function (input) {
        input.addEventListener('change', function () {
            const card = input.closest('.proof-upload-card');
            const selectedLabel = card?.querySelector('.proof-upload-selected');

            if (!selectedLabel) {
                return;
            }

            card.classList.remove('proof-upload-card--invalid');
            const errorLabel = card.querySelector('.proof-upload-error');
            if (errorLabel) {
                errorLabel.textContent = '';
            }

            const files = Array.from(input.files || []);
            if (files.length === 0) {
                selectedLabel.textContent = selectedLabel.dataset.emptyLabel || 'No file selected';
                return;
            }

            if (files.length === 1) {
                selectedLabel.textContent = files[0].name;
                return;
            }

            selectedLabel.textContent = files.length + ' files selected: ' + files.slice(0, 2).map(function (file) {
                return file.name;
            }).join(', ') + (files.length > 2 ? '...' : '');
        });
    });

    document.querySelectorAll('.status-form-stack').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.uploading === 'true') {
                event.preventDefault();
                return;
            }

            const maxRequestBytes = {{ config('cleanflow.proof_uploads.max_request_kb', 32768) * 1024 }};
            let totalBytes = 0;
            let invalidCard = null;

            form.querySelectorAll('input[type="file"]').forEach(function (input) {
                const card = input.closest('.proof-upload-card');
                const errorLabel = card?.querySelector('.proof-upload-error');

                card?.classList.remove('proof-upload-card--invalid');
                if (errorLabel) {
                    errorLabel.textContent = '';
                }

                if (input.dataset.requiredMessage && (!input.files || input.files.length === 0)) {
                    event.preventDefault();
                    invalidCard = invalidCard || card;
                    card?.classList.add('proof-upload-card--invalid');
                    if (errorLabel) {
                        errorLabel.textContent = input.dataset.requiredMessage;
                    }
                }

                Array.from(input.files || []).forEach(function (file) {
                    totalBytes += file.size;

                    const maxFileMb = Number(input.dataset.maxFileMb || 0);
                    if (maxFileMb > 0 && file.size > maxFileMb * 1024 * 1024) {
                        event.preventDefault();
                        invalidCard = invalidCard || card;
                        card?.classList.add('proof-upload-card--invalid');
                        if (errorLabel) {
                            errorLabel.textContent = 'The completion video is too large. Choose a video under ' + maxFileMb + ' MB.';
                        }
                    }
                });
            });

            if (!event.defaultPrevented && totalBytes > maxRequestBytes) {
                event.preventDefault();
                alert('The proof upload is too large. Keep the total upload under {{ $proofMaxRequestMb }} MB.');
            }

            if (invalidCard) {
                invalidCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            if (!event.defaultPrevented) {
                form.dataset.uploading = 'true';
                form.classList.add('status-form-stack--uploading');
                form.setAttribute('aria-busy', 'true');
                form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                    button.disabled = true;
                    button.dataset.originalText = button.textContent.trim();
                    button.textContent = 'Uploading...';
                });
            }
        });
    });

    document.querySelectorAll('.staff-route-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const panel = document.getElementById(button.dataset.routePanel);
            if (panel) {
                panel.classList.remove('hidden');
            }

            initStaffBookingMap(document.getElementById(button.dataset.mapTarget));
            drawStaffRoute(button.dataset.mapTarget);

            requestAnimationFrame(function () {
                document.getElementById(button.dataset.routePanel)?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            });
        });
    });

    document.querySelectorAll('.staff-route-close').forEach(function (button) {
        button.addEventListener('click', function () {
            const panel = document.getElementById(button.dataset.routePanel);
            if (panel) {
                panel.classList.add('hidden');
            }
        });
    });
});

const staffLocationWatchIds = {};

async function postStaffLocation(bookingId, position) {
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

        throw new Error(payload && payload.message ? payload.message : 'We could not share your location. Please try again.');
    }
}

function setShareLocationButtonState(bookingId, isLive) {
    const button = document.getElementById('share-location-btn-' + bookingId);

    if (!button) {
        return;
    }

    button.classList.toggle('is-live', isLive);
    button.innerHTML = isLive
        ? '<i class="fas fa-satellite-dish"></i><span>Location Live</span>'
        : '<i class="fas fa-location-arrow"></i><span>Share Live Location</span>';
}

async function shareLocation(bookingId) {
    if (!navigator.geolocation) {
        alert('This device does not support location sharing.');
        return;
    }

    if (staffLocationWatchIds[bookingId]) {
        navigator.geolocation.clearWatch(staffLocationWatchIds[bookingId]);
        delete staffLocationWatchIds[bookingId];
        setShareLocationButtonState(bookingId, false);
        return;
    }

    staffLocationWatchIds[bookingId] = navigator.geolocation.watchPosition(async function (position) {
        try {
            await postStaffLocation(bookingId, position);
            setShareLocationButtonState(bookingId, true);
        } catch (error) {
            navigator.geolocation.clearWatch(staffLocationWatchIds[bookingId]);
            delete staffLocationWatchIds[bookingId];
            setShareLocationButtonState(bookingId, false);
            alert(error.message || 'We could not share your location. Please try again.');
        }
    }, function () {
        delete staffLocationWatchIds[bookingId];
        setShareLocationButtonState(bookingId, false);
        alert('We could not access your location. Please allow location sharing and try again.');
    }, {
        enableHighAccuracy: true,
        maximumAge: 15000,
        timeout: 20000
    });
}
</script>
@endpush
