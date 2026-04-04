<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <h2 class="text-2xl font-bold">Overtime</h2>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
          <button
            type="button"
            class="rounded bg-emerald-600 px-4 py-2 text-white"
            @click="exportExcel"
          >
            Export Excel
          </button>
          <Link href="/overtime/create" class="rounded bg-indigo-600 px-4 py-2 text-center text-white">
            + Ajukan Permintaan
          </Link>
        </div>
      </div>

      <!-- Filters -->
      <div class="mb-4 rounded-lg border border-slate-700 bg-slate-800 p-3">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
          <div class="md:col-span-4 relative">
            <input
              v-model="filters.search"
              @keyup.enter="fetchData"
              placeholder=" "
              class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
            />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">
              Cari Karyawan
            </label>
          </div>

          <div class="md:col-span-3 relative group">
            <SearchableSelect
              v-model="filters.status"
              :options="statusOptions"
              option-value="value"
              option-label="label"
              placeholder=" "
              empty-label="Status"
              input-class="w-full h-[52px] pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100 placeholder-transparent"
              button-class="h-[52px] border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
              @update:modelValue="fetchData"
            />
            <label
              :class="[
                'pointer-events-none absolute left-3 z-10 transition-all',
                (filters.status
                  ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                  : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
              ]"
            >
              Status
            </label>
          </div>

          <div v-if="isAdmin" class="md:col-span-5 relative group">
            <SearchableSelect
              v-model="filters.department_id"
              :options="departments"
              option-value="id"
              option-label="name"
              placeholder=" "
              empty-label="Semua Department"
              input-class="w-full h-[52px] pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100 placeholder-transparent"
              button-class="h-[52px] border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
              @update:modelValue="fetchData"
            />
            <label
              :class="[
                'pointer-events-none absolute left-3 z-10 transition-all',
                (filters.department_id
                  ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                  : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
              ]"
            >
              Department
            </label>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="rounded-lg border border-slate-700 bg-slate-800 p-4">
        <div v-if="!overtimes.data || overtimes.data.length === 0" class="text-slate-400 text-sm">
          Tidak ada data
        </div>
        <div v-else class="hidden overflow-auto lg:block">
          <table class="w-full min-w-[1220px] text-sm table-fixed">
            <thead class="text-slate-400 border-b border-slate-700">
              <tr>
                <th class="text-left py-2 pr-3 w-[56px]">No</th>
                <th class="text-left py-2 pr-3 w-[132px]">Tanggal Pengajuan</th>
                <th class="text-left py-2 pr-3 w-[160px]">Karyawan</th>
                <th class="text-left py-2 pr-3 w-[124px]">Department</th>
                <th class="text-left py-2 pr-3 w-[110px]">Tanggal Lembur</th>
                <th class="text-left py-2 pr-3 w-[92px]">Jam Mulai</th>
                <th class="text-left py-2 pr-3 w-[92px]">Jam Selesai</th>
                <th class="text-left py-2 pr-3 w-[92px]">Jumlah Jam</th>
                <th class="text-left py-2 pr-3 w-[260px]">Alasan</th>
                <th class="text-left py-2 pr-3 w-[110px]">Status</th>
                <th class="text-left py-2 w-[120px]">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in overtimes.data" :key="item.id" class="border-b border-slate-700/60">
                <td class="py-2 pr-3 align-top whitespace-nowrap">{{ index + 1 }}</td>
                <td class="py-2 pr-3 align-top whitespace-nowrap">{{ formatDate(item.created_at) }}</td>
                <td class="py-2 pr-3 align-top break-words" :title="item.employee?.name || item.user?.name || '-'">
                  {{ item.employee?.name || item.user?.name || '-' }}
                </td>
                <td class="py-2 pr-3 align-top truncate" :title="item.employee?.department?.name || item.user?.department?.name || '-'">
                  {{ item.employee?.department?.name || item.user?.department?.name || '-' }}
                </td>
                <td class="py-2 pr-3 align-top whitespace-nowrap">{{ formatDate(item.overtime_date) }}</td>
                <td class="py-2 pr-3 align-top whitespace-nowrap">{{ item.start_time }}</td>
                <td class="py-2 pr-3 align-top whitespace-nowrap">{{ item.end_time }}</td>
                <td class="py-2 pr-3 align-top whitespace-nowrap">{{ item.hours }} jam</td>
                <td class="py-2 pr-3 align-top overflow-visible">
                  <div class="overtime-reason-wrap">
                    <div :ref="(el) => setReasonElement(item.id, el)" class="overtime-reason">{{ item.reason || '-' }}</div>
                    <button
                      v-if="shouldShowReasonMore(item)"
                      type="button"
                      class="overtime-readmore text-xs text-sky-300 hover:text-sky-200 underline decoration-dotted"
                      @click.stop="showReasonTooltip(item.reason, $event)"
                    >
                      Read more
                    </button>
                  </div>
                </td>
                <td class="py-2 pr-3 align-top whitespace-nowrap">
                  <span :class="getStatusClass(item.status)" class="inline-flex px-2 py-1 rounded text-xs font-semibold whitespace-nowrap">
                    {{ item.status }}
                  </span>
                </td>
                <td class="py-2 align-top">
                  <div class="flex items-center gap-3 whitespace-nowrap">
                    <Link
                      :href="`/overtime/${item.id}`"
                      class="inline-flex items-center px-3 py-1.5 rounded bg-sky-600 text-white hover:bg-sky-500"
                    >
                      Detail
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="overtimes.data && overtimes.data.length > 0" class="overflow-hidden rounded-lg border border-slate-700 lg:hidden">
          <div
            v-for="item in overtimes.data"
            :key="`mobile-${item.id}`"
            class="border-b border-slate-700/60 bg-slate-900/30 p-4 last:border-b-0"
          >
            <div class="mb-3 flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="font-semibold text-white">{{ item.employee?.name || item.user?.name || '-' }}</div>
                <div class="text-sm text-slate-400">{{ formatDate(item.overtime_date) }}</div>
              </div>
              <span :class="getStatusClass(item.status)" class="inline-flex rounded px-2 py-1 text-xs font-semibold">
                {{ item.status }}
              </span>
            </div>

            <div class="space-y-2 text-sm">
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Department</span>
                <span class="max-w-[62%] text-right">{{ item.employee?.department?.name || item.user?.department?.name || '-' }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Pengajuan</span>
                <span class="text-right">{{ formatDate(item.created_at) }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Jam</span>
                <span class="text-right">{{ item.start_time }} - {{ item.end_time }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Jumlah Jam</span>
                <span class="text-right">{{ item.hours }} jam</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Alasan</span>
                <span class="max-w-[62%] whitespace-pre-wrap text-right">{{ item.reason || '-' }}</span>
              </div>
            </div>

            <div class="mt-4">
              <Link :href="`/overtime/${item.id}`" class="inline-flex w-full items-center justify-center rounded bg-sky-600 px-3 py-2 text-white hover:bg-sky-500">
                Detail
              </Link>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div class="text-sm text-slate-400">
            Menampilkan {{ overtimes.data?.length || 0 }} dari {{ overtimes.total || 0 }} data
          </div>
          <Pagination :paginator="overtimes" :onPageChange="goToPage" />
        </div>
      </div>

      <!-- Create Modal -->
      <OvertimeForm 
        v-if="showCreateModal" 
        title="Ajukan Permintaan Lembur"
        @close="showCreateModal = false"
        @success="fetchData"
      />

      <!-- Detail Modal -->
      <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-lg rounded-lg bg-transparent p-4 md:p-6">
          <h3 class="text-xl font-bold mb-4">Detail Permintaan Lembur</h3>
          
          <div class="space-y-3">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
              <span class="text-slate-400">Karyawan:</span>
              <span class="text-left sm:text-right">{{ selectedItem?.employee?.name || selectedItem?.user?.name }}</span>
            </div>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
              <span class="text-slate-400">Department:</span>
              <span class="text-left sm:text-right">{{ selectedItem?.employee?.department?.name || selectedItem?.user?.department?.name }}</span>
            </div>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
              <span class="text-slate-400">Tanggal Lembur:</span>
              <span class="text-left sm:text-right">{{ formatDate(selectedItem?.overtime_date) }}</span>
            </div>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
              <span class="text-slate-400">Jam Mulai:</span>
              <span class="text-left sm:text-right">{{ selectedItem?.start_time }}</span>
            </div>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
              <span class="text-slate-400">Jam Selesai:</span>
              <span class="text-left sm:text-right">{{ selectedItem?.end_time }}</span>
            </div>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
              <span class="text-slate-400">Jumlah Jam:</span>
              <span class="text-left sm:text-right">{{ selectedItem?.hours }} jam</span>
            </div>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
              <span class="text-slate-400">Alasan:</span>
              <span class="whitespace-pre-wrap text-left sm:max-w-[60%] sm:text-right">{{ selectedItem?.reason }}</span>
            </div>
            <div v-if="selectedItem?.attachment_url" class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
              <span class="text-slate-400">Attachment:</span>
              <a
                :href="selectedItem.attachment_url"
                target="_blank"
                rel="noopener"
                class="text-left text-indigo-400 underline hover:text-indigo-300 sm:text-right"
              >
                Lihat Attachment
              </a>
            </div>
            <div v-if="selectedItem?.attachment_url && !isPdfAttachment(selectedItem)" class="pt-2">
              <img
                :src="selectedItem.attachment_url"
                alt="Attachment lembur"
                class="max-h-56 rounded border border-slate-700 object-contain bg-slate-900/40"
              />
            </div>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
              <span class="text-slate-400">Status:</span>
              <span :class="getStatusClass(selectedItem?.status)" class="inline-flex px-2 py-1 rounded text-xs font-semibold">
                {{ selectedItem?.status }}
              </span>
            </div>
            <div v-if="selectedItem?.review_notes" class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
              <span class="text-slate-400">Catatan:</span>
              <span class="whitespace-pre-wrap text-left sm:max-w-[60%] sm:text-right">{{ selectedItem?.review_notes }}</span>
            </div>
          </div>

          <!-- Action buttons for manager/admin when status is pending -->
          <div v-if="selectedItem?.status === 'pending' && (isAdmin || isManager)" class="mt-4 pt-4 border-t border-slate-700">
            <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
              <button @click="rejectRequest(selectedItem)" class="px-4 py-2 rounded bg-red-600 text-white">
                Tolak
              </button>
              <button @click="approveRequest(selectedItem)" class="px-4 py-2 rounded bg-green-600 text-white">
                Setuju
              </button>
            </div>
          </div>

          <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-end">
            <button @click="showDetailModal = false" class="px-4 py-2 rounded bg-slate-700 text-slate-300">
              Tutup
            </button>
          </div>
        </div>
      </div>

      <div v-if="reasonTooltip.visible" class="fixed inset-0 z-50" @click="hideReasonTooltip">
        <div
          class="fixed z-50 max-w-[320px] bg-slate-800 border border-slate-700 text-slate-100 text-sm rounded-lg shadow-xl p-3"
          :style="{ top: `${reasonTooltip.top}px`, left: `${reasonTooltip.left}px` }"
          @click.stop
        >
          <div class="font-semibold text-slate-200 mb-1">Alasan</div>
          <div class="whitespace-pre-wrap">{{ reasonTooltip.text }}</div>
          <button
            type="button"
            class="mt-2 text-xs text-slate-300 hover:text-white"
            @click="hideReasonTooltip"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, nextTick, onMounted, onBeforeUnmount, onUpdated } from 'vue';
import { router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import OvertimeForm from '@/Components/OvertimeForm.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Pagination from '@/Components/Pagination.vue';
import { swalConfirm } from '@/Utils/swalConfirm';

const props = defineProps({
  overtimes: Object,
  filters: Object,
  departments: Array,
  isAdmin: Boolean,
  isManager: Boolean,
});

const filters = reactive({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  department_id: props.filters?.department_id || '',
});

const showCreateModal = ref(false);
const showDetailModal = ref(false);
const selectedItem = ref(null);
const reasonElements = new Map();
const reasonOverflow = reactive({});
const reasonTooltip = reactive({
  visible: false,
  text: '',
  top: 0,
  left: 0,
});
const statusOptions = [
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
];

function fetchData() {
  const params = {};
  if (filters.search) params.search = filters.search;
  if (filters.status) params.status = filters.status;
  if (filters.department_id) params.department_id = filters.department_id;
  
  router.get('/overtime', params, { 
    preserveState: true, 
    preserveScroll: true 
  });
}

function exportExcel() {
  const params = {};
  if (filters.search) params.search = filters.search;
  if (filters.status) params.status = filters.status;
  if (filters.department_id) params.department_id = filters.department_id;
  params.export = 1;

  const qs = new URLSearchParams(params).toString();
  window.location.href = `/overtime?${qs}`;
}

function goToPage(pageNum) {
  const params = { page: pageNum };
  if (filters.search) params.search = filters.search;
  if (filters.status) params.status = filters.status;
  if (filters.department_id) params.department_id = filters.department_id;
  
  router.get('/overtime', params, { 
    preserveState: true, 
    preserveScroll: true 
  });
}

function prevPage() {
  if (props.overtimes.prev_page_url) {
    goToPage(props.overtimes.current_page - 1);
  }
}

function nextPage() {
  if (props.overtimes.next_page_url) {
    goToPage(props.overtimes.current_page + 1);
  }
}

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID');
}

function getStatusClass(status) {
  const classes = {
    'pending': 'bg-amber-600/30 text-amber-200',
    'approved': 'bg-emerald-600/30 text-emerald-200',
    'rejected': 'bg-rose-600/30 text-rose-200',
  };
  return classes[status] || 'bg-slate-600/30 text-slate-200';
}

function viewDetail(item) {
  selectedItem.value = item;
  showDetailModal.value = true;
}

function isPdfAttachment(item) {
  const name = String(item?.attachment_original_name || '').toLowerCase();
  const url = String(item?.attachment_url || '').toLowerCase();
  return name.endsWith('.pdf') || url.includes('.pdf');
}

function setReasonElement(id, el) {
  if (el) {
    reasonElements.set(id, el);
    return;
  }

  reasonElements.delete(id);
  delete reasonOverflow[id];
}

function updateReasonOverflow() {
  const activeIds = new Set();

  reasonElements.forEach((el, id) => {
    activeIds.add(String(id));
    if (!el) {
      reasonOverflow[id] = false;
      return;
    }

    reasonOverflow[id] = (el.scrollHeight - el.clientHeight > 1) || (el.scrollWidth - el.clientWidth > 1);
  });

  Object.keys(reasonOverflow).forEach((key) => {
    if (!activeIds.has(String(key))) {
      delete reasonOverflow[key];
    }
  });
}

function shouldShowReasonMore(item) {
  return Boolean(reasonOverflow[item?.id]);
}

function showReasonTooltip(text, event) {
  const reason = String(text || '').trim();
  if (!reason) return;

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

  reasonTooltip.text = reason;
  reasonTooltip.left = Math.round(left);
  reasonTooltip.top = Math.round(top);
  reasonTooltip.visible = true;
}

function hideReasonTooltip() {
  reasonTooltip.visible = false;
  reasonTooltip.text = '';
}

function handleEscape(event) {
  if (event.key === 'Escape') {
    hideReasonTooltip();
  }
}

async function approveRequest(item) {
  const ok = await swalConfirm({
    title: 'Approve Request',
    text: 'Apakah Anda yakin ingin menyetujui permintaan ini?',
    confirmButtonText: 'Approve',
    confirmButtonColor: '#16a34a',
  });
  if (!ok) return;
  
  router.put(`/overtime/${item.id}`, {
    status: 'approved',
    review_notes: '',
  }, {
    onSuccess: () => {
      showDetailModal.value = false;
      fetchData();
    },
  });
}

function rejectRequest(item) {
  const notes = prompt('Masukkan alasan penolakan:');
  if (!notes) return;
  
  router.put(`/overtime/${item.id}`, {
    status: 'rejected',
    review_notes: notes,
  }, {
    onSuccess: () => {
      showDetailModal.value = false;
      fetchData();
    },
  });
}

onMounted(() => {
  window.addEventListener('keydown', handleEscape);
  nextTick(updateReasonOverflow);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleEscape);
});

onUpdated(() => {
  nextTick(updateReasonOverflow);
});
</script>

<style scoped>
.overtime-reason-wrap {
  position: relative;
}

.overtime-reason {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.25rem;
  min-height: calc(1.25rem * 2);
  padding-right: 4.25rem;
  word-break: break-word;
}

.overtime-readmore {
  position: absolute;
  bottom: 0.25rem;
  right: 0.5rem;
  background: rgba(15, 23, 42, 0.95);
  padding: 0 0.25rem;
  border-radius: 4px;
}
</style>
