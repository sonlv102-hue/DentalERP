<template>
    <AppLayout :title="order ? `Sửa đơn ${order.code}` : 'Tạo đơn đặt xưởng'">
        <div class="max-w-3xl mx-auto bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-5">
                {{ order ? `Sửa đơn ${order.code}` : 'Tạo đơn đặt xưởng mới' }}
            </h2>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Lab select -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Labo <span class="text-red-500">*</span></label>
                        <select v-model="form.lab_id" required class="input-field" @change="onLabChange">
                            <option :value="null">-- Chọn labo --</option>
                            <option v-for="l in labs" :key="l.id" :value="l.id">{{ l.name }}</option>
                        </select>
                        <p v-if="form.errors.lab_id" class="text-xs text-red-500 mt-1">{{ form.errors.lab_id }}</p>
                    </div>

                    <!-- Branch -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chi nhánh</label>
                        <select v-model="form.branch_id" class="input-field">
                            <option :value="null">-- Chọn chi nhánh --</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>

                    <!-- Patient ID (manual input) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mã bệnh nhân (ID) <span class="text-red-500">*</span></label>
                        <input v-model.number="form.patient_id" type="number" min="1" required class="input-field" placeholder="Nhập ID bệnh nhân" />
                        <p v-if="form.errors.patient_id" class="text-xs text-red-500 mt-1">{{ form.errors.patient_id }}</p>
                    </div>

                    <!-- Expected date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ngày nhận dự kiến</label>
                        <input v-model="form.expected_date" type="date" class="input-field" />
                    </div>
                </div>

                <!-- Items table -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-gray-700">Chi tiết đơn hàng</label>
                        <button type="button" @click="addItem" class="text-xs text-primary-600 hover:text-primary-700">+ Thêm dòng</button>
                    </div>

                    <!-- Quick-add from lab price list -->
                    <div v-if="selectedLabPriceItems.length > 0" class="mb-2 flex flex-wrap gap-1">
                        <button v-for="pi in selectedLabPriceItems" :key="pi.service_name" type="button"
                            @click="addFromPriceItem(pi)"
                            class="text-xs bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-1 rounded hover:bg-indigo-100">
                            + {{ pi.service_name }}
                        </button>
                    </div>

                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Tên dịch vụ</th>
                                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 w-20">SL</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 w-32">Đơn giá</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 w-32">Thành tiền</th>
                                    <th class="px-3 py-2 w-8"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="form.items.length === 0">
                                    <td colspan="5" class="px-3 py-4 text-center text-gray-400 text-xs">Chưa có dịch vụ</td>
                                </tr>
                                <tr v-for="(item, idx) in form.items" :key="idx">
                                    <td class="px-3 py-1.5">
                                        <input v-model="item.service_name" type="text" required
                                            class="w-full border border-gray-200 rounded px-2 py-1 text-sm focus:ring-1 focus:ring-primary-400 focus:outline-none" />
                                    </td>
                                    <td class="px-3 py-1.5">
                                        <input v-model.number="item.quantity" type="number" min="1" required
                                            class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-center focus:ring-1 focus:ring-primary-400 focus:outline-none" />
                                    </td>
                                    <td class="px-3 py-1.5">
                                        <input v-model.number="item.unit_price" type="number" min="0" required
                                            class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right focus:ring-1 focus:ring-primary-400 focus:outline-none" />
                                    </td>
                                    <td class="px-3 py-1.5 text-right font-mono text-gray-600 text-xs">
                                        {{ formatVnd((item.quantity || 0) * (item.unit_price || 0)) }}
                                    </td>
                                    <td class="px-3 py-1.5 text-center">
                                        <button type="button" @click="removeItem(idx)" class="text-red-400 hover:text-red-600 text-xs">✕</button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="form.items.length > 0" class="bg-gray-50 border-t border-gray-200">
                                <tr>
                                    <td colspan="3" class="px-3 py-2 text-right text-sm font-semibold text-gray-600">Tổng cộng:</td>
                                    <td class="px-3 py-2 text-right font-mono font-bold text-gray-800">{{ formatVnd(totalAmount) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                    <textarea v-model="form.notes" rows="2" class="input-field" />
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <Link :href="route('lab.orders.index')" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                        {{ order ? 'Cập nhật' : 'Tạo đơn' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({ order: Object, labs: Array, branches: Array });

const form = useForm({
    lab_id:            props.order?.lab_id ?? null,
    patient_id:        props.order?.patient_id ?? null,
    branch_id:         props.order?.branch_id ?? null,
    treatment_plan_id: props.order?.treatment_plan_id ?? null,
    items:             props.order?.items ?? [],
    notes:             props.order?.notes ?? '',
    expected_date:     props.order?.expected_date ?? '',
    total_amount:      props.order?.total_amount ?? 0,
});

const selectedLabPriceItems = computed(() => {
    if (!form.lab_id) return [];
    return props.labs.find(l => l.id === form.lab_id)?.price_items ?? [];
});

const totalAmount = computed(() =>
    form.items.reduce((sum, i) => sum + ((i.quantity || 0) * (i.unit_price || 0)), 0)
);

function onLabChange() {
    form.items = [];
}

function addItem() {
    form.items.push({ service_name: '', quantity: 1, unit_price: 0 });
}

function addFromPriceItem(pi) {
    form.items.push({ service_name: pi.service_name, quantity: 1, unit_price: pi.unit_price });
}

function removeItem(idx) {
    form.items.splice(idx, 1);
}

function submit() {
    form.total_amount = totalAmount.value;
    if (props.order) {
        form.put(route('lab.orders.update', props.order.id));
    } else {
        form.post(route('lab.orders.store'));
    }
}

function formatVnd(value) {
    return new Intl.NumberFormat('vi-VN').format(value || 0) + ' ₫';
}
</script>

<style scoped>
.input-field {
    @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none;
}
</style>
