<template>
    <AppLayout title="Bảo hành labo">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Bảo hành labo</h1>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters" type="text" placeholder="Tìm theo tên dịch vụ..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none w-56" />
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
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Bệnh nhân</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Dịch vụ</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Labo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Số răng</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Từ ngày</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Đến ngày</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="warranties.data.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">Chưa có bảo hành nào</td>
                        </tr>
                        <tr v-for="w in warranties.data" :key="w.id" :class="['hover:bg-gray-50', isExpiringSoon(w.end_date) ? 'bg-yellow-50/40' : '']">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ w.patient }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ w.service_name }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ w.lab ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ w.tooth_number ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ w.start_date }}</td>
                            <td class="px-4 py-3 text-xs" :class="isExpiringSoon(w.end_date) ? 'text-orange-600 font-medium' : 'text-gray-500'">
                                {{ w.end_date }}
                                <span v-if="isExpiringSoon(w.end_date)" class="ml-1">⚠</span>
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="w.status_color">{{ w.status_label }}</StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="can('labo.manage') && w.status === 'active'" @click="claimWarranty(w.id)"
                                    class="text-xs text-orange-500 hover:text-orange-700">Ghi nhận BH</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="warranties.links" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ warranties: Object, filters: Object, statuses: Array });

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(route('lab.warranties.index'), { search: search.value, status: status.value }, { preserveState: true });
}

function claimWarranty(id) {
    if (confirm('Xác nhận ghi nhận bảo hành?')) {
        router.post(route('lab.warranties.claim', id));
    }
}

function isExpiringSoon(endDateStr) {
    // highlight if ends within 30 days and still active
    const parts = endDateStr.split('/');
    if (parts.length !== 3) return false;
    const end = new Date(`${parts[2]}-${parts[1]}-${parts[0]}`);
    const now = new Date();
    const diff = (end - now) / (1000 * 60 * 60 * 24);
    return diff >= 0 && diff <= 30;
}
</script>
