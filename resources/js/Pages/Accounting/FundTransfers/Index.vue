<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

const props = defineProps({ transfers: Object, fundAccounts: Array });

const modal = ref({ open: false });
const form = useForm({ from_account_id: '', to_account_id: '', amount: 0, transfer_date: new Date().toISOString().slice(0, 10), description: '', reference: '' });

function submit() {
    form.post(route('accounting.fund-transfers.store'), { onSuccess: () => { modal.value.open = false; form.reset(); } });
}

function remove(id) {
    if (!confirm('Xóa phiếu chuyển quỹ này?')) return;
    router.delete(route('accounting.fund-transfers.destroy', id));
}

function formatVnd(v) {
    return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫';
}
</script>

<template>
    <AppLayout title="Chuyển quỹ">
        <div class="max-w-5xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Chuyển quỹ</h1>
                <button v-if="can('accounting.manage')" @click="modal.open = true"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700">
                    + Chuyển quỹ
                </button>
            </div>

            <!-- Fund account balances -->
            <div class="grid grid-cols-3 gap-3">
                <div v-for="acc in fundAccounts" :key="acc.id"
                    class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 mb-1">{{ acc.name }}</p>
                    <p class="text-lg font-bold text-primary-700">{{ formatVnd(acc.balance) }}</p>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Ngày</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Từ quỹ</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Đến quỹ</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Số tiền</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Mô tả</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Tham chiếu</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="t in transfers.data" :key="t.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">{{ t.transfer_date }}</td>
                            <td class="px-4 py-3 text-red-600">{{ t.from_account }}</td>
                            <td class="px-4 py-3 text-green-600">{{ t.to_account }}</td>
                            <td class="px-4 py-3 text-right font-mono font-semibold">{{ formatVnd(t.amount) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ t.description ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ t.reference ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="can('accounting.manage')" @click="remove(t.id)"
                                    class="text-xs text-red-400 hover:underline">Xóa</button>
                            </td>
                        </tr>
                        <tr v-if="transfers.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">Không có phiếu chuyển quỹ</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="transfers.links" />
        </div>

        <!-- Transfer modal -->
        <Teleport to="body">
            <div v-if="modal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
                    <h3 class="font-semibold text-gray-800 mb-4">Tạo phiếu chuyển quỹ</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Từ quỹ *</label>
                            <select v-model="form.from_account_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">-- Chọn --</option>
                                <option v-for="a in fundAccounts" :key="a.id" :value="a.id">{{ a.name }} ({{ formatVnd(a.balance) }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Đến quỹ *</label>
                            <select v-model="form.to_account_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">-- Chọn --</option>
                                <option v-for="a in fundAccounts.filter(x => x.id != form.from_account_id)" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-sm font-medium text-gray-600 block mb-1">Số tiền (₫) *</label>
                                <input v-model="form.amount" type="number" min="1" step="1000"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 block mb-1">Ngày *</label>
                                <input v-model="form.transfer_date" type="date"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Mô tả</label>
                            <input v-model="form.description" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-4">
                        <button @click="modal.open = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Hủy</button>
                        <button @click="submit" :disabled="form.processing"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm disabled:opacity-50">Chuyển</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
