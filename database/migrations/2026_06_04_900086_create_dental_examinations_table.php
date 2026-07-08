<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_examinations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // KH-0001
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('consultant_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('chief_complaint')->nullable();
            $table->text('diagnosis_note')->nullable();
            $table->text('examination_note')->nullable();
            $table->text('recommended_plan_note')->nullable();
            // draft | completed | cancelled
            $table->string('status', 20)->default('draft');
            $table->timestampTz('examined_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('dental_examination_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_id')->constrained('dental_examinations')->cascadeOnDelete();
            $table->foreignId('condition_id')->constrained('dental_conditions')->cascadeOnDelete();
            $table->string('tooth_no', 10)->nullable(); // e.g. 11, 21, 36
            $table->string('severity', 20)->nullable(); // mild | moderate | severe
            $table->text('note')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_examination_conditions');
        Schema::dropIfExists('dental_examinations');
    }
};
