<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\StaffSeeder;
use Tests\TestCase;

class SeederSafetyTest extends TestCase
{
    public function test_demo_staff_seeder_is_skipped_in_production(): void
    {
        $previousEnvironment = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        try {
            (new StaffSeeder)->run();
        } finally {
            app()->detectEnvironment(fn () => $previousEnvironment);
        }

        $this->assertDatabaseMissing('users', [
            'username' => 'mariadizon',
        ]);
        $this->assertDatabaseMissing('users', [
            'username' => 'johnreyes',
        ]);
        $this->assertSame(0, User::where('email', 'like', '%@cleanflow.local')->count());
    }
}
