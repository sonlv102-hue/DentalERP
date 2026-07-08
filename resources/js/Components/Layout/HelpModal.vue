<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/40" @click="$emit('close')" />

            <!-- Dialog -->
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 max-h-[85vh] flex flex-col">
                <div class="flex items-start gap-4 px-6 py-5 border-b border-gray-100">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900">Hướng dẫn sử dụng</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Tổng quan các khu vực chính trong hệ thống Dental ERP Demo</p>
                    </div>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 overflow-y-auto space-y-5 text-sm text-gray-600">
                    <p>
                        Đây là bản demo hệ thống <strong>Dental ERP</strong> — phần mềm quản lý phòng khám nha khoa.
                        Dữ liệu trong hệ thống (khách hàng, lịch hẹn, hóa đơn...) là dữ liệu mẫu, bạn có thể thao tác
                        tự do để trải nghiệm mà không ảnh hưởng dữ liệu thật.
                    </p>

                    <div class="space-y-3">
                        <div v-for="section in sections" :key="section.title" class="flex gap-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center" :class="section.badgeClass">
                                <span class="text-sm">{{ section.icon }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">{{ section.title }}</p>
                                <p class="text-gray-500 text-xs mt-0.5">{{ section.desc }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-amber-800 text-xs">
                        Nếu dữ liệu demo bị chỉnh sửa lộn xộn trong quá trình trải nghiệm, dùng nút
                        <strong>“Khôi phục dữ liệu Demo”</strong> ở thanh trên cùng để đưa hệ thống về trạng thái mẫu ban đầu.
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
                    <button @click="$emit('close')"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                        Đã hiểu
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
defineProps({ show: { type: Boolean, default: false } });
defineEmits(['close']);

const sections = [
    { icon: '👥', title: 'CRM & Khách hàng', desc: 'Quản lý hồ sơ khách hàng, lịch sử điều trị và thông tin liên hệ.', badgeClass: 'bg-blue-50' },
    { icon: '📅', title: 'Lịch hẹn', desc: 'Đăng ký khám và quản lý lịch hẹn theo bác sĩ, chi nhánh.', badgeClass: 'bg-teal-50' },
    { icon: '🦷', title: 'Điều trị', desc: 'Lập kế hoạch điều trị, theo dõi tiến độ từng hạng mục.', badgeClass: 'bg-indigo-50' },
    { icon: '💰', title: 'Thu ngân', desc: 'Xuất hóa đơn, ghi nhận thanh toán và theo dõi công nợ.', badgeClass: 'bg-emerald-50' },
    { icon: '📞', title: 'Chăm sóc khách hàng', desc: 'Quản lý lead tiềm năng, công việc follow-up và mẫu tin nhắn.', badgeClass: 'bg-amber-50' },
    { icon: '📊', title: 'Báo cáo', desc: 'Doanh thu, công nợ, hiệu suất nhân viên và các chỉ số vận hành.', badgeClass: 'bg-purple-50' },
    { icon: '🧑‍💼', title: 'Nhân sự', desc: 'Hồ sơ nhân viên, chấm công, bảng lương và đánh giá.', badgeClass: 'bg-rose-50' },
    { icon: '⚙️', title: 'Quản lý & Admin', desc: 'Chi nhánh, dịch vụ, bảng giá, người dùng và phân quyền.', badgeClass: 'bg-gray-100' },
];
</script>
