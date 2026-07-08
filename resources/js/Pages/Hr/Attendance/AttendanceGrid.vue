<template>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="text-xs border-collapse min-w-max">
                <!-- Header row 1: fixed labels + day numbers -->
                <thead>
                    <tr class="bg-[#1E3A5F] text-white">
                        <th class="sticky left-0 z-20 bg-[#1E3A5F] px-2 py-2 text-center w-8 border-r border-blue-900">STT</th>
                        <th class="sticky left-8 z-20 bg-[#1E3A5F] px-2 py-2 text-left w-20 border-r border-blue-900">Mã NV</th>
                        <th class="sticky left-28 z-20 bg-[#1E3A5F] px-2 py-2 text-left w-40 border-r border-blue-900">Họ và tên</th>
                        <th class="sticky left-68 z-20 bg-[#1E3A5F] px-2 py-2 text-left w-24 border-r border-blue-900">Chức vụ</th>
                        <th v-for="d in days" :key="d.day"
                            :class="['px-1 py-2 text-center w-8 border-r border-blue-900 font-semibold', d.is_sun ? 'bg-red-800' : '']">
                            {{ d.day }}
                        </th>
                        <th class="px-2 py-2 text-center w-10 bg-[#2D5F3F] border-r border-green-900">Công</th>
                        <th class="px-2 py-2 text-center w-12 bg-[#2D5F3F] border-r border-green-900">NghỉHL</th>
                        <th class="px-2 py-2 text-center w-12 bg-[#2D5F3F] border-r border-green-900">NghỉKL</th>
                        <th class="px-2 py-2 text-center w-10 bg-[#2D5F3F] border-r border-green-900">OT(h)</th>
                        <th class="px-2 py-2 text-center w-12 bg-[#2D5F3F]">Tổng</th>
                    </tr>
                    <!-- Header row 2: weekday labels -->
                    <tr class="bg-gray-700 text-gray-200">
                        <th class="sticky left-0 z-20 bg-gray-700 border-r border-gray-600" />
                        <th class="sticky left-8 z-20 bg-gray-700 border-r border-gray-600" />
                        <th class="sticky left-28 z-20 bg-gray-700 border-r border-gray-600" />
                        <th class="sticky left-68 z-20 bg-gray-700 border-r border-gray-600" />
                        <th v-for="d in days" :key="d.day"
                            :class="['px-1 py-1 text-center border-r border-gray-600 font-normal text-gray-300', d.is_sun ? 'bg-red-900 text-red-200' : d.is_sat ? 'bg-gray-600' : '']">
                            {{ d.weekday }}
                        </th>
                        <th colspan="5" class="bg-gray-700 border-l border-gray-600" />
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(emp, idx) in employees" :key="emp.id"
                        :class="['border-b border-gray-100', idx % 2 === 1 ? 'bg-gray-50' : 'bg-white']">
                        <!-- Fixed left columns -->
                        <td class="sticky left-0 z-10 px-2 py-1.5 text-center text-gray-400 border-r border-gray-200"
                            :class="idx % 2 === 1 ? 'bg-gray-50' : 'bg-white'">{{ idx + 1 }}</td>
                        <td class="sticky left-8 z-10 px-2 py-1.5 font-mono text-gray-500 border-r border-gray-200"
                            :class="idx % 2 === 1 ? 'bg-gray-50' : 'bg-white'">{{ emp.code }}</td>
                        <td class="sticky left-28 z-10 px-2 py-1.5 font-medium text-gray-800 whitespace-nowrap border-r border-gray-200"
                            :class="idx % 2 === 1 ? 'bg-gray-50' : 'bg-white'">{{ emp.full_name }}</td>
                        <td class="sticky left-68 z-10 px-2 py-1.5 text-gray-500 whitespace-nowrap border-r border-gray-200"
                            :class="idx % 2 === 1 ? 'bg-gray-50' : 'bg-white'">{{ emp.position }}</td>

                        <!-- Day cells -->
                        <td v-for="d in days" :key="d.day"
                            :class="['border-r border-gray-100 text-center relative', d.is_sun ? 'bg-red-50' : '', !locked ? 'cursor-pointer hover:bg-primary-50' : '']"
                            @click="!locked && openEditor(emp, d, $event)">
                            <CellBadge :cell="getCell(emp.id, d.date)" :symbols="symbolMap" />
                        </td>

                        <!-- Summary columns -->
                        <td class="px-2 py-1.5 text-center font-semibold text-green-700 border-r border-gray-100 bg-green-50">
                            {{ getSummary(emp.id, 'cong') }}
                        </td>
                        <td class="px-2 py-1.5 text-center text-blue-600 border-r border-gray-100 bg-blue-50">
                            {{ getSummary(emp.id, 'nghi_hl') }}
                        </td>
                        <td class="px-2 py-1.5 text-center text-red-600 border-r border-gray-100 bg-red-50">
                            {{ getSummary(emp.id, 'nghi_kl') }}
                        </td>
                        <td class="px-2 py-1.5 text-center text-orange-600 border-r border-gray-100 bg-orange-50">
                            {{ getSummary(emp.id, 'ot_hours') }}
                        </td>
                        <td class="px-2 py-1.5 text-center font-bold text-gray-800 bg-yellow-50">
                            {{ getSummary(emp.id, 'total') }}
                        </td>
                    </tr>

                    <!-- Total row -->
                    <tr class="bg-yellow-50 font-bold border-t-2 border-yellow-300">
                        <td colspan="4" class="sticky left-0 z-10 bg-yellow-50 px-2 py-2 text-center text-gray-700 border-r border-yellow-200">TỔNG CỘNG</td>
                        <td v-for="d in days" :key="d.day" class="border-r border-yellow-100" />
                        <td class="px-2 py-2 text-center text-green-700 border-r border-gray-100">{{ grandTotal('cong') }}</td>
                        <td class="px-2 py-2 text-center text-blue-600 border-r border-gray-100">{{ grandTotal('nghi_hl') }}</td>
                        <td class="px-2 py-2 text-center text-red-600 border-r border-gray-100">{{ grandTotal('nghi_kl') }}</td>
                        <td class="px-2 py-2 text-center text-orange-600 border-r border-gray-100">{{ grandTotal('ot_hours') }}</td>
                        <td class="px-2 py-2 text-center text-gray-800">{{ grandTotal('total') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { defineComponent, h, computed } from 'vue';

const props = defineProps({
    employees: Array,
    days:      Array,
    grid:      Object,
    summaries: Object,
    symbolMap: Object,
    locked:    Boolean,
});

const emit = defineEmits(['open-editor']);

// Inline CellBadge to avoid extra file
const CellBadge = defineComponent({
    props: { cell: Object, symbols: Object },
    setup(p) {
        return () => {
            if (!p.cell?.symbol) return h('span', { class: 'block w-8 h-6' });
            const sym = p.symbols[p.cell.symbol];
            const color = sym?.color ?? 'gray';
            const display = p.cell.display || p.cell.symbol;
            const hasOt = p.cell.overtime_hours > 0 && p.cell.symbol === 'X';
            return h('span', {
                class: `inline-flex items-center justify-center rounded px-1 py-0.5 text-xs font-bold bg-${color}-100 text-${color}-700`,
                title: `${sym?.label ?? ''} ${hasOt ? '| OT: ' + p.cell.overtime_hours + 'h' : ''} ${p.cell.note ? '| ' + p.cell.note : ''}`,
            }, hasOt ? display + '+OT' : display);
        };
    },
});

function getCell(empId, date) {
    return props.grid[empId]?.[date] ?? null;
}

function getSummary(empId, key) {
    return props.summaries[empId]?.[key] ?? 0;
}

function grandTotal(key) {
    return Object.values(props.summaries).reduce((s, v) => s + (v[key] ?? 0), 0).toFixed(1).replace('.0', '');
}

function openEditor(emp, day, event) {
    const rect = event.currentTarget.getBoundingClientRect();
    const cell = getCell(emp.id, day.date);
    emit('open-editor', {
        employeeId:     emp.id,
        employee:       emp.full_name,
        date:           day.date,
        dateLabel:      `${day.day}/${String(day.date).slice(5, 7)}`,
        symbol:         cell?.symbol ?? null,
        overtime_hours: cell?.overtime_hours ?? 0,
        note:           cell?.note ?? '',
        recordId:       cell?.record_id ?? null,
    }, { x: Math.min(rect.left, window.innerWidth - 300), y: rect.bottom + 4 });
}
</script>
