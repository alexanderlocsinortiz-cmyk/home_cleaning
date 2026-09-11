<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_cancel_pending_booking_without_staff(): void
    {
        $client = $this->createVerifiedClient('client-cancel@example.com', 'clientcancel');
        $booking = $this->createBooking($client, null, 'pending');

        $response = $this->actingAs($client)
            ->from(route('bookings.index'))
            ->patch(route('bookings.cancel', $booking->id));

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('success');

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_client_cannot_cancel_booking_with_assigned_staff(): void
    {
        $client = $this->createVerifiedClient('client-cancel-staff@example.com', 'clientcancelstaff');
        $staff = $this->createStaff('staff-cancel@example.com', 'staffcancel');
        $booking = $this->createBooking($client, $staff, 'pending');

        $response = $this->actingAs($client)
            ->from(route('bookings.index'))
            ->patch(route('bookings.cancel', $booking->id));

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('error');

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_client_cannot_cancel_non_pending_booking(): void
    {
        $client = $this->createVerifiedClient('client-cancel-confirmed@example.com', 'clientcancelconfirmed');
        $staff = $this->createStaff('staff-cancel-conf@example.com', 'staffcancelconfirmed');
        $booking = $this->createBooking($client, $staff, 'confirmed');

        $response = $this->actingAs($client)
            ->from(route('bookings.index'))
            ->patch(route('bookings.cancel', $booking->id));

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('error');

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_client_can_cancel_confirmed_unassigned_online_booking_before_payment_without_refund(): void
    {
        $client = $this->createVerifiedClient('client-confirmed-online-cancel@example.com', 'clientconfirmedonlinecancel');
        $booking = $this->createBooking($client, null, 'confirmed');
        $booking->payment->forceFill([
            'method' => 'gcash',
            'status' => 'pending',
            'provider' => 'paymongo',
            'amount' => 1200,
        ])->save();

        $response = $this->actingAs($client)
            ->from(route('bookings.index'))
            ->patch(route('bookings.cancel', $booking->id));

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('success');
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('pending', $booking->fresh()->payment->status);
        $this->assertSame('none', $booking->fresh()->payment->refund_status);
    }

    public function test_client_cannot_cancel_other_clients_booking(): void
    {
        $client = $this->createVerifiedClient('client-owner@example.com', 'clientowner');
        $otherClient = $this->createVerifiedClient('client-other@example.com', 'clientother');
        $booking = $this->createBooking($otherClient, null, 'pending');

        $response = $this->actingAs($client)
            ->patch(route('bookings.cancel', $booking->id));

        $response->assertStatus(403);
    }

    public function test_admin_can_cancel_any_booking(): void
    {
        $admin = $this->createAdmin('admin-cancel@example.com', 'admincancel');
        $client = $this->createVerifiedClient('client-admin-cancel@example.com', 'clientadmincancel');
        $booking = $this->createBooking($client, null, 'confirmed');

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'cancelled',
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHas('success');

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_admin_cancellation_refunds_paid_online_booking(): void
    {
        Config::set('services.paymongo.secret_key', 'sk_test_secret');
        Config::set('services.paymongo.api_url', 'https://api.paymongo.test');
        Http::fake([
            'api.paymongo.test/v1/refunds' => Http::response([
                'data' => [
                    'id' => 'ref_admin_cancel',
                    'attributes' => ['status' => 'succeeded', 'amount' => 120000],
                ],
            ], 201),
        ]);

        $admin = $this->createAdmin('admin-paid-cancel@example.com', 'adminpaidcancel');
        $client = $this->createVerifiedClient('client-admin-paid-cancel@example.com', 'clientadminpaidcancel');
        $booking = $this->createBooking($client, null, 'confirmed');
        $booking->payment->forceFill([
            'method' => 'gcash',
            'status' => 'paid',
            'provider' => 'paymongo',
            'provider_payment_id' => 'pay_admin_cancel',
            'amount' => 1200,
            'paid_at' => now(),
        ])->save();

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), ['status' => 'cancelled']);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHas('success');
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('refunded', $booking->fresh()->payment->status);
        $this->assertSame('succeeded', $booking->fresh()->payment->refund_status);
    }

    public function test_admin_cannot_skip_status_to_cancel_completed_booking(): void
    {
        $admin = $this->createAdmin('admin-cancel-complete@example.com', 'admincancelcomplete');
        $client = $this->createVerifiedClient('client-cancel-complete@example.com', 'clientcancelcomplete');
        $staff = $this->createStaff('staff-cancel-complete@example.com', 'staffcancelcomplete');
        $booking = $this->createBooking($client, $staff, 'completed');

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'cancelled',
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('status');

        $this->assertSame('completed', $booking->fresh()->status);
    }

    public function test_cancellation_creates_notification(): void
    {
        $admin = $this->createAdmin('admin-notify-cancel@example.com', 'adminnotifycancel');
        $client = $this->createVerifiedClient('client-notify-cancel@example.com', 'clientnotifycancel');
        $booking = $this->createBooking($client, null, 'pending');

        $this->actingAs($admin)
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'cancelled',
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $client->id,
            'title' => 'Booking cancelled',
        ]);
    }

    public function test_client_cancelling_paid_online_booking_refunds_before_cancelling(): void
    {
        Config::set('services.paymongo.secret_key', 'sk_test_secret');
        Config::set('services.paymongo.api_url', 'https://api.paymongo.test');
        Http::fake([
            'api.paymongo.test/v1/refunds' => Http::response([
                'data' => [
                    'id' => 'ref_test_123',
                    'attributes' => ['status' => 'succeeded', 'amount' => 120000],
                ],
            ], 201),
        ]);

        $client = $this->createVerifiedClient('client-paid-cancel@example.com', 'clientpaidcancel');
        $booking = $this->createBooking($client, null, 'pending');
        $booking->payment->forceFill([
            'method' => 'gcash',
            'status' => 'paid',
            'provider' => 'paymongo',
            'provider_payment_id' => 'pay_test_123',
            'amount' => 1200,
            'paid_at' => now(),
        ])->save();

        $response = $this->actingAs($client)
            ->from(route('bookings.index'))
            ->patch(route('bookings.cancel', $booking->id));

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('success');

        $payment = $booking->fresh()->payment;
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('refunded', $payment->status);
        $this->assertSame('succeeded', $payment->refund_status);
        $this->assertSame('ref_test_123', $payment->refund_reference);

        Http::assertSent(function ($request) use ($payment): bool {
            return $request->url() === 'https://api.paymongo.test/v1/refunds'
                && $request->header('Idempotency-Key')[0] === 'cleanflow-refund-payment-'.$payment->id.'-120000'
                && $request->data()['data']['attributes']['amount'] === 120000
                && $request->data()['data']['attributes']['payment_id'] === 'pay_test_123';
        });
    }

    public function test_client_cancellation_stays_pending_when_online_refund_fails(): void
    {
        Config::set('services.paymongo.secret_key', 'sk_test_secret');
        Config::set('services.paymongo.api_url', 'https://api.paymongo.test');
        Http::fake([
            'api.paymongo.test/v1/refunds' => Http::response(['errors' => [['code' => 'payment_not_refundable']]], 422),
        ]);

        $client = $this->createVerifiedClient('client-refund-fail@example.com', 'clientrefundfail');
        $booking = $this->createBooking($client, null, 'pending');
        $booking->payment->forceFill([
            'method' => 'maya',
            'status' => 'paid',
            'provider' => 'paymongo',
            'provider_payment_id' => 'pay_test_fail',
            'amount' => 1200,
            'paid_at' => now(),
        ])->save();

        $response = $this->actingAs($client)
            ->from(route('bookings.index'))
            ->patch(route('bookings.cancel', $booking->id));

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHasErrors('cancel');
        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertSame('paid', $booking->fresh()->payment->status);
        $this->assertSame('failed', $booking->fresh()->payment->refund_status);
    }

    private function createVerifiedClient(string $email, string $username): User
    {
        $user = User::create([
            'first_name' => 'Client',
            'last_name' => 'User',
            'email' => $email,
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => $username,
            'role' => 'client',
            'password' => Hash::make('password123'),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }

    private function createAdmin(string $email, string $username): User
    {
        return User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => $email,
            'phone' => '09171234567',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'street' => '123 Admin Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => $username,
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);
    }

    private function createStaff(string $email, string $username): User
    {
        return User::create([
            'first_name' => 'Staff',
            'last_name' => 'User',
            'email' => $email,
            'phone' => '09171234567',
            'date_of_birth' => '1995-01-01',
            'gender' => 'male',
            'street' => '123 Staff Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => $username,
            'role' => 'staff',
            'password' => Hash::make('password123'),
        ]);
    }

    private function createBooking(User $client, ?User $staff, string $status): Booking
    {
        return Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 1200,
            'status' => $status,
            'staff_id' => $staff?->id,
        ]);
    }
}
