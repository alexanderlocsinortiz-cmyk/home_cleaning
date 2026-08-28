<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('reviewed_by')->constrained('users')->nullOnDelete();
            $table->string('activation_token_hash', 64)->nullable()->unique()->after('reviewed_at');
            $table->timestamp('activation_token_expires_at')->nullable()->after('activation_token_hash');
            $table->timestamp('activated_at')->nullable()->after('activation_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['activation_token_hash', 'activation_token_expires_at', 'activated_at']);
        });
    }
};
