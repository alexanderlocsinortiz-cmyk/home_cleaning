<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table): void {
            $table->string('government_id_front_document_path')->nullable()->after('government_id_document_original_filename');
            $table->string('government_id_front_document_original_filename')->nullable()->after('government_id_front_document_path');
            $table->string('government_id_back_document_path')->nullable()->after('government_id_front_document_original_filename');
            $table->string('government_id_back_document_original_filename')->nullable()->after('government_id_back_document_path');
        });
    }

    public function down(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table): void {
            $table->dropColumn([
                'government_id_front_document_path',
                'government_id_front_document_original_filename',
                'government_id_back_document_path',
                'government_id_back_document_original_filename',
            ]);
        });
    }
};
