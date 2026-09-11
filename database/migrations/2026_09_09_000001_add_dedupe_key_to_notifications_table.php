<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            if (! Schema::hasColumn('notifications', 'dedupe_key')) {
                $table->string('dedupe_key')->nullable()->unique();
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            if (Schema::hasColumn('notifications', 'dedupe_key')) {
                $table->dropUnique(['dedupe_key']);
                $table->dropColumn('dedupe_key');
            }
        });
    }
};
