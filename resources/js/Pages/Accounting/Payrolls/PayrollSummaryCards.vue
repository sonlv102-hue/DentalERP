<template>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <div v-for="card in cards" :key="card.label"
            class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col gap-1">
            <p class="text-xs text-gray-500">{{ card.label }}</p>
            <p :class="['text-lg font-bold', card.color]">{{ vnd(card.value) }}</p>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({ payroll: Object });

function vnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }

const cards = [
    { label: 'Tổng lương CB',     value: props.payroll.total_base_salary,          color: 'text-gray-800' },
    { label: 'Tổng phụ cấp',      value: props.payroll.total_allowances,            color: 'text-blue-700' },
    { label: 'BHXH CP công ty',   value: props.payroll.total_company_insurance,     color: 'text-orange-600' },
    { label: 'Thuế TNCN',         value: props.payroll.total_personal_income_tax,   color: 'text-red-600' },
    { label: 'Tổng thực lĩnh',    value: props.payroll.total_net_salary,            color: 'text-green-700' },
];
</script>
