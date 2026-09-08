<?php

namespace App\Http\Controllers;

use App\Models\CleanerApplication;
use App\Models\User;
use App\Support\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProviderActivationController extends Controller
{
    public function show(string $token)
    {
        $application = $this->applicationForToken($token);

        if (! $application) {
            return view('provider.activation-invalid');
        }

        return view('provider.activate', compact('application', 'token'));
    }

    public function store(Request $request, string $token)
    {
        $application = $this->applicationForToken($token);

        if (! $application) {
            return redirect()
                ->route('provider.activate.invalid')
                ->withErrors(['token' => 'This activation link is invalid, expired, or already used.']);
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', StrongPassword::rule()],
            'username' => [
                'nullable',
                'string',
                'min:5',
                'max:20',
                Rule::unique('users', 'username'),
            ],
        ]);

        if (User::where('email', $application->email)->exists()) {
            return back()->withErrors([
                'email' => 'A user account already exists for this email. Contact CleanFlow admin to link the provider account.',
            ]);
        }

        $nameParts = preg_split('/\s+/', trim($application->contact_person), 2);
        $firstName = $nameParts[0] ?: $application->business_name;
        $lastName = $nameParts[1] ?? 'Provider';

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $application->email,
            'phone' => $application->phone,
            'username' => $validated['username'] ?? null,
            'role' => 'provider',
            'password' => Hash::make($validated['password']),
            'city' => 'Valencia City',
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $application->forceFill([
            'user_id' => $user->id,
            'activation_token_hash' => null,
            'activation_token_expires_at' => null,
            'activated_at' => now(),
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('provider.dashboard')
            ->with('success', 'Provider account activated successfully.');
    }

    public function invalid()
    {
        return view('provider.activation-invalid');
    }

    private function applicationForToken(string $token): ?CleanerApplication
    {
        return CleanerApplication::where('activation_token_hash', CleanerApplication::activationTokenHash($token))
            ->where('status', CleanerApplication::STATUS_APPROVED)
            ->whereNull('activated_at')
            ->where('activation_token_expires_at', '>', now())
            ->first();
    }
}
