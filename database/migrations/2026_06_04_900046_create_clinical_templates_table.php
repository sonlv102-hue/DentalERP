<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);                              // diagnosis | prescription | note
            $table->string('title', 255);
            $table->text('content');
            $table->foreignId('service_id')->nullable()->constrained('dental_services')->nullOnDelete();
            $table->boolean('is_global')->default(true);
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_templates');
    }
};
