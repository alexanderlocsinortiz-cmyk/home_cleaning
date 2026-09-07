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

    @if($errors->any())
        <div class="cleanflow-alert cleanflow-alert--error flex items-start gap-3">
            <i class="fas fa-exclamation-triangle mt-0.5"></i>
            <div>
                <div class="text-sm font-bold">Check the form</div>
                <div class="text-sm">{{ $errors->first() }}</div>
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
            <div class="flex flex-col items-start gap-3 rounded-3xl border border-white/18 bg-white/10 px-5 py-4 shadow-[0_18px_40px_rgba(15,23,42,0.15)] backdrop-blur xl:min-w-[320px] xl:items-end">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Catalog Count</div>
                <div class="text-4xl font-black leading-none">{{ number_format($services->count()) }}</div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="toggle-add-ons-panel" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-bold text-blue-700 transition hover:bg-blue-50" aria-controls="booking-add-ons-panel" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}">
                        <i class="fas fa-puzzle-piece"></i>
                        Manage Add-ons
                    </button>
                    <a href="{{ route('admin.services.create') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50">
                        <i class="fas fa-plus"></i>
                        Add Service
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section id="booking-add-ons-panel" class="{{ $errors->any() ? '' : 'hidden' }} rounded-[28px] border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-slate-900">Booking Add-ons</h3>
                <p class="mt-1 text-sm text-slate-500">Manage optional extras clients can select during booking. These are not service packages. Current add-ons are charged once per booking; quantity billing is not supported.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-500">
                <i class="fas fa-puzzle-piece text-slate-400"></i>
                {{ number_format($addOns->count()) }} add-on{{ $addOns->count() === 1 ? '' : 's' }}
            </div>
        </div>

        <form action="{{ route('admin.services.add-ons.store') }}" method="POST" class="grid gap-4 border-b border-slate-100 px-6 py-5 lg:grid-cols-[1.2fr_1.6fr_0.7fr_0.55fr_auto_auto] lg:items-end"
            data-service-confirm
            data-confirm-title="Add this add-on?"
            data-confirm-message="This will add a new optional booking add-on to the catalog."
            data-confirm-button="Add Add-on"
            data-confirm-tone="primary">
            @csrf
            <div>
                <label for="addon-label" class="text-xs font-bold uppercase tracking-wide text-slate-500">Add-on Name</label>
                <input id="addon-label" type="text" name="label" value="{{ old('label') }}" required class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" placeholder="Example: Oven Cleaning">
            </div>
            <div>
                <label for="addon-description" class="text-xs font-bold uppercase tracking-wide text-slate-500">Description</label>
                <input id="addon-description" type="text" name="description" value="{{ old('description') }}" class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" placeholder="What this add-on includes">
            </div>
            <div>
                <label for="addon-price" class="text-xs font-bold uppercase tracking-wide text-slate-500">Price</label>
                <input id="addon-price" type="number" name="price" value="{{ old('price') }}" min="0" step="0.01" required class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
            </div>
            <div>
                <label for="addon-sort-order" class="text-xs font-bold uppercase tracking-wide text-slate-500">Order</label>
                <input id="addon-sort-order" type="number" name="sort_order" value="{{ old('sort_order', $addOns->count() + 1) }}" min="0" class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
            </div>
            <label class="inline-flex items-center gap-2 text-sm font-bold text-slate-700">
                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" checked>
                Active
            </label>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                <i class="fas fa-plus"></i>
                Add Add-on
            </button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full text-sm">
                <thead class="bg-slate-50/90">
                    <tr>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Add-on</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Description</th>
                        <th class="px-5 py-3 text-center text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Price</th>
                        <th class="px-5 py-3 text-center text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Order</th>
                        <th class="px-5 py-3 text-center text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Status</th>
                        <th class="px-5 py-3 text-right text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($addOns as $addOn)
                        <tr class="border-t border-slate-100 transition hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <input form="addon-update-{{ $addOn->id }}" type="text" name="label" value="{{ old('label', $addOn->label) }}" required class="w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                <div class="mt-2 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Key: {{ $addOn->key }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <input form="addon-update-{{ $addOn->id }}" type="text" name="description" value="{{ old('description', $addOn->description) }}" class="w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm text-slate-600 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </td>
                            <td class="px-5 py-4">
                                <input form="addon-update-{{ $addOn->id }}" type="number" name="price" value="{{ old('price', $addOn->price) }}" min="0" step="0.01" required class="mx-auto w-32 rounded-2xl border border-slate-200 px-3 py-2 text-center text-sm font-black text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </td>
                            <td class="px-5 py-4">
                                <input form="addon-update-{{ $addOn->id }}" type="number" name="sort_order" value="{{ old('sort_order', $addOn->sort_order) }}" min="0" class="mx-auto w-24 rounded-2xl border border-slate-200 px-3 py-2 text-center text-sm font-bold text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </td>
                            <td class="px-5 py-4 text-center">
                                <label class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold {{ $addOn->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                    <input form="addon-update-{{ $addOn->id }}" type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" {{ $addOn->is_active ? 'checked' : '' }}>
                                    {{ $addOn->is_active ? 'Active' : 'Inactive' }}
                                </label>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <form id="addon-update-{{ $addOn->id }}" action="{{ route('admin.services.add-ons.update', $addOn) }}" method="POST"
                                        data-service-confirm
                                        data-confirm-title="Save add-on changes?"
                                        data-confirm-message="This will update {{ $addOn->label }} in the booking add-on catalog."
                                        data-confirm-button="Save Changes"
                                        data-confirm-tone="primary">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition hover:bg-blue-100">
                                            <i class="fas fa-save"></i>
                                            Save
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.services.add-ons.destroy', $addOn) }}" method="POST"
                                        data-service-confirm
                                        data-confirm-title="{{ $addOn->is_active ? 'Deactivate this add-on?' : 'Delete this inactive add-on?' }}"
                                        data-confirm-message="{{ $addOn->is_active ? 'Clients will no longer be able to select '.$addOn->label.' during booking.' : 'This permanently deletes '.$addOn->label.' from the add-on catalog.' }}"
                                        data-confirm-button="{{ $addOn->is_active ? 'Deactivate' : 'Delete' }}"
                                        data-confirm-tone="{{ $addOn->is_active ? 'warning' : 'danger' }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border {{ $addOn->is_active ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100' }} px-3 py-2 text-xs font-bold transition">
                                            <i class="fas {{ $addOn->is_active ? 'fa-power-off' : 'fa-trash' }}"></i>
                                            {{ $addOn->is_active ? 'Deactivate' : 'Delete' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">No add-ons have been added yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section id="service-catalog-panel" class="{{ $errors->any() ? 'hidden' : '' }} rounded-[28px] border border-slate-200 bg-white shadow-sm">
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
            <table class="min-w-[900px] w-full text-sm">
                <thead class="bg-slate-50/90">
                    <tr>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">#</th>
                        <th class="px-5 py-3 text-center text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Order</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Image / Service Name</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Description</th>
                        <th class="px-5 py-3 text-center text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Price</th>
                        <th class="px-5 py-3 text-center text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Duration</th>
                        <th class="px-5 py-3 text-center text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Measured Scope</th>
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
                            <td class="px-5 py-4 text-center text-sm font-black text-blue-700">{{ $service->sort_order ?? 0 }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <img src="{{ $service->image_url }}" alt="{{ $service->image_alt }}" loading="lazy" decoding="async" class="h-16 w-20 shrink-0 rounded-xl border border-slate-200 object-cover">
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $service->name }}</div>
                                        <div class="mt-1 text-[11px] font-semibold {{ $service->image_path ? 'text-blue-600' : 'text-emerald-600' }}">
                                            <i class="fas {{ $service->image_path ? 'fa-cloud-arrow-up' : 'fa-wand-magic-sparkles' }} mr-1"></i>{{ $service->image_path ? 'Custom image' : 'Matching default' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @if($package)
                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-indigo-700">{{ $package['badge'] }}</span>
                                    @else
                                        <span class="rounded-full bg-cyan-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-cyan-700">Custom Service</span>
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
                            <td class="px-5 py-4 text-center text-sm font-black text-slate-900">
                                @if(\App\Models\Service::usesFlatRateRangePricing($service->slug) && ($range = \App\Models\Service::priceRangeForSlug($service->slug)))
                                    &#8369;{{ number_format($range['min'], 2) }} - &#8369;{{ number_format($range['max'], 2) }}
                                @else
                                    &#8369;{{ number_format($service->price, 2) }}{{ \App\Models\Service::usesPerSquareMeterPricing($service->slug) ? ' / sqm' : '' }}
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center text-sm font-bold text-slate-700">{{ number_format($service->duration_minutes ?? \App\Models\Service::DEFAULT_DURATION_MINUTES) }} min</td>
                            <td class="px-5 py-4 text-center text-xs font-semibold text-slate-600">
                                <div>{{ $service->scope_max_floor_area ? number_format($service->scope_max_floor_area) . ' sqm' : 'No area limit' }}</div>
                                <div class="mt-1 text-[11px] {{ $service->scopeApprovalIsComplete() ? 'text-emerald-600' : 'text-amber-600' }}">{{ $service->scopeApprovalIsComplete() ? 'Definition ready for approval' : 'Approval definition incomplete' }}</div>
                                <div class="mt-1 text-[11px] text-slate-400">{{ $service->scope_cleaner_count ?: 1 }} cleaner · {{ $service->scopeIsApproved() ? 'Approved' : 'Provisional' }}</div>
                            </td>
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
                                    @if($service->is_active)
                                        <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST"
                                            data-service-confirm
                                            data-confirm-title="Deactivate this service?"
                                            data-confirm-message="Clients will no longer be able to book {{ $service->name }}."
                                            data-confirm-button="Deactivate"
                                            data-confirm-tone="warning">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700 transition hover:bg-amber-100">
                                                <i class="fas fa-power-off"></i>
                                                Deactivate
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.services.reactivate', $service) }}" method="POST"
                                            data-service-confirm
                                            data-confirm-title="Reactivate this service?"
                                            data-confirm-message="Clients will be able to book {{ $service->name }} again."
                                            data-confirm-button="Reactivate"
                                            data-confirm-tone="success">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100">
                                                <i class="fas fa-rotate-left"></i>
                                                Reactivate
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST"
                                            data-service-confirm
                                            data-confirm-title="Delete this inactive service?"
                                            data-confirm-message="This permanently deletes {{ $service->name }} from the service catalog."
                                            data-confirm-button="Delete"
                                            data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700 transition hover:bg-red-100">
                                                <i class="fas fa-trash"></i>
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center">
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
@include('admin.services._confirm_modal')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('toggle-add-ons-panel');
    const panel = document.getElementById('booking-add-ons-panel');
    const serviceCatalog = document.getElementById('service-catalog-panel');

    if (!toggle || !panel || !serviceCatalog) {
        return;
    }

    toggle.addEventListener('click', function () {
        const willShow = panel.classList.contains('hidden');

        panel.classList.toggle('hidden', !willShow);
        serviceCatalog.classList.toggle('hidden', willShow);
        toggle.setAttribute('aria-expanded', String(willShow));
        toggle.innerHTML = willShow
            ? '<i class="fas fa-table-list"></i> Show Services'
            : '<i class="fas fa-puzzle-piece"></i> Manage Add-ons';

        if (willShow) {
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            serviceCatalog.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>
@endpush
