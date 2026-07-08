<template>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="text-xs border-collapse min-w-max">
                <thead>
                    <!-- Group header row -->
                    <tr class="bg-[#1E3A5F] text-white">
                        <th class="sticky left-0 z-20 bg-[#1E3A5F] px-2 py-2 w-8 border-r border-blue-900">STT</th>
                        <th class="sticky left-8 z-20 bg-[#1E3A5F] px-2 py-2 w-40 text-left border-r border-blue-900">Họ và tên</th>
                        <th class="sticky left-48 z-20 bg-[#1E3A5F] px-2 py-2 w-24 text-left border-r border-blue-900">Chức vụ</th>
                        <th colspan="5" class="px-2 py-1 text-center border-r border-blue-900 bg-[#1E4A8F]">Lương & Công</th>
                        <th colspan="7" class="px-2 py-1 text-center border-r border-blue-900 bg-[#1A5F3A]">Phụ cấp / Trợ cấp</th>
                        <th colspan="4" class="px-2 py-1 text-center border-r border-blue-900 bg-[#7A3B1E]">BH Doanh nghiệp</th>
                        <th colspan="4" class="px-2 py-1 text-center border-r border-blue-900 bg-[#5A2D6F]">BH Nhân viên</th>
                        <th colspan="3" class="px-2 py-1 text-center border-r border-blue-900 bg-[#6B1E1E]">Thuế TNCN</th>
                        <th colspan="3" class="px-2 py-1 text-center bg-[#1A4A2A]">Kết quả</th>
                    </tr>
                    <!-- Column header row -->
                    <tr class="bg-gray-700 text-gray-200 text-[10px]">
                        <th class="sticky left-0 z-20 bg-gray-700 border-r border-gray-600 py-1" />
                        <th class="sticky left-8 z-20 bg-gray-700 border-r border-gray-600 px-2 py-1 text-left">Mã NV</th>
                        <th class="sticky left-48 z-20 bg-gray-700 border-r border-gray-600" />
                        <th v-for="col in dataCols" :key="col.key" class="px-1 py-1 text-center whitespace-nowrap border-r border-gray-600">{{ col.label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="group in grouped" :key="group.department">
                        <!-- Department header -->
                        <tr class="bg-blue-50 border-y border-blue-200">
                            <td class="sticky left-0 z-10 bg-blue-50 px-2 py-1 text-blue-800 font-bold" />
                            <td class="sticky left-8 z-10 bg-blue-50 px-2 py-1 text-blue-800 font-bold" colspan="2">{{ group.department }}</td>
                            <td v-for="col in dataCols" :key="col.key" class="px-1 py-1 text-center text-blue-700 border-r border-blue-100">
                                {{ col.numeric ? compact(subtotal(group.items, col.key)) : '' }}
                            </td>
                        </tr>
                        <!-- Employee rows -->
                        <tr v-for="(item, idx) in group.items" :key="item.id"
                            :class="['border-b border-gray-100', idx % 2 === 1 ? 'bg-gray-50' : 'bg-white', canEdit ? 'cursor-pointer hover:bg-primary-50' : '']"
                            @click="canEdit && $emit('edit', item)">
                            <td class="sticky left-0 z-10 px-2 py-1.5 text-center text-gray-400 border-r border-gray-200" :class="idx % 2 === 1 ? 'bg-gray-50' : 'bg-white'">{{ idx + 1 }}</td>
                            <td class="sticky left-8 z-10 px-2 py-1.5 border-r border-gray-200" :class="idx % 2 === 1 ? 'bg-gray-50' : 'bg-white'">
                                <div class="font-medium text-gray-800 whitespace-nowrap">{{ item.employee_name }}</div>
                                <div class="text-gray-400 font-mono">{{ item.employee_code }}</div>
                            </td>
                            <td class="sticky left-48 z-10 px-2 py-1.5 text-gray-500 whitespace-nowrap border-r border-gray-200" :class="idx % 2 === 1 ? 'bg-gray-50' : 'bg-white'">{{ item.position_name }}</td>
                            <td v-for="col in dataCols" :key="col.key" class="px-1 py-1.5 text-right text-gray-700 border-r border-gray-100 whitespace-nowrap">
                                <span v-if="col.key === 'workday_ratio'">{{ pct(item[col.key]) }}</span>
                                <span v-else-if="col.numeric">{{ compact(item[col.key]) }}</span>
                                <span v-else>{{ item[col.key] }}</span>
                            </td>
                        </tr>
                        <!-- Department subtotal -->
                        <tr class="bg-yellow-50 border-y border-yellow-200 font-semibold text-[10px]">
                            <td class="sticky left-0 z-10 bg-yellow-50 px-2 py-1" />
                            <td class="sticky left-8 z-10 bg-yellow-50 px-2 py-1 text-yellow-800" colspan="2">Tổng {{ group.department }}</td>
                            <td v-for="col in dataCols" :key="col.key" class="px-1 py-1 text-right text-yellow-800 border-r border-yellow-100">
                                {{ col.numeric ? compact(subtotal(group.items, col.key)) : '' }}
                            </td>
                        </tr>
                    </template>
                    <!-- Grand total -->
                    <tr class="bg-yellow-100 border-t-2 border-yellow-400 font-bold text-[11px]">
                        <td class="sticky left-0 z-10 bg-yellow-100 px-2 py-2" />
                        <td class="sticky left-8 z-10 bg-yellow-100 px-2 py-2 text-gray-800 uppercase" colspan="2">Tổng cộng</td>
                        <td v-for="col in dataCols" :key="col.key" class="px-1 py-2 text-right text-gray-800 border-r border-yellow-200">
                            {{ col.numeric ? compact(grandTotal(col.key)) : '' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Signature block (print only) -->
        <div class="hidden print:flex justify-around mt-12 px-6 pb-4 text-sm text-gray-700">
            <div class="text-center"><p class="font-semibold mb-12">Người lập biểu</p><p class="border-t border-gray-400 pt-1">Ký, ghi rõ họ tên</p></div>
            <div class="text-center"><p class="font-semibold mb-12">Kế toán trưởng</p><p class="border-t border-gray-400 pt-1">Ký, ghi rõ họ tên</p></div>
            <div class="text-center"><p class="font-semibold mb-12">Giám đốc</p><p class="border-t border-gray-400 pt-1">Ký, đóng dấu</p></div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({ grouped: Array, canEdit: Boolean });
const emit  = defineEmits(['edit']);

const dataCols = [
    { key: 'base_salary',                    label: 'Lương CB',   numeric: true },
    { key: 'standard_working_days',          label: 'NC Chuẩn',  numeric: true },
    { key: 'actual_working_days',            label: 'NC TT',     numeric: true },
    { key: 'workday_ratio',                  label: 'Tỷ lệ',     numeric: false },
    { key: 'salary_by_workday',              label: 'L.Công',    numeric: true },
    { key: 'fixed_allowance',               label: 'PC Cố định', numeric: true },
    { key: 'responsibility_allowance',       label: 'PC TN',     numeric: true },
    { key: 'lunch_allowance',               label: 'PC Ăn',     numeric: true },
    { key: 'phone_allowance',               label: 'PC ĐT',     numeric: true },
    { key: 'travel_allowance',              label: 'PC Xăng',   numeric: true },
    { key: 'performance_kpi_amount',        label: 'KPI',       numeric: true },
    { key: 'other_allowance',               label: 'Khác',      numeric: true },
    { key: 'company_social_insurance',      label: 'BHXH DN',  numeric: true },
    { key: 'company_health_insurance',      label: 'BHYT DN',  numeric: true },
    { key: 'company_unemployment_insurance',label: 'BHTN DN',  numeric: true },
    { key: 'total_company_insurance',       label: 'ΣDN',      numeric: true },
    { key: 'employee_social_insurance',     label: 'BHXH NV',  numeric: true },
    { key: 'employee_health_insurance',     label: 'BHYT NV',  numeric: true },
    { key: 'employee_unemployment_insurance',label: 'BHTN NV', numeric: true },
    { key: 'total_employee_insurance',      label: 'ΣNV',      numeric: true },
    { key: 'taxable_income',               label: 'TN Thuế',  numeric: true },
    { key: 'family_deduction',             label: 'Giảm trừ', numeric: true },
    { key: 'personal_income_tax',          label: 'Thuế TNCN',numeric: true },
    { key: 'gross_income',                 label: 'Tổng TN',  numeric: true },
    { key: 'total_deductions',             label: 'Tổng KT',  numeric: true },
    { key: 'net_salary',                   label: 'Thực lĩnh',numeric: true },
];

const allItems = computed(() => props.grouped.flatMap(g => g.items));

function subtotal(items, key) { return items.reduce((s, i) => s + (Number(i[key]) || 0), 0); }
function grandTotal(key)       { return subtotal(allItems.value, key); }
function compact(v)            { return v ? new Intl.NumberFormat('vi-VN').format(Math.round(v)) : '—'; }
function pct(v)                { return v ? (Number(v) * 100).toFixed(1) + '%' : '—'; }
</script>
