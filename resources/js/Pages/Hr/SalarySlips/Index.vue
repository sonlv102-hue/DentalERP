<template>
    <AppLayout title="Phiếu lương">
        <div class="max-w-5xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Phiếu lương nhân viên</h1>
                <button @click="showGenerate = true" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                    + Tạo phiếu lương
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="period" @keyup.enter="applyFilters" type="month" class="filter-input" />
                <select v-model="employeeId" @change="applyFilters" class="filter-input">
                    <option value="">Tất cả nhân viên</option>
                    <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }} ({{ e.code }})</option>
                </select>
                <select v-model="status" @change="applyFilters" class="filter-input">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kỳ</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nhân viên</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Lương CB</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Hoa hồng</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Thực nhận</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="slips.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">Chưa có phiếu lương nào</td>
                        </tr>
                        <tr v-for="s in slips.data" :key="s.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-gray-700">{{ s.period }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ s.employee }}</p>
                                <p class="text-xs text-gray-400 font-mono">{{ s.employee_code }}</p>
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-gray-600">{{ formatVnd(s.base_salary) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-green-600">{{ formatVnd(s.commission_total) }}</td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-gray-800">{{ formatVnd(s.net_salary) }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="s.status_color">{{ s.status_label }}</StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('hr.salary-slips.show', s.id)" class="text-xs text-primary-600 hover:underline">Xem</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="slips.links" />
        </div>

        <!-- Generate Modal -->
        <Teleport to="body">
            <div v-if="showGenerate" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Tạo phiếu lương</h3>
                        <button @click="showGenerate = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="generate" class="p-5 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <label class="label">Nhân viên <span class="text-red-500">*</span></label>
                                <select v-model="genForm.employee_id" required class="input-field" @change="fetchPreview">
                                    <option :value="null">-- Chọn --</option>
                                    <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="label">Kỳ lương <span class="text-red-500">*</span></label>
                                <div class="flex gap-2">
                                    <input v-model="genForm.period" type="month" required class="input-field" @change="fetchPreview" />
                                    <button type="button" @click="fetchPreview" :disabled="previewing"
                                        class="px-3 py-2 text-xs bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 whitespace-nowrap">
                                        {{ previewing ? '...' : 'Tự động' }}
                                    </button>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Nhấn "Tự động" để điền từ hợp đồng + bảng công</p>
                            </div>
                            <div>
                                <label class="label">Lương cơ bản (₫) <span class="text-red-500">*</span></label>
                                <input v-model.number="genForm.base_salary" type="number" min="0" required class="input-field" />
                            </div>
                            <div>
                                <label class="label">Khấu trừ (₫)</label>
                                <input v-model.number="genForm.deductions" type="number" min="0" class="input-field" />
                            </div>
                            <div>
                                <label class="label">Giờ tăng ca (h)</label>
                                <input v-model.number="genForm.ot_hours" type="number" min="0" step="0.5" class="input-field" />
                            </div>
                            <div>
                                <label class="label">Đơn giá OT (₫/h)</label>
                                <input v-model.number="genForm.ot_rate" type="number" min="0" class="input-field" />
                            </div>
                        </div>
                        <!-- Preview net -->
                        <div class="bg-indigo-50 rounded-lg px-3 py-2 flex justify-between text-sm">
                            <span class="text-gray-600">Dự tính thực nhận:</span>
                            <span class="font-bold text-indigo-700">{{ formatVnd(netPreview()) }}</span>
                        </div>
                        <div>
                            <label class="label">Ghi chú</label>
                            <textarea v-model="genForm.notes" rows="2" class="input-field" />
                        </div>
                        <div class="flex justify-end gap-3 pt-1">
                            <button type="button" @click="showGenerate = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="genForm.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                                Tạo phiếu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';

const props = defineProps({ slips: Object, filters: Object, employees: Array, statuses: Array });

const period     = ref(props.filters.period ?? '');
const employeeId = ref(props.filters.employee_id ?? '');
const status     = ref(props.filters.status ?? '');
const showGenerate = ref(false);

const genForm = useForm({ employee_id: null, period: '', base_salary: 0, ot_hours: 0, ot_rate: 0, deductions: 0, notes: '' });
const previewing = ref(false);

function applyFilters() {
    router.get(route('hr.salary-slips.index'), { period: period.value, employee_id: employeeId.value, status: status.value }, { preserveState: true });
}

async function fetchPreview() {
    if (!genForm.employee_id || !genForm.period) return;
    previewing.value = true;
    try {
        const res = await fetch(route('hr.salary-slips.preview') + `?employee_id=${genForm.employee_id}&period=${genForm.period}`);
        const data = await res.json();
        genForm.base_salary = data.base_salary;
        genForm.ot_hours    = data.ot_hours;
        genForm.ot_rate     = data.ot_rate;
    } finally {
        previewing.value = false;
    }
}

function generate() {
    genForm.post(route('hr.salary-slips.generate'), { onSuccess: () => { showGenerate.value = false; } });
}

function netPreview() {
    return (genForm.base_salary || 0) + Math.round((genForm.ot_hours || 0) * (genForm.ot_rate || 0)) - (genForm.deductions || 0);
}

function formatVnd(v) {
    return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫';
}
</script>

<style scoped>
.filter-input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
.input-field  { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
.label        { @apply block text-sm font-medium text-gray-700 mb-1; }
</style>
