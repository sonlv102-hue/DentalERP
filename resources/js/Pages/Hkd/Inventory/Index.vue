<template>
    <AppLayout title="Hàng hóa HKD (S2d)">
        <div class="max-w-5xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Hàng hóa — {{ profile.full_name }}</h1>
                <div class="flex gap-2">
                    <button v-if="!isLocked && can('hkd.manage')" @click="openAddItem" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">+ Mặt hàng</button>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                <input v-model="curPeriod" type="month" @change="navigate" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                <span v-if="isLocked" class="text-xs font-medium bg-gray-100 text-gray-500 px-2 py-1 rounded">🔒 Kỳ đã chốt</span>
            </div>

            <!-- Per-item sections -->
            <div v-for="item in items" :key="item.id" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                    <div>
                        <span class="font-semibold text-gray-800 text-sm">{{ item.name }}</span>
                        <span class="ml-2 text-xs text-gray-400">{{ item.unit }}</span>
                    </div>
                    <button v-if="!isLocked && can('hkd.manage')" @click="openAddTxn(item)" class="text-xs text-primary-600 hover:underline">+ Phiếu</button>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 w-24">Ngày</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Diễn giải</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500 w-20">Loại</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 w-20">SL</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 w-28">Đơn giá BQ</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 w-28">Thành tiền</th>
                            <th class="px-4 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="item.transactions.length === 0"><td colspan="7" class="px-4 py-4 text-center text-gray-400 text-xs">Chưa có phát sinh kỳ này</td></tr>
                        <tr v-for="t in item.transactions" :key="t.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-600 text-xs">{{ t.date }}</td>
                            <td class="px-4 py-2 text-gray-700 text-xs">{{ t.description }}</td>
                            <td class="px-4 py-2 text-center">
                                <span :class="t.trans_type === 'import' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'" class="text-xs px-2 py-0.5 rounded">
                                    {{ t.trans_type === 'import' ? 'Nhập' : 'Xuất' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right font-mono text-xs">{{ t.quantity }}</td>
                            <td class="px-4 py-2 text-right font-mono text-xs">{{ fmtVnd(t.avg_cost) }}</td>
                            <td class="px-4 py-2 text-right font-mono text-xs">{{ fmtVnd(t.amount) }}</td>
                            <td class="px-4 py-2 text-right">
                                <button v-if="!isLocked && can('hkd.manage')" @click="deleteTxn(t.id)" class="text-xs text-red-500 hover:underline">Xoá</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="items.length === 0" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400 text-sm">Chưa có mặt hàng nào. Thêm mặt hàng để theo dõi hàng tồn kho.</div>
        </div>

        <Teleport to="body">
            <!-- Item form -->
            <div v-if="showItemForm" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Thêm mặt hàng</h3>
                        <button @click="showItemForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="submitItem" class="p-5 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2"><label class="label">Tên mặt hàng <span class="text-red-500">*</span></label><input v-model="itemForm.name" required class="input-field" /></div>
                            <div><label class="label">Đơn vị tính</label><input v-model="itemForm.unit" class="input-field" placeholder="cái, hộp, kg..." /></div>
                            <div><label class="label">SL tồn đầu kỳ</label><input v-model.number="itemForm.opening_qty" type="number" min="0" step="0.001" class="input-field" /></div>
                            <div class="col-span-2"><label class="label">Giá vốn đầu kỳ (₫/đvt)</label><input v-model.number="itemForm.opening_cost" type="number" min="0" class="input-field" /></div>
                        </div>
                        <div class="flex justify-end gap-3 pt-1">
                            <button type="button" @click="showItemForm = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="itemForm.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Transaction form -->
            <div v-if="showTxnForm" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Phiếu nhập/xuất — {{ selectedItem?.name }}</h3>
                        <button @click="showTxnForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="submitTxn" class="p-5 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="label">Ngày <span class="text-red-500">*</span></label><input v-model="txnForm.transaction_date" type="date" required class="input-field" /></div>
                            <div><label class="label">Loại <span class="text-red-500">*</span></label>
                                <select v-model="txnForm.trans_type" required class="input-field">
                                    <option value="import">Nhập</option>
                                    <option value="export">Xuất</option>
                                </select>
                            </div>
                            <div><label class="label">Số lượng <span class="text-red-500">*</span></label><input v-model.number="txnForm.quantity" type="number" min="0.001" step="0.001" required class="input-field" /></div>
                            <div><label class="label">Đơn giá (₫)</label><input v-model.number="txnForm.unit_cost" type="number" min="0" class="input-field" /></div>
                            <div class="col-span-2"><label class="label">Diễn giải</label><input v-model="txnForm.description" class="input-field" /></div>
                        </div>
                        <div class="flex justify-end gap-3 pt-1">
                            <button type="button" @click="showTxnForm = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="txnForm.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ profile: Object, items: Array, period: String, isLocked: Boolean });

const curPeriod    = ref(props.period);
const showItemForm = ref(false);
const showTxnForm  = ref(false);
const selectedItem = ref(null);

const itemForm = useForm({ name: '', unit: '', opening_qty: 0, opening_cost: 0, period: props.period });
const txnForm  = useForm({ inventory_item_id: null, transaction_date: '', trans_type: 'import', quantity: 1, unit_cost: 0, description: '', period: props.period });

function navigate() { router.get(route('hkd.inventory.index'), { period: curPeriod.value }, { preserveState: true }); }
function openAddItem() { itemForm.reset(); itemForm.period = curPeriod.value; showItemForm.value = true; }
function openAddTxn(item) { txnForm.reset(); txnForm.inventory_item_id = item.id; txnForm.period = curPeriod.value; selectedItem.value = item; showTxnForm.value = true; }
function submitItem() { itemForm.post(route('hkd.inventory.store-item'), { onSuccess: () => { showItemForm.value = false; } }); }
function submitTxn()  { txnForm.post(route('hkd.inventory.store-transaction'), { onSuccess: () => { showTxnForm.value = false; } }); }
function deleteTxn(id) { if (confirm('Xoá phiếu này?')) router.delete(route('hkd.inventory.destroy-transaction', id)); }
function fmtVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
</script>

<style scoped>
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
.label { @apply block text-sm font-medium text-gray-700 mb-1; }
</style>
