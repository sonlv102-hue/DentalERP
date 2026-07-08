<template>
    <AppLayout :title="`Hồ sơ — ${e.code}`">
        <div class="max-w-5xl mx-auto p-6 space-y-5">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xl font-bold">
                        {{ e.full_name.charAt(0) }}
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ e.full_name }}</h1>
                        <p class="text-sm text-gray-500">{{ e.code }} · {{ e.position || e.role_label }}</p>
                        <span :class="`inline-flex mt-1 px-2 py-0.5 rounded-full text-xs font-medium bg-${e.status_color}-100 text-${e.status_color}-700`">
                            {{ e.status_label }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Link v-if="can('employees.manage')" :href="route('employees.edit', e.id)"
                        class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        Sửa hồ sơ
                    </Link>
                    <Link :href="route('employees.index')" class="px-4 py-2 text-sm border rounded-lg text-gray-600 hover:bg-gray-50">
                        ← Danh sách
                    </Link>
                </div>
            </div>

            <!-- KPI summary -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border p-4">
                    <p class="text-xs text-gray-500">Tổng thu nhập (Gross)</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ formatVnd(e.gross_income) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">/ tháng</p>
                </div>
                <div class="bg-white rounded-xl border p-4">
                    <p class="text-xs text-gray-500">Tổng phụ cấp & trợ cấp</p>
                    <p class="text-xl font-bold text-emerald-700 mt-1">{{ formatVnd(e.total_allowances) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">/ tháng</p>
                </div>
                <div class="bg-white rounded-xl border p-4">
                    <p class="text-xs text-gray-500">Trạng thái BHXH</p>
                    <p class="text-xl font-bold mt-1" :class="e.social_insurance_enabled ? 'text-green-600' : 'text-gray-400'">
                        {{ e.social_insurance_enabled ? 'Đang tham gia' : 'Không tham gia' }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ e.social_insurance_enabled ? 'BHXH/BHYT/BHTN' : 'Chưa bật bảo hiểm' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-5">
                <!-- Left column -->
                <div class="space-y-5">
                    <!-- Basic info -->
                    <InfoCard title="Thông tin cơ bản" color="blue">
                        <InfoRow label="Chi nhánh" :value="e.branch_name" />
                        <InfoRow label="Phòng ban" :value="e.department_name" />
                        <InfoRow label="Chức vụ" :value="e.position" />
                        <InfoRow label="Điện thoại" :value="e.phone" />
                        <InfoRow label="Email" :value="e.email" />
                        <InfoRow label="Ngày sinh" :value="e.date_of_birth" />
                        <InfoRow label="Giới tính" :value="GENDERS[e.gender]" />
                        <InfoRow label="Tài khoản" :value="e.user_name ? `${e.user_name} (${e.user_email})` : '—'" />
                    </InfoCard>

                    <!-- Contract -->
                    <InfoCard title="Hợp đồng lao động" color="yellow">
                        <InfoRow label="Ngày vào làm" :value="e.start_date" />
                        <InfoRow label="Loại hợp đồng" :value="e.contract_label" />
                        <InfoRow label="Trạng thái" :value="e.status_label" />
                    </InfoCard>

                    <!-- Allowances -->
                    <InfoCard title="Phụ cấp & Trợ cấp" color="emerald">
                        <InfoRow label="Phụ cấp trách nhiệm" :value="formatVnd(e.responsibility_allowance)" />
                        <InfoRow label="Phụ cấp cố định khác" :value="formatVnd(e.fixed_allowance)" />
                        <InfoRow label="Hỗ trợ ăn trưa" :value="formatVnd(e.lunch_allowance)" />
                        <InfoRow label="Hỗ trợ xăng xe" :value="formatVnd(e.travel_allowance)" />
                        <InfoRow label="Hỗ trợ điện thoại" :value="formatVnd(e.phone_allowance)" />
                    </InfoCard>
                </div>

                <!-- Right column -->
                <div class="space-y-5">
                    <!-- Salary -->
                    <InfoCard title="Lương & Bảo hiểm" color="green">
                        <InfoRow label="Lương cơ bản" :value="formatVnd(e.base_salary)" />
                        <InfoRow label="Ngày công chuẩn" :value="e.standard_working_days ? `${e.standard_working_days} ngày` : '—'" />
                        <InfoRow label="Số NPT" :value="e.dependents_count ? `${e.dependents_count} người` : '0'" />
                        <InfoRow label="MST TNCN" :value="e.personal_tax_code" />
                    </InfoCard>

                    <!-- Dental professional -->
                    <InfoCard title="Chuyên môn nha khoa" color="purple">
                        <InfoRow label="Vai trò chuyên môn" :value="e.dental_role_label" />
                        <InfoRow label="Chuyên khoa" :value="e.dental_specialty" />
                        <InfoRow label="Chứng chỉ hành nghề" :value="e.practice_certificate" />
                        <InfoRow label="Mã hành nghề" :value="e.dentist_license_code" />
                        <InfoRow label="Kinh nghiệm" :value="e.years_of_experience ? `${e.years_of_experience} năm` : '—'" />
                        <InfoRow label="Kỹ năng X-quang" :value="e.xray_scan_skill" />
                        <InfoRow label="Phân quyền lâm sàng" :value="e.clinical_permission" />
                        <InfoRow label="Lịch làm việc" :value="e.work_schedule" />
                        <InfoRow label="Ghế / Phòng" :value="e.assigned_chair_room" />
                        <InfoRow label="KPI mặc định" :value="e.default_kpi_rate != null ? `${e.default_kpi_rate}%` : '—'" />
                        <InfoRow label="Tỷ lệ hỗ trợ" :value="e.support_step_rate != null ? `${e.support_step_rate}%` : '—'" />
                        <InfoRow label="Quản lý trực tiếp" :value="e.direct_manager_name" />
                    </InfoCard>

                    <!-- Address -->
                    <InfoCard v-if="e.permanent_address || e.notes" title="Địa chỉ & Ghi chú" color="slate">
                        <InfoRow label="Thường trú" :value="e.permanent_address" />
                        <InfoRow label="Ghi chú" :value="e.notes" />
                    </InfoCard>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ employee: Object });
const e = props.employee;

const GENDERS = { male: 'Nam', female: 'Nữ', other: 'Khác' };

function formatVnd(v) { return v ? new Intl.NumberFormat('vi-VN').format(v) + ' ₫' : '—'; }
</script>

<script>
// Inline sub-components to keep file count low
import { defineComponent, h } from 'vue';

const InfoCard = defineComponent({
    props: { title: String, color: { type: String, default: 'gray' } },
    setup(props, { slots }) {
        const colors = {
            blue: 'border-l-blue-400 text-blue-700',
            yellow: 'border-l-yellow-400 text-yellow-700',
            green: 'border-l-green-500 text-green-700',
            emerald: 'border-l-emerald-400 text-emerald-700',
            purple: 'border-l-purple-500 text-purple-700',
            slate: 'border-l-slate-400 text-slate-700',
        };
        return () => h('div', { class: `bg-white rounded-xl border border-l-4 ${colors[props.color] || colors.slate} p-4 space-y-3` }, [
            h('h3', { class: `text-sm font-semibold ${colors[props.color]?.split(' ')[1] || 'text-gray-700'}` }, props.title),
            h('div', { class: 'space-y-2' }, slots.default?.()),
        ]);
    },
});

const InfoRow = defineComponent({
    props: { label: String, value: [String, Number] },
    setup(props) {
        return () => h('div', { class: 'flex gap-3 text-sm' }, [
            h('span', { class: 'w-40 flex-shrink-0 text-gray-500' }, props.label),
            h('span', { class: 'text-gray-800 font-medium' }, props.value || '—'),
        ]);
    },
});
</script>
