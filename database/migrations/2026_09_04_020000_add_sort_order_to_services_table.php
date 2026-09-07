<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('services', 'sort_order')) {
            Schema::table('services', function (Blueprint $table): void {
                $table->unsignedInteger('sort_order')->default(0)->after('duration_minutes');
                $table->index(['is_active', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('services', 'sort_order')) {
            Schema::table('services', function (Blueprint $table): void {
                $table->dropIndex(['is_active', 'sort_order']);
                $table->dropColumn('sort_order');
            });
        }
    }
};
