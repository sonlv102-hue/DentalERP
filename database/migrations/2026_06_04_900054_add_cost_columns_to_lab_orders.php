<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->bigInteger('estimated_cost')->default(0)->after('notes');
            $table->bigInteger('cost_paid')->default(0)->after('estimated_cost');
        });
    }

    public function down(): void
    {
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->dropColumn(['estimated_cost', 'cost_paid']);
        });
    }
};
