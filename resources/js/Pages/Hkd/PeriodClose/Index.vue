<template>
    <AppLayout title="Chốt kỳ HKD">
        <div class="max-w-3xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Chốt kỳ kế toán — {{ profile.full_name }}</h1>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                <input v-model="curPeriod" type="month" @change="navigate" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>

            <!-- Current period status card -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="font-semibold text-gray-800 mb-1">Kỳ {{ curPeriod }}</h2>
                        <div v-if="periodClose" class="space-y-1 text-sm">
                            <p v-if="periodClose.status === 'closed'" class="text-gray-600">
                                🔒 Chốt lúc <strong>{{ periodClose.closed_at }}</strong> bởi <strong>{{ periodClose.closed_by_name }}</strong>
                            </p>
                            <p v-if="periodClose.unlock_reason" class="text-amber-600 text-xs">
                                Mở lại: {{ periodClose.unlock_reason }} ({{ periodClose.unlocked_at }})
                            </p>
                        </div>
                        <p v-else class="text-sm text-gray-400">Kỳ chưa được chốt.</p>
                    </div>
                    <div class="flex gap-2">
                        <button
                            v-if="!isClosed && can('hkd.manage')"
                            @click="closePeriod"
                            class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700"
                        >🔒 Chốt kỳ</button>
                        <button
                            v-if="isClosed && can('hkd.manage')"
                            @click="showUnlockForm = true"
                            class="px-4 py-2 text-sm text-amber-700 bg-amber-100 rounded-lg hover:bg-amber-200"
                        >🔓 Mở lại</button>
                    </div>
                </div>

                <div v-if="isClosed" class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-500 mb-3">Dữ liệu snapshot khi chốt:</p>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-400">Doanh thu</p>
                            <p class="font-semibold text-gray-800">{{ fmtVnd(periodClose.snapshot_data?.revenue?.total_amount) }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-400">Chi phí</p>
                            <p class="font-semibold text-gray-800">{{ fmtVnd(periodClose.snapshot_data?.expenses?.total_amount) }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-400">Lợi nhuận</p>
                            <p class="font-semibold text-gray-800">{{ fmtVnd((periodClose.snapshot_data?.revenue?.total_amount || 0) - (periodClose.snapshot_data?.expenses?.total_amount || 0)) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History of all closed periods -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Lịch sử chốt kỳ</h3>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Kỳ</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Thời gian chốt</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Người chốt</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="history.length === 0"><td colspan="4" class="px-4 py-6 text-center text-gray-400 text-sm">Chưa có kỳ nào được chốt</td></tr>
                        <tr v-for="h in history" :key="h.id" class="hover:bg-gray-50 cursor-pointer" @click="curPeriod = h.period; navigate()">
                            <td class="px-4 py-2 font-medium text-gray-700">{{ h.period }}</td>
                            <td class="px-4 py-2 text-gray-600 text-xs">{{ h.closed_at }}</td>
                            <td class="px-4 py-2 text-gray-600 text-xs">{{ h.closed_by_name }}</td>
                            <td class="px-4 py-2 text-center">
                                <span :class="h.status === 'closed' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'" class="text-xs px-2 py-0.5 rounded">
                                    {{ h.status === 'closed' ? '🔒 Đã chốt' : '🔓 Đã mở' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showUnlockForm" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Mở lại kỳ {{ curPeriod }}</h3>
                        <button @click="showUnlockForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="unlockPeriod" class="p-5 space-y-3">
                        <div>
                            <label class="label">Lý do mở lại <span class="text-red-500">*</span></label>
                            <textarea v-model="unlockReason" required rows="3" class="input-field" placeholder="Nhập lý do mở lại kỳ kế toán..."></textarea>
                        </div>
                        <p class="text-xs text-amber-600">⚠️ Mở lại kỳ sẽ cho phép chỉnh sửa dữ liệu kế toán. Hành động này được ghi lại trong audit log.</p>
                        <div class="flex justify-end gap-3 pt-1">
                            <button type="button" @click="showUnlockForm = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button type="submit" :disabled="!unlockReason.trim()" class="px-4 py-2 text-sm text-white bg-amber-600 rounded-lg hover:bg-amber-700 disabled:opacity-50">Xác nhận mở lại</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ profile: Object, periodClose: Object, history: Array, period: String });

const curPeriod     = ref(props.period);
const showUnlockForm = ref(false);
const unlockReason  = ref('');

const isClosed = computed(() => props.periodClose?.status === 'closed');

function navigate() { router.get(route('hkd.periods.index'), { period: curPeriod.value }, { preserveState: true }); }

function closePeriod() {
    if (!confirm(`Chốt kỳ ${curPeriod.value}? Sau khi chốt, dữ liệu sẽ bị khóa.`)) return;
    router.post(route('hkd.periods.close'), { period: curPeriod.value });
}

function unlockPeriod() {
    router.post(route('hkd.periods.unlock'), { period: curPeriod.value, reason: unlockReason.value }, {
        onSuccess: () => { showUnlockForm.value = false; unlockReason.value = ''; }
    });
}

function fmtVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
</script>

<style scoped>
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
.label { @apply block text-sm font-medium text-gray-700 mb-1; }
</style>
