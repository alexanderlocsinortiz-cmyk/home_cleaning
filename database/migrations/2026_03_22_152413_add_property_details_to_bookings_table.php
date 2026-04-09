<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('property_type')->nullable()->after('barangay')->comment('house, apartment, boarding_house');
            $table->integer('rooms')->default(1)->after('property_type');
            $table->integer('bathrooms')->default(1)->after('rooms');
            $table->integer('floor_area')->default(0)->after('bathrooms')->comment('in sqm');
            $table->json('add_ons')->nullable()->after('floor_area');
            $table->decimal('base_price', 10, 2)->default(0)->after('add_ons');
            $table->decimal('property_fee', 10, 2)->default(0)->after('base_price');
            $table->decimal('rooms_fee', 10, 2)->default(0)->after('property_fee');
            $table->decimal('bathrooms_fee', 10, 2)->default(0)->after('rooms_fee');
            $table->decimal('floor_area_fee', 10, 2)->default(0)->after('bathrooms_fee');
            $table->decimal('add_ons_fee', 10, 2)->default(0)->after('floor_area_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'property_type',
                'rooms',
                'bathrooms',
                'floor_area',
                'add_ons',
                'base_price',
                'property_fee',
                'rooms_fee',
                'bathrooms_fee',
                'floor_area_fee',
                'add_ons_fee',
            ]);
        });
    }
};
