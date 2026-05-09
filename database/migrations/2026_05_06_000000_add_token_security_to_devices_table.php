<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Add security fields for token encryption and expiration
            $table->text('secret_key')->nullable()->after('api_token');
            $table->timestamp('token_expires_at')->nullable()->after('secret_key');
            $table->timestamp('last_token_rotated_at')->nullable()->after('token_expires_at');
            
            // Add indexes for performance
            $table->index('token_expires_at');
            $table->index('is_active');
            $table->index('serial_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['token_expires_at']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['serial_number']);
            $table->dropColumn([
                'secret_key',
                'token_expires_at',
                'last_token_rotated_at',
            ]);
        });
    }
};
