<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

const props = defineProps({ requests: Object, leaveTypes: Array, employees: Array, filters: Object });

const status     = ref(props.filters.status ?? '');
const employeeId = ref(props.filters.employee_id ?? '');

function applyFilters() {
    router.get(route('hr.leaves.index'), { status: status.value || undefined, employee_id: employeeId.value || undefined }, { preserveState: true });
}

const modal = ref({ open: false });
const form = useForm({ employee_id: '', leave_type_id: '', start_date: '', end_date: '', days_count: 1, reason: '' });

function submit() {
    form.post(route('hr.leaves.store'), { onSuccess: () => { modal.value.open = false; form.reset(); } });
}

function approve(id) {
    router.post(route('hr.leaves.approve', id));
}

function reject(id) {
    const notes = prompt('Lý do từ chối (tùy chọn):') ?? '';
    router.post(route('hr.leaves.reject', id), { notes });
}

function remove(id) {
    if (!confirm('Xóa đơn này?')) return;
    router.delete(route('hr.leaves.destroy', id));
}
</script>

<template>
    <AppLayout title="Nghỉ phép">
        <div class="max-w-6xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Đơn nghỉ phép</h1>
                <button v-if="can('employees.manage')" @click="modal.open = true"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700">
                    + Tạo đơn
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex gap-3">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Trạng thái</label>
                    <select v-model="status" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option value="">Tất cả</option>
                        <option value="pending">Chờ duyệt</option>
                        <option value="approved">Đã duyệt</option>
                        <option value="rejected">Từ chối</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Nhân viên</label>
                    <select v-model="employeeId" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option value="">Tất cả</option>
                        <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Nhân viên</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Loại nghỉ</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Từ</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Đến</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Số ngày</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="r in requests.data" :key="r.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ r.employee }}</p>
                                <p class="text-xs text-gray-400">{{ r.employee_code }}</p>
                            </td>
                            <td class="px-4 py-3">
                                {{ r.leave_type }}
                                <span v-if="r.is_paid" class="ml-1 text-xs bg-green-100 text-green-700 px-1 rounded">Có lương</span>
                            </td>
                            <td class="px-4 py-3">{{ r.start_date }}</td>
                            <td class="px-4 py-3">{{ r.end_date }}</td>
                            <td class="px-4 py-3 text-right">{{ r.days_count }}</td>
                            <td class="px-4 py-3"><StatusBadge :color="r.status_color">{{ r.status_label }}</StatusBadge></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <template v-if="can('employees.manage') && r.status === 'pending'">
                                    <button @click="approve(r.id)" class="text-xs text-green-600 hover:underline mr-2">Duyệt</button>
                                    <button @click="reject(r.id)" class="text-xs text-red-400 hover:underline mr-2">Từ chối</button>
                                </template>
                                <button v-if="can('employees.manage') && r.status === 'pending'" @click="remove(r.id)"
                                    class="text-xs text-gray-400 hover:underline">Xóa</button>
                            </td>
                        </tr>
                        <tr v-if="requests.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">Không có đơn nghỉ phép</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="requests.links" />
        </div>

        <!-- Create modal -->
        <Teleport to="body">
            <div v-if="modal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
                    <h3 class="font-semibold text-gray-800 mb-4">Tạo đơn nghỉ phép</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Nhân viên *</label>
                            <select v-model="form.employee_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">-- Chọn --</option>
                                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Loại nghỉ *</label>
                            <select v-model="form.leave_type_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">-- Chọn --</option>
                                <option v-for="t in leaveTypes" :key="t.id" :value="t.id">{{ t.name }} ({{ t.days_per_year }} ngày/năm)</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-sm font-medium text-gray-600 block mb-1">Từ ngày *</label>
                                <input v-model="form.start_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 block mb-1">Đến ngày *</label>
                                <input v-model="form.end_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Số ngày *</label>
                            <input v-model="form.days_count" type="number" min="1"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Lý do</label>
                            <textarea v-model="form.reason" rows="2"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
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
