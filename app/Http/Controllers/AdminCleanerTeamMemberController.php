<?php

namespace App\Http\Controllers;

use App\Models\CleanerApplicationActivityLog;
use App\Models\CleanerTeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminCleanerTeamMemberController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', CleanerTeamMember::STATUS_PENDING);
        abort_unless(in_array($status, array_merge(CleanerTeamMember::statuses(), ['all']), true), 404);

        $search = trim((string) $request->query('search', ''));
        $members = CleanerTeamMember::with(['cleanerApplication', 'reviewer'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(function ($memberQuery) use ($like): void {
                    $memberQuery->where('full_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhereHas('cleanerApplication', function ($applicationQuery) use ($like): void {
                            $applicationQuery->where('business_name', 'like', $like)
                                ->orWhere('contact_person', 'like', $like);
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = collect(CleanerTeamMember::statuses())
            ->mapWithKeys(fn (string $memberStatus): array => [$memberStatus => CleanerTeamMember::where('status', $memberStatus)->count()])
            ->all();

        return view('admin.cleaner-team-members.index', compact('members', 'search', 'stats', 'status'));
    }

    public function update(Request $request, CleanerTeamMember $member)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                CleanerTeamMember::STATUS_APPROVED,
                CleanerTeamMember::STATUS_REJECTED,
                CleanerTeamMember::STATUS_PENDING,
                CleanerTeamMember::STATUS_SUSPENDED,
            ])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $application = $member->cleanerApplication;
        if (! $application || ! $application->isTeam()) {
            return back()->with('error', 'Only members belonging to a cleaning team can be reviewed here.');
        }

        if ($validated['status'] === CleanerTeamMember::STATUS_APPROVED && ! $member->verificationDocumentsComplete()) {
            return back()->withErrors([
                'status' => 'This cleaner cannot be approved until all ID, NBI clearance, and selfie documents are complete.',
            ]);
        }

        $oldStatus = $member->status;
        DB::transaction(function () use ($member, $validated, $request, $oldStatus): void {
            $member->forceFill([
                'status' => $validated['status'],
                'admin_notes' => $validated['admin_notes'] ?? null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();

            if ($validated['status'] !== CleanerTeamMember::STATUS_APPROVED && $oldStatus === CleanerTeamMember::STATUS_APPROVED) {
                $bookingIds = $member->bookings()
                    ->whereIn('bookings.status', ['pending', 'confirmed'])
                    ->pluck('bookings.id')
                    ->all();

                if ($bookingIds !== []) {
                    $member->bookings()->detach($bookingIds);
                }
            }

            CleanerApplicationActivityLog::create([
                'cleaner_application_id' => $member->cleaner_application_id,
                'actor_id' => $request->user()->id,
                'action' => 'team_member_status_changed',
                'description' => 'Admin changed team cleaner '.$member->full_name.' status from '.$oldStatus.' to '.$validated['status'].'.',
                'metadata' => [
                    'team_member_id' => $member->id,
                    'from' => $oldStatus,
                    'to' => $validated['status'],
                ],
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('success', $member->full_name.' is now '.($validated['status'] === CleanerTeamMember::STATUS_APPROVED ? 'approved and assignable.' : str_replace('_', ' ', $validated['status']).'.'));
    }

    public function download(CleanerTeamMember $member, string $type)
    {
        abort_unless(in_array($type, ['government-id-front', 'government-id-back', 'clearance', 'selfie-with-id'], true), 404);

        $path = $member->filePathFor($type);
        abort_unless(filled($path) && Storage::disk(config('filesystems.private_uploads_disk'))->exists($path), 404);

        CleanerApplicationActivityLog::create([
            'cleaner_application_id' => $member->cleaner_application_id,
            'actor_id' => request()->user()->id,
            'action' => 'team_member_verification_file_downloaded',
            'description' => 'Admin downloaded a verification file for team cleaner '.$member->full_name.'.',
            'metadata' => [
                'team_member_id' => $member->id,
                'file_type' => $type,
            ],
            'ip_address' => request()->ip(),
        ]);

        return Storage::disk(config('filesystems.private_uploads_disk'))->download(
            $path,
            $member->originalFilenameFor($type) ?: basename($path),
        );
    }
}
