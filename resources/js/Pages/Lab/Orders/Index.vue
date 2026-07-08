<template>
    <AppLayout title="Đơn đặt xưởng">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Đơn đặt xưởng</h1>
                <Link v-if="can('labo.manage')" :href="route('lab.orders.create')"
                    class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                    + Tạo đơn
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters" type="text" placeholder="Tìm theo mã đơn..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none w-48" />
                <select v-model="labId" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả labo</option>
                    <option v-for="l in labs" :key="l.id" :value="l.id">{{ l.name }}</option>
                </select>
                <select v-model="status" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mã đơn</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Labo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Bệnh nhân</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ngày nhận dự kiến</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Tổng tiền</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="orders.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">Chưa có đơn nào</td>
                        </tr>
                        <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">
                                <Link :href="route('lab.orders.show', order.id)" class="font-medium text-primary-600 hover:underline font-mono text-sm">
                                    {{ order.code }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ order.lab }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ order.patient }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ order.expected_date ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-mono text-gray-700">{{ formatVnd(order.total_amount) }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="order.status_color">{{ order.status_label }}</StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-right text-xs text-gray-400">{{ order.created_at }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="orders.links" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ orders: Object, filters: Object, labs: Array, statuses: Array });

const search = ref(props.filters.search ?? '');
const labId  = ref(props.filters.lab_id ?? '');
const status = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(route('lab.orders.index'), { search: search.value, lab_id: labId.value, status: status.value }, { preserveState: true });
}

function formatVnd(value) {
    return new Intl.NumberFormat('vi-VN').format(value || 0) + ' ₫';
}
</script>
