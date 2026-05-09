@extends('layouts.admin')
@section('title', 'Edit Service')
@section('page-title', 'Edit Service')
@section('page-subtitle', 'Update service details, pricing, and booking visibility')

@section('content')
<div class="admin-page-content cleanflow-page-shell max-w-5xl space-y-6 p-6">
    @if($errors->any())
        <div class="cleanflow-alert cleanflow-alert--error">
            <div class="text-sm font-bold">Please review the service form.</div>
            <div class="mt-1 text-sm">The service could not be updated because one or more fields need attention.</div>
        </div>
    @endif

    <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
        <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <span class="cleanflow-kicker">
                    <i class="fas fa-pen"></i>
                    Catalog Update
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Refine {{ $service->name }} without leaving the workflow.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                    Update pricing, descriptions, and catalog visibility so the public catalog and admin booking decisions stay aligned.
                </p>
            </div>
            <a href="{{ route('admin.services.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50">
                <i class="fas fa-arrow-left"></i>
                Back to Services
            </a>
        </div>
    </section>

    @if($servicePackage)
        <section class="rounded-[28px] border border-primary-200 bg-primary-50 px-6 py-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Recognized package template</h3>
                    <p class="mt-1 max-w-2xl text-sm leading-7 text-slate-500">
                        This service matches one of the standard Week 5 package templates used in the public catalog and booking flow.
                    </p>
                </div>
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-extrabold uppercase tracking-[0.14em] text-emerald-700">
                    {{ $servicePackage['badge'] }}
                </span>
            </div>
            <div class="mt-4 text-sm font-bold text-emerald-800">{{ $servicePackage['name'] }}</div>
            <div class="mt-1 text-sm leading-6 text-slate-500">{{ $servicePackage['highlight'] }}</div>
        </section>
    @endif

    <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 px-6 py-5">
            <h3 class="text-lg font-extrabold text-slate-900">Service Details</h3>
            <p class="mt-1 text-sm text-slate-500">Update the catalog details clients and admins rely on during booking.</p>
        </div>
        <form action="{{ route('admin.services.update', $service->id) }}" method="POST" class="space-y-6 px-6 py-6">
            @csrf
            @method('PUT')
            <div class="space-y-5">
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Service Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $service->name) }}" required class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                    @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Description</label>
                    <textarea name="description" rows="4" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">{{ old('description', $service->description) }}</textarea>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Price (&#8369;) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" value="{{ old('price', $service->price) }}" required min="1" step="0.01" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                    @error('price')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ $service->is_active ? 'checked' : '' }} class="h-4 w-4 rounded text-emerald-600">
                    <span class="text-sm font-semibold text-slate-700">Active and visible to clients</span>
                </label>
            </div>
            <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-4">
                <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                    <i class="fas fa-save"></i>
                    Save Service Changes
                </button>
                <a href="{{ route('admin.services.index') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                    <i class="fas fa-arrow-left"></i>
                    Back to Services
                </a>
            </div>
        </form>
    </section>
</div>
@endsection
