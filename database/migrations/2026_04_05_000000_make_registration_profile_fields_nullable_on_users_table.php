<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
            $table->date('date_of_birth')->nullable()->change();
            $table->enum('gender', ['male', 'female', 'prefer_not_to_say'])->nullable()->change();
            $table->string('street')->nullable()->change();
            $table->string('barangay')->nullable()->change();
            $table->string('zip_code', 10)->nullable()->change();
            $table->string('username', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->whereNull('phone')->update(['phone' => '09170000000']);
        DB::table('users')->whereNull('date_of_birth')->update(['date_of_birth' => '2000-01-01']);
        DB::table('users')->whereNull('gender')->update(['gender' => 'prefer_not_to_say']);
        DB::table('users')->whereNull('street')->update(['street' => 'Address not provided']);
        DB::table('users')->whereNull('barangay')->update(['barangay' => 'Poblacion']);
        DB::table('users')->whereNull('zip_code')->update(['zip_code' => '8709']);

        DB::table('users')
            ->select('id')
            ->whereNull('username')
            ->orderBy('id')
            ->get()
            ->each(function ($user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['username' => 'user' . $user->id]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable(false)->change();
            $table->date('date_of_birth')->nullable(false)->change();
            $table->enum('gender', ['male', 'female', 'prefer_not_to_say'])->nullable(false)->change();
            $table->string('street')->nullable(false)->change();
            $table->string('barangay')->nullable(false)->change();
            $table->string('zip_code', 10)->nullable(false)->change();
            $table->string('username', 20)->nullable(false)->change();
        });
    }
};
