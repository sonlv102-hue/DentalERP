<template>
    <AppLayout :title="`Bảng lương — ${payroll.code}`">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs text-gray-400 mb-1">
                        <Link :href="route('accounting.payrolls.index')" class="hover:underline">← Danh sách bảng lương</Link>
                    </p>
                    <h1 class="text-xl font-bold text-gray-900 uppercase tracking-wide">BẢNG TÍNH - THANH TOÁN TIỀN LƯƠNG</h1>
                    <p class="text-sm text-gray-600 mt-0.5 font-medium">{{ payroll.period_label }} · {{ payroll.code }}</p>
                    <span :class="`inline-flex mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-${payroll.status_color}-100 text-${payroll.status_color}-700 uppercase`">
                        {{ payroll.status_label }}
                    </span>
                </div>
                <div class="flex gap-2 flex-wrap no-print">
                    <a :href="route('accounting.payrolls.export', payroll.id)"
                        class="px-3 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50">Xuất Excel</a>
                    <button @click="printPage" class="px-3 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50">In bảng lương</button>
                    <template v-if="payroll.can_unconfirm">
                        <button @click="post('unconfirm')" class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Hủy xác nhận</button>
                    </template>
                    <template v-if="payroll.can_confirm">
                        <button @click="post('confirm')" class="px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">Đã xác nhận</button>
                    </template>
                    <template v-if="payroll.can_lock">
                        <button @click="post('lock')" class="px-3 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700">Khóa bảng lương</button>
                    </template>
                    <template v-if="payroll.can_unlock">
                        <button @click="showUnlock = true" class="px-3 py-2 text-sm bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">Mở khóa</button>
                    </template>
                </div>
            </div>

            <!-- Summary cards -->
            <PayrollSummaryCards :payroll="localPayroll" />

            <!-- KPCĐ warning -->
            <div :class="['rounded-xl border px-4 py-3 text-sm', localPayroll.union_fee_confirmed ? 'bg-green-50 border-green-200 text-green-800' : 'bg-yellow-50 border-yellow-300 text-yellow-800']">
                <p class="font-semibold">Kinh phí công đoàn (KPCĐ) doanh nghiệp phải nộp</p>
                <p class="text-xs mt-0.5">
                    Hệ thống tính được: <strong>{{ vnd(localPayroll.total_union_fee) }}</strong>
                    · <span v-if="!localPayroll.union_fee_confirmed">Chưa xác nhận — sẽ không hạch toán vào bút toán lương</span>
                    <span v-else>KPCĐ đã xác nhận</span>
                </p>
            </div>

            <!-- Grid -->
            <PayrollGrid
                :grouped="localGrouped"
                :can-edit="payroll.can_edit"
                @edit="openEditor"
            />

            <!-- Print signature block is rendered inside PayrollGrid -->

            <!-- Editor -->
            <Teleport to="body">
                <PayrollItemEditor
                    v-if="editorOpen"
                    :item="editorItem"
                    :payroll-id="payroll.id"
                    @close="editorOpen = false"
                    @saved="onSaved"
                />
            </Teleport>

            <!-- Unlock modal -->
            <div v-if="showUnlock" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl p-6 w-96 shadow-xl">
                    <h2 class="text-lg font-bold text-gray-800 mb-1">Mở khóa bảng lương</h2>
                    <p class="text-sm text-gray-500 mb-3">Vui lòng nhập lý do mở khóa (bắt buộc).</p>
                    <textarea v-model="unlockReason" rows="3" placeholder="Lý do mở khóa..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none mb-4" />
                    <div class="flex justify-end gap-3">
                        <button @click="showUnlock = false" class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Hủy</button>
                        <button @click="submitUnlock" :disabled="!unlockReason.trim()"
                            class="px-4 py-2 text-sm bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 disabled:opacity-50">Mở khóa</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import PayrollSummaryCards from './PayrollSummaryCards.vue';
import PayrollGrid from './PayrollGrid.vue';
import PayrollItemEditor from './PayrollItemEditor.vue';

const props = defineProps({ payroll: Object, grouped: Array });

const localPayroll = reactive({ ...props.payroll });
const localGrouped = reactive(props.grouped.map(g => ({
    ...g,
    items: g.items.map(i => ({ ...i })),
})));

const editorOpen  = ref(false);
const editorItem  = ref(null);
const showUnlock  = ref(false);
const unlockReason = ref('');

function vnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
function printPage() { window.print(); }

function openEditor(item) {
    editorItem.value = item;
    editorOpen.value = true;
}

function onSaved({ item, totals }) {
    editorOpen.value = false;
    // Update the item in localGrouped
    for (const group of localGrouped) {
        const idx = group.items.findIndex(i => i.id === item.id);
        if (idx !== -1) {
            Object.assign(group.items[idx], item);
            break;
        }
    }
    // Update payroll totals
    if (totals) Object.assign(localPayroll, totals);
}

function post(action) {
    const messages = { confirm: 'Xác nhận bảng lương?', unconfirm: 'Hủy xác nhận?', lock: 'Khóa bảng lương? Sau khi khóa không thể sửa.' };
    if (!confirm(messages[action] ?? 'Tiếp tục?')) return;
    router.post(route(`accounting.payrolls.${action}`, props.payroll.id), {}, { preserveScroll: true });
}

function submitUnlock() {
    router.post(route('accounting.payrolls.unlock', props.payroll.id), { reason: unlockReason.value }, {
        preserveScroll: true,
        onSuccess: () => { showUnlock.value = false; unlockReason.value = ''; },
    });
}
</script>

<style>
@media print {
    nav, aside, header, .no-print, button, a[href] { display: none !important; }
    body { font-size: 10px; }
    .bg-white { background: white !important; }
    .print\:flex { display: flex !important; }
}
</style>
