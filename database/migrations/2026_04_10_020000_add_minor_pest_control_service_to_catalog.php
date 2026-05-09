<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('services')->where('slug', 'minorpest')->exists();

        if ($exists) {
            return;
        }

        DB::table('services')->insert([
            'name' => 'Minor Pest Control',
            'slug' => 'minorpest',
            'description' => 'Specialty service for minor pest concerns in residential properties, including targeted treatment and preventive inspection guidance.',
            'price' => 950.0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('services')->where('slug', 'minorpest')->delete();
    }
};
