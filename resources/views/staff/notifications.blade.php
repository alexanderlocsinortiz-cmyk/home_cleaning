@extends('layouts.staff')
@section('title', 'Notifications - Home Cleaning Service')
@section('page-title', 'Notifications')
@section('page-subtitle', 'Your booking assignments and updates')

@section('content')
@php
    $notificationMeta = [
        'success' => [
            'icon' => 'fa-circle-check',
            'iconClasses' => 'bg-accent-100 text-accent-700',
            'itemClasses' => 'border-accent-200 bg-accent-50/55',
        ],
        'warning' => [
            'icon' => 'fa-triangle-exclamation',
            'iconClasses' => 'bg-amber-100 text-amber-700',
            'itemClasses' => 'border-amber-200 bg-amber-50/55',
        ],
        'info' => [
            'icon' => 'fa-circle-info',
            'iconClasses' => 'bg-accent-100 text-accent-700',
            'itemClasses' => 'border-accent-200 bg-accent-50/55',
        ],
    ];
@endphp

<div class="cleanflow-page-shell min-h-[calc(100vh-81px)] px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-5xl space-y-6">
        @if (session('success'))
            <div class="cleanflow-alert cleanflow-alert--success flex items-start gap-3">
                <i class="fas fa-circle-check mt-0.5 text-base"></i>
                <div>
                    <p class="text-sm font-semibold">Notifications updated.</p>
                    <p class="mt-1 text-sm text-emerald-800/80">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8 lg:px-10">
            <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl space-y-4">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-bell text-[0.75rem]"></i>
                        Staff notifications
                    </span>
                    <div class="space-y-3">
                        <h1 class="max-w-2xl text-3xl font-black tracking-tight sm:text-4xl">
                            Stay on top of new assignments and status changes
                        </h1>
                        <p class="max-w-2xl text-sm leading-7 text-white/80 sm:text-base">
                            Review booking updates, proof reminders, and assignment changes in one place so you can act
                            quickly when something needs your attention.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm text-white/85">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-inbox text-xs"></i>
                            {{ $notifications->total() }} total notification{{ $notifications->total() === 1 ? '' : 's' }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-eye text-xs"></i>
                            {{ $unreadCount }} unread
                        </span>
                    </div>
                </div>

                @if ($unreadCount > 0)
                    <form action="{{ route('staff.notifications.read-all') }}" method="POST" class="self-start xl:self-auto">
                        @csrf
                        <button type="submit" class="cleanflow-ghost-button">
                            <i class="fas fa-check-double text-xs"></i>
                            Mark all as read
                        </button>
                    </form>
                @endif
            </div>
        </section>

        <section class="cleanflow-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">All notifications</h2>
                    <p class="mt-1 text-sm text-slate-500">Latest assignment updates, service reminders, and workflow notices.</p>
                </div>
                @if ($unreadCount > 0)
                    <span class="rounded-full border border-accent-200 bg-accent-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">
                        {{ $unreadCount }} unread
                    </span>
                @endif
            </div>

            @if ($notifications->count())
                <div class="divide-y divide-slate-100">
                    @foreach ($notifications as $notif)
                        @php
                            $type = $notif->type ?? 'info';
                            $meta = $notificationMeta[$type] ?? $notificationMeta['info'];
                            $isUnread = ! $notif->isRead();
                        @endphp
                        <article class="px-6 py-5 transition hover:bg-slate-50/70 {{ $isUnread ? $meta['itemClasses'] : '' }}">
                            <div class="flex items-start gap-4">
                                <div class="relative">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl {{ $meta['iconClasses'] }}">
                                        <i class="fas {{ $meta['icon'] }}"></i>
                                    </span>
                                    @if ($isUnread)
                                        <span class="absolute -right-1 -top-1 h-3.5 w-3.5 rounded-full border-2 border-white bg-primary-500"></span>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-bold text-slate-900">{{ $notif->title }}</h3>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $notif->message }}</p>
                                        </div>
                                        <span class="text-xs font-medium uppercase tracking-[0.14em] text-slate-400">
                                            {{ \Carbon\Carbon::parse($notif->created_at)->diffForHumans() }}
                                        </span>
                                    </div>

                                    <div class="mt-4 flex flex-wrap items-center gap-2">
                                        @if (! $notif->isRead())
                                            <form action="{{ route('staff.notifications.read', $notif->id) }}" method="POST">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                                                >
                                                    <i class="fas fa-check text-[10px]"></i>
                                                    Mark as read
                                                </button>
                                            </form>
                                        @endif

                                        @if ($notif->link)
                                            <a
                                                href="{{ $notif->link }}"
                                                class="inline-flex items-center gap-2 rounded-full border border-secondary-200 bg-secondary-50 px-3 py-1.5 text-xs font-semibold text-secondary-700 transition hover:border-secondary-300 hover:bg-secondary-100"
                                            >
                                                <i class="fas fa-arrow-right text-[10px]"></i>
                                                View details
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="border-t border-slate-100 px-6 py-4">
                    {{ $notifications->links('pagination::tailwind') }}
                </div>
            @else
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-secondary-50 text-secondary-600">
                        <i class="fas fa-bell-slash text-xl"></i>
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-slate-900">No notifications to review</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                        Booking assignments and service updates will appear here as soon as they happen.
                    </p>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
