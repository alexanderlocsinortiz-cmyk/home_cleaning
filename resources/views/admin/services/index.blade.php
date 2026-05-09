@extends('layouts.admin')
@section('title', 'Services - Home Cleaning Service Admin')
@section('page-title', 'Services')
@section('page-subtitle', 'Manage cleaning service types, pricing, and visibility for booking')

@section('content')
<div class="admin-page-content cleanflow-page-shell space-y-6 p-6">
    @if(session('success'))
        <div class="cleanflow-alert cleanflow-alert--success flex items-start gap-3">
            <i class="fas fa-check-circle mt-0.5"></i>
            <div>
                <div class="text-sm font-bold">Action completed</div>
                <div class="text-sm">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="cleanflow-alert cleanflow-alert--error flex items-start gap-3">
            <i class="fas fa-exclamation-triangle mt-0.5"></i>
            <div>
                <div class="text-sm font-bold">Action blocked</div>
                <div class="text-sm">{{ session('error') }}</div>
            </div>
        </div>
    @endif

    <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
        <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <span class="cleanflow-kicker">
                    <i class="fas fa-sparkles"></i>
                    Service Catalog
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Keep every package clear, current, and ready to book.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                    Maintain pricing, descriptions, and package visibility so the admin team, public catalog, and booking
                    flow stay aligned with the current service lineup.
                </p>
            </div>
            <div class="flex flex-col items-start gap-3 rounded-3xl border border-white/18 bg-white/10 px-5 py-4 shadow-[0_18px_40px_rgba(15,23,42,0.15)] backdrop-blur xl:min-w-[260px] xl:items-end">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Catalog Count</div>
                <div class="text-4xl font-black leading-none">{{ number_format($services->count()) }}</div>
                <div class="text-sm text-white/72">{{ number_format($missingPackages->count()) }} recommended template{{ $missingPackages->count() === 1 ? '' : 's' }} still available</div>
                <a href="{{ route('admin.services.create') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50">
                    <i class="fas fa-plus"></i>
                    Add Service
                </a>
            </div>
        </div>
    </section>

    @if($missingPackages->isNotEmpty())
        <section class="rounded-[28px] border border-primary-200 bg-primary-50 px-6 py-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h3 class="text-lg font-extrabold text-emerald-950">Recommended package templates</h3>
                    <p class="mt-1 max-w-2xl text-sm leading-7 text-emerald-800/80">
                        Quick-add any missing standard package so the catalog stays aligned with the defense-ready service lineup.
                    </p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/80 px-3 py-1.5 text-xs font-bold text-emerald-700">
                    <i class="fas fa-layer-group"></i>
                    {{ number_format($missingPackages->count()) }} template{{ $missingPackages->count() === 1 ? '' : 's' }} available
                </div>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($missingPackages as $package)
                    <article class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-lg text-emerald-600">
                                <i class="fas {{ $package['icon'] }}"></i>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-extrabold uppercase tracking-[0.14em] text-emerald-700">
                                {{ $package['badge'] }}
                            </span>
                        </div>
                        <h4 class="mt-4 text-base font-extrabold text-slate-900">{{ $package['name'] }}</h4>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $package['summary'] }}</p>
                        <div class="mt-4 text-sm font-bold text-emerald-700">
                            Suggested starting rate: &#8369;{{ number_format($package['recommended_price'], 0) }}
                        </div>
                        <a href="{{ route('admin.services.create', ['template' => $package['slug']]) }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-emerald-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-700">
                            <i class="fas fa-plus"></i>
                            Add This Package
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-slate-900">Service Catalog</h3>
                <p class="mt-1 text-sm text-slate-500">Review available services, update package pricing, and control what clients can book.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-500">
                <i class="fas fa-table-list text-slate-400"></i>
                {{ number_format($services->count()) }} service{{ $services->count() === 1 ? '' : 's' }}
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full text-sm">
                <thead class="bg-slate-50/90">
                    <tr>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">#</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Service Name</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Description</th>
                        <th class="px-5 py-3 text-center text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Price</th>
                        <th class="px-5 py-3 text-center text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Status</th>
                        <th class="px-5 py-3 text-right text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                        @php
                            $package = $packageCatalog[$service->slug] ?? null;
                        @endphp
                        <tr class="border-t border-slate-100 transition hover:bg-slate-50/70">
                            <td class="px-5 py-4 text-sm font-semibold text-slate-400">{{ $loop->iteration }}</td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900">{{ $service->name }}</div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @if($package)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-700">{{ $package['badge'] }}</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-slate-600">Custom Service</span>
                                    @endif
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Slug: {{ $service->slug }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-500">
                                <div>{{ $package['summary'] ?? $service->description ?? '--' }}</div>
                                @if(!empty($package['highlight']))
                                    <div class="mt-1 text-xs text-slate-400">{{ $package['highlight'] }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center text-sm font-black text-emerald-600">&#8369;{{ number_format($service->price, 2) }}</td>
                            <td class="px-5 py-4 text-center">
                                @if($service->is_active)
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Active</span>
                                @else
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">Inactive</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.services.edit', $service->id) }}" class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition hover:bg-blue-100">
                                        <i class="fas fa-pen"></i>
                                        Edit Service
                                    </a>
                                    <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST" onsubmit="return confirm('Archive this service? Existing booking history will stay intact.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700 transition hover:bg-amber-100">
                                            <i class="fas fa-box-archive"></i>
                                            Archive
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-[4.5rem] w-[4.5rem] items-center justify-center rounded-[1.75rem] bg-slate-100 text-3xl text-slate-400">
                                    <i class="fas fa-concierge-bell"></i>
                                </div>
                                <h4 class="mt-5 text-xl font-black text-slate-900">No services have been added yet</h4>
                                <p class="mx-auto mt-2 max-w-xl text-sm leading-7 text-slate-500">
                                    Create your first service so clients can begin booking through the platform.
                                </p>
                                <a href="{{ route('admin.services.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                                    <i class="fas fa-plus"></i>
                                    Add Service
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
