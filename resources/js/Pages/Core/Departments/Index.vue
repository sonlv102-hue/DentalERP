<template>
    <AppLayout title="Bộ phận">
        <div class="max-w-3xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Bộ phận / Phòng ban</h1>
                <button @click="openCreate" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                    + Thêm bộ phận
                </button>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tên bộ phận</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Chi nhánh</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mô tả</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">NV</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="departments.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">Chưa có bộ phận nào</td>
                        </tr>
                        <tr v-for="d in departments" :key="d.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ d.name }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ d.branch ?? 'Tất cả' }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ d.description ?? '—' }}</td>
                            <td class="px-4 py-3 text-center font-mono text-gray-600">{{ d.employee_count }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="d.is_active ? 'text-green-600' : 'text-gray-400'" class="text-xs font-medium">
                                    {{ d.is_active ? 'Hoạt động' : 'Tắt' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button @click="openEdit(d)" class="text-xs text-gray-400 hover:text-gray-600 mr-2">Sửa</button>
                                <button @click="remove(d)" class="text-xs text-red-300 hover:text-red-500">Xóa</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="modal.show" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">{{ modal.id ? 'Sửa bộ phận' : 'Thêm bộ phận' }}</h3>
                        <button @click="modal.show = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="save" class="p-5 space-y-4">
                        <div>
                            <label class="label">Tên bộ phận <span class="text-red-500">*</span></label>
                            <input v-model="modal.name" required class="input-field" />
                        </div>
                        <div>
                            <label class="label">Chi nhánh (để trống = áp dụng tất cả)</label>
                            <select v-model="modal.branch_id" class="input-field">
                                <option :value="null">Tất cả chi nhánh</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Mô tả</label>
                            <input v-model="modal.description" class="input-field" />
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="modal.show = false" class="px-4 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="saving" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg disabled:opacity-50">
                                {{ modal.id ? 'Cập nhật' : 'Tạo' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props  = defineProps({ departments: Array, branches: Array });
const saving = ref(false);
const modal  = reactive({ show: false, id: null, name: '', branch_id: null, description: '' });

function openCreate() { Object.assign(modal, { show: true, id: null, name: '', branch_id: null, description: '' }); }
function openEdit(d)  { Object.assign(modal, { show: true, id: d.id, name: d.name, branch_id: d.branch_id ?? null, description: d.description ?? '' }); }
function remove(d)    { if (confirm(`Xóa bộ phận "${d.name}"?`)) router.delete(route('core.departments.destroy', d.id)); }

function save() {
    saving.value = true;
    const url    = modal.id ? route('core.departments.update', modal.id) : route('core.departments.store');
    const method = modal.id ? 'put' : 'post';
    router[method](url, { name: modal.name, branch_id: modal.branch_id, description: modal.description },
        { onSuccess: () => { modal.show = false; }, onFinish: () => { saving.value = false; } });
}
</script>

<style scoped>
.label       { @apply block text-sm font-medium text-gray-700 mb-1; }
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
