<?php

namespace App\Http\Controllers;

use App\Models\CleanerApplication;
use App\Models\CleanerApplicationActivityLog;
use App\Models\CleanerTeamMember;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminProviderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', CleanerApplication::STATUS_APPROVED);
        $allowedStatuses = [
            CleanerApplication::STATUS_PENDING,
            CleanerApplication::STATUS_NEEDS_CHANGES,
            CleanerApplication::STATUS_APPROVED,
            CleanerApplication::STATUS_REJECTED,
            'all',
        ];
        $type = $request->query('type', 'all');
        $allowedTypes = ['all', CleanerApplication::TYPE_INDIVIDUAL, CleanerApplication::TYPE_TEAM];

        abort_unless(in_array($status, $allowedStatuses, true), 404);
        abort_unless(in_array($type, $allowedTypes, true), 404);

        $search = trim((string) $request->query('search', ''));

        $providersQuery = CleanerApplication::query()
            ->with(['documents', 'user'])
            ->withCount('bookings')
            ->withCount('teamMembers')
            ->withCount(['teamMembers as approved_team_members_count' => function ($query): void {
                $query->where('status', CleanerTeamMember::STATUS_APPROVED);
            }])
            ->withCount(['bookings as active_bookings_count' => function ($query): void {
                $query->whereIn('status', ['pending', 'confirmed', 'in_progress']);
            }])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($type !== 'all', fn ($query) => $query->where('applicant_type', $type))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(function ($searchQuery) use ($like): void {
                    $searchQuery->where('business_name', 'like', $like)
                        ->orWhere('contact_person', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('service_area', 'like', $like);
                });
            })
            ->orderBy('business_name');

        $providerMapPoints = (clone $providersQuery)
            ->whereNotNull('location_latitude')
            ->whereNotNull('location_longitude')
            ->get([
                'id',
                'business_name',
                'contact_person',
                'location_area',
                'location_latitude',
                'location_longitude',
                'availability_status',
                'status',
            ])
            ->map(fn (CleanerApplication $provider): array => [
                'id' => $provider->id,
                'name' => $provider->business_name,
                'contact' => $provider->contact_person,
                'area' => $provider->location_area,
                'lat' => (float) $provider->location_latitude,
                'lng' => (float) $provider->location_longitude,
                'availability' => $provider->availabilityLabel(),
                'status' => $provider->status,
            ])
            ->values()
            ->all();

        $providers = $providersQuery->paginate(12)->withQueryString();

        $providerStats = [
            'approved' => CleanerApplication::where('status', CleanerApplication::STATUS_APPROVED)->count(),
            'individual' => CleanerApplication::where('status', CleanerApplication::STATUS_APPROVED)
                ->where('applicant_type', CleanerApplication::TYPE_INDIVIDUAL)
                ->count(),
            'team' => CleanerApplication::where('status', CleanerApplication::STATUS_APPROVED)
                ->where('applicant_type', CleanerApplication::TYPE_TEAM)
                ->count(),
            'pending' => CleanerApplication::where('status', CleanerApplication::STATUS_PENDING)->count(),
            'needs_changes' => CleanerApplication::where('status', CleanerApplication::STATUS_NEEDS_CHANGES)->count(),
            'rejected' => CleanerApplication::where('status', CleanerApplication::STATUS_REJECTED)->count(),
        ];

        return view('admin.providers.index', compact(
            'providerStats',
            'providerMapPoints',
            'providers',
            'search',
            'status',
            'type',
        ));
    }

    public function updateAvailability(Request $request, CleanerApplication $cleanerApplication)
    {
        $validated = $request->validate([
            'availability_status' => ['required', Rule::in(CleanerApplication::availabilityStatuses())],
            'availability_notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($cleanerApplication->status !== CleanerApplication::STATUS_APPROVED) {
            return back()->with('error', 'Only approved providers can have their availability changed.');
        }

        $oldStatus = $cleanerApplication->availability_status ?: CleanerApplication::AVAILABILITY_AVAILABLE;

        $cleanerApplication->update([
            'availability_status' => $validated['availability_status'],
            'availability_notes' => $validated['availability_notes'] ?? null,
        ]);

        CleanerApplicationActivityLog::create([
            'cleaner_application_id' => $cleanerApplication->id,
            'actor_id' => $request->user()->id,
            'action' => 'availability_changed',
            'description' => 'Admin changed provider availability from '.str_replace('_', ' ', $oldStatus).' to '.str_replace('_', ' ', $validated['availability_status']).'.',
            'metadata' => [
                'from' => $oldStatus,
                'to' => $validated['availability_status'],
            ],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Provider availability updated.');
    }
}
