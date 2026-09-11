<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'preferred_cleaner_application_id')) {
                $table->foreignId('preferred_cleaner_application_id')
                    ->nullable()
                    ->after('preferred_staff_status')
                    ->constrained('cleaner_applications')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('bookings', 'preferred_cleaner_status')) {
                $table->string('preferred_cleaner_status', 30)
                    ->default('none')
                    ->after('preferred_cleaner_application_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (Schema::hasColumn('bookings', 'preferred_cleaner_application_id')) {
                $table->dropConstrainedForeignId('preferred_cleaner_application_id');
            }

            if (Schema::hasColumn('bookings', 'preferred_cleaner_status')) {
                $table->dropColumn('preferred_cleaner_status');
            }
        });
    }
};
