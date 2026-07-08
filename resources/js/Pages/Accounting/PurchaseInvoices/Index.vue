<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

const props = defineProps({ invoices: Object, suppliers: Array, statuses: Array, filters: Object });

const supplierId = ref(props.filters.supplier_id ?? '');
const status     = ref(props.filters.status ?? '');
const from       = ref(props.filters.from ?? '');
const to         = ref(props.filters.to ?? '');

function applyFilters() {
    router.get(route('accounting.purchase-invoices.index'), {
        supplier_id: supplierId.value || undefined,
        status: status.value || undefined,
        from: from.value || undefined,
        to: to.value || undefined,
    }, { preserveState: true });
}

function formatVnd(v) {
    return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫';
}
</script>

<template>
    <AppLayout title="Hóa đơn mua hàng">
        <div class="max-w-6xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Hóa đơn mua hàng</h1>
                <Link v-if="can('accounting.manage')" :href="route('accounting.purchase-invoices.create')"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700">
                    + Tạo hóa đơn
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap gap-3">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Nhà cung cấp</label>
                    <select v-model="supplierId" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option value="">Tất cả</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Trạng thái</label>
                    <select v-model="status" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option value="">Tất cả</option>
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Từ ngày</label>
                    <input v-model="from" type="date" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Đến ngày</label>
                    <input v-model="to" type="date" @change="applyFilters" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Mã HĐ</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Nhà cung cấp</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Ngày</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Tổng tiền</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">VAT</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Còn nợ</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs">{{ inv.code }}</td>
                            <td class="px-4 py-3 font-medium">{{ inv.supplier }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ inv.invoice_date }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ formatVnd(inv.total) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-orange-600">{{ formatVnd(inv.vat_amount) }}</td>
                            <td class="px-4 py-3 text-right font-mono" :class="inv.amount_due > 0 ? 'text-red-600' : 'text-gray-400'">
                                {{ formatVnd(inv.amount_due) }}
                            </td>
                            <td class="px-4 py-3"><StatusBadge :color="inv.status_color">{{ inv.status_label }}</StatusBadge></td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('accounting.purchase-invoices.show', inv.id)"
                                    class="text-xs text-primary-600 hover:underline">Chi tiết</Link>
                            </td>
                        </tr>
                        <tr v-if="invoices.data.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400">Không có hóa đơn nào</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="invoices.links" />
        </div>
    </AppLayout>
</template>
