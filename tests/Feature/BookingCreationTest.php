<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingActivityLog;
use App\Models\BookingServiceProof;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BookingCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_booking_create_route_uses_the_real_booking_form(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        Service::updateOrCreate(['slug' => 'postconstruction'], [
            'name' => 'Post Construction Cleaning',
            'description' => 'Detailed post-construction cleanup',
            'price' => 1800,
            'is_active' => true,
        ]);

        $user = User::create([
            'first_name' => 'Client',
            'last_name' => 'User',
            'email' => 'client-route@example.com',
            'phone' => '09171234568',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'clientroute',
            'role' => 'client',
            'password' => Hash::make('password123'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $response = $this->actingAs($user)->get(route('client.bookings.create'));

        $response->assertOk();
        $response->assertViewIs('bookings.create');
        $response->assertSee('Floor Area (sqm)', false);
        $response->assertSee('Add-ons (optional)', false);
        $response->assertSee('Street / Purok / House Details', false);
        $response->assertSee('Preferred Cleaner (optional)', false);
        $response->assertSee('Payment and Service Plan', false);
        $response->assertSee('Cash on Service Day', false);
        $response->assertSee('Subscription Plan', false);
        $response->assertSee('Post Construction Cleaning', false);
        $response->assertSee('Eco-Friendly Supplies', false);
    }

    public function test_authenticated_client_can_create_a_booking_with_calculated_price(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $user = User::create([
            'first_name' => 'Client',
            'last_name' => 'User',
            'email' => 'client@example.com',
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'clientuser',
            'role' => 'client',
            'password' => Hash::make('password123'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $payload = [
            'service_type' => 'basic',
            'property_type' => 'apartment',
            'rooms' => 3,
            'bathrooms' => 2,
            'floor_area' => 45,
            'add_ons' => ['window_glass', 'refrigerator'],
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '09:00',
        ];

        $response = $this->actingAs($user)->post(route('bookings.store'), $payload);

        $response->assertRedirect(route('bookings.index'));

        $booking = Booking::first();

        $this->assertNotNull($booking);
        $this->assertSame($user->id, $booking->user_id);
        $this->assertSame('pending', $booking->status);
        $this->assertSame('not_required', $booking->manual_review_status);
        $this->assertNull($booking->risk_reasons);
        $this->assertSame(1520.0, (float) $booking->price);
        $this->assertSame(570.0, (float) $booking->base_price);
        $this->assertSame(200.0, (float) $booking->property_fee);
        $this->assertSame(100.0, (float) $booking->rooms_fee);
        $this->assertSame(100.0, (float) $booking->bathrooms_fee);
        $this->assertSame(120.0, (float) $booking->floor_area_fee);
        $this->assertSame(430.0, (float) $booking->add_ons_fee);
        $this->assertSame(['window_glass', 'refrigerator'], $booking->add_ons);
    }

    public function test_booking_details_page_shows_price_breakdown_for_floor_area_and_add_ons(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $client = $this->createVerifiedUser([
            'email' => 'breakdown-client@example.com',
            'username' => 'breakdownclient',
        ]);

        $booking = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'property_type' => 'apartment',
            'rooms' => 3,
            'bathrooms' => 2,
            'floor_area' => 45,
            'add_ons' => ['window_glass', 'refrigerator'],
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '09:00',
            'base_price' => 570,
            'property_fee' => 200,
            'rooms_fee' => 100,
            'bathrooms_fee' => 100,
            'floor_area_fee' => 120,
            'add_ons_fee' => 430,
            'payment_method' => 'gcash',
            'payment_status' => 'paid',
            'payment_reference' => 'GCASH-TEST-12345',
            'paid_at' => now(),
            'service_plan' => 'subscription',
            'subscription_frequency' => 'weekly',
            'subscription_occurrences' => 4,
            'subscription_group_id' => 'test-group',
            'subscription_sequence' => 1,
            'price' => 1520,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($client)->get(route('bookings.show', $booking->id));

        $response->assertOk();
        $response->assertSee('Price Breakdown', false);
        $response->assertSee('Service Basis', false);
        $response->assertSee('Floor area adjustment', false);
        $response->assertSee('Window Glass Cleaning', false);
        $response->assertSee('Refrigerator Cleaning', false);
        $response->assertSee('Payment', false);
        $response->assertSee('GCash', false);
        $response->assertSee('Subscription Plan', false);
    }

    public function test_assigned_staff_can_view_booking_details_with_service_proof_sections(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $client = $this->createVerifiedUser([
            'email' => 'detail-client@example.com',
            'username' => 'detailclient',
        ]);
        $staff = $this->createVerifiedUser([
            'email' => 'detail-staff@example.com',
            'username' => 'detailstaff',
            'role' => 'staff',
            'first_name' => 'Detail',
            'last_name' => 'Cleaner',
        ]);

        $booking = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'floor_area' => 30,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 620,
            'status' => 'in_progress',
            'staff_id' => $staff->id,
        ]);

        BookingServiceProof::create([
            'booking_id' => $booking->id,
            'uploaded_by' => $staff->id,
            'stage' => 'before',
            'media_type' => 'image',
            'file_path' => 'booking-proofs/before/example.jpg',
            'original_name' => 'example.jpg',
        ]);

        BookingActivityLog::create([
            'booking_id' => $booking->id,
            'actor_id' => $staff->id,
            'actor_role' => 'staff',
            'actor_name' => $staff->full_name,
            'action' => 'status_updated',
            'description' => 'Marked the booking as in progress.',
        ]);

        $response = $this->actingAs($staff)->get(route('bookings.show', $booking->id));

        $response->assertOk();
        $response->assertSee('Proof of Service', false);
        $response->assertSee('Before Service Photos', false);
        $response->assertSee('Staff Action History', false);
    }

    public function test_client_can_create_a_subscription_booking_with_digital_payment(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $user = $this->createVerifiedUser([
            'email' => 'subscription-client@example.com',
            'username' => 'subscriptionclient',
        ]);

        $response = $this->actingAs($user)->post(route('bookings.store'), [
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'floor_area' => 30,
            'add_ons' => ['eco_friendly_supplies'],
            'payment_method' => 'gcash',
            'service_plan' => 'subscription',
            'subscription_frequency' => 'weekly',
            'subscription_occurrences' => 3,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(5)->toDateString(),
            'scheduled_time' => '09:00',
        ]);

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('success');

        $bookings = Booking::where('user_id', $user->id)->orderBy('scheduled_date')->get();

        $this->assertCount(3, $bookings);
        $this->assertTrue($bookings->every(fn (Booking $booking) => $booking->service_plan === 'subscription'));
        $this->assertTrue($bookings->every(fn (Booking $booking) => $booking->payment_method === 'gcash'));
        $this->assertTrue($bookings->every(fn (Booking $booking) => $booking->payment_status === 'pending'));
        $this->assertTrue($bookings->every(fn (Booking $booking) => empty($booking->payment_reference)));
        $this->assertSame('weekly', $bookings->first()->subscription_frequency);
        $this->assertSame(3, (int) $bookings->first()->subscription_occurrences);
        $this->assertSame(1, $bookings->pluck('subscription_group_id')->unique()->count());
        $this->assertSame([1, 2, 3], $bookings->pluck('subscription_sequence')->all());
        $this->assertSame(
            [
                now()->addDays(5)->toDateString(),
                now()->addDays(12)->toDateString(),
                now()->addDays(19)->toDateString(),
            ],
            $bookings->pluck('scheduled_date')->all()
        );
    }

    public function test_client_can_request_a_preferred_cleaner_when_the_schedule_is_available(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $client = $this->createVerifiedUser([
            'email' => 'preferred-client@example.com',
            'username' => 'preferredclient',
        ]);
        $preferredCleaner = $this->createVerifiedUser([
            'email' => 'preferred-cleaner@example.com',
            'username' => 'preferredcleaner',
            'role' => 'staff',
            'first_name' => 'Preferred',
            'last_name' => 'Cleaner',
        ]);

        $response = $this->actingAs($client)->post(route('bookings.store'), [
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'floor_area' => 30,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(4)->toDateString(),
            'scheduled_time' => '08:00',
            'preferred_staff_id' => $preferredCleaner->id,
        ]);

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('info');

        $booking = Booking::first();

        $this->assertSame($preferredCleaner->id, $booking->preferred_staff_id);
        $this->assertSame('requested', $booking->preferred_staff_status);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $client->id,
            'title' => 'Preferred cleaner request received',
            'type' => 'info',
            'link' => '/bookings/'.$booking->id,
        ]);
    }

    public function test_booking_marks_requested_cleaner_unavailable_when_they_are_already_busy(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $existingClient = $this->createVerifiedUser([
            'email' => 'existing-preferred@example.com',
            'username' => 'existingpreferred',
        ]);
        $newClient = $this->createVerifiedUser([
            'email' => 'new-preferred@example.com',
            'username' => 'newpreferred',
        ]);
        $preferredCleaner = $this->createVerifiedUser([
            'email' => 'busy-preferred-cleaner@example.com',
            'username' => 'busypreferredcleaner',
            'role' => 'staff',
            'first_name' => 'Busy',
            'last_name' => 'Cleaner',
        ]);
        $this->createVerifiedUser([
            'email' => 'backup-cleaner@example.com',
            'username' => 'backupcleaner',
            'role' => 'staff',
            'first_name' => 'Backup',
            'last_name' => 'Cleaner',
        ]);

        $scheduledDate = now()->addDays(5)->toDateString();

        Booking::create([
            'user_id' => $existingClient->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => '09:00',
            'price' => 620,
            'status' => 'confirmed',
            'staff_id' => $preferredCleaner->id,
        ]);

        $response = $this->actingAs($newClient)->post(route('bookings.store'), [
            'service_type' => 'basic',
            'property_type' => 'apartment',
            'rooms' => 3,
            'bathrooms' => 2,
            'floor_area' => 30,
            'barangay' => 'Poblacion',
            'street_address' => '456 Mabini Street',
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => '09:00',
            'preferred_staff_id' => $preferredCleaner->id,
        ]);

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('warning');

        $booking = Booking::where('user_id', $newClient->id)->latest('id')->first();

        $this->assertSame($preferredCleaner->id, $booking->preferred_staff_id);
        $this->assertSame('unavailable', $booking->preferred_staff_status);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $newClient->id,
            'title' => 'Preferred cleaner unavailable',
            'type' => 'warning',
            'link' => '/bookings/'.$booking->id,
        ]);
    }

    public function test_client_cannot_create_another_active_booking_for_the_same_time_slot(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $client = $this->createVerifiedUser([
            'email' => 'duplicate-client@example.com',
            'username' => 'duplicateclient',
            'role' => 'client',
        ]);

        $this->createVerifiedUser([
            'email' => 'staff-one@example.com',
            'username' => 'staffone',
            'role' => 'staff',
        ]);
        $this->createVerifiedUser([
            'email' => 'staff-two@example.com',
            'username' => 'stafftwo',
            'role' => 'staff',
        ]);

        Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(4)->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 620,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($client)
            ->from(route('bookings.create'))
            ->post(route('bookings.store'), [
                'service_type' => 'basic',
                'property_type' => 'apartment',
                'rooms' => 3,
                'bathrooms' => 2,
                'floor_area' => 30,
                'barangay' => 'Poblacion',
                'street_address' => '123 Rizal Street',
                'scheduled_date' => now()->addDays(4)->toDateString(),
                'scheduled_time' => '09:00',
            ]);

        $response->assertRedirect(route('bookings.create'));
        $response->assertSessionHasErrors('scheduled_time');
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_different_clients_can_book_the_same_time_slot_when_capacity_is_available(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $existingClient = $this->createVerifiedUser([
            'email' => 'existing-client@example.com',
            'username' => 'existingclient',
            'role' => 'client',
        ]);
        $newClient = $this->createVerifiedUser([
            'email' => 'new-client@example.com',
            'username' => 'newclient',
            'role' => 'client',
        ]);

        $this->createVerifiedUser([
            'email' => 'staff-capacity-one@example.com',
            'username' => 'capacityone',
            'role' => 'staff',
        ]);
        $this->createVerifiedUser([
            'email' => 'staff-capacity-two@example.com',
            'username' => 'capacitytwo',
            'role' => 'staff',
        ]);

        Booking::create([
            'user_id' => $existingClient->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(5)->toDateString(),
            'scheduled_time' => '10:00',
            'price' => 620,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($newClient)->post(route('bookings.store'), [
            'service_type' => 'basic',
            'property_type' => 'apartment',
            'rooms' => 3,
            'bathrooms' => 2,
            'floor_area' => 30,
            'barangay' => 'Poblacion',
            'street_address' => '456 Mabini Street',
            'scheduled_date' => now()->addDays(5)->toDateString(),
            'scheduled_time' => '10:00',
        ]);

        $response->assertRedirect(route('bookings.index'));
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_client_cannot_book_a_time_slot_when_staff_capacity_is_full(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $existingClient = $this->createVerifiedUser([
            'email' => 'full-slot-existing@example.com',
            'username' => 'fullslotexisting',
            'role' => 'client',
        ]);
        $newClient = $this->createVerifiedUser([
            'email' => 'full-slot-new@example.com',
            'username' => 'fullslotnew',
            'role' => 'client',
        ]);

        $staff = $this->createVerifiedUser([
            'email' => 'single-staff@example.com',
            'username' => 'singlestaff',
            'role' => 'staff',
        ]);

        Booking::create([
            'user_id' => $existingClient->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(6)->toDateString(),
            'scheduled_time' => '11:00',
            'price' => 620,
            'status' => 'confirmed',
            'staff_id' => $staff->id,
        ]);

        $response = $this->actingAs($newClient)
            ->from(route('bookings.create'))
            ->post(route('bookings.store'), [
                'service_type' => 'basic',
                'property_type' => 'apartment',
                'rooms' => 3,
                'bathrooms' => 2,
                'floor_area' => 30,
                'barangay' => 'Poblacion',
                'street_address' => '456 Mabini Street',
                'scheduled_date' => now()->addDays(6)->toDateString(),
                'scheduled_time' => '11:00',
            ]);

        $response->assertRedirect(route('bookings.create'));
        $response->assertSessionHasErrors('scheduled_time');
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_client_with_missing_profile_details_is_redirected_to_profile_edit_before_booking(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $client = $this->createVerifiedUser([
            'email' => 'missing-profile@example.com',
            'username' => 'missingprofile',
            'phone' => null,
            'date_of_birth' => null,
        ]);

        $response = $this->actingAs($client)
            ->from(route('client.bookings.create'))
            ->post(route('bookings.store'), [
                'service_type' => 'basic',
                'property_type' => 'apartment',
                'rooms' => 2,
                'bathrooms' => 1,
                'floor_area' => 30,
                'barangay' => 'Poblacion',
                'street_address' => '123 Rizal Street',
                'scheduled_date' => now()->addDays(3)->toDateString(),
                'scheduled_time' => '09:00',
            ]);

        $response->assertRedirect(route('client.profile.edit'));
        $response->assertSessionHasErrors(['phone', 'date_of_birth']);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_is_flagged_for_manual_review_when_same_address_and_schedule_already_exists_for_another_client(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $existingClient = $this->createVerifiedUser([
            'email' => 'existing-suspicious@example.com',
            'username' => 'existingsuspicious',
        ]);
        $newClient = $this->createVerifiedUser([
            'email' => 'new-suspicious@example.com',
            'username' => 'newsuspicious',
        ]);

        $this->createVerifiedUser([
            'email' => 'review-staff-one@example.com',
            'username' => 'reviewstaffone',
            'role' => 'staff',
        ]);
        $this->createVerifiedUser([
            'email' => 'review-staff-two@example.com',
            'username' => 'reviewstafftwo',
            'role' => 'staff',
        ]);

        $scheduledDate = now()->addDays(7)->toDateString();

        Booking::create([
            'user_id' => $existingClient->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => '14:00',
            'price' => 620,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($newClient)->post(route('bookings.store'), [
            'service_type' => 'basic',
            'property_type' => 'apartment',
            'rooms' => 3,
            'bathrooms' => 2,
            'floor_area' => 30,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => '14:00',
        ]);

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('success', 'Your booking request has been submitted and is pending manual review before confirmation.');

        $flaggedBooking = Booking::where('user_id', $newClient->id)->orderByDesc('id')->first();

        $this->assertSame('pending', $flaggedBooking->manual_review_status);
        $this->assertContains('Another client already requested this exact address and schedule.', $flaggedBooking->risk_reasons ?? []);
    }

    public function test_booking_is_flagged_for_manual_review_when_client_creates_multiple_recent_requests(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $client = $this->createVerifiedUser([
            'email' => 'rapid-booker@example.com',
            'username' => 'rapidbooker',
        ]);

        Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(8)->toDateString(),
            'scheduled_time' => '08:00',
            'price' => 620,
            'status' => 'pending',
        ]);

        Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'barangay' => 'Poblacion',
            'street_address' => '456 Mabini Street',
            'scheduled_date' => now()->addDays(8)->toDateString(),
            'scheduled_time' => '10:00',
            'price' => 620,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($client)->post(route('bookings.store'), [
            'service_type' => 'basic',
            'property_type' => 'apartment',
            'rooms' => 3,
            'bathrooms' => 2,
            'floor_area' => 30,
            'barangay' => 'Poblacion',
            'street_address' => '789 Bonifacio Street',
            'scheduled_date' => now()->addDays(9)->toDateString(),
            'scheduled_time' => '13:00',
        ]);

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('success', 'Your booking request has been submitted and is pending manual review before confirmation.');

        $flaggedBooking = Booking::where('user_id', $client->id)->orderByDesc('id')->first();

        $this->assertSame('pending', $flaggedBooking->manual_review_status);
        $this->assertContains('This client has created multiple booking requests within the last 24 hours.', $flaggedBooking->risk_reasons ?? []);
    }

    public function test_unverified_client_cannot_create_a_booking(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $user = User::create([
            'first_name' => 'Pending',
            'last_name' => 'Client',
            'email' => 'pending@example.com',
            'phone' => '09171234569',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'pendingclient',
            'role' => 'client',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post(route('bookings.store'), [
            'service_type' => 'basic',
            'property_type' => 'apartment',
            'rooms' => 2,
            'bathrooms' => 1,
            'floor_area' => 30,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '09:00',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_staff_cannot_create_a_booking_through_client_routes(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $user = User::create([
            'first_name' => 'Staff',
            'last_name' => 'User',
            'email' => 'staff-booking@example.com',
            'phone' => '09171234560',
            'date_of_birth' => '1998-01-01',
            'gender' => 'male',
            'street' => '456 Mabini Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'staffbooking',
            'role' => 'staff',
            'password' => Hash::make('password123'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $response = $this->actingAs($user)->post(route('bookings.store'), [
            'service_type' => 'basic',
            'property_type' => 'apartment',
            'rooms' => 2,
            'bathrooms' => 1,
            'floor_area' => 30,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '09:00',
        ]);

        $response->assertRedirect(route('staff.dashboard'));
        $this->assertDatabaseCount('bookings', 0);
    }

    private function createVerifiedUser(array $overrides): User
    {
        $user = User::create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user@example.com',
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'testuser',
            'role' => 'client',
            'password' => Hash::make('password123'),
        ], $overrides));

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }
}
