<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_periods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // CC-202605
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->string('status', 20)->default('open'); // open / locked
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreignId('locked_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestampTz('locked_at')->nullable();
            $table->foreignId('unlocked_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestampTz('unlocked_at')->nullable();
            $table->text('unlock_reason')->nullable();
            $table->timestampsTz();
            $table->unique(['month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_periods');
    }
};
