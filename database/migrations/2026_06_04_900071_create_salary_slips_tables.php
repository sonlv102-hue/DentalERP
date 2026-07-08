<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add OT + payroll columns to the existing salary_slips table
        Schema::table('salary_slips', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete()->after('employee_id');
            $table->unsignedSmallInteger('work_days')->default(0)->after('branch_id');
            $table->decimal('ot_hours', 5, 2)->default(0)->after('base_salary');
            $table->unsignedInteger('ot_rate')->default(0)->after('ot_hours');  // ₫ per OT hour
            $table->unsignedBigInteger('ot_amount')->default(0)->after('ot_rate');
            $table->timestampTz('paid_at')->nullable()->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('salary_slips', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['branch_id', 'work_days', 'ot_hours', 'ot_rate', 'ot_amount', 'paid_at']);
        });
    }
};
