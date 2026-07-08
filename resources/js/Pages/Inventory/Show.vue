<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ item: Object, transactions: Array, templates: Array });

const showStockIn  = ref(false);
const showAdjust   = ref(false);

const stockForm = useForm({ qty: '', unit_cost: props.item.unit_cost, date: new Date().toISOString().slice(0,10), document_no: '', notes: '' });
const adjForm   = useForm({ qty: '', notes: '' });

function submitStock() {
  stockForm.post(route('inventory.add-stock', props.item.id), {
    onSuccess: () => { showStockIn.value = false; stockForm.reset(); },
  });
}
function submitAdj() {
  adjForm.post(route('inventory.adjust', props.item.id), {
    onSuccess: () => { showAdjust.value = false; adjForm.reset(); },
  });
}

function formatVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }

const typeColors = { purchase: 'green', usage: 'blue', adjustment: 'yellow', return: 'gray' };
</script>

<template>
  <AppLayout :title="item.name">
    <div class="max-w-5xl mx-auto px-4 py-6 space-y-5">
      <!-- Header card -->
      <div class="bg-white rounded-xl shadow-sm border p-5 flex justify-between items-start">
        <div>
          <div class="text-xs text-gray-400 mb-1">{{ item.code }} · {{ item.category_label }}</div>
          <h1 class="text-xl font-semibold text-gray-900">{{ item.name }}</h1>
          <p class="text-sm text-gray-500 mt-1">{{ item.branch_name }}</p>
        </div>
        <div class="text-right space-y-1">
          <div :class="item.is_low_stock ? 'text-red-600 font-bold text-2xl' : 'text-gray-900 font-bold text-2xl'">
            {{ item.current_stock_qty }} <span class="text-sm font-normal">{{ item.unit }}</span>
          </div>
          <div class="text-sm text-gray-500">Đơn giá: {{ formatVnd(item.unit_cost) }}</div>
          <div class="text-sm text-gray-500">Giá trị: {{ formatVnd(item.stock_value) }}</div>
        </div>
      </div>

      <!-- Actions -->
      <div v-if="can('inventory.manage')" class="flex gap-2">
        <button @click="showStockIn = true"
                class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700">
          + Nhập kho
        </button>
        <button @click="showAdjust = true"
                class="px-4 py-2 text-sm bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
          Điều chỉnh tồn
        </button>
      </div>

      <!-- Stock-in form -->
      <div v-if="showStockIn" class="bg-green-50 border border-green-200 rounded-xl p-4 space-y-3">
        <h3 class="font-medium text-green-800">Nhập kho</h3>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs text-gray-600">Số lượng *</label>
            <input v-model="stockForm.qty" type="number" step="0.001" min="0.001" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" />
          </div>
          <div>
            <label class="text-xs text-gray-600">Đơn giá nhập *</label>
            <input v-model="stockForm.unit_cost" type="number" min="0" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" />
          </div>
          <div>
            <label class="text-xs text-gray-600">Ngày nhập *</label>
            <input v-model="stockForm.date" type="date" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" />
          </div>
          <div>
            <label class="text-xs text-gray-600">Số phiếu</label>
            <input v-model="stockForm.document_no" type="text" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" />
          </div>
          <div class="col-span-2">
            <label class="text-xs text-gray-600">Ghi chú</label>
            <input v-model="stockForm.notes" type="text" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" />
          </div>
        </div>
        <div class="flex gap-2">
          <button @click="submitStock" :disabled="stockForm.processing"
                  class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700">Lưu</button>
          <button @click="showStockIn = false" class="px-4 py-2 text-sm bg-white border rounded-lg">Hủy</button>
        </div>
      </div>

      <!-- Adjust form -->
      <div v-if="showAdjust" class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 space-y-3">
        <h3 class="font-medium text-yellow-800">Điều chỉnh tồn kho</h3>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs text-gray-600">Số lượng (âm = giảm) *</label>
            <input v-model="adjForm.qty" type="number" step="0.001" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" />
          </div>
          <div>
            <label class="text-xs text-gray-600">Lý do *</label>
            <input v-model="adjForm.notes" type="text" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" />
          </div>
        </div>
        <div class="flex gap-2">
          <button @click="submitAdj" :disabled="adjForm.processing"
                  class="px-4 py-2 text-sm bg-yellow-500 text-white rounded-lg">Lưu</button>
          <button @click="showAdjust = false" class="px-4 py-2 text-sm bg-white border rounded-lg">Hủy</button>
        </div>
      </div>

      <!-- Transaction history -->
      <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-4 py-3 border-b font-medium text-sm text-gray-700">Lịch sử nhập/xuất</div>
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
            <tr>
              <th class="px-4 py-2 text-left">Ngày</th>
              <th class="px-4 py-2 text-left">Loại</th>
              <th class="px-4 py-2 text-right">Số lượng</th>
              <th class="px-4 py-2 text-right">Đơn giá</th>
              <th class="px-4 py-2 text-right">Thành tiền</th>
              <th class="px-4 py-2 text-left">Ghi chú</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="t in transactions" :key="t.id" class="hover:bg-gray-50">
              <td class="px-4 py-2 text-gray-500">{{ t.transaction_date }}</td>
              <td class="px-4 py-2">
                <span :class="`px-2 py-0.5 rounded text-xs font-medium bg-${t.type_color}-100 text-${t.type_color}-700`">
                  {{ t.type_label }}
                </span>
              </td>
              <td class="px-4 py-2 text-right" :class="t.qty < 0 ? 'text-red-600' : 'text-green-600'">
                {{ t.qty > 0 ? '+' : '' }}{{ t.qty }}
              </td>
              <td class="px-4 py-2 text-right text-gray-600">{{ formatVnd(t.unit_cost) }}</td>
              <td class="px-4 py-2 text-right text-gray-700">{{ formatVnd(t.amount) }}</td>
              <td class="px-4 py-2 text-gray-500 text-xs">{{ t.notes }}</td>
            </tr>
            <tr v-if="!transactions.length">
              <td colspan="6" class="px-4 py-6 text-center text-gray-400">Chưa có giao dịch</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Service templates -->
      <div v-if="templates.length" class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-4 py-3 border-b font-medium text-sm text-gray-700">Định mức sử dụng theo dịch vụ</div>
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
            <tr>
              <th class="px-4 py-2 text-left">Dịch vụ</th>
              <th class="px-4 py-2 text-left">Công đoạn</th>
              <th class="px-4 py-2 text-right">Định mức / lần</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="t in templates" :key="t.id">
              <td class="px-4 py-2 text-gray-700">{{ t.service_name }}</td>
              <td class="px-4 py-2 text-gray-500">{{ t.step_name }}</td>
              <td class="px-4 py-2 text-right text-gray-700">{{ t.qty_per_execution }} {{ item.unit }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
