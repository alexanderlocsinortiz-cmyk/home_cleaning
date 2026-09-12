@extends('layouts.admin')

@section('title', 'Team Cleaner Verification')
@section('page-title', 'Team Cleaner Verification')
@section('page-subtitle', 'Review identity and clearance records submitted by team members')

@section('content')
<div class="admin-page-content space-y-5 p-6">
    @if(session('success'))<div class="cleanflow-alert cleanflow-alert--success text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="cleanflow-alert cleanflow-alert--error text-sm">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="cleanflow-alert cleanflow-alert--error text-sm">{{ $errors->first() }}</div>@endif

    <section class="rounded-3xl bg-gradient-to-br from-blue-950 via-blue-900 to-blue-700 p-6 text-white shadow-xl shadow-blue-950/15">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="text-xs font-black uppercase tracking-[0.16em] text-blue-200">Individual verification queue</div>
                <h1 class="mt-2 text-3xl font-black">Team cleaner records</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-blue-100">Approving a team business does not approve every worker. Review each cleaner separately before they can be assigned to a customer booking.</p>
            </div>
            <form method="GET" class="flex flex-col gap-2 sm:flex-row">
                <input name="search" value="{{ $search }}" placeholder="Search cleaner or team" class="rounded-xl border-0 px-3 py-2 text-sm text-slate-900">
                <select name="status" class="rounded-xl border-0 px-3 py-2 text-sm font-bold text-slate-900">
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending review</option>
                    <option value="invited" {{ $status === 'invited' ? 'selected' : '' }}>Invited</option>
                    <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All statuses</option>
                </select>
                <button class="rounded-xl bg-white px-4 py-2 text-sm font-black text-blue-800 hover:bg-blue-50">Filter</button>
            </form>
        </div>
    </section>

    <div class="grid gap-3 sm:grid-cols-5">
        @foreach([
            ['key' => 'pending', 'label' => 'Pending review', 'class' => 'border-amber-200 bg-amber-50 text-amber-800'],
            ['key' => 'approved', 'label' => 'Approved', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-800'],
            ['key' => 'rejected', 'label' => 'Rejected', 'class' => 'border-red-200 bg-red-50 text-red-800'],
            ['key' => 'suspended', 'label' => 'Suspended', 'class' => 'border-slate-200 bg-slate-50 text-slate-700'],
            ['key' => 'invited', 'label' => 'Invited', 'class' => 'border-blue-200 bg-blue-50 text-blue-800'],
        ] as $stat)
            <a href="{{ route('admin.cleaner-team-members.index', ['status' => $stat['key']]) }}" class="rounded-2xl border p-4 {{ $stat['class'] }}"><div class="text-xs font-black uppercase tracking-wide">{{ $stat['label'] }}</div><div class="mt-2 text-2xl font-black">{{ number_format($stats[$stat['key']] ?? 0) }}</div></a>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse($members as $member)
                @php($missing = $member->missingVerificationDocuments())
                <article class="p-5 sm:p-6">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-black text-slate-950">{{ $member->full_name }}</h2>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $member->statusBadgeClass() }}">{{ $member->statusLabel() }}</span>
                            </div>
                            <div class="mt-2 text-sm font-bold text-blue-700">{{ $member->cleanerApplication?->business_name }}</div>
                            <div class="mt-1 text-xs text-slate-500">Contact person: {{ $member->cleanerApplication?->contact_person }} &bull; {{ $member->email ?: 'No email' }} @if($member->phone)&bull; {{ $member->phone }}@endif</div>
                            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                @foreach([
                                    ['label' => 'ID front', 'type' => 'government-id-front', 'ready' => filled($member->government_id_front_document_path)],
                                    ['label' => 'ID back', 'type' => 'government-id-back', 'ready' => filled($member->government_id_back_document_path)],
                                    ['label' => 'NBI / Police clearance', 'type' => 'clearance', 'ready' => filled($member->nbi_clearance_document_path)],
                                    ['label' => 'Selfie with ID', 'type' => 'selfie-with-id', 'ready' => filled($member->selfie_with_id_path)],
                                ] as $document)
                                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2 text-xs">
                                        <span class="font-bold text-slate-600">{{ $document['label'] }}</span>
                                        @if($document['ready'])
                                            <a href="{{ route('admin.cleaner-team-members.documents.download', [$member, $document['type']]) }}" class="font-black text-blue-700 hover:text-blue-900"><i class="fas fa-download mr-1"></i>Download</a>
                                        @else
                                            <span class="font-bold text-red-600">Missing</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            @if($missing !== [])<div class="mt-3 text-xs font-bold text-amber-700">Missing: {{ implode(', ', $missing) }}</div>@endif
                        </div>
                        <form action="{{ route('admin.cleaner-team-members.update', $member) }}" method="POST" class="w-full rounded-2xl border border-slate-200 bg-slate-50 p-4 xl:max-w-sm">
                            @csrf
                            @method('PATCH')
                            <label for="status-{{ $member->id }}" class="block text-xs font-black uppercase tracking-wide text-slate-500">Review decision</label>
                            <select id="status-{{ $member->id }}" name="status" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold text-slate-700">
                                @foreach([
                                    \App\Models\CleanerTeamMember::STATUS_APPROVED => 'Approved - assignable',
                                    \App\Models\CleanerTeamMember::STATUS_REJECTED => 'Rejected - not assignable',
                                    \App\Models\CleanerTeamMember::STATUS_PENDING => 'Pending review',
                                    \App\Models\CleanerTeamMember::STATUS_SUSPENDED => 'Suspended - not assignable',
                                ] as $memberStatus => $memberStatusLabel)
                                    <option value="{{ $memberStatus }}" {{ $member->status === $memberStatus ? 'selected' : '' }}>{{ $memberStatusLabel }}</option>
                                @endforeach
                            </select>
                            <textarea name="admin_notes" rows="3" maxlength="2000" placeholder="Reason or review note" class="mt-3 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">{{ $member->admin_notes }}</textarea>
                            <button type="submit" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white hover:bg-blue-700"><i class="fas fa-save"></i> Save review</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="px-6 py-16 text-center text-sm font-semibold text-slate-500">No team cleaner records match this filter.</div>
            @endforelse
        </div>
        @if($members->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $members->links() }}</div>@endif
    </section>
</div>
@endsection
