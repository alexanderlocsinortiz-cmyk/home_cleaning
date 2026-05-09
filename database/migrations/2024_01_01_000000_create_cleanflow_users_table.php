<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');
            $table->string('phone')->after('email');
            $table->date('date_of_birth')->after('phone');
            $table->enum('gender', ['male', 'female', 'prefer_not_to_say'])->after('date_of_birth');
            $table->string('street')->after('gender');
            $table->string('barangay')->after('street');
            $table->string('city')->default('Valencia City')->after('barangay');
            $table->string('zip_code', 10)->after('city');
            $table->string('username', 20)->unique()->after('zip_code');
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'phone', 'date_of_birth', 'gender', 'street', 'barangay', 'city', 'zip_code', 'username']);
            $table->string('name');
        });
    }
};
