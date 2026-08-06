<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Portal Odoo - Stock Card</h2>
          <p class="text-sm text-slate-400">
            Menampilkan stock card Odoo untuk customer
            <span class="font-semibold text-slate-200">{{ customerLabel }}</span>
            dan product
            <span class="font-semibold text-slate-200">{{ productLabel }}</span>.
          </p>
        </div>
        <div class="text-sm text-slate-400">
          Total: <span class="font-semibold text-slate-200">{{ totalRows }}</span> data
        </div>
      </div>

      <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
        <input
          ref="fileInput"
          type="file"
          accept=".csv,.xls,.xlsx"
          class="hidden"
          @change="handleFileSelect"
        />
        <button
          type="button"
          class="inline-flex items-center justify-center rounded border border-blue-700 bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500"
          @click="triggerFileInput"
          :disabled="isUploadLoading"
        >
          <span v-if="isUploadLoading">Memproses...</span>
          <span v-else>Import Excel/CSV</span>
        </button>
      </div>
      <div v-if="uploadMessage" :class="uploadMessage.type === 'success' ? 'mb-4 rounded border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800' : 'mb-4 rounded border border-rose-300 bg-rose-50 p-3 text-sm text-rose-800'">
        {{ uploadMessage.text }}
      </div>

      <div v-if="isUploadLoading" class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl p-6 shadow-xl w-[320px] text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-lg font-semibold text-slate-900 mb-3">{{ activeLoadingMessage }}</p>
          <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
            <div
              class="h-full bg-blue-600 transition-all duration-300 ease-out"
              :style="{ width: `${loadingProgress}%` }"
            ></div>
          </div>
          <p class="text-xs text-slate-500 mt-2">{{ loadingProgress }}%</p>
        </div>
      </div>

      <div class="mb-4 rounded border border-slate-300 bg-slate-50 p-4">
        <form ref="filterForm" method="get" class="grid gap-3 sm:grid-cols-4">
          <input type="hidden" name="page" v-model.number="currentPage" />
          <div>
            <label class="mb-1 block text-xs bg-white font-semibold uppercase tracking-wider text-slate-800" for="owner_id">Owner</label>
            <select
              id="owner_id"
              name="owner_id"
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              :value="selectedOwnerId"
              @change="submitFilters"
            >
              <option v-for="owner in owners" :key="owner.owner_id" :value="owner.owner_id">
                {{ owner.owner_name }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-1 bg-white block text-xs font-semibold uppercase tracking-wider text-slate-800" for="start_date">Start Date</label>
            <input
              id="start_date"
              name="start_date"
              type="date"
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              :value="startDate"
              @change="submitFilters"
            />
          </div>

          <div>
            <label class="mb-1 bg-white block text-xs font-semibold uppercase tracking-wider text-slate-800" for="end_date">End Date</label>
            <input
              id="end_date"
              name="end_date"
              type="date"
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              :value="endDate"
              @change="submitFilters"
            />
          </div>

          <div class="flex items-end">
            <input type="hidden" name="product_id" :value="targetProductId || ''" />
            <button
              type="submit"
              class="inline-flex w-full justify-center rounded bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
            >
              Apply filters
            </button>
          </div>
        </form>
      </div>

      <div class="overflow-x-auto rounded border border-slate-600 bg-white">
        <table class="w-full border-collapse text-xs text-slate-900" style="table-layout: auto;">
          <thead>
            <tr class="bg-sky-100 text-slate-900">
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-center font-semibold">No</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">TGL_TRAN</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">KD_GUDANG</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">KD_CUST</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">NM_CUST</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">KD_BRG</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">NM_BRG</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">NO_MOBIL</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">NO_REFERENCE_1</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">NO_REFERENCE_2</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">NO_PO_SO</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">NO_INVOICE</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">KETERANGAN</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">SD_AW</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">MUTASI_IN</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">MUTASI_OUT</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">SALDO_AKHIR</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!paginatedRows.length">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-6 text-center text-slate-400" colspan="17">
                Tidak ada data untuk filter yang dipilih.
              </td>
            </tr>
            <tr
              v-for="(row, index) in paginatedRows"
              :key="row.id ?? index"
              :class="(index % 2 === 0 ? 'bg-white' : 'bg-slate-50') + ' text-slate-900'"
              class="hover:bg-blue-50"
            >
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-center text-slate-900">{{ (currentPage - 1) * perPage + index + 1 }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ formatDateShort(row.transaction_date) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.product_name || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.mobile_no || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.reference_1 || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.reference_2 || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.po_so || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.invoice_no || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.description || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.opening_qty) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.qty_in) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.qty_out) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono font-semibold text-slate-900">{{ formatNumber(row.balance_qty) }}</td>
            </tr>
          </tbody>
          <tfoot v-if="paginatedRows.length">
            <tr class="bg-sky-50 font-semibold text-slate-900">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right" colspan="13">Total Halaman</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-mono">{{ formatNumber(pageTotalOpening) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-mono">{{ formatNumber(pageTotalIn) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-mono">{{ formatNumber(pageTotalOut) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-mono">{{ formatNumber(pageTotalBalance) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div v-if="totalPages > 1" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-slate-400">
          Menampilkan {{ totalRows === 0 ? 0 : (currentPage - 1) * perPage + 1 }}-{{ Math.min(currentPage * perPage, totalRows) }} dari {{ totalRows }} data
        </div>
        <div class="flex items-center gap-1">
          <button
            type="button"
            class="rounded border border-slate-600 bg-slate-800 px-2.5 py-1 text-xs text-slate-300 transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
            :disabled="currentPage === 1"
            @click="changePage(1)"
          >
            &laquo;
          </button>
          <button
            type="button"
            class="rounded border border-slate-600 bg-slate-800 px-2.5 py-1 text-xs text-slate-300 transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
            :disabled="currentPage === 1"
            @click="changePage(currentPage - 1)"
          >
            &lsaquo;
          </button>

          <template v-for="page in visiblePages" :key="page">
            <span v-if="page === '...'" class="px-1.5 py-1 text-xs text-slate-500">...</span>
            <button
              v-else
              type="button"
              class="min-w-8 rounded border px-2.5 py-1 text-xs font-semibold transition"
              :class="page === currentPage
                ? 'border-indigo-500 bg-indigo-600 text-white'
                : 'border-slate-600 bg-slate-800 text-slate-300 hover:bg-slate-700'"
              @click="changePage(page)"
            >
              {{ page }}
            </button>
          </template>

          <button
            type="button"
            class="rounded border border-slate-600 bg-slate-800 px-2.5 py-1 text-xs text-slate-300 transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
            :disabled="currentPage === totalPages"
            @click="changePage(currentPage + 1)"
          >
            &rsaquo;
          </button>
          <button
            type="button"
            class="rounded border border-slate-600 bg-slate-800 px-2.5 py-1 text-xs text-slate-300 transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
            :disabled="currentPage === totalPages"
            @click="changePage(totalPages)"
          >
            &raquo;
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  rows: {
    type: Array,
    default: () => [],
  },
  owners: {
    type: Array,
    default: () => [],
  },
  selectedOwnerId: {
    type: [String, Number],
    default: null,
  },
  startDate: {
    type: String,
    default: '2026-01-01',
  },
  endDate: {
    type: String,
    default: '2026-12-31',
  },
  targetProductId: {
    type: [String, Number],
    default: null,
  },
  customerName: {
    type: String,
    default: 'Customer',
  },
  productName: {
    type: String,
    default: 'Product',
  },
  currentPage: {
    type: Number,
    default: 1,
  },
  perPage: {
    type: Number,
    default: 25,
  },
  totalRows: {
    type: Number,
    default: 0,
  },
});

const allRows = computed(() => props.rows || []);
const owners = computed(() => props.owners || []);
const selectedOwnerId = computed(() => props.selectedOwnerId);
const startDate = computed(() => props.startDate);
const endDate = computed(() => props.endDate);
const targetProductId = computed(() => props.targetProductId);
const customerLabel = computed(() => props.customerName || 'Customer');
const productLabel = computed(() => props.productName || 'Product');
const currentPage = ref(props.currentPage);
const perPage = computed(() => props.perPage);
const totalRows = computed(() => props.totalRows);
const fileInput = ref(null);
const isUploadLoading = ref(false);
const uploadMessage = ref(null);
const loadingMessage = ref('Memproses file...');
const loadingProgress = ref(0);
const activeLoadingMessage = computed(() => loadingMessage.value);

const paginatedRows = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return allRows.value.slice(start, start + perPage.value).filter((r) => r != null);
});

const totalPages = computed(() => Math.max(1, Math.ceil(totalRows.value / perPage.value)));

const visiblePages = computed(() => {
  const pages = [];
  const maxButtons = 5;
  let start = Math.max(1, currentPage.value - Math.floor(maxButtons / 2));
  let end = Math.min(totalPages.value, start + maxButtons - 1);

  if (end - start < maxButtons - 1) {
    start = Math.max(1, end - maxButtons + 1);
  }

  for (let page = start; page <= end; page += 1) {
    if (page === start && page > 1) {
      pages.push(1);
      if (start > 2) {
        pages.push('...');
      }
    }

    pages.push(page);

    if (page === end && end < totalPages.value) {
      if (end < totalPages.value - 1) {
        pages.push('...');
      }
      pages.push(totalPages.value);
    }
  }

  return pages;
});

const pageTotalOpening = computed(() => paginatedRows.value.reduce((sum, row) => sum + (row.opening_qty || 0), 0));
const pageTotalIn = computed(() => paginatedRows.value.reduce((sum, row) => sum + (row.qty_in || 0), 0));
const pageTotalOut = computed(() => paginatedRows.value.reduce((sum, row) => sum + (row.qty_out || 0), 0));
const pageTotalBalance = computed(() => paginatedRows.value.reduce((sum, row) => sum + (row.balance_qty || 0), 0));

const formatNumber = (value) => {
  if (value === null || value === undefined || Number.isNaN(value)) {
    return '-';
  }
  return Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(value);
};

const formatDateShort = (value) => {
  if (!value) {
    return '-';
  }
  try {
    return new Date(value).toISOString().slice(0, 10);
  } catch {
    return value;
  }
};

const triggerFileInput = () => {
  uploadMessage.value = null;
  fileInput.value?.click();
};

const handleFileSelect = async (event) => {
  const file = event.target.files?.[0];
  if (!file) {
    return;
  }

  const extension = file.name.split('.').pop()?.toLowerCase();
  if (!['csv', 'xls', 'xlsx'].includes(extension)) {
    uploadMessage.value = { type: 'error', text: 'File harus berformat CSV atau Excel.' };
    event.target.value = '';
    return;
  }

  await uploadFile(file);
  event.target.value = '';
};

const uploadFile = async (file) => {
  isUploadLoading.value = true;
  uploadMessage.value = null;
  loadingMessage.value = 'Membaca file...';
  loadingProgress.value = 10;

  const formData = new FormData();
  formData.append('file', file);

  try {
    const response = await fetch('/portal/odoo/stock-card/import', {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    loadingProgress.value = 40;
    loadingMessage.value = 'Mengunggah file...';

    const data = await response.json();
    loadingProgress.value = 70;

    if (response.ok) {
      uploadMessage.value = {
        type: 'success',
        text: data.message || 'File berhasil diunggah.',
      };
      loadingMessage.value = 'Selesai memproses.';
      loadingProgress.value = 100;
    } else {
      let msg = data.message || 'Gagal mengunggah file.';
      if (data.detected_headers) {
        msg += ' Detected headers: ' + (Array.isArray(data.detected_headers) ? data.detected_headers.join(', ') : String(data.detected_headers));
      }
      if (data.raw_headers) {
        try {
          msg += ' Raw headers: ' + JSON.stringify(data.raw_headers);
        } catch (e) {
          msg += ' Raw headers: ' + String(data.raw_headers);
        }
      }

      uploadMessage.value = {
        type: 'error',
        text: msg,
      };
      loadingMessage.value = 'Gagal memproses.';
      loadingProgress.value = 100;
    }
  } catch (error) {
    loadingMessage.value = 'Terjadi kesalahan.';
    loadingProgress.value = 100;
    uploadMessage.value = {
      type: 'error',
      text: 'Terjadi kesalahan saat mengunggah file: ' + error.message,
    };
  } finally {
    setTimeout(() => {
      isUploadLoading.value = false;
      loadingProgress.value = 0;
    }, 300);
  }
};

const submitFilters = () => {
  setTimeout(() => {
    const form = document.querySelector('form');
    if (form) {
      form.submit();
    }
  }, 0);
};

const changePage = (page) => {
  currentPage.value = page;
  submitFilters();
};
</script>
