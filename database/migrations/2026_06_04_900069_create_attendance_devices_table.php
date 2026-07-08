<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('ip', 45);                          // IPv4 or IPv6
            $table->unsignedSmallInteger('port')->default(4370);
            $table->string('password', 20)->default('');       // device auth password
            $table->string('serial_number', 50)->nullable();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('last_sync_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        // Add ZKTeco user PIN mapping to employees
        Schema::table('employees', function (Blueprint $table) {
            $table->string('zk_user_pin', 20)->nullable()->after('license_number');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('zk_user_pin');
        });
        Schema::dropIfExists('attendance_devices');
    }
};
