<template>
    <AppLayout title="Thu chi theo ngày">
        <div class="space-y-4">
            <h1 class="text-lg font-semibold text-gray-800">Báo cáo Thu / Chi theo ngày</h1>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Từ ngày</label>
                    <input v-model="from" type="date" class="filter-input" />
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Đến ngày</label>
                    <input v-model="to" type="date" class="filter-input" />
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Nguồn quỹ</label>
                    <select v-model="fundAccountId" class="filter-input">
                        <option value="">Tất cả</option>
                        <option v-for="f in fundAccounts" :key="f.id" :value="f.id">{{ f.name }} ({{ f.type_label }})</option>
                    </select>
                </div>
                <button @click="applyFilters" class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700">Lọc</button>
            </div>

            <!-- Fund account balances -->
            <div v-if="fundAccounts.length" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div v-for="f in fundAccounts" :key="f.id" class="bg-white rounded-xl border border-gray-200 p-3 text-center">
                    <p class="text-xs text-gray-400">{{ f.name }}</p>
                    <p class="text-xs text-gray-400">{{ f.type_label }}</p>
                    <p class="font-bold mt-1" :class="f.current_balance >= 0 ? 'text-green-700' : 'text-red-600'">
                        {{ formatVnd(f.current_balance) }}
                    </p>
                </div>
            </div>

            <!-- Summary bar -->
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-green-50 rounded-xl border border-green-200 p-4 text-center">
                    <p class="text-xs text-green-500">Tổng thu</p>
                    <p class="text-xl font-bold text-green-700 mt-1">{{ formatVnd(totalIncome) }}</p>
                </div>
                <div class="bg-red-50 rounded-xl border border-red-200 p-4 text-center">
                    <p class="text-xs text-red-400">Tổng chi</p>
                    <p class="text-xl font-bold text-red-600 mt-1">{{ formatVnd(totalExpense) }}</p>
                </div>
                <div :class="['rounded-xl border p-4 text-center', net >= 0 ? 'bg-indigo-50 border-indigo-200' : 'bg-orange-50 border-orange-200']">
                    <p class="text-xs" :class="net >= 0 ? 'text-indigo-400' : 'text-orange-400'">Chênh lệch</p>
                    <p class="text-xl font-bold mt-1" :class="net >= 0 ? 'text-indigo-700' : 'text-orange-600'">{{ formatVnd(net) }}</p>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ngày</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Thu</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Chi</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Tồn quỹ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="rows.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">Không có dữ liệu trong khoảng thời gian này</td>
                        </tr>
                        <tr v-for="r in rows" :key="r.day" class="hover:bg-gray-50">
                            <td class="px-4 py-2.5 font-mono text-gray-600 text-xs">{{ formatDate(r.day) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-green-600">{{ r.income ? '+' + formatVnd(r.income) : '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-red-500">{{ r.expense ? '-' + formatVnd(r.expense) : '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-mono font-semibold" :class="r.running >= 0 ? 'text-gray-800' : 'text-red-700'">
                                {{ formatVnd(r.running) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({ rows: Array, totalIncome: Number, totalExpense: Number, net: Number, fundAccounts: Array, filters: Object });

const from          = ref(props.filters.from ?? '');
const to            = ref(props.filters.to ?? '');
const fundAccountId = ref(props.filters.fundAccountId ?? '');

function applyFilters() {
    router.get(route('reports.cashflow'), { from: from.value, to: to.value, fund_account_id: fundAccountId.value }, { preserveState: true });
}

function formatVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
function formatDate(d) {
    const [y, m, day] = d.split('-');
    return `${day}/${m}/${y}`;
}
</script>

<style scoped>
.filter-input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
