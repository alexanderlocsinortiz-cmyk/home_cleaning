@extends('layouts.staff')
@section('title', 'Notifications - Home Cleaning Service')
@section('page-title', 'Notifications')
@section('page-subtitle', 'Your booking assignments and updates')

@section('content')
<style>
  .sf-page { max-width: 900px; margin: 0 auto; padding: 1rem 1.5rem 2rem; font-family: 'DM Sans', sans-serif; }
  .sf-card { background: white; border-radius: 14px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
  .sf-card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: space-between; }
  .sf-card-title { font-size: 15px; font-weight: 600; color: #1a1a2e; }
  .notif-item { display: flex; gap: 1rem; padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(0,0,0,0.06); transition: background 0.15s; }
  .notif-item:last-child { border-bottom: none; }
  .notif-item.unread { background: #f0f7ff; }
  .notif-item:hover { background: #f8fafc; }
  .notif-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
  .notif-icon.info { background: #E6F1FB; }
  .notif-icon.success { background: #EAF3DE; }
  .notif-icon.warning { background: #FAEEDA; }
  .notif-content { flex: 1; }
  .notif-title { font-size: 14px; font-weight: 600; color: #1a1a2e; margin-bottom: 3px; }
  .notif-message { font-size: 13px; color: #6b7280; line-height: 1.5; margin-bottom: 4px; }
  .notif-time { font-size: 11px; color: #9ca3af; }
  .unread-dot { width: 8px; height: 8px; border-radius: 50%; background: #185FA5; flex-shrink: 0; margin-top: 6px; }
  .btn-mark-read { background: none; border: 1px solid #e5e7eb; border-radius: 6px; padding: 3px 10px; font-size: 11px; color: #6b7280; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: all 0.15s; }
  .btn-mark-read:hover { border-color: #185FA5; color: #185FA5; }
  .mark-all-btn { background: #E6F1FB; color: #185FA5; border: none; border-radius: 8px; padding: 7px 14px; font-size: 13px; font-weight: 500; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: all 0.15s; }
  .mark-all-btn:hover { background: #185FA5; color: white; }
  .unread-badge { background: #185FA5; color: white; border-radius: 20px; padding: 2px 8px; font-size: 11px; font-weight: 600; margin-left: 6px; }
</style>

<div class="sf-page">

  @if(session('success'))
  <div style="background:#dcfce7;border:1px solid #86efac;color:#16a34a;border-radius:10px;padding:12px 16px;margin-bottom:1rem;font-size:14px;">
    ✅ {{ session('success') }}
  </div>
  @endif

  <div class="sf-card">
    <div class="sf-card-header">
      <p class="sf-card-title">
        All Notifications
        @if($unreadCount > 0)
        <span class="unread-badge">{{ $unreadCount }} unread</span>
        @endif
      </p>
      @if($unreadCount > 0)
      <form action="{{ route('staff.notifications.read-all') }}" method="POST">
        @csrf
        <button type="submit" class="mark-all-btn">✅ Mark All as Read</button>
      </form>
      @endif
    </div>

    @if($notifications->count())
      @foreach($notifications as $notif)
      <div class="notif-item {{ $notif->isRead() ? '' : 'unread' }}">
        <div class="notif-icon {{ $notif->type }}">
          @if($notif->type === 'success') ✅
          @elseif($notif->type === 'warning') ⚠️
          @else 📋
          @endif
        </div>
        <div class="notif-content">
          <div class="notif-title">{{ $notif->title }}</div>
          <div class="notif-message">{{ $notif->message }}</div>
          <div style="display:flex;align-items:center;gap:10px;margin-top:4px;">
            <span class="notif-time">{{ \Carbon\Carbon::parse($notif->created_at)->diffForHumans() }}</span>
            @if(!$notif->isRead())
            <form action="{{ route('staff.notifications.read', $notif->id) }}" method="POST" style="display:inline;">
              @csrf
              <button type="submit" class="btn-mark-read">Mark as read</button>
            </form>
            @endif
            @if($notif->link)
            <a href="{{ $notif->link }}" style="font-size:11px;color:#185FA5;text-decoration:none;">View →</a>
            @endif
          </div>
        </div>
        @if(!$notif->isRead())
        <div class="unread-dot"></div>
        @endif
      </div>
      @endforeach
      <div style="padding:1rem 1.5rem;border-top:1px solid rgba(0,0,0,0.08);">
        {{ $notifications->links('pagination::tailwind') }}
      </div>
    @else
      <div style="text-align:center;padding:3rem;color:#9ca3af;">
        <div style="font-size:48px;margin-bottom:12px;">🔔</div>
        <p style="font-size:15px;font-weight:500;margin-bottom:6px;">No notifications to review</p>
        <p style="font-size:13px;">Booking assignments and service updates will appear here as they happen.</p>
      </div>
    @endif
  </div>

</div>
@endsection

