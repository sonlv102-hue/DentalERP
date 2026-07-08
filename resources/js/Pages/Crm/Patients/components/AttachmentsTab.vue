<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

const props = defineProps({
    patientId: Number,
    attachments: Array,
    attachmentTypes: Array,
});

const showForm = ref(false);
const uploading = ref(false);
const form = ref({ type: 'document', title: '', file: null });
const fileInput = ref(null);

function onFileChange(e) {
    form.value.file = e.target.files[0] || null;
}

function upload() {
    if (!form.value.file || !form.value.title) return;
    uploading.value = true;

    const fd = new FormData();
    fd.append('type', form.value.type);
    fd.append('title', form.value.title);
    fd.append('file', form.value.file);

    router.post(route('patient-attachments.store', props.patientId), fd, {
        forceFormData: true,
        onFinish: () => {
            uploading.value = false;
            showForm.value = false;
            form.value = { type: 'document', title: '', file: null };
        },
    });
}

function remove(id) {
    if (!confirm('Xóa tài liệu này?')) return;
    router.delete(route('patient-attachments.destroy', id));
}

function formatSize(bytes) {
    if (!bytes) return '';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function isImage(mime) {
    return mime && mime.startsWith('image/');
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-700">Tài liệu đính kèm ({{ attachments.length }})</h3>
            <button v-if="can('patients.edit')" @click="showForm = !showForm"
                class="px-3 py-1.5 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                + Tải lên
            </button>
        </div>

        <!-- Upload form -->
        <div v-if="showForm" class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Loại</label>
                    <select v-model="form.type" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option v-for="t in attachmentTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tiêu đề</label>
                    <input v-model="form.title" type="text" placeholder="Nhập tiêu đề..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                </div>
            </div>
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">File (JPEG, PNG, PDF, DOC, max 5MB)</label>
                <input ref="fileInput" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx"
                    @change="onFileChange" class="text-sm" />
            </div>
            <div class="flex gap-2">
                <button @click="upload" :disabled="uploading || !form.file || !form.title"
                    class="px-4 py-1.5 bg-primary-600 text-white text-sm rounded-lg disabled:opacity-50">
                    {{ uploading ? 'Đang tải...' : 'Tải lên' }}
                </button>
                <button @click="showForm = false" class="px-4 py-1.5 border border-gray-300 text-sm rounded-lg">Hủy</button>
            </div>
        </div>

        <!-- List -->
        <div v-if="attachments.length === 0" class="text-center py-8 text-gray-400 text-sm">Chưa có tài liệu nào</div>
        <div v-else class="space-y-2">
            <div v-for="a in attachments" :key="a.id"
                class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                <!-- Thumbnail / icon -->
                <div class="w-10 h-10 flex-shrink-0 rounded overflow-hidden bg-gray-100 flex items-center justify-center">
                    <img v-if="isImage(a.mime_type)" :src="a.file_url" class="w-full h-full object-cover" />
                    <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ a.title }}</p>
                    <p class="text-xs text-gray-500">{{ a.type_label }} · {{ formatSize(a.file_size) }} · {{ a.created_at }}</p>
                </div>
                <StatusBadge :color="a.type_color">{{ a.type_label }}</StatusBadge>
                <a :href="a.file_url" target="_blank"
                    class="text-xs text-primary-600 hover:underline whitespace-nowrap">Xem</a>
                <button v-if="can('patients.edit')" @click="remove(a.id)"
                    class="text-red-400 hover:text-red-600 ml-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
