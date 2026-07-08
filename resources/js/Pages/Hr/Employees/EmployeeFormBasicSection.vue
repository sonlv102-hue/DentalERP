<template>
    <div class="bg-white rounded-xl border border-l-4 border-l-blue-400 p-5 space-y-4">
        <h3 class="text-base font-semibold text-blue-700 flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-blue-100 text-xs flex items-center justify-center font-bold">1</span>
            Thông tin cơ bản
        </h3>

        <!-- Code (read-only if editing) -->
        <div v-if="employee" class="text-sm text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
            Mã nhân viên: <span class="font-mono font-semibold text-gray-800">{{ employee.code }}</span>
            <span class="ml-2 text-xs text-gray-400">— Mã không thể thay đổi sau khi tạo</span>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <FormInput label="Chi nhánh *" :error="errors.branch_id">
                <select v-model="form.branch_id" class="input-field">
                    <option value="">-- Chọn chi nhánh --</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
            </FormInput>
            <FormInput label="Phòng ban" :error="errors.department_id">
                <select v-model="form.department_id" class="input-field">
                    <option :value="null">-- Chọn phòng ban --</option>
                    <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
            </FormInput>
            <FormInput label="Họ và tên *" :error="errors.full_name">
                <input v-model="form.full_name" type="text" placeholder="Nguyễn Văn A" class="input-field" />
            </FormInput>
            <FormInput label="Chức vụ" :error="errors.position">
                <input v-model="form.position" type="text" list="positions-list" class="input-field" placeholder="VD: Bác sĩ chỉnh nha" />
                <datalist id="positions-list">
                    <option v-for="p in POSITIONS" :key="p" :value="p" />
                </datalist>
            </FormInput>
            <FormInput label="Điện thoại" :error="errors.phone">
                <input v-model="form.phone" type="tel" placeholder="0901234567" class="input-field" />
            </FormInput>
            <FormInput label="Email" :error="errors.email">
                <input v-model="form.email" type="email" placeholder="nhanvien@phongkham.vn" class="input-field" />
            </FormInput>
            <FormInput label="Ngày sinh" :error="errors.date_of_birth">
                <input v-model="form.date_of_birth" type="date" class="input-field" />
            </FormInput>
            <FormInput label="Giới tính" :error="errors.gender">
                <select v-model="form.gender" class="input-field">
                    <option value="">-- Chọn --</option>
                    <option value="male">Nam</option>
                    <option value="female">Nữ</option>
                    <option value="other">Khác</option>
                </select>
            </FormInput>
            <FormInput label="Chức danh HR" :error="errors.role_type">
                <select v-model="form.role_type" class="input-field">
                    <option value="">-- Chọn chức danh --</option>
                    <option v-for="r in roleTypes" :key="r.value" :value="r.value">{{ r.label }}</option>
                </select>
            </FormInput>
            <FormInput label="Tài khoản đăng nhập" :error="errors.user_id">
                <select v-model="form.user_id" class="input-field">
                    <option :value="null">-- Chưa gắn tài khoản --</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
                </select>
            </FormInput>
        </div>
    </div>
</template>

<script setup>
import FormInput from '@/Components/Shared/FormInput.vue';

defineProps({
    form:        { type: Object, required: true },
    employee:    { type: Object, default: null },
    errors:      { type: Object, default: () => ({}) },
    branches:    { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    roleTypes:   { type: Array, default: () => [] },
    users:       { type: Array, default: () => [] },
});

const POSITIONS = [
    'Giám đốc', 'Quản lý phòng khám', 'Bác sĩ', 'Bác sĩ chính',
    'Bác sĩ chỉnh nha', 'Bác sĩ Implant', 'Phụ tá nha khoa',
    'Tư vấn viên', 'Lễ tân', 'Kế toán viên', 'Thủ kho', 'Kỹ thuật viên', 'CSKH',
];
</script>

<style scoped>
.input-field {
    @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none;
}
</style>
