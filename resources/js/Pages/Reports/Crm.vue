<template>
    <AppLayout title="Báo cáo CRM">
        <div class="space-y-5">

            <!-- ── Header ───────────────────────────────────────────────── -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Báo cáo CRM</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Phân tích hiệu quả tuyến khách hàng</p>
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
                <button @click="applyFilters"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 font-medium self-end">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Xem báo cáo
                </button>
            </div>

            <!-- ── Visual Funnel — Bambu style ─────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-5 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Phễu chuyển đổi Lead
                </h3>
                <!-- Funnel steps -->
                <div class="relative">
                    <div class="flex flex-col sm:flex-row items-stretch gap-1">
                        <div v-for="(step, idx) in funnelSteps" :key="step.key"
                            :class="['relative flex-1 rounded-xl p-4 text-center', step.bg]"
                            :style="{ opacity: 0.4 + idx * 0.12 }">
                            <!-- Arrow connector -->
                            <div v-if="idx < funnelSteps.length - 1"
                                class="hidden sm:block absolute -right-3 top-1/2 -translate-y-1/2 z-10 w-6 text-gray-400 text-lg leading-none">
                                ›
                            </div>
                            <p :class="['text-3xl font-bold', step.text]">{{ conversion[step.key] ?? 0 }}</p>
                            <p :class="['text-xs font-semibold mt-1', step.subText]">{{ step.label }}</p>
                            <!-- Drop rate -->
                            <p v-if="idx > 0" class="text-xs text-gray-400 mt-1.5">
                                {{ dropRate(idx) }}%
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Conversion rate hero -->
                <div class="mt-5 p-4 bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-xl">
                    <div class="flex items-center justify-between text-white">
                        <div>
                            <p class="text-sm font-medium text-indigo-100">Tỷ lệ chuyển đổi tổng</p>
                            <p class="text-4xl font-bold mt-1 tabular-nums">{{ conversionRate }}%</p>
                            <p class="text-indigo-200 text-xs mt-1">
                                {{ conversion.converted }} / {{ conversion.total }} lead → khách hàng
                            </p>
                        </div>
                        <div class="w-20 h-20 rounded-full border-4 border-white/30 flex items-center justify-center">
                            <p class="text-2xl font-bold text-white">{{ conversionRate }}%</p>
                        </div>
                    </div>
                    <div class="mt-3 bg-white/20 rounded-full h-2">
                        <div class="bg-white h-2 rounded-full transition-all duration-700"
                            :style="{ width: conversionRate + '%' }"></div>
                    </div>
                </div>
            </div>

            <!-- ── Two column: Funnel chart + Source breakdown ──────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Lead funnel bar chart -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Phân bổ theo trạng thái</h3>
                    <ChartCard type="bar" :data="funnelChartData" :height="200" />
                </div>

                <!-- Source doughnut -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Lead theo nguồn</h3>
                    <div v-if="bySource.length === 0" class="text-center text-gray-400 text-sm py-10">
                        Chưa có dữ liệu
                    </div>
                    <ChartCard v-else type="doughnut" :data="sourceChartData" :height="200" />
                </div>
            </div>

            <!-- ── Source conversion table ──────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Tỷ lệ chuyển đổi theo nguồn</h3>
                </div>
                <div v-if="bySource.length === 0" class="text-center py-10 text-gray-400 text-sm">
                    Không có dữ liệu trong khoảng thời gian này
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50/60 text-gray-500 text-xs border-b border-gray-100">
                            <tr>
                                <th class="px-5 py-2.5 text-left font-medium">Nguồn</th>
                                <th class="px-5 py-2.5 text-right font-medium">Tổng Lead</th>
                                <th class="px-5 py-2.5 text-right font-medium">Đã chuyển</th>
                                <th class="px-5 py-2.5 text-right font-medium">Tỷ lệ</th>
                                <th class="px-5 py-2.5 font-medium">Hiệu quả</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="row in sortedBySource" :key="row.source" class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">{{ sourceIcon(row.source) }}</span>
                                        <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', sourceBadgeClass(row.source)]">
                                            {{ sourceLabel(row.source) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-right tabular-nums font-medium text-gray-700">{{ row.total }}</td>
                                <td class="px-5 py-3 text-right tabular-nums font-semibold text-emerald-700">{{ row.converted }}</td>
                                <td class="px-5 py-3 text-right">
                                    <span :class="['font-bold tabular-nums',
                                        row.rate >= 50 ? 'text-emerald-600' :
                                        row.rate >= 25 ? 'text-amber-600' : 'text-rose-500']">
                                        {{ row.rate }}%
                                    </span>
                                </td>
                                <td class="px-5 py-3 w-40">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-100 rounded-full h-2">
                                            <div class="h-2 rounded-full transition-all"
                                                :class="row.rate >= 50 ? 'bg-emerald-500' : row.rate >= 25 ? 'bg-amber-500' : 'bg-rose-400'"
                                                :style="{ width: row.rate + '%' }"></div>
                                        </div>
                                        <span class="text-xs text-gray-400 w-8 text-right">{{ row.rate }}%</span>
                                    </div>
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
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import ChartCard from '@/Components/Shared/ChartCard.vue';

const props = defineProps({
    conversion: Object,
    bySource:   { type: Array, default: () => [] },
    filters:    Object,
});

const from = ref(props.filters.from ?? '');
const to   = ref(props.filters.to   ?? '');

function applyFilters() {
    router.get(route('reports.crm'), { from: from.value, to: to.value }, { preserveState: true });
}

const conversionRate = computed(() =>
    props.conversion.total > 0
        ? Math.round(props.conversion.converted / props.conversion.total * 100)
        : 0
);

const funnelSteps = [
    { key: 'total',     label: 'Tổng Lead',    bg: 'bg-gray-100',    text: 'text-gray-800',    subText: 'text-gray-600' },
    { key: 'contacted', label: 'Đã tiếp cận',  bg: 'bg-blue-100',    text: 'text-blue-700',    subText: 'text-blue-600' },
    { key: 'qualified', label: 'Đủ điều kiện', bg: 'bg-teal-100',    text: 'text-teal-700',    subText: 'text-teal-600' },
    { key: 'converted', label: 'Đã chuyển đổi', bg: 'bg-emerald-100', text: 'text-emerald-700', subText: 'text-emerald-600' },
    { key: 'lost',      label: 'Đã mất',       bg: 'bg-rose-100',    text: 'text-rose-700',    subText: 'text-rose-600' },
];

function dropRate(idx) {
    const prev = props.conversion[funnelSteps[idx - 1].key] ?? 0;
    const curr = props.conversion[funnelSteps[idx].key] ?? 0;
    if (prev === 0) return 0;
    return Math.round((1 - curr / prev) * 100);
}

const sortedBySource = computed(() => [...props.bySource].sort((a, b) => b.total - a.total));

const SOURCE_LABELS = {
    facebook: 'Facebook', zalo: 'Zalo', google: 'Google',
    referral: 'Giới thiệu', walk_in: 'Vãng lai', other: 'Khác',
};
const SOURCE_ICONS = {
    facebook: '📘', zalo: '💬', google: '🔍',
    referral: '👥', walk_in: '🚶', other: '📌',
};
const SOURCE_HEX = {
    facebook: '#3b82f6', zalo: '#14b8a6', google: '#ef4444',
    referral: '#a855f7', walk_in: '#9ca3af', other: '#f97316',
};

function sourceLabel(s) { return SOURCE_LABELS[s] ?? s; }
function sourceIcon(s)  { return SOURCE_ICONS[s] ?? '📌'; }
function sourceBadgeClass(src) {
    return {
        facebook: 'bg-blue-100 text-blue-700',
        zalo:     'bg-teal-100 text-teal-700',
        google:   'bg-red-100 text-red-700',
        referral: 'bg-purple-100 text-purple-700',
        walk_in:  'bg-gray-100 text-gray-700',
        other:    'bg-orange-100 text-orange-700',
    }[src] ?? 'bg-gray-100 text-gray-600';
}

const funnelChartData = computed(() => ({
    labels: ['Tổng', 'Tiếp cận', 'Đủ ĐK', 'Chuyển đổi', 'Mất'],
    datasets: [{
        label: 'Lead',
        data: [
            props.conversion.total,
            props.conversion.contacted,
            props.conversion.qualified,
            props.conversion.converted,
            props.conversion.lost,
        ],
        backgroundColor: ['#6b7280', '#3b82f6', '#14b8a6', '#22c55e', '#ef4444'],
        borderRadius: 6,
    }],
}));

const sourceChartData = computed(() => ({
    labels: props.bySource.map(r => sourceLabel(r.source)),
    datasets: [{
        data: props.bySource.map(r => r.total),
        backgroundColor: props.bySource.map(r => SOURCE_HEX[r.source] ?? '#9ca3af'),
        borderWidth: 2,
        borderColor: '#fff',
    }],
}));
</script>
