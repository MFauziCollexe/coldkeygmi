<template>
  <AppLayout>
    <div class="space-y-6 p-4 md:p-6">
      <div>
        <h2 class="text-2xl font-bold">Roster Upload</h2>
        <p class="text-slate-400 text-sm">Preview roster lalu upload. Data akan masuk ke antrean approval manager departemen.</p>
      </div>

      <div class="rounded-lg border border-slate-700 bg-slate-800 p-4 space-y-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="relative group">
            <SearchableSelect
              v-model="form.month"
              :options="months"
              option-value="value"
              option-label="label"
              placeholder=" "
              empty-label="Pilih Bulan"
              input-class="w-full pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100"
              button-class="border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
            />
            <label
              :class="[
                'pointer-events-none absolute left-3 z-10 transition-all',
                (form.month
                  ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                  : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
              ]"
            >
              Bulan
            </label>
          </div>
          <div class="relative group">
            <SearchableSelect
              v-model="form.year"
              :options="yearOptions"
              option-value="value"
              option-label="label"
              placeholder=" "
              empty-label="Pilih Tahun"
              input-class="w-full pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100"
              button-class="border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
            />
            <label
              :class="[
                'pointer-events-none absolute left-3 z-10 transition-all',
                (form.year
                  ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                  : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
              ]"
            >
              Tahun
            </label>
          </div>
        </div>

        <RosterFileDropzone :file-name="form.fileName" @file-selected="setSelectedFile" />

        <div class="relative">
          <textarea
            v-model="form.changeReason"
            rows="2"
            class="peer w-full px-3 pt-6 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
            placeholder=" "
          />
          <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-4 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">
            Alasan Perubahan (wajib jika periode sudah pernah approved)
          </label>
        </div>

        <RosterActionBar
          :template-type="form.templateType"
          :is-preview-loading="isPreviewLoading"
          :is-upload-loading="isUploadLoading"
          :has-file="!!form.file"
          :can-upload="!!previewKey"
          :delimiter="detectedDelimiter"
          @update:templateType="form.templateType = $event"
          @download-template="downloadTemplate"
          @preview="previewFile"
          @upload="uploadPreview"
        />

        <p v-if="message.text" :class="message.type === 'error' ? 'text-rose-300' : 'text-emerald-300'" class="text-sm">
          {{ message.text }}
        </p>
      </div>

      <RosterPreviewMatrix
        :rows="previewRows"
        :matrix="previewMatrix"
        :summary="previewSummary"
        :columns="previewColumns"
        :month-label="monthLabel"
        :year="form.year"
        @shift-input="onShiftInput"
      />
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import RosterFileDropzone from '@/Pages/GMIHR/Roster/Components/RosterFileDropzone.vue';
import RosterActionBar from '@/Pages/GMIHR/Roster/Components/RosterActionBar.vue';
import RosterPreviewMatrix from '@/Pages/GMIHR/Roster/Components/RosterPreviewMatrix.vue';

const now = new Date();
const isPreviewLoading = ref(false);
const isUploadLoading = ref(false);
const previewRows = ref([]);
const previewSummary = reactive({
  total_preview_rows: 0,
  valid_rows: 0,
  invalid_rows: 0,
});
const previewKey = ref('');
const detectedDelimiter = ref('');
const message = reactive({ type: '', text: '' });

const form = reactive({
  month: now.getMonth() + 1,
  year: now.getFullYear(),
  templateType: '',
  file: null,
  fileName: '',
  changeReason: '',
});

const years = computed(() => {
  const current = now.getFullYear();
  return Array.from({ length: 6 }, (_, i) => current - 2 + i);
});

const yearOptions = computed(() => years.value.map((year) => ({ value: year, label: String(year) })));

const months = [
  { value: 1, label: 'Januari' }, { value: 2, label: 'Februari' }, { value: 3, label: 'Maret' },
  { value: 4, label: 'April' }, { value: 5, label: 'Mei' }, { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' }, { value: 8, label: 'Agustus' }, { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' }, { value: 11, label: 'November' }, { value: 12, label: 'Desember' },
];

const monthLabel = computed(() => months.find((m) => m.value === Number(form.month))?.label || '');

const previewColumns = computed(() => {
  const totalDays = new Date(Number(form.year), Number(form.month), 0).getDate();
  const labels = ['MIN', 'SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB'];
  return Array.from({ length: totalDays }, (_, i) => {
    const day = i + 1;
    const date = new Date(Number(form.year), Number(form.month) - 1, day);
    return { day, dayName: labels[date.getDay()] };
  });
});

const previewMatrix = computed(() => {
  const map = new Map();
  for (const row of previewRows.value) {
    if (!map.has(row.employee_key)) {
      map.set(row.employee_key, {
        employee_key: row.employee_key,
        employee_name: row.employee_name,
        employee_nrp: row.employee_nrp,
        days: {},
      });
    }
    const employee = map.get(row.employee_key);
    const day = Number((row.roster_date || '').split('-')[2]);
    if (!Number.isNaN(day) && day > 0) {
      employee.days[day] = { row };
    }
  }
  return Array.from(map.values());
});

function resetPreviewState() {
  previewRows.value = [];
  previewSummary.total_preview_rows = 0;
  previewSummary.valid_rows = 0;
  previewSummary.invalid_rows = 0;
  previewKey.value = '';
  detectedDelimiter.value = '';
}

function setSelectedFile(file) {
  message.type = '';
  message.text = '';
  resetPreviewState();
  form.file = file;
  form.fileName = file?.name || '';
  if (file?.name) {
    form.templateType = detectTemplateTypeFromFilename(file.name) || form.templateType;
  }
}

async function previewFile() {
  if (!form.file) {
    message.type = 'error';
    message.text = 'Pilih file CSV/Excel terlebih dahulu.';
    return;
  }

  message.type = '';
  message.text = '';
  isPreviewLoading.value = true;
  resetPreviewState();

  const payload = new FormData();
  payload.append('month', String(form.month));
  payload.append('year', String(form.year));
  payload.append('template_type', String(form.templateType || 'inventory'));
  payload.append('file', form.file);

  try {
    const response = await axios.post('/roster/preview', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    previewRows.value = (response.data.rows || []).map((row) => normalizeRowOnClient({
      employee_key: row.employee_key,
      employee_nrp: row.employee_nrp,
      employee_name: row.employee_name,
      roster_date: row.roster_date,
      shift_code: (row.shift_code || '').toUpperCase(),
    }));
    if (response.data?.month && response.data?.year) {
      form.month = Number(response.data.month);
      form.year = Number(response.data.year);
    }
    previewKey.value = response.data.preview_key || '';
    detectedDelimiter.value = response.data.delimiter || '';
    previewSummary.total_preview_rows = response.data.summary?.total_preview_rows || 0;
    previewSummary.valid_rows = response.data.summary?.valid_rows || 0;
    previewSummary.invalid_rows = response.data.summary?.invalid_rows || 0;

    message.type = 'success';
    message.text = 'Preview berhasil. Silakan cek data lalu klik Upload.';
  } catch (error) {
    message.type = 'error';
    message.text = error?.response?.data?.message || 'Preview gagal.';
  } finally {
    isPreviewLoading.value = false;
  }
}

async function uploadPreview() {
  if (!previewKey.value) {
    message.type = 'error';
    message.text = 'Harus klik preview dulu sebelum upload.';
    return;
  }

  message.type = '';
  message.text = '';
  isUploadLoading.value = true;

  try {
    const response = await axios.post('/roster/upload', {
      month: form.month,
      year: form.year,
      template_type: form.templateType,
      preview_key: previewKey.value,
      edited_rows: previewRows.value.map((row) => ({
        employee_key: row.employee_key,
        employee_nrp: row.employee_nrp,
        employee_name: row.employee_name,
        roster_date: row.roster_date,
        shift_code: row.shift_code,
      })),
      change_reason: form.changeReason,
    });

    message.type = 'success';
    message.text = response.data.message || 'Upload berhasil.';
    resetPreviewState();
  } catch (error) {
    message.type = 'error';
    message.text = error?.response?.data?.message || 'Upload gagal.';
  } finally {
    isUploadLoading.value = false;
  }
}

function downloadTemplate() {
  const params = new URLSearchParams({
    month: String(form.month),
    year: String(form.year),
    type: String(form.templateType),
    _t: String(Date.now()),
  });
  window.location.href = `/roster/template?${params.toString()}`;
}

function onShiftInput(row, value) {
  if (!row) return;
  row.shift_code = String(value || '').trim().toUpperCase();
  const recalculated = normalizeRowOnClient(row);
  row.is_off = recalculated.is_off;
  row.start_time = recalculated.start_time;
  row.end_time = recalculated.end_time;
  row.work_hours = recalculated.work_hours;
  row.is_valid = recalculated.is_valid;
  row.error = recalculated.error;
}

function normalizeRowOnClient(row) {
  const shiftCode = String(row.shift_code || '').trim().toUpperCase();
  const date = new Date(`${row.roster_date}T00:00:00`);
  const isDateValid = !Number.isNaN(date.getTime());
  const isSaturday = isDateValid ? date.getDay() === 6 : false;
  const defaultHours = isSaturday ? 4 : 8;

  const normalized = {
    ...row,
    shift_code: shiftCode,
    is_off: false,
    start_time: null,
    end_time: null,
    work_hours: 0,
    is_valid: true,
    error: null,
  };

  if (!isDateValid) {
    normalized.is_valid = false;
    normalized.error = 'Tanggal tidak valid';
    return normalized;
  }

  if (shiftCode === 'OFF' || shiftCode === 'NONE') {
    normalized.is_off = true;
    return normalized;
  }

  if (!/^\d+$/.test(shiftCode)) {
    normalized.is_valid = false;
    normalized.error = `Kode shift tidak dikenali: ${shiftCode}`;
    return normalized;
  }

  const hour = Number(shiftCode);
  if (hour < 0 || hour > 23) {
    normalized.is_valid = false;
    normalized.error = `Jam tidak valid: ${shiftCode}`;
    return normalized;
  }

  const pad = (n) => String(n).padStart(2, '0');
  normalized.start_time = `${pad(hour)}:00:00`;
  normalized.end_time = `${pad((hour + defaultHours) % 24)}:00:00`;
  normalized.work_hours = defaultHours;
  return normalized;
}

function detectTemplateTypeFromFilename(filename) {
  const name = String(filename || '').toLowerCase();
  if (name.includes('admin_loket') || name.includes('admin-loket') || name.includes('loket')) return 'admin_loket';
  if (name.includes('risk_control') || name.includes('risk-control') || name.includes('risk')) return 'risk_control';
  if (name.includes('maintanance') || name.includes('maintenance') || name.includes('mnt')) return 'maintanance';
  if (name.includes('security') || name.includes('satpam')) return 'security';
  if (name.includes('inventory') || name.includes('inv')) return 'inventory';
  return null;
}
</script>
