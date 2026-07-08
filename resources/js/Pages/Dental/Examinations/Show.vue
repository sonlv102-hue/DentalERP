<template>
    <AppLayout :title="`Phiếu khám ${examination.code}`">
        <div class="max-w-5xl mx-auto p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a :href="route('dental.examinations.index')" class="text-sm text-blue-600 hover:underline">← Danh sách</a>
                    <h1 class="text-xl font-bold text-gray-800">{{ examination.code }}</h1>
                    <span :class="`inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-${examination.status_color}-100 text-${examination.status_color}-700`">
                        {{ examination.status_label }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <button v-if="examination.status === 'draft' && can('dental.view')"
                        @click="complete" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm">
                        ✓ Hoàn thành khám
                    </button>
                    <a v-if="examination.patient?.id" :href="route('dental.examinations.create') + '?patient_id=' + examination.patient.id"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">
                        Tạo KHDT
                    </a>
                </div>
            </div>

            <!-- Info grid -->
            <div class="bg-white rounded-xl shadow p-5 grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500">Bệnh nhân</p>
                    <p class="font-semibold text-gray-800">{{ examination.patient?.full_name }}</p>
                    <p class="text-sm text-gray-500">{{ examination.patient?.phone }} — {{ examination.patient?.code }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Bác sĩ khám</p>
                    <p class="font-medium text-gray-700">{{ examination.doctor?.full_name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Tư vấn</p>
                    <p class="text-sm text-gray-600">{{ examination.consultant?.full_name || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Chi nhánh / Ngày khám</p>
                    <p class="text-sm text-gray-600">{{ examination.branch_name }} — {{ examination.examined_at }}</p>
                </div>
            </div>

            <!-- Clinical notes -->
            <div class="bg-white rounded-xl shadow p-5 space-y-3">
                <h2 class="font-semibold text-gray-700 border-b pb-2">Ghi chú lâm sàng</h2>
                <div v-if="examination.chief_complaint">
                    <p class="text-xs text-gray-500">Lý do đến khám</p>
                    <p class="text-sm text-gray-800 mt-0.5">{{ examination.chief_complaint }}</p>
                </div>
                <div v-if="examination.diagnosis_note">
                    <p class="text-xs text-gray-500">Chẩn đoán</p>
                    <p class="text-sm text-gray-800 mt-0.5">{{ examination.diagnosis_note }}</p>
                </div>
                <div v-if="examination.examination_note">
                    <p class="text-xs text-gray-500">Ghi chú điều trị</p>
                    <p class="text-sm text-gray-800 mt-0.5">{{ examination.examination_note }}</p>
                </div>
                <div v-if="examination.recommended_plan_note">
                    <p class="text-xs text-gray-500">Đề xuất kế hoạch</p>
                    <p class="text-sm text-gray-800 mt-0.5">{{ examination.recommended_plan_note }}</p>
                </div>
            </div>

            <!-- Conditions -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <h2 class="font-semibold text-gray-700 px-5 py-3 border-b">Bệnh/Vấn đề phát hiện ({{ examination.conditions.length }})</h2>
                <table v-if="examination.conditions.length" class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Bệnh/Vấn đề</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Nhóm</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Răng số</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Mức độ</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="c in examination.conditions" :key="c.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm font-medium text-gray-800">{{ c.condition_name }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500">{{ c.group }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600">{{ c.tooth_no || '—' }}</td>
                            <td class="px-4 py-2">
                                <span v-if="c.severity" :class="{ 'text-red-600': c.severity === 'severe', 'text-orange-500': c.severity === 'moderate', 'text-green-600': c.severity === 'mild' }" class="text-xs font-medium">
                                    {{ { mild: 'Nhẹ', moderate: 'Trung bình', severe: 'Nặng' }[c.severity] ?? c.severity }}
                                </span>
                                <span v-else class="text-gray-300 text-xs">—</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ c.note || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="px-5 py-4 text-sm text-gray-400 text-center">Không ghi nhận bệnh/vấn đề nào.</p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ examination: Object });

function complete() {
    if (!confirm('Hoàn thành phiếu khám này?')) return;
    router.post(route('dental.examinations.complete', props.examination.id));
}
</script>
