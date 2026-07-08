<template>
    <AppLayout title="Hoa hồng nhân viên">
        <div class="space-y-5">

            <!-- ── Header ───────────────────────────────────────────────── -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Hoa hồng & Doanh số nhân viên</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Theo dõi hoa hồng, duyệt và thanh toán</p>
                </div>
                <Link v-if="can('commissions.manage')" :href="route('hr.commissions.rules')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50 font-medium">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Quản lý quy tắc
                </Link>
            </div>

            <!-- ── KPI Summary cards ─────────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div v-for="s in statuses" :key="s.value"
                    :class="['rounded-xl p-5 text-white shadow-sm', statusCardGradient(s.value)]">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-white/80 text-sm font-medium">{{ s.label }}</span>
                        <span class="text-2xl">{{ statusIcon(s.value) }}</span>
                    </div>
                    <p class="text-2xl font-bold tabular-nums">{{ formatVnd(summaryByStatus[s.value]?.total ?? 0) }}</p>
                    <p class="text-white/70 text-xs mt-1">{{ summaryByStatus[s.value]?.count ?? 0 }} khoản</p>
                </div>
            </div>

            <!-- ── Top performers ranking — Bambu style ──────────────────── -->
            <div v-if="topPerformers.length > 0" class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    Top doanh số — kỳ này
                </h3>
                <div class="space-y-3">
                    <div v-for="(p, idx) in topPerformers" :key="p.name" class="flex items-center gap-3">
                        <!-- Rank badge -->
                        <div :class="['w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0',
                            idx === 0 ? 'bg-amber-100 text-amber-700' :
                            idx === 1 ? 'bg-gray-200 text-gray-600' :
                            idx === 2 ? 'bg-orange-100 text-orange-700' :
                            'bg-gray-100 text-gray-400']">
                            {{ idx < 3 ? ['🥇','🥈','🥉'][idx] : idx + 1 }}
                        </div>
                        <!-- Name -->
                        <span class="font-semibold text-gray-800 w-36 flex-shrink-0 text-sm">{{ p.name }}</span>
                        <!-- Progress bar -->
                        <div class="flex-1 h-5 bg-gray-100 rounded-full overflow-hidden">
                            <div :class="['h-full rounded-full transition-all duration-700',
                                idx === 0 ? 'bg-gradient-to-r from-amber-400 to-yellow-500' :
                                idx === 1 ? 'bg-gradient-to-r from-slate-400 to-gray-500' :
                                idx === 2 ? 'bg-gradient-to-r from-orange-400 to-amber-500' :
                                'bg-gradient-to-r from-indigo-400 to-blue-500']"
                                :style="{ width: Math.max(2, p.total / topPerformers[0].total * 100) + '%' }">
                            </div>
                        </div>
                        <!-- Commission total -->
                        <span class="font-bold text-gray-800 tabular-nums text-sm w-28 text-right flex-shrink-0">{{ formatVnd(p.total) }}</span>
                    </div>
                </div>
            </div>

            <!-- ── Filters ───────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Kỳ (tháng)</label>
                    <input type="month" v-model="period" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Nhân viên</label>
                    <select v-model="selectedEmployee"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Tất cả nhân viên</option>
                        <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Trạng thái</label>
                    <select v-model="selectedStatus"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Tất cả</option>
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>
                <button @click="applyFilters"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 font-medium self-end">
                    Lọc
                </button>
            </div>

            <!-- ── Transactions table ────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Chi tiết hoa hồng</h3>
                    <span class="text-xs text-gray-400">{{ transactions.length }} khoản</span>
                </div>
                <div v-if="transactions.length === 0" class="flex flex-col items-center py-12 text-gray-400">
                    <svg class="w-10 h-10 mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="text-sm">Không có hoa hồng trong kỳ này</p>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50/60 text-gray-500 text-xs border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Nhân viên</th>
                                <th class="px-4 py-2.5 text-left font-medium hidden sm:table-cell">Khách hàng</th>
                                <th class="px-4 py-2.5 text-left font-medium">Hóa đơn</th>
                                <th class="px-4 py-2.5 text-right font-medium hidden md:table-cell">Doanh thu HĐ</th>
                                <th class="px-4 py-2.5 text-right font-medium">Hoa hồng</th>
                                <th class="px-4 py-2.5 text-center font-medium">Trạng thái</th>
                                <th v-if="can('commissions.manage')" class="px-4 py-2.5 text-right font-medium">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="t in transactions" :key="t.id" class="hover:bg-blue-50/20 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 text-xs font-bold flex-shrink-0">
                                            {{ t.employee_name.charAt(0) }}
                                        </div>
                                        <span class="font-semibold text-gray-800">{{ t.employee_name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ t.patient_name }}</td>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ t.invoice_code }}</span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700 tabular-nums hidden md:table-cell">{{ formatVnd(t.invoice_total) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-bold text-teal-700 tabular-nums">{{ formatVnd(t.amount) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <StatusBadge :color="t.status_color">{{ t.status_label }}</StatusBadge>
                                </td>
                                <td v-if="can('commissions.manage')" class="px-4 py-3 text-right">
                                    <button v-if="t.status === 'pending'" @click="approve(t)"
                                        class="inline-flex items-center gap-1 text-xs px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 font-medium border border-blue-200">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Duyệt
                                    </button>
                                    <button v-else-if="t.status === 'approved'" @click="markPaid(t)"
                                        class="inline-flex items-center gap-1 text-xs px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100 font-medium border border-emerald-200">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Đã trả
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { usePermission } from '@/composables/usePermission';
import { useCurrency } from '@/composables/useCurrency';

const { hasPermission: can } = usePermission();
const { formatVnd } = useCurrency();

const props = defineProps({
    transactions: Array,
    summary:      Object,
    employees:    Array,
    statuses:     Array,
    filters:      Object,
});

const period           = ref(props.filters.period ?? '');
const selectedEmployee = ref(props.filters.employeeId ?? '');
const selectedStatus   = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(route('hr.commissions.index'), {
        period:      period.value,
        employee_id: selectedEmployee.value || undefined,
        status:      selectedStatus.value   || undefined,
    }, { preserveState: true });
}

const summaryByStatus = computed(() => props.summary ?? {});

// Top performers: aggregate from transactions
const topPerformers = computed(() => {
    const map = {};
    props.transactions.forEach(t => {
        if (!map[t.employee_name]) map[t.employee_name] = { name: t.employee_name, total: 0 };
        map[t.employee_name].total += t.amount;
    });
    return Object.values(map).sort((a, b) => b.total - a.total).slice(0, 5);
});

function statusCardGradient(status) {
    return {
        pending:  'bg-gradient-to-br from-amber-500 to-orange-600',
        approved: 'bg-gradient-to-br from-blue-500 to-indigo-600',
        paid:     'bg-gradient-to-br from-emerald-500 to-teal-600',
    }[status] ?? 'bg-gradient-to-br from-gray-500 to-slate-600';
}

function statusIcon(status) {
    return { pending: '⏳', approved: '✅', paid: '💚' }[status] ?? '📋';
}

function approve(t) { router.post(route('hr.commissions.approve', t.id)); }
function markPaid(t) { router.post(route('hr.commissions.mark-paid', t.id)); }
</script>
