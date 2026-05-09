<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_one_subscription_booking_cancels_all_occurrences(): void
    {
        $client = $this->createVerifiedClient('client-sub-cancel@example.com', 'clientsubcancel');
        $admin = $this->createAdmin('admin-sub-cancel@example.com', 'adminsubcancel');
        $subscriptionGroupId = Str::uuid()->toString();

        $bookings = collect();
        for ($i = 1; $i <= 3; $i++) {
            $booking = Booking::create([
                'user_id' => $client->id,
                'service_type' => 'basic',
                'barangay' => 'Poblacion',
                'street_address' => '123 Rizal Street',
                'scheduled_date' => now()->addDays($i)->toDateString(),
                'scheduled_time' => '09:00',
                'price' => 1200,
                'status' => 'pending',
                'service_plan' => 'subscription',
                'subscription_frequency' => 'weekly',
                'subscription_occurrences' => 3,
                'subscription_group_id' => $subscriptionGroupId,
                'subscription_sequence' => $i,
            ]);
            $bookings->push($booking);
        }

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $bookings[0]->id), [
                'status' => 'cancelled',
            ]);

        $response->assertRedirect(route('admin.bookings'));

        foreach ($bookings as $booking) {
            $this->assertSame('cancelled', $booking->fresh()->status);
        }
    }

    public function test_admin_can_cancel_single_subscription_occurrence(): void
    {
        $client = $this->createVerifiedClient('client-sub-single@example.com', 'clientsubsingle');
        $admin = $this->createAdmin('admin-sub-single@example.com', 'adminsubsingle');
        $subscriptionGroupId = Str::uuid()->toString();

        $booking = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 1200,
            'status' => 'confirmed',
            'service_plan' => 'subscription',
            'subscription_frequency' => 'weekly',
            'subscription_occurrences' => 3,
            'subscription_group_id' => $subscriptionGroupId,
            'subscription_sequence' => 1,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'cancelled',
            ]);

        $response->assertRedirect(route('admin.bookings'));

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_subscription_with_preferred_staff_assigns_alternate_if_unavailable(): void
    {
        $client = $this->createVerifiedClient('client-sub-pref@example.com', 'clientsubpref');
        $preferredStaff = $this->createStaff('preferred-sub@example.com', 'preferredsub');
        $alternateStaff = $this->createStaff('alternate-sub@example.com', 'alternatesub');
        $admin = $this->createAdmin('admin-sub-pref@example.com', 'adminsubpref');
        $subscriptionGroupId = Str::uuid()->toString();

        $bookingWithConflict = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 1200,
            'status' => 'confirmed',
            'staff_id' => $preferredStaff->id,
            'service_plan' => 'subscription',
            'subscription_frequency' => 'weekly',
            'subscription_occurrences' => 3,
            'subscription_group_id' => $subscriptionGroupId,
            'subscription_sequence' => 1,
        ]);

        $newBooking = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(9)->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 1200,
            'status' => 'confirmed',
            'service_plan' => 'subscription',
            'subscription_frequency' => 'weekly',
            'subscription_occurrences' => 3,
            'subscription_group_id' => $subscriptionGroupId,
            'subscription_sequence' => 2,
            'preferred_staff_id' => $preferredStaff->id,
            'preferred_staff_status' => 'requested',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $newBooking->id), [
                'status' => 'confirmed',
                'staff_id' => $alternateStaff->id,
            ]);

        $response->assertRedirect(route('admin.bookings'));

        $updatedBooking = $newBooking->fresh();
        $this->assertSame($alternateStaff->id, $updatedBooking->staff_id);
    }

    public function test_subscription_booking_pricing_is_consistent_across_occurrences(): void
    {
        $client = $this->createVerifiedClient('client-sub-price@example.com', 'clientsubprice');
        $subscriptionGroupId = Str::uuid()->toString();

        $basePrice = 1200;
        $expectedTotal = $basePrice * 4;

        for ($i = 1; $i <= 4; $i++) {
            Booking::create([
                'user_id' => $client->id,
                'service_type' => 'basic',
                'barangay' => 'Poblacion',
                'street_address' => '123 Rizal Street',
                'scheduled_date' => now()->addDays($i)->toDateString(),
                'scheduled_time' => '09:00',
                'price' => $basePrice,
                'base_price' => 800,
                'property_fee' => 100,
                'rooms_fee' => 150,
                'bathrooms_fee' => 100,
                'floor_area_fee' => 32,
                'add_ons_fee' => 18,
                'status' => 'pending',
                'service_plan' => 'subscription',
                'subscription_frequency' => 'weekly',
                'subscription_occurrences' => 4,
                'subscription_group_id' => $subscriptionGroupId,
                'subscription_sequence' => $i,
            ]);
        }

        $bookings = Booking::where('subscription_group_id', $subscriptionGroupId)->get();

        $this->assertCount(4, $bookings);

        foreach ($bookings as $booking) {
            $this->assertEquals($basePrice, $booking->price);
        }

        $totalPrice = $bookings->sum('price');
        $this->assertEquals($expectedTotal, $totalPrice);
    }

    public function test_cannot_schedule_subscription_with_conflict_on_any_occurrence(): void
    {
        $client = $this->createVerifiedClient('client-sub-conflict@example.com', 'clientsubconflict');

        $existingBooking = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '10:00',
            'price' => 1200,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($client)->post(route('bookings.store'), [
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'floor_area' => 30,
            'payment_method' => 'on_site_cash',
            'service_plan' => 'subscription',
            'subscription_frequency' => 'weekly',
            'subscription_occurrences' => 3,
            'barangay' => 'Poblacion',
            'street_address' => '123 New Street',
            'scheduled_date' => now()->addDays(1)->toDateString(),
            'scheduled_time' => '10:00',
        ]);

        $response->assertSessionHasErrors('scheduled_time');
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
}
