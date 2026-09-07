@extends('layouts.app')

@section('title', $service->name . ' - Home Cleaning Service')

@section('content')
<main class="bg-slate-50">
    <section class="border-b border-slate-200 bg-white">
        <div class="container-pad mx-auto max-w-7xl px-6 py-6">
            <a href="{{ route('home') }}#services" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 transition hover:text-blue-900">
                <i class="fas fa-arrow-left"></i>
                Back to services
            </a>
        </div>
    </section>

    <section class="container-pad mx-auto max-w-7xl px-6 py-10 lg:py-16">
        <div class="grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg">
                <img src="{{ $service->image_url }}" alt="{{ $service->image_alt }}" class="h-[280px] w-full object-cover sm:h-[420px]" fetchpriority="high" decoding="async">
            </div>

            <div>
                <div class="text-sm font-extrabold uppercase tracking-[0.18em] text-blue-600">{{ $isOfficeService ? 'Office cleaning service' : 'Home cleaning service' }}</div>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-950 sm:text-5xl">{{ $service->name }}</h1>
                <p class="mt-6 text-lg leading-8 text-slate-600">{{ $service->description ?: ($servicePackage['default_description'] ?? 'Professional cleaning support for your space.') }}</p>

                <div class="mt-7 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-2xl border border-blue-100 bg-white p-4 shadow-sm">
                        <div class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Price</div>
                        <div class="mt-2 text-xl font-black text-blue-700">
                            @if(\App\Models\Service::usesPerSquareMeterPricing($service->slug))
                                &#8369;{{ number_format($service->price, 0) }} per sqm
                            @elseif(\App\Models\Service::usesFlatRateRangePricing($service->slug) && ($range = \App\Models\Service::priceRangeForSlug($service->slug)))
                                &#8369;{{ number_format($range['min'], 0) }} - &#8369;{{ number_format($range['max'], 0) }} flat rate
                            @else
                                Starting at &#8369;{{ number_format($service->price, 0) }}
                            @endif
                        </div>
                    </div>
                    <div class="rounded-2xl border border-blue-100 bg-white p-4 shadow-sm">
                        <div class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Typical duration</div>
                        <div class="mt-2 text-xl font-black text-slate-900">{{ $service->duration_minutes ?: \App\Models\Service::durationForSlug($service->slug) }} minutes</div>
                    </div>
                    <div class="rounded-2xl border border-blue-100 bg-white p-4 shadow-sm sm:col-span-2 lg:col-span-1">
                        <div class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Planning basis</div>
                        <div class="mt-2 text-lg font-black text-slate-900">
                            {{ $scope['max_floor_area'] ? 'Up to '.number_format($scope['max_floor_area']).' sqm' : 'Manual quote' }}
                        </div>
                        <div class="mt-1 text-xs leading-5 text-slate-500">
                            {{ $scope['cleaner_count'] }} planned cleaner{{ $scope['cleaner_count'] === 1 ? '' : 's' }}; actual staffing may change after review.
                        </div>
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border {{ $scope['status'] === 'approved' ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900' }} p-4 text-sm leading-6">
                    <div class="font-extrabold">{{ $scope['status'] === 'approved' ? 'Approved scope basis' : 'Provisional planning scope' }}</div>
                    <div class="mt-1">{{ $scope['status'] === 'approved' ? 'The measurable limit and customer-facing scope fields are approved for self-service booking.' : 'The listed features are a planning baseline, not a promise that every possible task is included. Final work depends on property condition, access, equipment, and booked time.' }}</div>
                </div>

                <a href="{{ route('bookings.create', ['service' => $service->slug]) }}" class="sales-primary-button mt-8 inline-flex w-full items-center justify-center gap-2 rounded-2xl px-6 py-4 text-base font-bold text-white transition hover:-translate-y-0.5 sm:w-auto">
                    <i class="fas fa-calendar-check"></i>
                    Book this service
                </a>
            </div>
        </div>
    </section>

    <section class="container-pad mx-auto max-w-7xl px-6 pb-16">
        <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-2xl font-black text-slate-950">Package features and boundaries</h2>
                @if(!empty($servicePackage['features']))
                    <div class="mt-6 space-y-3">
                        @foreach($servicePackage['features'] as $feature)
                            <div class="flex items-start gap-3 text-sm leading-7 text-slate-600">
                                <i class="fas fa-check-circle mt-1 text-blue-600"></i>
                                <span>{{ $feature }}</span>
                            </div>
                        @endforeach
                    </div>
                @elseif(filled($scopeDefinition['included_tasks'] ?? null))
                    <p class="mt-5 text-sm leading-7 text-slate-600">{{ $scopeDefinition['included_tasks'] }}</p>
                @else
                    <p class="mt-5 text-sm leading-7 text-slate-600">The exact work depends on your property condition, access, floor area, and booked service time.</p>
                @endif

                @if(filled($scopeDefinition['included_tasks'] ?? null))
                    <h3 class="mt-8 text-lg font-bold text-slate-900">Detailed work</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">{{ $scopeDefinition['included_tasks'] }}</p>
                @endif

                @if(filled($scopeDefinition['included_areas'] ?? null))
                    <h3 class="mt-8 text-lg font-bold text-slate-900">Areas covered</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">{{ $scopeDefinition['included_areas'] }}</p>
                @endif
            </div>

            <div class="space-y-8">
                @if(filled($scopeDefinition['excluded_tasks'] ?? null))
                    <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                        <h2 class="text-2xl font-black text-amber-950">Important limits</h2>
                        <p class="mt-4 text-sm leading-7 text-amber-900">{{ $scopeDefinition['excluded_tasks'] }}</p>
                    </div>
                @endif

                @if(filled($scopeDefinition['condition_limits'] ?? null))
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-2xl font-black text-slate-950">Before you book</h2>
                        <p class="mt-4 text-sm leading-7 text-slate-600">{{ $scopeDefinition['condition_limits'] }}</p>
                    </div>
                @endif

                @if(filled($scopeDefinition['equipment_policy'] ?? null))
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-2xl font-black text-slate-950">Supplies and equipment</h2>
                        <p class="mt-4 text-sm leading-7 text-slate-600">{{ $scopeDefinition['equipment_policy'] }}</p>
                    </div>
                @endif

                @if(filled($scopeDefinition['access_limits'] ?? null))
                    <div class="rounded-3xl border border-red-200 bg-red-50 p-6 shadow-sm">
                        <h2 class="text-2xl font-black text-red-950">Access and safety</h2>
                        <p class="mt-4 text-sm leading-7 text-red-900">{{ $scopeDefinition['access_limits'] }}</p>
                    </div>
                @endif

                @if(filled($scopeDefinition['extra_work_policy'] ?? null))
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-2xl font-black text-slate-950">If the work exceeds this scope</h2>
                        <p class="mt-4 text-sm leading-7 text-slate-600">{{ $scopeDefinition['extra_work_policy'] }}</p>
                    </div>
                @endif

                @if(filled($scopeDefinition['acceptance_criteria'] ?? null))
                    <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                        <h2 class="text-2xl font-black text-emerald-950">Completion and acceptance</h2>
                        <p class="mt-4 text-sm leading-7 text-emerald-900">{{ $scopeDefinition['acceptance_criteria'] }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
</main>
@endsection
