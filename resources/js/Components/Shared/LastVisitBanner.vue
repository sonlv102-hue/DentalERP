<template>
    <!-- Last visit banner -->
    <div v-if="lastActivity" class="flex items-start gap-3 px-4 py-2.5 bg-blue-50 border border-blue-100 rounded-xl text-sm">
        <div class="flex-shrink-0 mt-0.5">
            <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <span class="text-blue-600 font-medium">Đến gần nhất:</span>
            <span class="text-blue-800 ml-1">{{ lastActivity.created_at }}</span>
            <span class="text-blue-600 mx-1">·</span>
            <span class="text-blue-700">{{ lastActivity.content || lastActivity.type_label }}</span>
            <span v-if="lastActivity.creator" class="text-blue-500 text-xs ml-1">— {{ lastActivity.creator }}</span>
        </div>
    </div>
    <div v-else class="flex items-center gap-2 px-4 py-2 bg-gray-50 border border-dashed border-gray-200 rounded-xl text-sm text-gray-400">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        Chưa có lần khám nào
    </div>
</template>

<script setup>
defineProps({
    lastActivity: { type: Object, default: null },
});
</script>
