<template>
    <AppLayout :title="`Bảng chấm công — ${period.code}`">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs text-gray-400 mb-1">Chấm công / {{ period.code }}</p>
                    <h1 class="text-xl font-bold text-gray-900 uppercase tracking-wide">
                        BẢNG CHẤM CÔNG — THÁNG {{ period.month }}/{{ period.year }}
                    </h1>
                    <p class="text-xs text-gray-500 mt-0.5">{{ period.created_by }} lập ngày {{ period.created_at }}</p>
                    <span :class="`inline-flex mt-1 px-2 py-0.5 rounded-full text-xs font-medium bg-${period.status_color}-100 text-${period.status_color}-700`">
                        {{ period.status_label }}
                    </span>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <a :href="route('hr.attendance.export', period.id)"
                        class="px-3 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50">
                        Xuất Excel
                    </a>
                    <button @click="printPage"
                        class="px-3 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50">
                        In bảng
                    </button>
                    <template v-if="period.status === 'locked'">
                        <button @click="showUnlock = true"
                            class="px-3 py-2 text-sm bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                            Mở lại
                        </button>
                    </template>
                    <template v-else>
                        <button @click="confirmLock"
                            class="px-3 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                            Khóa bảng
                        </button>
                    </template>
                </div>
            </div>

            <!-- Symbol legend -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3">
                <p class="text-xs font-semibold text-blue-700 mb-2">Ký hiệu chấm công</p>
                <div class="flex flex-wrap gap-2">
                    <span v-for="sym in symbolList" :key="sym.code"
                        :class="`inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-semibold bg-${sym.color}-100 text-${sym.color}-700 border border-${sym.color}-200`">
                        {{ sym.display }} <span class="font-normal text-gray-500">{{ sym.label }}</span>
                    </span>
                </div>
            </div>

            <!-- Grid -->
            <AttendanceGrid
                :employees="employees"
                :days="days"
                :grid="localGrid"
                :summaries="localSummaries"
                :symbol-map="symbols"
                :locked="period.status === 'locked'"
                @open-editor="openEditor"
            />

            <!-- Cell editor popover -->
            <Teleport to="body">
                <div v-if="editorOpen" class="fixed inset-0 z-40" @click.self="editorOpen = false" />
                <AttendanceCellEditor
                    v-if="editorOpen"
                    :cell="{ ...editorCell, periodId: period.id }"
                    :symbols="symbolList"
                    :pos="editorPos"
                    @close="editorOpen = false"
                    @saved="onCellSaved"
                />
            </Teleport>

            <!-- Unlock modal -->
            <div v-if="showUnlock" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl p-6 w-96 shadow-xl">
                    <h2 class="text-lg font-bold text-gray-800 mb-1">Mở lại bảng chấm công</h2>
                    <p class="text-sm text-gray-500 mb-4">Vui lòng nhập lý do mở lại.</p>
                    <textarea v-model="unlockReason" rows="3" placeholder="Lý do mở lại..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none mb-4" />
                    <div class="flex justify-end gap-3">
                        <button @click="showUnlock = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
                        <button @click="submitUnlock" :disabled="!unlockReason.trim()"
                            class="px-4 py-2 text-sm bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 disabled:opacity-50">Mở lại</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import AttendanceGrid from './AttendanceGrid.vue';
import AttendanceCellEditor from './AttendanceCellEditor.vue';

const props = defineProps({
    period:    Object,
    employees: Array,
    days:      Array,
    grid:      Object,
    summaries: Object,
    symbols:   Object,
});

// Mutable local copies so grid updates reactively without page reload
const localGrid      = reactive({ ...props.grid });
const localSummaries = reactive({ ...props.summaries });

const symbolList = computed(() => Object.values(props.symbols));

// Editor state
const editorOpen = ref(false);
const editorCell = ref({});
const editorPos  = ref({ x: 0, y: 0 });

function openEditor(cell, pos) {
    editorCell.value = cell;
    editorPos.value  = pos;
    editorOpen.value = true;
}

function onCellSaved(data) {
    const { employeeId, date, symbol, overtime_hours, display } = data;
    if (!localGrid[employeeId]) localGrid[employeeId] = {};
    localGrid[employeeId][date] = {
        ...(localGrid[employeeId][date] ?? {}),
        symbol, display, overtime_hours,
        record_id: data.recordId,
    };
    recomputeSummary(employeeId);
}

function recomputeSummary(empId) {
    const empGrid   = localGrid[empId] ?? {};
    let cong = 0, nghi_hl = 0, nghi_kl = 0, ot_hours = 0;
    for (const cell of Object.values(empGrid)) {
        if (!cell.symbol) continue;
        const sym = props.symbols[cell.symbol];
        if (!sym) continue;
        if (sym.is_overtime)     ot_hours += cell.overtime_hours ?? 0;
        else if (sym.is_unpaid_leave) nghi_kl += 1;
        else if (sym.paid_workday > 0) {
            if (cell.symbol === 'X' || cell.symbol === 'CT') cong += sym.paid_workday;
            else nghi_hl += sym.paid_workday;
        }
    }
    localSummaries[empId] = { cong, nghi_hl, nghi_kl, ot_hours, total: cong + nghi_hl };
}

function printPage() { window.print(); }

// Lock
function confirmLock() {
    if (confirm('Khóa bảng chấm công? Sau khi khóa sẽ không thể sửa.')) {
        router.post(route('hr.attendance.lock', props.period.id), {}, { preserveScroll: true });
    }
}

// Unlock
const showUnlock   = ref(false);
const unlockReason = ref('');
function submitUnlock() {
    router.post(route('hr.attendance.unlock', props.period.id), { reason: unlockReason.value }, {
        preserveScroll: true,
        onSuccess: () => { showUnlock.value = false; unlockReason.value = ''; },
    });
}
</script>

<style>
@media print {
    nav, aside, header, .no-print, button, a[href] { display: none !important; }
    .bg-white { background: white !important; }
    body { font-size: 10px; }
}
</style>
