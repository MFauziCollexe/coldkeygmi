<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div>
        <h2 class="text-2xl font-bold">Electricity - HV Meter (Gardu & Office)</h2>
        <p class="text-slate-400 text-sm">Input LBP/WBP, total otomatis, selisih harian, warning pembacaan turun, grafik trend, dan export Excel.</p>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 space-y-3">
        <h3 class="text-sm font-semibold text-slate-200">Input Log Baru</h3>
        <form class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end" @submit.prevent="submitLog">
          <div class="md:col-span-2 relative">
            <input v-model="form.meter_id" placeholder=" " class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">Meter ID</label>
          </div>
          <div class="md:col-span-2 relative group">
            <EnhancedDatePicker v-model="form.tanggal" placeholder=" " input-class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label :class="['pointer-events-none absolute left-3 z-10 transition-all', form.tanggal ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2' : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2','group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2']">Tanggal</label>
          </div>
          <div class="md:col-span-1 relative">
            <input v-model="form.jam" type="time" class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2">Jam</label>
          </div>
          <div class="md:col-span-2 relative">
            <input v-model.number="form.lbp" type="number" step="0.01" min="0" placeholder=" " class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">LBP</label>
          </div>
          <div class="md:col-span-2 relative">
            <input v-model.number="form.wbp" type="number" step="0.01" min="0" placeholder=" " class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">WBP</label>
          </div>
          <div class="md:col-span-1 relative">
            <input :value="computedTotal" type="text" readonly class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-900 border border-slate-700 text-slate-200" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-900 top-0 -translate-y-1/2">Total</label>
          </div>
          <div class="md:col-span-2 relative">
            <input v-model.number="form.kvarh" type="number" step="0.01" min="0" placeholder=" " class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">kVARh</label>
          </div>
          <div class="md:col-span-2">
            <button type="submit" class="w-full h-[52px] rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-medium" :disabled="form.processing">Simpan</button>
          </div>
        </form>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-3">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
          <div class="md:col-span-4 relative group">
            <SearchableSelect
              v-model="filters.meter_id"
              :options="meterOptions"
              option-value="value"
              option-label="label"
              placeholder=" "
              empty-label="Semua Meter"
              input-class="w-full h-[52px] pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100 placeholder-transparent"
              button-class="h-[52px] border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
            />
            <label :class="['pointer-events-none absolute left-3 z-10 transition-all', filters.meter_id ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2' : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2','group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2']">Filter Meter</label>
          </div>
          <div class="md:col-span-2 relative group">
            <EnhancedDatePicker v-model="filters.start_date" placeholder=" " input-class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label :class="['pointer-events-none absolute left-3 z-10 transition-all', filters.start_date ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2' : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2','group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2']">Periode Mulai</label>
          </div>
          <div class="md:col-span-2 relative group">
            <EnhancedDatePicker v-model="filters.end_date" placeholder=" " input-class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label :class="['pointer-events-none absolute left-3 z-10 transition-all', filters.end_date ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2' : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2','group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2']">Periode Selesai</label>
          </div>
          <div class="md:col-span-4 flex gap-2">
            <button class="h-[52px] px-5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white" @click="applyFilters">Filter</button>
            <button class="h-[52px] px-5 rounded-lg bg-slate-600 hover:bg-slate-500 text-white" @click="resetFilters">Reset</button>
            <a
              v-if="canExportLogs"
              :href="exportUrl"
              class="inline-flex h-[52px] items-center px-5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white"
            >
              Export Excel
            </a>
          </div>
        </div>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-slate-200 mb-3">Grafik Trend</h3>
        <div class="flex flex-wrap items-center gap-3 text-sm mb-3">
          <div v-for="series in chartSeries" :key="`legend-${series.meter_id}`" class="inline-flex items-center gap-2">
            <span class="w-3 h-3 rounded-sm" :style="{ backgroundColor: series.color }"></span>
            <span>Meter: {{ series.meter_id }}</span>
          </div>
        </div>
        <div v-if="!chartSeries.length || chartLabels.length < 2" class="text-slate-400 text-sm">Belum ada data trend.</div>
        <div v-else class="border border-slate-700 rounded-lg p-3">
          <svg :viewBox="`0 0 ${chartWidth} ${chartHeight}`" class="w-full h-64 bg-slate-900/40 rounded">
            <line
              v-for="tick in yTicks"
              :key="`grid-${tick.value}`"
              :x1="paddingLeft"
              :x2="chartWidth - paddingRight"
              :y1="tick.y"
              :y2="tick.y"
              stroke="#334155"
              stroke-width="1"
            />
            <text
              v-for="tick in yTicks"
              :key="`ylabel-${tick.value}`"
              :x="paddingLeft - 10"
              :y="tick.y + 4"
              text-anchor="end"
              font-size="11"
              fill="#94a3b8"
            >{{ tick.value }}</text>
            <text
              v-for="(label, idx) in chartLabels"
              :key="`xlabel-${label}`"
              :x="xForIndex(idx)"
              :y="chartHeight - 10"
              text-anchor="middle"
              font-size="11"
              fill="#94a3b8"
            >{{ formatChartLabel(label) }}</text>
            <polyline
              v-for="series in chartSeries"
              :key="series.meter_id"
              :points="toLinePoints(series.values)"
              fill="none"
              :stroke="series.color"
              stroke-width="3"
            />
          </svg>
        </div>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 overflow-x-auto">
        <table class="w-full text-sm min-w-[1180px]">
          <thead class="border-b border-slate-700 text-slate-400">
            <tr>
              <th class="text-left py-2 pr-3">Tanggal</th>
              <th class="text-left py-2 pr-3">Jam</th>
              <th class="text-left py-2 pr-3">Meter ID</th>
              <th class="text-left py-2 pr-3">LBP (kWh)</th>
              <th class="text-left py-2 pr-3">WBP (kWh)</th>
              <th class="text-left py-2 pr-3">Total (kWh)</th>
              <th class="text-left py-2 pr-3">kVARh</th>
              <th class="text-left py-2 pr-3">Pemakaian (kWh, vs hari sebelumnya)</th>
              <th class="text-left py-2 pr-3">Warning</th>
              <th class="text-left py-2">User</th>
              <th v-if="canEditList" class="text-left py-2">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in logs.data" :key="row.id" class="border-b border-slate-700/40">
              <td class="py-2 pr-3">
                <input v-if="isEditing(row.id)" v-model="editForm.tanggal" type="date" class="w-36 px-2 py-1 rounded bg-slate-900 border border-slate-700 text-slate-100" />
                <span v-else>{{ formatDate(row.tanggal) }}</span>
              </td>
              <td class="py-2 pr-3">
                <input v-if="isEditing(row.id)" v-model="editForm.jam" type="time" class="w-28 px-2 py-1 rounded bg-slate-900 border border-slate-700 text-slate-100" />
                <span v-else>{{ String(row.jam || '').slice(0, 5) }}</span>
              </td>
              <td class="py-2 pr-3">
                <input v-if="isEditing(row.id)" v-model="editForm.meter_id" type="text" class="w-36 px-2 py-1 rounded bg-slate-900 border border-slate-700 text-slate-100" />
                <span v-else>{{ row.meter_id }}</span>
              </td>
              <td class="py-2 pr-3">
                <input v-if="isEditing(row.id)" v-model="editForm.lbp" type="text" inputmode="decimal" class="w-28 px-2 py-1 rounded bg-slate-900 border border-slate-700 text-slate-100" />
                <span v-else>{{ number(row.lbp) }}</span>
              </td>
              <td class="py-2 pr-3">
                <input v-if="isEditing(row.id)" v-model="editForm.wbp" type="text" inputmode="decimal" class="w-28 px-2 py-1 rounded bg-slate-900 border border-slate-700 text-slate-100" />
                <span v-else>{{ number(row.wbp) }}</span>
              </td>
              <td class="py-2 pr-3 font-semibold text-emerald-300">
                <span v-if="isEditing(row.id)">{{ editComputedTotal }}</span>
                <span v-else>{{ number(row.total) }}</span>
              </td>
              <td class="py-2 pr-3">
                <input v-if="isEditing(row.id)" v-model="editForm.kvarh" type="text" inputmode="decimal" class="w-28 px-2 py-1 rounded bg-slate-900 border border-slate-700 text-slate-100" />
                <span v-else>{{ number(row.kvarh) }}</span>
              </td>
              <td class="py-2 pr-3" :class="row.usage < 0 ? 'text-rose-300' : 'text-emerald-300'">{{ row.usage === null ? '-' : number(row.usage) }}</td>
              <td class="py-2 pr-3">
                <span v-if="row.is_warning" class="px-2 py-1 rounded bg-rose-600/20 text-rose-200 border border-rose-500/40 text-xs font-semibold">Turun</span>
                <span v-else class="text-slate-500">-</span>
              </td>
              <td class="py-2">{{ row.user?.name || '-' }}</td>
              <td v-if="canEditList" class="py-2">
                <div class="flex items-center gap-2">
                  <template v-if="isEditing(row.id)">
                    <button class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white text-xs" :disabled="savingEdit" @click="saveEdit(row.id)">Save</button>
                    <button class="px-2 py-1 rounded bg-slate-600 hover:bg-slate-500 text-white text-xs" @click="cancelEdit">Cancel</button>
                  </template>
                  <button v-else class="px-2 py-1 rounded bg-indigo-600 hover:bg-indigo-500 text-white text-xs" @click="startEdit(row)">Edit</button>
                </div>
              </td>
            </tr>
            <tr v-if="!logs.data?.length">
              <td :colspan="canEditList ? 11 : 10" class="py-8 text-center text-slate-400">Belum ada data.</td>
            </tr>
          </tbody>
        </table>

        <div v-if="logs.last_page > 1" class="pt-4 mt-4 border-t border-slate-700 flex items-center justify-end text-sm">
          <Pagination :paginator="logs" :onPageChange="goToPage" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  logs: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
  meterOptions: { type: Array, default: () => [] },
  trendData: { type: Array, default: () => [] },
  canEditList: { type: Boolean, default: false },
  canExportLogs: { type: Boolean, default: false },
});

const form = useForm({
  meter_id: '',
  tanggal: '',
  jam: '',
  lbp: 0,
  wbp: 0,
  kvarh: 0,
});
const canEditList = computed(() => !!props.canEditList);
const canExportLogs = computed(() => !!props.canExportLogs);
const editingId = ref(null);
const savingEdit = ref(false);
const editForm = reactive({
  meter_id: '',
  tanggal: '',
  jam: '',
  lbp: 0,
  wbp: 0,
  kvarh: 0,
});

const filters = reactive({
  meter_id: props.filters?.meter_id || '',
  start_date: props.filters?.start_date || '',
  end_date: props.filters?.end_date || '',
});

const chartWidth = 900;
const chartHeight = 250;
const paddingLeft = 46;
const paddingRight = 14;
const paddingTop = 16;
const paddingBottom = 36;

const chartLabels = computed(() => {
  const labels = new Set();
  props.trendData.forEach((series) => {
    (series.points || []).forEach((point) => {
      if (point?.date) labels.add(point.date);
    });
  });
  return Array.from(labels).sort();
});

const chartSeries = computed(() => {
  return (props.trendData || []).map((series, idx) => {
    const byDate = new Map((series.points || []).map((p) => [p.date, Number(p.usage || 0)]));
    const hue = (idx * 67) % 360;
    return {
      meter_id: series.meter_id,
      color: `hsl(${hue} 80% 58%)`,
      values: chartLabels.value.map((date) => byDate.get(date) ?? 0),
    };
  });
});

const allValues = computed(() => chartSeries.value.flatMap((series) => series.values));

const chartMin = computed(() => {
  const values = allValues.value;
  if (!values.length) return 0;
  return Math.min(...values);
});

const chartMax = computed(() => {
  const values = allValues.value;
  if (!values.length) return 100;
  return Math.max(...values);
});

const yTicks = computed(() => {
  const min = chartMin.value;
  const max = chartMax.value;
  const range = max - min || 1;
  const steps = 5;
  const ticks = [];
  for (let i = 0; i <= steps; i += 1) {
    const value = min + (range * i) / steps;
    ticks.push({
      value: Math.round(value),
      y: yForValue(value),
    });
  }
  return ticks.reverse();
});

const computedTotal = computed(() => {
  const lbp = Number(form.lbp || 0);
  const wbp = Number(form.wbp || 0);
  return number(lbp + wbp);
});
const editComputedTotal = computed(() => {
  const lbp = Number(editForm.lbp || 0);
  const wbp = Number(editForm.wbp || 0);
  return number(lbp + wbp);
});

const exportUrl = computed(() => {
  const params = new URLSearchParams();
  if (filters.meter_id) params.append('meter_id', String(filters.meter_id));
  if (filters.start_date) params.append('start_date', String(filters.start_date));
  if (filters.end_date) params.append('end_date', String(filters.end_date));
  const qs = params.toString();
  return qs
    ? `/gmium/resource-monitoring/electricity/hv-meter/export?${qs}`
    : '/gmium/resource-monitoring/electricity/hv-meter/export';
});

function applyFilters() {
  router.get('/gmium/resource-monitoring/electricity/hv-meter', {
    meter_id: filters.meter_id || undefined,
    start_date: filters.start_date || undefined,
    end_date: filters.end_date || undefined,
  }, { preserveState: true, replace: true });
}

function resetFilters() {
  filters.meter_id = '';
  filters.start_date = '';
  filters.end_date = '';
  applyFilters();
}

function goToPage(page) {
  router.get('/gmium/resource-monitoring/electricity/hv-meter', {
    meter_id: filters.meter_id || undefined,
    start_date: filters.start_date || undefined,
    end_date: filters.end_date || undefined,
    page,
  }, { preserveState: true, replace: true });
}

function submitLog() {
  form.post('/gmium/resource-monitoring/electricity/hv-meter', {
    preserveScroll: true,
    onSuccess: () => form.reset('jam', 'lbp', 'wbp', 'kvarh'),
  });
}

function isEditing(id) {
  return editingId.value === id;
}

function startEdit(row) {
  editingId.value = row.id;
  editForm.meter_id = String(row.meter_id || '');
  editForm.tanggal = toDateInput(row.tanggal);
  editForm.jam = String(row.jam || '').slice(0, 5);
  editForm.lbp = Number(row.lbp || 0);
  editForm.wbp = Number(row.wbp || 0);
  editForm.kvarh = Number(row.kvarh || 0);
}

function cancelEdit() {
  editingId.value = null;
}

function saveEdit(id) {
  if (!id || savingEdit.value) return;
  savingEdit.value = true;
  router.put(`/gmium/resource-monitoring/electricity/hv-meter/${id}`, {
    meter_id: editForm.meter_id,
    tanggal: editForm.tanggal,
    jam: editForm.jam,
    lbp: editForm.lbp,
    wbp: editForm.wbp,
    kvarh: editForm.kvarh,
  }, {
    preserveScroll: true,
    onError: (errors) => {
      const first = errors?.lbp || errors?.wbp || errors?.kvarh || errors?.jam || errors?.tanggal || errors?.meter_id;
      if (first) window.alert(first);
    },
    onFinish: () => {
      savingEdit.value = false;
    },
    onSuccess: () => {
      editingId.value = null;
    },
  });
}

function number(value) {
  const num = Number(value || 0);
  return Number.isFinite(num) ? num.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
}

function formatDate(value) {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '-';
  return date.toLocaleDateString('id-ID');
}

function toDateInput(value) {
  if (!value) return '';
  if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)) return value;
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  return date.toISOString().slice(0, 10);
}

function toLinePoints(values) {
  if (!Array.isArray(values) || values.length <= 1) return '';
  return values.map((value, idx) => {
    const x = xForIndex(idx);
    const y = yForValue(Number(value || 0));
    return `${x},${y}`;
  }).join(' ');
}

function xForIndex(idx) {
  if (chartLabels.value.length <= 1) return paddingLeft;
  const usableWidth = chartWidth - paddingLeft - paddingRight;
  return paddingLeft + (idx / (chartLabels.value.length - 1)) * usableWidth;
}

function yForValue(value) {
  const min = chartMin.value;
  const max = chartMax.value;
  const range = max - min || 1;
  const usableHeight = chartHeight - paddingTop - paddingBottom;
  return chartHeight - paddingBottom - ((value - min) / range) * usableHeight;
}

function formatChartLabel(value) {
  if (!value) return '';
  const parts = String(value).split('-');
  if (parts.length !== 3) return value;
  return `${parts[2]}/${parts[1]}`;
}
</script>
