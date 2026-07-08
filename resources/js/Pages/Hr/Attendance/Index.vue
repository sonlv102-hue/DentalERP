<template>
    <AppLayout title="Bảng chấm công">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">Bảng chấm công</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Quản lý kỳ chấm công theo tháng</p>
                </div>
                <button @click="showCreate = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                    + Tạo kỳ chấm công
                </button>
            </div>

            <!-- Periods table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left">Mã kỳ</th>
                            <th class="px-4 py-3 text-left">Tháng</th>
                            <th class="px-4 py-3 text-left">Người lập</th>
                            <th class="px-4 py-3 text-left">Ngày lập</th>
                            <th class="px-4 py-3 text-left">Ngày khóa</th>
                            <th class="px-4 py-3 text-center">Trạng thái</th>
                            <th class="px-4 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!periods.length">
                            <td colspan="7" class="text-center py-10 text-gray-400">Chưa có kỳ chấm công nào</td>
                        </tr>
                        <tr v-for="p in periods" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ p.code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ p.period_label }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ p.created_by || '—' }}</td>
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ p.created_at }}</td>
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ p.locked_at || '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="`inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-${p.status_color}-100 text-${p.status_color}-700`">
                                    {{ p.status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('hr.attendance.show', p.id)"
                                    class="text-xs text-primary-600 hover:text-primary-800 font-medium">
                                    Xem bảng →
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create modal -->
        <div v-if="showCreate" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-6 w-96 shadow-xl">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Tạo kỳ chấm công mới</h2>
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Tháng</label>
                            <select v-model="form.month" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option v-for="m in 12" :key="m" :value="m">Tháng {{ m }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Năm</label>
                            <input v-model="form.year" type="number" min="2020" max="2099" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Ghi chú</label>
                        <textarea v-model="form.note" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none" />
                    </div>
                    <p v-if="createError" class="text-xs text-red-600">{{ createError }}</p>
                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" @click="showCreate = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
                        <button type="submit" :disabled="creating" class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50">Tạo kỳ</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

defineProps({ periods: Array, statuses: Array });

const showCreate  = ref(false);
const creating    = ref(false);
const createError = ref('');
const now         = new Date();
const form        = ref({ month: now.getMonth() + 1, year: now.getFullYear(), note: '' });

function submitCreate() {
    creating.value    = true;
    createError.value = '';
    router.post(route('hr.attendance.store'), form.value, {
        onError: (e) => { createError.value = e.month || e.year || e.error || 'Lỗi tạo kỳ.'; },
        onFinish: () => { creating.value = false; },
        onSuccess: () => { showCreate.value = false; },
    });
}
</script>
