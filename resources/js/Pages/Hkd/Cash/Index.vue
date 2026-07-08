<template>
    <AppLayout title="Tiền mặt/Ngân hàng HKD (S2e)">
        <div class="max-w-5xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Sổ quỹ/Ngân hàng — {{ profile.full_name }}</h1>
                <div class="flex gap-2">
                    <button v-if="can('hkd.manage')" @click="openAddAccount" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">+ Tài khoản</button>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                <input v-model="curPeriod" type="month" @change="navigate" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                <span v-if="isLocked" class="text-xs font-medium bg-gray-100 text-gray-500 px-2 py-1 rounded">🔒 Kỳ đã chốt</span>
            </div>

            <div v-for="acct in accounts" :key="acct.id" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-gray-800 text-sm">{{ acct.name }}</span>
                        <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">{{ acct.type_label }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-600">Số dư: <strong class="text-gray-900">{{ fmtVnd(acct.balance) }}</strong></span>
                        <button v-if="!isLocked && can('hkd.manage')" @click="openAddTxn(acct)" class="text-xs text-primary-600 hover:underline">+ Phiếu</button>
                    </div>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Ngày</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Chứng từ</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Diễn giải</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500 w-20">Loại</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 w-28">Thu</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 w-28">Chi</th>
                            <th class="px-4 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="acct.transactions.length === 0"><td colspan="7" class="px-4 py-4 text-center text-gray-400 text-xs">Chưa có phát sinh kỳ này</td></tr>
                        <tr v-for="t in acct.transactions" :key="t.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-600 text-xs whitespace-nowrap">{{ t.date }}</td>
                            <td class="px-4 py-2 text-gray-500 text-xs">{{ t.document_no ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-700 text-xs">{{ t.description }}</td>
                            <td class="px-4 py-2 text-center">
                                <span :class="t.trans_type === 'receipt' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="text-xs px-2 py-0.5 rounded">
                                    {{ t.trans_type === 'receipt' ? 'Thu' : 'Chi' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right font-mono text-xs text-green-700">{{ t.trans_type === 'receipt' ? fmtVnd(t.amount) : '' }}</td>
                            <td class="px-4 py-2 text-right font-mono text-xs text-red-600">{{ t.trans_type === 'payment' ? fmtVnd(t.amount) : '' }}</td>
                            <td class="px-4 py-2 text-right">
                                <button v-if="!isLocked && can('hkd.manage')" @click="deleteTxn(t.id)" class="text-xs text-red-500 hover:underline">Xoá</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="accounts.length === 0" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400 text-sm">Chưa có tài khoản tiền mặt/ngân hàng. Thêm tài khoản để bắt đầu theo dõi.</div>
        </div>

        <Teleport to="body">
            <!-- Account form -->
            <div v-if="showAccountForm" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Thêm tài khoản quỹ</h3>
                        <button @click="showAccountForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="submitAccount" class="p-5 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2"><label class="label">Tên tài khoản <span class="text-red-500">*</span></label><input v-model="acctForm.name" required class="input-field" placeholder="Tiền mặt VNĐ, TK Vietcombank..." /></div>
                            <div><label class="label">Loại <span class="text-red-500">*</span></label>
                                <select v-model="acctForm.type" required class="input-field">
                                    <option value="cash">Tiền mặt</option>
                                    <option value="bank">Ngân hàng</option>
                                    <option value="e_wallet">Ví điện tử</option>
                                </select>
                            </div>
                            <div><label class="label">Số dư đầu kỳ (₫)</label><input v-model.number="acctForm.opening_balance" type="number" min="0" class="input-field" /></div>
                        </div>
                        <div class="flex justify-end gap-3 pt-1">
                            <button type="button" @click="showAccountForm = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="acctForm.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Transaction form -->
            <div v-if="showTxnForm" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Phiếu thu/chi — {{ selectedAccount?.name }}</h3>
                        <button @click="showTxnForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="submitTxn" class="p-5 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="label">Ngày <span class="text-red-500">*</span></label><input v-model="txnForm.transaction_date" type="date" required class="input-field" /></div>
                            <div><label class="label">Loại <span class="text-red-500">*</span></label>
                                <select v-model="txnForm.trans_type" required class="input-field">
                                    <option value="receipt">Thu</option>
                                    <option value="payment">Chi</option>
                                </select>
                            </div>
                            <div><label class="label">Số chứng từ</label><input v-model="txnForm.document_no" class="input-field" /></div>
                            <div><label class="label">Số tiền (₫) <span class="text-red-500">*</span></label><input v-model.number="txnForm.amount" type="number" min="1" required class="input-field" /></div>
                            <div class="col-span-2"><label class="label">Diễn giải <span class="text-red-500">*</span></label><input v-model="txnForm.description" required class="input-field" /></div>
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
const props = defineProps({ profile: Object, accounts: Array, period: String, isLocked: Boolean });

const curPeriod       = ref(props.period);
const showAccountForm = ref(false);
const showTxnForm     = ref(false);
const selectedAccount = ref(null);

const acctForm = useForm({ name: '', type: 'cash', opening_balance: 0 });
const txnForm  = useForm({ cash_account_id: null, transaction_date: '', trans_type: 'receipt', document_no: '', amount: 0, description: '', period: props.period });

function navigate() { router.get(route('hkd.cash.index'), { period: curPeriod.value }, { preserveState: true }); }
function openAddAccount() { acctForm.reset(); showAccountForm.value = true; }
function openAddTxn(acct) { txnForm.reset(); txnForm.cash_account_id = acct.id; txnForm.period = curPeriod.value; selectedAccount.value = acct; showTxnForm.value = true; }
function submitAccount() { acctForm.post(route('hkd.cash.store-account'), { onSuccess: () => { showAccountForm.value = false; } }); }
function submitTxn()     { txnForm.post(route('hkd.cash.store-transaction'), { onSuccess: () => { showTxnForm.value = false; } }); }
function deleteTxn(id)   { if (confirm('Xoá phiếu này?')) router.delete(route('hkd.cash.destroy-transaction', id)); }
function fmtVnd(v)       { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
</script>

<style scoped>
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
.label { @apply block text-sm font-medium text-gray-700 mb-1; }
</style>
