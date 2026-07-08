<template>
    <div class="flex flex-wrap items-center gap-3 px-4 py-2.5 bg-gradient-to-r from-slate-800 to-slate-700 rounded-xl text-sm">
        <!-- Phải trả -->
        <div class="flex items-center gap-2 min-w-0">
            <span class="text-slate-400 text-xs whitespace-nowrap">Phải trả</span>
            <span class="font-bold text-white text-base tabular-nums">{{ fmt(totalAmount) }}</span>
        </div>

        <div class="h-4 w-px bg-slate-600 hidden sm:block"></div>

        <!-- Đã thu -->
        <div class="flex items-center gap-2 min-w-0">
            <span class="text-slate-400 text-xs whitespace-nowrap">Đã thu</span>
            <span class="font-bold text-emerald-400 text-base tabular-nums">{{ fmt(amountPaid) }}</span>
        </div>

        <div class="h-4 w-px bg-slate-600 hidden sm:block"></div>

        <!-- Còn nợ -->
        <div class="flex items-center gap-2 min-w-0">
            <span class="text-slate-400 text-xs whitespace-nowrap">Còn nợ</span>
            <span :class="['font-bold text-base tabular-nums', amountDue > 0 ? 'text-rose-400' : 'text-emerald-400']">
                {{ fmt(amountDue) }}
            </span>
        </div>

        <!-- Badge trạng thái -->
        <div class="ml-auto">
            <span v-if="amountDue <= 0"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                Đã thanh toán
            </span>
            <span v-else
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Còn nợ
            </span>
        </div>
    </div>
</template>

<script setup>
defineProps({
    totalAmount: { type: Number, default: 0 },
    amountPaid:  { type: Number, default: 0 },
    amountDue:   { type: Number, default: 0 },
});

function fmt(val) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val ?? 0);
}
</script>
