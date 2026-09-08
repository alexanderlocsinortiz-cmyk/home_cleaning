<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index()
    {
        $staffQuery = User::where('role', 'staff');

        $staff = (clone $staffQuery)
            ->with(['assignedBookings.rating'])
            ->orderByDesc('created_at')
            ->paginate(10);

        $staff->getCollection()->transform(function ($s) {
            $ratings = $s->assignedBookings->pluck('rating')->filter();
            $s->avg_rating = $ratings->count() > 0 ? round($ratings->avg('stars'), 1) : null;
            $s->total_ratings = $ratings->count();

            return $s;
        });

        $staffStats = [
            'total' => (clone $staffQuery)->count(),
            'rated_on_page' => $staff->getCollection()
                ->filter(fn (User $member) => (int) $member->total_ratings > 0)
                ->count(),
        ];

        return view('admin.staff.index', compact('staff', 'staffStats'));
    }

    public function create()
    {
        $staff = null;
        $roles = ['staff'];

        return view('admin.staff.create', compact('staff', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
            'username' => 'required|string|unique:users,username',
            'password' => ['required', StrongPassword::rule()],
        ]);

        User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'username' => $request->username,
            'password' => bcrypt($request->password),
            'role' => 'staff',
            'street' => null,
            'city' => 'Valencia City',
            'zip_code' => null,
            'gender' => null,
            'date_of_birth' => null,
        ]);

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member added successfully.');
    }

    public function edit(User $staff)
    {
        abort_if($staff->role !== 'staff', 404);

        $roles = ['staff'];

        return view('admin.staff.edit', compact('staff', 'roles'));
    }

    public function update(Request $request, User $staff)
    {
        abort_if($staff->role !== 'staff', 404);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($staff->id)],
            'phone' => ['nullable', 'regex:/^09[0-9]{9}$/'],
            'username' => ['required', 'string', 'min:5', 'max:20', Rule::unique('users', 'username')->ignore($staff->id)],
            'password' => ['nullable', StrongPassword::rule()],
        ]);

        $staff->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'username' => $validated['username'],
            'role' => 'staff',
            'password' => $validated['password'] ?? $staff->password,
        ]);

        return redirect()->route('admin.staff.index')->with('success', 'Staff member updated successfully.');
    }

    public function destroy(User $staff)
    {
        abort_if($staff->role !== 'staff', 404);

        if ($staff->assignedBookings()->exists()) {
            return redirect()->route('admin.staff.index')
                ->with('error', 'Staff members with booking history are protected from deletion.');
        }

        $staff->delete();

        return redirect()->route('admin.staff.index')->with('success', 'Staff member removed successfully.');
    }
}
