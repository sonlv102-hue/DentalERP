<template>
    <AppLayout title="Doanh thu HKD">
        <div class="max-w-5xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Doanh thu — {{ profile.full_name }}</h1>
                <div class="flex gap-2">
                    <button v-if="!isLocked && can('hkd.manage')" @click="importInvoices" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">↓ Nhập từ HĐ</button>
                    <button v-if="!isLocked && can('hkd.manage')" @click="openAdd" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">+ Thêm</button>
                </div>
            </div>

            <!-- Period filter + lock indicator -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                <input v-model="curPeriod" type="month" @change="navigate" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                <span v-if="isLocked" class="text-xs font-medium bg-gray-100 text-gray-500 px-2 py-1 rounded">🔒 Kỳ đã chốt</span>
                <div class="ml-auto flex gap-4 text-sm">
                    <span class="text-gray-500">Doanh thu: <strong class="text-gray-800">{{ fmtVnd(totals.amount) }}</strong></span>
                    <span v-if="profile.tax_status !== 'not_subject'" class="text-gray-500">VAT: <strong class="text-blue-700">{{ fmtVnd(totals.vat_amount) }}</strong></span>
                    <span v-if="profile.tax_status === 'vat_pit_revenue'" class="text-gray-500">TNCN: <strong class="text-purple-700">{{ fmtVnd(totals.pit_amount) }}</strong></span>
                </div>
            </div>

            <!-- Entries table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Ngày</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Chứng từ</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Diễn giải</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Loại DT</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Doanh thu</th>
                            <th v-if="profile.tax_status !== 'not_subject'" class="px-4 py-2 text-right text-xs font-semibold text-gray-500">VAT</th>
                            <th v-if="profile.tax_status === 'vat_pit_revenue'" class="px-4 py-2 text-right text-xs font-semibold text-gray-500">TNCN</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="entries.length === 0"><td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">Chưa có doanh thu kỳ này</td></tr>
                        <tr v-for="e in entries" :key="e.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ e.date }}</td>
                            <td class="px-4 py-2 text-gray-500 text-xs">{{ e.document_no ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ e.description }}<span v-if="e.buyer_name" class="text-xs text-gray-400 ml-1">({{ e.buyer_name }})</span></td>
                            <td class="px-4 py-2"><span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded">{{ e.revenue_category_label }}</span></td>
                            <td class="px-4 py-2 text-right font-mono text-gray-700">{{ fmtVnd(e.amount) }}</td>
                            <td v-if="profile.tax_status !== 'not_subject'" class="px-4 py-2 text-right font-mono text-blue-700">{{ fmtVnd(e.vat_amount) }}</td>
                            <td v-if="profile.tax_status === 'vat_pit_revenue'" class="px-4 py-2 text-right font-mono text-purple-700">{{ fmtVnd(e.pit_amount) }}</td>
                            <td class="px-4 py-2 text-right">
                                <button v-if="!isLocked && can('hkd.manage') && e.source_type === 'manual'" @click="openEdit(e)" class="text-xs text-primary-600 hover:underline mr-2">Sửa</button>
                                <button v-if="!isLocked && can('hkd.manage')" @click="deleteEntry(e.id)" class="text-xs text-red-500 hover:underline">Xoá</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit modal -->
        <Teleport to="body">
            <div v-if="showForm" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">{{ editId ? 'Sửa' : 'Thêm' }} doanh thu</h3>
                        <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="submit" class="p-5 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="label">Ngày <span class="text-red-500">*</span></label><input v-model="form.entry_date" type="date" required class="input-field" /></div>
                            <div><label class="label">Số chứng từ</label><input v-model="form.document_no" class="input-field" /></div>
                            <div class="col-span-2"><label class="label">Diễn giải <span class="text-red-500">*</span></label><input v-model="form.description" required class="input-field" /></div>
                            <div><label class="label">Tên người mua</label><input v-model="form.buyer_name" class="input-field" /></div>
                            <div><label class="label">MST người mua</label><input v-model="form.buyer_tax_code" class="input-field" /></div>
                            <div><label class="label">Loại doanh thu</label>
                                <select v-model="form.revenue_category" class="input-field">
                                    <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                                </select>
                            </div>
                            <div><label class="label">Doanh thu (₫) <span class="text-red-500">*</span></label><input v-model.number="form.amount" type="number" min="0" required class="input-field" /></div>
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
const props = defineProps({ profile: Object, entries: Array, period: String, isLocked: Boolean, categories: Array, totals: Object });

const curPeriod = ref(props.period);
const showForm  = ref(false);
const editId    = ref(null);

const form = useForm({ period: props.period, entry_date: '', document_no: '', buyer_name: '', buyer_tax_code: '', description: '', revenue_category: 'services', amount: 0, notes: '' });

function navigate() { router.get(route('hkd.revenue.index'), { period: curPeriod.value }, { preserveState: true }); }

function openAdd()  { form.reset(); form.period = curPeriod.value; editId.value = null; showForm.value = true; }
function openEdit(e) { form.period = e.period; form.entry_date = e.date.split('/').reverse().join('-'); form.document_no = e.document_no; form.buyer_name = e.buyer_name; form.buyer_tax_code = e.buyer_tax_code; form.description = e.description; form.revenue_category = e.revenue_category; form.amount = e.amount; form.notes = e.notes; editId.value = e.id; showForm.value = true; }
function submit() {
    if (editId.value) {
        form.put(route('hkd.revenue.update', editId.value), { onSuccess: () => { showForm.value = false; } });
    } else {
        form.post(route('hkd.revenue.store'), { onSuccess: () => { showForm.value = false; } });
    }
}
function deleteEntry(id) { if (confirm('Xoá dòng doanh thu này?')) router.delete(route('hkd.revenue.destroy', id)); }
function importInvoices() { if (confirm(`Nhập doanh thu từ hóa đơn bệnh nhân đã thanh toán kỳ ${curPeriod.value}?`)) router.post(route('hkd.revenue.import-invoices'), { period: curPeriod.value }); }

function fmtVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
</script>

<style scoped>
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
.label { @apply block text-sm font-medium text-gray-700 mb-1; }
</style>
