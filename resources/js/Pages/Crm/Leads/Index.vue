<template>
    <AppLayout title="Lead / CRM Pipeline">
        <div class="space-y-5">

            <!-- ── Header ───────────────────────────────────────────────── -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Pipeline Lead CRM</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Theo dõi và chuyển đổi tiềm năng thành khách hàng</p>
                </div>
                <Link v-if="can('leads.create')" :href="route('crm.leads.create')"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 font-medium shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Thêm Lead
                </Link>
            </div>

            <!-- ── Visual funnel pipeline ──────────────────────────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <button v-for="(s, idx) in statuses" :key="s.value"
                    @click="filterStatus(s.value)"
                    :class="['rounded-xl p-3 text-center transition-all border-2',
                        status === s.value ? 'border-indigo-500 shadow-md scale-[1.02]' : 'border-transparent',
                        funnelBg(s.value)]">
                    <p class="text-2xl font-bold" :class="funnelText(s.value)">
                        {{ statusCount(s.value) }}
                    </p>
                    <p class="text-xs font-medium mt-0.5" :class="funnelSubText(s.value)">{{ s.label }}</p>
                    <!-- Progress bar -->
                    <div class="mt-2 w-full bg-white/40 rounded-full h-1">
                        <div class="h-1 rounded-full bg-current opacity-70 transition-all duration-500"
                            :class="funnelText(s.value)"
                            :style="{ width: Math.max(2, statusCount(s.value) / Math.max(1, totalLeads) * 100) + '%' }">
                        </div>
                    </div>
                </button>
                <button @click="filterStatus('')"
                    :class="['rounded-xl p-3 text-center transition-all border-2 bg-slate-800',
                        !status ? 'border-indigo-400 shadow-md scale-[1.02]' : 'border-transparent']">
                    <p class="text-2xl font-bold text-white">{{ totalLeads }}</p>
                    <p class="text-xs font-medium text-slate-400 mt-0.5">Tất cả</p>
                    <div class="mt-2 w-full bg-white/10 rounded-full h-1">
                        <div class="h-1 rounded-full bg-indigo-400" style="width:100%"></div>
                    </div>
                </button>
            </div>

            <!-- ── Conversion rate bar ────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 px-5 py-3 flex items-center gap-4">
                <span class="text-xs text-gray-500 font-medium whitespace-nowrap">Tỷ lệ chuyển đổi</span>
                <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-emerald-500 transition-all duration-700"
                        :style="{ width: conversionRate + '%' }"></div>
                </div>
                <span class="text-sm font-bold text-indigo-700 tabular-nums whitespace-nowrap">{{ conversionRate }}%</span>
                <span class="text-xs text-gray-400">
                    {{ statusCount('converted') }} / {{ totalLeads }} lead
                </span>
            </div>

            <!-- ── Filters ───────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Tìm kiếm</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input v-model="search" @keyup.enter="applyFilters" placeholder="Tên hoặc SĐT..."
                            class="border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm w-56 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                    </div>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Nguồn</label>
                    <select v-model="source" @change="applyFilters"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Tất cả nguồn</option>
                        <option v-for="s in sources" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>
                <button @click="applyFilters"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 font-medium self-end">
                    Lọc
                </button>
            </div>

            <!-- ── Table ─────────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Danh sách Lead</h3>
                    <span class="text-xs text-gray-400">{{ leads.data.length }} leads</span>
                </div>
                <div v-if="leads.data.length === 0" class="flex flex-col items-center py-12 text-gray-400">
                    <svg class="w-10 h-10 mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="text-sm">Không có lead nào</p>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50/60 text-gray-500 text-xs border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Lead</th>
                                <th class="px-4 py-2.5 text-left font-medium hidden sm:table-cell">SĐT</th>
                                <th class="px-4 py-2.5 text-left font-medium hidden md:table-cell">Nguồn</th>
                                <th class="px-4 py-2.5 text-left font-medium">Trạng thái</th>
                                <th class="px-4 py-2.5 text-left font-medium hidden md:table-cell">Phụ trách</th>
                                <th class="px-4 py-2.5 text-right font-medium">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="l in leads.data" :key="l.id"
                                :class="['hover:bg-blue-50/20 transition-colors', l.converted ? 'bg-emerald-50/30' : '']">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <!-- Avatar -->
                                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0', sourceAvatarBg(l.source)]">
                                            {{ l.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">{{ l.name }}</p>
                                            <p class="text-xs text-gray-400 font-mono">{{ l.code }}</p>
                                        </div>
                                        <span v-if="l.converted"
                                            class="ml-1 text-xs bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-full font-medium">
                                            ✓ Đã chuyển
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ l.phone }}</td>
                                <td class="px-4 py-3 hidden md:table-cell">
                                    <span v-if="l.source" :class="['text-xs px-2 py-0.5 rounded-full font-medium', sourceBadgeClass(l.source)]">
                                        {{ l.source_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <StatusBadge :color="l.status_color">{{ l.status_label }}</StatusBadge>
                                </td>
                                <td class="px-4 py-3 text-gray-500 hidden md:table-cell">
                                    <span v-if="l.assigned_to" class="flex items-center gap-1.5">
                                        <div class="w-5 h-5 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 text-xs font-bold">
                                            {{ (l.assigned_to ?? '?').toString().charAt(0) }}
                                        </div>
                                        {{ l.assigned_to }}
                                    </span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('crm.leads.show', l.id)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 font-medium">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Xem
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination :links="leads.links" :meta="leads.meta" @navigate="url => router.get(url)" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ leads: Object, statuses: Array, sources: Array, assignees: Array, filters: Object });

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const source = ref(props.filters.source ?? '');

function applyFilters() {
    router.get(route('crm.leads.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        source: source.value || undefined,
    }, { preserveState: true });
}

function filterStatus(val) {
    status.value = val;
    applyFilters();
}

// Count leads by status from the paginated data (approximation)
function statusCount(val) {
    if (!val) return props.leads.meta?.total ?? props.leads.data.length;
    return props.leads.data.filter(l => l.status === val || l.status_value === val).length;
}

const totalLeads = computed(() => props.leads.meta?.total ?? props.leads.data.length);
const conversionRate = computed(() => {
    const converted = props.leads.data.filter(l => l.converted).length;
    return totalLeads.value > 0 ? Math.round(converted / totalLeads.value * 100) : 0;
});

// Funnel styling
function funnelBg(val) {
    return {
        new:       'bg-gray-100',
        contacted: 'bg-blue-100',
        qualified: 'bg-teal-100',
        converted: 'bg-emerald-100',
        lost:      'bg-rose-100',
    }[val] ?? 'bg-gray-100';
}
function funnelText(val) {
    return {
        new:       'text-gray-700',
        contacted: 'text-blue-700',
        qualified: 'text-teal-700',
        converted: 'text-emerald-700',
        lost:      'text-rose-700',
    }[val] ?? 'text-gray-700';
}
function funnelSubText(val) {
    return {
        new:       'text-gray-600',
        contacted: 'text-blue-600',
        qualified: 'text-teal-600',
        converted: 'text-emerald-600',
        lost:      'text-rose-600',
    }[val] ?? 'text-gray-500';
}

function sourceAvatarBg(src) {
    return {
        facebook: 'bg-blue-500',
        zalo:     'bg-teal-500',
        google:   'bg-red-500',
        referral: 'bg-purple-500',
        walk_in:  'bg-gray-500',
        other:    'bg-orange-500',
    }[src] ?? 'bg-indigo-500';
}

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
</script>
