<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_period_id')->constrained('attendance_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->unsignedTinyInteger('weekday'); // 1=Mon..7=Sun
            $table->string('symbol', 10)->nullable(); // X,P,KP,L,CT,OT,NB,O,TS
            $table->string('status_type', 20)->nullable();
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->decimal('working_hours', 4, 2)->default(0);
            $table->decimal('overtime_hours', 4, 2)->default(0);
            $table->decimal('paid_workday', 3, 1)->default(0);
            $table->decimal('unpaid_workday', 3, 1)->default(0);
            $table->text('note')->nullable();
            $table->string('source_type', 20)->default('manual'); // manual/machine/import/system
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['attendance_period_id', 'employee_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
