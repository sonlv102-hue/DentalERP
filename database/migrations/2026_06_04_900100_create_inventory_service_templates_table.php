<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Định mức vật tư tiêu hao cho mỗi công đoạn dịch vụ
        Schema::create('inventory_service_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('dental_services')->cascadeOnDelete();
            // nullable — nếu null áp dụng cho toàn bộ dịch vụ, không riêng công đoạn
            $table->foreignId('service_step_id')->nullable()->constrained('dental_service_steps')->nullOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty_per_execution', 12, 3)->default(1);
            $table->string('notes', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['service_id', 'service_step_id', 'inventory_item_id'], 'inv_svc_tpl_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_service_templates');
    }
};
