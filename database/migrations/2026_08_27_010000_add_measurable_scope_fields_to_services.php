<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            if (! Schema::hasColumn('services', 'scope_max_floor_area')) {
                $table->unsignedSmallInteger('scope_max_floor_area')->nullable()->after('duration_minutes');
            }

            if (! Schema::hasColumn('services', 'scope_cleaner_count')) {
                $table->unsignedTinyInteger('scope_cleaner_count')->default(1)->after('scope_max_floor_area');
            }

            if (! Schema::hasColumn('services', 'scope_status')) {
                $table->string('scope_status', 20)->default('provisional')->after('scope_cleaner_count');
            }

            if (! Schema::hasColumn('services', 'scope_manual_review_above_limit')) {
                $table->boolean('scope_manual_review_above_limit')->default(true)->after('scope_status');
            }
        });

        DB::table('services')
            ->whereIn('slug', [
                'basic',
                'deep',
                'moveinout',
                'postconstruction',
                'commercial',
                'office-basic',
                'office-deep',
                'weeklymaintenance',
            ])
            ->update([
                'scope_max_floor_area' => 30,
                'scope_cleaner_count' => 1,
                'scope_status' => 'provisional',
                'scope_manual_review_above_limit' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            foreach ([
                'scope_manual_review_above_limit',
                'scope_status',
                'scope_cleaner_count',
                'scope_max_floor_area',
            ] as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
