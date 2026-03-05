<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div>
        <h2 class="text-2xl font-bold">Utility Report</h2>
        <p class="text-slate-400 text-sm">Dashboard summary, rekap listrik/air, dan comparison penggunaan utility.</p>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-3">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
          <div class="md:col-span-3">
            <label class="text-xs text-slate-400">Bulan</label>
            <select v-model.number="filterForm.month" class="mt-1 w-full h-[44px] rounded-lg bg-slate-900 border border-slate-700 px-3 text-slate-100">
              <option v-for="month in monthOptions" :key="`m-${month.value}`" :value="month.value">{{ month.label }}</option>
            </select>
          </div>
          <div class="md:col-span-3">
            <label class="text-xs text-slate-400">Tahun</label>
            <select v-model.number="filterForm.year" class="mt-1 w-full h-[44px] rounded-lg bg-slate-900 border border-slate-700 px-3 text-slate-100">
              <option v-for="year in yearOptions" :key="`y-${year.value}`" :value="year.value">{{ year.label }}</option>
            </select>
          </div>
          <div class="md:col-span-6 flex gap-2">
            <button class="h-[44px] px-5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white" @click="applyFilter">Filter</button>
            <button class="h-[44px] px-5 rounded-lg bg-slate-600 hover:bg-slate-500 text-white" @click="resetFilter">Reset</button>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
          <p class="text-slate-400 text-sm">Total Listrik Hari Ini</p>
          <p class="text-2xl font-bold text-yellow-300 mt-1">{{ number(summary.today?.electricity) }} kWh</p>
        </div>
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
          <p class="text-slate-400 text-sm">Total Air Hari Ini</p>
          <p class="text-2xl font-bold text-cyan-300 mt-1">{{ number(summary.today?.water) }}</p>
        </div>
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
          <p class="text-slate-400 text-sm">Total Bulan Dipilih (Listrik)</p>
          <p class="text-2xl font-bold text-emerald-300 mt-1">{{ number(summary.month?.electricity) }} kWh</p>
        </div>
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
          <p class="text-slate-400 text-sm">Total Bulan Dipilih (Semua)</p>
          <p class="text-2xl font-bold text-indigo-300 mt-1">{{ number(summary.month?.total) }}</p>
        </div>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-slate-200 mb-2">Grafik Trend (Bulan Dipilih)</h3>
        <div class="flex flex-wrap items-center gap-3 text-sm mb-3">
          <div class="inline-flex items-center gap-2">
            <span class="w-3 h-3 rounded-sm bg-yellow-400"></span>
            <span>Listrik</span>
          </div>
          <div class="inline-flex items-center gap-2">
            <span class="w-3 h-3 rounded-sm bg-cyan-400"></span>
            <span>Air</span>
          </div>
        </div>
        <div v-if="!dashboardTrend?.labels?.length" class="text-slate-400 text-sm">Belum ada data trend.</div>
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
              v-for="(label, idx) in dashboardTrend.labels"
              :key="`xlabel-${label}`"
              :x="xForIndex(idx)"
              :y="chartHeight - 10"
              text-anchor="middle"
              font-size="11"
              fill="#94a3b8"
            >{{ shortDate(label) }}</text>
            <polyline :points="toLinePoints(dashboardTrend.electricity || [])" fill="none" stroke="#facc15" stroke-width="3" />
            <polyline :points="toLinePoints(dashboardTrend.water || [])" fill="none" stroke="#22d3ee" stroke-width="3" />
          </svg>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 overflow-x-auto">
          <h3 class="text-sm font-semibold text-slate-200 mb-3">Rekap Listrik - Per Meter</h3>
          <table class="w-full text-sm min-w-[320px]">
            <thead class="border-b border-slate-700 text-slate-400">
              <tr><th class="text-left py-2 pr-3">Meter</th><th class="text-left py-2">Total (kWh)</th></tr>
            </thead>
            <tbody>
              <tr v-for="row in electricityRecap.by_meter" :key="`em-${row.label}`" class="border-b border-slate-700/40">
                <td class="py-2 pr-3">{{ row.label }}</td><td class="py-2">{{ number(row.value) }}</td>
              </tr>
              <tr v-if="!electricityRecap.by_meter?.length"><td colspan="2" class="py-6 text-center text-slate-400">Belum ada data.</td></tr>
            </tbody>
          </table>
        </div>

        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 overflow-x-auto">
          <h3 class="text-sm font-semibold text-slate-200 mb-3">Rekap Listrik - Per Lokasi</h3>
          <table class="w-full text-sm min-w-[320px]">
            <thead class="border-b border-slate-700 text-slate-400">
              <tr><th class="text-left py-2 pr-3">Lokasi</th><th class="text-left py-2">Total (kWh)</th></tr>
            </thead>
            <tbody>
              <tr v-for="row in electricityRecap.by_location" :key="`el-${row.label}`" class="border-b border-slate-700/40">
                <td class="py-2 pr-3">{{ row.label }}</td><td class="py-2">{{ number(row.value) }}</td>
              </tr>
              <tr v-if="!electricityRecap.by_location?.length"><td colspan="2" class="py-6 text-center text-slate-400">Belum ada data.</td></tr>
            </tbody>
          </table>
        </div>

        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 overflow-x-auto">
          <h3 class="text-sm font-semibold text-slate-200 mb-3">Rekap Listrik - Per Bulan</h3>
          <table class="w-full text-sm min-w-[320px]">
            <thead class="border-b border-slate-700 text-slate-400">
              <tr><th class="text-left py-2 pr-3">Bulan</th><th class="text-left py-2">Total (kWh)</th></tr>
            </thead>
            <tbody>
              <tr v-for="row in electricityRecap.by_month" :key="`eb-${row.label}`" class="border-b border-slate-700/40">
                <td class="py-2 pr-3">{{ row.label }}</td><td class="py-2">{{ number(row.value) }}</td>
              </tr>
              <tr v-if="!electricityRecap.by_month?.length"><td colspan="2" class="py-6 text-center text-slate-400">Belum ada data.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 overflow-x-auto">
          <h3 class="text-sm font-semibold text-slate-200 mb-3">Rekap Air - Per Meter</h3>
          <table class="w-full text-sm min-w-[320px]">
            <thead class="border-b border-slate-700 text-slate-400">
              <tr><th class="text-left py-2 pr-3">Meter</th><th class="text-left py-2">Total</th></tr>
            </thead>
            <tbody>
              <tr v-for="row in waterRecap.by_meter" :key="`wm-${row.label}`" class="border-b border-slate-700/40">
                <td class="py-2 pr-3">{{ row.label }}</td><td class="py-2">{{ number(row.value) }}</td>
              </tr>
              <tr v-if="!waterRecap.by_meter?.length"><td colspan="2" class="py-6 text-center text-slate-400">Belum ada data.</td></tr>
            </tbody>
          </table>
        </div>

        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 overflow-x-auto">
          <h3 class="text-sm font-semibold text-slate-200 mb-3">Rekap Air - Per Bulan</h3>
          <table class="w-full text-sm min-w-[320px]">
            <thead class="border-b border-slate-700 text-slate-400">
              <tr><th class="text-left py-2 pr-3">Bulan</th><th class="text-left py-2">Total</th></tr>
            </thead>
            <tbody>
              <tr v-for="row in waterRecap.by_month" :key="`wb-${row.label}`" class="border-b border-slate-700/40">
                <td class="py-2 pr-3">{{ row.label }}</td><td class="py-2">{{ number(row.value) }}</td>
              </tr>
              <tr v-if="!waterRecap.by_month?.length"><td colspan="2" class="py-6 text-center text-slate-400">Belum ada data.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
          <h3 class="text-sm font-semibold text-slate-200 mb-3">Comparison: PLN vs Office</h3>
          <div class="space-y-2">
            <div>
              <p class="text-xs text-slate-400 mb-1">PLN</p>
              <div class="h-8 rounded bg-slate-700 overflow-hidden">
                <div class="h-full bg-yellow-500" :style="{ width: percent(comparison.pln_vs_office?.pln, comparisonMaxPlnOffice) + '%' }"></div>
              </div>
              <p class="text-xs text-slate-300 mt-1">{{ number(comparison.pln_vs_office?.pln) }} kWh</p>
            </div>
            <div>
              <p class="text-xs text-slate-400 mb-1">Office</p>
              <div class="h-8 rounded bg-slate-700 overflow-hidden">
                <div class="h-full bg-emerald-500" :style="{ width: percent(comparison.pln_vs_office?.office, comparisonMaxPlnOffice) + '%' }"></div>
              </div>
              <p class="text-xs text-slate-300 mt-1">{{ number(comparison.pln_vs_office?.office) }} kWh</p>
            </div>
          </div>
        </div>

        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
          <h3 class="text-sm font-semibold text-slate-200 mb-3">Comparison: Listrik vs Air</h3>
          <div class="space-y-2">
            <div>
              <p class="text-xs text-slate-400 mb-1">Listrik</p>
              <div class="h-8 rounded bg-slate-700 overflow-hidden">
                <div class="h-full bg-indigo-500" :style="{ width: percent(comparison.electricity_vs_water?.electricity, comparisonMaxElectricityWater) + '%' }"></div>
              </div>
              <p class="text-xs text-slate-300 mt-1">{{ number(comparison.electricity_vs_water?.electricity) }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-400 mb-1">Air</p>
              <div class="h-8 rounded bg-slate-700 overflow-hidden">
                <div class="h-full bg-cyan-500" :style="{ width: percent(comparison.electricity_vs_water?.water, comparisonMaxElectricityWater) + '%' }"></div>
              </div>
              <p class="text-xs text-slate-300 mt-1">{{ number(comparison.electricity_vs_water?.water) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  summary: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  monthOptions: { type: Array, default: () => [] },
  yearOptions: { type: Array, default: () => [] },
  dashboardTrend: { type: Object, default: () => ({ labels: [], electricity: [], water: [] }) },
  electricityRecap: { type: Object, default: () => ({ by_meter: [], by_location: [], by_month: [] }) },
  waterRecap: { type: Object, default: () => ({ by_meter: [], by_month: [] }) },
  comparison: { type: Object, default: () => ({}) },
});

const filterForm = reactive({
  month: Number(props.filters?.month || new Date().getMonth() + 1),
  year: Number(props.filters?.year || new Date().getFullYear()),
});

const chartWidth = 900;
const chartHeight = 250;
const paddingLeft = 46;
const paddingRight = 14;
const paddingTop = 16;
const paddingBottom = 36;

const allTrendValues = computed(() => {
  return [
    ...(props.dashboardTrend?.electricity || []),
    ...(props.dashboardTrend?.water || []),
  ].map((v) => Number(v || 0));
});

const chartMin = computed(() => {
  if (!allTrendValues.value.length) return 0;
  return Math.min(...allTrendValues.value);
});

const chartMax = computed(() => {
  if (!allTrendValues.value.length) return 100;
  return Math.max(...allTrendValues.value);
});

const yTicks = computed(() => {
  const min = chartMin.value;
  const max = chartMax.value;
  const range = max - min || 1;
  const steps = 5;
  const ticks = [];
  for (let i = 0; i <= steps; i += 1) {
    const value = min + (range * i) / steps;
    ticks.push({ value: Math.round(value), y: yForValue(value) });
  }
  return ticks.reverse();
});

const comparisonMaxPlnOffice = computed(() => Math.max(
  Number(props.comparison?.pln_vs_office?.pln || 0),
  Number(props.comparison?.pln_vs_office?.office || 0),
  1,
));

const comparisonMaxElectricityWater = computed(() => Math.max(
  Number(props.comparison?.electricity_vs_water?.electricity || 0),
  Number(props.comparison?.electricity_vs_water?.water || 0),
  1,
));

function number(value) {
  const num = Number(value || 0);
  return Number.isFinite(num) ? num.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
}

function shortDate(value) {
  if (!value) return '';
  const parts = String(value).split('-');
  if (parts.length !== 3) return value;
  return `${parts[2]}/${parts[1]}`;
}

function xForIndex(idx) {
  const total = props.dashboardTrend?.labels?.length || 0;
  if (total <= 1) return paddingLeft;
  const usableWidth = chartWidth - paddingLeft - paddingRight;
  return paddingLeft + (idx / (total - 1)) * usableWidth;
}

function yForValue(value) {
  const min = chartMin.value;
  const max = chartMax.value;
  const range = max - min || 1;
  const usableHeight = chartHeight - paddingTop - paddingBottom;
  return chartHeight - paddingBottom - ((value - min) / range) * usableHeight;
}

function toLinePoints(values) {
  if (!Array.isArray(values) || values.length <= 1) return '';
  return values.map((value, idx) => `${xForIndex(idx)},${yForValue(Number(value || 0))}`).join(' ');
}

function percent(value, max) {
  const v = Number(value || 0);
  const m = Number(max || 1);
  return Math.max(0, Math.min(100, (v / m) * 100));
}

function applyFilter() {
  router.get('/gmium/utility-report', {
    month: filterForm.month,
    year: filterForm.year,
  }, { preserveState: true, replace: true });
}

function resetFilter() {
  const now = new Date();
  filterForm.month = now.getMonth() + 1;
  filterForm.year = now.getFullYear();
  applyFilter();
}
</script>
