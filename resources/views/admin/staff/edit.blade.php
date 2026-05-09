@extends('layouts.admin')
@section('title','Edit Staff')
@section('page-title','Edit Staff Member')
@section('page-subtitle','Update staff account details, contact information, and service coverage')

@section('content')
<div class="admin-page-content cleanflow-page-shell max-w-5xl space-y-6 p-6">
    @if($errors->any())
        <div class="cleanflow-alert cleanflow-alert--error">
            <div class="text-sm font-bold">Please review the staff form.</div>
            <div class="mt-1 text-sm">The staff profile could not be updated because one or more fields need attention.</div>
        </div>
    @endif

    <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
        <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <span class="cleanflow-kicker">
                    <i class="fas fa-user-pen"></i>
                    Staff Profile
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Update {{ $staff->full_name }} with confidence.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                    Refresh account details, contact information, and service coverage without leaving the operations workflow.
                </p>
            </div>
            <a href="{{ route('admin.staff.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50">
                <i class="fas fa-arrow-left"></i>
                Back to Staff
            </a>
        </div>
    </section>

    <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 px-6 py-5">
            <h3 class="text-lg font-extrabold text-slate-900">Staff Profile Details</h3>
            <p class="mt-1 text-sm text-slate-500">Update the staff member's contact details, username, and access information.</p>
        </div>
        <form action="{{ route('admin.staff.update', $staff) }}" method="POST" class="space-y-6 px-6 py-6">
            @method('PUT')
            @include('admin.staff._form')
        </form>
    </section>
</div>
@endsection
