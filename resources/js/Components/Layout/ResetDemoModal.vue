<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/40" @click="!loading && $emit('cancel')" />

            <!-- Dialog -->
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
                <div class="flex items-start gap-4 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Khôi phục dữ liệu Demo</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Đưa toàn bộ dữ liệu về trạng thái mẫu ban đầu</p>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-5 text-sm text-amber-800 flex gap-2">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <span>
                        Toàn bộ thay đổi bạn đã thực hiện (khách hàng, lịch hẹn, hóa đơn...) sẽ bị
                        <strong>xóa vĩnh viễn</strong> và thay bằng bộ dữ liệu mẫu ban đầu. Bạn sẽ cần đăng nhập lại sau khi hoàn tất.
                    </span>
                </div>

                <div class="flex justify-end gap-3">
                    <button @click="$emit('cancel')" :disabled="loading"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50">
                        Hủy bỏ
                    </button>
                    <button @click="$emit('confirm')" :disabled="loading"
                        class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition-colors disabled:opacity-50 flex items-center gap-2">
                        <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        {{ loading ? 'Đang khôi phục...' : 'Xác nhận khôi phục' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
defineProps({
    show: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
});
defineEmits(['confirm', 'cancel']);
</script>
