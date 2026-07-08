<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chứng từ điện tử — 5-year retention per TT152
        Schema::create('hkd_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hkd_profile_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 30);      // revenue / expense / inventory / cash / other_tax
            $table->unsignedBigInteger('source_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedInteger('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->date('document_date')->nullable();
            $table->date('retention_until');        // document_date + 5 years
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['source_type', 'source_id']);
            $table->index(['hkd_profile_id', 'retention_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hkd_documents');
    }
};
