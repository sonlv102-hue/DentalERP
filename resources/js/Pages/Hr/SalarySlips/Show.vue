<template>
    <AppLayout :title="`Phiếu lương ${slip.period} — ${slip.employee}`">
        <div class="max-w-2xl space-y-4">
            <!-- Header -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ slip.period }}</span>
                            <StatusBadge :color="slip.status_color">{{ slip.status_label }}</StatusBadge>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">{{ slip.employee }}</h2>
                        <p class="text-sm text-gray-500 mt-0.5">{{ slip.employee_code }} <span v-if="slip.branch">· {{ slip.branch }}</span></p>
                    </div>
                    <div class="flex gap-2">
                        <button v-if="slip.status === 'draft'" @click="confirm"
                            class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Duyệt phiếu
                        </button>
                        <button v-if="slip.status === 'confirmed'" @click="markPaid"
                            class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Đã thanh toán
                        </button>
                        <button v-if="slip.status === 'draft'" @click="deleteslip"
                            class="px-3 py-1.5 text-sm border border-red-300 text-red-600 rounded-lg hover:bg-red-50">
                            Xóa
                        </button>
                    </div>
                </div>

                <!-- Summary boxes -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-4">
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-400">Lương cơ bản</p>
                        <p class="font-bold text-gray-800 mt-1">{{ formatVnd(slip.base_salary) }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ slip.work_days }} ngày công</p>
                    </div>
                    <div class="p-3 bg-orange-50 rounded-lg">
                        <p class="text-xs text-orange-400">Tăng ca (OT)</p>
                        <p class="font-bold text-orange-700 mt-1">{{ formatVnd(slip.ot_amount) }}</p>
                        <p class="text-xs text-orange-400 mt-0.5">{{ slip.ot_hours }}h × {{ formatVnd(slip.ot_rate) }}/h</p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <p class="text-xs text-green-400">Hoa hồng</p>
                        <p class="font-bold text-green-700 mt-1">{{ formatVnd(slip.commission_total) }}</p>
                    </div>
                    <div class="p-3 bg-red-50 rounded-lg">
                        <p class="text-xs text-red-400">Khấu trừ</p>
                        <p class="font-bold text-red-600 mt-1">{{ formatVnd(slip.deductions) }}</p>
                    </div>
                    <div class="p-3 bg-indigo-50 rounded-lg col-span-2">
                        <p class="text-xs text-indigo-400">Thực nhận</p>
                        <p class="font-bold text-indigo-700 mt-1 text-xl">{{ formatVnd(slip.net_salary) }}</p>
                        <p v-if="slip.paid_at" class="text-xs text-indigo-400 mt-0.5">Thanh toán: {{ slip.paid_at }}</p>
                    </div>
                </div>
            </div>

            <!-- Line items -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Chi tiết phiếu lương</h3>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Loại</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Mô tả</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Số tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(item, idx) in slip.items" :key="idx" class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <span :class="typeClass(item.type)" class="text-xs font-medium px-2 py-0.5 rounded">
                                    {{ typeLabel(item.type) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-700">{{ item.description }}</td>
                            <td class="px-4 py-2 text-right font-mono" :class="item.amount < 0 ? 'text-red-600' : 'text-gray-700'">
                                {{ formatVnd(item.amount) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td colspan="2" class="px-4 py-2 text-right font-semibold text-gray-700 text-sm">Thực nhận:</td>
                            <td class="px-4 py-2 text-right font-bold font-mono text-indigo-700">{{ formatVnd(slip.net_salary) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div v-if="slip.notes" class="bg-white rounded-xl border border-gray-200 p-4 text-sm text-gray-600">
                <p class="text-xs font-semibold text-gray-400 mb-1">Ghi chú</p>{{ slip.notes }}
            </div>

            <div class="text-xs text-gray-400 text-right">Tạo lúc {{ slip.created_at }}</div>
        </div>
    </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';

const props = defineProps({ slip: Object });

function confirm()  { router.post(route('hr.salary-slips.confirm', props.slip.id)); }
function markPaid() { router.post(route('hr.salary-slips.mark-paid', props.slip.id)); }
function deleteslip() {
    if (confirm('Xóa phiếu lương này?')) router.delete(route('hr.salary-slips.destroy', props.slip.id));
}

function typeLabel(type) {
    return { base: 'Lương CB', ot: 'Tăng ca', commission: 'Hoa hồng', deduction: 'Khấu trừ', bonus: 'Thưởng' }[type] ?? type;
}
function typeClass(type) {
    return { base: 'bg-gray-100 text-gray-600', ot: 'bg-orange-100 text-orange-700', commission: 'bg-green-100 text-green-700', deduction: 'bg-red-100 text-red-600', bonus: 'bg-yellow-100 text-yellow-700' }[type] ?? 'bg-gray-100 text-gray-600';
}
function formatVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
</script>
