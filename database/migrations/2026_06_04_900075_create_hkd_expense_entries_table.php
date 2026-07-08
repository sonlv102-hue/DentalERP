<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hkd_expense_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hkd_profile_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);            // YYYY-MM
            $table->date('entry_date');
            $table->string('document_no')->nullable();
            $table->string('supplier_name')->nullable();
            $table->string('supplier_tax_code', 20)->nullable();
            // materials / labor / rent / utilities / depreciation / tax / other
            $table->string('category', 30)->default('other');
            $table->text('description');
            $table->unsignedBigInteger('amount');
            // manual / expense (from expenses table) / purchase (from purchase_invoices)
            $table->string('source_type', 20)->default('manual');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['hkd_profile_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hkd_expense_entries');
    }
};
