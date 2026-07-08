<template>
    <AppLayout title="Danh sách Labo">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Danh sách Labo</h1>
                <Link v-if="can('labo.manage')" :href="route('lab.labs.create')"
                    class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                    + Thêm labo
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <input v-model="search" @keyup.enter="applyFilters" type="text" placeholder="Tìm theo tên, mã..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none w-56" />
                <button @click="applyFilters" class="px-3 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700">Tìm</button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mã / Tên</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Liên hệ</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Người liên hệ</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Đơn hàng</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="labs.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">Chưa có labo nào</td>
                        </tr>
                        <tr v-for="lab in labs.data" :key="lab.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <Link :href="route('lab.labs.show', lab.id)" class="font-medium text-primary-600 hover:underline">{{ lab.name }}</Link>
                                <p class="text-xs text-gray-400 font-mono">{{ lab.code }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ lab.phone ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ lab.contact_person ?? '—' }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ lab.orders_count }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="lab.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                                    {{ lab.is_active ? 'Hoạt động' : 'Ngừng' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('lab.labs.show', lab.id)" class="text-xs text-gray-400 hover:text-gray-600">Xem</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="labs.links" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ labs: Object, filters: Object });

const search = ref(props.filters.search ?? '');

function applyFilters() {
    router.get(route('lab.labs.index'), { search: search.value }, { preserveState: true });
}
</script>
