<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cleaner_applications', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('contact_person');
            $table->string('email');
            $table->string('phone', 30);
            $table->string('service_area');
            $table->unsignedTinyInteger('years_experience')->default(0);
            $table->text('services_offered');
            $table->text('verification_notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cleaner_applications');
    }
};
