@extends('layouts.app')

@section('title', 'Cleaner Verification')

@section('content')
<main class="min-h-screen bg-slate-50 px-4 py-10 sm:px-6">
    <div class="mx-auto max-w-3xl">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-xl text-white"><i class="fas fa-user-shield"></i></div>
                <div>
                    <div class="text-xs font-black uppercase tracking-[0.16em] text-blue-700">CleanFlow team verification</div>
                    <h1 class="mt-2 text-2xl font-black text-slate-950 sm:text-3xl">Verify your cleaner profile</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-600">You are joining <strong>{{ $member->cleanerApplication->business_name }}</strong>. This private link is only for your verification and does not create a CleanFlow dashboard account.</p>
                </div>
            </div>

            @if($errors->any())
                <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
                    <div class="font-black">Please fix the following:</div>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('cleaner-team-members.verify.store', ['token' => $token]) }}" method="POST" enctype="multipart/form-data" class="mt-8 space-y-7">
                @csrf
                <section>
                    <h2 class="text-lg font-black text-slate-950">Your information</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="full_name" class="block text-sm font-bold text-slate-800">Full legal name *</label>
                            <input id="full_name" name="full_name" value="{{ old('full_name', $member->full_name) }}" required maxlength="150" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-bold text-slate-800">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $member->email) }}" maxlength="150" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-bold text-slate-800">Mobile number</label>
                            <input id="phone" name="phone" value="{{ old('phone', $member->phone) }}" placeholder="09XXXXXXXXX" maxlength="11" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div>
                            <label for="date_of_birth" class="block text-sm font-bold text-slate-800">Date of birth *</label>
                            <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $member->date_of_birth?->format('Y-m-d')) }}" required class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="current_address" class="block text-sm font-bold text-slate-800">Current address *</label>
                            <input id="current_address" name="current_address" value="{{ old('current_address', $member->current_address) }}" required maxlength="255" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Enter either an email address or mobile number so the team contact can reach you.</p>
                </section>

                <section class="border-t border-slate-100 pt-7">
                    <h2 class="text-lg font-black text-slate-950">Identity and clearance</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">CleanFlow reviews these documents privately. Your team contact sees your verification status, not the uploaded files.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="government_id_type" class="block text-sm font-bold text-slate-800">Government ID type *</label>
                            <select id="government_id_type" name="government_id_type" required class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                <option value="">Select ID type</option>
                                @foreach($governmentIdTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('government_id_type', $member->government_id_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="government_id_number" class="block text-sm font-bold text-slate-800">Government ID number *</label>
                            <input id="government_id_number" name="government_id_number" value="{{ old('government_id_number') }}" required maxlength="100" pattern="[A-Za-z0-9][A-Za-z0-9 -]{0,99}" autocomplete="off" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div>
                            <label for="government_id_front_document" class="block text-sm font-bold text-slate-800">Government ID front *</label>
                            <input id="government_id_front_document" type="file" name="government_id_front_document" accept=".jpg,.jpeg,.png,.pdf" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-blue-700">
                        </div>
                        <div>
                            <label for="government_id_back_document" class="block text-sm font-bold text-slate-800">Government ID back *</label>
                            <input id="government_id_back_document" type="file" name="government_id_back_document" accept=".jpg,.jpeg,.png,.pdf" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2.5 file:text-xs file:font-bold file:text-blue-700">
                        </div>
                        <div>
                            <label for="nbi_clearance_number" class="block text-sm font-bold text-slate-800">NBI / Police clearance number *</label>
                            <input id="nbi_clearance_number" name="nbi_clearance_number" value="{{ old('nbi_clearance_number') }}" required maxlength="100" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div>
                            <label for="nbi_clearance_document" class="block text-sm font-bold text-slate-800">NBI / Police clearance *</label>
                            <input id="nbi_clearance_document" type="file" name="nbi_clearance_document" accept=".jpg,.jpeg,.png,.pdf" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-blue-700">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="selfie_with_id" class="block text-sm font-bold text-slate-800">Selfie holding your ID *</label>
                            <input id="selfie_with_id" type="file" name="selfie_with_id" accept="image/jpeg,image/png" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-blue-700">
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">JPG, PNG, or PDF. Maximum 5 MB per file.</p>
                </section>

                <section class="border-t border-slate-100 pt-7">
                    <label for="verification_notes" class="block text-sm font-bold text-slate-800">Additional note (optional)</label>
                    <textarea id="verification_notes" name="verification_notes" rows="3" maxlength="2000" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('verification_notes') }}</textarea>
                    <label class="mt-4 flex items-start gap-3 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-slate-700">
                        <input type="checkbox" name="consent" value="1" required class="mt-1 h-4 w-4 rounded text-blue-600">
                        <span>I confirm that this information and these documents belong to me, and I consent to CleanFlow reviewing them for identity and service-provider verification. *</span>
                    </label>
                </section>

                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white transition hover:bg-blue-700">
                    <i class="fas fa-paper-plane"></i>
                    Submit verification
                </button>
            </form>
        </section>
    </div>
</main>
@endsection
