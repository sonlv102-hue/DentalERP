<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7); // YYYY-MM
            $table->foreignId('reviewer_id')->references('id')->on('users')->restrictOnDelete();
            $table->unsignedTinyInteger('overall_score')->default(3); // 1-5
            $table->unsignedTinyInteger('punctuality_score')->default(3);
            $table->unsignedTinyInteger('quality_score')->default(3);
            $table->unsignedTinyInteger('teamwork_score')->default(3);
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('goals')->nullable();
            $table->string('status', 20)->default('draft'); // draft/finalized
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['employee_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
