<template>
    <div class="bg-white rounded-xl border border-l-4 border-l-slate-400 p-5 space-y-4">
        <h3 class="text-base font-semibold text-slate-700 flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs flex items-center justify-center font-bold">7</span>
            Địa chỉ & Ghi chú
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <FormInput label="Địa chỉ thường trú" :error="errors.permanent_address">
                <textarea v-model="form.permanent_address" rows="3" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành" class="input-field resize-none" />
            </FormInput>
            <FormInput label="Ghi chú" :error="errors.notes">
                <textarea v-model="form.notes" rows="3" placeholder="Thông tin nội bộ, lưu ý khi phân công..." class="input-field resize-none" />
            </FormInput>
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
