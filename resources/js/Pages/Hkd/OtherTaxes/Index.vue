<template>
    <AppLayout title="Thuế khác HKD (S3a)">
        <div class="max-w-4xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Thuế khác — {{ profile.full_name }}</h1>
                <button v-if="!isLocked && can('hkd.manage')" @click="openAdd" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">+ Thêm</button>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                <input v-model="curPeriod" type="month" @change="navigate" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                <span v-if="isLocked" class="text-xs font-medium bg-gray-100 text-gray-500 px-2 py-1 rounded">🔒 Kỳ đã chốt</span>
                <div class="ml-auto text-sm text-gray-500">
                    Tổng thuế phải nộp: <strong class="text-gray-800">{{ fmtVnd(totalTax) }}</strong>
                    <span class="ml-3">Đã nộp: <strong class="text-green-700">{{ fmtVnd(totalPaid) }}</strong></span>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Loại thuế</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Căn cứ tính</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Tỷ lệ</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Số thuế</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Hạn nộp</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Đã nộp</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500">Trạng thái</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="entries.length === 0"><td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">Chưa có thuế khác kỳ này</td></tr>
                        <tr v-for="e in entries" :key="e.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-700">{{ e.tax_type }}</td>
                            <td class="px-4 py-2 text-right font-mono text-gray-600">{{ fmtVnd(e.taxable_amount) }}</td>
                            <td class="px-4 py-2 text-right font-mono text-gray-600">{{ (e.tax_rate * 100).toFixed(1) }}%</td>
                            <td class="px-4 py-2 text-right font-mono font-semibold text-gray-800">{{ fmtVnd(e.tax_amount) }}</td>
                            <td class="px-4 py-2 text-gray-600 text-xs">{{ e.due_date }}</td>
                            <td class="px-4 py-2 text-right font-mono text-green-700">{{ fmtVnd(e.paid_amount) }}</td>
                            <td class="px-4 py-2 text-center">
                                <span :class="e.is_paid ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'" class="text-xs px-2 py-0.5 rounded">
                                    {{ e.is_paid ? 'Đã nộp' : 'Chưa nộp' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <button v-if="!isLocked && can('hkd.manage')" @click="openEdit(e)" class="text-xs text-primary-600 hover:underline mr-2">Sửa</button>
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
                        <h3 class="font-semibold text-gray-800">{{ editId ? 'Sửa' : 'Thêm' }} thuế khác</h3>
                        <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="submit" class="p-5 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2"><label class="label">Loại thuế <span class="text-red-500">*</span></label><input v-model="form.tax_type" required class="input-field" placeholder="Thuế môn bài, thuế nhà đất..." /></div>
                            <div><label class="label">Căn cứ tính thuế (₫)</label><input v-model.number="form.taxable_amount" type="number" min="0" class="input-field" /></div>
                            <div><label class="label">Tỷ lệ thuế (0–1)</label><input v-model.number="form.tax_rate" type="number" step="0.001" min="0" max="1" class="input-field" placeholder="0.01" /></div>
                            <div><label class="label">Số thuế phải nộp (₫) <span class="text-red-500">*</span></label><input v-model.number="form.tax_amount" type="number" min="0" required class="input-field" /></div>
                            <div><label class="label">Hạn nộp</label><input v-model="form.due_date" type="date" class="input-field" /></div>
                            <div><label class="label">Ngày nộp</label><input v-model="form.paid_date" type="date" class="input-field" /></div>
                            <div><label class="label">Số đã nộp (₫)</label><input v-model.number="form.paid_amount" type="number" min="0" class="input-field" /></div>
                            <div class="col-span-2"><label class="label">Ghi chú</label><input v-model="form.notes" class="input-field" /></div>
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
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ profile: Object, entries: Array, period: String, isLocked: Boolean });

const curPeriod = ref(props.period);
const showForm  = ref(false);
const editId    = ref(null);
const form = useForm({ period: props.period, tax_type: '', taxable_amount: 0, tax_rate: 0, tax_amount: 0, due_date: '', paid_date: '', paid_amount: 0, notes: '' });

const totalTax  = computed(() => props.entries.reduce((s, e) => s + (e.tax_amount  || 0), 0));
const totalPaid = computed(() => props.entries.reduce((s, e) => s + (e.paid_amount || 0), 0));

function navigate() { router.get(route('hkd.other-taxes.index'), { period: curPeriod.value }, { preserveState: true }); }
function openAdd()   { form.reset(); form.period = curPeriod.value; editId.value = null; showForm.value = true; }
function openEdit(e) { form.period = e.period; form.tax_type = e.tax_type; form.taxable_amount = e.taxable_amount; form.tax_rate = e.tax_rate; form.tax_amount = e.tax_amount; form.due_date = e.due_date; form.paid_date = e.paid_date; form.paid_amount = e.paid_amount; form.notes = e.notes; editId.value = e.id; showForm.value = true; }
function submit() {
    if (editId.value) {
        form.put(route('hkd.other-taxes.update', editId.value), { onSuccess: () => { showForm.value = false; } });
    } else {
        form.post(route('hkd.other-taxes.store'), { onSuccess: () => { showForm.value = false; } });
    }
}
function deleteEntry(id) { if (confirm('Xoá dòng thuế này?')) router.delete(route('hkd.other-taxes.destroy', id)); }
function fmtVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
</script>

<style scoped>
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
.label { @apply block text-sm font-medium text-gray-700 mb-1; }
</style>
