<template>
    <AppLayout title="Bảng lương">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-900">BẢNG TÍNH - THANH TOÁN TIỀN LƯƠNG</h1>
                <button @click="showCreate = true"
                    class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    + Tạo bảng lương
                </button>
            </div>

            <!-- List -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Mã bảng</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Kỳ lương</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Tổng thực lĩnh</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Người lập</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Trạng thái</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Ngày tạo</th>
                            <th />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="p in payrolls.data" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-gray-700">{{ p.code }}</td>
                            <td class="px-4 py-3 text-gray-800 font-medium">{{ p.period_label }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-green-700">{{ vnd(p.total_net_salary) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ p.created_by }}</td>
                            <td class="px-4 py-3">
                                <span :class="`px-2 py-0.5 rounded-full text-xs font-medium bg-${p.status_color}-100 text-${p.status_color}-700`">
                                    {{ p.status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ p.created_at }}</td>
                            <td class="px-4 py-3">
                                <Link :href="route('accounting.payrolls.show', p.id)"
                                    class="text-primary-600 hover:underline text-xs">Xem chi tiết</Link>
                            </td>
                        </tr>
                        <tr v-if="!payrolls.data.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">Chưa có bảng lương nào.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Create modal -->
            <div v-if="showCreate" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl p-6 w-[440px] shadow-xl">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Tạo bảng lương mới</h2>
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-medium text-gray-600 block mb-1">Tháng</label>
                                <select v-model="form.month" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option v-for="m in 12" :key="m" :value="m">Tháng {{ m }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600 block mb-1">Năm</label>
                                <input v-model.number="form.year" type="number" min="2020" max="2100"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600 block mb-1">Liên kết kỳ chấm công (tùy chọn)</label>
                            <select v-model="form.attendance_period_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">— Không liên kết —</option>
                                <option v-for="ap in attendancePeriods" :key="ap.id" :value="ap.id">{{ ap.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600 block mb-1">Ghi chú</label>
                            <textarea v-model="form.note" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-5">
                        <button @click="showCreate = false" class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Hủy</button>
                        <button @click="submitCreate" :disabled="creating"
                            class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50">
                            {{ creating ? 'Đang tạo...' : 'Tạo bảng lương' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({ payrolls: Object, filters: Object, attendancePeriods: Array });

const showCreate = ref(false);
const creating   = ref(false);
const now        = new Date();
const form       = ref({ month: now.getMonth() + 1, year: now.getFullYear(), attendance_period_id: '', note: '' });

function vnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }

function submitCreate() {
    creating.value = true;
    router.post(route('accounting.payrolls.store'), form.value, {
        onFinish: () => { creating.value = false; },
        onSuccess: () => { showCreate.value = false; },
    });
}
</script>
