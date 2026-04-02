<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div>
        <h2 class="text-2xl font-bold">Water Meter</h2>
        <p class="text-slate-400 text-sm">Catat meter air, hitung selisih otomatis, grafik harian, rata-rata pemakaian, dan export Excel.</p>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 space-y-3">
        <h3 class="text-sm font-semibold text-slate-200">Input Log Baru</h3>
        <form class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end" @submit.prevent="submitLog">
          <div class="md:col-span-3 relative">
            <input v-model="form.meter_id" placeholder=" " class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">Meter ID</label>
          </div>
          <div class="md:col-span-2 relative group">
            <EnhancedDatePicker v-model="form.tanggal" placeholder=" " input-class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label :class="['pointer-events-none absolute left-3 z-10 transition-all', form.tanggal ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2' : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2','group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2']">Tanggal</label>
          </div>
          <div class="md:col-span-2 relative">
            <input v-model="form.jam" type="time" class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2">Jam</label>
          </div>
          <div class="md:col-span-3 relative">
            <input v-model.number="form.meter_value" type="number" step="0.01" min="0" placeholder=" " class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">Meter Value</label>
          </div>
          <div class="md:col-span-2">
            <button type="submit" class="w-full h-[52px] rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-medium" :disabled="form.processing">Simpan</button>
          </div>
        </form>
        <p v-if="form.errors.meter_value" class="text-rose-300 text-sm">{{ form.errors.meter_value }}</p>
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

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
          <p class="text-slate-400 text-sm">Rata-rata Pemakaian</p>
          <p class="text-2xl font-bold text-emerald-300 mt-1">{{ number(averageUsage) }}</p>
        </div>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-slate-200 mb-3">Grafik Harian</h3>
        <div v-if="!trendData.length" class="text-slate-400 text-sm">Belum ada data trend.</div>
        <div v-else class="space-y-4">
          <div v-for="series in trendData" :key="series.meter_id" class="border border-slate-700 rounded-lg p-3">
            <p class="text-sm font-semibold mb-2">Meter: {{ series.meter_id }}</p>
            <svg v-if="series.points.length > 1" viewBox="0 0 600 140" class="w-full h-36 bg-slate-900/40 rounded">
              <polyline :points="toLinePoints(series.points)" fill="none" stroke="#22d3ee" stroke-width="2" />
            </svg>
            <div v-else class="text-slate-400 text-xs">Data belum cukup untuk grafik harian.</div>
          </div>
        </div>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 overflow-x-auto">
        <table class="w-full text-sm min-w-[980px]">
          <thead class="border-b border-slate-700 text-slate-400">
            <tr>
              <th class="text-left py-2 pr-3">Tanggal</th>
              <th class="text-left py-2 pr-3">Jam</th>
              <th class="text-left py-2 pr-3">Meter Value (m³)</th>
              <th class="text-left py-2 pr-3">Selisih (m³)</th>
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
                <input v-if="isEditing(row.id)" v-model="editForm.meter_value" type="text" inputmode="decimal" class="w-32 px-2 py-1 rounded bg-slate-900 border border-slate-700 text-slate-100" />
                <span v-else>{{ number(row.meter_value) }}</span>
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
              <td :colspan="canEditList ? 7 : 6" class="py-8 text-center text-slate-400">Belum ada data.</td>
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
  averageUsage: { type: Number, default: 0 },
  canEditList: { type: Boolean, default: false },
  canExportLogs: { type: Boolean, default: false },
});

const form = useForm({
  meter_id: '',
  tanggal: '',
  jam: '',
  meter_value: 0,
});
const canEditList = computed(() => !!props.canEditList);
const canExportLogs = computed(() => !!props.canExportLogs);
const editingId = ref(null);
const savingEdit = ref(false);
const editForm = reactive({
  meter_id: '',
  tanggal: '',
  jam: '',
  meter_value: 0,
});

const filters = reactive({
  meter_id: props.filters?.meter_id || '',
  start_date: props.filters?.start_date || '',
  end_date: props.filters?.end_date || '',
});

const exportUrl = computed(() => {
  const params = new URLSearchParams();
  if (filters.meter_id) params.append('meter_id', String(filters.meter_id));
  if (filters.start_date) params.append('start_date', String(filters.start_date));
  if (filters.end_date) params.append('end_date', String(filters.end_date));
  const qs = params.toString();
  return qs
    ? `/gmium/resource-monitoring/water-meter/export?${qs}`
    : '/gmium/resource-monitoring/water-meter/export';
});

function applyFilters() {
  router.get('/gmium/resource-monitoring/water-meter', {
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
  router.get('/gmium/resource-monitoring/water-meter', {
    meter_id: filters.meter_id || undefined,
    start_date: filters.start_date || undefined,
    end_date: filters.end_date || undefined,
    page,
  }, { preserveState: true, replace: true });
}

function submitLog() {
  form.post('/gmium/resource-monitoring/water-meter', {
    preserveScroll: true,
    onSuccess: () => form.reset('jam', 'meter_value'),
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
  editForm.meter_value = Number(row.meter_value || 0);
}

function cancelEdit() {
  editingId.value = null;
}

function saveEdit(id) {
  if (!id || savingEdit.value) return;
  savingEdit.value = true;
  router.put(`/gmium/resource-monitoring/water-meter/${id}`, {
    meter_id: editForm.meter_id,
    tanggal: editForm.tanggal,
    jam: editForm.jam,
    meter_value: editForm.meter_value,
  }, {
    preserveScroll: true,
    onError: (errors) => {
      const first = errors?.meter_value || errors?.jam || errors?.tanggal || errors?.meter_id;
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

function toLinePoints(points) {
  if (!Array.isArray(points) || points.length <= 1) return '';
  const values = points.map((p) => Number(p.usage || 0));
  const min = Math.min(...values);
  const max = Math.max(...values);
  const range = max - min || 1;
  const width = 600;
  const height = 140;
  return points.map((p, idx) => {
    const x = (idx / (points.length - 1)) * (width - 20) + 10;
    const y = height - 10 - ((Number(p.usage || 0) - min) / range) * (height - 20);
    return `${x},${y}`;
  }).join(' ');
}
</script>
