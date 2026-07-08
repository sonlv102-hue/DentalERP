<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('attendance_devices')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_pin', 20);                    // raw pin from device
            $table->timestampTz('punched_at');
            $table->unsignedTinyInteger('status')->default(0); // 0=in,1=out,4=ot_in,5=ot_out
            $table->unsignedTinyInteger('punch_type')->default(0); // 0=finger,15=password
            $table->boolean('is_processed')->default(false);   // whether pushed to timesheets
            $table->timestampsTz();
            $table->unique(['device_id', 'user_pin', 'punched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
