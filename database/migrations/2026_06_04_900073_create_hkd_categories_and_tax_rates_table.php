<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hkd_business_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hkd_profile_id')->constrained()->cascadeOnDelete();
            $table->string('industry_code', 20)->nullable();
            $table->string('industry_name');
            // goods / services / production / construction / transportation / other
            $table->string('revenue_category', 30)->default('services');
            $table->boolean('is_primary')->default(false);
            $table->timestampsTz();
        });

        // Configurable tax rates — NOT hardcoded. effective_from/effective_to for version history.
        Schema::create('hkd_tax_rates', function (Blueprint $table) {
            $table->id();
            // goods / services / production / construction / transportation / other
            $table->string('revenue_category', 30);
            $table->decimal('vat_rate', 5, 4)->default(0.01);   // e.g. 0.0100 = 1%
            $table->decimal('pit_rate', 5, 4)->default(0.005);  // e.g. 0.0050 = 0.5%
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hkd_tax_rates');
        Schema::dropIfExists('hkd_business_categories');
    }
};
