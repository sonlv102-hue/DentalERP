<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('period', 7);                     // YYYY-MM
            $table->bigInteger('base_salary')->default(0);
            $table->bigInteger('commission_total')->default(0);
            $table->bigInteger('deductions')->default(0);
            $table->bigInteger('net_salary')->default(0);    // base + commission - deductions
            $table->string('status', 20)->default('draft');  // draft | confirmed | paid
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['employee_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slips');
    }
};
