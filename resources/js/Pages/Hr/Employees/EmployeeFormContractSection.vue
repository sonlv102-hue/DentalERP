<template>
    <div class="bg-white rounded-xl border border-l-4 border-l-yellow-400 p-5 space-y-4">
        <h3 class="text-base font-semibold text-yellow-700 flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-yellow-100 text-yellow-700 text-xs flex items-center justify-center font-bold">2</span>
            Hợp đồng lao động
        </h3>
        <div class="grid grid-cols-3 gap-4">
            <FormInput label="Ngày vào làm" :error="errors.start_date">
                <input v-model="form.start_date" type="date" class="input-field" />
            </FormInput>
            <FormInput label="Loại hợp đồng *" :error="errors.contract_type">
                <select v-model="form.contract_type" class="input-field">
                    <option value="">-- Chọn loại HĐ --</option>
                    <option v-for="c in contractTypes" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
            </FormInput>
            <FormInput label="Trạng thái *" :error="errors.employment_status">
                <select v-model="form.employment_status" class="input-field">
                    <option v-for="s in employmentStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </FormInput>
        </div>
    </div>
</template>

<script setup>
import FormInput from '@/Components/Shared/FormInput.vue';

defineProps({
    form:               { type: Object, required: true },
    errors:             { type: Object, default: () => ({}) },
    contractTypes:      { type: Array, default: () => [] },
    employmentStatuses: { type: Array, default: () => [] },
});
</script>

<style scoped>
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
