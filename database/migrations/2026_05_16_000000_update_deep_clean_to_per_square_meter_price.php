<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('services')
            ->where('slug', 'deep')
            ->update([
                'price' => 95.0,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('services')
            ->where('slug', 'deep')
            ->update([
                'price' => 1200.0,
                'updated_at' => now(),
            ]);
    }
};
