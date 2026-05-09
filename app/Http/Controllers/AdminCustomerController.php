<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $filters = [
            'barangay' => (string) $request->get('barangay', ''),
            'verification' => (string) $request->get('verification', ''),
            'booking_activity' => (string) $request->get('booking_activity', ''),
            'registration_month' => (string) $request->get('registration_month', ''),
        ];

        $baseCustomerQuery = User::query()->where('role', 'client');

        $stats = [
            'total' => (clone $baseCustomerQuery)->count(),
            'verified' => (clone $baseCustomerQuery)->whereNotNull('email_verified_at')->count(),
            'with_bookings' => (clone $baseCustomerQuery)->whereHas('bookings')->count(),
            'new_this_month' => (clone $baseCustomerQuery)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfDay()])
                ->count(),
        ];

        $customersQuery = User::query()
            ->where('role', 'client')
            ->select([
                'id', 'first_name', 'last_name', 'email', 'phone', 'gender',
                'street', 'barangay', 'city', 'zip_code', 'username',
                'email_verified_at', 'created_at',
            ])
            ->addSelect([
                'latest_booking_id' => Booking::query()
                    ->select('id')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('scheduled_date')
                    ->orderByDesc('created_at')
                    ->limit(1),
                'latest_booking_date' => Booking::query()
                    ->select('scheduled_date')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('scheduled_date')
                    ->orderByDesc('created_at')
                    ->limit(1),
                'latest_booking_status' => Booking::query()
                    ->select('status')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('scheduled_date')
                    ->orderByDesc('created_at')
                    ->limit(1),
            ])
            ->with([
                'bookings' => function ($query) {
                    $query->select('id', 'user_id', 'status', 'scheduled_date', 'created_at')
                        ->latest('scheduled_date');
                },
            ])
            ->withCount('bookings')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('barangay', 'like', "%{$search}%");
                });
            })
            ->when($filters['barangay'] !== '', fn ($query) => $query->where('barangay', $filters['barangay']))
            ->when($filters['verification'] === 'verified', fn ($query) => $query->whereNotNull('email_verified_at'))
            ->when($filters['verification'] === 'pending', fn ($query) => $query->whereNull('email_verified_at'))
            ->when($filters['booking_activity'] === 'with_bookings', fn ($query) => $query->has('bookings'))
            ->when($filters['booking_activity'] === 'without_bookings', fn ($query) => $query->doesntHave('bookings'))
            ->when(
                preg_match('/^\d{4}-\d{2}$/', $filters['registration_month']) === 1,
                function ($query) use ($filters) {
                    $date = Carbon::createFromFormat('Y-m', $filters['registration_month']);
                    $query->whereBetween('created_at', [
                        $date->startOfMonth(),
                        $date->endOfMonth(),
                    ]);
                }
            );

        $filteredCount = (clone $customersQuery)->count();
        $customers = $customersQuery
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $barangays = config('cleanflow.barangays', []);
        $genderOptions = $this->customerGenderOptions();
        $registrationMonthOptions = (clone $baseCustomerQuery)
            ->orderByDesc('created_at')
            ->get(['created_at'])
            ->pluck('created_at')
            ->filter()
            ->map(function ($createdAt) {
                $date = $createdAt instanceof Carbon ? $createdAt : Carbon::parse($createdAt);

                return $date->copy()->startOfMonth();
            })
            ->unique(fn (Carbon $date) => $date->format('Y-m'))
            ->sortByDesc(fn (Carbon $date) => $date->timestamp)
            ->mapWithKeys(fn (Carbon $date) => [$date->format('Y-m') => $date->format('F Y')])
            ->all();

        return view('admin.customers', compact(
            'customers', 'search', 'filters', 'stats',
            'filteredCount', 'barangays', 'genderOptions', 'registrationMonthOptions',
        ));
    }

    public function editCustomerVerification(User $customer)
    {
        $customer = $this->ensureClientCustomer($customer);
        $latestBooking = $customer->bookings()
            ->with('service')
            ->orderByDesc('scheduled_date')
            ->orderByDesc('created_at')
            ->first();

        $customer->loadCount('bookings');

        return view('admin.customers-verification', [
            'customer' => $customer,
            'latestBooking' => $latestBooking,
        ]);
    }

    public function updateCustomerVerification(Request $request, User $customer)
    {
        $customer = $this->ensureClientCustomer($customer);
        $validated = $request->validate([
            'verification_status' => ['required', Rule::in(['verified', 'pending'])],
        ]);

        $wasVerified = $customer->email_verified_at !== null;
        $shouldVerify = $validated['verification_status'] === 'verified';

        if ($shouldVerify) {
            $customer->email_verified_at = $customer->email_verified_at ?: now();
        } else {
            $customer->email_verified_at = null;
        }

        $customer->save();

        $isVerified = $customer->email_verified_at !== null;
        $message = match (true) {
            $isVerified && ! $wasVerified => 'Customer marked as verified.',
            ! $isVerified && $wasVerified => 'Customer marked as pending verification.',
            $isVerified => 'Customer remains verified.',
            default => 'Customer remains pending verification.',
        };

        return redirect()
            ->route('admin.customers.verification.edit', $customer)
            ->with('success', $message);
    }

    public function destroy(User $customer)
    {
        $customer = $this->ensureClientCustomer($customer);

        if ($customer->bookings()->exists()) {
            return back()->with('error', 'Customer accounts with booking history are protected from deletion to preserve operational records.');
        }

        $customer->delete();

        return back()->with('success', 'Customer deleted successfully.');
    }

    private function ensureClientCustomer(User $customer): User
    {
        abort_if($customer->role !== 'client', 404);

        return $customer;
    }

    private function customerGenderOptions(): array
    {
        return [
            'male' => 'Male',
            'female' => 'Female',
            'prefer_not_to_say' => 'Prefer not to say',
        ];
    }
}
