<template>
    <AppLayout :title="`Công đoạn & Chi phí — ${service.name}`">
        <div class="max-w-6xl mx-auto p-6 space-y-6">
            <div class="flex items-center gap-4">
                <a :href="route('catalog.services.index')" class="text-sm text-blue-600 hover:underline">← Dịch vụ</a>
                <h1 class="text-xl font-bold text-gray-800">{{ service.name }} — Công đoạn & Chi phí KPI</h1>
            </div>

            <!-- KPI summary card -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800 flex gap-6">
                <span>Cách tính KPI: <strong>{{ kpiBaseLabel }}</strong></span>
                <span>Tỷ lệ: <strong>{{ (service.kpi_rate * 100).toFixed(1) }}%</strong></span>
                <span v-if="service.kpi_base_type === 'fixed'">Cố định: <strong>{{ formatVnd(service.fixed_kpi_amount) }}</strong></span>
                <span>Tổng % công đoạn: <strong :class="totalStepPercent > 100 ? 'text-red-600' : 'text-green-700'">{{ totalStepPercent.toFixed(1) }}%</strong></span>
            </div>

            <!-- Steps -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 border-b">
                    <h2 class="font-semibold text-gray-700">Công đoạn dịch vụ</h2>
                    <button @click="openStepCreate" class="px-3 py-1.5 bg-primary-600 text-white rounded-lg text-sm">+ Thêm công đoạn</button>
                </div>
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">#</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Tên công đoạn</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Vai trò</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">% KPI</th>
                            <th class="px-4 py-2 text-center text-xs text-gray-500">Trừ BS chính</th>
                            <th class="px-4 py-2 text-center text-xs text-gray-500">KT chất lượng</th>
                            <th class="px-4 py-2 text-center text-xs text-gray-500">Cần file</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="s in steps" :key="s.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-500">{{ s.step_order }}</td>
                            <td class="px-4 py-2 text-sm font-medium text-gray-800">{{ s.step_name }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500">{{ roleLabel(s.default_role) }}</td>
                            <td class="px-4 py-2 text-sm text-right font-mono">{{ s.kpi_share_percent }}%</td>
                            <td class="px-4 py-2 text-center">
                                <span :class="s.deduct_from_main_doctor ? 'text-orange-500' : 'text-gray-300'">{{ s.deduct_from_main_doctor ? '✓' : '✗' }}</span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span :class="s.require_quality_check ? 'text-blue-500' : 'text-gray-300'">{{ s.require_quality_check ? '✓' : '✗' }}</span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span :class="s.require_attachment ? 'text-purple-500' : 'text-gray-300'">{{ s.require_attachment ? '✓' : '✗' }}</span>
                            </td>
                            <td class="px-4 py-2 text-right space-x-2">
                                <button @click="openStepEdit(s)" class="text-xs text-blue-600 hover:underline">Sửa</button>
                                <button @click="deleteStep(s)" class="text-xs text-red-500 hover:underline">Xóa</button>
                            </td>
                        </tr>
                        <tr v-if="!steps.length">
                            <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-400">Chưa có công đoạn</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Costs -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 border-b">
                    <h2 class="font-semibold text-gray-700">Chi phí trực tiếp</h2>
                    <button @click="openCostCreate" class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-sm">+ Thêm chi phí</button>
                </div>
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Loại</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Tên chi phí</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Chi phí chuẩn</th>
                            <th class="px-4 py-2 text-center text-xs text-gray-500">Loại trừ KPI</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="c in costs" :key="c.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-xs text-gray-600 bg-gray-50 rounded">{{ c.cost_type_label }}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">{{ c.cost_name }}</td>
                            <td class="px-4 py-2 text-sm text-right font-mono">{{ formatVnd(c.standard_cost) }}</td>
                            <td class="px-4 py-2 text-center">
                                <span :class="c.is_excluded_from_kpi_base ? 'text-red-500' : 'text-gray-300'">{{ c.is_excluded_from_kpi_base ? '✓ Loại trừ' : '—' }}</span>
                            </td>
                            <td class="px-4 py-2 text-right space-x-2">
                                <button @click="openCostEdit(c)" class="text-xs text-blue-600 hover:underline">Sửa</button>
                                <button @click="deleteCost(c)" class="text-xs text-red-500 hover:underline">Xóa</button>
                            </td>
                        </tr>
                        <tr v-if="!costs.length">
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">Chưa có chi phí</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Step Modal -->
        <div v-if="showStepModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4">
                <h2 class="text-lg font-bold">{{ stepForm.id ? 'Sửa' : 'Thêm' }} công đoạn</h2>
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium mb-1">Tên công đoạn *</label>
                        <input v-model="stepForm.step_name" class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Thứ tự</label>
                        <input v-model.number="stepForm.step_order" type="number" min="0" class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Thời gian (phút)</label>
                        <input v-model.number="stepForm.estimated_minutes" type="number" min="0" class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium mb-1">Vai trò mặc định</label>
                        <select v-model="stepForm.default_role" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">-- Không chỉ định --</option>
                            <option v-for="r in roles" :key="r" :value="r">{{ roleLabel(r) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">% KPI công đoạn *</label>
                        <input v-model.number="stepForm.kpi_share_percent" type="number" min="0" max="100" step="0.5" class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="flex flex-col justify-end gap-2 pb-1">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="stepForm.deduct_from_main_doctor" />
                            Trừ KPI bác sĩ chính
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="stepForm.require_quality_check" />
                            Cần kiểm tra chất lượng
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="stepForm.require_attachment" />
                            Cần file đính kèm
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button @click="showStepModal = false" class="px-4 py-2 border rounded-lg text-sm">Hủy</button>
                    <button @click="submitStep" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium">{{ stepForm.id ? 'Cập nhật' : 'Thêm' }}</button>
                </div>
            </div>
        </div>

        <!-- Cost Modal -->
        <div v-if="showCostModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
                <h2 class="text-lg font-bold">{{ costForm.id ? 'Sửa' : 'Thêm' }} chi phí</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Loại chi phí *</label>
                        <select v-model="costForm.cost_type" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option v-for="t in cost_types" :key="t" :value="t">{{ costTypeLabel(t) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Tên chi phí *</label>
                        <input v-model="costForm.cost_name" class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Chi phí chuẩn (VNĐ) *</label>
                        <input v-model.number="costForm.standard_cost" type="number" min="0" class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" v-model="costForm.is_excluded_from_kpi_base" />
                        Loại trừ khỏi tính lãi gộp KPI
                    </label>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button @click="showCostModal = false" class="px-4 py-2 border rounded-lg text-sm">Hủy</button>
                    <button @click="submitCost" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium">{{ costForm.id ? 'Cập nhật' : 'Thêm' }}</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ service: Object, steps: Array, costs: Array, cost_types: Array, roles: Array });

const showStepModal = ref(false);
const showCostModal = ref(false);
const stepForm = ref({});
const costForm = ref({});

const totalStepPercent = computed(() => props.steps.reduce((sum, s) => sum + (s.kpi_share_percent || 0), 0));
const kpiBaseLabel = computed(() => ({ revenue: 'Theo doanh thu', gross_margin: 'Theo lãi gộp', fixed: 'Cố định' })[props.service.kpi_base_type] ?? '—');

function formatVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
function roleLabel(r) { return ({ counseling: 'Tư vấn', examination: 'Khám', imaging: 'Chụp phim', treatment_planning: 'Lập kế hoạch', main_treatment: 'Điều trị chính', chairside_assist: 'Phụ tá ghế', impression: 'Lấy dấu', prosthetics: 'Gắn phục hình', follow_up: 'Tái khám', aftercare: 'CSKH' })[r] ?? r ?? '—'; }
function costTypeLabel(t) { return ({ material: 'Vật tư', lab: 'Labo', implant_fixture: 'Trụ Implant', medicine: 'Thuốc', imaging: 'Chụp chiếu', chair_overhead: 'Chi phí ghế', other: 'Khác' })[t] ?? t; }

function openStepCreate() { stepForm.value = { step_name: '', step_order: props.steps.length + 1, default_role: '', estimated_minutes: 0, kpi_share_percent: 0, deduct_from_main_doctor: true, require_quality_check: false, require_attachment: false }; showStepModal.value = true; }
function openStepEdit(s) { stepForm.value = { ...s }; showStepModal.value = true; }
function submitStep() {
    if (stepForm.value.id) {
        router.put(route('dental.service-steps.update', stepForm.value.id), stepForm.value, { onSuccess: () => { showStepModal.value = false; } });
    } else {
        router.post(route('dental.services.steps.store', props.service.id), stepForm.value, { onSuccess: () => { showStepModal.value = false; } });
    }
}
function deleteStep(s) { if (!confirm(`Xóa công đoạn "${s.step_name}"?`)) return; router.delete(route('dental.service-steps.destroy', s.id)); }

function openCostCreate() { costForm.value = { cost_type: 'material', cost_name: '', standard_cost: 0, is_excluded_from_kpi_base: false }; showCostModal.value = true; }
function openCostEdit(c) { costForm.value = { ...c }; showCostModal.value = true; }
function submitCost() {
    if (costForm.value.id) {
        router.put(route('dental.service-costs.update', costForm.value.id), costForm.value, { onSuccess: () => { showCostModal.value = false; } });
    } else {
        router.post(route('dental.services.costs.store', props.service.id), costForm.value, { onSuccess: () => { showCostModal.value = false; } });
    }
}
function deleteCost(c) { if (!confirm(`Xóa chi phí "${c.cost_name}"?`)) return; router.delete(route('dental.service-costs.destroy', c.id)); }
</script>
