<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alias recipient_id to user_id for compatibility
        // The notifications table already has user_id, so we don't need to add anything
        // Just add new optional fields for the SMS-to-email feature
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'booking_id')) {
                $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('cascade');
            }
            if (!Schema::hasColumn('notifications', 'subject')) {
                $table->string('subject')->nullable();
            }
            if (!Schema::hasColumn('notifications', 'sent_at')) {
                $table->timestamp('sent_at')->nullable();
            }

            // Add indexes for performance
            $table->index('user_id');
            $table->index('booking_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeignKeyIfExists('notifications_booking_id_foreign');

            $table->dropColumnIfExists('booking_id');
            $table->dropColumnIfExists('subject');
            $table->dropColumnIfExists('sent_at');

            $table->dropIndex('notifications_user_id_index');
            $table->dropIndex('notifications_booking_id_index');
            $table->dropIndex('notifications_created_at_index');
        });
    }
};
