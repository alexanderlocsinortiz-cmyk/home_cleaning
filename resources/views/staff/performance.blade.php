@extends('layouts.staff')
@section('title', 'My Performance - Home Cleaning Service')
@section('page-title', 'My Performance')
@section('page-subtitle', 'Your ratings, ranking, and reviews')

@section('content')
<style>
@media (max-width: 767px) {
    .perf-page-wrap { padding: 0.875rem !important; }
    .perf-stat-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 0.875rem !important; }
    .perf-main-grid { grid-template-columns: 1fr !important; gap: 1rem !important; }
    .perf-reviews-table { overflow-x: auto !important; -webkit-overflow-scrolling: touch !important; }
    .perf-reviews-table table { min-width: 500px !important; }
    .perf-header { flex-direction: column !important; align-items: flex-start !important; gap: 8px !important; }
    .perf-header h1 { font-size: 20px !important; }
    .rating-big-number { font-size: 56px !important; }
}
</style>
<style>
  .sf-page { max-width: 1100px; margin: 0 auto; padding: 1rem 1.5rem 2rem; font-family: 'DM Sans', sans-serif; }
  .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
  .stat-card { background: white; border-radius: 14px; padding: 1.25rem 1.5rem; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 1px 3px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 1rem; }
  .stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
  .stat-label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500; margin-bottom: 4px; }
  .stat-value { font-size: 26px; font-weight: 600; color: #1a1a2e; line-height: 1; }
  .main-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem; }
  .sf-card { background: white; border-radius: 14px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 1.25rem; }
  .sf-card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(0,0,0,0.08); }
  .sf-card-title { font-size: 15px; font-weight: 600; color: #1a1a2e; }
  .sf-card-body { padding: 1.5rem; }
  .big-rating { text-align: center; padding: 1.5rem; }
  .big-rating .number { font-size: 64px; font-weight: 700; color: #1a1a2e; line-height: 1; }
  .big-rating .stars { font-size: 24px; color: #f59e0b; margin: 8px 0; }
  .big-rating .sub { font-size: 13px; color: #6b7280; }
  .star-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
  .star-label { font-size: 13px; color: #6b7280; width: 40px; flex-shrink: 0; }
  .star-bar-bg { flex: 1; background: #f3f4f6; border-radius: 10px; height: 8px; overflow: hidden; }
  .star-bar-fill { height: 8px; border-radius: 10px; background: #f59e0b; transition: width 0.3s; }
  .star-count { font-size: 13px; color: #6b7280; width: 24px; text-align: right; flex-shrink: 0; }
  .rank-badge { text-align: center; padding: 2rem; }
  .rank-number { font-size: 72px; font-weight: 700; color: #185FA5; line-height: 1; }
  .rank-sub { font-size: 14px; color: #6b7280; margin-top: 4px; }
  .rank-medal { font-size: 48px; margin-bottom: 8px; }
  .review-item { padding: 1rem 0; border-bottom: 1px solid rgba(0,0,0,0.06); }
  .review-item:last-child { border-bottom: none; }
  .review-stars { color: #f59e0b; font-size: 14px; margin-bottom: 4px; }
  .review-comment { font-size: 14px; color: #374151; margin-bottom: 4px; font-style: italic; }
  .review-meta { font-size: 12px; color: #9ca3af; }
</style>

<div class="sf-page perf-page-wrap">
  <div class="perf-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
    <div>
      <h1 style="font-size:24px;font-weight:700;color:#1a1a2e;margin-bottom:4px;">Performance Overview</h1>
      <p style="font-size:13px;color:#6b7280;">Track your ratings, ranking, and customer reviews.</p>
    </div>
  </div>

  <div class="stats-row perf-stat-grid">
    <div class="stat-card">
      <div class="stat-icon" style="background:#fef3c7;">&#9733;</div>
      <div>
        <p class="stat-label">Avg Rating</p>
        <p class="stat-value">{{ $avgRating ?? '-' }}</p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#E6F1FB;">&#127942;</div>
      <div>
        <p class="stat-label">My Rank</p>
        <p class="stat-value">#{{ $myRank }}</p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#EAF3DE;">&#10004;</div>
      <div>
        <p class="stat-label">Completion</p>
        <p class="stat-value" style="color:{{ $completionRate >= 70 ? '#16a34a' : ($completionRate >= 40 ? '#d97706' : '#dc2626') }};">{{ $completionRate }}%</p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#E6F1FB;">&#128221;</div>
      <div>
        <p class="stat-label">Total Reviews</p>
        <p class="stat-value">{{ $totalRatings }}</p>
      </div>
    </div>
  </div>

  <div class="main-grid perf-main-grid">
    <div class="sf-card">
      <div class="sf-card-header">
        <p class="sf-card-title">&#9733; Rating Summary</p>
      </div>
      <div class="sf-card-body">
        @if($avgRating)
        <div class="big-rating">
          <div class="number rating-big-number">{{ $avgRating }}</div>
          <div class="stars">
            @for($i = 1; $i <= 5; $i++)
              {!! $i <= round($avgRating) ? '&#9733;' : '&#9734;' !!}
            @endfor
          </div>
          <div class="sub">Based on {{ $totalRatings }} review{{ $totalRatings != 1 ? 's' : '' }}</div>
        </div>
        <div style="margin-top:1rem;">
          @foreach($starBreakdown as $star => $count)
          <div class="star-row">
            <span class="star-label">{{ $star }}&#9733;</span>
            <div class="star-bar-bg">
              <div class="star-bar-fill" style="width:{{ $totalRatings > 0 ? ($count / $totalRatings) * 100 : 0 }}%"></div>
            </div>
            <span class="star-count">{{ $count }}</span>
          </div>
          @endforeach
        </div>
        @else
        <div style="text-align:center;padding:2rem;color:#9ca3af;">
          <div style="font-size:40px;margin-bottom:8px;">&#9733;</div>
          <p>No ratings to show yet</p>
          <p style="font-size:13px;">Ratings will appear here after completed bookings receive customer feedback.</p>
        </div>
        @endif
      </div>
    </div>

    <div class="sf-card">
      <div class="sf-card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <p class="sf-card-title">&#127942; My Ranking</p>
        <span style="font-size:11px;color:#9ca3af;background:#f8fafc;padding:3px 8px;border-radius:6px;">Based on average star rating</span>
      </div>
      <div class="sf-card-body">
        <div class="rank-badge">
          <div class="rank-medal">
            @if($myRank === 1)
            &#129351;
            @elseif($myRank === 2)
            &#129352;
            @elseif($myRank === 3)
            &#129353;
            @else
            &#127942;
            @endif
          </div>
          <div class="rank-number">#{{ $myRank }}</div>
          <div class="rank-sub">out of {{ $totalStaff }} staff members</div>
        </div>
        <div style="border-top:1px solid rgba(0,0,0,0.06);padding-top:1rem;display:grid;grid-template-columns:1fr 1fr;gap:1rem;text-align:center;">
          <div>
            <div style="font-size:22px;font-weight:700;color:#1a1a2e;">{{ $completedCount }}</div>
            <div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Jobs Done</div>
          </div>
          <div>
            <div style="font-size:22px;font-weight:700;color:{{ $completionRate >= 70 ? '#16a34a' : ($completionRate >= 40 ? '#d97706' : '#dc2626') }};">{{ $completionRate }}%</div>
            <div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Completion Rate</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="sf-card">
    <div class="sf-card-header" style="display:flex;align-items:center;justify-content:space-between;">
      <p class="sf-card-title">&#128172; Customer Reviews</p>
      <span style="font-size:12px;color:#9ca3af;">{{ $totalRatings }} review(s)</span>
    </div>
    <div class="sf-card-body perf-reviews-table">
        @php $allRatings = $completedBookings->filter(fn($b) => $b->rating); @endphp
        @if($allRatings->count())
            @foreach($allRatings as $booking)
            <div class="review-item">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <div class="review-stars">
                        @for($i = 1; $i <= 5; $i++)
                            {!! $i <= $booking->rating->stars ? '&#9733;' : '&#9734;' !!}
                        @endfor
                    </div>
                    <span style="font-size:11px;color:#9ca3af;">{{ \Carbon\Carbon::parse($booking->updated_at)->format('M d, Y') }}</span>
                </div>
                @if($booking->rating->comment)
                <div class="review-comment">"{{ $booking->rating->comment }}"</div>
                @else
                <div style="font-size:13px;color:#9ca3af;font-style:italic;">No written feedback was provided.</div>
                @endif

                @if($booking->rating->photo)
                <div style="margin-top: 8px; margin-bottom: 8px;">
                    <img src="{{ asset('storage/' . $booking->rating->photo) }}" alt="Client photo" style="max-height: 120px; max-width: 200px; border-radius: 8px; border: 1px solid #e2e8f0; object-fit: cover;">
                </div>
                @endif

                <div class="review-meta">
                    {{ $booking->user->first_name }} {{ $booking->user->last_name }}
                    &nbsp;&middot;&nbsp; {{ $booking->service_label }}
                </div>
            </div>
            @endforeach
        @else
            <div style="text-align:center;padding:2rem;color:#9ca3af;">
                <div style="font-size:40px;margin-bottom:8px;">&#128172;</div>
                <p>No reviews to show yet</p>
                <p style="font-size:13px;">Customer comments will appear here after completed bookings are reviewed.</p>
            </div>
        @endif
    </div>
  </div>

</div>
@endsection

