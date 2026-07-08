<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('trigger_event', 50);             // appointment_completed | treatment_completed
            $table->foreignId('trigger_service_id')->nullable()->constrained('dental_services')->nullOnDelete();
            $table->unsignedSmallInteger('delay_days');
            $table->foreignId('message_template_id')->constrained('message_templates')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_rules');
    }
};
