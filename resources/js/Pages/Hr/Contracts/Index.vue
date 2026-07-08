<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

const props = defineProps({ contracts: Object, employees: Array, filters: Object });

const employeeId = ref(props.filters.employee_id ?? '');

function applyFilters() {
    router.get(route('hr.contracts.index'), { employee_id: employeeId.value || undefined }, { preserveState: true });
}

const modal = ref({ open: false });
const form = useForm({ employee_id: '', type: 'full_time', start_date: '', end_date: '', base_salary: 0, notes: '' });

function submit() {
    form.post(route('hr.contracts.store'), { onSuccess: () => { modal.value.open = false; form.reset(); } });
}

function remove(id) {
    if (!confirm('Xóa hợp đồng này?')) return;
    router.delete(route('hr.contracts.destroy', id));
}

function formatVnd(v) {
    return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫';
}
</script>

<template>
    <AppLayout title="Hợp đồng lao động">
        <div class="max-w-6xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Hợp đồng lao động</h1>
                <button v-if="can('employees.manage')" @click="modal.open = true"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700">
                    + Tạo hợp đồng
                </button>
            </div>

            <!-- Filter -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex gap-3">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Nhân viên</label>
                    <select v-model="employeeId" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option value="">Tất cả</option>
                        <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }} ({{ e.code }})</option>
                    </select>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Nhân viên</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Loại HĐ</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Bắt đầu</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Kết thúc</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Lương cơ bản</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="c in contracts.data" :key="c.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ c.employee }}</p>
                                <p class="text-xs text-gray-400">{{ c.employee_code }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="c.type_color">{{ c.type_label }}</StatusBadge>
                            </td>
                            <td class="px-4 py-3">{{ c.start_date }}</td>
                            <td class="px-4 py-3">{{ c.end_date ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ formatVnd(c.base_salary) }}</td>
                            <td class="px-4 py-3">
                                <span :class="['text-xs px-2 py-0.5 rounded-full', c.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                                    {{ c.is_active ? 'Còn hiệu lực' : 'Hết hạn' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="can('employees.manage')" @click="remove(c.id)"
                                    class="text-xs text-red-400 hover:underline">Xóa</button>
                            </td>
                        </tr>
                        <tr v-if="contracts.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">Chưa có hợp đồng</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="contracts.links" />
        </div>

        <!-- Create modal -->
        <Teleport to="body">
            <div v-if="modal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
                    <h3 class="font-semibold text-gray-800 mb-4">Tạo hợp đồng lao động</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Nhân viên *</label>
                            <select v-model="form.employee_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">-- Chọn --</option>
                                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Loại hợp đồng *</label>
                            <select v-model="form.type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="probation">Thử việc</option>
                                <option value="full_time">Chính thức</option>
                                <option value="part_time">Bán thời gian</option>
                                <option value="contractor">Hợp đồng dịch vụ</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-sm font-medium text-gray-600 block mb-1">Ngày bắt đầu *</label>
                                <input v-model="form.start_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 block mb-1">Ngày kết thúc</label>
                                <input v-model="form.end_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Lương cơ bản (₫) *</label>
                            <input v-model="form.base_salary" type="number" min="0" step="100000"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Ghi chú</label>
                            <textarea v-model="form.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-4">
                        <button @click="modal.open = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Hủy</button>
                        <button @click="submit" :disabled="form.processing"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm disabled:opacity-50">Lưu</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
