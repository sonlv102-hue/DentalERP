<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();                 // BL-YYYYMM
            $table->unsignedSmallInteger('month');
            $table->unsignedSmallInteger('year');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attendance_period_id')->nullable()
                  ->constrained('attendance_periods')->nullOnDelete();
            $table->string('status', 20)->default('draft');       // draft/confirmed/locked/posted/paid

            // Aggregated totals
            $table->unsignedBigInteger('total_base_salary')->default(0);
            $table->unsignedBigInteger('total_allowances')->default(0);
            $table->unsignedBigInteger('total_company_insurance')->default(0);
            $table->unsignedBigInteger('total_employee_insurance')->default(0);
            $table->unsignedBigInteger('total_personal_income_tax')->default(0);
            $table->unsignedBigInteger('total_union_fee')->default(0);
            $table->unsignedBigInteger('total_gross_income')->default(0);
            $table->unsignedBigInteger('total_deductions')->default(0);
            $table->unsignedBigInteger('total_net_salary')->default(0);

            // KPCĐ confirmation
            $table->boolean('union_fee_confirmed')->default(false);

            // Workflow timestamps
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('confirmed_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('locked_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('posted_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestampsTz();

            $table->unique(['month', 'year', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
