<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend treatment_plan_items with doctor + timestamps + new statuses
        Schema::table('treatment_plan_items', function (Blueprint $table) {
            $table->foreignId('responsible_doctor_id')->nullable()->after('status')
                ->constrained('employees')->nullOnDelete();
            $table->foreignId('examination_id')->nullable()->after('responsible_doctor_id')
                ->constrained('dental_examinations')->nullOnDelete();
            $table->timestampTz('started_at')->nullable()->after('examination_id');
            $table->timestampTz('completed_at')->nullable()->after('started_at');
            // status values: pending | scheduled | in_progress | completed | cancelled | warranty | redo
        });

        // Step executions — actual work done per step per plan item
        Schema::create('treatment_step_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_step_id')->constrained('dental_service_steps')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('performed_by')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('assisted_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('ended_at')->nullable();
            // pending | done | cancelled | redo
            $table->string('status', 20)->default('pending');
            // pending | passed | failed
            $table->string('quality_status', 20)->default('pending');
            $table->string('quality_rule_id')->nullable(); // which rule triggered failure
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        // Multi-person participation in a single step execution
        Schema::create('treatment_step_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('step_execution_id')->constrained('treatment_step_executions')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('role', 50)->nullable(); // doctor | assistant | imaging_tech | counselor
            $table->decimal('share_percent', 5, 2)->default(100); // % of this step's KPI this person gets
            $table->text('note')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_step_participants');
        Schema::dropIfExists('treatment_step_executions');
        Schema::table('treatment_plan_items', function (Blueprint $table) {
            $table->dropForeign(['responsible_doctor_id']);
            $table->dropForeign(['examination_id']);
            $table->dropColumn(['responsible_doctor_id', 'examination_id', 'started_at', 'completed_at']);
        });
    }
};
