<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_payments', function (Blueprint $table) {
            $table->foreignId('fund_account_id')->nullable()->after('method')
                ->constrained('fund_accounts')->nullOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('fund_account_id')->nullable()->after('payment_method')
                ->constrained('fund_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patient_payments', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\FundAccount::class);
            $table->dropColumn('fund_account_id');
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\FundAccount::class);
            $table->dropColumn('fund_account_id');
        });
    }
};
