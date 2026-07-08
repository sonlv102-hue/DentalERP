<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({ item: Object, branches: Array });

const form = useForm({
  name:          props.item?.name          ?? '',
  category:      props.item?.category      ?? 'material',
  unit:          props.item?.unit          ?? 'cái',
  branch_id:     props.item?.branch_id     ?? '',
  min_stock_qty: props.item?.min_stock_qty ?? 0,
  unit_cost:     props.item?.unit_cost     ?? 0,
  notes:         props.item?.notes         ?? '',
});

function submit() {
  if (props.item) {
    form.put(route('inventory.update', props.item.id));
  } else {
    form.post(route('inventory.store'));
  }
}

const categories = [
  { value: 'material',   label: 'Vật tư' },
  { value: 'medicine',   label: 'Thuốc' },
  { value: 'equipment',  label: 'Thiết bị nhỏ' },
  { value: 'consumable', label: 'Vật tư tiêu hao' },
  { value: 'other',      label: 'Khác' },
];

const commonUnits = ['cái', 'hộp', 'tuýp', 'ml', 'lọ', 'gói', 'bộ', 'tờ', 'cuộn'];
</script>

<template>
  <AppLayout :title="item ? 'Chỉnh sửa vật tư' : 'Thêm vật tư'">
    <div class="max-w-xl mx-auto px-4 py-6">
      <div class="bg-white rounded-xl shadow-sm border p-6 space-y-5">
        <h1 class="text-lg font-semibold text-gray-900">
          {{ item ? 'Chỉnh sửa vật tư' : 'Thêm vật tư mới' }}
        </h1>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm text-gray-700 mb-1">Tên vật tư *</label>
            <input v-model="form.name" type="text" required
                   class="w-full border rounded-lg px-3 py-2 text-sm" />
            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-1">Loại *</label>
              <select v-model="form.category" required class="w-full border rounded-lg px-3 py-2 text-sm">
                <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Đơn vị tính *</label>
              <input v-model="form.unit" type="text" list="unit-list" required
                     class="w-full border rounded-lg px-3 py-2 text-sm" />
              <datalist id="unit-list">
                <option v-for="u in commonUnits" :key="u" :value="u" />
              </datalist>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-1">Tồn tối thiểu</label>
              <input v-model="form.min_stock_qty" type="number" step="0.001" min="0"
                     class="w-full border rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Đơn giá (₫)</label>
              <input v-model="form.unit_cost" type="number" min="0"
                     class="w-full border rounded-lg px-3 py-2 text-sm" />
            </div>
          </div>

          <div>
            <label class="block text-sm text-gray-700 mb-1">Chi nhánh áp dụng</label>
            <select v-model="form.branch_id" class="w-full border rounded-lg px-3 py-2 text-sm">
              <option value="">Tất cả chi nhánh</option>
              <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm text-gray-700 mb-1">Ghi chú</label>
            <textarea v-model="form.notes" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm" />
          </div>

          <div class="flex gap-3 pt-2">
            <button type="submit" :disabled="form.processing"
                    class="px-5 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
              {{ item ? 'Cập nhật' : 'Tạo vật tư' }}
            </button>
            <a :href="route('inventory.index')"
               class="px-5 py-2 bg-white border text-gray-700 text-sm rounded-lg hover:bg-gray-50">
              Hủy
            </a>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
