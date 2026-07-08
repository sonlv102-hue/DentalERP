<template>
    <AppLayout title="Nguồn quỹ">
        <div class="max-w-4xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Nguồn quỹ</h1>
                <button @click="openCreate" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                    + Thêm nguồn quỹ
                </button>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div v-for="a in accounts" :key="a.id" :class="['p-4 rounded-xl border', a.is_active ? 'bg-white border-gray-200' : 'bg-gray-50 border-gray-100 opacity-60']">
                    <div class="flex items-center justify-between mb-2">
                        <span :class="typeClass(a.type)" class="text-xs font-medium px-2 py-0.5 rounded">{{ a.type_label }}</span>
                        <div class="flex gap-1">
                            <button @click="openEdit(a)" class="text-xs text-gray-400 hover:text-gray-600">Sửa</button>
                            <button v-if="a.is_active" @click="deactivate(a)" class="text-xs text-red-300 hover:text-red-500 ml-1">Tắt</button>
                        </div>
                    </div>
                    <p class="font-semibold text-gray-800">{{ a.name }}</p>
                    <p v-if="a.bank_name" class="text-xs text-gray-400 mt-0.5">{{ a.bank_name }} · {{ a.account_number }}</p>
                    <p class="text-xl font-bold mt-2" :class="a.current_balance >= 0 ? 'text-green-700' : 'text-red-600'">
                        {{ formatVnd(a.current_balance) }}
                    </p>
                    <p class="text-xs text-gray-400">Số dư hiện tại</p>
                </div>
                <div v-if="accounts.length === 0" class="col-span-3 text-center text-gray-400 py-8 text-sm">
                    Chưa có nguồn quỹ nào
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="modal.show" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">{{ modal.id ? 'Sửa nguồn quỹ' : 'Thêm nguồn quỹ' }}</h3>
                        <button @click="modal.show = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="save" class="p-5 space-y-4">
                        <div>
                            <label class="label">Tên <span class="text-red-500">*</span></label>
                            <input v-model="modal.name" required class="input-field" />
                        </div>
                        <div>
                            <label class="label">Loại <span class="text-red-500">*</span></label>
                            <select v-model="modal.type" required class="input-field">
                                <option value="cash">Tiền mặt</option>
                                <option value="bank">Ngân hàng</option>
                                <option value="ewallet">Ví điện tử</option>
                            </select>
                        </div>
                        <div v-if="modal.type === 'bank'">
                            <label class="label">Ngân hàng</label>
                            <input v-model="modal.bank_name" class="input-field" placeholder="VD: Vietcombank" />
                        </div>
                        <div v-if="modal.type === 'bank'">
                            <label class="label">Số tài khoản</label>
                            <input v-model="modal.account_number" class="input-field" />
                        </div>
                        <div>
                            <label class="label">Số dư ban đầu (₫)</label>
                            <input v-model.number="modal.initial_balance" type="number" min="0" class="input-field" />
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="modal.show = false" class="px-4 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="saving" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                                {{ modal.id ? 'Cập nhật' : 'Tạo' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props  = defineProps({ accounts: Array });
const saving = ref(false);
const modal  = reactive({ show: false, id: null, name: '', type: 'cash', initial_balance: 0, bank_name: '', account_number: '' });

function openCreate() { Object.assign(modal, { show: true, id: null, name: '', type: 'cash', initial_balance: 0, bank_name: '', account_number: '' }); }
function openEdit(a)  { Object.assign(modal, { show: true, id: a.id, name: a.name, type: a.type, initial_balance: a.initial_balance, bank_name: a.bank_name ?? '', account_number: a.account_number ?? '' }); }
function deactivate(a) {
    if (confirm(`Tắt nguồn quỹ "${a.name}"?`)) router.delete(route('core.fund-accounts.destroy', a.id));
}

function save() {
    saving.value = true;
    const url    = modal.id ? route('core.fund-accounts.update', modal.id) : route('core.fund-accounts.store');
    const method = modal.id ? 'put' : 'post';
    const payload = { name: modal.name, type: modal.type, initial_balance: modal.initial_balance, bank_name: modal.bank_name, account_number: modal.account_number };
    router[method](url, payload, { onSuccess: () => { modal.show = false; }, onFinish: () => { saving.value = false; } });
}

function formatVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
function typeClass(type) {
    return { cash: 'bg-green-100 text-green-700', bank: 'bg-blue-100 text-blue-700', ewallet: 'bg-purple-100 text-purple-700' }[type] ?? 'bg-gray-100 text-gray-600';
}
</script>

<style scoped>
.label       { @apply block text-sm font-medium text-gray-700 mb-1; }
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
