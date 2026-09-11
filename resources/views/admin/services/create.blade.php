@extends('layouts.admin')
@section('title', 'Add Service')
@section('page-title', 'Add Service')
@section('page-subtitle', 'Create a new cleaning service and make it available for booking')

@section('content')
<div class="admin-page-content cleanflow-page-shell max-w-5xl space-y-6 p-6">
    @php
        $nameValue = old('name', '');
        $descriptionValue = old('description', '');
        $priceValue = old('price', '');
        $durationValue = old('duration_minutes', \App\Models\Service::DEFAULT_DURATION_MINUTES);
        $sortOrderValue = old('sort_order', 0);
        $scopeMaxAreaValue = old('scope_max_floor_area', 30);
        $scopeCleanerCountValue = old('scope_cleaner_count', 1);
        $scopeStatusValue = old('scope_status', 'provisional');
        $isActiveChecked = old('is_active', '1');
    @endphp

    @if($errors->any())
        <div class="cleanflow-alert cleanflow-alert--error">
            <div class="text-sm font-bold">Please review the service form.</div>
            <div class="mt-1 text-sm">The new service could not be saved because one or more fields need attention.</div>
        </div>
    @endif

    <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
        <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <span class="cleanflow-kicker">
                    <i class="fas fa-plus"></i>
                    Catalog Builder
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Add a booking-ready service with clean defaults.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                    Create a service entry for the catalog your clients and admins rely on during booking.
                </p>
            </div>
            <a href="{{ route('admin.services.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50">
                <i class="fas fa-arrow-left"></i>
                Back to Services
            </a>
        </div>
    </section>

    <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 px-6 py-5">
            <h3 class="text-lg font-extrabold text-slate-900">Service Details</h3>
            <p class="mt-1 text-sm text-slate-500">Define the service name, description, price, and availability for clients.</p>
        </div>
        <form action="{{ route('admin.services.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6 px-6 py-6"
            data-service-confirm
            data-confirm-title="Create this service?"
            data-confirm-message="This will add a new service package to the admin service catalog."
            data-confirm-button="Create Service"
            data-confirm-tone="primary">
            @csrf
            <div class="space-y-5">
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Service Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ $nameValue }}" required maxlength="100" placeholder="e.g. Basic Clean" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                    @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Description</label>
                    <textarea name="description" rows="4" maxlength="5000" placeholder="Brief description of this service..." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">{{ $descriptionValue }}</textarea>
                </div>
                @include('admin.services._image_fields')
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Price (&#8369;) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" value="{{ $priceValue }}" required min="1" max="99999999.99" step="0.01" placeholder="e.g. 500" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                    @error('price')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Estimated Duration (minutes) <span class="text-red-500">*</span></label>
                    <input type="number" name="duration_minutes" value="{{ $durationValue }}" required min="30" max="720" step="15" placeholder="e.g. 120" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                    <p class="mt-1 text-xs text-slate-500">Staff availability blocks this duration plus 60 minutes rest.</p>
                    @error('duration_minutes')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Landing Page Display Order</label>
                    <input type="number" name="sort_order" value="{{ $sortOrderValue }}" min="0" max="9999" step="1" placeholder="e.g. 1" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                    <p class="mt-1 text-xs text-slate-500">Lower numbers appear first. Equal numbers fall back to price.</p>
                    @error('sort_order')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4">
                    <div class="text-sm font-extrabold text-slate-900">Measurable Scope Controls</div>
                    <p class="mt-1 text-xs leading-5 text-slate-600">Set only limits the system can measure: area, cleaner count, and approval status. Approved requires the area limit, cleaner count, and every scope-definition field below.</p>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Maximum Floor Area (sqm) <span class="text-red-500">*</span> <span class="font-normal normal-case tracking-normal text-slate-400">when approved</span></label>
                            <input type="number" name="scope_max_floor_area" value="{{ $scopeMaxAreaValue }}" min="10" max="1000" step="1" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                            @error('scope_max_floor_area')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Standard Cleaner Count <span class="text-red-500">*</span></label>
                            <input type="number" name="scope_cleaner_count" value="{{ $scopeCleanerCountValue }}" required min="1" max="20" step="1" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                            @error('scope_cleaner_count')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Scope Status <span class="text-red-500">*</span></label>
                            <select name="scope_status" required class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                                <option value="provisional" {{ $scopeStatusValue === 'provisional' ? 'selected' : '' }}>Provisional — warn above limit</option>
                                <option value="approved" {{ $scopeStatusValue === 'approved' ? 'selected' : '' }}>Approved — block above limit</option>
                            </select>
                            @error('scope_status')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <label class="flex items-center gap-3 self-end rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <input type="checkbox" name="scope_manual_review_above_limit" value="1" {{ old('scope_manual_review_above_limit', true) ? 'checked' : '' }} class="h-4 w-4 rounded text-emerald-600">
                            <span class="text-sm font-semibold text-slate-700">Flag over-limit requests for manual review</span>
                        </label>
                    </div>
                </div>
                @include('admin.services._scope_definition_fields', ['scopeService' => null])
                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ $isActiveChecked ? 'checked' : '' }} class="h-4 w-4 rounded text-emerald-600">
                    <span class="text-sm font-semibold text-slate-700">Active and visible to clients</span>
                </label>
            </div>
            <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-4">
                <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                    <i class="fas fa-save"></i>
                    Save Service
                </button>
                <a href="{{ route('admin.services.index') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                    <i class="fas fa-arrow-left"></i>
                    Back to Services
                </a>
            </div>
        </form>
    </section>
</div>
@include('admin.services._confirm_modal')
@endsection
