@extends('layouts.staff')
@section('title', 'My Schedule - Home Cleaning Service')
@section('page-title', 'My Schedule')
@section('page-subtitle', 'Your upcoming cleaning assignments')

@section('content')
<style>
@media (max-width: 767px) {
    .sched-page-wrap { padding: 0.875rem !important; }
    .sched-header { flex-direction: column !important; align-items: flex-start !important; gap: 8px !important; }
    .sched-header h1 { font-size: 20px !important; }
    .sched-grid { grid-template-columns: 1fr !important; }
    .sched-calendar { overflow-x: auto !important; }
    .sched-table-wrap { overflow-x: auto !important; -webkit-overflow-scrolling: touch !important; }
    .sched-table-wrap table { min-width: 500px !important; }
}
</style>
<style>
  .sf-page { max-width: 1100px; margin: 0 auto; padding: 1rem 1.5rem 2rem; font-family: 'DM Sans', sans-serif; }
  .schedule-grid { display: grid; grid-template-columns: 1fr 340px; gap: 1.25rem; }
  .sf-card { background: white; border-radius: 14px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 1.25rem; }
  .sf-card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: space-between; }
  .sf-card-title { font-size: 15px; font-weight: 600; color: #1a1a2e; }
  .sf-card-body { padding: 1.5rem; }
  .calendar-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
  .calendar-month { font-size: 16px; font-weight: 600; color: #1a1a2e; }
  .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
  .calendar-day-label { text-align: center; font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; padding: 4px 0; }
  .calendar-day {
    aspect-ratio: 1; border-radius: 8px; display: flex; flex-direction: column;
    align-items: center; justify-content: center; font-size: 13px; font-weight: 500;
    color: #374151; cursor: default; position: relative; padding: 2px;
  }
  .calendar-day.empty { background: none; }
  .calendar-day.today { background: #185FA5; color: white; font-weight: 700; }
  .calendar-day.has-booking { background: #E6F1FB; color: #185FA5; font-weight: 600; cursor: pointer; }
  .calendar-day.has-booking:hover { background: #185FA5; color: white; }
  .calendar-day.today.has-booking { background: #185FA5; color: white; border: 2px solid #0C447C; }
  .booking-dot { width: 5px; height: 5px; border-radius: 50%; background: #185FA5; margin-top: 2px; }
  .today .booking-dot { background: white; }
  .has-booking:hover .booking-dot { background: white; }
  .booking-item { padding: 1rem; border-radius: 10px; border: 1px solid rgba(0,0,0,0.08); margin-bottom: 0.75rem; transition: all 0.15s; }
  .booking-item:hover { border-color: #185FA5; box-shadow: 0 2px 8px rgba(24,95,165,0.1); }
  .booking-item:last-child { margin-bottom: 0; }
  .booking-date-badge { display: inline-block; background: #E6F1FB; color: #185FA5; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-bottom: 6px; }
  .booking-date-badge.today { background: #185FA5; color: white; }
  .booking-service { font-size: 14px; font-weight: 600; color: #1a1a2e; margin-bottom: 2px; }
  .booking-client { font-size: 13px; color: #6b7280; margin-bottom: 4px; }
  .booking-address { font-size: 12px; color: #9ca3af; }
  .booking-time { font-size: 13px; font-weight: 600; color: #185FA5; }
  .cf-badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 500; }
  .badge-confirmed { background: #E6F1FB; color: #185FA5; }
  .badge-in_progress { background: #f3e8ff; color: #9333ea; }
  .empty-state { text-align: center; padding: 2.5rem; color: #9ca3af; }
</style>

@php
  $today = \Carbon\Carbon::today();
  $startOfMonth = $today->copy()->startOfMonth();
  $endOfMonth = $today->copy()->endOfMonth();
  $daysInMonth = $endOfMonth->day;
  $firstDayOfWeek = $startOfMonth->dayOfWeek;
@endphp

<div class="sf-page sched-page-wrap">
  <div class="sched-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
    <div>
      <h1 style="font-size:24px;font-weight:700;color:#1a1a2e;margin-bottom:4px;">Schedule Overview</h1>
      <p style="font-size:13px;color:#6b7280;">Review your calendar and upcoming assignments.</p>
    </div>
  </div>

  <div class="schedule-grid sched-grid">

    <div class="sf-card">
      <div class="sf-card-header">
        <p class="sf-card-title">&#128197; {{ $today->format('F Y') }}</p>
        <span style="font-size:12px;color:#6b7280;">{{ $bookings->count() }} upcoming job(s)</span>
      </div>
      <div class="sf-card-body sched-calendar">
        <div class="calendar-grid" style="margin-bottom:8px;">
          @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day)
          <div class="calendar-day-label">{{ $day }}</div>
          @endforeach
        </div>
        <div class="calendar-grid">
          @for($i = 0; $i < $firstDayOfWeek; $i++)
          <div class="calendar-day empty"></div>
          @endfor

          @for($day = 1; $day <= $daysInMonth; $day++)
          @php
            $dateStr = $today->copy()->startOfMonth()->addDays($day - 1)->format('Y-m-d');
            $isToday = $dateStr === $today->format('Y-m-d');
            $hasBooking = isset($bookingsByDate[$dateStr]);
            $dayBookings = $bookingsByDate[$dateStr] ?? collect();
          @endphp
          <div class="calendar-day {{ $isToday ? 'today' : '' }} {{ $hasBooking ? 'has-booking' : '' }}"
               title="{{ $hasBooking ? $dayBookings->count() . ' booking(s)' : '' }}">
            {{ $day }}
            @if($hasBooking)
            <div class="booking-dot"></div>
            @endif
          </div>
          @endfor
        </div>

        <div style="display:flex;gap:1rem;margin-top:1.25rem;padding-top:1rem;border-top:1px solid rgba(0,0,0,0.06);font-size:12px;color:#6b7280;">
          <div style="display:flex;align-items:center;gap:5px;">
            <div style="width:12px;height:12px;border-radius:3px;background:#185FA5;"></div> Today
          </div>
          <div style="display:flex;align-items:center;gap:5px;">
            <div style="width:12px;height:12px;border-radius:3px;background:#E6F1FB;"></div> Has Booking
          </div>
        </div>
      </div>
    </div>

    <div class="sf-card">
      <div class="sf-card-header">
        <p class="sf-card-title">&#128203; Upcoming Jobs</p>
      </div>
      <div class="sf-card-body sched-table-wrap" style="max-height:500px;overflow-y:auto;">
        @if($bookings->count())
          @foreach($bookings as $booking)
          @php $isToday = \Carbon\Carbon::parse($booking->scheduled_date)->isToday(); @endphp
          <div class="booking-item">
            <div class="booking-date-badge {{ $isToday ? 'today' : '' }}">
              {{ $isToday ? 'Today' : \Carbon\Carbon::parse($booking->scheduled_date)->format('M d, Y') }}
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;">
              <div class="booking-service">{{ $booking->service_label }}</div>
              <div class="booking-time">{{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}</div>
            </div>
            <div class="booking-client">
              {{ $booking->user->first_name }} {{ $booking->user->last_name }}
              @if($booking->user->phone)
              &middot; {{ substr($booking->user->phone, 0, 4) . '****' . substr($booking->user->phone, -3) }}
              @endif
            </div>
            <div class="booking-address">
              {{ $booking->street_address }}, {{ ucfirst($booking->barangay) }}
            </div>
            <div style="margin-top:6px;">
              <span class="cf-badge badge-{{ $booking->status }}">
                {{ ucfirst(str_replace('_',' ',$booking->status)) }}
              </span>
              <span style="font-size:11px;color:#9ca3af;margin-left:6px;">CF-{{ str_pad($booking->id,5,'0',STR_PAD_LEFT) }}</span>
            </div>
          </div>
          @endforeach
        @else
          <div class="empty-state">
            <div style="font-size:40px;margin-bottom:8px;">&#128197;</div>
            <p style="font-weight:500;">No upcoming jobs scheduled</p>
            <p style="font-size:13px;">New assignments will appear here as soon as they are confirmed.</p>
          </div>
        @endif
      </div>
    </div>

  </div>
</div>
@endsection

