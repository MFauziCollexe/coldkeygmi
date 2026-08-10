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

      <div v-if="isFilterLoading" class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl p-6 shadow-xl w-[280px] text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-lg font-semibold text-slate-900">Memuat data...</p>
        </div>
      </div>

      <div class="mb-4 rounded border border-slate-300 bg-slate-50 p-4">
        <form ref="filterForm" method="get" class="grid gap-3 md:grid-cols-5" @submit="handleApplyFilters">
          <input type="hidden" name="page" v-model.number="currentPage" />
          <div>
            <label class="mb-1 block text-xs bg-white font-semibold uppercase tracking-wider text-slate-800" for="owner_id">Owner</label>
            <select
              id="owner_id"
              name="owner_id"
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              v-model="ownerFilter"
            >
              <option value="">Semua Owner</option>
              <option v-for="owner in owners" :key="owner.owner_id" :value="owner.owner_id">
                {{ owner.owner_name }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-1 bg-white block text-xs font-semibold uppercase tracking-wider text-slate-800" for="product_id">Product</label>
            <select
              id="product_id"
              name="product_id"
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              v-model="productFilter"
            >
              <option value="">Semua Product</option>
              <option v-for="product in products" :key="product.product_id" :value="product.product_id">
                {{ product.default_code }} - {{ product.product_name }}
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
              v-model="startFilter"
            />
          </div>

          <div>
            <label class="mb-1 bg-white block text-xs font-semibold uppercase tracking-wider text-slate-800" for="end_date">End Date</label>
            <input
              id="end_date"
              name="end_date"
              type="date"
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              v-model="endFilter"
            />
          </div>

          <div class="flex items-end">
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
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">NO_PO_SO</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">KETERANGAN</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">SD_AW</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">MUTASI_IN</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">MUTASI_OUT</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">SALDO_AKHIR</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!paginatedRows.length">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-6 text-center text-slate-400" colspan="14">
                {{ hasApplied ? 'Tidak ada data untuk filter yang dipilih.' : 'Pilih filter lalu klik Apply filters untuk memuat data.' }}
              </td>
            </tr>
            <tr
              v-for="(row, index) in paginatedRows"
              :key="row.id ?? index"
              :class="(index % 2 === 0 ? 'bg-white' : 'bg-slate-50') + ' text-slate-900'"
              class="hover:bg-blue-50"
            >
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-center text-slate-900">{{ (currentPage - 1) * perPage + index + 1 }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ formatDateShort(row.tgl_tran) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.kd_gudang || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.kd_cust || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.nm_cust || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.kd_brg || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.nm_brg || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.no_mobil || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.no_po_so || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.keterangan || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.sd_aw) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.mutasi_in) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.mutasi_out) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono font-semibold text-slate-900">{{ formatNumber(row.saldo_akhir) }}</td>
            </tr>
          </tbody>
          <tfoot v-if="paginatedRows.length">
            <tr class="bg-sky-50 font-semibold text-slate-900">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right" colspan="10">Total Halaman</td>
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
  products: {
    type: Array,
    default: () => [],
  },
  selectedOwnerId: {
    type: [String, Number],
    default: null,
  },
  startDate: {
    type: String,
    default: () => {
      const now = new Date();
      return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
    },
  },
  endDate: {
    type: String,
    default: () => {
      const now = new Date();
      const next = new Date(now.getFullYear(), now.getMonth() + 1, 1);
      return `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}-01`;
    },
  },
  targetProductId: {
    type: [String, Number],
    default: null,
  },
  applied: {
    type: Boolean,
    default: false,
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
const customerLabel = computed(() => props.customerName || 'Customer');
const productLabel = computed(() => props.productName || 'Product');
const currentPage = ref(props.currentPage);
const perPage = computed(() => props.perPage);
const totalRows = computed(() => props.totalRows);
const hasApplied = computed(() => props.applied);
const ownerFilter = ref(props.selectedOwnerId != null && props.selectedOwnerId !== '' ? String(props.selectedOwnerId) : '');
const productFilter = ref(props.targetProductId != null && props.targetProductId !== '' ? String(props.targetProductId) : '');
const startFilter = ref(props.startDate || '');
const endFilter = ref(props.endDate || '');
const fileInput = ref(null);
const filterForm = ref(null);
const isUploadLoading = ref(false);
const isFilterLoading = ref(false);
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

const pageTotalOpening = computed(() => (paginatedRows.value.length ? Number(paginatedRows.value[0].sd_aw) || 0 : 0));
const pageTotalIn = computed(() => paginatedRows.value.reduce((sum, row) => sum + (Number(row.mutasi_in) || 0), 0));
const pageTotalOut = computed(() => paginatedRows.value.reduce((sum, row) => sum + (Number(row.mutasi_out) || 0), 0));
const pageTotalBalance = computed(() => pageTotalOpening.value + pageTotalIn.value - pageTotalOut.value);

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

  return new Promise((resolve) => {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/portal/odoo/stock-card/import');

    // Set headers (CSRF, accept)
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta && csrfMeta.content) {
      xhr.setRequestHeader('X-CSRF-TOKEN', csrfMeta.content);
    }
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.upload.onprogress = (event) => {
      if (event.lengthComputable) {
        const percent = Math.round((event.loaded / event.total) * 80); // map upload to 0-80
        loadingProgress.value = Math.max(15, percent);
        loadingMessage.value = 'Mengunggah file...';
      } else {
        loadingProgress.value = 30;
        loadingMessage.value = 'Mengunggah file...';
      }
    };

    xhr.onload = () => {
      loadingProgress.value = 85;
      loadingMessage.value = 'Memproses file di server...';

      let data = {};
      try {
        data = JSON.parse(xhr.responseText || '{}');
      } catch (e) {
        uploadMessage.value = { type: 'error', text: 'Response tidak valid dari server.' };
        loadingProgress.value = 100;
        loadingMessage.value = 'Gagal memproses.';
        setTimeout(() => {
          isUploadLoading.value = false;
          loadingProgress.value = 0;
        }, 300);
        return resolve();
      }

      if (xhr.status >= 200 && xhr.status < 300) {
        uploadMessage.value = { type: 'success', text: data.message || 'File berhasil diunggah.' };
        loadingMessage.value = 'Selesai memproses.';
        loadingProgress.value = 100;
      } else {
        let msg = data.message || 'Gagal mengunggah file.';
        if (data.detected_headers) {
          msg += ' Detected headers: ' + (Array.isArray(data.detected_headers) ? data.detected_headers.join(', ') : String(data.detected_headers));
        }
        if (data.raw_headers) {
          try { msg += ' Raw headers: ' + JSON.stringify(data.raw_headers); } catch (e) { msg += ' Raw headers: ' + String(data.raw_headers); }
        }
        uploadMessage.value = { type: 'error', text: msg };
        loadingMessage.value = 'Gagal memproses.';
        loadingProgress.value = 100;
      }

      setTimeout(() => {
        isUploadLoading.value = false;
        loadingProgress.value = 0;
      }, 600);

      resolve();
    };

    xhr.onerror = () => {
      uploadMessage.value = { type: 'error', text: 'Terjadi kesalahan saat mengunggah file.' };
      loadingMessage.value = 'Terjadi kesalahan.';
      loadingProgress.value = 100;
      setTimeout(() => {
        isUploadLoading.value = false;
        loadingProgress.value = 0;
      }, 600);
      resolve();
    };

    // Start upload
    try {
      xhr.send(formData);
      loadingMessage.value = 'Mengunggah file...';
      // If upload is very fast, ensure progress changes from 10
      loadingProgress.value = 20;
    } catch (e) {
      uploadMessage.value = { type: 'error', text: 'Gagal memulai unggahan: ' + (e.message || e) };
      loadingMessage.value = 'Terjadi kesalahan.';
      loadingProgress.value = 100;
      setTimeout(() => {
        isUploadLoading.value = false;
        loadingProgress.value = 0;
      }, 600);
      resolve();
    }
  });
};

const submitFilters = () => {
  setTimeout(() => {
    const form = document.querySelector('form');
    if (form) {
      form.submit();
    }
  }, 0);
};

const handleApplyFilters = (event) => {
  event.preventDefault();
  currentPage.value = 1;
  isFilterLoading.value = true;
  setTimeout(() => {
    filterForm.value?.submit();
  }, 50);
};

const changePage = (page) => {
  currentPage.value = page;
  submitFilters();
};
</script>
