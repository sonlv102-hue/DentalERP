<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_service_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('dental_services')->cascadeOnDelete();
            // material | lab | implant_fixture | medicine | imaging | chair_overhead | other
            $table->string('cost_type', 50);
            $table->string('cost_name', 200);
            $table->bigInteger('standard_cost')->default(0); // VND
            $table->boolean('is_excluded_from_kpi_base')->default(false); // exclude from gross_margin calc
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_service_costs');
    }
};
