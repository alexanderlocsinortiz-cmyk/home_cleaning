<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;

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
                'last_name'  => 'Dizon',
                'role'       => 'Cleaner',
                'phone'      => '09171234567',
                'barangay'   => $barangays[0] ?? 'poblacion',
                'status'     => 'active',
            ],
            [
                'first_name' => 'John',
                'last_name'  => 'Reyes',
                'role'       => 'Supervisor',
                'phone'      => '09181234567',
                'barangay'   => $barangays[5] ?? 'balite',
                'status'     => 'active',
            ],
            [
                'first_name' => 'Ella',
                'last_name'  => 'Santos',
                'role'       => 'Cleaner',
                'phone'      => '09191234567',
                'barangay'   => $barangays[10] ?? 'big_lagao',
                'status'     => 'inactive',
            ],
            [
                'first_name' => 'Ramon',
                'last_name'  => 'Flores',
                'role'       => 'Driver',
                'phone'      => '09051234567',
                'barangay'   => $barangays[15] ?? 'katipunan',
                'status'     => 'active',
            ],
            [
                'first_name' => 'Jessa',
                'last_name'  => 'Lim',
                'role'       => 'Cleaner',
                'phone'      => '09061234567',
                'barangay'   => $barangays[20] ?? 'panay',
                'status'     => 'active',
            ],
            [
                'first_name' => 'Carlos',
                'last_name'  => 'Medina',
                'role'       => 'Supervisor',
                'phone'      => '09071234567',
                'barangay'   => $barangays[25] ?? 'tongantongan',
                'status'     => 'active',
            ],
        ];

        foreach ($staff as $member) {
            Staff::updateOrCreate(
                [
                    'first_name' => $member['first_name'],
                    'last_name'  => $member['last_name'],
                    'role'       => $member['role'],
                ],
                $member
            );
        }
    }
}
