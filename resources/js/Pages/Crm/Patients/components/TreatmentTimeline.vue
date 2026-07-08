<script setup>
defineProps({
    timeline: Array,
});

const typeConfig = {
    appointment: { label: 'Lịch hẹn', color: 'bg-blue-500', icon: 'calendar' },
    clinical_note: { label: 'Lâm sàng', color: 'bg-green-500', icon: 'clipboard' },
    payment: { label: 'Thanh toán', color: 'bg-yellow-500', icon: 'cash' },
};
</script>

<template>
    <div>
        <h3 class="font-semibold text-gray-700 mb-4">Timeline điều trị</h3>
        <div v-if="timeline.length === 0" class="text-center py-8 text-gray-400 text-sm">
            Chưa có lịch sử nào
        </div>
        <div v-else class="relative">
            <!-- Vertical line -->
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
            <div class="space-y-4">
                <div v-for="(item, idx) in timeline" :key="idx" class="flex gap-4 pl-10 relative">
                    <!-- Dot -->
                    <div class="absolute left-2.5 top-1.5 w-3 h-3 rounded-full border-2 border-white"
                        :class="typeConfig[item.type]?.color ?? 'bg-gray-400'"></div>
                    <!-- Content -->
                    <div class="flex-1 bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                {{ typeConfig[item.type]?.label ?? item.type }}
                            </span>
                            <span class="text-xs text-gray-400">{{ item.date }}</span>
                        </div>
                        <p class="text-sm text-gray-700 mt-1">{{ item.detail }}</p>
                        <span v-if="item.status" class="text-xs text-gray-400">{{ item.status }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
