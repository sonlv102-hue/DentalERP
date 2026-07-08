<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('category', 50);
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('acquisition_date');
            $table->bigInteger('acquisition_cost');
            $table->unsignedInteger('useful_life_months');
            $table->bigInteger('monthly_depreciation');
            $table->bigInteger('accumulated_depreciation')->default(0);
            $table->bigInteger('net_book_value');
            $table->string('last_depreciation_period', 7)->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
