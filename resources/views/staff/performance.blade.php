@extends('layouts.staff')
@section('title', 'My Performance - Home Cleaning Service')
@section('page-title', 'My Performance')
@section('page-subtitle', 'Your ratings, ranking, and reviews')

@section('content')
@php
    $stats = [
        [
            'label' => 'Average Rating',
            'value' => $avgRating ?? '-',
            'suffix' => $avgRating ? ' / 5' : '',
            'icon' => 'fa-star',
            'cardClasses' => 'border-l-4 border-amber-300 bg-amber-50/80',
            'iconClasses' => 'bg-amber-100 text-amber-700',
        ],
        [
            'label' => 'My Rank',
            'value' => '#' . $myRank,
            'suffix' => ' of ' . $totalStaff,
            'icon' => 'fa-trophy',
            'cardClasses' => 'border-l-4 border-secondary-300 bg-secondary-50/80',
            'iconClasses' => 'bg-secondary-100 text-secondary-700',
        ],
        [
            'label' => 'Completion Rate',
            'value' => $completionRate . '%',
            'suffix' => '',
            'icon' => 'fa-chart-line',
            'cardClasses' => 'border-l-4 ' . ($completionRate >= 70 ? 'border-accent-300 bg-accent-50/80' : ($completionRate >= 40 ? 'border-amber-300 bg-amber-50/80' : 'border-danger-300 bg-danger-50/80')),
            'iconClasses' => $completionRate >= 70
                ? 'bg-accent-100 text-accent-700'
                : ($completionRate >= 40 ? 'bg-amber-100 text-amber-700' : 'bg-danger-100 text-danger-700'),
        ],
        [
            'label' => 'Total Reviews',
            'value' => $totalRatings,
            'suffix' => '',
            'icon' => 'fa-comments',
            'cardClasses' => 'border-l-4 border-primary-300 bg-primary-50/80',
            'iconClasses' => 'bg-primary-100 text-primary-700',
        ],
    ];

    $ratingBarColor = $avgRating >= 4.5 ? 'bg-accent-500' : ($avgRating >= 3 ? 'bg-amber-400' : 'bg-danger-400');
    $rankTone = $myRank === 1 ? 'text-amber-600' : ($myRank <= 3 ? 'text-accent-600' : 'text-slate-700');
    $rankBadge = $myRank === 1 ? 'First place' : ($myRank === 2 ? 'Second place' : ($myRank === 3 ? 'Third place' : 'Team ranking'));
@endphp

<div class="cleanflow-page-shell min-h-[calc(100vh-81px)] px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8 lg:px-10">
            <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl space-y-4">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-medal text-[0.75rem]"></i>
                        Staff performance
                    </span>
                    <div class="space-y-3">
                        <h1 class="max-w-2xl text-3xl font-black tracking-tight sm:text-4xl">
                            See how your service quality is trending
                        </h1>
                        <p class="max-w-2xl text-sm leading-7 text-white/80 sm:text-base">
                            Track customer feedback, compare your ranking, and review recent comments from completed
                            bookings so you always know where you’re doing well.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm text-white/85">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-star text-xs"></i>
                            {{ $totalRatings }} review{{ $totalRatings === 1 ? '' : 's' }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-check-double text-xs"></i>
                            {{ $completedCount }} completed job{{ $completedCount === 1 ? '' : 's' }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-wallet text-xs"></i>
                            P{{ number_format($totalEarnings, 0) }} earned
                        </span>
                    </div>
                </div>

                <div class="rounded-2xl border border-white/15 bg-white/10 px-5 py-4 text-center backdrop-blur-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Current rank</div>
                    <div class="mt-2 text-3xl font-black text-white">#{{ $myRank }}</div>
                    <div class="mt-1 text-sm text-white/75">out of {{ $totalStaff }} staff members</div>
                </div>
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <section class="cleanflow-panel px-5 py-5 {{ $stat['cardClasses'] }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $stat['label'] }}</p>
                            <strong class="mt-3 block text-3xl font-black tracking-tight text-slate-900">{{ $stat['value'] }}</strong>
                            @if ($stat['suffix'])
                                <p class="mt-1 text-sm text-slate-500">{{ $stat['suffix'] }}</p>
                            @endif
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl {{ $stat['iconClasses'] }}">
                            <i class="fas {{ $stat['icon'] }}"></i>
                        </span>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
            <section class="space-y-6">
                <div class="grid gap-6 lg:grid-cols-2">
                    <section class="cleanflow-panel p-6">
                        <div class="mb-5 flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Rating summary</h2>
                                <p class="mt-1 text-sm text-slate-500">A quick snapshot of your average rating and distribution.</p>
                            </div>
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                {{ $totalRatings }} review{{ $totalRatings === 1 ? '' : 's' }}
                            </span>
                        </div>

                        @if ($avgRating)
                            <div class="rounded-3xl border border-slate-100 bg-slate-50/80 p-6 text-center">
                                <div class="text-6xl font-black tracking-tight text-slate-900">{{ $avgRating }}</div>
                                <div class="mt-3 text-xl tracking-[0.2em] text-amber-500">
                                    @for ($i = 1; $i <= 5; $i++)
                                        {!! $i <= round($avgRating) ? '&#9733;' : '&#9734;' !!}
                                    @endfor
                                </div>
                                <p class="mt-3 text-sm text-slate-500">
                                    Based on {{ $totalRatings }} submitted review{{ $totalRatings === 1 ? '' : 's' }}.
                                </p>
                            </div>

                            <div class="mt-5 space-y-3">
                                @foreach ($starBreakdown as $star => $count)
                                    @php
                                        $percent = $totalRatings > 0 ? round(($count / $totalRatings) * 100, 1) : 0;
                                    @endphp
                                    <div class="flex items-center gap-3">
                                        <span class="w-12 text-sm font-semibold text-slate-600">{{ $star }} star</span>
                                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full {{ $ratingBarColor }}" data-fill-width="{{ $percent }}"></div>
                                        </div>
                                        <span class="w-16 text-right text-sm text-slate-500">{{ $count }} / {{ $percent }}%</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="py-10 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                                    <i class="fas fa-star text-xl"></i>
                                </div>
                                <h3 class="mt-4 text-lg font-bold text-slate-900">No ratings yet</h3>
                                <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">
                                    Ratings will appear here once completed bookings receive customer feedback.
                                </p>
                            </div>
                        @endif
                    </section>

                    <section class="cleanflow-panel p-6">
                        <div class="mb-5 flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Ranking</h2>
                                <p class="mt-1 text-sm text-slate-500">Your current standing compared with the rest of the staff team.</p>
                            </div>
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                {{ $rankBadge }}
                            </span>
                        </div>

                        <div class="rounded-3xl border border-slate-100 bg-slate-50/80 p-6 text-center">
                            <div class="text-5xl">
                                @if ($myRank === 1)
                                    &#129351;
                                @elseif ($myRank === 2)
                                    &#129352;
                                @elseif ($myRank === 3)
                                    &#129353;
                                @else
                                    &#127942;
                                @endif
                            </div>
                            <div class="mt-4 text-6xl font-black tracking-tight {{ $rankTone }}">#{{ $myRank }}</div>
                            <p class="mt-2 text-sm text-slate-500">out of {{ $totalStaff }} staff members</p>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-[1.25rem] border border-slate-100 bg-white px-4 py-4 text-center shadow-sm">
                                <div class="text-2xl font-black text-slate-900">{{ $completedCount }}</div>
                                <div class="mt-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Jobs done</div>
                            </div>
                            <div class="rounded-[1.25rem] border border-slate-100 bg-white px-4 py-4 text-center shadow-sm">
                                <div class="text-2xl font-black {{ $completionRate >= 70 ? 'text-accent-700' : ($completionRate >= 40 ? 'text-amber-600' : 'text-danger-600') }}">
                                    {{ $completionRate }}%
                                </div>
                                <div class="mt-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Completion rate</div>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="cleanflow-panel overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Customer reviews</h2>
                            <p class="mt-1 text-sm text-slate-500">Recent comments and review photos from completed bookings.</p>
                        </div>
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                            {{ $totalRatings }} total
                        </span>
                    </div>

                    <div class="px-6 py-6">
                        @php $allRatings = $completedBookings->filter(fn ($booking) => $booking->rating); @endphp

                        @if ($allRatings->count())
                            <div class="space-y-5">
                                @foreach ($allRatings as $booking)
                                    <article class="rounded-[1.4rem] border border-slate-100 bg-slate-50/75 p-5 transition hover:border-slate-200 hover:bg-white hover:shadow-sm">
                                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <div class="text-base tracking-[0.16em] text-amber-500">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        {!! $i <= $booking->rating->stars ? '&#9733;' : '&#9734;' !!}
                                                    @endfor
                                                </div>
                                                <p class="mt-3 text-sm font-semibold text-slate-900">
                                                    {{ $booking->user->display_name }}
                                                </p>
                                                <p class="mt-1 text-sm text-slate-500">{{ $booking->service_label }}</p>
                                            </div>
                                            <span class="text-xs font-medium uppercase tracking-[0.14em] text-slate-400">
                                                {{ \Carbon\Carbon::parse($booking->updated_at)->format('M d, Y') }}
                                            </span>
                                        </div>

                                        @if ($booking->rating->comment)
                                            <blockquote class="mt-4 rounded-[1.1rem] border border-slate-100 bg-white px-4 py-4 text-sm italic leading-7 text-slate-600 shadow-sm">
                                                "{{ $booking->rating->comment }}"
                                            </blockquote>
                                        @else
                                            <p class="mt-4 text-sm italic text-slate-400">No written feedback was provided.</p>
                                        @endif

                                        @if ($booking->rating->photo)
                                            <div class="mt-4">
                                                <img
                                                    src="{{ asset('storage/' . $booking->rating->photo) }}"
                                                    alt="Client review photo"
                                                    class="max-h-32 rounded-2xl border border-slate-200 object-cover shadow-sm"
                                                >
                                            </div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <div class="py-10 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-accent-50 text-accent-600">
                                    <i class="fas fa-comments text-xl"></i>
                                </div>
                                <h3 class="mt-4 text-lg font-bold text-slate-900">No reviews yet</h3>
                                <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">
                                    Customer comments will appear here after completed bookings are reviewed.
                                </p>
                            </div>
                        @endif
                    </div>
                </section>
            </section>

            <aside class="space-y-6">
                <section class="cleanflow-panel p-6">
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-slate-900">Performance snapshot</h2>
                        <p class="mt-1 text-sm text-slate-500">A quick read of the numbers that matter most right now.</p>
                    </div>

                    <div class="space-y-3">
                        <div class="client-profile-tip">
                            <span class="client-profile-tip-icon">
                                <i class="fas fa-wallet text-xs"></i>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Completed-job earnings</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">P{{ number_format($totalEarnings, 0) }} from completed bookings.</p>
                            </div>
                        </div>

                        <div class="client-profile-tip">
                            <span class="client-profile-tip-icon">
                                <i class="fas fa-broom text-xs"></i>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Completed services</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $completedCount }} job{{ $completedCount === 1 ? '' : 's' }} fully completed so far.</p>
                            </div>
                        </div>

                        <div class="client-profile-tip">
                            <span class="client-profile-tip-icon">
                                <i class="fas fa-user-group text-xs"></i>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Ranking context</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">You currently rank #{{ $myRank }} out of {{ $totalStaff }} staff members.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="cleanflow-panel border border-accent-100 bg-accent-50/80 p-6">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-accent-600 shadow-sm">
                            <i class="fas fa-lightbulb text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Keep momentum</h2>
                            <p class="text-sm text-slate-500">Small service habits can steadily improve reviews and completion rate.</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="client-profile-tip">
                            <span class="client-profile-tip-icon">
                                <i class="fas fa-check text-xs"></i>
                            </span>
                            <p class="text-sm leading-6 text-slate-600">
                                Upload before and after proof consistently so clients feel confident leaving feedback.
                            </p>
                        </div>
                        <div class="client-profile-tip">
                            <span class="client-profile-tip-icon">
                                <i class="fas fa-clock text-xs"></i>
                            </span>
                            <p class="text-sm leading-6 text-slate-600">
                                Stay on top of confirmed jobs early to keep your completion rate and service timing strong.
                            </p>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection
