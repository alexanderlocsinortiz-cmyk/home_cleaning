<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table) {
            $table->string('applicant_type', 20)->default('individual')->after('id');
            $table->unsignedTinyInteger('team_size')->nullable()->after('years_experience');
        });
    }

    public function down(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table) {
            $table->dropColumn(['applicant_type', 'team_size']);
        });
    }
};
