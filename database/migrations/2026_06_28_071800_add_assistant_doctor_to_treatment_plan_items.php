<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('treatment_plan_items', function (Blueprint $table) {
            $table->foreignId('assistant_doctor_id')->nullable()->constrained('employees')->nullOnDelete()->after('responsible_doctor_id');
        });
    }

    public function down(): void
    {
        Schema::table('treatment_plan_items', function (Blueprint $table) {
            $table->dropForeign(['assistant_doctor_id']);
            $table->dropColumn('assistant_doctor_id');
        });
    }
};
