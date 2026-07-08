<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ devices: Array, branches: Array });

const showForm  = ref(false);
const editTarget = ref(null);

const form = useForm({
    name: '', ip: '', port: 4370, password: '', serial_number: '', branch_id: null, is_active: true,
});

function openCreate() {
    editTarget.value = null;
    form.reset();
    form.port = 4370;
    form.is_active = true;
    showForm.value = true;
}

function openEdit(d) {
    editTarget.value = d;
    form.name = d.name; form.ip = d.ip; form.port = d.port;
    form.password = ''; form.serial_number = d.serial ?? '';
    form.branch_id = d.branch_id; form.is_active = d.is_active;
    showForm.value = true;
}

function save() {
    if (editTarget.value) {
        form.put(route('hr.attendance-devices.update', editTarget.value.id), {
            onSuccess: () => { showForm.value = false; },
        });
    } else {
        form.post(route('hr.attendance-devices.store'), {
            onSuccess: () => { showForm.value = false; },
        });
    }
}

function remove(d) {
    if (!confirm(`Xóa máy "${d.name}"?`)) return;
    router.delete(route('hr.attendance-devices.destroy', d.id));
}

function sync(d) {
    if (!confirm(`Đồng bộ dữ liệu từ "${d.name}" (${d.ip})?\nMáy phải online và đúng IP.`)) return;
    router.post(route('hr.attendance-devices.sync', d.id));
}
</script>

<template>
    <AppLayout title="Máy chấm công">
        <div class="max-w-4xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Máy chấm công ZKTeco</h1>
                <button v-if="can('employees.manage')" @click="openCreate"
                    class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                    + Thêm máy
                </button>
            </div>

            <!-- Device cards -->
            <div v-if="devices.length === 0" class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400 text-sm">
                Chưa có máy chấm công nào. Thêm máy để bắt đầu đồng bộ dữ liệu.
            </div>

            <div v-for="d in devices" :key="d.id"
                class="bg-white rounded-xl border border-gray-200 p-5 flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <!-- Status dot -->
                    <div :class="d.is_active ? 'bg-green-400' : 'bg-gray-300'" class="w-3 h-3 rounded-full flex-shrink-0" />
                    <div>
                        <p class="font-semibold text-gray-800">{{ d.name }}</p>
                        <p class="text-sm text-gray-500 font-mono">{{ d.ip }}:{{ d.port }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Chi nhánh: {{ d.branch }}
                            <span v-if="d.serial" class="ml-2">· S/N: {{ d.serial }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-shrink-0">
                    <div class="text-right text-xs text-gray-400">
                        <p>Đồng bộ lần cuối</p>
                        <p class="font-medium text-gray-600">{{ d.last_sync_at ?? 'Chưa đồng bộ' }}</p>
                    </div>

                    <button v-if="can('employees.manage')" @click="sync(d)"
                        class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Đồng bộ
                    </button>
                    <button v-if="can('employees.manage')" @click="openEdit(d)"
                        class="px-3 py-1.5 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Sửa
                    </button>
                    <button v-if="can('employees.manage')" @click="remove(d)"
                        class="px-3 py-1.5 text-sm border border-red-200 text-red-600 rounded-lg hover:bg-red-50">
                        Xóa
                    </button>
                </div>
            </div>

            <!-- Info box -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                <p class="font-semibold mb-1">Hướng dẫn kết nối ZKTeco</p>
                <ol class="list-decimal list-inside space-y-1 text-blue-600">
                    <li>Trên máy chấm công: vào <strong>Comm → Ethernet</strong>, cấu hình IP tĩnh trong cùng mạng LAN.</li>
                    <li>Mật khẩu kết nối: mặc định để trống (hoặc <code>0</code>). Kiểm tra trong <strong>Comm → Password</strong>.</li>
                    <li>Nhân viên: vào tab <strong>Nhân viên</strong>, nhập <strong>Mã PIN ZK</strong> trùng với User ID trên máy.</li>
                    <li>Nhấn <strong>Đồng bộ</strong> để kéo dữ liệu — log sẽ tự động tạo bảng công cho ngày tương ứng.</li>
                    <li>Lên lịch tự động: <code>php artisan attendance:sync</code> (có thể thêm vào cron daily).</li>
                </ol>
            </div>
        </div>

        <!-- Create / Edit modal -->
        <Teleport to="body">
            <div v-if="showForm" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">{{ editTarget ? 'Sửa máy chấm công' : 'Thêm máy chấm công' }}</h3>
                        <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="save" class="p-5 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <label class="label">Tên máy <span class="text-red-500">*</span></label>
                                <input v-model="form.name" required class="input" placeholder="Máy tầng 1 — ZKTeco K40" />
                            </div>
                            <div>
                                <label class="label">Địa chỉ IP <span class="text-red-500">*</span></label>
                                <input v-model="form.ip" required class="input" placeholder="192.168.1.201" />
                            </div>
                            <div>
                                <label class="label">Cổng</label>
                                <input v-model.number="form.port" type="number" class="input" />
                            </div>
                            <div>
                                <label class="label">Mật khẩu kết nối</label>
                                <input v-model="form.password" class="input" placeholder="Để trống nếu không có" />
                            </div>
                            <div>
                                <label class="label">Số S/N</label>
                                <input v-model="form.serial_number" class="input" />
                            </div>
                            <div class="col-span-2">
                                <label class="label">Chi nhánh <span class="text-red-500">*</span></label>
                                <select v-model="form.branch_id" required class="input">
                                    <option :value="null">-- Chọn --</option>
                                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                            </div>
                            <div class="col-span-2 flex items-center gap-2">
                                <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded" />
                                <label for="is_active" class="text-sm text-gray-700">Đang hoạt động</label>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showForm = false"
                                class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="form.processing"
                                class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                                {{ editTarget ? 'Cập nhật' : 'Thêm máy' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.label { @apply block text-sm font-medium text-gray-700 mb-1; }
.input  { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
