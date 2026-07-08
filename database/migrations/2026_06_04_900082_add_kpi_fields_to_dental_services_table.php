<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dental_services', function (Blueprint $table) {
            // Group / category consolidation (rename category → service_group)
            $table->string('service_group', 100)->nullable()->after('category');
            $table->integer('estimated_sessions')->default(1)->after('duration_minutes');

            // KPI configuration
            $table->string('kpi_base_type', 20)->default('revenue')->after('estimated_sessions'); // revenue | gross_margin | fixed
            $table->decimal('kpi_rate', 5, 4)->default(0)->after('kpi_base_type');      // 0.0000–1.0000
            $table->bigInteger('fixed_kpi_amount')->default(0)->after('kpi_rate');       // used when kpi_base_type=fixed
            $table->text('notes')->nullable()->after('fixed_kpi_amount');
        });
    }

    public function down(): void
    {
        Schema::table('dental_services', function (Blueprint $table) {
            $table->dropColumn(['service_group', 'estimated_sessions', 'kpi_base_type', 'kpi_rate', 'fixed_kpi_amount', 'notes']);
        });
    }
};
