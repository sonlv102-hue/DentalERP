<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_slip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_slip_id')->constrained('salary_slips')->cascadeOnDelete();
            $table->string('type', 20);                      // base | commission | deduction | bonus
            $table->string('description', 255);
            $table->bigInteger('amount');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slip_items');
    }
};
