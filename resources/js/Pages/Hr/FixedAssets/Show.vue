<template>
    <AppLayout :title="`TSC: ${asset.name}`">
        <div class="max-w-4xl space-y-4">
            <!-- Header -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ asset.code }}</span>
                            <StatusBadge :color="asset.status_color">{{ asset.status_label }}</StatusBadge>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">{{ asset.name }}</h2>
                        <p class="text-sm text-gray-500 mt-0.5">{{ asset.category_label }} <span v-if="asset.branch">· {{ asset.branch }}</span></p>
                    </div>
                    <div class="flex gap-2">
                        <Link v-if="can('fixed_assets.manage') && asset.status !== 'disposed'" :href="route('hr.fixed-assets.edit', asset.id)"
                            class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-700">Sửa</Link>
                        <button v-if="can('fixed_assets.manage') && asset.status === 'active'" @click="dispose"
                            class="px-3 py-1.5 text-sm border border-red-300 text-red-600 rounded-lg hover:bg-red-50">Thanh lý</button>
                    </div>
                </div>

                <!-- Key figures -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4">
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-400">Nguyên giá</p>
                        <p class="font-bold text-gray-800 mt-1">{{ formatVnd(asset.acquisition_cost) }}</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-400">Đã khấu hao</p>
                        <p class="font-bold text-rose-600 mt-1">{{ formatVnd(asset.accumulated_depreciation) }}</p>
                    </div>
                    <div class="text-center p-3 bg-indigo-50 rounded-lg">
                        <p class="text-xs text-indigo-400">Giá trị còn lại</p>
                        <p class="font-bold text-indigo-700 mt-1">{{ formatVnd(asset.net_book_value) }}</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-400">KH/tháng</p>
                        <p class="font-bold text-gray-800 mt-1">{{ formatVnd(asset.monthly_depreciation) }}</p>
                    </div>
                </div>
            </div>

            <!-- Depreciation history + schedule tabs -->
            <div class="flex gap-1 bg-white border border-gray-200 p-1 rounded-xl w-fit">
                <button v-for="tab in ['history', 'schedule']" :key="tab" @click="activeTab = tab"
                    :class="['px-4 py-1.5 text-sm rounded-lg transition-all',
                        activeTab === tab ? 'bg-indigo-600 text-white font-medium shadow' : 'text-gray-500 hover:bg-gray-50']">
                    {{ tab === 'history' ? 'Lịch sử khấu hao' : 'Kế hoạch khấu hao' }}
                </button>
            </div>

            <!-- History tab -->
            <div v-if="activeTab === 'history'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kỳ</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Khấu hao</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Lũy kế trước</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Giá trị còn lại</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ngày ghi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="asset.depreciations.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400">Chưa có kỳ khấu hao nào</td>
                        </tr>
                        <tr v-for="d in asset.depreciations" :key="d.period" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono font-medium text-gray-700">{{ d.period }}</td>
                            <td class="px-4 py-3 text-right font-mono text-rose-600">{{ formatVnd(d.amount) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-gray-500">{{ formatVnd(d.accumulated_before) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-gray-700">{{ formatVnd(d.net_book_value_after) }}</td>
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ d.created_at }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Schedule tab -->
            <div v-if="activeTab === 'schedule'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kỳ</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Khấu hao</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Lũy kế</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Còn lại</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="row in schedule" :key="row.period"
                            :class="['hover:bg-gray-50', row.posted ? 'bg-green-50/40' : '']">
                            <td class="px-4 py-2 font-mono text-gray-700">{{ row.period }}</td>
                            <td class="px-4 py-2 text-right font-mono text-rose-600">{{ formatVnd(row.amount) }}</td>
                            <td class="px-4 py-2 text-right font-mono text-gray-500">{{ formatVnd(row.accumulated) }}</td>
                            <td class="px-4 py-2 text-right font-mono text-gray-700">{{ formatVnd(row.net_book_value) }}</td>
                            <td class="px-4 py-2 text-center">
                                <span v-if="row.posted" class="text-xs text-green-600 font-medium">✓ Đã ghi</span>
                                <span v-else class="text-xs text-gray-400">Dự kiến</span>
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
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ asset: Object, schedule: Array });

const activeTab = ref('history');

function formatVnd(value) {
    return new Intl.NumberFormat('vi-VN').format(value || 0) + ' ₫';
}

function dispose() {
    if (confirm('Xác nhận thanh lý tài sản này?')) {
        router.post(route('hr.fixed-assets.dispose', props.asset.id));
    }
}
</script>
