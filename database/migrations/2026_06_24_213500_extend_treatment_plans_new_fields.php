<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatment_plans', function (Blueprint $table) {
            $table->string('diagnosis')->nullable();
            $table->text('chief_complaint')->nullable();
            $table->string('treatment_goal')->nullable();
            $table->date('start_date')->nullable();
            $table->date('expected_end_date')->nullable();
            $table->unsignedInteger('estimated_sessions')->nullable();
            $table->string('frequency')->nullable();
            $table->string('priority', 50)->default('normal'); // normal | urgent | emergency
        });

        Schema::table('treatment_plan_items', function (Blueprint $table) {
            $table->string('diagnosis')->nullable();
            $table->bigInteger('discount')->default(0);
            $table->bigInteger('amount')->default(0);
            $table->unsignedInteger('estimated_sessions')->nullable();
            $table->string('stage_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('treatment_plan_items', function (Blueprint $table) {
            $table->dropColumn(['diagnosis', 'discount', 'amount', 'estimated_sessions', 'stage_name']);
        });

        Schema::table('treatment_plans', function (Blueprint $table) {
            $table->dropColumn(['diagnosis', 'chief_complaint', 'treatment_goal', 'start_date', 'expected_end_date', 'estimated_sessions', 'frequency', 'priority']);
        });
    }
};
