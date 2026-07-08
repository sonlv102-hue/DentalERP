<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_quality_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_code', 50)->unique();
            $table->string('rule_name', 200);
            // quality_factor applied (0.0 = zero out, 0.5 = halve, 1.0 = no change)
            $table->decimal('quality_factor', 3, 2)->default(1.00);
            $table->boolean('hold_kpi')->default(false);    // suspend until resolved
            $table->boolean('reverse_kpi')->default(false); // claw back paid KPI
            $table->string('trigger_event', 100)->nullable(); // refund | redo | complaint | missing_attachment | protocol_violation
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_quality_rules');
    }
};
