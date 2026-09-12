<?php

namespace App\Http\Controllers;

use App\Models\CleanerApplication;
use App\Models\CleanerTeamMember;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class CleanerTeamMemberVerificationController extends Controller
{
    public function show(string $token)
    {
        $member = $this->memberForToken($token);

        if (! $member) {
            return view('cleaner-team-members.verification-invalid');
        }

        if ($member->isApproved()) {
            return view('cleaner-team-members.verification-complete', compact('member'));
        }

        $governmentIdTypes = CleanerApplication::GOVERNMENT_ID_LABELS;

        return view('cleaner-team-members.verify', compact('member', 'governmentIdTypes', 'token'));
    }

    public function store(Request $request, string $token)
    {
        $member = $this->memberForToken($token);

        if (! $member) {
            return redirect()
                ->route('cleaner-team-members.verify.invalid')
                ->withErrors(['token' => 'This verification link is invalid, expired, or already used.']);
        }

        if ($member->isApproved()) {
            return redirect()
                ->route('cleaner-team-members.verify.show', ['token' => $token])
                ->with('success', 'This cleaner has already been approved.');
        }

        $minimumBirthDate = now(config('cleanflow.attendance_timezone', config('app.timezone')))
            ->subYears(18)
            ->toDateString();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', 'required_without:phone'],
            'phone' => ['nullable', 'regex:/^09[0-9]{9}$/', 'required_without:email'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$minimumBirthDate],
            'current_address' => ['required', 'string', 'max:255'],
            'government_id_type' => ['required', Rule::in(array_keys(CleanerApplication::GOVERNMENT_ID_LABELS))],
            'government_id_number' => ['required', 'regex:/^[A-Za-z0-9][A-Za-z0-9 -]{0,99}$/'],
            'government_id_front_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'government_id_back_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'nbi_clearance_number' => ['required', 'string', 'max:100'],
            'nbi_clearance_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'selfie_with_id' => ['required', 'image', 'max:5120'],
            'consent' => ['accepted'],
            'verification_notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'email.required_without' => 'Enter either your email or phone number.',
            'phone.required_without' => 'Enter either your email or phone number.',
            'phone.regex' => 'Phone number must start with 09 and contain exactly 11 digits.',
            'date_of_birth.before_or_equal' => 'Cleaners must be at least 18 years old to submit verification.',
            'government_id_number.regex' => 'Government ID number may contain letters, numbers, spaces, and hyphens only.',
            'government_id_front_document.required' => 'The front of your government ID is required.',
            'government_id_back_document.required' => 'The back of your government ID is required.',
            'nbi_clearance_document.required' => 'Your NBI or police clearance document is required.',
            'consent.accepted' => 'You must consent to identity and clearance verification.',
        ]);

        $disk = Storage::disk(config('filesystems.private_uploads_disk'));
        $storedPaths = [];
        $oldPaths = collect([
            $member->government_id_front_document_path,
            $member->government_id_back_document_path,
            $member->nbi_clearance_document_path,
            $member->selfie_with_id_path,
        ])->filter()->values()->all();

        try {
            $fileAttributes = [];
            foreach ([
                'government_id_front_document',
                'government_id_back_document',
                'nbi_clearance_document',
                'selfie_with_id',
            ] as $field) {
                $file = $request->file($field);
                $path = $this->storeMemberFile($member, $field, $file, $storedPaths);
                $fileAttributes[$field.'_path'] = $path;
                $fileAttributes[$field.'_original_filename'] = $file->getClientOriginalName();
            }

            DB::transaction(function () use ($member, $validated, $fileAttributes): void {
                $member->forceFill([
                    'full_name' => trim($validated['full_name']),
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'date_of_birth' => $validated['date_of_birth'],
                    'current_address' => $validated['current_address'],
                    'government_id_type' => $validated['government_id_type'],
                    'government_id_number' => $validated['government_id_number'],
                    'nbi_clearance_number' => $validated['nbi_clearance_number'],
                    'verification_notes' => $validated['verification_notes'] ?? null,
                    'status' => CleanerTeamMember::STATUS_PENDING,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'admin_notes' => null,
                    'consent_at' => now(),
                    'submitted_at' => now(),
                    'verification_token_hash' => null,
                    'verification_token_expires_at' => null,
                ] + $fileAttributes)->save();
            });

            if ($oldPaths !== []) {
                $disk->delete(array_values(array_diff($oldPaths, $storedPaths)));
            }
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                $disk->delete($storedPaths);
            }

            report($exception);

            return back()
                ->withErrors(['verification' => 'We could not securely save your verification. Please try again.'])
                ->withInput();
        }

        return view('cleaner-team-members.submitted', compact('member'));
    }

    private function memberForToken(string $token): ?CleanerTeamMember
    {
        return CleanerTeamMember::query()
            ->where('verification_token_hash', CleanerTeamMember::verificationTokenHash($token))
            ->where('verification_token_expires_at', '>', now())
            ->whereHas('cleanerApplication', function ($query): void {
                $query->where('applicant_type', CleanerApplication::TYPE_TEAM)
                    ->where('status', CleanerApplication::STATUS_APPROVED);
            })
            ->with('cleanerApplication')
            ->first();
    }

    private function storeMemberFile(CleanerTeamMember $member, string $field, UploadedFile $file, array &$storedPaths): string
    {
        $path = $file->store('cleaner-team-members/'.$member->id, config('filesystems.private_uploads_disk'));

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Unable to store team member verification file.');
        }

        $storedPaths[] = $path;

        return $path;
    }
}
