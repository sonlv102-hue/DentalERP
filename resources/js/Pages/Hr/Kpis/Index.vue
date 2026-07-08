<template>
    <AppLayout title="KPI nhân viên">
        <div class="max-w-5xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Mục tiêu KPI nhân viên</h1>
                <input v-model="period" @change="applyFilter" type="month" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nhân viên</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Mục tiêu DT</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Mục tiêu ca</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Thưởng khi đạt</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="r in rows" :key="r.employee_id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ r.employee }}</p>
                                <p class="text-xs text-gray-400 font-mono">{{ r.employee_code }} · {{ r.role_type }}</p>
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-gray-600">
                                {{ r.revenue_target ? formatVnd(r.revenue_target) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ r.case_target || '—' }}</td>
                            <td class="px-4 py-3 text-right font-mono text-green-600">
                                {{ r.bonus_amount ? formatVnd(r.bonus_amount) : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="r.kpi_id" :class="r.status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                    class="text-xs font-medium px-2 py-0.5 rounded">{{ r.status_label }}</span>
                                <span v-else class="text-xs text-gray-300">Chưa đặt</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button @click="openEdit(r)" class="text-xs text-primary-600 hover:underline">
                                    {{ r.kpi_id ? 'Sửa' : 'Đặt KPI' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="modal.show" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">KPI: {{ modal.employee }}</h3>
                        <button @click="modal.show = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="save" class="p-5 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label">Mục tiêu doanh thu (₫) <span class="text-red-500">*</span></label>
                                <input v-model.number="modal.revenue_target" type="number" min="0" required class="input-field" />
                            </div>
                            <div>
                                <label class="label">Mục tiêu số ca <span class="text-red-500">*</span></label>
                                <input v-model.number="modal.case_target" type="number" min="0" required class="input-field" />
                            </div>
                        </div>
                        <div>
                            <label class="label">Thưởng khi đạt KPI (₫)</label>
                            <input v-model.number="modal.bonus_amount" type="number" min="0" class="input-field" />
                        </div>
                        <div>
                            <label class="label">Ghi chú</label>
                            <textarea v-model="modal.notes" rows="2" class="input-field" />
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button v-if="modal.kpi_id" type="button" @click="deleteKpi" class="px-4 py-2 text-sm border border-red-300 text-red-500 rounded-lg hover:bg-red-50">Xóa</button>
                            <button type="button" @click="modal.show = false" class="px-4 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="saving" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg disabled:opacity-50">Lưu KPI</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props  = defineProps({ rows: Array, period: String, filters: Object });
const period = ref(props.filters.period ?? '');
const saving = ref(false);
const modal  = reactive({ show: false, kpi_id: null, employee_id: null, employee: '', revenue_target: 0, case_target: 0, bonus_amount: 0, notes: '' });

function applyFilter() {
    router.get(route('hr.kpis.index'), { period: period.value }, { preserveState: true });
}

function openEdit(r) {
    Object.assign(modal, { show: true, kpi_id: r.kpi_id, employee_id: r.employee_id, employee: r.employee, revenue_target: r.revenue_target, case_target: r.case_target, bonus_amount: r.bonus_amount, notes: r.notes ?? '' });
}

function save() {
    saving.value = true;
    const payload = { employee_id: modal.employee_id, period: period.value, revenue_target: modal.revenue_target, case_target: modal.case_target, bonus_amount: modal.bonus_amount, notes: modal.notes };
    const url    = modal.kpi_id ? route('hr.kpis.update', modal.kpi_id) : route('hr.kpis.store');
    const method = modal.kpi_id ? 'put' : 'post';
    router[method](url, payload, { onSuccess: () => { modal.show = false; }, onFinish: () => { saving.value = false; } });
}

function deleteKpi() {
    if (confirm('Xóa KPI này?')) router.delete(route('hr.kpis.destroy', modal.kpi_id), { onSuccess: () => { modal.show = false; } });
}

function formatVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
</script>

<style scoped>
.label       { @apply block text-sm font-medium text-gray-700 mb-1; }
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
