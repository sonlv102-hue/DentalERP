<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

const props = defineProps({
    patientId: Number,
    consentForms: Array,
    treatmentPlans: Array,
});

const showCreate = ref(false);
const signModal = ref({ open: false, id: null, signedByName: '' });

const createForm = useForm({ title: '', content: '', treatment_plan_id: '', notes: '' });

function submit() {
    createForm.post(route('consent-forms.store', props.patientId), {
        onSuccess: () => { showCreate.value = false; createForm.reset(); },
    });
}

function openSign(id) {
    signModal.value = { open: true, id, signedByName: '' };
}

const signForm = useForm({ signed_by_name: '' });
function confirmSign() {
    signForm.signed_by_name = signModal.value.signedByName;
    signForm.post(route('consent-forms.sign', signModal.value.id), {
        onSuccess: () => { signModal.value.open = false; },
    });
}

function remove(id) {
    if (!confirm('Xóa phiếu đồng ý này?')) return;
    useForm({}).delete(route('consent-forms.destroy', id));
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-700">Phiếu đồng ý điều trị ({{ consentForms.length }})</h3>
            <button v-if="can('patients.edit')" @click="showCreate = !showCreate"
                class="px-3 py-1.5 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                + Tạo phiếu
            </button>
        </div>

        <!-- Create form -->
        <div v-if="showCreate" class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tiêu đề *</label>
                    <input v-model="createForm.title" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                    <p v-if="createForm.errors.title" class="text-red-500 text-xs mt-1">{{ createForm.errors.title }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Kế hoạch điều trị</label>
                    <select v-model="createForm.treatment_plan_id" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option value="">-- Không liên kết --</option>
                        <option v-for="tp in treatmentPlans" :key="tp.id" :value="tp.id">{{ tp.code }}</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Nội dung *</label>
                <textarea v-model="createForm.content" rows="4"
                    class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
            </div>
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Ghi chú</label>
                <input v-model="createForm.notes" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
            </div>
            <div class="flex gap-2">
                <button @click="submit" :disabled="createForm.processing"
                    class="px-4 py-1.5 bg-primary-600 text-white text-sm rounded-lg disabled:opacity-50">Lưu</button>
                <button @click="showCreate = false" class="px-4 py-1.5 border border-gray-300 text-sm rounded-lg">Hủy</button>
            </div>
        </div>

        <!-- List -->
        <div v-if="consentForms.length === 0" class="text-center py-8 text-gray-400 text-sm">Chưa có phiếu đồng ý nào</div>
        <div v-else class="space-y-3">
            <div v-for="cf in consentForms" :key="cf.id"
                class="p-4 bg-white border border-gray-200 rounded-lg">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-medium text-gray-800">{{ cf.title }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">Tạo ngày {{ cf.created_at }}</p>
                        <p v-if="cf.signed_at" class="text-xs text-green-600 mt-0.5">
                            Đã ký bởi {{ cf.signed_by_name }} lúc {{ cf.signed_at }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 ml-4 flex-shrink-0">
                        <StatusBadge :color="cf.status_color">{{ cf.status_label }}</StatusBadge>
                        <button v-if="can('patients.edit') && cf.status === 'pending'" @click="openSign(cf.id)"
                            class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200">Ký</button>
                        <button v-if="can('patients.edit') && cf.status === 'pending'" @click="remove(cf.id)"
                            class="text-red-400 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-2 line-clamp-2">{{ cf.content }}</p>
            </div>
        </div>

        <!-- Sign modal -->
        <Teleport to="body">
            <div v-if="signModal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
                    <h3 class="font-semibold text-gray-800 mb-4">Xác nhận ký phiếu đồng ý</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-600 mb-1">Tên người ký *</label>
                        <input v-model="signModal.signedByName" type="text" placeholder="Họ và tên bệnh nhân hoặc người giám hộ"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button @click="signModal.open = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Hủy</button>
                        <button @click="confirmSign" :disabled="!signModal.signedByName || signForm.processing"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm disabled:opacity-50">Xác nhận ký</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
