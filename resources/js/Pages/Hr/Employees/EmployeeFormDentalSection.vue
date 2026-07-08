<template>
    <div class="bg-white rounded-xl border border-l-4 border-l-purple-500 p-5 space-y-4">
        <h3 class="text-base font-semibold text-purple-700 flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-purple-100 text-purple-700 text-xs flex items-center justify-center font-bold">6</span>
            Thông tin chuyên môn nha khoa
        </h3>

        <div class="grid grid-cols-2 gap-4">
            <FormInput label="Vai trò chuyên môn *" :error="errors.dental_role">
                <select v-model="form.dental_role" class="input-field">
                    <option value="">-- Chọn vai trò --</option>
                    <option v-for="r in dentalRoles" :key="r.value" :value="r.value">{{ r.label }}</option>
                </select>
            </FormInput>
            <FormInput label="Chuyên khoa" :error="errors.dental_specialty">
                <select v-model="form.dental_specialty" class="input-field">
                    <option value="">-- Chọn chuyên khoa --</option>
                    <option v-for="s in SPECIALTIES" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </FormInput>
            <FormInput label="Chứng chỉ / hành nghề" :error="errors.practice_certificate">
                <input v-model="form.practice_certificate" type="text" placeholder="VD: CCHN-12345" class="input-field" />
            </FormInput>
            <FormInput label="Mã bác sĩ / mã hành nghề" :error="errors.dentist_license_code">
                <input v-model="form.dentist_license_code" type="text" class="input-field" />
            </FormInput>
            <FormInput label="Số năm kinh nghiệm" :error="errors.years_of_experience">
                <input v-model.number="form.years_of_experience" type="number" min="0" max="60" class="input-field" />
            </FormInput>
            <FormInput label="Kỹ năng chụp X-quang / Scan" :error="errors.xray_scan_skill">
                <select v-model="form.xray_scan_skill" class="input-field">
                    <option value="">-- Chọn --</option>
                    <option v-for="x in XRAY_SKILLS" :key="x.value" :value="x.value">{{ x.label }}</option>
                </select>
            </FormInput>
            <FormInput label="Phân quyền thao tác lâm sàng" :error="errors.clinical_permission">
                <select v-model="form.clinical_permission" class="input-field">
                    <option value="">-- Chọn --</option>
                    <option v-for="c in CLINICAL_PERMS" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
            </FormInput>
            <FormInput label="Lịch làm việc" :error="errors.work_schedule">
                <select v-model="form.work_schedule" class="input-field">
                    <option value="">-- Chọn --</option>
                    <option v-for="w in WORK_SCHEDULES" :key="w" :value="w">{{ w }}</option>
                </select>
            </FormInput>
            <FormInput label="Ghế / Phòng phụ trách" :error="errors.assigned_chair_room">
                <input v-model="form.assigned_chair_room" type="text" placeholder="VD: Ghế 01, Phòng B" class="input-field" />
            </FormInput>
            <FormInput label="Người quản lý trực tiếp" :error="errors.direct_manager_id">
                <select v-model="form.direct_manager_id" class="input-field">
                    <option :value="null">-- Không có --</option>
                    <option v-for="m in managers" :key="m.id" :value="m.id">
                        {{ m.full_name }}{{ m.position ? ` — ${m.position}` : '' }}
                    </option>
                </select>
            </FormInput>
            <FormInput label="Tỷ lệ KPI mặc định (%)" :error="errors.default_kpi_rate">
                <input v-model.number="form.default_kpi_rate" type="number" min="0" max="100" step="0.5" placeholder="0" class="input-field" />
                <p class="text-xs text-gray-400 mt-1">Dùng khi là người chịu trách nhiệm chính một dịch vụ</p>
            </FormInput>
            <FormInput label="Tỷ lệ hỗ trợ công đoạn (%)" :error="errors.support_step_rate">
                <input v-model.number="form.support_step_rate" type="number" min="0" max="100" step="0.5" placeholder="0" class="input-field" />
                <p class="text-xs text-gray-400 mt-1">Dùng khi chỉ hỗ trợ một phần công đoạn</p>
            </FormInput>
        </div>

        <div class="rounded-lg bg-purple-50 border border-purple-200 px-4 py-3 text-sm text-purple-700">
            Thông tin chuyên môn dùng cho phân công, lịch hẹn, bảng lương và báo cáo hiệu suất chuyên môn.
        </div>
    </div>
</template>

<script setup>
import FormInput from '@/Components/Shared/FormInput.vue';

defineProps({
    form:        { type: Object, required: true },
    errors:      { type: Object, default: () => ({}) },
    dentalRoles: { type: Array, default: () => [] },
    managers:    { type: Array, default: () => [] },
});

const SPECIALTIES = [
    { value: 'general',        label: 'Tổng quát' },
    { value: 'orthodontics',   label: 'Chỉnh nha' },
    { value: 'implant',        label: 'Implant' },
    { value: 'prosthodontics', label: 'Phục hình' },
    { value: 'endodontics',    label: 'Nội nha' },
    { value: 'periodontics',   label: 'Nha chu' },
    { value: 'pediatric',      label: 'Răng trẻ em' },
    { value: 'surgery',        label: 'Phẫu thuật' },
    { value: 'aesthetic',      label: 'Thẩm mỹ' },
    { value: 'none',           label: 'Không áp dụng' },
];

const XRAY_SKILLS = [
    { value: 'none',       label: 'Không có' },
    { value: 'basic',      label: 'Cơ bản' },
    { value: 'proficient', label: 'Thành thạo' },
    { value: 'specialist', label: 'Chuyên trách' },
];

const CLINICAL_PERMS = [
    { value: 'none',             label: 'Không có quyền lâm sàng' },
    { value: 'assistant',        label: 'Phụ tá' },
    { value: 'associate_doctor', label: 'Bác sĩ phụ' },
    { value: 'primary_doctor',   label: 'Bác sĩ chính' },
    { value: 'head_doctor',      label: 'Bác sĩ trưởng chuyên môn' },
    { value: 'xray_tech',        label: 'KTV chụp phim / Scan' },
];

const WORK_SCHEDULES = ['Ca sáng', 'Ca chiều', 'Ca tối', 'Full day', 'Theo lịch phân công'];
</script>

<style scoped>
.input-field { @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
