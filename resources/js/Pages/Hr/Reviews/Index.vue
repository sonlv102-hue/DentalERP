<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

const props = defineProps({ reviews: Array, employees: Array, period: String, filters: Object });

const currentPeriod = ref(props.filters.period);

function applyFilters() {
    router.get(route('hr.reviews.index'), { period: currentPeriod.value }, { preserveState: true });
}

const modal = ref({ open: false, item: null });
const form = useForm({
    employee_id: '', period: props.period,
    overall_score: 3, punctuality_score: 3, quality_score: 3, teamwork_score: 3,
    strengths: '', improvements: '', goals: '',
});

function openModal(review = null) {
    modal.value = { open: true, item: review };
    if (review) {
        Object.assign(form, {
            employee_id: review.employee_id, period: props.period,
            overall_score: review.overall_score, punctuality_score: review.punctuality_score,
            quality_score: review.quality_score, teamwork_score: review.teamwork_score,
            strengths: review.strengths ?? '', improvements: review.improvements ?? '', goals: review.goals ?? '',
        });
    } else {
        form.reset();
        form.period = props.period;
    }
}

function submit() {
    form.post(route('hr.reviews.store'), { onSuccess: () => { modal.value.open = false; } });
}

function finalize(id) {
    if (!confirm('Xác nhận hoàn thành đánh giá này?')) return;
    router.post(route('hr.reviews.finalize', id));
}

function remove(id) {
    if (!confirm('Xóa đánh giá này?')) return;
    router.delete(route('hr.reviews.destroy', id));
}

const SCORE_LABELS = { 1: 'Yếu', 2: 'Trung bình', 3: 'Đạt', 4: 'Tốt', 5: 'Xuất sắc' };
</script>

<template>
    <AppLayout title="Đánh giá nhân viên">
        <div class="max-w-6xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Đánh giá nhân viên</h1>
                <button v-if="can('employees.manage')" @click="openModal()"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700">
                    + Thêm đánh giá
                </button>
            </div>

            <!-- Period filter -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex gap-3 items-center">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Kỳ đánh giá</label>
                    <input v-model="currentPeriod" type="month" @change="applyFilters"
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                </div>
                <span class="text-sm text-gray-500 mt-4">{{ reviews.length }} nhân viên được đánh giá</span>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Nhân viên</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Chuyên môn</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Giờ giấc</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Tinh thần</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Tổng hợp</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">TB</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="r in reviews" :key="r.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ r.employee }}</p>
                                <p class="text-xs text-gray-400">{{ r.employee_code }}</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span :title="SCORE_LABELS[r.quality_score]">{{ r.quality_score }}/5</span>
                            </td>
                            <td class="px-4 py-3 text-center">{{ r.punctuality_score }}/5</td>
                            <td class="px-4 py-3 text-center">{{ r.teamwork_score }}/5</td>
                            <td class="px-4 py-3 text-center">{{ r.overall_score }}/5</td>
                            <td class="px-4 py-3 text-center font-semibold text-primary-600">{{ r.average_score.toFixed(1) }}</td>
                            <td class="px-4 py-3"><StatusBadge :color="r.status_color">{{ r.status_label }}</StatusBadge></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button v-if="can('employees.manage')" @click="openModal(r)"
                                    class="text-xs text-primary-600 hover:underline mr-2">Sửa</button>
                                <button v-if="can('employees.manage') && r.status === 'draft'" @click="finalize(r.id)"
                                    class="text-xs text-green-600 hover:underline mr-2">Hoàn thành</button>
                                <button v-if="can('employees.manage')" @click="remove(r.id)"
                                    class="text-xs text-red-400 hover:underline">Xóa</button>
                            </td>
                        </tr>
                        <tr v-if="reviews.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400">Chưa có đánh giá nào</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="modal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl max-h-screen overflow-y-auto">
                    <h3 class="font-semibold text-gray-800 mb-4">Đánh giá nhân viên</h3>
                    <div class="space-y-3">
                        <div v-if="!modal.item">
                            <label class="text-sm font-medium text-gray-600 block mb-1">Nhân viên *</label>
                            <select v-model="form.employee_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">-- Chọn --</option>
                                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div v-for="field in [
                                { key: 'overall_score', label: 'Tổng hợp' },
                                { key: 'quality_score', label: 'Chuyên môn' },
                                { key: 'punctuality_score', label: 'Giờ giấc' },
                                { key: 'teamwork_score', label: 'Tinh thần đồng đội' },
                            ]" :key="field.key">
                                <label class="text-sm font-medium text-gray-600 block mb-1">{{ field.label }} (1–5)</label>
                                <select v-model="form[field.key]" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option v-for="n in [1,2,3,4,5]" :key="n" :value="n">{{ n }} – {{ SCORE_LABELS[n] }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Điểm mạnh</label>
                            <textarea v-model="form.strengths" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Cần cải thiện</label>
                            <textarea v-model="form.improvements" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Mục tiêu kỳ sau</label>
                            <textarea v-model="form.goals" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
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
