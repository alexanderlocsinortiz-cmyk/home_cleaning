<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('services')
            ->where('slug', 'moveinout')
            ->update([
                'price' => 3500.0,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('services')
            ->where('slug', 'moveinout')
            ->update([
                'price' => 2000.0,
                'updated_at' => now(),
            ]);
    }
};
