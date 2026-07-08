<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_account_id')->references('id')->on('fund_accounts')->restrictOnDelete();
            $table->foreignId('to_account_id')->references('id')->on('fund_accounts')->restrictOnDelete();
            $table->bigInteger('amount');
            $table->date('transfer_date');
            $table->string('description')->nullable();
            $table->string('reference', 100)->nullable();
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transfers');
    }
};
