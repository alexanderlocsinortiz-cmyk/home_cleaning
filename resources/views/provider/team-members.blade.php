@extends('layouts.provider')

@section('title', 'Team Cleaners')
@section('page-title', 'Team Cleaners')
@section('page-subtitle', 'Manage your roster and assign only approved cleaners')

@section('content')
<section class="min-h-screen bg-slate-50 px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-5">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
        @endif

        @if(session('verification_url'))
            <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white"><i class="fas fa-link"></i></div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-base font-black text-slate-950">Verification link for {{ session('verification_member_name') }}</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Send this private link directly to the cleaner. It expires in 7 days and lets them submit only their own verification documents.</p>
                        <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                            <input id="verification-link" readonly value="{{ session('verification_url') }}" class="min-w-0 flex-1 rounded-xl border border-blue-200 bg-white px-3 py-2 text-xs text-slate-600">
                            <button type="button" onclick="navigator.clipboard?.writeText(document.getElementById('verification-link').value); this.textContent = 'Copied';" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-xs font-black text-white hover:bg-blue-700"><i class="fas fa-copy"></i> Copy link</button>
                        </div>
                        <p class="mt-2 text-xs font-semibold text-blue-800">CleanFlow admin will see the uploaded files. You will see the cleaner’s status only.</p>
                    </div>
                </div>
            </section>
        @endif

        <section class="rounded-3xl bg-gradient-to-br from-blue-950 via-blue-900 to-blue-700 p-6 text-white shadow-xl shadow-blue-950/15 sm:p-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="text-xs font-black uppercase tracking-[0.16em] text-blue-200">{{ $application->business_name }}</div>
                    <h1 class="mt-2 text-3xl font-black tracking-tight">Your cleaner roster</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-blue-100">You control this team account. Add cleaners here, send each person their private verification link, and assign only approved cleaners to accepted bookings.</p>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center sm:min-w-[330px]">
                    @foreach([
                        ['label' => 'Total', 'value' => $members->count()],
                        ['label' => 'Approved', 'value' => $members->where('status', \App\Models\CleanerTeamMember::STATUS_APPROVED)->count()],
                        ['label' => 'Needs review', 'value' => $members->whereIn('status', [\App\Models\CleanerTeamMember::STATUS_INVITED, \App\Models\CleanerTeamMember::STATUS_PENDING])->count()],
                    ] as $stat)
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/10"><div class="text-2xl font-black">{{ $stat['value'] }}</div><div class="mt-1 text-[11px] font-bold text-blue-100">{{ $stat['label'] }}</div></div>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Add a cleaner</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Add their name and one contact method. The next screen gives you a secure link to send them.</p>
                <form action="{{ route('provider.team-members.store') }}" method="POST" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label for="full_name" class="block text-sm font-bold text-slate-800">Full legal name *</label>
                        <input id="full_name" name="full_name" value="{{ old('full_name') }}" required maxlength="150" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-bold text-slate-800">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="150" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-bold text-slate-800">Mobile number</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" placeholder="09XXXXXXXXX" maxlength="11" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <p class="text-xs text-slate-500">Enter either an email address or mobile number. CleanFlow does not automatically create a cleaner login.</p>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white hover:bg-blue-700"><i class="fas fa-user-plus"></i> Add cleaner and create link</button>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-black text-slate-950">Team members</h2>
                    <p class="mt-1 text-sm text-slate-500">Only Approved and available cleaners can be assigned to bookings.</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($members as $member)
                        <article class="p-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-black text-slate-950">{{ $member->full_name }}</h3>
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $member->statusBadgeClass() }}">{{ $member->statusLabel() }}</span>
                                    </div>
                                    <div class="mt-2 text-xs text-slate-500">{{ $member->email ?: 'No email' }} @if($member->phone)&bull; {{ $member->phone }}@endif</div>
                                    <div class="mt-2 text-xs font-semibold text-slate-500">
                                        @if($member->status === \App\Models\CleanerTeamMember::STATUS_INVITED)
                                            Waiting for cleaner to complete the private form.
                                        @elseif($member->status === \App\Models\CleanerTeamMember::STATUS_PENDING)
                                            Documents submitted and waiting for CleanFlow admin review.
                                        @elseif($member->status === \App\Models\CleanerTeamMember::STATUS_APPROVED)
                                            Eligible for booking assignment.
                                        @else
                                            Not eligible for booking assignment.
                                        @endif
                                    </div>
                                    @if($member->admin_notes)
                                        <div class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">Admin note: {{ $member->admin_notes }}</div>
                                    @endif
                                </div>
                                <div class="flex shrink-0 flex-col gap-2 sm:items-end">
                                    @if(! $member->isApproved())
                                        <form action="{{ route('provider.team-members.resend-verification', $member) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 hover:bg-blue-100"><i class="fas fa-rotate"></i> Create new link</button>
                                        </form>
                                    @endif
                                    @if($member->isApproved())
                                        <form action="{{ route('provider.team-members.availability.update', $member) }}" method="POST" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="availability_status" class="rounded-lg border border-slate-200 px-2 py-2 text-xs font-bold text-slate-700">
                                                <option value="available" {{ $member->availability_status === 'available' ? 'selected' : '' }}>Available</option>
                                                <option value="paused" {{ $member->availability_status === 'paused' ? 'selected' : '' }}>Paused</option>
                                            </select>
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-2 text-xs font-black text-white hover:bg-slate-700"><i class="fas fa-save"></i> Save</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="px-6 py-14 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-xl text-blue-600"><i class="fas fa-users"></i></div>
                            <h3 class="mt-4 font-black text-slate-950">No cleaners added yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Add your first cleaner to begin individual verification.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</section>
@endsection
