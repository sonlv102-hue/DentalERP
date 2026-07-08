<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot fields (copied at payroll creation time)
            $table->string('employee_code', 20);
            $table->string('employee_name', 100);
            $table->string('position_name', 100)->nullable();
            $table->string('department_name', 100)->nullable();

            // Work days
            $table->unsignedSmallInteger('standard_working_days')->default(26);
            $table->decimal('actual_working_days', 5, 2)->default(0);
            $table->decimal('workday_ratio', 6, 4)->default(0);  // actual / standard

            // Salary base
            $table->unsignedBigInteger('base_salary')->default(0);
            $table->unsignedBigInteger('insurance_salary')->default(0);  // lương đóng BH
            $table->unsignedBigInteger('salary_by_workday')->default(0); // base × ratio

            // Allowances (phụ cấp tính BH)
            $table->unsignedBigInteger('fixed_allowance')->default(0);
            $table->unsignedBigInteger('responsibility_allowance')->default(0);
            // Trợ cấp/phúc lợi (không tính BH)
            $table->unsignedBigInteger('lunch_allowance')->default(0);
            $table->unsignedBigInteger('phone_allowance')->default(0);
            $table->unsignedBigInteger('travel_allowance')->default(0);
            $table->unsignedBigInteger('performance_kpi_amount')->default(0);
            $table->unsignedBigInteger('other_allowance')->default(0);

            // Company insurance
            $table->unsignedBigInteger('company_social_insurance')->default(0);
            $table->unsignedBigInteger('company_health_insurance')->default(0);
            $table->unsignedBigInteger('company_unemployment_insurance')->default(0);
            $table->unsignedBigInteger('total_company_insurance')->default(0);

            // Employee insurance
            $table->unsignedBigInteger('employee_social_insurance')->default(0);
            $table->unsignedBigInteger('employee_health_insurance')->default(0);
            $table->unsignedBigInteger('employee_unemployment_insurance')->default(0);
            $table->unsignedBigInteger('total_employee_insurance')->default(0);

            // PIT
            $table->unsignedBigInteger('taxable_income')->default(0);
            $table->unsignedSmallInteger('dependents_count')->default(0);
            $table->unsignedBigInteger('family_deduction')->default(0);
            $table->unsignedBigInteger('dependent_deduction')->default(0);
            $table->unsignedBigInteger('personal_income_tax')->default(0);
            $table->boolean('pit_manual_override')->default(false);
            $table->unsignedBigInteger('pit_manual_amount')->nullable();

            // Union fee (KPCĐ — borne by company, not deducted from salary)
            $table->unsignedBigInteger('union_fee_amount')->default(0);
            $table->boolean('union_fee_confirmed')->default(false);

            // Totals
            $table->unsignedBigInteger('gross_income')->default(0);
            $table->unsignedBigInteger('total_deductions')->default(0);
            $table->unsignedBigInteger('net_salary')->default(0);

            // Flags
            $table->boolean('social_insurance_enabled')->default(true);
            $table->boolean('insurance_manual_override')->default(false);
            $table->boolean('salary_manual_override')->default(false);
            $table->string('kpi_source_type', 20)->default('manual'); // manual/kpi_module/payroll_input
            $table->string('status', 20)->default('draft');
            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['payroll_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
