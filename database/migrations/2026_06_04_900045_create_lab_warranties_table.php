<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->string('service_name');
            $table->string('tooth_number', 20)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_warranties');
    }
};
