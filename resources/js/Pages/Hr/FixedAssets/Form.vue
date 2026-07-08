<template>
    <AppLayout :title="asset ? 'Sửa tài sản cố định' : 'Thêm tài sản cố định'">
        <div class="max-w-2xl mx-auto bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-6">
                {{ asset ? 'Cập nhật tài sản cố định' : 'Thêm tài sản cố định mới' }}
            </h2>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormInput label="Tên tài sản" :error="form.errors.name" required class="sm:col-span-2">
                        <input v-model="form.name" type="text" class="input-field" />
                    </FormInput>

                    <FormInput label="Loại tài sản" :error="form.errors.category" required>
                        <select v-model="form.category" class="input-field">
                            <option value="">-- Chọn --</option>
                            <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                        </select>
                    </FormInput>

                    <FormInput label="Chi nhánh" :error="form.errors.branch_id">
                        <select v-model="form.branch_id" class="input-field">
                            <option :value="null">-- Tất cả --</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </FormInput>

                    <FormInput label="Ngày mua" :error="form.errors.acquisition_date" required>
                        <input v-model="form.acquisition_date" type="date" class="input-field" />
                    </FormInput>

                    <FormInput label="Nguyên giá (₫)" :error="form.errors.acquisition_cost" required>
                        <input v-model.number="form.acquisition_cost" type="number" min="1" class="input-field" />
                    </FormInput>

                    <FormInput label="Thời gian sử dụng (tháng)" :error="form.errors.useful_life_months" required>
                        <input v-model.number="form.useful_life_months" type="number" min="1" max="600" class="input-field" />
                    </FormInput>

                    <div class="sm:col-span-1 p-3 bg-indigo-50 rounded-lg border border-indigo-100">
                        <p class="text-xs text-indigo-500 font-medium">Khấu hao hàng tháng (tự tính)</p>
                        <p class="text-lg font-bold text-indigo-700 mt-1">{{ monthlyDepreciationLabel }}</p>
                    </div>
                </div>

                <FormInput label="Ghi chú" :error="form.errors.notes">
                    <textarea v-model="form.notes" rows="2" class="input-field" />
                </FormInput>

                <div class="flex justify-end gap-3 pt-2">
                    <Link :href="route('hr.fixed-assets.index')" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</Link>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                        {{ asset ? 'Cập nhật' : 'Tạo tài sản' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import FormInput from '@/Components/Shared/FormInput.vue';

const props = defineProps({ asset: Object, branches: Array, categories: Array });

const form = useForm({
    name:                props.asset?.name ?? '',
    category:            props.asset?.category ?? '',
    branch_id:           props.asset?.branch_id ?? null,
    acquisition_date:    props.asset?.acquisition_date ?? '',
    acquisition_cost:    props.asset?.acquisition_cost ?? '',
    useful_life_months:  props.asset?.useful_life_months ?? '',
    notes:               props.asset?.notes ?? '',
});

const monthlyDepreciationLabel = computed(() => {
    if (!form.acquisition_cost || !form.useful_life_months) return '—';
    const monthly = Math.ceil(form.acquisition_cost / form.useful_life_months);
    return new Intl.NumberFormat('vi-VN').format(monthly) + ' ₫/tháng';
});

function submit() {
    if (props.asset) {
        form.put(route('hr.fixed-assets.update', props.asset.id));
    } else {
        form.post(route('hr.fixed-assets.store'));
    }
}
</script>

<style scoped>
.input-field {
    @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none;
}
</style>
