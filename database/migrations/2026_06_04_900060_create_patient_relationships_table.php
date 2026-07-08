<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('relationship_type', 20); // parent/child/spouse/sibling/referrer
            $table->decimal('referral_rate', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->unique(['patient_id', 'related_patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_relationships');
    }
};
