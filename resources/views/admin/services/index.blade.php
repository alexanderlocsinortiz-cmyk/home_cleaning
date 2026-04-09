@extends('layouts.admin')
@section('title', 'Services - Home Cleaning Service Admin')
@section('page-title', 'Services')
@section('page-subtitle', 'Manage cleaning service types, pricing, and visibility for booking')

@section('content')
<div class="space-y-6" style="font-family: 'DM Sans', sans-serif;">
    @if(session('success'))
    <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;border-radius:14px;padding:14px 16px;display:flex;align-items:flex-start;gap:10px;">
        <i class="fas fa-check-circle" style="margin-top:2px;"></i>
        <div>
            <div style="font-size:14px;font-weight:700;">Action completed</div>
            <div style="font-size:13px;margin-top:2px;">{{ session('success') }}</div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:14px;padding:14px 16px;display:flex;align-items:flex-start;gap:10px;">
        <i class="fas fa-exclamation-triangle" style="margin-top:2px;"></i>
        <div>
            <div style="font-size:14px;font-weight:700;">Action blocked</div>
            <div style="font-size:13px;margin-top:2px;">{{ session('error') }}</div>
        </div>
    </div>
    @endif

    <div style="background:white;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 1px 4px rgba(0,0,0,0.05);overflow:hidden;">
        <div style="padding:20px 22px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
            <div>
                <div style="font-size:18px;font-weight:800;color:#1e293b;">Service Catalog</div>
                <div style="font-size:13px;color:#64748b;margin-top:4px;">Review available services, update pricing, and control what clients can book.</div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <div style="font-size:12px;color:#94a3b8;">
                    {{ number_format($services->count()) }} service{{ $services->count() === 1 ? '' : 's' }}
                </div>
                <a href="{{ route('admin.services.create') }}" style="display:inline-flex;align-items:center;gap:8px;background:#1D9E75;color:white;border-radius:12px;padding:11px 16px;font-size:13px;font-weight:700;text-decoration:none;">
                    <i class="fas fa-plus"></i>
                    Add Service
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm" style="min-width:760px;">
                <thead>
                    <tr class="border-b border-gray-100 bg-slate-50">
                        <th class="text-left py-3 px-5 text-xs font-bold uppercase tracking-wider text-slate-500">#</th>
                        <th class="text-left py-3 px-5 text-xs font-bold uppercase tracking-wider text-slate-500">Service Name</th>
                        <th class="text-left py-3 px-5 text-xs font-bold uppercase tracking-wider text-slate-500">Description</th>
                        <th class="text-center py-3 px-5 text-xs font-bold uppercase tracking-wider text-slate-500">Price</th>
                        <th class="text-center py-3 px-5 text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="text-right py-3 px-5 text-xs font-bold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="py-3 px-5 text-gray-400">{{ $loop->iteration }}</td>
                        <td class="py-3 px-5 font-semibold text-gray-800">{{ $service->name }}</td>
                        <td class="py-3 px-5 text-gray-500">{{ $service->description ?? '--' }}</td>
                        <td class="py-3 px-5 text-center font-bold text-green-600">&#8369;{{ number_format($service->price, 2) }}</td>
                        <td class="py-3 px-5 text-center">
                            @if($service->is_active)
                                <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">Active</span>
                            @else
                                <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">Inactive</span>
                            @endif
                        </td>
                        <td class="py-3 px-5">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.services.edit', $service->id) }}" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                                    <i class="fas fa-pen"></i> Edit Service
                                </a>
                                <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST" onsubmit="return confirm('Remove this service from the catalog?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:56px 24px;text-align:center;color:#94a3b8;">
                            <div style="width:68px;height:68px;border-radius:20px;background:#f8fafc;color:#94a3b8;display:inline-flex;align-items:center;justify-content:center;font-size:28px;">
                                <i class="fas fa-concierge-bell"></i>
                            </div>
                            <div style="font-size:18px;font-weight:800;color:#1e293b;margin-top:18px;">No services have been added yet</div>
                            <div style="font-size:13px;color:#64748b;line-height:1.7;max-width:420px;margin:8px auto 0;">
                                Create your first service so clients can begin booking through the platform.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
