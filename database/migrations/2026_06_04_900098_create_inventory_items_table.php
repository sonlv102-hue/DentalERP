<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            // material / medicine / equipment / consumable / other
            $table->string('category', 30)->default('material');
            $table->string('unit', 20)->default('cái');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('min_stock_qty', 12, 3)->default(0);
            $table->decimal('current_stock_qty', 12, 3)->default(0);
            $table->unsignedBigInteger('unit_cost')->default(0);  // weighted avg
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
