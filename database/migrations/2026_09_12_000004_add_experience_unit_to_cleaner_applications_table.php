<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table): void {
            $table->string('experience_unit', 10)->default('years')->after('years_experience');
        });

        $this->resizeExperienceColumn('SMALLINT');
    }

    public function down(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table): void {
            $table->dropColumn('experience_unit');
        });

        $this->resizeExperienceColumn('TINYINT');
    }

    private function resizeExperienceColumn(string $mysqlType): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE cleaner_applications MODIFY years_experience '.$mysqlType.' UNSIGNED NOT NULL DEFAULT 0');
        }
    }
};
