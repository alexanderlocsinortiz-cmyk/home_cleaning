<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_disputes') && Schema::getConnection()->getDriverName() === 'pgsql') {
            Schema::table('booking_disputes', function (Blueprint $table): void {
                $table->string('reason', 80)->nullable()->change();
                $table->text('description')->nullable()->change();
                $table->timestamp('disputed_at')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('booking_disputes') && Schema::getConnection()->getDriverName() === 'pgsql') {
            Schema::table('booking_disputes', function (Blueprint $table): void {
                $table->string('reason', 80)->nullable(false)->change();
                $table->text('description')->nullable(false)->change();
                $table->timestamp('disputed_at')->nullable(false)->change();
            });
        }
    }
};
