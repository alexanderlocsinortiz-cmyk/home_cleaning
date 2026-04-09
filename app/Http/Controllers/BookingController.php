<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\BookingSubmitted;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function index()
    {
        $user = $this->requireVerifiedClient();

        $bookings = Booking::where('user_id', $user->id)
            ->with(['staff', 'service'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('bookings.index', compact('bookings'));
    }

    public function create()
    {
        $this->requireVerifiedClient();

        $barangays = array_keys(config('cleanflow.barangays'));
        $services = Service::where('is_active', true)->get();

        return view('bookings.create', compact('barangays', 'services'));
    }

    public function store(Request $request)
    {
        $user = $this->requireVerifiedClient();

        $validSlugs = Service::where('is_active', true)->pluck('slug')->toArray();

        $request->validate([
            'service_type'   => ['required', Rule::in($validSlugs)],
            'property_type'  => 'required|in:house,apartment,boarding_house',
            'rooms'          => 'required|integer|min:1|max:20',
            'bathrooms'      => 'required|integer|min:1|max:10',
            'barangay'       => 'required|string',
            'street_address' => 'required|string|max:255',
            'scheduled_date' => 'required|date|after:today',
            'scheduled_time' => 'required',
            'notes'          => 'nullable|string|max:500',
        ], [
            'scheduled_date.after'    => 'Please select a future date.',
            'service_type.required'   => 'Please select a service type.',
            'barangay.required'       => 'Please select your barangay.',
            'property_type.required'  => 'Please select your property type.',
            'rooms.required'          => 'Please enter number of rooms.',
            'bathrooms.required'      => 'Please enter number of bathrooms.',
        ]);

        $pricing = Booking::calculatePrice(
            $request->service_type,
            $request->property_type,
            $request->rooms,
            $request->bathrooms
        );

        $booking = Booking::create([
            'user_id'        => $user->id,
            'service_type'   => $request->service_type,
            'property_type'  => $request->property_type,
            'rooms'          => $request->rooms,
            'bathrooms'      => $request->bathrooms,
            'barangay'       => $request->barangay,
            'street_address' => $request->street_address,
            'scheduled_date' => $request->scheduled_date,
            'scheduled_time' => $request->scheduled_time,
            'notes'          => $request->notes,
            'price'          => $pricing['total'],
            'base_price'     => $pricing['base_price'],
            'property_fee'   => $pricing['property_fee'],
            'rooms_fee'      => $pricing['rooms_fee'],
            'bathrooms_fee'  => $pricing['bathrooms_fee'],
            'status'         => 'pending',
        ]);

        try {
            Mail::to($booking->user->email)->send(new BookingSubmitted($booking->load(['user', 'service'])));
        } catch (\Exception $e) {
            // silently ignore mail failures
        }

        return redirect()->route('bookings.index')->with('success', 'Your booking request has been submitted successfully. We will confirm your schedule shortly.');
    }

    public function calculatePrice(Request $request)
    {
        $this->requireVerifiedClient();

        $validSlugs = Service::where('is_active', true)->pluck('slug')->toArray();

        $request->validate([
            'service_type'  => ['required', Rule::in($validSlugs)],
            'property_type' => 'required|in:house,apartment,boarding_house',
            'rooms'         => 'required|integer|min:1|max:20',
            'bathrooms'     => 'required|integer|min:1|max:10',
        ]);

        $pricing = Booking::calculatePrice(
            $request->service_type,
            $request->property_type,
            $request->rooms,
            $request->bathrooms
        );

        return response()->json($pricing);
    }

    public function show($id)
    {
        $booking = Booking::with(['staff', 'user', 'rating', 'service'])
            ->findOrFail($id);

        $user = auth()->user();

        if ($user->role === 'client' && $booking->user_id !== $user->id) {
            abort(403);
        }

        if (! in_array($user->role, ['client', 'admin'], true)) {
            abort(403);
        }

        return view('bookings.show', compact('booking'));
    }

    public function rate(Request $request, $id)
    {
        $user = $this->requireVerifiedClient();
        $booking = Booking::with(['user', 'staff', 'rating'])->findOrFail($id);

        if ($user->id !== $booking->user_id) {
            abort(403);
        }

        if ($booking->status !== 'completed') {
            return back()->withErrors([
                'rating' => 'You can only leave feedback after the booking is completed.',
            ]);
        }

        if (! $booking->staff_id) {
            return back()->withErrors([
                'rating' => 'This booking cannot be rated until a staff member has been assigned.',
            ]);
        }

        if ($booking->rating) {
            return back()->withErrors([
                'rating' => 'You have already submitted feedback for this booking.',
            ]);
        }

        $request->validate([
            'stars'   => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
            'photo'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('ratings', 'public');
        }

        \App\Models\Rating::create([
            'booking_id' => $booking->id,
            'client_id'  => $user->id,
            'staff_id'   => $booking->staff_id,
            'stars'      => $request->stars,
            'comment'    => $request->comment,
            'photo'      => $photoPath,
        ]);

        return back()->with('success', 'Thank you for your feedback. Your rating has been submitted.');
    }

    public function cancel($id)
    {
        $user = $this->requireVerifiedClient();
        $booking = Booking::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        if ($booking->status !== 'pending') {
            return back()->with('error', 'Only pending bookings can be cancelled.');
        }

        if ($booking->staff_id) {
            return back()->with('error', 'This booking can no longer be cancelled because a staff member has already been assigned.');
        }

        $booking->update(['status' => 'cancelled']);

        return back()->with('success', 'Your booking has been cancelled successfully.');
    }

    private function requireVerifiedClient(): User
    {
        $user = auth()->user();

        abort_if(
            ! $user || $user->role !== 'client' || ! $user->hasVerifiedEmail(),
            403
        );

        return $user;
    }
}
