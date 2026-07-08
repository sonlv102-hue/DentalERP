<template>
    <AppLayout :title="employee ? `Cập nhật — ${employee.code}` : 'Thêm cán bộ'">
        <div class="max-w-5xl mx-auto p-6 space-y-5">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">
                        {{ employee ? 'Cập nhật hồ sơ cán bộ' : 'Thêm cán bộ' }}
                    </h1>
                    <p v-if="employee" class="text-sm text-gray-400 mt-0.5">{{ employee.code }}</p>
                    <p class="text-sm text-gray-500 mt-0.5">Quản lý thông tin nhân sự của phòng khám</p>
                </div>
                <Link :href="route('employees.index')" class="text-sm text-gray-500 hover:text-gray-700">← Danh sách</Link>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <EmployeeFormBasicSection
                    :form="form" :employee="employee" :errors="errors"
                    :branches="branches" :departments="departments"
                    :role-types="roleTypes" :users="users"
                />
                <EmployeeFormContractSection
                    :form="form" :errors="errors"
                    :contract-types="contractTypes" :employment-statuses="employmentStatuses"
                />
                <EmployeeFormSalarySection :form="form" :errors="errors" />
                <EmployeeFormAllowanceSection :form="form" :errors="errors" />
                <EmployeeFormBenefitsSection :form="form" :errors="errors" />
                <EmployeeFormDentalSection
                    :form="form" :errors="errors"
                    :dental-roles="dentalRoles" :managers="managers"
                />
                <EmployeeFormAddressSection :form="form" :errors="errors" />

                <!-- Summary boxes -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl border p-4 space-y-1">
                        <p class="text-xs text-gray-500 font-medium">Tổng thu nhập (Gross)</p>
                        <p class="text-xl font-bold text-gray-900">{{ formatVnd(grossIncome) }}</p>
                        <p class="text-xs text-gray-400">Lương + phụ cấp + trợ cấp. Không gồm KPI biến động.</p>
                    </div>
                    <div class="bg-white rounded-xl border p-4 space-y-1">
                        <p class="text-xs text-gray-500 font-medium">Tổng phụ cấp & trợ cấp</p>
                        <p class="text-xl font-bold text-emerald-700">{{ formatVnd(totalAllowances) }}</p>
                        <p class="text-xs text-gray-400">Tất cả phụ cấp & trợ cấp cộng lại</p>
                    </div>
                    <div class="bg-white rounded-xl border p-4 space-y-1">
                        <p class="text-xs text-gray-500 font-medium">Trạng thái BHXH</p>
                        <p class="text-xl font-bold" :class="form.social_insurance_enabled ? 'text-green-600' : 'text-gray-400'">
                            {{ form.social_insurance_enabled ? 'Đang tham gia' : 'Không tham gia' }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ form.social_insurance_enabled ? 'Đóng đầy đủ BHXH/BHYT/BHTN' : 'Chưa bật đóng bảo hiểm' }}
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-2">
                    <Link :href="route('employees.index')"
                        class="px-5 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Hủy
                    </Link>
                    <button type="submit" :disabled="submitting"
                        class="px-5 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                        {{ employee ? 'Cập nhật' : 'Lưu hồ sơ' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, computed, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import EmployeeFormBasicSection    from './EmployeeFormBasicSection.vue';
import EmployeeFormContractSection from './EmployeeFormContractSection.vue';
import EmployeeFormSalarySection   from './EmployeeFormSalarySection.vue';
import EmployeeFormAllowanceSection from './EmployeeFormAllowanceSection.vue';
import EmployeeFormBenefitsSection from './EmployeeFormBenefitsSection.vue';
import EmployeeFormDentalSection   from './EmployeeFormDentalSection.vue';
import EmployeeFormAddressSection  from './EmployeeFormAddressSection.vue';

const props = defineProps({
    employee:           Object,
    branches:           Array,
    departments:        Array,
    users:              Array,
    managers:           Array,
    contractTypes:      Array,
    employmentStatuses: Array,
    roleTypes:          Array,
    dentalRoles:        Array,
});

const e = props.employee;
const submitting = ref(false);
const errors = ref({});

const form = reactive({
    branch_id: e?.branch_id ?? '',         department_id: e?.department_id ?? null,
    full_name: e?.full_name ?? '',         position: e?.position ?? '',
    phone: e?.phone ?? '',                 email: e?.email ?? '',
    date_of_birth: e?.date_of_birth ?? '', gender: e?.gender ?? '',
    role_type: e?.role_type ?? '',         specialization: e?.specialization ?? '',
    license_number: e?.license_number ?? '', user_id: e?.user_id ?? null,
    is_active: e?.is_active ?? true,
    // Contract
    start_date: e?.start_date ?? '',       contract_type: e?.contract_type ?? '',
    employment_status: e?.employment_status ?? 'active',
    // Salary
    base_salary: e?.base_salary ?? 0,     social_insurance_enabled: e?.social_insurance_enabled ?? false,
    dependents_count: e?.dependents_count ?? 0, personal_tax_code: e?.personal_tax_code ?? '',
    standard_working_days: e?.standard_working_days ?? 26,
    // Allowances
    responsibility_allowance: e?.responsibility_allowance ?? 0, fixed_allowance: e?.fixed_allowance ?? 0,
    lunch_allowance: e?.lunch_allowance ?? 0, travel_allowance: e?.travel_allowance ?? 0,
    phone_allowance: e?.phone_allowance ?? 0,
    // Dental
    dental_role: e?.dental_role ?? '',           dental_specialty: e?.dental_specialty ?? '',
    practice_certificate: e?.practice_certificate ?? '', dentist_license_code: e?.dentist_license_code ?? '',
    years_of_experience: e?.years_of_experience ?? null, xray_scan_skill: e?.xray_scan_skill ?? '',
    clinical_permission: e?.clinical_permission ?? '', work_schedule: e?.work_schedule ?? '',
    assigned_chair_room: e?.assigned_chair_room ?? '', default_kpi_rate: e?.default_kpi_rate ?? null,
    support_step_rate: e?.support_step_rate ?? null,   direct_manager_id: e?.direct_manager_id ?? null,
    // Location
    permanent_address: e?.permanent_address ?? '', notes: e?.notes ?? '',
});

const grossIncome = computed(() =>
    (form.base_salary || 0) + (form.responsibility_allowance || 0) + (form.fixed_allowance || 0)
    + (form.lunch_allowance || 0) + (form.travel_allowance || 0) + (form.phone_allowance || 0)
);
const totalAllowances = computed(() =>
    (form.responsibility_allowance || 0) + (form.fixed_allowance || 0)
    + (form.lunch_allowance || 0) + (form.travel_allowance || 0) + (form.phone_allowance || 0)
);

function formatVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }

function submit() {
    submitting.value = true;
    errors.value = {};
    const method = e ? 'put' : 'post';
    const url    = e ? route('employees.update', e.id) : route('employees.store');
    router[method](url, form, {
        onError: (errs) => { errors.value = errs; submitting.value = false; },
        onFinish: () => { submitting.value = false; },
    });
}
</script>
