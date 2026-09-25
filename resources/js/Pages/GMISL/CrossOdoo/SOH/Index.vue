<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold">{{ pageTitle }}</h2>
          <p class="text-sm text-slate-400">
            Menampilkan stock on hand untuk customer
            <span class="font-semibold text-slate-200">{{ customerName }}</span>
            dan product
            <span class="font-semibold text-slate-200">{{ productName }}</span>.
          </p>
        </div>
        <div class="flex flex-col items-end gap-2">
          <div class="text-sm text-slate-400">
            Total: <span class="font-semibold text-slate-200">{{ totalRows }}</span> data
          </div>
          <a
            :href="exportUrl"
            data-inertia-ignore
            class="inline-flex items-center justify-center rounded bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700"
          >
            Export
          </a>
        </div>
      </div>

      <div class="mb-4 rounded border border-slate-300 bg-slate-50 p-4">
        <div class="grid gap-3">
          <div class="flex items-center gap-3">
            <label class="w-28 shrink-0 text-xs font-semibold uppercase tracking-wider text-slate-600" for="customer_id">Customer :</label>
            <SearchableSelect
              id="customer_id"
              v-model="localCustomerId"
              variant="light"
              class="min-w-0 w-full sm:w-72"
              style="width: calc(70% - 208px)"
              :options="customerOptions"
              option-value="customer_id"
              option-label="label"
              placeholder="Ketik untuk mencari customer..."
              empty-label="Pilih customer"
              input-class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
              button-class="border-l border-slate-300"
              @update:modelValue="onCustomerChange"
            />
          </div>

          <div class="flex items-center gap-3">
            <label class="w-28 shrink-0 text-xs font-semibold uppercase tracking-wider text-slate-600" for="product_id">Product :</label>
            <SearchableSelect
              id="product_id"
              v-model="localProductId"
              variant="light"
              class="min-w-0 flex-none"
              style="width: calc(100% - 280px)"
              :options="availableProducts"
              option-value="product_id"
              option-label="label"
              placeholder="Ketik untuk mencari product..."
              empty-label="Pilih product"
              input-class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
              button-class="border-l border-slate-300"
              @update:modelValue="onProductChange"
            />
            <button
              type="button"
              class="inline-flex items-center justify-center rounded bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
              @click="applyFilters"
            >
              Apply filter
            </button>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto rounded border border-slate-600 bg-white">
        <table class="w-full border-collapse text-xs text-slate-900" style="table-layout: auto;">
          <thead>
            <tr class="bg-sky-100 text-slate-900">
              <th v-if="showDate" class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Date</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Owner</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Location</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Destination package</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Kode barang</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Nama barang</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Preference</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Source Document</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Expired</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">Stock On Hand</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">Qty Reserve</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">Stock On Hand Available</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">QTY SOH (KG)</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">UOM</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Lot</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Nopol</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!paginatedRows.length">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-6 text-center text-slate-400" :colspan="showDate ? 16 : 15">
                Tidak ada data untuk filter yang dipilih.
              </td>
            </tr>
            <tr
              v-for="(row, index) in paginatedRows"
              :key="index"
              :class="(index % 2 === 0 ? 'bg-white' : 'bg-slate-50') + ' text-slate-900'"
              class="hover:bg-blue-50"
            >
              <td v-if="showDate" class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row['Date'] || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row['Owner'] || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row['Location'] || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row['Destination package'] || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row['Kode barang'] || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row['Nama barang'] || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row['Preference'] || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row['Source Document'] || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row['Expired'] || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right text-slate-900">{{ formatNumber(row['SOH Available']) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right text-slate-900">{{ formatNumber(row['Qty Reserve']) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right text-slate-900">{{ formatNumber(row['On Hand Available']) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right text-slate-900">{{ formatNumber(row['Qty SOH']) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row['UOM'] || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row['Lot'] || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row['Nopol'] || '-' }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="bg-slate-200 text-slate-900">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-bold" :colspan="showDate ? 9 : 8">TOTAL</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-bold text-slate-900">{{ formatNumber(totalSoh) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-bold text-slate-900">{{ formatNumber(totalReserve) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-bold text-slate-900">{{ formatNumber(totalOnHandAvailable) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-bold text-slate-900">{{ formatNumber(totalQtySoh) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5" colspan="3"></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div v-if="totalPages > 1" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-slate-400">
          Menampilkan {{ totalRows === 0 ? 0 : (currentPage - 1) * perPage + 1 }}-{{ Math.min(currentPage * perPage, totalRows) }} dari {{ totalRows }} data
        </div>
        <div class="flex items-center gap-1">
          <button type="button" class="rounded border border-slate-600 bg-slate-800 px-2.5 py-1 text-xs text-slate-300 transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-40" :disabled="currentPage === 1" @click="changePage(1)">
            &laquo;
          </button>
          <button type="button" class="rounded border border-slate-600 bg-slate-800 px-2.5 py-1 text-xs text-slate-300 transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-40" :disabled="currentPage === 1" @click="changePage(currentPage - 1)">
            &lsaquo;
          </button>
          <template v-for="page in visiblePages" :key="page">
            <span v-if="page === '...'" class="px-1.5 py-1 text-xs text-slate-500">...</span>
            <button v-else type="button" class="min-w-8 rounded border px-2.5 py-1 text-xs font-semibold transition" :class="page === currentPage ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-slate-600 bg-slate-800 text-slate-300 hover:bg-slate-700'" @click="changePage(page)">
              {{ page }}
            </button>
          </template>
          <button type="button" class="rounded border border-slate-600 bg-slate-800 px-2.5 py-1 text-xs text-slate-300 transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-40" :disabled="currentPage === totalPages" @click="changePage(currentPage + 1)">
            &rsaquo;
          </button>
          <button type="button" class="rounded border border-slate-600 bg-slate-800 px-2.5 py-1 text-xs text-slate-300 transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-40" :disabled="currentPage === totalPages" @click="changePage(totalPages)">
            &raquo;
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
  pageTitle:            { type: String,   default: 'Cross Odoo - SOH' },
  basePath:             { type: String,   default: '/gmisl/cross-odoo/soh' },
  showDate:             { type: Boolean,  default: false },
  rows:                { type: Array,    default: () => [] },
  customers:           { type: Array,    default: () => [] },
  products:            { type: Array,    default: () => [] },
  selectedCustomerId:  { type: [String, Number], default: null },
  selectedProductId:   { type: [String, Number], default: null },
  customerName:        { type: String,   default: 'Customer' },
  productName:         { type: String,   default: 'Product' },
  currentPage:         { type: Number,   default: 1 },
  perPage:             { type: Number,   default: 25 },
  totalRows:           { type: Number,   default: 0 },
  totalSoh:            { type: Number,   default: 0 },
  totalQtySoh:         { type: Number,   default: 0 },
  totalReserve:         { type: Number,   default: 0 },
  totalOnHandAvailable:  { type: Number,   default: 0 },
});

const paginatedRows   = computed(() => props.rows || []);
const totalPages      = computed(() => Math.max(1, Math.ceil(props.totalRows / props.perPage)));

const customerOptions = computed(() =>
  (props.customers || []).map(c => ({ customer_id: c.customer_id, label: c.customer_name }))
);

const availableProducts = computed(() => {
  const cid = Number(localCustomerId.value ?? -1);
  return (props.products || [])
    .filter(p => Number(p.customer_id) === cid)
    .map(p => ({ product_id: p.product_id, label: (p.default_code ? p.default_code + ' - ' : '') + p.product_name }));
});

const localCustomerId = ref(props.selectedCustomerId);
const localProductId  = ref(props.selectedProductId);

const exportUrl = computed(() => {
  const params = new URLSearchParams();
  if (localCustomerId.value !== null && localCustomerId.value !== undefined && localCustomerId.value !== '') params.set('customer_id', localCustomerId.value);
  if (localProductId.value !== null && localProductId.value !== undefined && localProductId.value !== '') params.set('product_id', localProductId.value);
  return `${props.basePath}/export?${params.toString()}`;
});

const visiblePages = computed(() => {
  const total = totalPages.value;
  const cur   = props.currentPage;
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
  const pages = [1];
  if (cur > 3) pages.push('...');
  for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i);
  if (cur < total - 2) pages.push('...');
  pages.push(total);
  return pages;
});

function formatNumber(v) {
  if (v === null || v === undefined || v === '') return '-';
  return Number(v).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function buildParams(overrides = {}) {
  return {
    customer_id: localCustomerId.value ?? undefined,
    product_id:  localProductId.value  ?? undefined,
    page: props.currentPage,
    ...overrides,
  };
}

function reload(params, only) {
  router.get(props.basePath, params, {
    preserveState: true,
    preserveScroll: true,
    only,
  });
}

const ONLY_FILTER = ['rows', 'selectedCustomerId', 'selectedProductId', 'customerName', 'productName', 'currentPage', 'perPage', 'totalRows', 'totalSoh', 'totalQtySoh', 'totalReserve', 'totalOnHandAvailable'];

function onCustomerChange(value) {
  localCustomerId.value = value || null;
  const first = availableProducts.value[0];
  localProductId.value = first ? first.product_id : null;
}

function onProductChange(value) {
  localProductId.value = value || null;
}

function applyFilters() {
  reload(buildParams({ page: 1 }), ONLY_FILTER);
}

function changePage(p) {
  const safe = Math.max(1, Math.min(p, totalPages.value));
  if (safe === props.currentPage) return;
  reload(buildParams({ page: safe }), ONLY_FILTER);
}
</script>