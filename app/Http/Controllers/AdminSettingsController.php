<?php

namespace App\Http\Controllers;

use App\Models\AccessRestrictionHistory;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class AdminSettingsController extends Controller
{
    public const STAFF_PAGES = [
        'dashboard' => 'Dashboard',
        'bookings' => 'Bookings',
        'schedule' => 'Schedule',
        'performance' => 'Performance',
        'notifications' => 'Notifications and fingerprint consent',
        'profile' => 'Profile',
        'service_areas' => 'Service Areas',
    ];

    public function index()
    {
        $staff = User::where('role', 'staff')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $clients = User::where('role', 'client')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $restrictionHistories = AccessRestrictionHistory::with(['actorUser', 'targetUser'])
            ->latest()
            ->limit(30)
            ->get();

        return view('admin.settings', [
            'staff' => $staff,
            'clients' => $clients,
            'staffPages' => self::STAFF_PAGES,
            'restrictionHistories' => $restrictionHistories,
            'generalSettings' => SiteSetting::current(),
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $settings = SiteSetting::current();

        $validated = $request->validate([
            'website_name' => ['required', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_address' => ['nullable', 'string', 'max:180'],
            'office_hours' => ['nullable', 'string', 'max:120'],
            'admin_name' => ['nullable', 'string', 'max:120'],
            'admin_email' => ['nullable', 'email', 'max:120'],
            'admin_phone' => ['nullable', 'string', 'max:30'],
            'admin_current_password' => ['nullable', 'string'],
            'admin_new_password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        if (filled($validated['admin_new_password'] ?? null)) {
            if (blank($validated['admin_current_password'] ?? null) || ! Hash::check($validated['admin_current_password'], $request->user()->password)) {
                return back()
                    ->withErrors(['admin_current_password' => 'Current password is incorrect.'])
                    ->withInput($request->except(['admin_current_password', 'admin_new_password', 'admin_new_password_confirmation', 'logo']));
            }

            $request->user()->update([
                'password' => $validated['admin_new_password'],
            ]);
        }

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('site', 'public');
        }

        unset(
            $validated['logo'],
            $validated['admin_current_password'],
            $validated['admin_new_password'],
            $validated['admin_new_password_confirmation'],
        );

        $settings->update($validated);

        return back()->with('success', filled($request->input('admin_new_password')) ? 'General settings and admin password updated.' : 'General settings updated.');
    }

    public function updateUserAccess(Request $request, User $user)
    {
        abort_if($user->role === 'admin', 403);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['restrict', 'clear'])],
            'restriction_days' => ['required_if:action,restrict', 'nullable', 'integer', 'min:1', 'max:365'],
            'access_restriction_reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['action'] === 'clear') {
            $previousUntil = $user->access_restricted_until;
            $previousReason = $user->access_restriction_reason;

            $user->update([
                'access_restricted_until' => null,
                'access_restriction_reason' => null,
            ]);

            $this->recordAccessHistory($request, $user, 'account_restriction_cleared', [
                'restricted_until' => $previousUntil,
                'reason' => $previousReason,
            ]);

            return back()->with('success', $user->display_name.' access restriction cleared.');
        }

        $days = (int) $validated['restriction_days'];
        $restrictedUntil = now()->addDays($days);
        $reason = $validated['access_restriction_reason'] ?: 'Restricted by admin.';

        $user->update([
            'access_restricted_until' => $restrictedUntil,
            'access_restriction_reason' => $reason,
        ]);

        $this->recordAccessHistory($request, $user, 'account_restricted', [
            'duration_days' => $days,
            'restricted_until' => $restrictedUntil,
            'reason' => $reason,
        ]);

        return back()->with('success', $user->display_name.' restricted for '.$days.' day'.($days === 1 ? '' : 's').'.');
    }

    public function updateStaffPages(Request $request, User $user)
    {
        abort_unless($user->role === 'staff', 404);

        $validated = $request->validate([
            'restricted_pages' => ['nullable', 'array'],
            'restricted_pages.*' => ['string', Rule::in(array_keys(self::STAFF_PAGES))],
        ]);

        $previousPages = collect($user->staff_restricted_pages ?? [])->sort()->values()->all();
        $restrictedPages = collect($validated['restricted_pages'] ?? [])->sort()->values()->all();

        $user->update([
            'staff_restricted_pages' => $restrictedPages,
        ]);

        if ($previousPages !== $restrictedPages) {
            $this->recordAccessHistory($request, $user, 'staff_pages_updated', [
                'restricted_pages' => $restrictedPages,
                'meta' => [
                    'previous_pages' => $previousPages,
                ],
            ]);
        }

        return back()->with('success', $user->display_name.' staff page access updated.');
    }

    private function recordAccessHistory(Request $request, User $target, string $action, array $data = []): void
    {
        AccessRestrictionHistory::create([
            'target_user_id' => $target->id,
            'actor_user_id' => $request->user()?->id,
            'target_name' => $target->display_name,
            'target_email' => $target->email,
            'target_role' => $target->role,
            'action' => $action,
            'duration_days' => $data['duration_days'] ?? null,
            'restricted_until' => $data['restricted_until'] ?? null,
            'reason' => $data['reason'] ?? null,
            'restricted_pages' => $data['restricted_pages'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);
    }
}
