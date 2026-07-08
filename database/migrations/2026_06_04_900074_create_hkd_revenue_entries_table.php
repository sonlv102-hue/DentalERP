<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hkd_revenue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hkd_profile_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);            // YYYY-MM
            $table->date('entry_date');
            $table->string('document_no')->nullable();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_tax_code', 20)->nullable();
            $table->text('description');
            // goods / services / production / construction / transportation / other
            $table->string('revenue_category', 30)->default('services');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('vat_amount')->default(0);
            $table->unsignedBigInteger('pit_amount')->default(0);
            // manual / invoice (from patient_invoices)
            $table->string('source_type', 20)->default('manual');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['hkd_profile_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hkd_revenue_entries');
    }
};
