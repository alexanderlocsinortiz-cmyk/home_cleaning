<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\CleanerApplication;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class E2EUserSeeder extends Seeder
{
    public function run(): void
    {
        $client = User::updateOrCreate(
            ['email' => 'e2e-client@cleanflow.local'],
            [
                'first_name' => 'E2E',
                'last_name' => 'Client',
                'phone' => '09170000001',
                'date_of_birth' => '1990-01-01',
                'gender' => 'prefer_not_to_say',
                'street' => '123 E2E Client Street',
                'barangay' => 'poblacion',
                'city' => 'Valencia City',
                'zip_code' => '8709',
                'username' => 'e2eclient',
                'role' => 'client',
                'password' => Hash::make('password123'),
            ]
        );
        $client->forceFill(['email_verified_at' => now()])->save();

        $provider = User::updateOrCreate(
            ['email' => 'e2e-provider@cleanflow.local'],
            [
                'first_name' => 'E2E',
                'last_name' => 'Provider',
                'phone' => '09170000002',
                'date_of_birth' => '1990-01-02',
                'gender' => 'prefer_not_to_say',
                'street' => '456 E2E Provider Street',
                'barangay' => 'poblacion',
                'city' => 'Valencia City',
                'zip_code' => '8709',
                'username' => 'e2eprovider',
                'role' => 'provider',
                'password' => Hash::make('password123'),
            ]
        );
        $provider->forceFill(['email_verified_at' => now()])->save();

        $staff = User::updateOrCreate(
            ['email' => 'e2e-staff@cleanflow.local'],
            [
                'first_name' => 'E2E',
                'last_name' => 'Staff',
                'phone' => '09170000003',
                'date_of_birth' => '1990-01-03',
                'gender' => 'prefer_not_to_say',
                'street' => '789 E2E Staff Street',
                'barangay' => 'poblacion',
                'city' => 'Valencia City',
                'zip_code' => '8709',
                'username' => 'e2estaff',
                'role' => 'staff',
                'password' => Hash::make('password123'),
            ]
        );
        $staff->forceFill(['email_verified_at' => now()])->save();

        CleanerApplication::updateOrCreate(
            ['user_id' => $provider->id],
            [
                'applicant_type' => CleanerApplication::TYPE_TEAM,
                'business_name' => 'E2E Provider Cleaners',
                'contact_person' => $provider->display_name,
                'email' => $provider->email,
                'phone' => $provider->phone,
                'service_area' => 'Valencia City',
                'coverage_barangays' => ['poblacion'],
                'years_experience' => 3,
                'team_size' => 5,
                'services_offered' => 'Residential cleaning',
                'status' => CleanerApplication::STATUS_APPROVED,
                'activated_at' => now(),
                'availability_status' => 'available',
            ]
        );

        Booking::factory()->create([
            'user_id' => $client->id,
            'service_id' => Service::query()->where('slug', 'deep')->value('id'),
            'staff_id' => $staff->id,
            'status' => 'in_progress',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'street_address' => '123 E2E Client Street',
            'barangay' => 'poblacion',
            'price' => 1200,
            'payment_method' => 'on_site_cash',
            'payment_status' => 'pending',
        ]);
    }
}
