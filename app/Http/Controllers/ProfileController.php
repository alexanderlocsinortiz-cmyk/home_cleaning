<?php

namespace App\Http\Controllers;

use App\Support\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $barangays = config('cleanflow.barangays');

        return view('profile.index', compact('user', 'barangays'));
    }

    public function show()
    {
        return $this->index();
    }

    public function edit()
    {
        $user = Auth::user();
        $barangays = config('cleanflow.barangays');

        return view('profile.edit', compact('user', 'barangays'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $barangays = array_keys(config('cleanflow.barangays'));
        $dateOfBirthRules = ['nullable', 'date_format:Y-m-d'];

        if ($user->role === 'client') {
            $dateOfBirthRules[] = 'before_or_equal:'.now(config('cleanflow.attendance_timezone', config('app.timezone')))
                ->subYears(18)
                ->toDateString();
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'regex:/^09[0-9]{9}$/'],
            'date_of_birth' => $dateOfBirthRules,
            'gender' => ['nullable', 'string', 'max:30'],
            'street' => ['required', 'string', 'max:255'],
            'barangay' => ['required', Rule::in($barangays)],
            'current_password' => ['nullable', 'string'],
            'new_password' => ['nullable', 'confirmed', StrongPassword::rule()],
        ], [
            'phone.regex' => 'Phone number must start with 09 and contain exactly 11 digits.',
            'date_of_birth.date_format' => 'Date of birth must use YYYY-MM-DD format.',
            'date_of_birth.before_or_equal' => 'Clients must be at least 18 years old to book a cleaning service.',
        ]);

        if (! empty($validated['new_password'])
            && (empty($validated['current_password']) || ! Hash::check($validated['current_password'], $user->password))) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $profileAttributes = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'street' => $validated['street'],
            'barangay' => $validated['barangay'],
        ];

        if (! empty($validated['new_password'])) {
            $profileAttributes['password'] = Hash::make($validated['new_password']);
        }

        $user->update($profileAttributes);

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
    }
}
