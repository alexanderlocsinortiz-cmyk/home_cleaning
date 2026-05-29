<?php

use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'duration_minutes')) {
                $table->unsignedSmallInteger('duration_minutes')->default(Service::DEFAULT_DURATION_MINUTES)->after('price');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'duration_minutes')) {
                $table->unsignedSmallInteger('duration_minutes')->default(Service::DEFAULT_DURATION_MINUTES)->after('scheduled_time');
            }
        });

        foreach (Service::packageCatalog() as $slug => $package) {
            DB::table('services')
                ->where('slug', $slug)
                ->update([
                    'duration_minutes' => (int) ($package['recommended_duration_minutes'] ?? Service::DEFAULT_DURATION_MINUTES),
                ]);

            DB::table('bookings')
                ->where('service_type', $slug)
                ->where(function ($query) {
                    $query->whereNull('duration_minutes')->orWhere('duration_minutes', Service::DEFAULT_DURATION_MINUTES);
                })
                ->update([
                    'duration_minutes' => (int) ($package['recommended_duration_minutes'] ?? Service::DEFAULT_DURATION_MINUTES),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }
        });
    }
};
