<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div>
        <h2 class="text-2xl font-bold">Roster List</h2>
        <p class="text-slate-400 text-sm">
          Menampilkan roster untuk {{ departmentName === 'Semua Departemen' ? 'semua departemen' : `departemen ${departmentName || '-'}` }}.
        </p>
      </div>

      <div class="bg-slate-800 rounded-lg p-4 border border-slate-700">
        <div v-if="!batches.length" class="text-slate-400 text-sm">Belum ada data roster.</div>
        <div v-else class="overflow-auto">
          <table class="w-full text-sm">
            <thead class="text-slate-400 border-b border-slate-700">
              <tr>
                <th class="text-left py-2 pr-3">Periode</th>
                <th class="text-left py-2 pr-3">Versi</th>
                <th class="text-left py-2 pr-3">File</th>
                <th class="text-left py-2 pr-3">Status</th>
                <th class="text-left py-2 pr-3">Rows</th>
                <th class="text-left py-2 pr-3">Uploader</th>
                <th class="text-left py-2 pr-3">Approver</th>
                <th class="text-left py-2 pr-3">Catatan</th>
                <th class="text-left py-2 pr-3">Waktu</th>
                <th class="text-left py-2">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="batch in batches" :key="batch.id" class="border-b border-slate-700/60">
                <td class="py-2 pr-3">{{ batch.month }}/{{ batch.year }}</td>
                <td class="py-2 pr-3">
                  <span class="font-semibold">v{{ batch.version || 1 }}</span>
                  <span v-if="batch.is_current" class="ml-2 text-xs px-2 py-0.5 rounded bg-emerald-700/40 text-emerald-200">Current</span>
                </td>
                <td class="py-2 pr-3">
                  <a
                    :href="`/roster/${batch.id}/download`"
                    class="text-sky-300 hover:text-sky-200 underline decoration-dotted"
                    :title="`Download ${batch.filename || 'file roster'}`"
                  >
                    {{ batch.filename }}
                  </a>
                </td>
                <td class="py-2 pr-3">
                  <span :class="statusClass(batch.status)" class="px-2 py-1 rounded text-xs font-semibold">
                    {{ statusLabel(batch.status) }}
                  </span>
                </td>
                <td class="py-2 pr-3">{{ batch.saved_rows }}/{{ batch.total_rows }}</td>
                <td class="py-2 pr-3">{{ batch.uploader?.name || '-' }}</td>
                <td class="py-2 pr-3">{{ batch.approver?.name || '-' }}</td>
                <td class="py-2 pr-3 max-w-[320px]">
                  <span v-if="batch.status === 'rejected'">{{ batch.reject_reason || '-' }}</span>
                  <span v-else>{{ batch.change_reason || '-' }}</span>
                </td>
                <td class="py-2 pr-3">{{ formatDate(batch.created_at) }}</td>
                <td class="py-2 flex items-center gap-2">
                  <button
                    class="px-3 py-1.5 rounded bg-sky-600 text-white hover:bg-sky-500 disabled:opacity-60"
                    :disabled="viewLoadingId === batch.id"
                    @click="viewBatch(batch)"
                  >
                    {{ viewLoadingId === batch.id ? 'Loading...' : 'View' }}
                  </button>
                  <button
                    v-if="batch.can_approve && batch.status === 'pending'"
                    class="px-3 py-1.5 rounded bg-emerald-600 text-white hover:bg-emerald-500 disabled:opacity-60"
                    :disabled="loadingId === batch.id"
                    @click="approveBatch(batch)"
                  >
                    {{ loadingId === batch.id ? 'Approving...' : 'Approve' }}
                  </button>
                  <button
                    v-if="batch.can_approve && batch.status === 'pending'"
                    class="px-3 py-1.5 rounded bg-rose-600 text-white hover:bg-rose-500 disabled:opacity-60"
                    :disabled="rejectingId === batch.id"
                    @click="rejectBatch(batch)"
                  >
                    {{ rejectingId === batch.id ? 'Rejecting...' : 'Reject' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <p v-if="message.text" :class="message.type === 'error' ? 'text-rose-300' : 'text-emerald-300'" class="text-sm">
        {{ message.text }}
      </p>

      <div v-if="showViewModal" class="fixed inset-0 z-50 bg-black/70 p-4 md:p-8 overflow-auto" @click.self="closeViewModal">
        <div class="max-w-[96vw] mx-auto bg-slate-900 border border-slate-700 rounded-lg p-4">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold">Roster View - {{ viewBatchInfo.month }}/{{ viewBatchInfo.year }}</h3>
            <div class="flex items-center gap-2">
              <button
                class="px-3 py-1.5 rounded bg-emerald-600 text-white hover:bg-emerald-500 disabled:opacity-60"
                :disabled="isExportingImage"
                @click="exportViewAsImage()"
              >
                {{ isExportingImage ? 'Saving...' : 'Save Image' }}
              </button>
              <button class="px-3 py-1.5 rounded bg-slate-700 hover:bg-slate-600" @click="closeViewModal">Close</button>
            </div>
          </div>

          <div ref="captureTarget">
            <RosterPreviewMatrix
              :rows="viewRows"
              :matrix="viewMatrix"
              :summary="viewSummary"
              :columns="viewColumns"
              :month-label="monthLabel(viewBatchInfo.month)"
              :year="viewBatchInfo.year"
              :readonly="true"
              :show-summary="false"
            />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import html2canvas from 'html2canvas';
import { toPng, toJpeg } from 'html-to-image';
import AppLayout from '@/Layouts/AppLayout.vue';
import RosterPreviewMatrix from '@/Pages/GMIHR/Roster/Components/RosterPreviewMatrix.vue';

const props = defineProps({
  batches: {
    type: Array,
    default: () => [],
  },
  canApprove: {
    type: Boolean,
    default: false,
  },
  departmentName: {
    type: String,
    default: '',
  },
});

const loadingId = ref(null);
const rejectingId = ref(null);
const viewLoadingId = ref(null);
const message = reactive({ type: '', text: '' });
const showViewModal = ref(false);
const viewRows = ref([]);
const captureTarget = ref(null);
const isExportingImage = ref(false);
const viewBatchInfo = reactive({ month: 0, year: 0 });
const viewSummary = reactive({
  total_preview_rows: 0,
  valid_rows: 0,
  invalid_rows: 0,
});

const viewColumns = computed(() => {
  if (!viewBatchInfo.month || !viewBatchInfo.year) return [];
  const totalDays = new Date(Number(viewBatchInfo.year), Number(viewBatchInfo.month), 0).getDate();
  const labels = ['MIN', 'SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB'];
  return Array.from({ length: totalDays }, (_, i) => {
    const day = i + 1;
    const date = new Date(Number(viewBatchInfo.year), Number(viewBatchInfo.month) - 1, day);
    return { day, dayName: labels[date.getDay()] };
  });
});

const viewMatrix = computed(() => {
  const map = new Map();
  for (const row of viewRows.value) {
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

const months = [
  { value: 1, label: 'Januari' }, { value: 2, label: 'Februari' }, { value: 3, label: 'Maret' },
  { value: 4, label: 'April' }, { value: 5, label: 'Mei' }, { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' }, { value: 8, label: 'Agustus' }, { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' }, { value: 11, label: 'November' }, { value: 12, label: 'Desember' },
];

function formatDate(value) {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '-';
  return date.toLocaleString();
}

function monthLabel(month) {
  return months.find((m) => Number(m.value) === Number(month))?.label || '';
}

function statusClass(status) {
  if (status === 'approved') return 'bg-emerald-600/30 text-emerald-200';
  if (status === 'pending') return 'bg-amber-600/30 text-amber-200';
  if (status === 'rejected') return 'bg-rose-600/30 text-rose-200';
  return 'bg-slate-600/30 text-slate-200';
}

function statusLabel(status) {
  if (status === 'approved') return 'Approved';
  if (status === 'pending') return 'Pending Manager';
  if (status === 'rejected') return 'Rejected';
  return status || '-';
}

function closeViewModal() {
  showViewModal.value = false;
  viewRows.value = [];
  viewBatchInfo.month = 0;
  viewBatchInfo.year = 0;
}

async function viewBatch(batch) {
  if (!batch?.id) return;
  message.type = '';
  message.text = '';
  viewLoadingId.value = batch.id;
  try {
    const response = await axios.get(`/roster/${batch.id}/view`);
    viewRows.value = response.data?.rows || [];
    viewBatchInfo.month = Number(response.data?.batch?.month || batch.month || 0);
    viewBatchInfo.year = Number(response.data?.batch?.year || batch.year || 0);
    viewSummary.total_preview_rows = response.data?.summary?.total_preview_rows || 0;
    viewSummary.valid_rows = response.data?.summary?.valid_rows || 0;
    viewSummary.invalid_rows = response.data?.summary?.invalid_rows || 0;
    showViewModal.value = true;
  } catch (error) {
    message.type = 'error';
    message.text = error?.response?.data?.message || 'Gagal memuat detail roster.';
  } finally {
    viewLoadingId.value = null;
  }
}

async function approveBatch(batch) {
  if (!batch?.id) return;
  message.type = '';
  message.text = '';
  loadingId.value = batch.id;
  try {
    const response = await axios.post(`/roster/${batch.id}/approve`);
    message.type = 'success';
    message.text = response.data?.message || 'Roster berhasil di-approve.';
    router.reload({ only: ['batches'] });
  } catch (error) {
    message.type = 'error';
    message.text = error?.response?.data?.message || 'Approve roster gagal.';
  } finally {
    loadingId.value = null;
  }
}

async function rejectBatch(batch) {
  if (!batch?.id) return;
  const result = await Swal.fire({
    title: 'Reject Roster',
    input: 'textarea',
    inputLabel: 'Alasan reject',
    inputPlaceholder: 'Tuliskan alasan kenapa roster di-reject...',
    inputAttributes: {
      maxlength: 1000,
    },
    showCancelButton: true,
    confirmButtonText: 'Reject',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#dc2626',
    preConfirm: (value) => {
      const reason = String(value || '').trim();
      if (!reason) {
        Swal.showValidationMessage('Alasan reject wajib diisi');
        return false;
      }
      return reason;
    },
  });

  if (!result.isConfirmed) return;

  message.type = '';
  message.text = '';
  rejectingId.value = batch.id;
  try {
    const response = await axios.post(`/roster/${batch.id}/reject`, {
      reason: result.value,
    });
    message.type = 'success';
    message.text = response.data?.message || 'Roster berhasil di-reject.';
    router.reload({ only: ['batches'] });
  } catch (error) {
    message.type = 'error';
    message.text = error?.response?.data?.message || 'Reject roster gagal.';
  } finally {
    rejectingId.value = null;
  }
}

async function exportViewAsImage() {
  const format = 'png';
  const target = captureTarget.value;
  if (!target) return;
  isExportingImage.value = true;
  try {
    const canvas = await html2canvas(target, {
      backgroundColor: '#0f172a',
      scale: 1.5,
      useCORS: true,
      logging: false,
      scrollX: 0,
      scrollY: -window.scrollY,
    });

    const extension = format === 'jpg' ? 'jpg' : 'png';
    const mimeType = format === 'jpg' ? 'image/jpeg' : 'image/png';
    const quality = format === 'jpg' ? 0.92 : 1;
    const month = String(viewBatchInfo.month || '').padStart(2, '0');
    const year = String(viewBatchInfo.year || '');
    const filename = `roster_view_${year}_${month}.${extension}`;

    const dataUrl = canvas.toDataURL(mimeType, quality);
    if (!dataUrl || dataUrl === 'data:,') {
      throw new Error('Hasil export kosong');
    }
    const link = document.createElement('a');
    link.href = dataUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (error) {
    try {
      const month = String(viewBatchInfo.month || '').padStart(2, '0');
      const year = String(viewBatchInfo.year || '');
      const extension = format === 'jpg' ? 'jpg' : 'png';
      const filename = `roster_view_${year}_${month}.${extension}`;
      const dataUrl = format === 'jpg'
        ? await toJpeg(target, { quality: 0.92, backgroundColor: '#0f172a', cacheBust: true, pixelRatio: 1.5 })
        : await toPng(target, { backgroundColor: '#0f172a', cacheBust: true, pixelRatio: 1.5 });

      if (!dataUrl || dataUrl === 'data:,') {
        throw new Error('Fallback export kosong');
      }

      const link = document.createElement('a');
      link.href = dataUrl;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      return;
    } catch (fallbackError) {
      const detail = fallbackError?.message || error?.message || 'Unknown error';
      Swal.fire({
        icon: 'error',
        title: 'Gagal Export',
        text: `Tidak bisa menyimpan gambar roster. (${detail})`,
        timer: 3500,
        showConfirmButton: false,
      });
    }
  } finally {
    isExportingImage.value = false;
  }
}
</script>
