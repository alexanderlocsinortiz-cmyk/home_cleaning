@extends('layouts.admin')
@section('title','Edit Staff')
@section('page-title','Edit Staff Member')
@section('page-subtitle','Update staff account details, contact information, and service coverage')

@section('content')
<div class="space-y-6" style="font-family: 'DM Sans', sans-serif; max-width: 900px;">
    @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:14px;padding:14px 16px;">
            <div style="font-size:14px;font-weight:700;">Please review the staff form.</div>
            <div style="font-size:13px;line-height:1.6;margin-top:4px;">The staff profile could not be updated because one or more fields need attention.</div>
        </div>
    @endif

    <div style="background:white;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 1px 4px rgba(0,0,0,0.05);overflow:hidden;">
        <div style="padding:20px 22px;border-bottom:1px solid #f1f5f9;">
            <div style="font-size:18px;font-weight:800;color:#1e293b;">Staff Profile Details</div>
            <div style="font-size:13px;color:#64748b;margin-top:4px;">Update the staff member's contact details, username, and access information.</div>
        </div>
        <form action="{{ route('admin.staff.update', $staff) }}" method="POST" class="space-y-4" style="padding:20px 22px;">
            @method('PUT')
            @include('admin.staff._form')
        </form>
    </div>
</div>
@endsection
