<?php

namespace App\Http\Controllers;

use App\Models\CleanerApplication;
use App\Models\CleanerTeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProviderTeamMemberController extends Controller
{
    public function index()
    {
        $application = $this->teamApplication();
        $members = $application->teamMembers()->latest()->get();

        return view('provider.team-members', compact('application', 'members'));
    }

    public function store(Request $request)
    {
        $application = $this->teamApplication();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', 'required_without:phone'],
            'phone' => ['nullable', 'regex:/^09[0-9]{9}$/', 'required_without:email'],
        ], [
            'email.required_without' => 'Enter either the cleaner email or phone number.',
            'phone.required_without' => 'Enter either the cleaner email or phone number.',
            'phone.regex' => 'Phone number must start with 09 and contain exactly 11 digits.',
        ]);

        $member = $application->teamMembers()->create([
            'full_name' => trim($validated['full_name']),
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => CleanerTeamMember::STATUS_INVITED,
            'availability_status' => CleanerTeamMember::AVAILABILITY_AVAILABLE,
        ]);

        $token = $member->issueVerificationToken();

        return redirect()
            ->route('provider.team-members')
            ->with('success', 'Cleaner added. Send the private verification link to '.$member->full_name.'.')
            ->with('verification_url', route('cleaner-team-members.verify.show', ['token' => $token]))
            ->with('verification_member_name', $member->full_name);
    }

    public function resendVerification(CleanerTeamMember $member)
    {
        $application = $this->teamApplication();
        abort_unless((int) $member->cleaner_application_id === (int) $application->id, 404);

        if ($member->isApproved()) {
            return back()->with('error', 'Approved cleaners do not need another verification link.');
        }

        $member->forceFill([
            'status' => CleanerTeamMember::STATUS_INVITED,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ])->save();

        $token = $member->issueVerificationToken();

        return back()
            ->with('success', 'A new verification link was created for '.$member->full_name.'.')
            ->with('verification_url', route('cleaner-team-members.verify.show', ['token' => $token]))
            ->with('verification_member_name', $member->full_name);
    }

    public function updateAvailability(Request $request, CleanerTeamMember $member)
    {
        $application = $this->teamApplication();
        abort_unless((int) $member->cleaner_application_id === (int) $application->id, 404);

        $validated = $request->validate([
            'availability_status' => ['required', Rule::in([
                CleanerTeamMember::AVAILABILITY_AVAILABLE,
                CleanerTeamMember::AVAILABILITY_PAUSED,
            ])],
            'availability_notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $member->isApproved()) {
            return back()->with('error', 'Only approved cleaners can be made available for bookings.');
        }

        $member->update($validated);

        if ($validated['availability_status'] === CleanerTeamMember::AVAILABILITY_PAUSED) {
            DB::transaction(function () use ($member): void {
                $bookingIds = $member->bookings()
                    ->whereIn('bookings.status', ['pending', 'confirmed'])
                    ->pluck('bookings.id')
                    ->all();

                if ($bookingIds !== []) {
                    $member->bookings()->detach($bookingIds);
                }
            });
        }

        return back()->with('success', 'Cleaner availability updated.');
    }

    private function teamApplication(): CleanerApplication
    {
        $application = auth()->user()->cleanerApplication;

        abort_if(! $application || ! $application->isTeam(), 403);

        return $application;
    }
}
