<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
  by_employee: Array,
  total_kpi: Number,
  total_paid: Number,
  total_pending: Number,
  period: String,
  branches: Array,
  filters: Object,
});

const period   = ref(props.filters.period ?? '');
const branchId = ref(props.filters.branch_id ?? '');

function applyFilters() {
  router.get(route('reports.kpi-summary'), { period: period.value, branch_id: branchId.value }, { preserveState: true });
}

function formatVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }

function paidPercent(row) {
  if (!row.total_kpi) return 0;
  return Math.round((row.paid / row.total_kpi) * 100);
}
</script>

<template>
  <AppLayout title="Tổng hợp KPI">
    <div class="max-w-5xl mx-auto px-4 py-6 space-y-5">
      <h1 class="text-xl font-semibold text-gray-900">Tổng hợp KPI theo nhân viên</h1>

      <!-- Filters -->
      <div class="bg-white rounded-xl border p-4 flex gap-3">
        <input v-model="period" type="month" @change="applyFilters"
               class="border rounded-lg px-3 py-2 text-sm" />
        <select v-model="branchId" @change="applyFilters" class="border rounded-lg px-3 py-2 text-sm">
          <option value="">Tất cả chi nhánh</option>
          <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
      </div>

      <!-- Summary cards -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border p-4">
          <div class="text-xs text-gray-500">Tổng KPI tháng {{ period }}</div>
          <div class="text-2xl font-bold text-gray-900 mt-1">{{ formatVnd(total_kpi) }}</div>
        </div>
        <div class="bg-green-50 rounded-xl border border-green-100 p-4">
          <div class="text-xs text-green-600">Đã thanh toán</div>
          <div class="text-2xl font-bold text-green-700 mt-1">{{ formatVnd(total_paid) }}</div>
        </div>
        <div class="bg-yellow-50 rounded-xl border border-yellow-100 p-4">
          <div class="text-xs text-yellow-600">Chờ duyệt / Tạm tính</div>
          <div class="text-2xl font-bold text-yellow-700 mt-1">{{ formatVnd(total_pending) }}</div>
        </div>
      </div>

      <!-- Employee breakdown table -->
      <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-4 py-3 border-b font-medium text-sm text-gray-700">
          Chi tiết theo nhân viên ({{ by_employee.length }} người)
        </div>
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
            <tr>
              <th class="px-4 py-3 text-left">Nhân viên</th>
              <th class="px-4 py-3 text-right">Tổng KPI</th>
              <th class="px-4 py-3 text-right">Đã duyệt</th>
              <th class="px-4 py-3 text-right">Chờ duyệt</th>
              <th class="px-4 py-3 text-right">Đã trả</th>
              <th class="px-4 py-3 text-right">Số phân bổ</th>
              <th class="px-4 py-3 text-left w-32">Tiến độ</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="row in by_employee" :key="row.employee_id" class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <div class="font-medium text-gray-900">{{ row.employee_name }}</div>
                <div class="text-xs text-gray-400">{{ row.employee_code }}</div>
              </td>
              <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ formatVnd(row.total_kpi) }}</td>
              <td class="px-4 py-3 text-right text-blue-600">{{ formatVnd(row.approved) }}</td>
              <td class="px-4 py-3 text-right text-yellow-600">{{ formatVnd(row.pending) }}</td>
              <td class="px-4 py-3 text-right text-green-600">{{ formatVnd(row.paid) }}</td>
              <td class="px-4 py-3 text-right text-gray-500">{{ row.count }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                    <div class="bg-green-500 h-1.5 rounded-full" :style="{ width: paidPercent(row) + '%' }" />
                  </div>
                  <span class="text-xs text-gray-400 w-8">{{ paidPercent(row) }}%</span>
                </div>
              </td>
            </tr>
            <tr v-if="!by_employee.length">
              <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                Không có dữ liệu KPI cho tháng {{ period }}
              </td>
            </tr>
          </tbody>
          <tfoot v-if="by_employee.length" class="border-t bg-gray-50 font-semibold text-sm">
            <tr>
              <td class="px-4 py-3 text-gray-700">Tổng cộng</td>
              <td class="px-4 py-3 text-right text-gray-900">{{ formatVnd(total_kpi) }}</td>
              <td colspan="4" />
              <td />
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
