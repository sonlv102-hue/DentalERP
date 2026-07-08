<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            // purchase / usage / adjustment / return
            $table->string('transaction_type', 20);
            // positive = stock in, negative = stock out
            $table->decimal('qty', 12, 3);
            $table->unsignedBigInteger('unit_cost')->default(0);
            $table->unsignedBigInteger('amount')->default(0); // abs(qty) * unit_cost
            $table->date('transaction_date');
            // manual / treatment_execution / purchase_invoice
            $table->string('source_type', 30)->default('manual');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('document_no', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['inventory_item_id', 'transaction_date']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
