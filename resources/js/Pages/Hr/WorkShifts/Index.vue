<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

const props = defineProps({ shifts: Array, branches: Array });

const modal = ref({ open: false, item: null });
const DAY_LABELS = ['', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];

function openModal(item = null) {
    modal.value = {
        open: true,
        item,
    };
    if (item) {
        form.name = item.name;
        form.branch_id = item.branch_id ?? '';
        form.start_time = item.start_time;
        form.end_time = item.end_time;
        form.days_of_week = item.days_of_week ?? [];
        form.is_active = item.is_active;
    } else {
        form.reset();
    }
}

const form = useForm({ name: '', branch_id: '', start_time: '08:00', end_time: '17:00', days_of_week: [], is_active: true });

function submit() {
    if (modal.value.item) {
        form.put(route('hr.work-shifts.update', modal.value.item.id), { onSuccess: () => { modal.value.open = false; } });
    } else {
        form.post(route('hr.work-shifts.store'), { onSuccess: () => { modal.value.open = false; form.reset(); } });
    }
}

function deactivate(id) {
    if (!confirm('Tắt ca này?')) return;
    useForm({}).delete(route('hr.work-shifts.destroy', id));
}

function toggleDay(day) {
    const idx = form.days_of_week.indexOf(day);
    if (idx >= 0) form.days_of_week.splice(idx, 1);
    else form.days_of_week.push(day);
}
</script>

<template>
    <AppLayout title="Ca làm việc">
        <div class="max-w-5xl">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl font-semibold text-gray-800">Ca làm việc</h1>
                <button v-if="can('employees.manage')" @click="openModal()"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700">
                    + Thêm ca
                </button>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Tên ca</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Giờ làm</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Ngày trong tuần</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Chi nhánh</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="s in shifts" :key="s.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ s.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ s.start_time }} – {{ s.end_time }}</td>
                            <td class="px-4 py-3">
                                <span v-for="d in (s.days_of_week ?? [])" :key="d"
                                    class="inline-block bg-blue-100 text-blue-700 text-xs px-1.5 py-0.5 rounded mr-1">
                                    {{ DAY_LABELS[d] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ s.branch ?? 'Tất cả' }}</td>
                            <td class="px-4 py-3">
                                <span :class="['text-xs px-2 py-0.5 rounded-full', s.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                                    {{ s.is_active ? 'Hoạt động' : 'Tắt' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="can('employees.manage')" @click="openModal(s)"
                                    class="text-xs text-primary-600 hover:underline mr-3">Sửa</button>
                                <button v-if="can('employees.manage') && s.is_active" @click="deactivate(s.id)"
                                    class="text-xs text-red-400 hover:underline">Tắt</button>
                            </td>
                        </tr>
                        <tr v-if="shifts.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">Chưa có ca làm việc</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="modal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
                    <h3 class="font-semibold text-gray-800 mb-4">{{ modal.item ? 'Sửa ca làm việc' : 'Thêm ca làm việc' }}</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Tên ca *</label>
                            <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Giờ bắt đầu</label>
                                <input v-model="form.start_time" type="time" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Giờ kết thúc</label>
                                <input v-model="form.end_time" type="time" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Ngày trong tuần</label>
                            <div class="flex gap-1">
                                <button v-for="d in [1,2,3,4,5,6,7]" :key="d"
                                    @click="toggleDay(d)"
                                    :class="['px-2 py-1 text-xs rounded border', form.days_of_week.includes(d) ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-300 text-gray-600']">
                                    {{ DAY_LABELS[d] }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Chi nhánh</label>
                            <select v-model="form.branch_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Tất cả chi nhánh</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
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
