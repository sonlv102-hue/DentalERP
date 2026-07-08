<template>
    <AppLayout title="Báo cáo doanh thu">
        <div class="space-y-5">

            <!-- ── Page header ───────────────────────────────────────────── -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Báo cáo doanh thu</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Theo dõi thu nhập theo thời gian và phương thức</p>
                </div>
                <!-- Quick date presets -->
                <div class="flex gap-1.5">
                    <button v-for="preset in datePresets" :key="preset.label"
                        @click="applyPreset(preset)"
                        :class="['px-2.5 py-1 text-xs rounded-lg border transition-colors font-medium',
                            activePreset === preset.label
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300']">
                        {{ preset.label }}
                    </button>
                </div>
            </div>

            <!-- ── Filters ───────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Từ ngày</label>
                    <input type="date" v-model="from" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Đến ngày</label>
                    <input type="date" v-model="to" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Chi nhánh</label>
                    <select v-model="branchId" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option :value="null">Tất cả chi nhánh</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
                <button @click="applyFilters"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 font-medium self-end">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Xem báo cáo
                </button>
            </div>

            <!-- ── Summary KPI cards ─────────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl p-5 text-white shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-emerald-100 text-sm font-medium">Tổng doanh thu</span>
                        <span class="text-2xl">💰</span>
                    </div>
                    <p class="text-2xl font-bold tabular-nums">{{ formatVnd(totalRevenue) }}</p>
                    <p class="text-emerald-200 text-xs mt-1">{{ byDay.length }} ngày có giao dịch</p>
                </div>
                <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl p-5 text-white shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-rose-100 text-sm font-medium">Tổng hoàn tiền</span>
                        <span class="text-2xl">↩️</span>
                    </div>
                    <p class="text-2xl font-bold tabular-nums">{{ formatVnd(totalRefunds) }}</p>
                    <p class="text-rose-200 text-xs mt-1">Từ các khoản trả lại</p>
                </div>
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl p-5 text-white shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-indigo-100 text-sm font-medium">Doanh thu thuần</span>
                        <span class="text-2xl">📈</span>
                    </div>
                    <p class="text-2xl font-bold tabular-nums">{{ formatVnd(netRevenue) }}</p>
                    <p class="text-indigo-200 text-xs mt-1">Sau hoàn tiền</p>
                </div>
            </div>

            <!-- ── Revenue chart ─────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Doanh thu theo ngày
                    </h3>
                    <span class="text-xs text-gray-400">{{ from }} → {{ to }}</span>
                </div>
                <ChartCard v-if="byDay.length > 0" type="bar" :data="chartData" :height="260" />
                <div v-else class="flex flex-col items-center py-12 text-gray-400">
                    <svg class="w-12 h-12 mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="text-sm">Chưa có dữ liệu cho khoảng thời gian này</p>
                </div>
            </div>

            <!-- ── By payment method — visual bars ──────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Theo phương thức thanh toán
                </h3>
                <div v-if="byMethod.length === 0" class="text-sm text-gray-400 text-center py-6">Chưa có dữ liệu</div>
                <div v-else class="space-y-3">
                    <div v-for="m in byMethod" :key="m.method" class="flex items-center gap-3">
                        <div class="flex items-center gap-2 w-36 flex-shrink-0">
                            <span class="text-base">{{ methodIcon(m.method) }}</span>
                            <span class="text-sm text-gray-700 font-medium capitalize">{{ methodLabel(m.method) }}</span>
                        </div>
                        <div class="flex-1 h-7 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 flex items-center justify-end pr-3"
                                :style="{ width: Math.max(2, (m.total / maxMethodTotal * 100)) + '%', background: methodColor(m.method) }">
                            </div>
                        </div>
                        <span class="text-sm font-bold text-gray-800 tabular-nums w-28 text-right flex-shrink-0">{{ formatVnd(m.total) }}</span>
                        <span class="text-xs text-gray-400 w-10 text-right flex-shrink-0">
                            {{ totalRevenue > 0 ? Math.round(m.total / totalRevenue * 100) : 0 }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- ── Daily breakdown table ─────────────────────────────────── -->
            <div v-if="byDay.length > 0" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Chi tiết theo ngày</h3>
                    <span class="text-xs text-gray-400">{{ byDay.length }} ngày</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50/60 text-gray-500 text-xs border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Ngày</th>
                                <th class="px-4 py-2.5 text-right font-medium">Doanh thu</th>
                                <th class="px-4 py-2.5 text-right font-medium">Hoàn tiền</th>
                                <th class="px-4 py-2.5 text-right font-medium">Thuần</th>
                                <th class="px-4 py-2.5 font-medium">Biểu đồ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="d in byDay" :key="d.day" class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-2.5 text-gray-700 font-medium">{{ d.day }}</td>
                                <td class="px-4 py-2.5 text-right text-emerald-700 font-semibold tabular-nums">{{ formatVnd(d.revenue) }}</td>
                                <td class="px-4 py-2.5 text-right text-rose-600 tabular-nums">{{ d.refunds > 0 ? '-' + formatVnd(d.refunds) : '—' }}</td>
                                <td class="px-4 py-2.5 text-right font-bold tabular-nums" :class="d.net >= 0 ? 'text-indigo-700' : 'text-rose-600'">{{ formatVnd(d.net) }}</td>
                                <td class="px-4 py-2.5 w-32">
                                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                                        <div class="bg-emerald-500 h-1.5 rounded-full"
                                            :style="{ width: Math.max(1, d.revenue / maxDayRevenue * 100) + '%' }"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="border-t-2 border-gray-200 bg-gray-50">
                            <tr>
                                <td class="px-4 py-3 font-bold text-gray-700">Tổng</td>
                                <td class="px-4 py-3 text-right font-bold text-emerald-700 tabular-nums">{{ formatVnd(totalRevenue) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-rose-600 tabular-nums">{{ formatVnd(totalRefunds) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-indigo-700 tabular-nums">{{ formatVnd(netRevenue) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import ChartCard from '@/Components/Shared/ChartCard.vue';
import { useCurrency } from '@/composables/useCurrency';
import dayjs from 'dayjs';

const { formatVnd } = useCurrency();
const props = defineProps({ byDay: Array, byMethod: Array, totalRevenue: Number, totalRefunds: Number, netRevenue: Number, branches: Array, filters: Object });

const from      = ref(props.filters.from);
const to        = ref(props.filters.to);
const branchId  = ref(props.filters.branchId ?? null);
const activePreset = ref('');

const datePresets = [
    { label: 'Hôm nay', from: dayjs().format('YYYY-MM-DD'), to: dayjs().format('YYYY-MM-DD') },
    { label: '7 ngày',  from: dayjs().subtract(6,'day').format('YYYY-MM-DD'), to: dayjs().format('YYYY-MM-DD') },
    { label: 'Tháng này', from: dayjs().startOf('month').format('YYYY-MM-DD'), to: dayjs().format('YYYY-MM-DD') },
    { label: 'Tháng trước', from: dayjs().subtract(1,'month').startOf('month').format('YYYY-MM-DD'), to: dayjs().subtract(1,'month').endOf('month').format('YYYY-MM-DD') },
];

function applyPreset(p) {
    from.value = p.from; to.value = p.to;
    activePreset.value = p.label;
    applyFilters();
}

function applyFilters() {
    router.get(route('reports.revenue'), { from: from.value, to: to.value, branch_id: branchId.value });
}

const chartData = computed(() => ({
    labels: props.byDay.map(r => r.day),
    datasets: [
        { label: 'Doanh thu', data: props.byDay.map(r => r.revenue), backgroundColor: 'rgba(16,185,129,0.85)', borderRadius: 4 },
        { label: 'Hoàn tiền', data: props.byDay.map(r => r.refunds), backgroundColor: 'rgba(239,68,68,0.7)', borderRadius: 4 },
    ],
}));

const maxDayRevenue  = computed(() => Math.max(1, ...props.byDay.map(d => d.revenue)));
const maxMethodTotal = computed(() => Math.max(1, ...props.byMethod.map(m => m.total)));

const METHOD_META = {
    cash:        { label: 'Tiền mặt',    icon: '💵', color: '#10b981' },
    transfer:    { label: 'Chuyển khoản', icon: '🏦', color: '#3b82f6' },
    card:        { label: 'Thẻ',         icon: '💳', color: '#8b5cf6' },
    ewallet:     { label: 'Ví điện tử',  icon: '📱', color: '#06b6d4' },
    installment: { label: 'Trả góp',     icon: '📅', color: '#f59e0b' },
    voucher:     { label: 'Voucher',     icon: '🎟️', color: '#ec4899' },
};

const methodLabel = m => METHOD_META[m]?.label ?? m;
const methodIcon  = m => METHOD_META[m]?.icon  ?? '💰';
const methodColor = m => METHOD_META[m]?.color ?? '#6366f1';
</script>
