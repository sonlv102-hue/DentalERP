<template>
    <AppLayout :title="lab ? lab.name : 'Thêm labo mới'">
        <div class="max-w-4xl space-y-4">
            <!-- Lab Info Card -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">
                        {{ lab ? lab.name : 'Thêm labo mới' }}
                        <span v-if="lab" class="ml-2 font-mono text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded">{{ lab.code }}</span>
                    </h2>
                    <div v-if="lab" class="flex gap-2">
                        <Link :href="route('lab.orders.create')" class="text-sm text-primary-600 hover:underline">+ Đơn đặt xưởng</Link>
                    </div>
                </div>

                <form @submit.prevent="submitLab" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tên labo <span class="text-red-500">*</span></label>
                            <input v-model="labForm.name" type="text" required class="input-field" />
                            <p v-if="labForm.errors.name" class="text-xs text-red-500 mt-1">{{ labForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Điện thoại</label>
                            <input v-model="labForm.phone" type="text" class="input-field" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input v-model="labForm.email" type="email" class="input-field" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Người liên hệ</label>
                            <input v-model="labForm.contact_person" type="text" class="input-field" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tài khoản ngân hàng</label>
                            <input v-model="labForm.bank_account" type="text" class="input-field" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ</label>
                            <input v-model="labForm.address" type="text" class="input-field" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                            <textarea v-model="labForm.notes" rows="2" class="input-field" />
                        </div>
                        <div class="flex items-center gap-2">
                            <input v-model="labForm.is_active" type="checkbox" id="is_active" class="rounded border-gray-300" />
                            <label for="is_active" class="text-sm text-gray-700">Đang hoạt động</label>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <Link :href="route('lab.labs.index')" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Quay lại
                        </Link>
                        <div class="flex gap-2">
                            <button v-if="lab && can('labo.manage')" type="button" @click="deleteLab"
                                class="px-3 py-2 text-sm text-red-600 border border-red-300 rounded-lg hover:bg-red-50">
                                Xóa
                            </button>
                            <button v-if="can('labo.manage')" type="submit" :disabled="labForm.processing"
                                class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                                {{ lab ? 'Cập nhật' : 'Tạo labo' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Price Items Card (only for existing lab) -->
            <div v-if="lab" class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Bảng giá dịch vụ</h3>

                <table class="min-w-full text-sm mb-4">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Tên dịch vụ</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500">Đơn giá</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Ghi chú</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="priceItems.length === 0">
                            <td colspan="4" class="px-3 py-4 text-center text-gray-400 text-xs">Chưa có giá dịch vụ</td>
                        </tr>
                        <tr v-for="item in priceItems" :key="item.id">
                            <td class="px-3 py-2 text-gray-700">{{ item.service_name }}</td>
                            <td class="px-3 py-2 text-right font-mono text-gray-700">{{ formatVnd(item.unit_price) }}</td>
                            <td class="px-3 py-2 text-gray-500 text-xs">{{ item.notes ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">
                                <button v-if="can('labo.manage')" @click="deletePriceItem(item.id)"
                                    class="text-xs text-red-400 hover:text-red-600">Xóa</button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Add price item form -->
                <div v-if="can('labo.manage')" class="border-t border-gray-100 pt-3">
                    <p class="text-xs font-medium text-gray-500 mb-2">Thêm giá mới</p>
                    <form @submit.prevent="addPriceItem" class="flex flex-wrap gap-2 items-end">
                        <div>
                            <input v-model="priceForm.service_name" type="text" placeholder="Tên dịch vụ *" required
                                class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none w-48" />
                        </div>
                        <div>
                            <input v-model.number="priceForm.unit_price" type="number" min="0" placeholder="Đơn giá *" required
                                class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none w-32" />
                        </div>
                        <div>
                            <input v-model="priceForm.notes" type="text" placeholder="Ghi chú"
                                class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none w-36" />
                        </div>
                        <button type="submit" :disabled="priceForm.processing"
                            class="px-3 py-1.5 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            Thêm
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ lab: Object });

const priceItems = ref(props.lab?.price_items ?? []);

const labForm = useForm({
    name:           props.lab?.name ?? '',
    phone:          props.lab?.phone ?? '',
    email:          props.lab?.email ?? '',
    address:        props.lab?.address ?? '',
    contact_person: props.lab?.contact_person ?? '',
    bank_account:   props.lab?.bank_account ?? '',
    notes:          props.lab?.notes ?? '',
    is_active:      props.lab?.is_active ?? true,
});

const priceForm = useForm({ service_name: '', unit_price: '', notes: '' });

function submitLab() {
    if (props.lab) {
        labForm.put(route('lab.labs.update', props.lab.id));
    } else {
        labForm.post(route('lab.labs.store'));
    }
}

function deleteLab() {
    if (confirm('Xác nhận xóa labo này?')) {
        router.delete(route('lab.labs.destroy', props.lab.id));
    }
}

function addPriceItem() {
    priceForm.post(route('lab.labs.price-items.store', props.lab.id), {
        onSuccess: () => {
            priceForm.reset();
        },
    });
}

function deletePriceItem(itemId) {
    if (confirm('Xóa giá dịch vụ này?')) {
        router.delete(route('lab.labs.price-items.destroy', [props.lab.id, itemId]));
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
