<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')
            ->where('contact_email', 'support@homecleaningservice.local')
            ->update(['contact_email' => null]);

        DB::table('site_settings')
            ->where('contact_address', 'Valencia City, Bukidnon, Philippines')
            ->update(['contact_address' => null]);

        DB::table('site_settings')
            ->where('office_hours', 'Monday - Saturday, 8:00 AM - 5:00 PM')
            ->update(['office_hours' => null]);
    }

    public function down(): void
    {
        // Placeholder contact details are intentionally not restored on rollback.
    }
};
