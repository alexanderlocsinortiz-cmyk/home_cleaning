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
            if (!Schema::hasColumn('bookings', 'property_type')) {
                $table->string('property_type')->nullable()->after('barangay')->comment('house, apartment, boarding_house');
            }
            if (!Schema::hasColumn('bookings', 'rooms')) {
                $table->integer('rooms')->default(1)->after('property_type');
            }
            if (!Schema::hasColumn('bookings', 'bathrooms')) {
                $table->integer('bathrooms')->default(1)->after('rooms');
            }
            if (!Schema::hasColumn('bookings', 'floor_area')) {
                $table->integer('floor_area')->default(0)->after('bathrooms')->comment('in sqm');
            }
            if (!Schema::hasColumn('bookings', 'add_ons')) {
                $table->json('add_ons')->nullable()->after('floor_area');
            }
            if (!Schema::hasColumn('bookings', 'base_price')) {
                $table->decimal('base_price', 10, 2)->default(0)->after('add_ons');
            }
            if (!Schema::hasColumn('bookings', 'property_fee')) {
                $table->decimal('property_fee', 10, 2)->default(0)->after('base_price');
            }
            if (!Schema::hasColumn('bookings', 'rooms_fee')) {
                $table->decimal('rooms_fee', 10, 2)->default(0)->after('property_fee');
            }
            if (!Schema::hasColumn('bookings', 'bathrooms_fee')) {
                $table->decimal('bathrooms_fee', 10, 2)->default(0)->after('rooms_fee');
            }
            if (!Schema::hasColumn('bookings', 'floor_area_fee')) {
                $table->decimal('floor_area_fee', 10, 2)->default(0)->after('bathrooms_fee');
            }
            if (!Schema::hasColumn('bookings', 'add_ons_fee')) {
                $table->decimal('add_ons_fee', 10, 2)->default(0)->after('floor_area_fee');
            }
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
