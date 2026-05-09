<?php

namespace App\Http\Controllers;

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

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:30'],
            'street' => ['required', 'string', 'max:255'],
            'barangay' => ['required', Rule::in($barangays)],
            'zip_code' => ['required', 'string', 'max:10'],
            'current_password' => ['nullable', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'street' => $validated['street'],
            'barangay' => $validated['barangay'],
            'zip_code' => $validated['zip_code'],
        ]);

        if (! empty($validated['new_password'])) {
            if (empty($validated['current_password']) || ! Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            $user->update(['password' => Hash::make($validated['new_password'])]);
        }

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
    }
}
