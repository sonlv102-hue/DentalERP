<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hkd_period_closes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hkd_profile_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);            // YYYY-MM
            // open / closed
            $table->string('status', 10)->default('open');
            $table->timestampTz('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('snapshot_data')->nullable();       // serialized summary at close time
            $table->string('snapshot_pdf_path')->nullable(); // storage path
            $table->text('unlock_reason')->nullable();
            $table->timestampTz('unlocked_at')->nullable();
            $table->foreignId('unlocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['hkd_profile_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hkd_period_closes');
    }
};
