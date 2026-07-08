<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

const props = defineProps({ suppliers: Object, filters: Object });

const search   = ref(props.filters.search ?? '');
const isActive = ref(props.filters.is_active ?? '');

function applyFilters() {
    router.get(route('accounting.suppliers.index'), { search: search.value || undefined, is_active: isActive.value !== '' ? isActive.value : undefined }, { preserveState: true });
}

const modal = ref({ open: false, item: null });
const form = useForm({ name: '', phone: '', email: '', address: '', tax_code: '', bank_account: '', bank_name: '', contact_person: '', is_active: true });

function openModal(item = null) {
    modal.value = { open: true, item };
    if (item) {
        Object.assign(form, { ...item });
    } else {
        form.reset();
        form.is_active = true;
    }
}

function submit() {
    if (modal.value.item) {
        form.put(route('accounting.suppliers.update', modal.value.item.id), { onSuccess: () => { modal.value.open = false; } });
    } else {
        form.post(route('accounting.suppliers.store'), { onSuccess: () => { modal.value.open = false; form.reset(); } });
    }
}
</script>

<template>
    <AppLayout title="Nhà cung cấp">
        <div class="max-w-6xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Nhà cung cấp</h1>
                <button v-if="can('accounting.manage')" @click="openModal()"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700">
                    + Thêm NCC
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex gap-3">
                <input v-model="search" @keydown.enter="applyFilters" type="text" placeholder="Tìm tên nhà cung cấp..."
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-64" />
                <select v-model="isActive" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1">Hoạt động</option>
                    <option value="0">Đã vô hiệu</option>
                </select>
                <button @click="applyFilters" class="px-4 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Tìm</button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Tên NCC</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Điện thoại</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">MST</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Người liên hệ</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Ngân hàng</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="s in suppliers.data" :key="s.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ s.name }}</p>
                                <p class="text-xs text-gray-400">{{ s.email }}</p>
                            </td>
                            <td class="px-4 py-3">{{ s.phone ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ s.tax_code ?? '—' }}</td>
                            <td class="px-4 py-3">{{ s.contact_person ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs">
                                <span v-if="s.bank_account">{{ s.bank_name }} · {{ s.bank_account }}</span>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="['text-xs px-2 py-0.5 rounded-full', s.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400']">
                                    {{ s.is_active ? 'Hoạt động' : 'Vô hiệu' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="can('accounting.manage')" @click="openModal(s)"
                                    class="text-xs text-primary-600 hover:underline">Sửa</button>
                            </td>
                        </tr>
                        <tr v-if="suppliers.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">Chưa có nhà cung cấp</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="suppliers.links" />
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="modal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl">
                    <h3 class="font-semibold text-gray-800 mb-4">{{ modal.item ? 'Sửa nhà cung cấp' : 'Thêm nhà cung cấp' }}</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="text-sm font-medium text-gray-600 block mb-1">Tên NCC *</label>
                            <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Điện thoại</label>
                            <input v-model="form.phone" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Email</label>
                            <input v-model="form.email" type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">MST</label>
                            <input v-model="form.tax_code" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Người liên hệ</label>
                            <input v-model="form.contact_person" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Tên ngân hàng</label>
                            <input v-model="form.bank_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Số tài khoản</label>
                            <input v-model="form.bank_account" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div class="col-span-2">
                            <label class="text-sm font-medium text-gray-600 block mb-1">Địa chỉ</label>
                            <input v-model="form.address" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
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
