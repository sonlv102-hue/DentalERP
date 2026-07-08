<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({ input: Object, output: Object, net_vat_payable: Number, period: Object, branches: Array, filters: Object });

const from     = ref(props.filters.from);
const to       = ref(props.filters.to);
const branchId = ref(props.filters.branchId ?? '');

function applyFilters() {
    router.get(route('reports.vat'), { from: from.value, to: to.value, branch_id: branchId.value || undefined }, { preserveState: true });
}

function formatVnd(v) {
    return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫';
}
</script>

<template>
    <AppLayout title="Báo cáo thuế VAT">
        <div class="max-w-5xl space-y-4">
            <h1 class="text-xl font-semibold text-gray-800">Báo cáo thuế VAT</h1>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap gap-3">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Từ ngày</label>
                    <input v-model="from" type="date" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Đến ngày</label>
                    <input v-model="to" type="date" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Chi nhánh</label>
                    <select v-model="branchId" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option value="">Tất cả</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 mb-1">VAT đầu vào (Mua hàng)</p>
                    <p class="text-2xl font-bold text-orange-600">{{ formatVnd(input.vat_amount) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ input.items.length }} hóa đơn</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 mb-1">Doanh thu dịch vụ</p>
                    <p class="text-2xl font-bold text-green-600">{{ formatVnd(output.revenue) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Dịch vụ nha khoa thường miễn VAT</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 border-l-4 border-l-primary-500">
                    <p class="text-xs text-gray-500 mb-1">VAT cần nộp</p>
                    <p class="text-2xl font-bold text-primary-700">{{ formatVnd(net_vat_payable) }}</p>
                    <p class="text-xs text-gray-400 mt-1">= VAT đầu vào (chỉ tracking đầu vào)</p>
                </div>
            </div>

            <!-- Input VAT detail -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h3 class="font-medium text-gray-700">Chi tiết VAT đầu vào</h3>
                    <p class="text-xs text-gray-500">Từ {{ period.from }} đến {{ period.to }}</p>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Ngày</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Mã HĐ</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Nhà cung cấp</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">MST</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Trước VAT</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">VAT</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Tổng</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in input.items" :key="item.code" class="hover:bg-gray-50">
                            <td class="px-4 py-3">{{ item.date }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ item.code }}</td>
                            <td class="px-4 py-3">{{ item.supplier }}</td>
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ item.supplier_tax ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ formatVnd(item.subtotal) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-orange-600">{{ formatVnd(item.vat_amount) }}</td>
                            <td class="px-4 py-3 text-right font-mono font-medium">{{ formatVnd(item.total) }}</td>
                        </tr>
                        <tr v-if="input.items.length === 0">
                            <td colspan="7" class="px-4 py-6 text-center text-gray-400">Không có hóa đơn có VAT</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right font-semibold text-sm">Tổng cộng:</td>
                            <td class="px-4 py-3 text-right font-mono font-bold">{{ formatVnd(input.subtotal) }}</td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-orange-600">{{ formatVnd(input.vat_amount) }}</td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-primary-700">{{ formatVnd(input.total) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
