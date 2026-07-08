<template>
    <div class="bg-white rounded-xl border border-l-4 border-l-green-500 p-5 space-y-4">
        <h3 class="text-base font-semibold text-green-700 flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-green-100 text-green-700 text-xs flex items-center justify-center font-bold">3</span>
            Lương cơ bản & Bảo hiểm
        </h3>

        <div class="grid grid-cols-2 gap-6">
            <!-- Left: salary fields -->
            <div class="space-y-4">
                <FormInput label="Lương cơ bản / tháng (VND) *" :error="errors.base_salary">
                    <input v-model.number="form.base_salary" type="number" min="0" step="100000" placeholder="0" class="input-field" />
                    <p class="text-xs text-gray-400 mt-1">Lương cơ bản dùng để tính các khoản theo tỷ lệ</p>
                </FormInput>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" v-model="form.social_insurance_enabled"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 w-4 h-4" />
                        <span class="text-sm font-medium text-gray-700">Đóng BHXH / BHYT / BHTN</span>
                    </label>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <FormInput label="Số người phụ thuộc (NPT)" :error="errors.dependents_count">
                        <input v-model.number="form.dependents_count" type="number" min="0" class="input-field" />
                        <p class="text-xs text-gray-400 mt-1">Giảm trừ 4.400.000 đ / NPT / tháng</p>
                    </FormInput>
                    <FormInput label="Mã số thuế TNCN" :error="errors.personal_tax_code">
                        <input v-model="form.personal_tax_code" type="text" maxlength="13" placeholder="10 hoặc 13 chữ số" class="input-field" />
                        <p class="text-xs text-gray-400 mt-1">10 chữ số theo đăng ký thuế cá nhân</p>
                    </FormInput>
                </div>
                <FormInput label="Ngày công chuẩn / tháng" :error="errors.standard_working_days">
                    <input v-model.number="form.standard_working_days" type="number" min="1" max="31" class="input-field" />
                    <p class="text-xs text-gray-400 mt-1">Dùng để tính lương theo ngày công thực tế</p>
                </FormInput>
            </div>

            <!-- Right: BHXH reference box -->
            <div v-if="form.social_insurance_enabled" class="bg-green-50 border border-green-200 rounded-xl p-4 space-y-3 self-start">
                <p class="text-sm font-semibold text-green-700">Mức đóng bảo hiểm tham khảo (NLĐ)</p>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">BHXH 8%</span>
                        <span class="font-mono font-medium">{{ formatVnd(bhxh) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">BHYT 1.5%</span>
                        <span class="font-mono font-medium">{{ formatVnd(bhyt) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">BHTN 1%</span>
                        <span class="font-mono font-medium">{{ formatVnd(bhtn) }}</span>
                    </div>
                    <div class="border-t border-green-200 pt-2 flex justify-between font-semibold">
                        <span class="text-green-700">Tổng khấu trừ</span>
                        <span class="font-mono text-green-700">{{ formatVnd(bhxh + bhyt + bhtn) }}</span>
                    </div>
                </div>
                <p class="text-xs text-gray-400">Tỷ lệ BHXH/BHYT/BHTN theo quy định hiện hành</p>
            </div>
            <div v-else class="bg-gray-50 border border-dashed border-gray-300 rounded-xl p-4 flex items-center justify-center self-start">
                <p class="text-sm text-gray-400 text-center">Bật đóng BHXH/BHYT/BHTN<br>để xem mức đóng tham khảo</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import FormInput from '@/Components/Shared/FormInput.vue';

const props = defineProps({
    form:   { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
});

const bhxh = computed(() => Math.round((props.form.base_salary || 0) * 0.08));
const bhyt = computed(() => Math.round((props.form.base_salary || 0) * 0.015));
const bhtn = computed(() => Math.round((props.form.base_salary || 0) * 0.01));

function formatVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
</script>

<style scoped>
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
