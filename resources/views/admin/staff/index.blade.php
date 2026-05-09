@extends('layouts.admin')
@section('title', 'Staff')
@section('page-title', 'Staff Directory')
@section('page-subtitle', 'Manage staff profiles, access details, and service coverage assignments')

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
                    <i class="fas fa-user-group"></i>
                    Operations Directory
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Keep staffing clean, visible, and ready for dispatch.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                    Review active staff accounts, confirm contact details, and maintain the people you assign to bookings,
                    schedules, and attendance devices.
                </p>
            </div>
            <div class="flex flex-col items-start gap-3 rounded-3xl border border-white/18 bg-white/10 px-5 py-4 shadow-[0_18px_40px_rgba(15,23,42,0.15)] backdrop-blur xl:min-w-[260px] xl:items-end">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Directory Count</div>
                <div class="text-4xl font-black leading-none">{{ number_format($staff->total()) }}</div>
                <div class="text-sm text-white/72">
                    @if($staff->count())
                        Showing {{ number_format($staff->firstItem()) }}-{{ number_format($staff->lastItem()) }} right now
                    @else
                        No staff profiles yet
                    @endif
                </div>
                <a href="{{ route('admin.staff.create') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50">
                    <i class="fas fa-plus"></i>
                    Add Staff Member
                </a>
            </div>
        </div>
    </section>

    <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-slate-900">Staff Members</h3>
                <p class="mt-1 text-sm text-slate-500">Operational directory for active staff across your service coverage areas.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-500">
                <i class="fas fa-table-list text-slate-400"></i>
                @if($staff->count())
                    Showing {{ number_format($staff->firstItem()) }}-{{ number_format($staff->lastItem()) }} of {{ number_format($staff->total()) }}
                @else
                    No records to display
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[960px] w-full text-sm">
                <thead class="bg-slate-50/90">
                    <tr>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Staff Member</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Email</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Phone</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Barangay</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Username</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Average Rating</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Reviews</th>
                        <th class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Joined</th>
                        <th class="px-5 py-3 text-right text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $member)
                        <tr class="border-t border-slate-100 transition hover:bg-slate-50/70">
                            <td class="px-5 py-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-600 text-sm font-black text-white shadow-sm">
                                        {{ strtoupper(substr($member->first_name, 0, 1) . substr($member->last_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-extrabold text-slate-900">{{ $member->full_name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">Field operations staff account</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 align-middle">
                                <div class="text-sm font-semibold text-slate-800">{{ $member->email }}</div>
                            </td>
                            <td class="px-5 py-4 align-middle text-sm text-slate-500">{{ $member->phone ?? '--' }}</td>
                            <td class="px-5 py-4 align-middle text-sm text-slate-500">{{ $barangays[$member->barangay] ?? $member->barangay }}</td>
                            <td class="px-5 py-4 align-middle">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                    &#64;{{ $member->username }}
                                </span>
                            </td>
                            <td class="px-5 py-4 align-middle">
                                @if($member->avg_rating)
                                    <div class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">
                                        <i class="fas fa-star"></i>
                                        {{ $member->avg_rating }}
                                    </div>
                                @else
                                    <span class="text-xs font-semibold text-slate-400">No ratings yet</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 align-middle text-sm font-semibold text-slate-600">{{ $member->total_ratings ?? 0 }}</td>
                            <td class="px-5 py-4 align-middle text-sm text-slate-500">{{ optional($member->created_at)->format('M d, Y') }}</td>
                            <td class="px-5 py-4 align-middle text-right">
                                <div class="inline-flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('admin.staff.edit', $member) }}" class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition hover:bg-blue-100">
                                        <i class="fas fa-pen"></i>
                                        Edit Staff
                                    </a>
                                    <form action="{{ route('admin.staff.destroy', $member) }}" method="POST" onsubmit="return confirm('Remove this staff member? Staff with booking history will be protected.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700 transition hover:bg-red-100">
                                            <i class="fas fa-trash"></i>
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-[4.5rem] w-[4.5rem] items-center justify-center rounded-[1.75rem] bg-slate-100 text-3xl text-slate-400">
                                    <i class="fas fa-user-group"></i>
                                </div>
                                <h4 class="mt-5 text-xl font-black text-slate-900">No staff members have been added yet</h4>
                                <p class="mx-auto mt-2 max-w-xl text-sm leading-7 text-slate-500">
                                    Add your first staff account to begin assignment, attendance tracking, and operational scheduling.
                                </p>
                                <a href="{{ route('admin.staff.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                                    <i class="fas fa-plus"></i>
                                    Add Staff Member
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-5 py-4">
            {{ $staff->links('pagination::tailwind') }}
        </div>
    </section>
</div>
@endsection
