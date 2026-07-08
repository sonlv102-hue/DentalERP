<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('active')->default(true);

            // Employee insurance rates (%)
            $table->decimal('employee_social_insurance_rate', 5, 2)->default(8.00);
            $table->decimal('employee_health_insurance_rate', 5, 2)->default(1.50);
            $table->decimal('employee_unemployment_insurance_rate', 5, 2)->default(1.00);

            // Company insurance rates (%)
            $table->decimal('company_social_insurance_rate', 5, 2)->default(17.50);
            $table->decimal('company_health_insurance_rate', 5, 2)->default(3.00);
            $table->decimal('company_unemployment_insurance_rate', 5, 2)->default(1.00);

            // Union fee rate (%)
            $table->decimal('union_fee_rate', 5, 2)->default(2.00);

            // PIT deductions (VND)
            $table->unsignedBigInteger('family_deduction_amount')->default(11000000);
            $table->unsignedBigInteger('dependent_deduction_amount')->default(4400000);

            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
    }
};
