<template>
    <AppLayout title="Chi phí HKD">
        <div class="max-w-5xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Chi phí hợp lý — {{ profile.full_name }}</h1>
                <div class="flex gap-2">
                    <button v-if="!isLocked && can('hkd.manage')" @click="importExpenses" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">↓ Nhập từ chi phí</button>
                    <button v-if="!isLocked && can('hkd.manage')" @click="openAdd" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">+ Thêm</button>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                <input v-model="curPeriod" type="month" @change="navigate" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                <span v-if="isLocked" class="text-xs font-medium bg-gray-100 text-gray-500 px-2 py-1 rounded">🔒 Kỳ đã chốt</span>
                <div class="ml-auto text-sm">
                    <span class="text-gray-500">Tổng chi phí: <strong class="text-red-700">{{ fmtVnd(total) }}</strong></span>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Ngày</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Chứng từ</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Nhà CC</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Loại</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Diễn giải</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Số tiền</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="entries.length === 0"><td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">Chưa có chi phí kỳ này</td></tr>
                        <tr v-for="e in entries" :key="e.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ e.date }}</td>
                            <td class="px-4 py-2 text-gray-500 text-xs">{{ e.document_no ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-600 text-xs">{{ e.supplier_name ?? '—' }}</td>
                            <td class="px-4 py-2"><span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded">{{ e.category_label }}</span></td>
                            <td class="px-4 py-2 text-gray-700">{{ e.description }}</td>
                            <td class="px-4 py-2 text-right font-mono text-red-600">{{ fmtVnd(e.amount) }}</td>
                            <td class="px-4 py-2 text-right">
                                <button v-if="!isLocked && can('hkd.manage') && e.source_type === 'manual'" @click="openEdit(e)" class="text-xs text-primary-600 hover:underline mr-2">Sửa</button>
                                <button v-if="!isLocked && can('hkd.manage')" @click="deleteEntry(e.id)" class="text-xs text-red-500 hover:underline">Xoá</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showForm" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">{{ editId ? 'Sửa' : 'Thêm' }} chi phí</h3>
                        <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="submit" class="p-5 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="label">Ngày <span class="text-red-500">*</span></label><input v-model="form.entry_date" type="date" required class="input-field" /></div>
                            <div><label class="label">Số chứng từ</label><input v-model="form.document_no" class="input-field" /></div>
                            <div><label class="label">Nhà cung cấp</label><input v-model="form.supplier_name" class="input-field" /></div>
                            <div><label class="label">MST nhà CC</label><input v-model="form.supplier_tax_code" class="input-field" /></div>
                            <div><label class="label">Loại chi phí</label>
                                <select v-model="form.category" class="input-field">
                                    <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                                </select>
                            </div>
                            <div><label class="label">Số tiền (₫) <span class="text-red-500">*</span></label><input v-model.number="form.amount" type="number" min="0" required class="input-field" /></div>
                            <div class="col-span-2"><label class="label">Diễn giải <span class="text-red-500">*</span></label><input v-model="form.description" required class="input-field" /></div>
                        </div>
                        <div class="flex justify-end gap-3 pt-1">
                            <button type="button" @click="showForm = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ profile: Object, entries: Array, period: String, isLocked: Boolean, categories: Array, total: Number });

const curPeriod = ref(props.period);
const showForm  = ref(false);
const editId    = ref(null);
const form = useForm({ period: props.period, entry_date: '', document_no: '', supplier_name: '', supplier_tax_code: '', category: 'other', description: '', amount: 0, notes: '' });

function navigate() { router.get(route('hkd.expenses.index'), { period: curPeriod.value }, { preserveState: true }); }
function openAdd()  { form.reset(); form.period = curPeriod.value; editId.value = null; showForm.value = true; }
function openEdit(e) { form.period = e.period; form.entry_date = e.date.split('/').reverse().join('-'); form.document_no = e.document_no; form.supplier_name = e.supplier_name; form.category = e.category; form.description = e.description; form.amount = e.amount; editId.value = e.id; showForm.value = true; }
function submit() {
    if (editId.value) {
        form.put(route('hkd.expenses.update', editId.value), { onSuccess: () => { showForm.value = false; } });
    } else {
        form.post(route('hkd.expenses.store'), { onSuccess: () => { showForm.value = false; } });
    }
}
function deleteEntry(id) { if (confirm('Xoá dòng chi phí này?')) router.delete(route('hkd.expenses.destroy', id)); }
function importExpenses() { if (confirm(`Nhập chi phí từ kỳ ${curPeriod.value}?`)) router.post(route('hkd.expenses.import-expenses'), { period: curPeriod.value }); }
function fmtVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
</script>

<style scoped>
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
.label { @apply block text-sm font-medium text-gray-700 mb-1; }
</style>
