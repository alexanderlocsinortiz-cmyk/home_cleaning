<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffSeeder extends Seeder
{
    /**
     * Seed the staff table with sample records.
     */
    public function run(): void
    {
        $barangays = array_keys(config('cleanflow.barangays'));

        $staff = [
            [
                'first_name' => 'Maria',
                'last_name' => 'Dizon',
                'position' => 'Cleaner',
                'phone' => '09171234567',
                'barangay' => $barangays[0] ?? 'poblacion',
                'status' => 'active',
            ],
            [
                'first_name' => 'John',
                'last_name' => 'Reyes',
                'position' => 'Supervisor',
                'phone' => '09181234567',
                'barangay' => $barangays[5] ?? 'balite',
                'status' => 'active',
            ],
            [
                'first_name' => 'Ella',
                'last_name' => 'Santos',
                'position' => 'Cleaner',
                'phone' => '09191234567',
                'barangay' => $barangays[10] ?? 'big_lagao',
                'status' => 'inactive',
            ],
            [
                'first_name' => 'Ramon',
                'last_name' => 'Flores',
                'position' => 'Driver',
                'phone' => '09051234567',
                'barangay' => $barangays[15] ?? 'katipunan',
                'status' => 'active',
            ],
            [
                'first_name' => 'Jessa',
                'last_name' => 'Lim',
                'position' => 'Cleaner',
                'phone' => '09061234567',
                'barangay' => $barangays[20] ?? 'panay',
                'status' => 'active',
            ],
            [
                'first_name' => 'Carlos',
                'last_name' => 'Medina',
                'position' => 'Supervisor',
                'phone' => '09071234567',
                'barangay' => $barangays[25] ?? 'tongantongan',
                'status' => 'active',
            ],
        ];

        foreach ($staff as $member) {
            $username = Str::slug($member['first_name'].$member['last_name']);
            $user = User::updateOrCreate(
                ['username' => $username],
                [
                    'email' => $username.'@cleanflow.local',
                    'first_name' => $member['first_name'],
                    'last_name' => $member['last_name'],
                    'role' => 'staff',
                    'phone' => $member['phone'],
                    'barangay' => $member['barangay'],
                    'city' => 'Valencia City',
                    'zip_code' => '8709',
                    'password' => Hash::make('password123'),
                ]
            );

            Staff::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_id' => 'EMP-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                    'hourly_rate' => $member['position'] === 'Supervisor' ? 150 : 120,
                    'bio' => $member['position'].' assigned to '.$member['barangay'].'.',
                    'years_of_experience' => 1,
                    'is_active' => $member['status'] === 'active',
                ]
            );
        }
    }
}
