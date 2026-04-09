<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
        * Create a test login account.
        */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'testuser'],
            [
                'email'        => 'tester@admin.com',
                'first_name'   => 'Test',
                'last_name'    => 'User',
                'role'         => 'admin',
                'phone'        => '09170000000',
                'date_of_birth'=> '1995-01-01',
                'gender'       => 'prefer_not_to_say',
                'street'       => '123 Cleanflow St.',
                'barangay'     => 'poblacion',
                'city'         => 'Valencia City',
                'zip_code'     => '8709',
                'username'     => 'testuser',
                'password'     => Hash::make('password123'),
            ]
        );

        User::updateOrCreate(
            ['username' => 'staffer'],
            [
                'email'        => 'staff@cleanflow.local',
                'first_name'   => 'Staff',
                'last_name'    => 'Member',
                'role'         => 'staff',
                'phone'        => '09171112222',
                'date_of_birth'=> '1996-02-02',
                'gender'       => 'prefer_not_to_say',
                'street'       => '456 Service Rd.',
                'barangay'     => 'bagontaas',
                'city'         => 'Valencia City',
                'zip_code'     => '8709',
                'username'     => 'staffer',
                'password'     => Hash::make('password123'),
            ]
        );

        User::updateOrCreate(
            ['username' => 'clientuser'],
            [
                'email'        => 'client@cleanflow.local',
                'first_name'   => 'Client',
                'last_name'    => 'User',
                'role'         => 'client',
                'phone'        => '09172223333',
                'date_of_birth'=> '1997-03-03',
                'gender'       => 'prefer_not_to_say',
                'street'       => '789 Customer Ave.',
                'barangay'     => 'balite',
                'city'         => 'Valencia City',
                'zip_code'     => '8709',
                'username'     => 'clientuser',
                'password'     => Hash::make('password123'),
            ]
        );
    }
}
