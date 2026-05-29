<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('services')
            ->where('slug', 'postconstruction')
            ->update([
                'price' => 105.0,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('services')
            ->where('slug', 'postconstruction')
            ->update([
                'price' => 1800.0,
                'updated_at' => now(),
            ]);
    }
};
