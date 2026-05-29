@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : (auth()->user()->role === 'staff' ? 'layouts.staff' : 'layouts.client'))
@section('title', 'Live Video - Home Cleaning Service')
@section('page-title', 'Live Video')
@section('page-subtitle', 'Booking-only service monitoring')

@section('content')
@php
    $viewer = auth()->user();
    $bookingCode = 'CF-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT);
    $backUrl = route('bookings.show', $booking->id);
@endphp

<div class="cleanflow-page-shell min-h-[calc(100vh-81px)] px-6 py-8">
    <div class="mx-auto max-w-7xl">
        <div class="cleanflow-hero mb-6 px-6 py-5 text-white">
            <div class="cleanflow-hero-content flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <span class="cleanflow-kicker">
                        <i class="fa-solid fa-video"></i>
                        Live Service View
                    </span>
                    <h1 class="mt-3 text-2xl font-bold text-white">{{ $bookingCode }} live video</h1>
                    <p class="mt-2 text-sm text-white/80">
                        {{ $canBroadcast ? 'Your camera and microphone can be shared with the client.' : 'Viewer mode is enabled. Your camera and microphone are blocked.' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-3 self-start lg:self-auto">
                    @if($booking->canManageLiveVideo($viewer))
                    <form action="{{ route('bookings.live-video.end', $booking) }}" method="POST" onsubmit="return confirm('End the live video for this booking? Clients will not be able to rejoin this room.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-red-600 px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:bg-red-700">
                            <i class="fa-solid fa-circle-stop"></i>
                            End Live Video
                        </button>
                    </form>
                    @endif
                    <a href="{{ $backUrl }}" class="cleanflow-ghost-button">
                        <i class="fa-solid fa-arrow-left"></i>
                        Back to booking
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
            <section class="cleanflow-panel overflow-hidden">
                <div id="daily-video-frame" class="h-[68vh] min-h-[460px] bg-slate-950"></div>
                <div id="daily-video-error" class="hidden border-t border-red-100 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700"></div>
            </section>

            <aside class="space-y-5">
                <div class="cleanflow-panel p-5">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Booking</div>
                    <div class="mt-2 text-xl font-bold text-slate-900">{{ $bookingCode }}</div>
                    <div class="mt-1 text-sm text-slate-500">{{ $booking->service_label }}</div>

                    <div class="mt-5 space-y-3 text-sm">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <div class="font-semibold text-slate-700">Client</div>
                            <div class="text-slate-500">{{ $booking->user->display_name }}</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <div class="font-semibold text-slate-700">Cleaner</div>
                            <div class="text-slate-500">{{ $booking->staff?->display_name ?? 'Assigned staff' }}</div>
                        </div>
                    </div>
                </div>

                <div class="cleanflow-panel border-l-4 border-amber-400 p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-slate-900">Privacy rule</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">
                                Use live video only during active service work. Do not record rooms, documents, IDs, or private belongings unless the client clearly approves it.
                            </p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/@daily-co/daily-js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('daily-video-frame');
        const errorPanel = document.getElementById('daily-video-error');

        if (!window.DailyIframe || !container) {
            if (errorPanel) {
                errorPanel.textContent = 'Live video could not load. Check your internet connection and refresh this page.';
                errorPanel.classList.remove('hidden');
            }
            return;
        }

        const callFrame = window.DailyIframe.createFrame(container, {
            iframeStyle: {
                width: '100%',
                height: '100%',
                border: '0',
                backgroundColor: '#020617'
            },
            showLeaveButton: true,
            showFullscreenButton: true
        });

        callFrame.join({
            url: @json($roomUrl),
            token: @json($meetingToken),
            userName: @json($viewerName)
        }).catch((error) => {
            if (errorPanel) {
                errorPanel.textContent = error?.message || 'Live video could not be joined.';
                errorPanel.classList.remove('hidden');
            }
        });
    });
</script>
@endpush
