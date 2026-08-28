<?php

namespace App\Http\Controllers;

use App\Jobs\SendCleanerApplicationDecisionEmail;
use App\Models\CleanerApplication;
use App\Models\CleanerApplicationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminCleanerApplicationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', CleanerApplication::STATUS_PENDING);
        $allowedStatuses = [
            CleanerApplication::STATUS_PENDING,
            CleanerApplication::STATUS_APPROVED,
            CleanerApplication::STATUS_REJECTED,
            'all',
        ];

        abort_unless(in_array($status, $allowedStatuses, true), 404);

        $applicationsQuery = CleanerApplication::with(['reviewer', 'documents'])
            ->latest();

        if ($status !== 'all') {
            $applicationsQuery->where('status', $status);
        }

        $applications = $applicationsQuery->paginate(10)->withQueryString();

        $applicationStats = [
            'pending' => CleanerApplication::where('status', CleanerApplication::STATUS_PENDING)->count(),
            'approved' => CleanerApplication::where('status', CleanerApplication::STATUS_APPROVED)->count(),
            'rejected' => CleanerApplication::where('status', CleanerApplication::STATUS_REJECTED)->count(),
            'all' => CleanerApplication::count(),
        ];

        return view('admin.cleaner-applications.index', compact(
            'applicationStats',
            'applications',
            'status',
        ));
    }

    public function update(Request $request, CleanerApplication $cleanerApplication)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                CleanerApplication::STATUS_APPROVED,
                CleanerApplication::STATUS_REJECTED,
            ])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $cleanerApplication->isPending()) {
            return redirect()
                ->route('admin.cleaner-applications.index', ['status' => $cleanerApplication->status])
                ->with('error', 'Only pending cleaner applications can be reviewed.');
        }

        $cleanerApplication->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $activationToken = $validated['status'] === CleanerApplication::STATUS_APPROVED
            ? $cleanerApplication->issueActivationToken()
            : null;

        SendCleanerApplicationDecisionEmail::dispatch($cleanerApplication->id, $activationToken);

        return redirect()
            ->route('admin.cleaner-applications.index')
            ->with('success', 'Cleaner application marked as '.$validated['status'].'. Applicant email notification queued.');
    }

    public function updatePayoutVerification(Request $request, CleanerApplication $cleanerApplication)
    {
        $validated = $request->validate([
            'payout_verification_status' => ['required', Rule::in(CleanerApplication::payoutVerificationStatuses())],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($cleanerApplication->status !== CleanerApplication::STATUS_APPROVED) {
            return back()->with('error', 'Only approved provider applications can be payout verified.');
        }

        if ($validated['payout_verification_status'] === CleanerApplication::PAYOUT_VERIFICATION_VERIFIED
            && (! $cleanerApplication->hasPayoutDetails() || ! $cleanerApplication->hasRequiredPayoutDocuments())) {
            return back()->withErrors([
                'payout_verification_status' => 'Complete payout details and required documents before verifying payout setup.',
            ]);
        }

        $cleanerApplication->update([
            'payout_verification_status' => $validated['payout_verification_status'],
            'admin_notes' => $validated['admin_notes'] ?? $cleanerApplication->admin_notes,
            'payout_verified_at' => $validated['payout_verification_status'] === CleanerApplication::PAYOUT_VERIFICATION_VERIFIED ? now() : null,
            'payout_verified_by' => $validated['payout_verification_status'] === CleanerApplication::PAYOUT_VERIFICATION_VERIFIED ? $request->user()->id : null,
        ]);

        return back()->with('success', 'Provider payout verification updated.');
    }

    public function downloadPayoutDocument(CleanerApplication $cleanerApplication, CleanerApplicationDocument $document)
    {
        abort_unless((int) $document->cleaner_application_id === (int) $cleanerApplication->id, 404);
        abort_unless(Storage::disk(config('filesystems.private_uploads_disk'))->exists($document->file_path), 404);

        return Storage::disk(config('filesystems.private_uploads_disk'))->download($document->file_path, $document->original_filename);
    }

    public function downloadApplicationFile(CleanerApplication $cleanerApplication, string $type)
    {
        $files = [
            'profile-photo' => ['profile_photo_path', 'profile_photo_original_filename'],
            'business-logo' => ['business_logo_path', 'business_logo_original_filename'],
            'government-id' => ['government_id_document_path', 'government_id_document_original_filename'],
            'clearance' => ['nbi_clearance_document_path', 'nbi_clearance_document_original_filename'],
            'selfie-with-id' => ['selfie_with_id_path', 'selfie_with_id_original_filename'],
        ];

        abort_unless(array_key_exists($type, $files), 404);

        [$pathField, $filenameField] = $files[$type];
        $path = $cleanerApplication->{$pathField};

        abort_unless(filled($path) && Storage::disk(config('filesystems.private_uploads_disk'))->exists($path), 404);

        return Storage::disk(config('filesystems.private_uploads_disk'))->download($path, $cleanerApplication->{$filenameField} ?: basename($path));
    }
}
