<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_service_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('dental_services')->cascadeOnDelete();
            $table->string('step_name', 200);
            $table->integer('step_order')->default(0);
            // counseling | examination | imaging | treatment_planning | main_treatment
            // chairside_assist | impression | prosthetics | follow_up | aftercare
            $table->string('default_role', 50)->nullable();
            $table->integer('estimated_minutes')->default(0);
            $table->decimal('kpi_share_percent', 5, 2)->default(0); // % of KPI pool this step gets
            $table->boolean('deduct_from_main_doctor')->default(true); // if another person does this, deduct from main doctor
            $table->boolean('require_quality_check')->default(false);
            $table->boolean('require_attachment')->default(false); // hold KPI until file uploaded
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_service_steps');
    }
};
