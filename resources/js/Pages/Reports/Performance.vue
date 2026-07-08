<template>
    <AppLayout title="Hiệu suất nhân viên">
        <div class="space-y-4">
            <h1 class="text-lg font-semibold text-gray-800">Doanh số nhân viên</h1>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Kỳ</label>
                    <input v-model="period" type="month" class="filter-input" />
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Chi nhánh</label>
                    <select v-model="branchId" class="filter-input">
                        <option value="">Tất cả</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
                <button @click="applyFilters" class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700">Lọc</button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nhân viên</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vai trò</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Số ca</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Doanh thu</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Hoa hồng</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="active.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">Không có dữ liệu</td>
                        </tr>
                        <tr v-for="r in active" :key="r.employee_id" class="hover:bg-gray-50" :class="r.revenue === 0 ? 'opacity-50' : ''">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ r.employee }}</p>
                                <p class="text-xs text-gray-400 font-mono">{{ r.code }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ r.role_type }}</td>
                            <td class="px-4 py-3 text-center font-mono text-gray-600">{{ r.case_count || '—' }}</td>
                            <td class="px-4 py-3 text-right font-mono" :class="r.revenue > 0 ? 'text-gray-800 font-semibold' : 'text-gray-300'">
                                {{ r.revenue ? formatVnd(r.revenue) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-green-600">
                                {{ r.commission ? formatVnd(r.commission) : '—' }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Tổng cộng:</td>
                            <td class="px-4 py-2 text-right font-bold font-mono text-gray-800">{{ formatVnd(totalRevenue) }}</td>
                            <td class="px-4 py-2 text-right font-bold font-mono text-green-700">{{ formatVnd(totalCommission) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props    = defineProps({ rows: Array, period: String, branches: Array, filters: Object });
const period   = ref(props.filters.period ?? '');
const branchId = ref(props.filters.branchId ?? '');

const active         = computed(() => props.rows ?? []);
const totalRevenue   = computed(() => active.value.reduce((s, r) => s + r.revenue, 0));
const totalCommission = computed(() => active.value.reduce((s, r) => s + r.commission, 0));

function applyFilters() {
    router.get(route('reports.performance'), { period: period.value, branch_id: branchId.value }, { preserveState: true });
}

function formatVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
</script>

<style scoped>
.filter-input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none; }
</style>
