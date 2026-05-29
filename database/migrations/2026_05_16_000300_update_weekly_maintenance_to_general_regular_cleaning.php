<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('services')
            ->where('slug', 'weeklymaintenance')
            ->update([
                'name' => 'General/Regular Cleaning',
                'description' => 'General or regular cleaning session for routine upkeep, up to 4 hours.',
                'price' => 500.0,
                'duration_minutes' => 240,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('services')
            ->where('slug', 'weeklymaintenance')
            ->update([
                'name' => 'Weekly Maintenance Plan',
                'description' => 'Recurring weekly cleaning plan for consistent upkeep and readiness.',
                'price' => 900.0,
                'duration_minutes' => 120,
                'updated_at' => now(),
            ]);
    }
};
