@extends('layouts.admin')
@section('title', 'Add Service')
@section('page-title', 'Add Service')
@section('page-subtitle', 'Create a new cleaning service and make it available for booking')

@section('content')
<div class="space-y-6" style="font-family: 'DM Sans', sans-serif; max-width: 820px;">
    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:14px;padding:14px 16px;">
        <div style="font-size:14px;font-weight:700;">Please review the service form.</div>
        <div style="font-size:13px;line-height:1.6;margin-top:4px;">The new service could not be saved because one or more fields need attention.</div>
    </div>
    @endif

    <div style="background:white;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 1px 4px rgba(0,0,0,0.05);overflow:hidden;">
        <div style="padding:20px 22px;border-bottom:1px solid #f1f5f9;">
            <div style="font-size:18px;font-weight:800;color:#1e293b;">Service Details</div>
            <div style="font-size:13px;color:#64748b;margin-top:4px;">Define the service name, description, price, and availability for clients.</div>
        </div>
        <form action="{{ route('admin.services.store') }}" method="POST" style="padding:20px 22px;">
            @csrf
            <div class="space-y-5">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Service Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Basic Clean" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                    @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Description</label>
                    <textarea name="description" rows="3" placeholder="Brief description of this service..." class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Price (&#8369;) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" value="{{ old('price') }}" required min="1" step="0.01" placeholder="e.g. 500" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                    @error('price')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked class="h-4 w-4 rounded text-emerald-600">
                    <label for="is_active" class="text-sm font-medium text-gray-700">Active and visible to clients</label>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" class="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    <i class="fas fa-save mr-1"></i> Save Service
                </button>
                <a href="{{ route('admin.services.index') }}" class="rounded-xl border border-gray-300 px-6 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100">
                    Back to Services
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
