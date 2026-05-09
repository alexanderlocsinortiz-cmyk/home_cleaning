@extends('layouts.admin')
@section('title','Add Staff')
@section('page-title','Add Staff Member')
@section('page-subtitle','Create a new staff account for scheduling, attendance, and field operations')

@section('content')
<div class="admin-page-content cleanflow-page-shell max-w-5xl space-y-6 p-6">
    @if($errors->any())
        <div class="cleanflow-alert cleanflow-alert--error">
            <div class="text-sm font-bold">Please review the staff form.</div>
            <div class="mt-1 text-sm">The staff profile could not be created because one or more fields need attention.</div>
        </div>
    @endif

    <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
        <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <span class="cleanflow-kicker">
                    <i class="fas fa-user-plus"></i>
                    Staff Onboarding
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Create a field-ready staff profile.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                    Add the account details the operations team needs for staffing, attendance, scheduling, and cleaner assignments.
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
            <h3 class="text-lg font-extrabold text-slate-900">New Staff Profile</h3>
            <p class="mt-1 text-sm text-slate-500">Enter the staff member's account details and service coverage information.</p>
        </div>
        <form action="{{ route('admin.staff.store') }}" method="POST" class="space-y-6 px-6 py-6">
            @include('admin.staff._form')
        </form>
    </section>
</div>
@endsection
