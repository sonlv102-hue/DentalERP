<template>
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                <h2 class="font-bold text-gray-800">Cập nhật lương — {{ form.employee_name }}</h2>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
            </div>

            <div class="p-5 space-y-4">
                <!-- Toggle BHXH -->
                <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-800">Đóng BHXH / BHYT / BHTN</p>
                        <p class="text-xs text-gray-500">Bật để tính bảo hiểm xã hội</p>
                    </div>
                    <button @click="form.social_insurance_enabled = !form.social_insurance_enabled"
                        :class="['relative w-12 h-6 rounded-full transition-colors', form.social_insurance_enabled ? 'bg-primary-600' : 'bg-gray-300']">
                        <span :class="['absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-transform', form.social_insurance_enabled ? 'translate-x-7' : 'translate-x-1']" />
                    </button>
                </div>

                <!-- Working days & salary -->
                <div>
                    <p class="text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide">Ngày công & Lương</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Lương cơ bản (đóng BH)</label>
                            <input v-model.number="form.base_salary" type="number" min="0" @change="onBaseSalaryChange"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Ngày công chuẩn</label>
                            <input v-model.number="form.standard_working_days" type="number" min="1" max="31"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Ngày công thực tế</label>
                            <input v-model.number="form.actual_working_days" type="number" min="0" max="31" step="0.5" @input="onDaysChange"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Tỷ lệ công</label>
                            <input :value="pct(form.workday_ratio)" readonly
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-600" />
                        </div>
                    </div>
                </div>

                <!-- Insurance box -->
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-amber-800">Số tiền BHXH tháng này (có thể sửa)</p>
                        <label class="flex items-center gap-1 cursor-pointer">
                            <input type="checkbox" v-model="form.insurance_manual_override" class="rounded" />
                            <span class="text-xs text-amber-700">Ghi đè thủ công</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <template v-for="f in insuranceFields" :key="f.key">
                            <div>
                                <label class="text-gray-600 block mb-0.5">{{ f.label }}</label>
                                <input v-model.number="form[f.key]" type="number" min="0"
                                    :disabled="!form.social_insurance_enabled || !form.insurance_manual_override"
                                    class="w-full border border-amber-300 rounded px-2 py-1 bg-white disabled:bg-gray-100 disabled:text-gray-400" />
                            </div>
                        </template>
                    </div>
                    <p class="text-xs text-amber-600 mt-1">Mặc định: theo công thức. Đặt về 0 nếu NV không đóng tháng này.</p>
                </div>

                <!-- PIT override -->
                <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-red-800">Ghi đè Thuế TNCN thủ công (Admin)</p>
                        <button @click="form.pit_manual_override = !form.pit_manual_override"
                            :class="['relative w-10 h-5 rounded-full transition-colors', form.pit_manual_override ? 'bg-red-500' : 'bg-gray-300']">
                            <span :class="['absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform', form.pit_manual_override ? 'translate-x-5' : 'translate-x-0.5']" />
                        </button>
                    </div>
                    <div v-if="form.pit_manual_override">
                        <label class="text-xs text-red-700 block mb-1">Số thuế TNCN (VND)</label>
                        <input v-model.number="form.pit_manual_amount" type="number" min="0"
                            class="w-full border border-red-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                </div>

                <!-- Allowances -->
                <div>
                    <p class="text-xs font-semibold text-gray-600 mb-1 uppercase tracking-wide">Phụ cấp lương (tính BHXH)</p>
                    <p class="text-xs text-gray-400 mb-2">PC Cố định, PC Trách nhiệm</p>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div v-for="f in salaryAllowanceFields" :key="f.key">
                            <label class="text-xs text-gray-500 block mb-1">{{ f.label }}</label>
                            <input v-model.number="form[f.key]" type="number" min="0"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <p class="text-xs font-semibold text-gray-600 mb-1 uppercase tracking-wide">Hỗ trợ phúc lợi (không BHXH)</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div v-for="f in welfareFields" :key="f.key">
                            <label class="text-xs text-gray-500 block mb-1">{{ f.label }}</label>
                            <input v-model.number="form[f.key]" type="number" min="0"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="bg-gray-50 rounded-lg p-3 text-sm space-y-1.5">
                    <div class="flex justify-between"><span class="text-gray-600">Tổng thu nhập (gross)</span><span class="font-semibold">{{ vnd(grossPreview) }}</span></div>
                    <div class="flex justify-between text-red-600"><span>Tổng BH nhân viên</span><span>— {{ vnd(empInsPreview) }}</span></div>
                    <div class="flex justify-between text-red-600"><span>Thuế TNCN</span><span>— {{ vnd(pitPreview) }}</span></div>
                    <div class="border-t border-gray-200 pt-1.5 flex justify-between font-bold text-green-700">
                        <span>Thực lĩnh</span><span>{{ vnd(netPreview) }}</span>
                    </div>
                </div>

                <!-- Note -->
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Ghi chú</label>
                    <textarea v-model="form.note" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none" />
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200 sticky bottom-0 bg-white">
                <button @click="$emit('close')" class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Hủy</button>
                <button @click="save" :disabled="saving" class="px-5 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50">
                    {{ saving ? 'Đang lưu...' : 'Lưu cập nhật' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, computed, ref } from 'vue';
import axios from 'axios';

const props = defineProps({ item: Object, payrollId: Number });
const emit  = defineEmits(['close', 'saved']);

const form  = reactive({ ...props.item });
const saving = ref(false);

const insuranceFields = [
    { key: 'company_social_insurance',       label: 'BHXH CP DN' },
    { key: 'company_health_insurance',       label: 'BHYT CP DN' },
    { key: 'company_unemployment_insurance', label: 'BHTN CP DN' },
    { key: 'employee_social_insurance',      label: 'BHXH NV' },
    { key: 'employee_health_insurance',      label: 'BHYT NV' },
    { key: 'employee_unemployment_insurance',label: 'BHTN NV' },
];
const salaryAllowanceFields = [
    { key: 'fixed_allowance',            label: 'PC Cố định' },
    { key: 'responsibility_allowance',   label: 'PC Trách nhiệm' },
];
const welfareFields = [
    { key: 'lunch_allowance',           label: 'PC Ăn trưa' },
    { key: 'phone_allowance',           label: 'PC Điện thoại' },
    { key: 'travel_allowance',          label: 'PC Xăng xe' },
    { key: 'performance_kpi_amount',    label: 'KPI / HQCV' },
];

function onDaysChange() {
    const std = form.standard_working_days || 26;
    form.workday_ratio = std > 0 ? Math.round(form.actual_working_days / std * 10000) / 10000 : 0;
}
function onBaseSalaryChange() {
    if (!form.insurance_manual_override) form.insurance_salary = form.base_salary;
}

const grossPreview = computed(() =>
    (form.salary_by_workday || Math.round(form.base_salary * form.workday_ratio))
    + (form.fixed_allowance || 0) + (form.responsibility_allowance || 0)
    + (form.lunch_allowance || 0) + (form.phone_allowance || 0)
    + (form.travel_allowance || 0) + (form.performance_kpi_amount || 0)
    + (form.other_allowance || 0)
);
const empInsPreview = computed(() => form.social_insurance_enabled
    ? (form.employee_social_insurance || 0) + (form.employee_health_insurance || 0) + (form.employee_unemployment_insurance || 0)
    : 0
);
const pitPreview = computed(() => {
    if (form.pit_manual_override) return form.pit_manual_amount || 0;
    const net = Math.max(0, grossPreview.value - empInsPreview.value - (form.family_deduction || 0) - (form.dependent_deduction || 0));
    return calcPit(net);
});
const netPreview = computed(() => Math.max(0, grossPreview.value - empInsPreview.value - pitPreview.value));

function calcPit(net) {
    if (net <= 0) return 0;
    const brackets = [[5e6, .05],[10e6,.10],[18e6,.15],[32e6,.20],[52e6,.25],[80e6,.30],[Infinity,.35]];
    let tax = 0, prev = 0;
    for (const [c, r] of brackets) {
        const t = Math.min(net - prev, c - prev);
        if (t <= 0) break;
        tax += t * r; prev = c;
        if (prev >= net) break;
    }
    return Math.round(tax);
}

function vnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
function pct(v) { return v ? (Number(v) * 100).toFixed(1) + '%' : '0%'; }

async function save() {
    saving.value = true;
    try {
        const res = await axios.put(route('accounting.payrolls.items.update', [props.payrollId, form.id]), form);
        emit('saved', res.data);
    } catch (e) {
        alert(e.response?.data?.message ?? 'Lỗi khi lưu. Vui lòng thử lại.');
    } finally {
        saving.value = false;
    }
}
</script>
