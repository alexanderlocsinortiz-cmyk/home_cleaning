@extends('layouts.app')
@section('title','Access Denied')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="bg-white rounded-xl shadow-lg p-10 max-w-lg text-center">
        <div class="text-red-500 text-5xl mb-4"><i class="fas fa-ban"></i></div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Access Denied</h1>
        <p class="text-gray-600 mb-6">You don't have permission to view this page.</p>
        <a href="{{ route('home') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 rounded-lg font-semibold inline-flex items-center gap-2">
            <i class="fas fa-home"></i> Go Home
        </a>
    </div>
</div>
@endsection

