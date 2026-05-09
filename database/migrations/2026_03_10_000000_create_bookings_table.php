<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings')) {
            return;
        }

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained()->onDelete('set null');
            $table->string('service_type')->nullable();
            $table->string('barangay')->nullable();
            $table->string('street_address')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->decimal('base_price', 10, 2)->nullable()->default(0);
            $table->decimal('property_adjustment', 10, 2)->nullable()->default(0);
            $table->decimal('room_bathroom_fees', 10, 2)->nullable()->default(0);
            $table->decimal('floor_area_fees', 10, 2)->nullable()->default(0);
            $table->decimal('add_on_fees', 10, 2)->nullable()->default(0);
            $table->string('property_type')->nullable();
            $table->integer('rooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->decimal('floor_area', 8, 2)->nullable();
            $table->enum('manual_review_status', ['not_required', 'pending', 'approved', 'blocked'])->default('not_required');
            $table->enum('preferred_staff_status', ['none', 'requested', 'assigned', 'unavailable'])->default('none');
            $table->enum('payment_method', ['on_site_cash', 'gcash', 'maya'])->default('on_site_cash');
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->decimal('price', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('address')->nullable();
            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
