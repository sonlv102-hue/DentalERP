<template>
    <div class="bg-white rounded-xl border border-l-4 border-l-sky-400 p-5 space-y-4">
        <h3 class="text-base font-semibold text-sky-700 flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-700 text-xs flex items-center justify-center font-bold">5</span>
            Trợ cấp & Phúc lợi
        </h3>

        <div class="grid grid-cols-3 gap-4">
            <FormInput label="Hỗ trợ ăn trưa / ca (VND)" :error="errors.lunch_allowance">
                <input v-model.number="form.lunch_allowance" type="number" min="0" step="50000" placeholder="0" class="input-field" />
            </FormInput>
            <FormInput label="Hỗ trợ xăng xe / đi lại (VND)" :error="errors.travel_allowance">
                <input v-model.number="form.travel_allowance" type="number" min="0" step="50000" placeholder="0" class="input-field" />
            </FormInput>
            <FormInput label="Hỗ trợ điện thoại / liên lạc (VND)" :error="errors.phone_allowance">
                <input v-model.number="form.phone_allowance" type="number" min="0" step="50000" placeholder="0" class="input-field" />
            </FormInput>
        </div>

        <div class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-700">
            <span class="font-semibold">PC Hiệu quả CV (KPI):</span>
            Biến động theo kết quả công việc — nhập từng tháng trong bảng lương hoặc module KPI.
            Không nhập KPI cố định tại đây.
        </div>
    </div>
</template>

<script setup>
import FormInput from '@/Components/Shared/FormInput.vue';

defineProps({
    form:   { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
});
</script>

<style scoped>
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
