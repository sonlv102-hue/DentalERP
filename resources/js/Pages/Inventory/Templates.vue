<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({ templates: Array, services: Array, items: Array });

const showForm = ref(false);
const form = useForm({
  service_id:        '',
  service_step_id:   '',
  inventory_item_id: '',
  qty_per_execution: 1,
  notes:             '',
});

function submit() {
  form.post(route('inventory.templates.store'), {
    onSuccess: () => { showForm.value = false; form.reset(); },
  });
}

function destroy(id) {
  if (!confirm('Xóa định mức này?')) return;
  router.delete(route('inventory.templates.destroy', id));
}

// Group by service
const grouped = props.templates.reduce((acc, t) => {
  const key = t.service_name ?? '—';
  if (!acc[key]) acc[key] = [];
  acc[key].push(t);
  return acc;
}, {});
</script>

<template>
  <AppLayout title="Định mức vật tư">
    <div class="max-w-5xl mx-auto px-4 py-6 space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Định mức vật tư theo dịch vụ</h1>
          <p class="text-sm text-gray-500 mt-1">Quy định số lượng vật tư tiêu hao cho mỗi lần thực hiện công đoạn</p>
        </div>
        <div class="flex gap-2">
          <a :href="route('inventory.index')" class="px-3 py-2 text-sm bg-white border rounded-lg text-gray-700">
            ← Kho vật tư
          </a>
          <button @click="showForm = !showForm"
                  class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700">
            + Thêm định mức
          </button>
        </div>
      </div>

      <!-- Add form -->
      <div v-if="showForm" class="bg-primary-50 border border-primary-200 rounded-xl p-5 space-y-4">
        <h3 class="font-medium text-primary-800">Thêm định mức vật tư</h3>
        <form @submit.prevent="submit" class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-xs text-gray-600">Dịch vụ *</label>
            <select v-model="form.service_id" required class="w-full border rounded-lg px-3 py-2 text-sm mt-1">
              <option value="">Chọn dịch vụ</option>
              <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div>
            <label class="text-xs text-gray-600">Công đoạn (tùy chọn)</label>
            <input v-model="form.service_step_id" type="number" placeholder="ID công đoạn"
                   class="w-full border rounded-lg px-3 py-2 text-sm mt-1" />
            <p class="text-xs text-gray-400 mt-0.5">Để trống = áp dụng toàn dịch vụ</p>
          </div>
          <div>
            <label class="text-xs text-gray-600">Vật tư *</label>
            <select v-model="form.inventory_item_id" required class="w-full border rounded-lg px-3 py-2 text-sm mt-1">
              <option value="">Chọn vật tư</option>
              <option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }} ({{ i.unit }})</option>
            </select>
          </div>
          <div>
            <label class="text-xs text-gray-600">Định mức / lần thực hiện *</label>
            <input v-model="form.qty_per_execution" type="number" step="0.001" min="0.001" required
                   class="w-full border rounded-lg px-3 py-2 text-sm mt-1" />
          </div>
          <div class="col-span-2">
            <label class="text-xs text-gray-600">Ghi chú</label>
            <input v-model="form.notes" type="text" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" />
          </div>
          <div class="col-span-2 flex gap-2">
            <button type="submit" :disabled="form.processing"
                    class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg">Lưu</button>
            <button type="button" @click="showForm = false"
                    class="px-4 py-2 text-sm bg-white border rounded-lg">Hủy</button>
          </div>
        </form>
      </div>

      <!-- Templates grouped by service -->
      <div v-for="(tpls, svcName) in grouped" :key="svcName"
           class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-4 py-3 border-b bg-gray-50 font-medium text-sm text-gray-700">{{ svcName }}</div>
        <table class="min-w-full text-sm">
          <thead class="text-xs text-gray-500 uppercase">
            <tr>
              <th class="px-4 py-2 text-left">Công đoạn</th>
              <th class="px-4 py-2 text-left">Vật tư</th>
              <th class="px-4 py-2 text-right">Định mức / lần</th>
              <th class="px-4 py-2"></th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="t in tpls" :key="t.id" class="hover:bg-gray-50">
              <td class="px-4 py-2 text-gray-600">{{ t.step_name }}</td>
              <td class="px-4 py-2 text-gray-800 font-medium">{{ t.item_name }}</td>
              <td class="px-4 py-2 text-right text-gray-700">{{ t.qty_per_execution }} {{ t.item_unit }}</td>
              <td class="px-4 py-2 text-right">
                <button @click="destroy(t.id)" class="text-red-500 hover:text-red-700 text-xs">Xóa</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="!templates.length" class="bg-white rounded-xl border p-8 text-center text-gray-400">
        Chưa có định mức nào. Thêm định mức để hệ thống tự xuất kho khi thực hiện điều trị.
      </div>
    </div>
  </AppLayout>
</template>
