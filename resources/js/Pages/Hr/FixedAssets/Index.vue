<template>
    <AppLayout title="Tài sản cố định">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Tài sản cố định</h1>
                <div class="flex gap-2">
                    <button v-if="can('fixed_assets.manage')" @click="runDepreciation"
                        class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-700">
                        Khấu hao {{ currentPeriod }}
                    </button>
                    <Link v-if="can('fixed_assets.manage')" :href="route('hr.fixed-assets.create')"
                        class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                        + Thêm tài sản
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters" type="text" placeholder="Tìm theo tên, mã..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none w-56" />
                <select v-model="status" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <select v-model="category" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả loại</option>
                    <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mã / Tên</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Loại</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Nguyên giá</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Giá trị còn lại</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kỳ KH cuối</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="assets.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">Chưa có tài sản nào</td>
                        </tr>
                        <tr v-for="asset in assets.data" :key="asset.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <Link :href="route('hr.fixed-assets.show', asset.id)" class="font-medium text-primary-600 hover:underline">{{ asset.name }}</Link>
                                <p class="text-xs text-gray-400 font-mono">{{ asset.code }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ asset.category_label }}</td>
                            <td class="px-4 py-3 text-right font-mono text-gray-700">{{ formatVnd(asset.acquisition_cost) }}</td>
                            <td class="px-4 py-3 text-right font-mono" :class="asset.net_book_value > 0 ? 'text-gray-700' : 'text-gray-400'">{{ formatVnd(asset.net_book_value) }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ asset.last_depreciation_period ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="asset.status_color">{{ asset.status_label }}</StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('hr.fixed-assets.edit', asset.id)" v-if="can('fixed_assets.manage')"
                                    class="text-xs text-gray-400 hover:text-gray-600">Sửa</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="assets.links" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ assets: Object, filters: Object, statuses: Array, categories: Array });

const search   = ref(props.filters.search ?? '');
const status   = ref(props.filters.status ?? '');
const category = ref(props.filters.category ?? '');
const currentPeriod = new Date().toISOString().slice(0, 7);

function applyFilters() {
    router.get(route('hr.fixed-assets.index'), { search: search.value, status: status.value, category: category.value }, { preserveState: true });
}

function runDepreciation() {
    if (confirm(`Chạy khấu hao cho kỳ ${currentPeriod}?`)) {
        router.post(route('hr.fixed-assets.depreciate'), { period: currentPeriod });
    }
}

function formatVnd(value) {
    return new Intl.NumberFormat('vi-VN').format(value || 0) + ' ₫';
}
</script>
