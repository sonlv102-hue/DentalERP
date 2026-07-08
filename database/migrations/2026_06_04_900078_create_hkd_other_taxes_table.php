<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // S3a — Sổ theo dõi nghĩa vụ thuế khác
        Schema::create('hkd_other_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hkd_profile_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);        // YYYY-MM
            $table->string('tax_type');         // e.g. "Thuế môn bài", "Thuế TTĐB", "Phí/lệ phí"
            $table->unsignedBigInteger('taxable_amount')->nullable();
            $table->decimal('tax_rate', 5, 4)->nullable();
            $table->unsignedBigInteger('tax_amount');
            $table->date('due_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['hkd_profile_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hkd_other_taxes');
    }
};
