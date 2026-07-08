<template>
    <AppLayout :title="`Đơn labo ${order.code}`">
        <div class="max-w-4xl space-y-4">
            <!-- Header -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ order.code }}</span>
                            <StatusBadge :color="order.status_color">{{ order.status_label }}</StatusBadge>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">{{ order.lab }}</h2>
                        <p class="text-sm text-gray-500 mt-0.5">
                            Bệnh nhân: <strong>{{ order.patient }}</strong>
                            <span v-if="order.branch"> · {{ order.branch }}</span>
                        </p>
                    </div>

                    <div class="flex flex-col items-end gap-2">
                        <!-- Status transitions -->
                        <div v-if="can('labo.manage') && transitions.length > 0" class="flex gap-1">
                            <button v-for="t in transitions" :key="t.value" @click="doTransition(t.value)"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg border border-primary-300 text-primary-700 hover:bg-primary-50">
                                → {{ t.label }}
                            </button>
                        </div>
                        <Link v-if="can('labo.manage') && order.status === 'draft'" :href="route('lab.orders.edit', order.id)"
                            class="text-sm text-gray-500 hover:text-gray-700">Sửa</Link>
                    </div>
                </div>

                <!-- Key info grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 text-sm">
                    <div class="p-2 bg-gray-50 rounded">
                        <p class="text-xs text-gray-400">Ngày tạo</p>
                        <p class="font-medium text-gray-700">{{ order.created_at }}</p>
                    </div>
                    <div v-if="order.expected_date" class="p-2 bg-gray-50 rounded">
                        <p class="text-xs text-gray-400">Ngày nhận dự kiến</p>
                        <p class="font-medium text-gray-700">{{ order.expected_date }}</p>
                    </div>
                    <div v-if="order.sent_date" class="p-2 bg-blue-50 rounded">
                        <p class="text-xs text-blue-400">Đã gửi</p>
                        <p class="font-medium text-blue-700">{{ order.sent_date }}</p>
                    </div>
                    <div v-if="order.received_date" class="p-2 bg-green-50 rounded">
                        <p class="text-xs text-green-400">Đã nhận</p>
                        <p class="font-medium text-green-700">{{ order.received_date }}</p>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Chi tiết đơn hàng</h3>
                    <span class="text-xs text-gray-400">{{ order.items.length }} dòng</span>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Dịch vụ</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500">SL</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Đơn giá</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="order.items.length === 0">
                            <td colspan="4" class="px-4 py-6 text-center text-gray-400 text-xs">Không có chi tiết</td>
                        </tr>
                        <tr v-for="(item, idx) in order.items" :key="idx" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-700">{{ item.service_name }}</td>
                            <td class="px-4 py-2 text-center text-gray-600">{{ item.quantity }}</td>
                            <td class="px-4 py-2 text-right font-mono text-gray-600">{{ formatVnd(item.unit_price) }}</td>
                            <td class="px-4 py-2 text-right font-mono font-medium text-gray-700">{{ formatVnd(item.quantity * item.unit_price) }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="order.items.length > 0" class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-right font-semibold text-gray-600 text-sm">Tổng cộng:</td>
                            <td class="px-4 py-2 text-right font-bold font-mono text-gray-800">{{ formatVnd(order.total_amount) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Notes -->
            <div v-if="order.notes" class="bg-white rounded-xl border border-gray-200 p-4 text-sm text-gray-600">
                <p class="text-xs font-semibold text-gray-400 mb-1">Ghi chú</p>
                {{ order.notes }}
            </div>

            <!-- Warranties -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Thẻ bảo hành</h3>
                    <button v-if="can('labo.manage') && order.status === 'completed'" @click="showWarrantyForm = !showWarrantyForm"
                        class="text-xs text-primary-600 hover:text-primary-700">
                        {{ showWarrantyForm ? 'Đóng' : '+ Thêm bảo hành' }}
                    </button>
                </div>

                <!-- Add warranty form -->
                <div v-if="showWarrantyForm" class="p-4 bg-indigo-50 border-b border-indigo-100">
                    <form @submit.prevent="addWarranty" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Tên dịch vụ *</label>
                            <input v-model="warrantyForm.service_name" type="text" required class="input-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Số răng</label>
                            <input v-model="warrantyForm.tooth_number" type="text" class="input-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Ghi chú</label>
                            <input v-model="warrantyForm.notes" type="text" class="input-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Ngày bắt đầu *</label>
                            <input v-model="warrantyForm.start_date" type="date" required class="input-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Ngày kết thúc *</label>
                            <input v-model="warrantyForm.end_date" type="date" required class="input-sm" />
                        </div>
                        <div class="flex items-end">
                            <button type="submit" :disabled="warrantyForm.processing"
                                class="px-3 py-1.5 text-xs text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                                Tạo bảo hành
                            </button>
                        </div>
                    </form>
                </div>

                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Dịch vụ</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Số răng</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Từ ngày</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Đến ngày</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Trạng thái</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="order.warranties.length === 0">
                            <td colspan="6" class="px-4 py-6 text-center text-gray-400 text-xs">Chưa có thẻ bảo hành</td>
                        </tr>
                        <tr v-for="w in order.warranties" :key="w.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-700">{{ w.service_name }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ w.tooth_number ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-500 text-xs">{{ w.start_date }}</td>
                            <td class="px-4 py-2 text-gray-500 text-xs">{{ w.end_date }}</td>
                            <td class="px-4 py-2">
                                <StatusBadge :color="w.status_color">{{ w.status_label }}</StatusBadge>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <button v-if="can('labo.manage') && w.status === 'active'" @click="claimWarranty(w.id)"
                                    class="text-xs text-orange-500 hover:text-orange-700">Ghi nhận BH</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ order: Object, statuses: Array, transitions: Array });

const showWarrantyForm = ref(false);

const warrantyForm = useForm({
    service_name: '',
    tooth_number: '',
    start_date:   '',
    end_date:     '',
    notes:        '',
});

function doTransition(status) {
    router.post(route('lab.orders.transition', props.order.id), { status });
}

function addWarranty() {
    warrantyForm.post(route('lab.warranties.store', props.order.id), {
        onSuccess: () => {
            showWarrantyForm.value = false;
            warrantyForm.reset();
        },
    });
}

function claimWarranty(warrantyId) {
    if (confirm('Xác nhận ghi nhận bảo hành?')) {
        router.post(route('lab.warranties.claim', warrantyId));
    }
}

function formatVnd(value) {
    return new Intl.NumberFormat('vi-VN').format(value || 0) + ' ₫';
}
</script>

<style scoped>
.input-sm {
    @apply block w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:ring-1 focus:ring-primary-500 focus:outline-none;
}
</style>
