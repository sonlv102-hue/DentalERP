<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Basic info extensions
            $table->string('email')->nullable()->after('phone');
            $table->date('date_of_birth')->nullable()->after('email');
            $table->string('gender')->nullable()->after('date_of_birth'); // male/female/other
            $table->string('position')->nullable()->after('department_id'); // free-text job title

            // Contract & employment
            $table->date('start_date')->nullable()->after('position');
            $table->string('contract_type')->nullable()->after('start_date');
            $table->string('employment_status')->default('active')->after('contract_type');

            // Salary & insurance
            $table->unsignedBigInteger('base_salary')->default(0)->after('employment_status');
            $table->boolean('social_insurance_enabled')->default(false)->after('base_salary');
            $table->smallInteger('dependents_count')->default(0)->after('social_insurance_enabled');
            $table->string('personal_tax_code', 13)->nullable()->after('dependents_count');
            $table->smallInteger('standard_working_days')->default(26)->after('personal_tax_code');

            // Allowances
            $table->unsignedBigInteger('responsibility_allowance')->default(0)->after('standard_working_days');
            $table->unsignedBigInteger('fixed_allowance')->default(0)->after('responsibility_allowance');
            $table->unsignedBigInteger('lunch_allowance')->default(0)->after('fixed_allowance');
            $table->unsignedBigInteger('travel_allowance')->default(0)->after('lunch_allowance');
            $table->unsignedBigInteger('phone_allowance')->default(0)->after('travel_allowance');

            // Dental professional profile
            $table->string('dental_role')->nullable()->after('phone_allowance');
            $table->string('dental_specialty')->nullable()->after('dental_role');
            $table->string('practice_certificate')->nullable()->after('dental_specialty');
            $table->unsignedSmallInteger('years_of_experience')->nullable()->after('practice_certificate');
            $table->string('dentist_license_code')->nullable()->after('years_of_experience');
            $table->string('xray_scan_skill')->nullable()->after('dentist_license_code');
            $table->string('clinical_permission')->nullable()->after('xray_scan_skill');
            $table->string('work_schedule')->nullable()->after('clinical_permission');
            $table->string('assigned_chair_room')->nullable()->after('work_schedule');
            $table->decimal('default_kpi_rate', 5, 2)->nullable()->after('assigned_chair_room');
            $table->decimal('support_step_rate', 5, 2)->nullable()->after('default_kpi_rate');
            $table->unsignedBigInteger('direct_manager_id')->nullable()->after('support_step_rate');

            // Location & notes
            $table->text('permanent_address')->nullable()->after('direct_manager_id');
            $table->text('notes')->nullable()->after('permanent_address');

            $table->foreign('direct_manager_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['direct_manager_id']);
            $table->dropColumn([
                'email', 'date_of_birth', 'gender', 'position',
                'start_date', 'contract_type', 'employment_status',
                'base_salary', 'social_insurance_enabled', 'dependents_count',
                'personal_tax_code', 'standard_working_days',
                'responsibility_allowance', 'fixed_allowance', 'lunch_allowance',
                'travel_allowance', 'phone_allowance',
                'dental_role', 'dental_specialty', 'practice_certificate',
                'years_of_experience', 'dentist_license_code', 'xray_scan_skill',
                'clinical_permission', 'work_schedule', 'assigned_chair_room',
                'default_kpi_rate', 'support_step_rate', 'direct_manager_id',
                'permanent_address', 'notes',
            ]);
        });
    }
};
