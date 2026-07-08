<template>
    <AppLayout title="Công nợ khách hàng">
        <div class="space-y-5">

            <!-- ── Page header ───────────────────────────────────────────── -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Công nợ khách hàng</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Theo dõi và thu hồi công nợ chưa thanh toán</p>
                </div>
            </div>

            <!-- ── Summary KPI cards ─────────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl p-5 text-white shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-rose-100 text-sm font-medium">Tổng công nợ</span>
                        <span class="text-2xl">⚠️</span>
                    </div>
                    <p class="text-2xl font-bold tabular-nums">{{ formatVnd(summary.total_outstanding) }}</p>
                    <p class="text-rose-200 text-xs mt-1">Chưa thu hồi</p>
                </div>
                <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl p-5 text-white shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-amber-100 text-sm font-medium">Số hóa đơn còn nợ</span>
                        <span class="text-2xl">📋</span>
                    </div>
                    <p class="text-2xl font-bold">{{ summary.count }}</p>
                    <p class="text-amber-200 text-xs mt-1">Hóa đơn chưa thanh toán đủ</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-xs text-gray-500 mb-2 font-medium uppercase tracking-wide">Quá hạn</p>
                    <p class="text-2xl font-bold text-rose-700 tabular-nums">
                        {{ formatVnd(debts.data.filter(d => d.overdue).reduce((s, d) => s + d.remaining, 0)) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">{{ debts.data.filter(d => d.overdue).length }} khách hàng quá hạn</p>
                </div>
            </div>

            <!-- ── Filters ───────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Tìm kiếm khách hàng</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input v-model="search" @keyup.enter="applyFilters" placeholder="Tên hoặc SĐT..."
                            class="border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm w-64 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                    </div>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Trạng thái</label>
                    <select v-model="statusFilter" @change="applyFilters"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Tất cả</option>
                        <option value="overdue">Quá hạn</option>
                        <option value="pending">Chờ thanh toán</option>
                        <option value="partial">Trả một phần</option>
                    </select>
                </div>
                <button @click="applyFilters"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 font-medium self-end">
                    Lọc
                </button>
            </div>

            <!-- ── Debts table ───────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Danh sách công nợ</h3>
                    <span class="text-xs text-gray-400">{{ debts.meta?.total ?? debts.data.length }} hóa đơn</span>
                </div>
                <div v-if="debts.data.length === 0" class="flex flex-col items-center py-14 text-gray-400">
                    <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium">Không có công nợ nào</p>
                    <p class="text-xs text-gray-400 mt-1">Tất cả hóa đơn đã được thanh toán đầy đủ</p>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50/60 text-gray-500 text-xs border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Khách hàng</th>
                                <th class="px-4 py-2.5 text-left font-medium hidden sm:table-cell">SĐT</th>
                                <th class="px-4 py-2.5 text-left font-medium">Mã HĐ</th>
                                <th class="px-4 py-2.5 text-right font-medium">Tổng tiền</th>
                                <th class="px-4 py-2.5 text-right font-medium hidden md:table-cell">Đã trả</th>
                                <th class="px-4 py-2.5 text-right font-medium">Còn nợ</th>
                                <th class="px-4 py-2.5 text-left font-medium">Hạn TT</th>
                                <th class="px-4 py-2.5 text-left font-medium">Trạng thái</th>
                                <th class="px-4 py-2.5 text-right font-medium">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="d in debts.data" :key="d.id"
                                :class="['hover:bg-gray-50/80 transition-colors', d.overdue ? 'bg-rose-50/40' : '']">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-900">{{ d.patient }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-500 hidden sm:table-cell">{{ d.phone }}</td>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ d.invoice_code }}</span>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-700">{{ formatVnd(d.amount) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-emerald-600 hidden md:table-cell">{{ formatVnd(d.paid) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-bold text-rose-600 tabular-nums">{{ formatVnd(d.remaining) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="d.due_date"
                                        :class="['text-xs font-medium px-2 py-1 rounded-full',
                                            d.overdue ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-600']">
                                        {{ d.overdue ? '⚠ ' : '' }}{{ d.due_date }}
                                    </span>
                                    <span v-else class="text-gray-300 text-xs">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <StatusBadge :color="d.status_color">{{ d.status_label }}</StatusBadge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('cashier.invoices.show', d.invoice_id)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-medium hover:bg-indigo-100 transition-colors">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        Thu tiền
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination :links="debts.links" :meta="debts.meta" @navigate="url => router.get(url)" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { useCurrency } from '@/composables/useCurrency';

const { formatVnd } = useCurrency();
const props = defineProps({ debts: Object, summary: Object, branches: Array, filters: Object });

const search       = ref(props.filters?.patient_search ?? '');
const statusFilter = ref(props.filters?.status ?? '');

function applyFilters() {
    router.get(route('cashier.debts.index'), {
        patient_search: search.value || undefined,
        status:         statusFilter.value || undefined,
    }, { preserveState: true });
}
</script>
