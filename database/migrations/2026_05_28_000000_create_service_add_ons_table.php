<?php

use App\Models\Booking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_add_ons', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        foreach (array_values(array_keys(Booking::ADD_ON_CATALOG)) as $index => $key) {
            $addOn = Booking::ADD_ON_CATALOG[$key];

            DB::table('service_add_ons')->insert([
                'key' => $key,
                'label' => $addOn['label'],
                'description' => $addOn['description'] ?? null,
                'price' => $addOn['price'],
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_add_ons');
    }
};
