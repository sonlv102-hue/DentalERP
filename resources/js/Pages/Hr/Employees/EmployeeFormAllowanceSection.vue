<template>
    <div class="bg-white rounded-xl border border-l-4 border-l-emerald-400 p-5 space-y-4">
        <h3 class="text-base font-semibold text-emerald-700 flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs flex items-center justify-center font-bold">4</span>
            Phụ cấp lương
        </h3>

        <div class="grid grid-cols-2 gap-4">
            <FormInput label="Phụ cấp trách nhiệm / chức vụ (VND)" :error="errors.responsibility_allowance">
                <input v-model.number="form.responsibility_allowance" type="number" min="0" step="100000" placeholder="0" class="input-field" />
            </FormInput>
            <FormInput label="Phụ cấp cố định khác (VND)" :error="errors.fixed_allowance">
                <input v-model.number="form.fixed_allowance" type="number" min="0" step="100000" placeholder="0" class="input-field" />
            </FormInput>
        </div>

        <div class="bg-emerald-50 rounded-lg px-4 py-3 text-sm font-medium text-emerald-800 flex items-center justify-between">
            <span>Tổng phụ cấp cố định</span>
            <span class="font-mono text-base">{{ formatVnd(totalFixed) }} / tháng</span>
        </div>

        <p class="text-xs text-gray-400">
            Nhóm phụ cấp này tính vào tổng thu nhập cố định. Xác định khoản nào tính BHXH trong quy chế lương.
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import FormInput from '@/Components/Shared/FormInput.vue';

const props = defineProps({
    form:   { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
});

const totalFixed = computed(() =>
    (props.form.responsibility_allowance || 0) + (props.form.fixed_allowance || 0)
);

function formatVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
</script>

<style scoped>
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
