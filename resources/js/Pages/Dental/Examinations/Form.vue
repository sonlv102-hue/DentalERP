<template>
    <AppLayout title="Tạo phiếu khám">
        <div class="max-w-4xl mx-auto p-6 space-y-6">
            <div class="flex items-center gap-4">
                <a :href="route('dental.examinations.index')" class="text-sm text-blue-600 hover:underline">← Danh sách</a>
                <h1 class="text-xl font-bold text-gray-800">Tạo phiếu khám mới</h1>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Basic info -->
                <div class="bg-white rounded-xl shadow p-5 space-y-4">
                    <h2 class="font-semibold text-gray-700 border-b pb-2">Thông tin khám</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Bệnh nhân *</label>
                            <select v-model="form.patient_id" class="w-full border rounded-lg px-3 py-2 text-sm">
                                <option value="">-- Chọn bệnh nhân --</option>
                                <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }} ({{ p.phone }})</option>
                            </select>
                            <p v-if="errors.patient_id" class="text-red-500 text-xs mt-1">{{ errors.patient_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Chi nhánh *</label>
                            <select v-model="form.branch_id" class="w-full border rounded-lg px-3 py-2 text-sm">
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Bác sĩ khám *</label>
                            <select v-model="form.doctor_id" class="w-full border rounded-lg px-3 py-2 text-sm">
                                <option value="">-- Chọn bác sĩ --</option>
                                <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.full_name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Tư vấn viên</label>
                            <select v-model="form.consultant_id" class="w-full border rounded-lg px-3 py-2 text-sm">
                                <option value="">-- Không có --</option>
                                <option v-for="c in consultants" :key="c.id" :value="c.id">{{ c.full_name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Ngày giờ khám</label>
                            <input v-model="form.examined_at" type="datetime-local" class="w-full border rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Lý do đến khám</label>
                        <textarea v-model="form.chief_complaint" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Bệnh nhân than đau/vấn đề gì..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Ghi chú chẩn đoán</label>
                        <textarea v-model="form.diagnosis_note" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Ghi chú điều trị</label>
                        <textarea v-model="form.examination_note" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Đề xuất kế hoạch điều trị</label>
                        <textarea v-model="form.recommended_plan_note" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                </div>

                <!-- Conditions -->
                <div class="bg-white rounded-xl shadow p-5 space-y-4">
                    <div class="flex items-center justify-between border-b pb-2">
                        <h2 class="font-semibold text-gray-700">Bệnh/Vấn đề phát hiện</h2>
                        <button type="button" @click="addCondition" class="px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-sm">+ Thêm</button>
                    </div>
                    <div v-for="(cond, idx) in form.conditions" :key="idx" class="grid grid-cols-12 gap-3 items-end border-b pb-3">
                        <div class="col-span-4">
                            <label class="text-xs text-gray-500 mb-1 block">Bệnh/Vấn đề *</label>
                            <select v-model="cond.condition_id" class="w-full border rounded-lg px-3 py-2 text-sm">
                                <option value="">-- Chọn --</option>
                                <option v-for="c in conditions" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="text-xs text-gray-500 mb-1 block">Răng số</label>
                            <input v-model="cond.tooth_no" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="11, 21..." />
                        </div>
                        <div class="col-span-2">
                            <label class="text-xs text-gray-500 mb-1 block">Mức độ</label>
                            <select v-model="cond.severity" class="w-full border rounded-lg px-3 py-2 text-sm">
                                <option value="">—</option>
                                <option value="mild">Nhẹ</option>
                                <option value="moderate">Trung bình</option>
                                <option value="severe">Nặng</option>
                            </select>
                        </div>
                        <div class="col-span-3">
                            <label class="text-xs text-gray-500 mb-1 block">Ghi chú</label>
                            <input v-model="cond.note" class="w-full border rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div class="col-span-1">
                            <button type="button" @click="removeCondition(idx)" class="text-red-500 hover:text-red-700 text-xs">✕</button>
                        </div>
                    </div>
                    <p v-if="!form.conditions.length" class="text-sm text-gray-400 text-center py-2">Chưa có bệnh/vấn đề. Nhấn "+ Thêm" để ghi nhận.</p>
                </div>

                <div class="flex justify-end gap-3">
                    <a :href="route('dental.examinations.index')" class="px-4 py-2 border rounded-lg text-sm">Hủy</a>
                    <button type="submit" :disabled="inertiaForm.processing"
                        class="px-6 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium disabled:opacity-50">
                        Tạo phiếu khám
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({ patients: Array, doctors: Array, consultants: Array, conditions: Array, branches: Array, patient_id: [String, Number] });

const form = reactive({
    patient_id: props.patient_id ?? '',
    branch_id: props.branches?.[0]?.id ?? '',
    doctor_id: '',
    consultant_id: '',
    chief_complaint: '',
    diagnosis_note: '',
    examination_note: '',
    recommended_plan_note: '',
    examined_at: new Date().toISOString().slice(0, 16),
    conditions: [],
});

const inertiaForm = useForm({});
const errors = {};

function addCondition() {
    form.conditions.push({ condition_id: '', tooth_no: '', severity: '', note: '' });
}

function removeCondition(idx) {
    form.conditions.splice(idx, 1);
}

function submit() {
    inertiaForm.transform(() => form).post(route('dental.examinations.store'));
}
</script>
