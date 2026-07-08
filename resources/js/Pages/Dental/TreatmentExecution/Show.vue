<template>
    <AppLayout :title="`Thực hiện điều trị — ${item.name}`">
        <div class="max-w-6xl mx-auto p-6 space-y-5">
            <div class="flex items-center gap-4">
                <button @click="$inertia.back()" class="text-sm text-blue-600 hover:underline">← Quay lại</button>
                <h1 class="text-xl font-bold text-gray-800">{{ item.name }}</h1>
                <span :class="`inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-${item.status_color}-100 text-${item.status_color}-700`">
                    {{ item.status_label }}
                </span>
            </div>

            <!-- Item summary -->
            <div class="bg-white rounded-xl shadow p-4 grid grid-cols-3 gap-4 text-sm">
                <div><p class="text-xs text-gray-500">Bệnh nhân</p><p class="font-medium">{{ item.patient_name }}</p></div>
                <div><p class="text-xs text-gray-500">Kế hoạch</p><p class="font-medium">{{ item.plan_code }}</p></div>
                <div><p class="text-xs text-gray-500">BS phụ trách</p><p class="font-medium">{{ item.doctor_name || '—' }}</p></div>
            </div>

            <!-- Status change -->
            <div class="bg-white rounded-xl shadow p-4 flex items-center gap-4">
                <span class="text-sm font-medium text-gray-700">Cập nhật trạng thái dịch vụ:</span>
                <select v-model="newStatus" class="border rounded-lg px-3 py-2 text-sm">
                    <option v-for="s in step_statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <button @click="updateStatus" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">Cập nhật</button>
            </div>

            <!-- Step executions -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 border-b">
                    <h2 class="font-semibold text-gray-700">Công đoạn đã thực hiện</h2>
                    <button @click="openAddExecution" class="px-3 py-1.5 bg-primary-600 text-white rounded-lg text-sm">+ Ghi nhận công đoạn</button>
                </div>

                <div v-for="ex in executions" :key="ex.id" class="border-b last:border-b-0 p-4 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-medium text-sm text-gray-800">{{ ex.step_name }}</span>
                            <span :class="`ml-2 inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-${ex.status_color}-100 text-${ex.status_color}-700`">{{ ex.status_label }}</span>
                            <span v-if="ex.quality_status === 'failed'" class="ml-2 inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">KT: Không đạt</span>
                        </div>
                        <div class="flex gap-2">
                            <button v-if="ex.status !== 'done'" @click="completeExecution(ex)"
                                class="px-3 py-1 text-xs bg-green-600 text-white rounded-lg">Hoàn thành</button>
                        </div>
                    </div>
                    <div class="mt-2 grid grid-cols-3 gap-2 text-xs text-gray-500">
                        <span>Người thực hiện: <strong>{{ ex.performer_name }}</strong></span>
                        <span>Phụ tá: {{ ex.assistant_name || '—' }}</span>
                        <span>{{ ex.started_at }} → {{ ex.ended_at || '...' }}</span>
                    </div>
                    <div v-if="ex.participants?.length" class="mt-2 flex flex-wrap gap-2">
                        <span v-for="p in ex.participants" :key="p.id" class="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full">
                            {{ p.employee_name }} ({{ p.share_percent }}%)
                        </span>
                    </div>
                    <p v-if="ex.note" class="mt-1 text-xs text-gray-500 italic">{{ ex.note }}</p>
                </div>

                <p v-if="!executions.length" class="px-5 py-6 text-center text-sm text-gray-400">Chưa ghi nhận công đoạn nào.</p>
            </div>
        </div>

        <!-- Add Execution Modal -->
        <div v-if="showExecModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4">
                <h2 class="text-lg font-bold">Ghi nhận công đoạn</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Công đoạn *</label>
                        <select v-model="execForm.service_step_id" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">-- Chọn công đoạn --</option>
                            <option v-for="s in steps" :key="s.id" :value="s.id">{{ s.step_name }} ({{ s.kpi_share_percent }}% KPI)</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Người thực hiện *</label>
                            <select v-model="execForm.performed_by" class="w-full border rounded-lg px-3 py-2 text-sm">
                                <option value="">-- Chọn --</option>
                                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.full_name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Phụ tá</label>
                            <select v-model="execForm.assisted_by" class="w-full border rounded-lg px-3 py-2 text-sm">
                                <option value="">-- Không có --</option>
                                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.full_name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Bắt đầu</label>
                            <input v-model="execForm.started_at" type="datetime-local" class="w-full border rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Kết thúc</label>
                            <input v-model="execForm.ended_at" type="datetime-local" class="w-full border rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Ghi chú</label>
                        <textarea v-model="execForm.note" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button @click="showExecModal = false" class="px-4 py-2 border rounded-lg text-sm">Hủy</button>
                    <button @click="submitExecution" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium">Ghi nhận</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({ item: Object, steps: Array, executions: Array, employees: Array, step_statuses: Array });

const showExecModal = ref(false);
const newStatus = ref(props.item.status);
const execForm = ref({});

function openAddExecution() {
    execForm.value = { service_step_id: '', performed_by: '', assisted_by: '', started_at: new Date().toISOString().slice(0, 16), ended_at: '', note: '' };
    showExecModal.value = true;
}

function submitExecution() {
    router.post(route('dental.treatment-items.executions.store', props.item.id), execForm.value, {
        onSuccess: () => { showExecModal.value = false; },
    });
}

function completeExecution(ex) {
    if (!confirm('Đánh dấu hoàn thành công đoạn này?')) return;
    router.post(route('dental.step-executions.complete', ex.id), { ended_at: new Date().toISOString().slice(0, 16), quality_status: 'passed' });
}

function updateStatus() {
    if (!confirm(`Cập nhật trạng thái thành "${newStatus.value}"?`)) return;
    router.post(route('dental.treatment-items.status', props.item.id), { status: newStatus.value });
}
</script>
