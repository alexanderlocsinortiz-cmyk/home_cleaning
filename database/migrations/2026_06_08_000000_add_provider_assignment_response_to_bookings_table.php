<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('provider_assignment_status')->nullable()->after('cleaner_application_id');
            $table->timestamp('provider_assignment_responded_at')->nullable()->after('provider_assignment_status');
            $table->text('provider_assignment_notes')->nullable()->after('provider_assignment_responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'provider_assignment_status',
                'provider_assignment_responded_at',
                'provider_assignment_notes',
            ]);
        });
    }
};
