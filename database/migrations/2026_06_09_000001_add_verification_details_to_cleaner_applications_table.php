<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->string('current_address')->nullable()->after('date_of_birth');
            $table->string('profile_photo_path')->nullable()->after('current_address');
            $table->string('profile_photo_original_filename')->nullable()->after('profile_photo_path');
            $table->string('business_logo_path')->nullable()->after('profile_photo_original_filename');
            $table->string('business_logo_original_filename')->nullable()->after('business_logo_path');
            $table->string('government_id_type')->nullable()->after('services_offered');
            $table->string('government_id_number')->nullable()->after('government_id_type');
            $table->string('government_id_document_path')->nullable()->after('government_id_number');
            $table->string('government_id_document_original_filename')->nullable()->after('government_id_document_path');
            $table->string('nbi_clearance_number')->nullable()->after('government_id_document_original_filename');
            $table->string('nbi_clearance_document_path')->nullable()->after('nbi_clearance_number');
            $table->string('nbi_clearance_document_original_filename')->nullable()->after('nbi_clearance_document_path');
            $table->string('selfie_with_id_path')->nullable()->after('nbi_clearance_document_original_filename');
            $table->string('selfie_with_id_original_filename')->nullable()->after('selfie_with_id_path');
            $table->boolean('worked_as_cleaner_before')->nullable()->after('verification_notes');
            $table->boolean('worked_for_cleaning_company_before')->nullable()->after('worked_as_cleaner_before');
            $table->boolean('has_cleaning_certifications')->nullable()->after('worked_for_cleaning_company_before');
            $table->boolean('owns_cleaning_equipment')->nullable()->after('has_cleaning_certifications');
            $table->json('available_days')->nullable()->after('max_daily_bookings');
            $table->boolean('terms_certify_accurate')->default(false)->after('available_days');
            $table->boolean('terms_agree_verification')->default(false)->after('terms_certify_accurate');
            $table->boolean('terms_approval_not_guaranteed')->default(false)->after('terms_agree_verification');
            $table->boolean('terms_service_standards')->default(false)->after('terms_approval_not_guaranteed');
        });
    }

    public function down(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_birth',
                'current_address',
                'profile_photo_path',
                'profile_photo_original_filename',
                'business_logo_path',
                'business_logo_original_filename',
                'government_id_type',
                'government_id_number',
                'government_id_document_path',
                'government_id_document_original_filename',
                'nbi_clearance_number',
                'nbi_clearance_document_path',
                'nbi_clearance_document_original_filename',
                'selfie_with_id_path',
                'selfie_with_id_original_filename',
                'worked_as_cleaner_before',
                'worked_for_cleaning_company_before',
                'has_cleaning_certifications',
                'owns_cleaning_equipment',
                'available_days',
                'terms_certify_accurate',
                'terms_agree_verification',
                'terms_approval_not_guaranteed',
                'terms_service_standards',
            ]);
        });
    }
};
