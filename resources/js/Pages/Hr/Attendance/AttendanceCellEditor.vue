<template>
    <!-- Positioned popover, caller uses Teleport to body -->
    <div class="fixed z-50 bg-white border border-gray-200 rounded-xl shadow-xl p-4 w-72"
         :style="{ top: pos.y + 'px', left: pos.x + 'px' }">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold text-gray-600">
                {{ cell.employee }} · {{ cell.dateLabel }}
            </p>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-lg leading-none">×</button>
        </div>

        <!-- Symbol grid -->
        <div class="grid grid-cols-3 gap-1.5 mb-3">
            <button v-for="sym in symbols" :key="sym.code"
                @click="selectSymbol(sym.code)"
                :class="[
                    'py-1.5 rounded-lg text-xs font-bold border-2 transition-all',
                    localSymbol === sym.code
                        ? `border-${sym.color}-500 bg-${sym.color}-100 text-${sym.color}-700`
                        : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'
                ]">
                {{ sym.display }}
                <span class="block text-gray-400 font-normal" style="font-size:9px">{{ sym.label }}</span>
            </button>
            <!-- Clear -->
            <button @click="selectSymbol(null)"
                :class="['py-1.5 rounded-lg text-xs border-2 border-dashed transition-all', !localSymbol ? 'border-gray-400 bg-gray-50' : 'border-gray-200 text-gray-400 hover:border-gray-300']">
                Xóa
            </button>
        </div>

        <!-- OT hours (shown when OT or X) -->
        <div v-if="localSymbol === 'OT' || localSymbol === 'X'" class="mb-3">
            <label class="block text-xs text-gray-500 mb-1">Số giờ tăng ca (OT)</label>
            <input v-model.number="localOt" type="number" min="0" max="12" step="0.5"
                class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm" placeholder="0" />
        </div>

        <!-- Note -->
        <div class="mb-3">
            <label class="block text-xs text-gray-500 mb-1">Ghi chú</label>
            <input v-model="localNote" type="text" maxlength="200"
                class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm" placeholder="Nhập ghi chú..." />
        </div>

        <div class="flex justify-end gap-2">
            <button @click="$emit('close')" class="px-3 py-1.5 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
            <button @click="save" :disabled="saving"
                class="px-3 py-1.5 text-xs bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50">
                {{ saving ? '...' : 'Lưu' }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    cell:    { type: Object, required: true },
    symbols: { type: Array,  required: true },
    pos:     { type: Object, default: () => ({ x: 100, y: 100 }) },
});

const emit = defineEmits(['close', 'saved']);

const localSymbol = ref(props.cell.symbol ?? null);
const localOt     = ref(props.cell.overtime_hours ?? 0);
const localNote   = ref(props.cell.note ?? '');
const saving      = ref(false);

watch(() => props.cell, (c) => {
    localSymbol.value = c.symbol ?? null;
    localOt.value     = c.overtime_hours ?? 0;
    localNote.value   = c.note ?? '';
});

function selectSymbol(code) {
    localSymbol.value = code;
    if (code !== 'OT' && code !== 'X') localOt.value = 0;
}

async function save() {
    saving.value = true;
    try {
        const res = await axios.put(
            route('hr.attendance.records.update', { attendance: props.cell.periodId, record: props.cell.recordId }),
            { symbol: localSymbol.value, overtime_hours: localOt.value, note: localNote.value }
        );
        emit('saved', { ...res.data, recordId: props.cell.recordId, date: props.cell.date, employeeId: props.cell.employeeId });
        emit('close');
    } catch (e) {
        alert(e.response?.data?.message || 'Lỗi lưu công.');
    } finally {
        saving.value = false;
    }
}
</script>
