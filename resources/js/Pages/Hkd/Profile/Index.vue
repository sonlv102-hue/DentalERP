<template>
    <AppLayout title="Hồ sơ HKD — TT152/2025">
        <div class="max-w-4xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Hồ sơ Hộ kinh doanh (TT152/2025)</h1>
                <button v-if="!profile && can('hkd.manage')" @click="openCreate" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">+ Tạo hồ sơ</button>
            </div>

            <!-- No profile yet -->
            <div v-if="!profile" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400">
                <p class="text-sm">Chưa có hồ sơ HKD. Tạo mới để bắt đầu sử dụng chế độ kế toán TT152.</p>
            </div>

            <!-- Profile card -->
            <div v-else class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">{{ profile.full_name }}</h2>
                        <p class="text-sm text-gray-500 mt-0.5">MST: {{ profile.tax_code ?? '—' }} · {{ profile.address }}</p>
                        <span class="mt-2 inline-block text-xs font-medium px-2 py-0.5 rounded bg-indigo-100 text-indigo-700">{{ profile.tax_status_label }}</span>
                    </div>
                    <button v-if="can('hkd.manage')" @click="openEdit" class="text-sm text-primary-600 hover:underline">Sửa</button>
                </div>
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-gray-400">Đại diện</dt><dd class="text-gray-700">{{ profile.representative_name ?? '—' }} ({{ profile.representative_id ?? '—' }})</dd></div>
                    <div><dt class="text-gray-400">Cơ quan thuế</dt><dd class="text-gray-700">{{ profile.tax_authority_name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-400">Ngày ĐK</dt><dd class="text-gray-700">{{ profile.registration_date ?? '—' }}</dd></div>
                    <div><dt class="text-gray-400">Chi nhánh</dt><dd class="text-gray-700">{{ profile.branch ?? '—' }}</dd></div>
                </dl>
            </div>

            <!-- Tax rates table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Bảng tỷ lệ thuế (TT152)</h3>
                    <button v-if="can('hkd.manage')" @click="showRateForm = true" class="text-xs text-primary-600 hover:underline">+ Thêm</button>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Ngành</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">VAT %</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">TNCN %</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Hiệu lực từ</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Đến</th>
                        <th class="px-4 py-2"></th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="taxRates.length === 0"><td colspan="6" class="px-4 py-6 text-center text-gray-400 text-sm">Chưa có tỷ lệ thuế nào</td></tr>
                        <tr v-for="r in taxRates" :key="r.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-700">{{ r.revenue_category_label }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ (r.vat_rate * 100).toFixed(1) }}%</td>
                            <td class="px-4 py-2 text-right font-mono">{{ (r.pit_rate * 100).toFixed(1) }}%</td>
                            <td class="px-4 py-2 text-gray-600">{{ r.effective_from }}</td>
                            <td class="px-4 py-2 text-gray-400">{{ r.effective_to ?? 'Còn hiệu lực' }}</td>
                            <td class="px-4 py-2 text-right">
                                <button v-if="can('hkd.manage')" @click="deleteRate(r.id)" class="text-xs text-red-500 hover:underline">Xoá</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Profile form modal -->
        <Teleport to="body">
            <div v-if="showForm" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">{{ editing ? 'Cập nhật' : 'Tạo' }} hồ sơ HKD</h3>
                        <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="submit" class="p-5 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2"><label class="label">Tên HKD / Cá nhân KD <span class="text-red-500">*</span></label><input v-model="form.full_name" required class="input-field" /></div>
                            <div><label class="label">Mã số thuế</label><input v-model="form.tax_code" class="input-field" /></div>
                            <div><label class="label">CCCD/CMND</label><input v-model="form.id_number" class="input-field" /></div>
                            <div class="col-span-2"><label class="label">Địa chỉ</label><input v-model="form.address" class="input-field" /></div>
                            <div><label class="label">Người đại diện</label><input v-model="form.representative_name" class="input-field" /></div>
                            <div><label class="label">CCCD người đại diện</label><input v-model="form.representative_id" class="input-field" /></div>
                            <div class="col-span-2"><label class="label">Cơ quan thuế quản lý</label><input v-model="form.tax_authority_name" class="input-field" /></div>
                            <div><label class="label">Ngày đăng ký</label><input v-model="form.registration_date" type="date" class="input-field" /></div>
                            <div><label class="label">Chế độ thuế <span class="text-red-500">*</span></label>
                                <select v-model="form.tax_status" required class="input-field">
                                    <option v-for="s in taxStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-1">
                            <button type="button" @click="showForm = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tax rate form modal -->
            <div v-if="showRateForm" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Thêm tỷ lệ thuế</h3>
                        <button @click="showRateForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="submitRate" class="p-5 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2"><label class="label">Loại doanh thu</label>
                                <select v-model="rateForm.revenue_category" required class="input-field">
                                    <option v-for="c in revCategories" :key="c.value" :value="c.value">{{ c.label }}</option>
                                </select>
                            </div>
                            <div><label class="label">Tỷ lệ VAT (0–1)</label><input v-model.number="rateForm.vat_rate" type="number" step="0.001" min="0" max="1" required class="input-field" placeholder="0.01" /></div>
                            <div><label class="label">Tỷ lệ TNCN (0–1)</label><input v-model.number="rateForm.pit_rate" type="number" step="0.001" min="0" max="1" required class="input-field" placeholder="0.005" /></div>
                            <div><label class="label">Hiệu lực từ</label><input v-model="rateForm.effective_from" type="date" required class="input-field" /></div>
                            <div><label class="label">Đến (bỏ trống = còn HĐ)</label><input v-model="rateForm.effective_to" type="date" class="input-field" /></div>
                        </div>
                        <div class="flex justify-end gap-3 pt-1">
                            <button type="button" @click="showRateForm = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="rateForm.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ profile: Object, taxRates: Array, branches: Object, taxStatuses: Array, revCategories: Array });

const showForm = ref(false);
const showRateForm = ref(false);
const editing = ref(false);

const form = useForm({ full_name: '', tax_code: '', id_number: '', address: '', province: '', district: '', representative_name: '', representative_id: '', tax_authority_name: '', registration_date: '', tax_status: 'not_subject', branch_id: null });
const rateForm = useForm({ revenue_category: 'services', vat_rate: 0.01, pit_rate: 0.005, effective_from: '', effective_to: '' });

function openCreate() { form.reset(); editing.value = false; showForm.value = true; }
function openEdit() {
    const p = props.profile;
    form.full_name = p.full_name; form.tax_code = p.tax_code; form.id_number = p.id_number;
    form.address = p.address; form.representative_name = p.representative_name;
    form.representative_id = p.representative_id; form.tax_authority_name = p.tax_authority_name;
    form.registration_date = p.registration_date; form.tax_status = p.tax_status;
    editing.value = true; showForm.value = true;
}
function submit() {
    if (editing.value) {
        form.put(route('hkd.profiles.update', props.profile.id), { onSuccess: () => { showForm.value = false; } });
    } else {
        form.post(route('hkd.profiles.store'), { onSuccess: () => { showForm.value = false; } });
    }
}
function submitRate() { rateForm.post(route('hkd.tax-rates.store'), { onSuccess: () => { showRateForm.value = false; rateForm.reset(); } }); }
function deleteRate(id) { if (confirm('Xoá tỷ lệ thuế này?')) useForm().delete(route('hkd.tax-rates.destroy', id)); }
</script>

<style scoped>
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
.label { @apply block text-sm font-medium text-gray-700 mb-1; }
</style>
