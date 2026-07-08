<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // S2e — Sổ chi tiết tiền
        Schema::create('hkd_cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hkd_profile_id')->constrained()->cascadeOnDelete();
            // cash / bank / e_wallet
            $table->string('type', 20)->default('cash');
            $table->string('name');
            $table->string('bank_name')->nullable();
            $table->string('account_number', 30)->nullable();
            $table->bigInteger('opening_balance')->default(0);  // can be negative
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('hkd_cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hkd_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('hkd_cash_accounts')->cascadeOnDelete();
            $table->string('period', 7);    // YYYY-MM
            $table->date('trans_date');
            // receipt / payment
            $table->string('trans_type', 20);
            $table->unsignedBigInteger('amount');
            $table->text('description');
            $table->string('reference')->nullable();
            // manual / invoice / expense / salary
            $table->string('source_type', 20)->default('manual');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['hkd_profile_id', 'period']);
            $table->index(['account_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hkd_cash_transactions');
        Schema::dropIfExists('hkd_cash_accounts');
    }
};
