<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileProfileController extends Controller
{
    /**
     * Update only the contact fields exposed by the mobile app.
     * Address, identity, and password changes remain in the authenticated web portal.
     */
    public function update(Request $request): JsonResponse
    {
        $minimumBirthDate = now(config('cleanflow.attendance_timezone', config('app.timezone')))
            ->subYears(18)
            ->toDateString();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'last_name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
            'date_of_birth' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'before_or_equal:'.$minimumBirthDate],
            'gender' => ['sometimes', 'nullable', 'string', 'max:30'],
            'street' => ['sometimes', 'nullable', 'string', 'max:255'],
            'barangay' => ['sometimes', 'nullable', Rule::in(array_keys(config('cleanflow.barangays', [])))],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'zip_code' => ['sometimes', 'nullable', 'string', 'max:20'],
        ], [
            'phone.regex' => 'Phone number must start with 09 and contain exactly 11 digits.',
            'date_of_birth.date_format' => 'Date of birth must use YYYY-MM-DD format.',
            'date_of_birth.before_or_equal' => 'Clients must be at least 18 years old to book a cleaning service.',
        ]);

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'date_of_birth' => $user->date_of_birth?->toDateString(),
                'gender' => $user->gender,
                'street' => $user->street,
                'barangay' => $user->barangay,
                'city' => $user->city,
                'zip_code' => $user->zip_code,
                'email_verified' => $user->hasVerifiedEmail(),
            ],
        ]);
    }
}
