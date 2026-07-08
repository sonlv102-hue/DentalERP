<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({ entries: Array, total_debit: Number, total_credit: Number, net: Number, period: Object, branches: Array, filters: Object });

const from     = ref(props.filters.from);
const to       = ref(props.filters.to);
const branchId = ref(props.filters.branchId ?? '');

function applyFilters() {
    router.get(route('reports.general-ledger'), { from: from.value, to: to.value, branch_id: branchId.value || undefined }, { preserveState: true });
}

function formatVnd(v) {
    return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫';
}

const TYPE_LABELS = {
    patient_payment: 'Thu tiền BN',
    expense: 'Chi phí',
    purchase: 'Mua hàng',
};
</script>

<template>
    <AppLayout title="Sổ cái">
        <div class="max-w-6xl space-y-4">
            <h1 class="text-xl font-semibold text-gray-800">Sổ cái tổng hợp</h1>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap gap-3">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Từ ngày</label>
                    <input v-model="from" type="date" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Đến ngày</label>
                    <input v-model="to" type="date" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Chi nhánh</label>
                    <select v-model="branchId" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option value="">Tất cả</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 mb-1">Tổng Nợ (Thu)</p>
                    <p class="text-2xl font-bold text-green-600">{{ formatVnd(total_debit) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 mb-1">Tổng Có (Chi)</p>
                    <p class="text-2xl font-bold text-red-500">{{ formatVnd(total_credit) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 border-l-4 border-l-primary-500">
                    <p class="text-xs text-gray-500 mb-1">Chênh lệch (Lãi ròng)</p>
                    <p class="text-2xl font-bold" :class="net >= 0 ? 'text-primary-700' : 'text-red-600'">{{ formatVnd(net) }}</p>
                </div>
            </div>

            <!-- Ledger table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Ngày</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Loại</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Mô tả</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600 text-green-600">Nợ (Thu)</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600 text-red-500">Có (Chi)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(entry, idx) in entries" :key="idx" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-500">{{ entry.date }}</td>
                            <td class="px-4 py-2">
                                <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">
                                    {{ TYPE_LABELS[entry.entry_type] ?? entry.entry_type }}
                                </span>
                            </td>
                            <td class="px-4 py-2">{{ entry.description }}</td>
                            <td class="px-4 py-2 text-right font-mono text-green-600">
                                <span v-if="entry.debit > 0">{{ formatVnd(entry.debit) }}</span>
                            </td>
                            <td class="px-4 py-2 text-right font-mono text-red-500">
                                <span v-if="entry.credit > 0">{{ formatVnd(entry.credit) }}</span>
                            </td>
                        </tr>
                        <tr v-if="entries.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400">Không có bút toán nào</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right font-bold">Tổng cộng:</td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-green-600">{{ formatVnd(total_debit) }}</td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-red-500">{{ formatVnd(total_credit) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
