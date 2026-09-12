@extends('layouts.staff')
@section('title', 'My Performance - Home Cleaning Service')
@section('page-title', 'My Performance')
@section('page-subtitle', 'Your ratings, ranking, and reviews')

@push('styles')
<style>
    .staff-performance-page {
        background: linear-gradient(90deg, rgba(219, 234, 254, 0.72), rgba(248, 250, 252, 0.96) 24%, rgba(239, 246, 255, 0.9));
    }

    .staff-performance-page [class*="tracking-"] {
        letter-spacing: 0;
    }

    .performance-hero {
        border: 1px solid #1e3a8a;
        border-radius: 1.25rem;
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 64%, #2563eb 100%);
        color: #fff;
        box-shadow: 0 18px 36px rgba(30, 58, 138, 0.16);
    }

    .performance-hero-rank {
        min-width: 11rem;
        border: 1px solid rgba(191, 219, 254, 0.72);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.12);
        padding: 1rem;
        text-align: center;
    }

    .performance-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    .performance-stat-card,
    .performance-panel {
        border: 1px solid #bfdbfe;
        border-radius: 1.15rem;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 16px 34px rgba(30, 64, 175, 0.07);
    }

    .performance-stat-card {
        min-height: 7.25rem;
        border: 1px solid #e2e8f0;
        border-left: 4px solid var(--stat-accent, #2563eb);
        background: #fff;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
    }

    .performance-stat-card--rating {
        --stat-accent: #d97706;
        --stat-soft: rgba(254, 243, 199, 0.86);
    }

    .performance-stat-card--rank {
        --stat-accent: #2563eb;
        --stat-soft: rgba(219, 234, 254, 0.82);
    }

    .performance-stat-card--completion {
        --stat-accent: #059669;
        --stat-soft: rgba(209, 250, 229, 0.82);
    }

    .performance-stat-card--reviews {
        --stat-accent: #0f766e;
        --stat-soft: rgba(204, 251, 241, 0.8);
    }

    .performance-stat-icon {
        background: var(--stat-accent, #2563eb);
        color: #fff;
    }

    .performance-main-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 1rem;
        align-items: start;
    }

    .performance-score-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .performance-empty-state {
        min-height: 14rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .performance-tip {
        display: flex;
        gap: 0.9rem;
        align-items: flex-start;
        border: 1px solid #dbeafe;
        border-radius: 1rem;
        background: #f8fbff;
        padding: 0.9rem;
    }

    .performance-tip-icon {
        display: inline-flex;
        width: 2rem;
        height: 2rem;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #ccfbf1;
        color: #0f766e;
    }

    @media (max-width: 1180px) {
        .performance-stat-grid,
        .performance-score-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .performance-main-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 760px) {
        .staff-performance-page {
            padding: 1rem;
        }

        .performance-stat-grid,
        .performance-score-grid {
            grid-template-columns: 1fr;
        }

        .performance-hero-rank {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
@php
    $performanceTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
    $stats = [
        [
            'label' => 'Average Rating',
            'value' => $avgRating ?? '-',
            'suffix' => $avgRating ? ' / 5' : '',
            'icon' => 'fa-star',
            'variant' => 'rating',
        ],
        [
            'label' => 'My Rank',
            'value' => '#' . $myRank,
            'suffix' => ' of ' . $totalStaff,
            'icon' => 'fa-trophy',
            'variant' => 'rank',
        ],
        [
            'label' => 'Completion Rate',
            'value' => $completionRate . '%',
            'suffix' => '',
            'icon' => 'fa-chart-line',
            'variant' => 'completion',
        ],
        [
            'label' => 'Total Reviews',
            'value' => $totalRatings,
            'suffix' => '',
            'icon' => 'fa-comments',
            'variant' => 'reviews',
        ],
    ];

    $ratingBarColor = $avgRating >= 4.5 ? 'bg-green-500' : ($avgRating >= 3 ? 'bg-amber-400' : 'bg-red-400');
    $rankTone = $myRank === 1 ? 'text-amber-600' : ($myRank <= 3 ? 'text-blue-700' : 'text-slate-700');
    $rankBadge = $myRank === 1 ? 'First place' : ($myRank === 2 ? 'Second place' : ($myRank === 3 ? 'Third place' : 'Team ranking'));
    $allRatings = $completedBookings->filter(fn ($booking) => $booking->rating);
@endphp

<div class="staff-performance-page cleanflow-page-shell min-h-[calc(100vh-81px)] px-4 py-5 sm:px-6 sm:py-7">
    <div class="mx-auto max-w-[92rem] space-y-5">
        <section class="performance-hero overflow-hidden px-6 py-6 sm:px-7">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div class="max-w-3xl">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-medal text-[0.75rem]"></i>
                        Staff performance
                    </span>
                    <h1 class="mt-4 max-w-2xl text-2xl font-black tracking-tight sm:text-3xl">
                        Service quality scorecard
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-blue-100">
                        Track customer feedback, rank, completed jobs, and recent reviews from one compact performance view.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2 text-sm text-blue-100">
                        <span class="inline-flex items-center gap-2 rounded-full border border-blue-300 bg-blue-800 px-3 py-2">
                            <i class="fas fa-star text-xs"></i>
                            {{ $totalRatings }} review{{ $totalRatings === 1 ? '' : 's' }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-blue-300 bg-blue-800 px-3 py-2">
                            <i class="fas fa-check-double text-xs"></i>
                            {{ $completedCount }} completed job{{ $completedCount === 1 ? '' : 's' }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-blue-300 bg-blue-800 px-3 py-2">
                            <i class="fas fa-wallet text-xs"></i>
                            P{{ number_format($totalEarnings, 2) }} service value
                        </span>
                    </div>
                </div>

                <div class="performance-hero-rank">
                    <div class="text-xs font-semibold uppercase text-blue-100">Current rank</div>
                    <div class="mt-2 text-3xl font-black text-white">#{{ $myRank }}</div>
                    <div class="mt-1 text-sm text-blue-100">out of {{ $totalStaff }} staff members</div>
                </div>
            </div>
        </section>

        <div class="performance-stat-grid">
            @foreach ($stats as $stat)
                <section class="performance-stat-card performance-stat-card--{{ $stat['variant'] }} px-5 py-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-400">{{ $stat['label'] }}</p>
                            <strong class="mt-2 block text-4xl font-black leading-none text-slate-900">{{ $stat['value'] }}</strong>
                            @if ($stat['suffix'])
                                <p class="mt-1 text-sm text-slate-500">{{ $stat['suffix'] }}</p>
                            @endif
                        </div>
                        <span class="performance-stat-icon inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl">
                            <i class="fas {{ $stat['icon'] }}"></i>
                        </span>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="performance-main-grid">
            <section class="space-y-5">
                <div class="performance-score-grid">
                    <section class="performance-panel p-5">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Rating summary</h2>
                                <p class="mt-1 text-sm text-slate-500">Average score and star distribution.</p>
                            </div>
                            <span class="rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-semibold uppercase text-blue-700">
                                {{ $totalRatings }} review{{ $totalRatings === 1 ? '' : 's' }}
                            </span>
                        </div>

                        @if ($avgRating)
                            <div class="rounded-[1.1rem] border border-blue-100 bg-blue-50 p-5 text-center">
                                <div class="text-5xl font-black tracking-tight text-slate-900">{{ $avgRating }}</div>
                                <div class="mt-3 text-xl text-amber-500">
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
                            <div class="performance-empty-state">
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

                    <section class="performance-panel p-5">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Ranking</h2>
                                <p class="mt-1 text-sm text-slate-500">Your standing compared with the staff team.</p>
                            </div>
                            <span class="rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-semibold uppercase text-blue-700">
                                {{ $rankBadge }}
                            </span>
                        </div>

                        <div class="rounded-[1.1rem] border border-blue-100 bg-blue-50 p-5 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-blue-700 shadow-sm">
                                <i class="fas fa-trophy text-2xl"></i>
                            </div>
                            <div class="mt-4 text-5xl font-black tracking-tight {{ $rankTone }}">#{{ $myRank }}</div>
                            <p class="mt-2 text-sm text-slate-500">out of {{ $totalStaff }} staff members</p>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-[1rem] border border-blue-100 bg-white px-4 py-4 text-center shadow-sm">
                                <div class="text-2xl font-black text-slate-900">{{ $completedCount }}</div>
                                <div class="mt-1 text-xs font-semibold uppercase text-slate-500">Jobs done</div>
                            </div>
                            <div class="rounded-[1rem] border border-blue-100 bg-white px-4 py-4 text-center shadow-sm">
                                <div class="text-2xl font-black {{ $completionRate >= 70 ? 'text-emerald-700' : ($completionRate >= 40 ? 'text-amber-600' : 'text-red-600') }}">
                                    {{ $completionRate }}%
                                </div>
                                <div class="mt-1 text-xs font-semibold uppercase text-slate-500">Completion rate</div>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="performance-panel overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-blue-100 px-5 py-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Customer reviews</h2>
                            <p class="mt-1 text-sm text-slate-500">Recent comments and review photos from completed bookings.</p>
                        </div>
                        <span class="rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-semibold uppercase text-blue-700">
                            {{ $totalRatings }} total
                        </span>
                    </div>

                    <div class="px-5 py-5">
                        @if ($allRatings->count())
                            <div class="space-y-4">
                                @foreach ($allRatings as $booking)
                                    <article class="rounded-[1.1rem] border border-blue-100 bg-blue-50/50 p-5 transition hover:bg-white">
                                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <div class="text-base text-amber-500">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        {!! $i <= $booking->rating->stars ? '&#9733;' : '&#9734;' !!}
                                                    @endfor
                                                </div>
                                                <p class="mt-3 text-sm font-semibold text-slate-900">{{ $booking->user->display_name }}</p>
                                                <p class="mt-1 text-sm text-slate-500">{{ $booking->service_label }}</p>
                                            </div>
                                            <span class="text-xs font-medium uppercase text-slate-400">
                                                {{ \Carbon\Carbon::parse($booking->updated_at)->timezone($performanceTimezone)->format('M d, Y') }}
                                            </span>
                                        </div>

                                        @if ($booking->rating->comment)
                                            <blockquote class="mt-4 rounded-[1rem] border border-blue-100 bg-white px-4 py-4 text-sm italic leading-7 text-slate-600 shadow-sm">
                                                "{{ $booking->rating->comment }}"
                                            </blockquote>
                                        @else
                                            <p class="mt-4 text-sm italic text-slate-400">No written feedback was provided.</p>
                                        @endif

                                        @if ($booking->rating->photo)
                                            <div class="mt-4">
                                                <img
                                                    src="{{ route('bookings.rating-photo', $booking) }}"
                                                    alt="Client review photo"
                                                    class="max-h-32 rounded-2xl border border-slate-200 object-cover shadow-sm"
                                                >
                                            </div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <div class="performance-empty-state">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
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

            <aside class="space-y-5">
                <section class="performance-panel p-5">
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-slate-900">Performance snapshot</h2>
                        <p class="mt-1 text-sm text-slate-500">The numbers that matter most right now.</p>
                    </div>

                    <div class="space-y-3">
                        <div class="performance-tip">
                            <span class="performance-tip-icon">
                                <i class="fas fa-wallet text-xs"></i>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Completed Service Value</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">P{{ number_format($totalEarnings, 2) }} from completed bookings.</p>
                            </div>
                        </div>

                        <div class="performance-tip">
                            <span class="performance-tip-icon">
                                <i class="fas fa-broom text-xs"></i>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Completed services</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $completedCount }} job{{ $completedCount === 1 ? '' : 's' }} fully completed so far.</p>
                            </div>
                        </div>

                        <div class="performance-tip">
                            <span class="performance-tip-icon">
                                <i class="fas fa-user-group text-xs"></i>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Ranking context</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">You currently rank #{{ $myRank }} out of {{ $totalStaff }} staff members.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="performance-panel border-amber-200 bg-amber-50/80 p-5">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-amber-600 shadow-sm">
                            <i class="fas fa-lightbulb text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Keep momentum</h2>
                            <p class="text-sm text-slate-500">Small habits improve reviews and completion rate.</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="performance-tip">
                            <span class="performance-tip-icon">
                                <i class="fas fa-check text-xs"></i>
                            </span>
                            <p class="text-sm leading-6 text-slate-600">
                                Upload before and after proof consistently so clients feel confident leaving feedback.
                            </p>
                        </div>
                        <div class="performance-tip">
                            <span class="performance-tip-icon">
                                <i class="fas fa-clock text-xs"></i>
                            </span>
                            <p class="text-sm leading-6 text-slate-600">
                                Stay on top of confirmed jobs early to keep service timing strong.
                            </p>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection
