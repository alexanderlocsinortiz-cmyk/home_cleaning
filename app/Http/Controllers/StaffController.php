<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::where('role', 'staff')
            ->with(['assignedBookings.rating'])
            ->orderByDesc('created_at')
            ->paginate(10);

        $staff->getCollection()->transform(function ($s) {
            $ratings = $s->assignedBookings->pluck('rating')->filter();
            $s->avg_rating = $ratings->count() > 0 ? round($ratings->avg('stars'), 1) : null;
            $s->total_ratings = $ratings->count();
            return $s;
        });

        $barangays = config('cleanflow.barangays');

        return view('admin.staff.index', compact('staff', 'barangays'));
    }

    public function create()
    {
        $staff = null;
        $barangays = config('cleanflow.barangays');
        $roles = ['staff'];

        return view('admin.staff.create', compact('staff', 'barangays', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'phone'      => 'required|string|max:20',
            'username'   => 'required|string|unique:users,username',
            'password'   => 'required|string|min:6',
            'barangay'   => 'required|string',
        ]);

        User::create([
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'email'         => $request->email,
            'phone'         => $request->phone,
            'username'      => $request->username,
            'password'      => bcrypt($request->password),
            'role'          => 'staff',
            'barangay'      => $request->barangay,
            'street'        => '123 Default St.',
            'city'          => 'Valencia City',
            'zip_code'      => '8709',
            'gender'        => 'prefer_not_to_say',
            'date_of_birth' => '1990-01-01',
        ]);

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member added successfully.');
    }

    public function edit(User $staff)
    {
        abort_if($staff->role !== 'staff', 404);

        $barangays = config('cleanflow.barangays');
        $roles = ['staff'];

        return view('admin.staff.edit', compact('staff', 'barangays', 'roles'));
    }

    public function update(Request $request, User $staff)
    {
        abort_if($staff->role !== 'staff', 404);

        $barangays = array_keys(config('cleanflow.barangays'));

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:150', Rule::unique('users','email')->ignore($staff->id)],
            'phone'      => ['nullable', 'string', 'max:30'],
            'barangay'   => ['required', Rule::in($barangays)],
            'username'   => ['required', 'string', 'min:5', 'max:20', Rule::unique('users','username')->ignore($staff->id)],
            'password'   => ['nullable', 'string', 'min:8'],
        ]);

        $staff->update([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'phone'      => $validated['phone'],
            'barangay'   => $validated['barangay'],
            'username'   => $validated['username'],
            'role'       => 'staff',
            'password'   => $validated['password'] ?? $staff->password,
        ]);

        return redirect()->route('admin.staff.index')->with('success', 'Staff member updated successfully.');
    }

    public function destroy(User $staff)
    {
        abort_if($staff->role !== 'staff', 404);

        $staff->delete();

        return redirect()->route('admin.staff.index')->with('success', 'Staff member removed successfully.');
    }
}
