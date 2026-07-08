<template>
    <AppLayout title="Xuất sổ HKD (TT152)">
        <div class="max-w-4xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">Xuất sổ kế toán — {{ profile.full_name }}</h1>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3 flex-wrap">
                <input v-model="curPeriod" type="month" @change="resetPreview" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                <select v-model="selectedBook" @change="resetPreview" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option v-for="b in availableBooks" :key="b" :value="b">{{ bookLabel(b) }}</option>
                </select>
                <div class="ml-auto flex gap-2">
                    <button @click="previewBook" :disabled="loading" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50">
                        {{ loading ? 'Đang tải...' : '👁 Xem trước' }}
                    </button>
                    <a :href="pdfUrl" target="_blank" class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700">⬇ Tải PDF</a>
                </div>
            </div>

            <!-- Book description -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-sm text-indigo-800">
                <strong>{{ bookLabel(selectedBook) }}</strong> — {{ bookDesc(selectedBook) }}
            </div>

            <!-- Preview table -->
            <div v-if="preview" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">{{ preview.title }}</h3>
                    <span class="text-xs text-gray-400">Kỳ {{ curPeriod }} · {{ profile.full_name }}</span>
                </div>

                <!-- S1a / S2a / S2b / S2c: key-value summary -->
                <div v-if="preview.type === 'summary'" class="p-4">
                    <dl class="divide-y divide-gray-100">
                        <div v-for="row in preview.rows" :key="row.label" class="flex justify-between py-2 text-sm">
                            <dt class="text-gray-600">{{ row.label }}</dt>
                            <dd class="font-mono font-semibold text-gray-800">{{ row.value }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- S2d / S2e: transaction table -->
                <div v-else-if="preview.type === 'table'" class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th v-for="col in preview.columns" :key="col.key" :class="['px-3 py-2 font-semibold text-gray-500', col.align === 'right' ? 'text-right' : 'text-left']">{{ col.label }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(row, i) in preview.rows" :key="i" :class="row._total ? 'bg-gray-50 font-semibold' : 'hover:bg-gray-50'">
                                <td v-for="col in preview.columns" :key="col.key" :class="['px-3 py-2', col.align === 'right' ? 'text-right font-mono' : 'text-gray-700']">
                                    {{ row[col.key] }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- S3a: other taxes table -->
                <div v-else-if="preview.type === 'taxes'" class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-gray-500">Loại thuế</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-500">Căn cứ</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-500">Tỷ lệ</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-500">Số thuế</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-500">Hạn nộp</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-500">Đã nộp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="row in preview.rows" :key="row.id" class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-gray-700">{{ row.tax_type }}</td>
                                <td class="px-3 py-2 text-right font-mono">{{ row.taxable_amount }}</td>
                                <td class="px-3 py-2 text-right font-mono">{{ row.tax_rate }}</td>
                                <td class="px-3 py-2 text-right font-mono font-semibold">{{ row.tax_amount }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ row.due_date }}</td>
                                <td class="px-3 py-2 text-right font-mono text-green-700">{{ row.paid_amount }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else-if="!loading" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400 text-sm">
                Chọn kỳ và loại sổ, sau đó nhấn "Xem trước" để hiển thị nội dung.
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({ profile: Object, availableBooks: Array, period: String });

const curPeriod    = ref(props.period);
const selectedBook = ref(props.availableBooks?.[0] ?? 'S1a');
const preview      = ref(null);
const loading      = ref(false);

const pdfUrl = computed(() => route('hkd.reports.pdf', { period: curPeriod.value, book: selectedBook.value }));

const BOOK_META = {
    'S1a': { label: 'S1a-HKD — Sổ theo dõi doanh thu',        desc: 'Theo dõi doanh thu không chịu thuế hoặc miễn thuế.' },
    'S2a': { label: 'S2a-HKD — Doanh thu & thuế khoán',        desc: 'Doanh thu tính thuế theo phương pháp khoán.' },
    'S2b': { label: 'S2b-HKD — Doanh thu theo DT-TNCN',        desc: 'Doanh thu tính VAT theo doanh thu và TNCN.' },
    'S2c': { label: 'S2c-HKD — Chi phí hợp lý',                desc: 'Sổ theo dõi các khoản chi phí được khấu trừ.' },
    'S2d': { label: 'S2d-HKD — Hàng tồn kho BQ gia quyền',    desc: 'Nhập xuất tồn theo phương pháp bình quân gia quyền.' },
    'S2e': { label: 'S2e-HKD — Sổ quỹ tiền mặt/ngân hàng',    desc: 'Theo dõi thu chi tiền mặt và tài khoản ngân hàng.' },
    'S3a': { label: 'S3a-HKD — Thuế khác phải nộp',            desc: 'Theo dõi các loại thuế khác: môn bài, nhà đất...' },
};

function bookLabel(b) { return BOOK_META[b]?.label ?? b; }
function bookDesc(b)  { return BOOK_META[b]?.desc  ?? ''; }
function resetPreview() { preview.value = null; }

async function previewBook() {
    loading.value = true;
    preview.value = null;
    try {
        const res = await fetch(route('hkd.reports.preview', { period: curPeriod.value, book: selectedBook.value }), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        if (res.ok) preview.value = await res.json();
    } finally {
        loading.value = false;
    }
}
</script>
