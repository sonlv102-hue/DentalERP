<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('dental_services')->cascadeOnDelete();
            $table->foreignId('step_execution_id')->nullable()->constrained('treatment_step_executions')->nullOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('role', 50)->nullable(); // main_doctor | step_performer | assistant

            // Calculation inputs
            $table->bigInteger('eligible_revenue')->default(0);      // amount actually paid for this item
            $table->bigInteger('direct_cost')->default(0);           // sum of service_costs
            $table->bigInteger('kpi_pool_amount')->default(0);       // gross KPI pool for this item
            $table->decimal('share_percent', 5, 2)->default(100);    // this employee's % of pool
            $table->bigInteger('kpi_amount')->default(0);            // pool × share_percent

            // Quality/collection adjustments
            $table->decimal('quality_factor', 3, 2)->default(1.00);
            $table->decimal('collection_factor', 3, 2)->default(1.00);
            $table->bigInteger('final_kpi_amount')->default(0);      // kpi_amount × quality × collection

            $table->string('calculation_base', 20)->default('revenue'); // revenue | gross_margin | fixed
            $table->string('period', 7)->nullable(); // YYYY-MM

            // accrued | pending_approval | approved | paid | reversed | held
            $table->string('status', 30)->default('accrued');
            $table->text('reason')->nullable(); // hold/reverse reason
            $table->text('notes')->nullable();

            $table->timestampTz('calculated_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_allocations');
    }
};
