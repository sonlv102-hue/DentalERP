<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hkd_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hkd_profile_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30)->nullable();
            $table->string('name');
            $table->string('unit', 20)->default('cái');
            $table->decimal('opening_qty', 12, 3)->default(0);
            $table->unsignedBigInteger('opening_unit_cost')->default(0);  // weighted avg cost at period start
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        // S2d — Sổ chi tiết vật liệu, dụng cụ, sản phẩm, hàng hóa
        Schema::create('hkd_inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hkd_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('hkd_inventory_items')->cascadeOnDelete();
            $table->string('period', 7);    // YYYY-MM
            $table->date('trans_date');
            // import / export
            $table->string('trans_type', 10);
            $table->decimal('qty', 12, 3);
            // For import: purchase price. For export: weighted avg cost at time of export (set by service).
            $table->unsignedBigInteger('unit_cost');
            $table->unsignedBigInteger('amount');       // qty * unit_cost
            $table->string('document_no')->nullable();
            $table->string('counterpart')->nullable();  // supplier (import) or buyer (export)
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['hkd_profile_id', 'period']);
            $table->index(['item_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hkd_inventory_transactions');
        Schema::dropIfExists('hkd_inventory_items');
    }
};
