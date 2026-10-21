<template>
  <AppLayout>
    <div class="space-y-6 p-4 md:p-6">
      <div>
        <h2 class="text-2xl font-bold">Attendance</h2>
        <p class="text-slate-400 text-sm">
          Data attendance hasil import dari file Excel (matriks bulanan atau export Attendance Log).
        </p>
      </div>

      <div class="rounded-lg border border-slate-700 bg-slate-800 p-4">
        <div class="flex flex-wrap items-end gap-3">
          <div class="w-40 shrink-0">
            <label class="text-xs text-slate-300">Tanggal Dari</label>
            <input v-model="form.date_from" type="date" class="mt-1 w-full rounded bg-slate-900 border border-slate-600 px-3 py-2 text-sm" />
          </div>
          <div class="w-40 shrink-0">
            <label class="text-xs text-slate-300">Sampai</label>
            <input v-model="form.date_to" type="date" class="mt-1 w-full rounded bg-slate-900 border border-slate-600 px-3 py-2 text-sm" />
          </div>
          <div class="w-48 shrink-0">
            <label class="text-xs text-slate-300">Status</label>
            <select v-model="form.status" class="mt-1 w-full rounded bg-slate-900 border border-slate-600 px-3 py-2 text-sm">
              <option value="all">Semua</option>
              <option v-for="status in statusOptions" :key="status" :value="status.toLowerCase()">{{ status }}</option>
            </select>
          </div>
          <div class="w-56 shrink-0">
            <label class="text-xs text-slate-300">Cari PIN / Nama</label>
            <input
              v-model="form.q"
              type="text"
              placeholder="PIN atau nama..."
              class="mt-1 w-full rounded bg-slate-900 border border-slate-600 px-3 py-2 text-sm"
              @keyup.enter="applyFilters"
            />
          </div>
          <template v-if="canImportAttendance">
            <div class="w-64 shrink-0">
              <label class="text-xs text-slate-300">File Excel / CSV</label>
              <input
                ref="fileInput"
                type="file"
                accept=".xlsx,.xls,.csv,.txt"
                class="mt-1 w-full rounded bg-slate-900 border border-slate-600 px-3 py-1.5 text-sm"
              />
            </div>
          </template>
          <div class="flex shrink-0 items-end gap-2">
            <button class="rounded bg-sky-600 px-4 py-2 text-sm font-semibold hover:bg-sky-500" @click="applyFilters">
              Tampilkan
            </button>
            <button class="rounded bg-slate-600 px-4 py-2 text-sm font-semibold hover:bg-slate-500" @click="resetFilters">
              Reset
            </button>
            <button class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold hover:bg-emerald-500" @click="exportExcel">
              Export Excel
            </button>
            <button
              v-if="canImportAttendance"
              class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold hover:bg-emerald-500 disabled:opacity-50"
              :disabled="importing"
              @click="previewImport"
            >
              {{ importing ? 'Memproses...' : 'Preview' }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="canImportAttendance && previewRows" class="rounded-lg border border-slate-700 bg-slate-800 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-sm font-semibold text-slate-200">Hasil Preview</p>
            <p class="mt-1 text-xs text-slate-400">
              <span class="font-medium text-slate-300">{{ previewPeriodLabel }}</span>
              <span class="ml-2">
                {{ previewSummary.valid_rows }} valid dari {{ previewSummary.total_preview_rows }} baris
              </span>
              <span v-if="previewSummary.invalid_rows > 0" class="text-rose-300">
                ({{ previewSummary.invalid_rows }} error)
              </span>
            </p>
          </div>
          <div class="flex gap-2">
            <button
              class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold hover:bg-emerald-500 disabled:opacity-50"
              :disabled="saving || !previewSummary.valid_rows"
              @click="saveImport"
            >
              {{ saving ? 'Menyimpan...' : 'Simpan ke Data Attendance' }}
            </button>
            <button class="rounded bg-slate-600 px-4 py-2 text-sm font-semibold hover:bg-slate-500" @click="clearPreview">
              Batal
            </button>
          </div>
        </div>

        <div v-if="previewInvalid.length" class="mt-4 rounded-lg border border-rose-700 bg-rose-950/40 p-3">
          <p class="text-xs font-semibold text-rose-300">Baris bermasalah (tidak akan disimpan):</p>
          <div class="mt-2 max-h-48 space-y-1 overflow-y-auto text-xs text-slate-300">
            <div v-for="(row, index) in previewInvalid" :key="index" class="flex justify-between gap-3 border-b border-rose-900/60 py-1 last:border-b-0">
              <span>{{ row.attendance_date_label }} — {{ row.pin || '-' }} {{ row.name }}</span>
              <span class="shrink-0 text-rose-300">{{ row.error }}</span>
            </div>
          </div>
        </div>

        <div class="mt-4 overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="border-b border-slate-700 text-slate-400">
              <tr>
                <th class="py-2 pr-3 text-left">Tanggal</th>
                <th class="py-2 pr-3 text-left">Hari</th>
                <th class="py-2 pr-3 text-left">PIN</th>
                <th class="py-2 pr-3 text-left">Nama</th>
                <th class="py-2 pr-3 text-left">Shift</th>
                <th class="py-2 pr-3 text-left">Masuk</th>
                <th class="py-2 pr-3 text-left">Pulang</th>
                <th class="py-2 pr-3 text-left">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, index) in previewRows.slice(0, 200)"
                :key="index"
                class="border-b border-slate-700 last:border-b-0"
                :class="row.is_valid ? '' : 'bg-rose-950/20'"
              >
                <td class="py-2 pr-3">{{ row.attendance_date_label }}</td>
                <td class="py-2 pr-3">{{ row.day_name }}</td>
                <td class="py-2 pr-3">{{ row.pin || '-' }}</td>
                <td class="py-2 pr-3">{{ row.name }}</td>
                <td class="py-2 pr-3">{{ row.shift_code || (row.is_off ? 'OFF' : '-') }}</td>
                <td class="py-2 pr-3">{{ row.check_in || '—' }}</td>
                <td class="py-2 pr-3">{{ row.check_out || '—' }}</td>
                <td class="py-2 pr-3">
                  <span v-if="row.is_valid" class="inline-flex rounded-full px-2 py-0.5 text-xs" :class="statusBadgeClass(row.status)">
                    {{ row.status || '-' }}
                  </span>
                  <span v-else class="text-xs text-rose-300">{{ row.error }}</span>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-if="previewRows.length > 200" class="mt-2 text-xs text-slate-400">
            Menampilkan 200 baris pertama dari {{ previewRows.length }} baris preview.
          </p>
        </div>
      </div>

      <div class="rounded-lg border border-slate-700 bg-slate-800 p-4">
        <div class="flex items-center justify-between">
          <p class="text-sm font-semibold text-slate-200">Data Attendance ({{ pagination.total }} karyawan)</p>
        </div>

        <div v-if="!groups.length" class="mt-4 text-sm text-slate-400">
          Tidak ada data untuk filter ini.
        </div>

        <div v-else class="mt-4 overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="border-b border-slate-700 text-slate-400">
              <tr>
                <th v-for="column in filterColumns" :key="column.key" class="py-2 pr-3 text-left">
                  <div class="inline-flex items-center gap-2">
                    <span>{{ column.label }}</span>
                    <button
                      type="button"
                      :aria-label="`Filter ${column.label}`"
                      :class="headerFilterButtonClass(isColumnFilterActive(column.key))"
                      @click.stop="toggleHeaderFilter(column.key, $event)"
                    >
                      <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 fill-current" aria-hidden="true">
                        <path d="M3 5h14l-5.5 6.2V16l-3-1v-3.8L3 5z" />
                      </svg>
                    </button>
                  </div>
                </th>
                <th class="py-2 pr-3 text-left">Absensi</th>
                <th class="py-2 pr-3 text-left">Terlambat</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="group in groups" :key="group.key">
                <tr class="cursor-pointer border-b border-slate-700/50 hover:bg-slate-700/30" @click="toggleExpand(group.key)">
                  <td class="py-2 pr-3">{{ group.pin || '-' }}</td>
                  <td class="py-2 pr-3">{{ group.name || '-' }}</td>
                  <td class="py-2 pr-3">{{ group.department_name || '-' }}</td>
                  <td class="py-2 pr-3">{{ group.supervisor_name || '-' }}</td>
                  <td class="py-2 pr-3">{{ group.total_records }}</td>
                  <td class="py-2 pr-3">
                    <span class="inline-flex min-w-8 justify-center rounded-md border border-amber-400/40 bg-amber-500/20 px-2 py-0.5 text-xs font-semibold text-amber-200">
                      {{ group.total_late }}
                    </span>
                    <span class="ml-2 text-slate-400">{{ expandedKeys.has(group.key) ? '▾' : '▸' }}</span>
                  </td>
                </tr>
                <tr v-if="expandedKeys.has(group.key)" class="border-b border-slate-700/50 bg-slate-900/30">
                  <td colspan="6" class="py-3">
                    <div class="overflow-x-auto">
                      <table class="w-full text-sm">
                        <thead class="border-b border-slate-700 text-slate-400">
                          <tr>
                            <th class="px-4 py-2 text-left">Tanggal</th>
                            <th class="px-4 py-2 text-left">Shift</th>
                            <th class="px-4 py-2 text-left">Hari</th>
                            <th class="px-4 py-2 text-left">Masuk</th>
                            <th class="px-4 py-2 text-left">Pulang</th>
                            <th class="px-4 py-2 text-left">Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="row in group.rows" :key="row.attendance_date" class="border-b border-slate-700 last:border-b-0">
                            <td class="px-4 py-2 text-slate-200">{{ row.attendance_date_label }}</td>
                            <td class="px-4 py-2">{{ row.shift_code || (row.is_off ? 'OFF' : '-') }}</td>
                            <td class="px-4 py-2 text-slate-300">{{ row.day_name }}</td>
                            <td class="px-4 py-2">{{ row.check_in || '—' }}</td>
                            <td class="px-4 py-2">{{ row.check_out || '—' }}</td>
                            <td class="px-4 py-2">
                              <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadgeClass(row.status)">
                                {{ row.status || '-' }}
                              </span>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>

          <Pagination
            v-if="pagination.last_page > 1"
            :paginator="pagination"
            :on-page-change="(page) => goToPage(page)"
          />
        </div>
      </div>

      <div v-if="canViewImportHistory && batches.length" class="rounded-lg border border-slate-700 bg-slate-800 p-4">
        <p class="text-sm font-semibold text-slate-200">Riwayat Import</p>
        <div class="mt-3 overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="border-b border-slate-700 text-slate-400">
              <tr>
                <th class="py-2 pr-3 text-left">File</th>
                <th class="py-2 pr-3 text-left">Periode</th>
                <th class="py-2 pr-3 text-right">Baris</th>
                <th class="py-2 pr-3 text-right">Tersimpan</th>
                <th class="py-2 pr-3 text-left">Oleh</th>
                <th class="py-2 pr-3 text-left">Waktu</th>
                <th class="py-2 pr-3 text-left"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="batch in batches" :key="batch.id" class="border-b border-slate-700 last:border-b-0">
                <td class="py-2 pr-3 text-slate-200">{{ batch.filename }}</td>
                <td class="py-2 pr-3">{{ monthNames[batch.month - 1] }} {{ batch.year }}</td>
                <td class="py-2 pr-3 text-right">{{ batch.valid_rows }}</td>
                <td class="py-2 pr-3 text-right">{{ batch.saved_rows }}</td>
                <td class="py-2 pr-3">{{ batch.uploaded_by }}</td>
                <td class="py-2 pr-3">{{ batch.imported_at }}</td>
                <td class="py-2 pr-3 text-right">
                  <button class="text-xs text-rose-400 hover:text-rose-300" @click="deleteBatch(batch)">Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <Teleport to="body">
        <div
          v-if="activeHeaderFilter"
          class="fixed z-100 w-72 rounded-lg border border-slate-600 bg-slate-900 p-3 shadow-2xl"
          :style="headerFilterPosition"
          @click.stop
        >
          <input
            v-model="headerFilterSearch"
            type="text"
            placeholder="Search"
            class="w-full rounded border border-slate-600 bg-slate-950 px-3 py-2 text-sm text-slate-200"
          />
          <div class="mt-3 max-h-56 overflow-y-auto rounded border border-slate-700 bg-slate-950/70 p-2">
            <label class="flex cursor-pointer items-center gap-2 px-1 py-1 text-sm text-slate-200">
              <input type="checkbox" :checked="activeHeaderAllSelected" @change="toggleHeaderSelectAll" />
              <span>(Select All)</span>
            </label>
            <label
              v-for="option in activeHeaderOptions"
              :key="option"
              class="flex cursor-pointer items-center gap-2 px-1 py-1 text-sm text-slate-200"
            >
              <input type="checkbox" :checked="activeHeaderDraft.includes(option)" @change="toggleHeaderOption(option)" />
              <span>{{ option }}</span>
            </label>
            <div v-if="!activeHeaderOptions.length" class="px-1 py-2 text-sm text-slate-400">
              Tidak ada opsi.
            </div>
          </div>
          <div class="mt-3 flex justify-end">
            <button type="button" class="rounded bg-sky-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-sky-500" @click="applyHeaderFilter">
              OK
            </button>
          </div>
        </div>
      </Teleport>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  groups: { type: Array, default: () => [] },
  pagination: { type: Object, default: () => ({}) },
  summary: { type: Object, default: () => ({}) },
  statusOptions: { type: Array, default: () => [] },
  filterOptions: { type: Object, default: () => ({}) },
  columnFilters: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  canImportAttendance: { type: Boolean, default: false },
  canViewImportHistory: { type: Boolean, default: false },
  batches: { type: Array, default: () => [] },
});

const filterColumns = [
  { key: 'pins', label: 'PIN' },
  { key: 'names', label: 'Nama' },
  { key: 'departments', label: 'Department' },
  { key: 'reporting_tos', label: 'Reporting To' },
];
const activeHeaderFilter = ref('');
const headerFilterSearch = ref('');
const headerFilterPosition = reactive({ left: '12px', top: '12px' });
const selectedColumnFilters = reactive({ pins: [], names: [], departments: [], reporting_tos: [] });
const draftColumnFilters = reactive({ pins: [], names: [], departments: [], reporting_tos: [] });

function syncColumnFiltersFromProps() {
  filterColumns.forEach(({ key }) => {
    const options = props.filterOptions?.[key] || [];
    const filter = props.columnFilters?.[key];
    const selected = filter?.active ? (filter.selected || []) : options;
    selectedColumnFilters[key] = [...selected];
    draftColumnFilters[key] = [...selected];
  });
}

watch(() => [props.filterOptions, props.columnFilters], syncColumnFiltersFromProps, { deep: true, immediate: true });

const activeHeaderOptions = computed(() => {
  const options = props.filterOptions?.[activeHeaderFilter.value] || [];
  const query = headerFilterSearch.value.trim().toLowerCase();
  return query ? options.filter((option) => String(option).toLowerCase().includes(query)) : options;
});
const activeHeaderDraft = computed(() => draftColumnFilters[activeHeaderFilter.value] || []);
const activeHeaderAllSelected = computed(() =>
  activeHeaderOptions.value.length > 0
    && activeHeaderOptions.value.every((option) => activeHeaderDraft.value.includes(option)),
);

const monthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const currentYear = new Date().getFullYear();
const currentMonth = new Date().getMonth() + 1;
const currentMonthStart = new Date(currentYear, currentMonth - 1, 1);
const currentMonthEnd = new Date(currentYear, currentMonth, 0);

function formatDateInput(date) {
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${date.getFullYear()}-${month}-${day}`;
}

const form = reactive({
  date_from: props.filters.date_from || formatDateInput(currentMonthStart),
  date_to: props.filters.date_to || formatDateInput(currentMonthEnd),
  status: props.filters.status || 'all',
  q: props.filters.q || '',
});

const fileInput = ref(null);
const previewKey = ref(null);
const previewRows = ref(null);
const previewPeriod = ref(null);
const previewSummary = ref({ total_preview_rows: 0, valid_rows: 0, invalid_rows: 0 });
const importing = ref(false);
const saving = ref(false);
const expandedKeys = ref(new Set());

const previewPeriodLabel = computed(() => {
  const period = previewPeriod.value;
  if (!period) return '';
  const label = `${monthNames[(period.month || 1) - 1]} ${period.year}`;
  return `Periode: ${label} (${period.source === 'file' ? 'dari file' : 'filter aktif'})`;
});

const previewInvalid = computed(() => (previewRows.value || []).filter((row) => !row.is_valid));

function formatSchedule(start, end) {
  if (!start || !end) return '-';
  return `${start} - ${end}`;
}

function isColumnFilterActive(key) {
  const options = props.filterOptions?.[key] || [];
  const selected = selectedColumnFilters[key] || [];
  return !(options.length === selected.length && options.every((option) => selected.includes(option)));
}

function columnFilterQuery() {
  const query = {};
  filterColumns.forEach(({ key }) => {
    const active = isColumnFilterActive(key);
    query[`filter_${key}`] = active;
    if (active) {
      query[key] = selectedColumnFilters[key];
    }
  });
  return query;
}

function headerFilterButtonClass(isActive) {
  return [
    'inline-flex h-6 w-6 items-center justify-center rounded border transition',
    isActive
      ? 'border-sky-400/60 bg-sky-500/20 text-sky-200'
      : 'border-slate-600 bg-slate-900 text-slate-400 hover:text-slate-200',
  ];
}

function toggleHeaderFilter(key, event) {
  if (activeHeaderFilter.value === key) {
    activeHeaderFilter.value = '';
    return;
  }

  activeHeaderFilter.value = key;
  headerFilterSearch.value = '';
  draftColumnFilters[key] = [...selectedColumnFilters[key]];

  const bounds = event.currentTarget.getBoundingClientRect();
  const popupHeight = 320;
  const left = Math.max(12, Math.min(window.innerWidth - 300, bounds.left));
  const top = bounds.bottom + popupHeight + 12 <= window.innerHeight
    ? bounds.bottom + 8
    : Math.max(12, bounds.top - popupHeight - 8);
  headerFilterPosition.left = `${left}px`;
  headerFilterPosition.top = `${top}px`;
}

function toggleHeaderOption(option) {
  const selected = draftColumnFilters[activeHeaderFilter.value];
  draftColumnFilters[activeHeaderFilter.value] = selected.includes(option)
    ? selected.filter((value) => value !== option)
    : [...selected, option];
}

function toggleHeaderSelectAll() {
  const key = activeHeaderFilter.value;
  const visible = activeHeaderOptions.value;
  if (activeHeaderAllSelected.value) {
    draftColumnFilters[key] = draftColumnFilters[key].filter((value) => !visible.includes(value));
    return;
  }

  draftColumnFilters[key] = Array.from(new Set([...draftColumnFilters[key], ...visible]));
}

function applyHeaderFilter() {
  const key = activeHeaderFilter.value;
  if (!key) return;

  selectedColumnFilters[key] = [...draftColumnFilters[key]];
  activeHeaderFilter.value = '';

  router.get('/attendance', {
    date_from: form.date_from,
    date_to: form.date_to,
    status: form.status,
    q: form.q,
    page: 1,
    ...columnFilterQuery(),
  }, { preserveState: true, preserveScroll: true });
}

function applyFilters() {
  router.get('/attendance', {
    date_from: form.date_from,
    date_to: form.date_to,
    status: form.status,
    q: form.q,
    ...columnFilterQuery(),
  }, { preserveState: true, preserveScroll: true });
}

function exportExcel() {
  const params = new URLSearchParams({
    export: 'excel',
    date_from: form.date_from,
    date_to: form.date_to,
    status: form.status,
    q: form.q,
  });

  Object.entries(columnFilterQuery()).forEach(([key, value]) => {
    if (Array.isArray(value)) {
      value.forEach((option) => params.append(`${key}[]`, option));
    } else if (value) {
      params.set(key, '1');
    }
  });

  window.location.href = `/attendance?${params.toString()}`;
}

function resetFilters() {
  const now = new Date();
  form.date_from = formatDateInput(new Date(now.getFullYear(), now.getMonth(), 1));
  form.date_to = formatDateInput(new Date(now.getFullYear(), now.getMonth() + 1, 0));
  form.status = 'all';
  form.q = '';
  applyFilters();
}

function goToPage(page) {
  router.get('/attendance', {
    page,
    date_from: form.date_from,
    date_to: form.date_to,
    status: form.status,
    q: form.q,
    ...columnFilterQuery(),
  }, { preserveState: true, preserveScroll: true });
}

function importFallbackPeriod() {
  const [, yearValue, monthValue] = String(form.date_from || '').match(/^(\d{4})-(\d{2})-\d{2}$/) || [];
  const year = Number(yearValue);
  const month = Number(monthValue);

  if (year >= 2000 && year <= 2100 && month >= 1 && month <= 12) {
    return { month, year };
  }

  return { month: currentMonth, year: currentYear };
}

async function previewImport() {
  const file = fileInput.value?.files?.[0];
  if (!file) {
    Swal.fire('Ops', 'Pilih file Excel/CSV terlebih dahulu.', 'warning');
    return;
  }

  const data = new FormData();
  const importPeriod = importFallbackPeriod();
  data.append('month', importPeriod.month);
  data.append('year', importPeriod.year);
  data.append('file', file);

  importing.value = true;
  try {
    const response = await fetch('/attendance/import/preview', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: data,
    });
    const json = await response.json();
    if (!response.ok) {
      Swal.fire('Gagal Preview', json.message || 'Tidak ada data attendance yang terbaca.', 'error');
      return;
    }
    previewKey.value = json.preview_key;
    previewRows.value = json.rows;
    previewPeriod.value = {
      month: json.month,
      year: json.year,
      source: json.period_source || 'file',
    };
    previewSummary.value = json.summary;
  } catch (error) {
    Swal.fire('Gagal Preview', 'Terjadi kesalahan saat memproses file.', 'error');
  } finally {
    importing.value = false;
  }
}

async function saveImport() {
  if (!previewKey.value || !previewPeriod.value) return;

  saving.value = true;
  try {
    const response = await fetch('/attendance/import/store', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify({
        preview_key: previewKey.value,
        month: previewPeriod.value.month,
        year: previewPeriod.value.year,
      }),
    });
    const json = await response.json();
    if (!response.ok) {
      if (json.message && json.message.includes('kadaluarsa')) {
        Swal.fire('Data Kemungkinan Sudah Tersimpan', 'Sesi preview kadaluarsa — data kemungkinan besar sudah tersimpan sebelumnya. Halaman akan dimuat ulang untuk memeriksa.', 'info');
        showImportedPeriod(previewPeriod.value.month, previewPeriod.value.year);
        return;
      }
      Swal.fire('Gagal Simpan', json.message || 'Terjadi kesalahan saat menyimpan.', 'error');
      return;
    }
    const savedMonth = previewPeriod.value.month;
    const savedYear = previewPeriod.value.year;
    clearPreview();
    Swal.fire('Berhasil', json.message, 'success');
    showImportedPeriod(savedMonth, savedYear);
  } catch (error) {
    console.error('Attendance save error:', error);
    const detail = error && error.message ? error.message : String(error);
    Swal.fire('Gagal Simpan', `Respons server tidak terbaca (${detail}). Periksa apakah data sudah masuk lewat "Cari" atau refresh, lalu coba Simpan lagi atau upload ulang.`, 'error');
  } finally {
    saving.value = false;
  }
}

function showImportedPeriod(month, year) {
  const paddedMonth = String(month).padStart(2, '0');
  const lastDay = new Date(Number(year), Number(month), 0).getDate();
  form.date_from = `${year}-${paddedMonth}-01`;
  form.date_to = `${year}-${paddedMonth}-${String(lastDay).padStart(2, '0')}`;
  form.status = 'all';
  form.q = '';

  router.get('/attendance', {
    date_from: form.date_from,
    date_to: form.date_to,
    status: form.status,
    q: form.q,
  }, { preserveScroll: true });
}

function clearPreview() {
  previewKey.value = null;
  previewRows.value = null;
  previewPeriod.value = null;
  previewSummary.value = { total_preview_rows: 0, valid_rows: 0, invalid_rows: 0 };
  if (fileInput.value) fileInput.value.value = '';
}

function toggleExpand(key) {
  const next = new Set(expandedKeys.value);
  if (next.has(key)) next.delete(key);
  else next.add(key);
  expandedKeys.value = next;
}

function deleteBatch(batch) {
  Swal.fire({
    title: 'Hapus batch import?',
    text: `Semua ${batch.saved_rows} data attendance dari ${batch.filename} akan dihapus.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus',
    cancelButtonText: 'Batal',
  }).then((result) => {
    if (!result.isConfirmed) return;
    router.delete(`/attendance/import/batches/${batch.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        Swal.fire('Terhapus', 'Batch import berhasil dihapus.', 'success');
      },
    });
  });
}

function statusBadgeClass(status) {
  const normalized = (status || '').toLowerCase();
  const classes = 'bg-slate-700/60 text-slate-300';
  if (normalized === '' || normalized === 'cek lagi' || normalized === 'cek_lagi') return 'bg-amber-600/20 text-amber-300';
  if (normalized === 'on time' || normalized === 'ontime') return 'bg-emerald-600/20 text-emerald-300';
  if (normalized === 'terlambat') return 'bg-rose-600/20 text-rose-300';
  if (normalized === 'off' || normalized === 'libur nasional') return 'bg-indigo-600/20 text-indigo-300';
  if (['cuti', 'izin', 'sakit'].includes(normalized)) return 'bg-sky-600/20 text-sky-300';
  if (normalized === 'dinas luar' || normalized === 'dinas') return 'bg-purple-600/20 text-purple-300';
  if (normalized.includes('tidak scan')) return 'bg-orange-600/20 text-orange-300';
  return classes;
}
</script>