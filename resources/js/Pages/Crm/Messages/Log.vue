<template>
    <AppLayout title="Lịch sử tin nhắn">
        <div class="space-y-4">
            <h1 class="text-lg font-semibold text-gray-800">Lịch sử tin nhắn</h1>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <select v-model="status" @change="applyFilters" class="filter-input">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <select v-model="channel" @change="applyFilters" class="filter-input">
                    <option value="">Tất cả kênh</option>
                    <option v-for="c in channels" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Bệnh nhân</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">SĐT</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kênh</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mẫu</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nội dung</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Gửi lúc</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="logs.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">Chưa có tin nhắn nào</td>
                        </tr>
                        <tr v-for="l in logs.data" :key="l.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ l.patient }}</td>
                            <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ l.phone }}</td>
                            <td class="px-4 py-3">
                                <span :class="l.channel === 'sms' ? 'bg-blue-100 text-blue-700' : 'bg-teal-100 text-teal-700'"
                                    class="text-xs font-medium px-2 py-0.5 rounded">{{ l.channel.toUpperCase() }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ l.template ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 text-xs max-w-xs truncate">{{ l.content_sent }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="l.status_color">{{ l.status_label }}</StatusBadge>
                                <p v-if="l.error_message" class="text-xs text-red-400 mt-0.5 truncate max-w-32" :title="l.error_message">{{ l.error_message }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ l.sent_at ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="logs.links" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';

const props = defineProps({ logs: Object, filters: Object, statuses: Array, channels: Array });

const status  = ref(props.filters.status ?? '');
const channel = ref(props.filters.channel ?? '');

function applyFilters() {
    router.get(route('crm.messages.log'), { status: status.value, channel: channel.value }, { preserveState: true });
}
</script>

<style scoped>
.filter-input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
