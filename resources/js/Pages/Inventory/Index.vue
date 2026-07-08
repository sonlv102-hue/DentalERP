<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ items: Object, branches: Array, categories: Array, filters: Object });

const search   = ref(props.filters.search ?? '');
const branchId = ref(props.filters.branch_id ?? '');
const category = ref(props.filters.category ?? '');
const lowStock = ref(props.filters.low_stock ?? false);

function applyFilters() {
  router.get(route('inventory.index'), {
    search: search.value, branch_id: branchId.value,
    category: category.value, low_stock: lowStock.value || undefined,
  }, { preserveState: true });
}

const categoryMap = {
  material: 'Vật tư', medicine: 'Thuốc', equipment: 'Thiết bị',
  consumable: 'Tiêu hao', other: 'Khác',
};

function formatVnd(v) {
  return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫';
}
</script>

<template>
  <AppLayout title="Kho vật tư">
    <div class="max-w-7xl mx-auto px-4 py-6 space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-900">Kho vật tư</h1>
        <div class="flex gap-2">
          <a v-if="can('inventory.manage')" :href="route('inventory.templates')"
             class="px-3 py-2 text-sm bg-white border rounded-lg text-gray-700 hover:bg-gray-50">
            Định mức vật tư
          </a>
          <a v-if="can('inventory.manage')" :href="route('inventory.create')"
             class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700">
            + Thêm vật tư
          </a>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white p-4 rounded-xl shadow-sm border flex flex-wrap gap-3">
        <input v-model="search" @keyup.enter="applyFilters" type="text" placeholder="Tìm theo tên, mã..."
               class="border rounded-lg px-3 py-2 text-sm w-56" />
        <select v-model="branchId" @change="applyFilters" class="border rounded-lg px-3 py-2 text-sm">
          <option value="">Tất cả chi nhánh</option>
          <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
        <select v-model="category" @change="applyFilters" class="border rounded-lg px-3 py-2 text-sm">
          <option value="">Tất cả loại</option>
          <option v-for="c in categories" :key="c" :value="c">{{ categoryMap[c] ?? c }}</option>
        </select>
        <label class="flex items-center gap-2 text-sm text-gray-600">
          <input v-model="lowStock" @change="applyFilters" type="checkbox" class="rounded" />
          Sắp hết hàng
        </label>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
            <tr>
              <th class="px-4 py-3 text-left">Mã / Tên</th>
              <th class="px-4 py-3 text-left">Loại</th>
              <th class="px-4 py-3 text-right">Tồn kho</th>
              <th class="px-4 py-3 text-right">Đơn giá</th>
              <th class="px-4 py-3 text-right">Giá trị tồn</th>
              <th class="px-4 py-3 text-left">Chi nhánh</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="item in items.data" :key="item.id" class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <div class="font-medium text-gray-900">{{ item.name }}</div>
                <div class="text-xs text-gray-400">{{ item.code }}</div>
              </td>
              <td class="px-4 py-3 text-gray-600">{{ item.category_label }}</td>
              <td class="px-4 py-3 text-right">
                <span :class="item.is_low_stock ? 'text-red-600 font-semibold' : 'text-gray-700'">
                  {{ item.current_stock_qty }} {{ item.unit }}
                </span>
                <div v-if="item.is_low_stock" class="text-xs text-red-400">Dưới mức tối thiểu</div>
              </td>
              <td class="px-4 py-3 text-right text-gray-700">{{ formatVnd(item.unit_cost) }}</td>
              <td class="px-4 py-3 text-right text-gray-700 font-medium">{{ formatVnd(item.stock_value) }}</td>
              <td class="px-4 py-3 text-gray-500 text-xs">{{ item.branch_name }}</td>
              <td class="px-4 py-3 text-right">
                <a :href="route('inventory.show', item.id)"
                   class="text-primary-600 hover:underline text-xs font-medium">Chi tiết</a>
              </td>
            </tr>
            <tr v-if="!items.data.length">
              <td colspan="7" class="px-4 py-8 text-center text-gray-400">Không có dữ liệu</td>
            </tr>
          </tbody>
        </table>
        <div class="px-4 py-3 border-t">
          <Pagination :links="items.links" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
