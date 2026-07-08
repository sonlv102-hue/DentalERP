<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hkd_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('tax_code', 20)->nullable();
            $table->string('id_number', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('province', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('representative_name')->nullable();
            $table->string('representative_id', 20)->nullable();
            $table->string('tax_authority_name')->nullable();
            $table->date('registration_date')->nullable();
            // not_subject / vat_pit_revenue / vat_revenue_pit_income
            $table->string('tax_status', 40)->default('not_subject');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('hkd_business_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hkd_profile_id')->constrained()->cascadeOnDelete();
            $table->text('address');
            $table->string('province', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('ward', 100)->nullable();
            $table->string('business_type')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hkd_business_locations');
        Schema::dropIfExists('hkd_profiles');
    }
};
