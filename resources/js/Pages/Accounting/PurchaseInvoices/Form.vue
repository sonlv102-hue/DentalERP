<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({ suppliers: Array, branches: Array, fundAccounts: Array });

const form = useForm({
    supplier_id: '',
    branch_id: '',
    fund_account_id: '',
    invoice_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    notes: '',
    items: [{ description: '', quantity: 1, unit_price: 0, vat_rate: 10, amount: 0 }],
});

function addItem() {
    form.items.push({ description: '', quantity: 1, unit_price: 0, vat_rate: 10, amount: 0 });
}

function removeItem(idx) {
    if (form.items.length <= 1) return;
    form.items.splice(idx, 1);
}

function calcAmount(item) {
    item.amount = Math.round(item.quantity * item.unit_price);
}

const subtotal  = () => form.items.reduce((s, i) => s + (i.amount || 0), 0);
const vatTotal  = () => form.items.reduce((s, i) => s + Math.round((i.amount || 0) * (i.vat_rate || 0) / 100), 0);
const grandTotal = () => subtotal() + vatTotal();

function formatVnd(v) {
    return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫';
}

function submit() {
    form.post(route('accounting.purchase-invoices.store'));
}
</script>

<template>
    <AppLayout title="Tạo hóa đơn mua hàng">
        <div class="max-w-4xl space-y-4">
            <div class="flex items-center gap-3">
                <Link :href="route('accounting.purchase-invoices.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Danh sách</Link>
                <h1 class="text-xl font-semibold text-gray-800">Tạo hóa đơn mua hàng</h1>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <!-- Header fields -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600 block mb-1">Nhà cung cấp *</label>
                        <select v-model="form.supplier_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">-- Chọn --</option>
                            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <p v-if="form.errors.supplier_id" class="text-red-500 text-xs mt-1">{{ form.errors.supplier_id }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 block mb-1">Ngày hóa đơn *</label>
                        <input v-model="form.invoice_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 block mb-1">Hạn thanh toán</label>
                        <input v-model="form.due_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 block mb-1">Chi nhánh</label>
                        <select v-model="form.branch_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">-- Chọn --</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 block mb-1">Nguồn quỹ</label>
                        <select v-model="form.fund_account_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">-- Chọn --</option>
                            <option v-for="a in fundAccounts" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 block mb-1">Ghi chú</label>
                        <input v-model="form.notes" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                </div>

                <!-- Items -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-medium text-gray-700">Chi tiết hàng hóa</h3>
                        <button @click="addItem" class="text-sm text-primary-600 hover:underline">+ Thêm dòng</button>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border border-gray-200 rounded-lg">
                            <tr>
                                <th class="px-3 py-2 text-left text-gray-600 font-medium">Mô tả</th>
                                <th class="px-3 py-2 text-right text-gray-600 font-medium w-20">SL</th>
                                <th class="px-3 py-2 text-right text-gray-600 font-medium w-32">Đơn giá</th>
                                <th class="px-3 py-2 text-right text-gray-600 font-medium w-20">VAT%</th>
                                <th class="px-3 py-2 text-right text-gray-600 font-medium w-32">Thành tiền</th>
                                <th class="w-8"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, idx) in form.items" :key="idx" class="border-b border-gray-100">
                                <td class="px-1 py-1">
                                    <input v-model="item.description" type="text" placeholder="Tên hàng..."
                                        class="w-full border border-gray-200 rounded px-2 py-1 text-sm" />
                                </td>
                                <td class="px-1 py-1">
                                    <input v-model.number="item.quantity" type="number" min="0.001" step="0.001"
                                        @input="calcAmount(item)"
                                        class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right" />
                                </td>
                                <td class="px-1 py-1">
                                    <input v-model.number="item.unit_price" type="number" min="0" step="1000"
                                        @input="calcAmount(item)"
                                        class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right" />
                                </td>
                                <td class="px-1 py-1">
                                    <select v-model.number="item.vat_rate" class="w-full border border-gray-200 rounded px-2 py-1 text-sm">
                                        <option :value="0">0%</option>
                                        <option :value="5">5%</option>
                                        <option :value="10">10%</option>
                                    </select>
                                </td>
                                <td class="px-1 py-1 text-right font-mono">{{ formatVnd(item.amount) }}</td>
                                <td class="px-1 py-1 text-center">
                                    <button @click="removeItem(idx)" :disabled="form.items.length <= 1"
                                        class="text-red-400 hover:text-red-600 disabled:opacity-30">×</button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="4" class="px-3 py-2 text-right text-sm text-gray-600">Tạm tính:</td>
                                <td class="px-3 py-2 text-right font-mono font-medium">{{ formatVnd(subtotal()) }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-3 py-2 text-right text-sm text-orange-600">VAT:</td>
                                <td class="px-3 py-2 text-right font-mono text-orange-600">{{ formatVnd(vatTotal()) }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-3 py-2 text-right text-sm font-semibold">Tổng cộng:</td>
                                <td class="px-3 py-2 text-right font-mono font-bold text-primary-700 text-base">{{ formatVnd(grandTotal()) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <Link :href="route('accounting.purchase-invoices.index')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Hủy</Link>
                    <button @click="submit" :disabled="form.processing"
                        class="px-6 py-2 bg-primary-600 text-white rounded-lg text-sm disabled:opacity-50">Tạo hóa đơn</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
