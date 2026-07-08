<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_symbols', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('label', 50);
            $table->string('color', 20)->default('gray'); // tailwind color name
            $table->boolean('is_paid')->default(true);
            $table->boolean('counts_as_workday')->default(false);
            $table->boolean('counts_as_leave')->default(false);
            $table->boolean('counts_as_unpaid_leave')->default(false);
            $table->boolean('counts_as_overtime')->default(false);
            $table->decimal('default_paid_workday', 3, 1)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_symbols');
    }
};
