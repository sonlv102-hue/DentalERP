<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
  revenue: Object,
  payroll: Object,
  branches: Array,
  filters: Object,
});

const period   = ref(props.filters.period ?? '');
const branchId = ref(props.filters.branch_id ?? '');

function applyFilters() {
  router.get(route('reports.reconciliation'), { period: period.value, branch_id: branchId.value }, { preserveState: true });
}

function formatVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }

function gapClass(gap) {
  if (gap === 0) return 'text-green-600 font-semibold';
  return gap > 0 ? 'text-blue-600 font-semibold' : 'text-red-600 font-semibold';
}
</script>

<template>
  <AppLayout title="Báo cáo đối chiếu">
    <div class="max-w-5xl mx-auto px-4 py-6 space-y-6">
      <h1 class="text-xl font-semibold text-gray-900">Báo cáo đối chiếu liên module</h1>

      <!-- Filters -->
      <div class="bg-white rounded-xl border p-4 flex gap-3">
        <input v-model="period" type="month" @change="applyFilters"
               class="border rounded-lg px-3 py-2 text-sm" />
        <select v-model="branchId" @change="applyFilters" class="border rounded-lg px-3 py-2 text-sm">
          <option value="">Tất cả chi nhánh</option>
          <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
      </div>

      <!-- Revenue reconciliation -->
      <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-5 py-4 border-b bg-blue-50">
          <h2 class="font-semibold text-blue-800">1. Đối chiếu doanh thu tháng {{ revenue.period }}</h2>
          <p class="text-xs text-blue-600 mt-0.5">Hóa đơn phát sinh → Tiền đã thu → Sổ doanh thu TT152</p>
        </div>
        <div class="p-5 grid grid-cols-2 gap-4 md:grid-cols-4">
          <div class="bg-gray-50 rounded-lg p-3">
            <div class="text-xs text-gray-500">Hóa đơn phát sinh</div>
            <div class="text-lg font-bold text-gray-800 mt-1">{{ formatVnd(revenue.invoice_total) }}</div>
          </div>
          <div class="bg-green-50 rounded-lg p-3">
            <div class="text-xs text-gray-500">Đã thu (PatientPayment)</div>
            <div class="text-lg font-bold text-green-700 mt-1">{{ formatVnd(revenue.collected) }}</div>
          </div>
          <div class="bg-yellow-50 rounded-lg p-3">
            <div class="text-xs text-gray-500">Còn nợ</div>
            <div class="text-lg font-bold text-yellow-700 mt-1">{{ formatVnd(revenue.outstanding) }}</div>
          </div>
          <div class="bg-purple-50 rounded-lg p-3">
            <div class="text-xs text-gray-500">Sổ TT152 (HKD)</div>
            <div class="text-lg font-bold text-purple-700 mt-1">{{ formatVnd(revenue.hkd_revenue) }}</div>
          </div>
        </div>
        <div class="px-5 pb-4">
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600">Chênh lệch (Thu - HKD):</span>
            <span :class="gapClass(revenue.gap)">{{ formatVnd(Math.abs(revenue.gap)) }}
              {{ revenue.gap > 0 ? '(thu > HKD)' : revenue.gap < 0 ? '(thu < HKD)' : '✓ Khớp' }}
            </span>
          </div>
          <p v-if="revenue.gap !== 0" class="text-xs text-gray-400 mt-1">
            Chênh lệch có thể do: thanh toán chưa ghi sổ, hoặc ghi sổ thủ công thừa/thiếu.
          </p>
        </div>
      </div>

      <!-- Payroll reconciliation -->
      <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-5 py-4 border-b bg-orange-50">
          <h2 class="font-semibold text-orange-800">2. Đối chiếu lương tháng {{ payroll.period }}</h2>
          <p class="text-xs text-orange-600 mt-0.5">Bảng lương (Payroll) → Sổ chi phí TT152 (source_type=payroll)</p>
        </div>
        <div class="p-5 grid grid-cols-3 gap-4">
          <div class="bg-gray-50 rounded-lg p-3">
            <div class="text-xs text-gray-500">Bảng lương (net)</div>
            <div class="text-lg font-bold text-gray-800 mt-1">{{ formatVnd(payroll.payroll_total) }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ payroll.payrolls.length }} bảng lương</div>
          </div>
          <div class="bg-orange-50 rounded-lg p-3">
            <div class="text-xs text-gray-500">Sổ chi phí TT152</div>
            <div class="text-lg font-bold text-orange-700 mt-1">{{ formatVnd(payroll.hkd_salary) }}</div>
          </div>
          <div class="bg-blue-50 rounded-lg p-3">
            <div class="text-xs text-gray-500">Phiếu chi lương (Expense)</div>
            <div class="text-lg font-bold text-blue-700 mt-1">{{ formatVnd(payroll.expense_salary) }}</div>
          </div>
        </div>
        <div class="px-5 pb-4">
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600">Chênh lệch (Payroll - HKD):</span>
            <span :class="gapClass(payroll.gap)">{{ formatVnd(Math.abs(payroll.gap)) }}
              {{ payroll.gap === 0 ? '✓ Khớp' : payroll.gap > 0 ? '(payroll > HKD — thiếu ghi sổ)' : '(HKD > payroll — ghi sổ thừa)' }}
            </span>
          </div>
        </div>

        <!-- Payroll breakdown -->
        <div v-if="payroll.payrolls.length" class="border-t">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
              <tr>
                <th class="px-4 py-2 text-left">Mã bảng lương</th>
                <th class="px-4 py-2 text-right">Net salary</th>
                <th class="px-4 py-2 text-left">Trạng thái</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="p in payroll.payrolls" :key="p.code">
                <td class="px-4 py-2 text-gray-700">{{ p.code }}</td>
                <td class="px-4 py-2 text-right">{{ formatVnd(p.net_salary) }}</td>
                <td class="px-4 py-2 text-gray-500 capitalize">{{ p.status }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="px-5 py-4 text-sm text-gray-400">Chưa có bảng lương nào trong tháng này.</div>
      </div>
    </div>
  </AppLayout>
</template>
