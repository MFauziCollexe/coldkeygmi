<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Products</h2>
          <p class="text-sm text-slate-400">Daftar produk dari tabel t_products.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
          <input
            ref="fileInput"
            type="file"
            accept=".csv,.xls,.xlsx"
            class="hidden"
            @change="handleFileSelect"
          />
          <button
            type="button"
            class="inline-flex items-center justify-center rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60"
            @click="triggerFileInput"
            :disabled="isUploadLoading"
          >
            <span v-if="isUploadLoading">Memproses...</span>
            <span v-else>Import Excel</span>
          </button>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search products..."
            class="rounded bg-slate-800 px-3 py-2 text-sm"
            @input="onSearchInput"
          />
        </div>
      </div>

      <div
        v-if="uploadMessage"
        :class="uploadMessage.type === 'success' ? 'mb-4 rounded border border-emerald-500 bg-emerald-900/40 p-3 text-sm text-emerald-300' : 'mb-4 rounded border border-rose-500 bg-rose-900/40 p-3 text-sm text-rose-300'"
      >
        {{ uploadMessage.text }}
      </div>

      <div v-if="isUploadLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
        <div class="w-[320px] rounded-xl bg-white p-6 text-center shadow-xl">
          <div class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-b-2 border-blue-600"></div>
          <p class="mb-3 text-lg font-semibold text-slate-900">{{ activeLoadingMessage }}</p>
          <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
            <div
              class="h-full bg-blue-600 transition-all duration-300 ease-out"
              :style="{ width: `${loadingProgress}%` }"
            ></div>
          </div>
          <p class="mt-2 text-xs text-slate-500">{{ loadingProgress }}%</p>
        </div>
      </div>

      <div class="rounded bg-slate-800 p-4">
        <div class="overflow-x-auto">
          <table class="w-max min-w-full table-auto whitespace-nowrap">
            <thead>
              <tr class="text-left text-slate-400">
                <th class="py-2 pl-2 pr-3">#</th>
                <th class="px-3 py-2">Customer</th>
                <th class="px-3 py-2">Barcode</th>
                <th class="px-3 py-2">Reference</th>
                <th class="px-3 py-2">Name</th>
                <th class="px-3 py-2">Product Category</th>
                <th class="px-3 py-2">Unit of Measure</th>
                <th class="px-3 py-2 text-right">QtyPallet</th>
                <th class="px-3 py-2">CF</th>
                <th class="px-3 py-2 text-right">Length</th>
                <th class="px-3 py-2 text-right">Width</th>
                <th class="px-3 py-2 text-right">Height</th>
                <th class="px-3 py-2 text-right">Weight</th>
                <th class="px-3 py-2">Stack</th>
                <th class="px-3 py-2 text-right">Volume</th>
                <th class="px-3 py-2 pr-2">Tags</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!products.data || products.data.length === 0" class="border-t border-slate-700">
                <td colspan="16" class="py-8 text-center text-slate-400">No products found.</td>
              </tr>
              <tr v-for="(item, idx) in products.data" :key="item.id" class="border-t border-slate-700 text-sm">
                <td class="py-2 pl-2 pr-3 text-slate-400">{{ (products.current_page - 1) * products.per_page + idx + 1 }}</td>
                <td class="px-3 py-2">{{ item.customer || '-' }}</td>
                <td class="px-3 py-2">{{ item.barcode || '-' }}</td>
                <td class="px-3 py-2">{{ item.internal_reference || '-' }}</td>
                <td class="px-3 py-2 font-semibold text-white">{{ item.name || '-' }}</td>
                <td class="px-3 py-2">{{ item.product_category || '-' }}</td>
                <td class="px-3 py-2">{{ item.unit_of_measure || '-' }}</td>
                <td class="px-3 py-2 text-right">{{ formatNumber(item.standard_qty_pallet) }}</td>
                <td class="px-3 py-2">{{ item.pack_size_cf || '-' }}</td>
                <td class="px-3 py-2 text-right">{{ formatNumber(item.length) }}</td>
                <td class="px-3 py-2 text-right">{{ formatNumber(item.width) }}</td>
                <td class="px-3 py-2 text-right">{{ formatNumber(item.height) }}</td>
                <td class="px-3 py-2 text-right">{{ formatNumber(item.weight) }}</td>
                <td class="px-3 py-2">{{ item.layer_stack || '-' }}</td>
                <td class="px-3 py-2 text-right">{{ formatNumber(item.volume) }}</td>
                <td class="px-3 py-2 pr-2">{{ item.tags_name || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4">
          <Pagination :paginator="products" :onPageChange="fetch" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  products: Object,
  filters: Object,
});

const products = computed(() => props.products);
const filters = reactive({
  search: props.filters?.search || '',
});

const fileInput = ref(null);
const isUploadLoading = ref(false);
const uploadMessage = ref(null);
const loadingMessage = ref('Memproses file...');
const loadingProgress = ref(0);
const activeLoadingMessage = computed(() => loadingMessage.value);

let searchTimer = null;
function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetch(), 300);
}

function fetch(page = 1) {
  const params = {};
  if (filters.search) params.search = filters.search;
  if (page > 1) params.page = page;
  router.get('/master-data/products', params, { preserveState: true, preserveScroll: true });
}

function formatNumber(v) {
  if (v === null || v === undefined || v === '') return '-';
  const num = Number(v);
  return Number.isNaN(num) ? String(v) : num.toLocaleString('id-ID');
}

function triggerFileInput() {
  uploadMessage.value = null;
  fileInput.value?.click();
}

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
    xhr.open('POST', '/master-data/products/import');

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta && csrfMeta.content) {
      xhr.setRequestHeader('X-CSRF-TOKEN', csrfMeta.content);
    }
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.upload.onprogress = (event) => {
      if (event.lengthComputable) {
        const percent = Math.round((event.loaded / event.total) * 80);
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
        if (xhr.status >= 200 && xhr.status < 300) {
          router.reload({ only: ['products'], preserveState: false, preserveScroll: true });
        }
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

    try {
      xhr.send(formData);
      loadingMessage.value = 'Mengunggah file...';
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
</script>
