<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

const props = defineProps({ timesheets: Array, shifts: Array, employees: Array, branches: Array, filters: Object });

const period   = ref(props.filters.period);
const branchId = ref(props.filters.branch_id ?? '');

function applyFilters() {
    router.get(route('hr.timesheets.index'), { period: period.value, branch_id: branchId.value || undefined }, { preserveState: true });
}

const modal = ref({ open: false });
const form = useForm({ employee_id: '', branch_id: '', shift_id: '', work_date: '', check_in: '', check_out: '', ot_hours: 0, notes: '' });

function openAdd() { modal.value.open = true; form.reset(); }

function submit() {
    form.post(route('hr.timesheets.store'), {
        onSuccess: () => { modal.value.open = false; },
    });
}

function approve(id) {
    router.post(route('hr.timesheets.approve', id));
}

function remove(id) {
    if (!confirm('Xóa bản ghi này?')) return;
    router.delete(route('hr.timesheets.destroy', id));
}
</script>

<template>
    <AppLayout title="Chấm công">
        <div class="max-w-6xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Chấm công</h1>
                <button v-if="can('employees.manage')" @click="openAdd()"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700">
                    + Ghi chấm công
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex gap-3">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Tháng</label>
                    <input v-model="period" type="month" @change="applyFilters"
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Chi nhánh</label>
                    <select v-model="branchId" @change="applyFilters"
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option value="">Tất cả</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Nhân viên</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Ngày</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Ca</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Vào</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Ra</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Giờ</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">OT</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="t in timesheets" :key="t.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <p class="font-medium">{{ t.employee }}</p>
                                <p class="text-xs text-gray-400">{{ t.employee_code }}</p>
                            </td>
                            <td class="px-4 py-2">{{ t.work_date }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ t.shift ?? '—' }}</td>
                            <td class="px-4 py-2">{{ t.check_in ?? '—' }}</td>
                            <td class="px-4 py-2">{{ t.check_out ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">{{ t.hours_worked.toFixed(1) }}</td>
                            <td class="px-4 py-2 text-right">{{ t.ot_hours }}</td>
                            <td class="px-4 py-2">
                                <StatusBadge :color="t.status_color">{{ t.status_label }}</StatusBadge>
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <button v-if="can('employees.manage') && t.status === 'pending'" @click="approve(t.id)"
                                    class="text-xs text-green-600 hover:underline mr-2">Duyệt</button>
                                <button v-if="can('employees.manage')" @click="remove(t.id)"
                                    class="text-xs text-red-400 hover:underline">Xóa</button>
                            </td>
                        </tr>
                        <tr v-if="timesheets.length === 0">
                            <td colspan="9" class="px-4 py-8 text-center text-gray-400">Không có dữ liệu</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add modal -->
        <Teleport to="body">
            <div v-if="modal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
                    <h3 class="font-semibold text-gray-800 mb-4">Ghi chấm công</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Nhân viên *</label>
                            <select v-model="form.employee_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">-- Chọn --</option>
                                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-sm font-medium text-gray-600 block mb-1">Chi nhánh *</label>
                                <select v-model="form.branch_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- Chọn --</option>
                                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 block mb-1">Ngày *</label>
                                <input v-model="form.work_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-sm font-medium text-gray-600 block mb-1">Vào</label>
                                <input v-model="form.check_in" type="time" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 block mb-1">Ra</label>
                                <input v-model="form.check_out" type="time" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Ca làm</label>
                            <select v-model="form.shift_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">-- Không chọn --</option>
                                <option v-for="s in shifts" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Giờ OT</label>
                            <input v-model="form.ot_hours" type="number" min="0" max="12" step="0.5"
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
