<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table): void {
            $table->string('tracking_token_previous_hash', 64)->nullable()->unique()->after('tracking_token_hash');
            $table->timestamp('tracking_token_previous_expires_at')->nullable()->after('tracking_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table): void {
            $table->dropUnique(['tracking_token_previous_hash']);
            $table->dropColumn(['tracking_token_previous_hash', 'tracking_token_previous_expires_at']);
        });
    }
};
