<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

const props = defineProps({ invoice: Object, fundAccounts: Array });

const payModal = ref({ open: false });
const payForm = useForm({ amount: props.invoice.amount_due, method: 'bank_transfer' });

function submitPayment() {
    payForm.post(route('accounting.purchase-invoices.payment', props.invoice.id), {
        onSuccess: () => { payModal.value.open = false; },
    });
}

function receive() {
    if (!confirm('Xác nhận đã nhận hàng?')) return;
    router.post(route('accounting.purchase-invoices.receive', props.invoice.id));
}

function cancel() {
    if (!confirm('Hủy hóa đơn này?')) return;
    router.post(route('accounting.purchase-invoices.cancel', props.invoice.id));
}

function formatVnd(v) {
    return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫';
}
</script>

<template>
    <AppLayout :title="`HĐ Mua: ${invoice.code}`">
        <div class="max-w-4xl space-y-4">
            <!-- Header -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <Link :href="route('accounting.purchase-invoices.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Danh sách</Link>
                        <span class="font-mono text-sm bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ invoice.code }}</span>
                        <StatusBadge :color="invoice.status_color">{{ invoice.status_label }}</StatusBadge>
                    </div>
                    <div class="flex gap-2">
                        <button v-if="can('accounting.manage') && invoice.status === 'draft'" @click="receive"
                            class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Xác nhận nhận hàng</button>
                        <button v-if="can('accounting.manage') && invoice.amount_due > 0 && invoice.status !== 'cancelled'" @click="payModal.open = true"
                            class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">Ghi thanh toán</button>
                        <button v-if="can('accounting.manage') && invoice.status !== 'paid' && invoice.status !== 'cancelled'" @click="cancel"
                            class="px-3 py-1.5 border border-red-300 text-red-600 text-sm rounded-lg hover:bg-red-50">Hủy</button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs text-gray-500">Nhà cung cấp</p>
                        <p class="font-semibold">{{ invoice.supplier }}</p>
                        <p class="text-xs text-gray-400">MST: {{ invoice.supplier_tax ?? '—' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Ngày hóa đơn</p>
                        <p class="font-semibold">{{ invoice.invoice_date }}</p>
                        <p class="text-xs text-gray-400">Hạn TT: {{ invoice.due_date ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Items table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h3 class="font-medium text-gray-700">Chi tiết hàng hóa</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Mô tả</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">SL</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Đơn giá</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">VAT%</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in invoice.items" :key="item.id">
                            <td class="px-4 py-3">{{ item.description }}</td>
                            <td class="px-4 py-3 text-right">{{ item.quantity }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ formatVnd(item.unit_price) }}</td>
                            <td class="px-4 py-3 text-right">{{ item.vat_rate }}%</td>
                            <td class="px-4 py-3 text-right font-mono">{{ formatVnd(item.amount) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right text-sm text-gray-600">Tạm tính:</td>
                            <td class="px-4 py-2 text-right font-mono">{{ formatVnd(invoice.subtotal) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right text-sm text-orange-600">VAT:</td>
                            <td class="px-4 py-2 text-right font-mono text-orange-600">{{ formatVnd(invoice.vat_amount) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right font-semibold">Tổng cộng:</td>
                            <td class="px-4 py-2 text-right font-mono font-bold text-primary-700 text-base">{{ formatVnd(invoice.total) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right text-sm text-green-600">Đã thanh toán:</td>
                            <td class="px-4 py-2 text-right font-mono text-green-600">{{ formatVnd(invoice.paid_amount) }}</td>
                        </tr>
                        <tr v-if="invoice.amount_due > 0">
                            <td colspan="4" class="px-4 py-2 text-right font-semibold text-red-600">Còn nợ:</td>
                            <td class="px-4 py-2 text-right font-mono font-bold text-red-600 text-base">{{ formatVnd(invoice.amount_due) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Payment modal -->
        <Teleport to="body">
            <div v-if="payModal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl p-6 w-full max-w-sm shadow-xl">
                    <h3 class="font-semibold text-gray-800 mb-4">Ghi nhận thanh toán</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Số tiền (₫) *</label>
                            <input v-model.number="payForm.amount" type="number" min="1"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Phương thức</label>
                            <select v-model="payForm.method" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="cash">Tiền mặt</option>
                                <option value="bank_transfer">Chuyển khoản</option>
                                <option value="card">Thẻ</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-4">
                        <button @click="payModal.open = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Hủy</button>
                        <button @click="submitPayment" :disabled="payForm.processing"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm disabled:opacity-50">Xác nhận</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
