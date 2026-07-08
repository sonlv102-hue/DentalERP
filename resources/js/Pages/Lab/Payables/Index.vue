<template>
    <AppLayout title="Công nợ Labo">
        <div class="space-y-4">
            <h1 class="text-lg font-semibold text-gray-800">Công nợ phải trả Labo</h1>

            <!-- Filter -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <select v-model="labId" @change="applyFilter" class="filter-input">
                    <option value="">Tất cả labo</option>
                    <option v-for="l in labs" :key="l.id" :value="l.id">{{ l.name }}</option>
                </select>
            </div>

            <!-- Summary by lab -->
            <div v-if="summary.length" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div v-for="s in summary" :key="s.lab_id" class="bg-white rounded-xl border border-gray-200 p-3">
                    <p class="text-xs text-gray-500 font-medium">{{ s.lab }}</p>
                    <div class="flex justify-between mt-2 text-xs text-gray-400">
                        <span>Phải trả</span><span class="font-mono font-semibold text-gray-700">{{ formatVnd(s.total_cost) }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-400">
                        <span>Đã trả</span><span class="font-mono text-green-600">{{ formatVnd(s.total_paid) }}</span>
                    </div>
                    <div class="flex justify-between mt-1 text-xs font-bold border-t border-gray-100 pt-1">
                        <span class="text-gray-600">Còn nợ</span>
                        <span :class="s.remaining > 0 ? 'text-red-600' : 'text-green-600'" class="font-mono">{{ formatVnd(s.remaining) }}</span>
                    </div>
                </div>
            </div>

            <!-- Detail table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mã đơn</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Labo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Phải trả</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Đã trả</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Còn nợ</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="rows.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">Chưa có đơn nào có chi phí</td>
                        </tr>
                        <tr v-for="r in rows" :key="r.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2.5 font-mono text-xs text-gray-600">{{ r.code }}</td>
                            <td class="px-4 py-2.5 text-gray-700">{{ r.lab }}</td>
                            <td class="px-4 py-2.5">
                                <StatusBadge :color="r.status === 'completed' ? 'green' : 'blue'">{{ r.status_label }}</StatusBadge>
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-gray-700">{{ formatVnd(r.estimated_cost) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-green-600">{{ formatVnd(r.cost_paid) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono font-semibold" :class="r.remaining > 0 ? 'text-red-600' : 'text-green-600'">
                                {{ formatVnd(r.remaining) }}
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <button @click="openPayment(r)" class="text-xs text-primary-600 hover:underline">Cập nhật</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment modal -->
        <Teleport to="body">
            <div v-if="modal.show" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Cập nhật thanh toán — {{ modal.code }}</h3>
                        <button @click="modal.show = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="savePayment" class="p-5 space-y-4">
                        <div>
                            <label class="label">Chi phí ước tính (₫)</label>
                            <input v-model.number="modal.estimated_cost" type="number" min="0" required class="input-field" />
                        </div>
                        <div>
                            <label class="label">Đã thanh toán (₫)</label>
                            <input v-model.number="modal.cost_paid" type="number" min="0" required class="input-field" />
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="modal.show = false" class="px-4 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="saving" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg disabled:opacity-50">Lưu</button>
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
import StatusBadge from '@/Components/Shared/StatusBadge.vue';

const props  = defineProps({ rows: Array, summary: Array, labs: Array, filters: Object });
const labId  = ref(props.filters.lab_id ?? '');
const saving = ref(false);
const modal  = reactive({ show: false, id: null, code: '', estimated_cost: 0, cost_paid: 0 });

function applyFilter() { router.get(route('lab.payables'), { lab_id: labId.value }, { preserveState: true }); }
function openPayment(r) { Object.assign(modal, { show: true, id: r.id, code: r.code, estimated_cost: r.estimated_cost, cost_paid: r.cost_paid }); }
function savePayment() {
    saving.value = true;
    router.post(route('lab.orders.record-payment', modal.id), { estimated_cost: modal.estimated_cost, cost_paid: modal.cost_paid },
        { onSuccess: () => { modal.show = false; }, onFinish: () => { saving.value = false; } });
}
function formatVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
</script>

<style scoped>
.label       { @apply block text-sm font-medium text-gray-700 mb-1; }
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
.filter-input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
