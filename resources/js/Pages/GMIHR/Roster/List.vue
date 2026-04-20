<template>
  <AppLayout>
    <div class="space-y-6 p-4 md:p-6">
      <div>
        <h2 class="text-2xl font-bold">Roster List</h2>
        <p class="text-slate-400 text-sm">
          Menampilkan roster untuk {{ departmentName === 'Semua Departemen' ? 'semua departemen' : `departemen ${departmentName || '-'}` }}.
        </p>
      </div>

      <div class="rounded-lg border border-slate-700 bg-slate-800 p-4">
        <div class="flex flex-wrap items-end gap-3">
          <div class="w-48">
            <label class="text-xs text-slate-300">Filter Bulan</label>
            <select
              v-model="selectedMonth"
              class="mt-1 w-full rounded border border-slate-600 bg-slate-900 px-3 py-2 text-sm text-white"
              @change="applyMonthFilter"
            >
              <option value="0">Semua Bulan</option>
              <option v-for="month in months" :key="month.value" :value="String(month.value)">
                {{ month.label }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-slate-700 bg-slate-800 p-4">
        <div v-if="!batches.data.length" class="text-slate-400 text-sm">Belum ada data roster.</div>
        <div v-else class="hidden overflow-auto lg:block">
          <table class="w-full min-w-[1120px] text-sm table-fixed">
            <thead class="text-slate-400 border-b border-slate-700">
              <tr>
                <th class="text-left py-2 pr-3 w-[96px]">Periode</th>
                <th class="text-left py-2 pr-3 w-[96px]">Versi</th>
                <th class="text-left py-2 pr-3 w-[280px]">File</th>
                <th class="text-left py-2 pr-3 w-[124px]">Status</th>
                <th class="text-left py-2 pr-3 w-[92px]">Rows</th>
                <th class="text-left py-2 pr-3 w-[132px]">Uploader</th>
                <th class="text-left py-2 pr-3 w-[132px]">Approver</th>
                <th class="text-left py-2 pr-3 w-[320px]">Catatan</th>
                <th class="text-left py-2 pr-3 w-[176px]">Waktu</th>
                <th class="text-left py-2 w-[220px]">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="batch in batches.data" :key="batch.id" class="border-b border-slate-700/60">
                <td class="py-2 pr-3 align-top whitespace-nowrap">{{ batch.month }}/{{ batch.year }}</td>
                <td class="py-2 pr-3 align-top whitespace-nowrap">
                  <span class="font-semibold">v{{ batch.version || 1 }}</span>
                  <span v-if="batch.is_current" class="ml-2 text-xs px-2 py-0.5 rounded bg-emerald-700/40 text-emerald-200">Current</span>
                </td>
                <td class="py-2 pr-3 align-top">
                  <a
                    :href="`/roster/${batch.id}/download`"
                    class="block truncate text-sky-300 hover:text-sky-200 underline decoration-dotted"
                    :title="`Download ${batch.filename || 'file roster'}`"
                  >
                    {{ batch.filename || '-' }}
                  </a>
                </td>
                <td class="py-2 pr-3 align-top whitespace-nowrap">
                  <span :class="statusClass(batch.status)" class="inline-flex px-2 py-1 rounded text-xs font-semibold whitespace-nowrap">
                    {{ statusLabel(batch.status) }}
                  </span>
                </td>
                <td class="py-2 pr-3 align-top whitespace-nowrap">{{ batch.saved_rows }}/{{ batch.total_rows }}</td>
                <td class="py-2 pr-3 align-top truncate" :title="batch.uploader?.name || '-'">{{ batch.uploader?.name || '-' }}</td>
                <td class="py-2 pr-3 align-top truncate" :title="batch.approver?.name || '-'">{{ batch.approver?.name || '-' }}</td>
                <td class="py-2 pr-3 align-top overflow-visible">
                  <div class="roster-note-wrap">
                    <div class="roster-note">{{ noteText(batch) }}</div>
                  <button
                    v-if="shouldShowReadMore(noteText(batch))"
                    type="button"
                    class="roster-readmore text-xs text-sky-300 hover:text-sky-200 underline decoration-dotted"
                    @click.stop="showNoteTooltip(noteText(batch), $event)"
                  >
                    Read more
                  </button>
                  </div>
                </td>
                <td class="py-2 pr-3 align-top whitespace-nowrap">{{ formatDate(batch.created_at) }}</td>
                <td class="py-2 align-top">
                  <div class="flex items-center gap-2">
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
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="batches.data.length" class="space-y-3 lg:hidden">
          <div
            v-for="batch in batches.data"
            :key="`mobile-${batch.id}`"
            class="rounded-xl border border-slate-700 bg-slate-900/40 p-4"
          >
            <div class="mb-3 flex items-start justify-between gap-3">
              <div>
                <div class="font-semibold text-white">{{ batch.month }}/{{ batch.year }}</div>
                <div class="mt-1 text-sm text-slate-400">
                  <span class="font-semibold">v{{ batch.version || 1 }}</span>
                  <span
                    v-if="batch.is_current"
                    class="ml-2 rounded bg-emerald-700/40 px-2 py-0.5 text-xs text-emerald-200"
                  >
                    Current
                  </span>
                </div>
              </div>
              <span :class="statusClass(batch.status)" class="inline-flex rounded px-2 py-1 text-xs font-semibold">
                {{ statusLabel(batch.status) }}
              </span>
            </div>

            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
              <div class="sm:col-span-2">
                <div class="text-slate-400">File</div>
                <a
                  :href="`/roster/${batch.id}/download`"
                  class="break-all text-sky-300 underline decoration-dotted hover:text-sky-200"
                  :title="`Download ${batch.filename || 'file roster'}`"
                >
                  {{ batch.filename || '-' }}
                </a>
              </div>
              <div>
                <div class="flex items-center justify-between gap-3 text-slate-400">
                  <span>Approver</span>
                  <span class="text-xs">{{ formatCompactDate(batch.approved_at || batch.created_at) }}</span>
                </div>
                <div class="flex items-start justify-between gap-3">
                  <div>{{ batch.approver?.name || '-' }}</div>
                  <div class="text-right text-xs text-slate-400">{{ batch.uploader?.name || '-' }}</div>
                </div>
              </div>
              <div class="sm:col-span-2">
                <div class="text-slate-400">Catatan</div>
                <div class="whitespace-pre-wrap">{{ noteText(batch) }}</div>
              </div>
            </div>

            <div class="mt-4 flex flex-col gap-2">
              <button
                class="w-full rounded bg-sky-600 px-3 py-2 text-white hover:bg-sky-500 disabled:opacity-60"
                :disabled="viewLoadingId === batch.id"
                @click="viewBatch(batch)"
              >
                {{ viewLoadingId === batch.id ? 'Loading...' : 'View' }}
              </button>
              <button
                v-if="batch.can_approve && batch.status === 'pending'"
                class="w-full rounded bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-500 disabled:opacity-60"
                :disabled="loadingId === batch.id"
                @click="approveBatch(batch)"
              >
                {{ loadingId === batch.id ? 'Approving...' : 'Approve' }}
              </button>
              <button
                v-if="batch.can_approve && batch.status === 'pending'"
                class="w-full rounded bg-rose-600 px-3 py-2 text-white hover:bg-rose-500 disabled:opacity-60"
                :disabled="rejectingId === batch.id"
                @click="rejectBatch(batch)"
              >
                {{ rejectingId === batch.id ? 'Rejecting...' : 'Reject' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="batches.last_page > 1" class="flex justify-end text-sm">
        <Pagination :paginator="batches" :onPageChange="goToPage" />
      </div>

      <p v-if="message.text" :class="message.type === 'error' ? 'text-rose-300' : 'text-emerald-300'" class="text-sm">
        {{ message.text }}
      </p>

      <div v-if="showViewModal" class="fixed inset-0 z-50 overflow-auto bg-black/70 p-4 md:p-8" @click.self="closeViewModal">
        <div class="mx-auto max-w-[96vw] rounded-lg border border-slate-700 bg-slate-900 p-4">
          <div class="mb-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h3 class="text-lg font-semibold">Roster View - {{ viewBatchInfo.month }}/{{ viewBatchInfo.year }}</h3>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
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

      <div v-if="noteTooltip.visible" class="fixed inset-0 z-50" @click="hideNoteTooltip">
        <div
          class="fixed z-50 max-w-[320px] bg-slate-800 border border-slate-700 text-slate-100 text-sm rounded-lg shadow-xl p-3"
          :style="{ top: `${noteTooltip.top}px`, left: `${noteTooltip.left}px` }"
          @click.stop
        >
          <div class="font-semibold text-slate-200 mb-1">Catatan</div>
          <div class="whitespace-pre-wrap">{{ noteTooltip.text }}</div>
          <button
            type="button"
            class="mt-2 text-xs text-slate-300 hover:text-white"
            @click="hideNoteTooltip"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import html2canvas from 'html2canvas';
import { toPng, toJpeg } from 'html-to-image';
import AppLayout from '@/Layouts/AppLayout.vue';
import RosterPreviewMatrix from '@/Pages/GMIHR/Roster/Components/RosterPreviewMatrix.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  batches: {
    type: Object,
    default: () => ({
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 8,
    }),
  },
  canApprove: {
    type: Boolean,
    default: false,
  },
  departmentName: {
    type: String,
    default: '',
  },
  filters: {
    type: Object,
    default: () => ({
      month: 0,
    }),
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

const noteTooltip = reactive({
  visible: false,
  text: '',
  top: 0,
  left: 0,
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
const selectedMonth = ref(String(props.filters?.month || 0));

function formatDate(value) {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '-';
  return date.toLocaleString();
}

function formatCompactDate(value) {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '-';
  return date.toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function monthLabel(month) {
  return months.find((m) => Number(m.value) === Number(month))?.label || '';
}

function shouldShowReadMore(text) {
  return String(text || '').trim().length > 80;
}

function showNoteTooltip(text, event) {
  const note = String(text || '').trim();
  if (!note) return;
  const rect = event?.currentTarget?.getBoundingClientRect?.();
  const padding = 12;
  const tooltipWidth = 320;
  const viewportWidth = window.innerWidth || 0;
  const viewportHeight = window.innerHeight || 0;

  let left = rect ? rect.left : padding;
  let top = rect ? rect.bottom + 8 : padding;

  if (left + tooltipWidth + padding > viewportWidth) {
    left = Math.max(padding, viewportWidth - tooltipWidth - padding);
  }

  const estimatedHeight = 180;
  if (top + estimatedHeight + padding > viewportHeight) {
    top = Math.max(padding, (rect ? rect.top : viewportHeight - estimatedHeight) - estimatedHeight - 8);
  }

  noteTooltip.text = note;
  noteTooltip.left = Math.round(left);
  noteTooltip.top = Math.round(top);
  noteTooltip.visible = true;
}

function hideNoteTooltip() {
  noteTooltip.visible = false;
  noteTooltip.text = '';
}

function handleEscape(event) {
  if (event.key === 'Escape') {
    hideNoteTooltip();
  }
}

function goToPage(page) {
  const current = Number(props.batches?.current_page || 1);
  const last = Number(props.batches?.last_page || 1);
  const target = Math.min(Math.max(1, Number(page || 1)), last);
  if (target === current) return;
  router.get('/roster/list', { page: target, month: Number(selectedMonth.value || 0) }, { preserveState: true, preserveScroll: true });
}

function applyMonthFilter() {
  router.get('/roster/list', {
    month: Number(selectedMonth.value || 0),
    page: 1,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
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

function noteText(batch) {
  if (!batch) return '-';
  if (batch.status === 'rejected') return batch.reject_reason || '-';
  if (batch.approval_locked_reason) return batch.approval_locked_reason;
  return batch.change_reason || '-';
}

onMounted(() => {
  window.addEventListener('keydown', handleEscape);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleEscape);
});

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

<style scoped>
.roster-note-wrap {
  position: relative;
}

.roster-note {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.25rem;
  min-height: calc(1.25rem * 2);
  padding-right: 3.75rem;
  word-break: break-word;
}

.roster-readmore {
  position: absolute;
  bottom: 0.25rem;
  right: 0.5rem;
  background: rgba(15, 23, 42, 0.95);
  padding: 0 0.25rem;
  border-radius: 4px;
}
</style>
