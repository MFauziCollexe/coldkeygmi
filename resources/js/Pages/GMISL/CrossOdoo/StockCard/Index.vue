<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Cross Odoo - Stock Card</h2>
          <p class="text-sm text-slate-400">
            Menampilkan stock card Odoo untuk customer
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
        <div class="grid gap-3 sm:grid-cols-5">
          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-600" for="customer_id">Customer</label>
            <SearchableSelect
              id="customer_id"
              v-model="localCustomerId"
              variant="light"
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

          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-600" for="product_id">Product</label>
            <SearchableSelect
              id="product_id"
              v-model="localProductId"
              variant="light"
              :options="availableProducts"
              option-value="product_id"
              option-label="label"
              placeholder="Ketik untuk mencari product..."
              empty-label="Pilih product"
              input-class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
              button-class="border-l border-slate-300"
              @update:modelValue="onProductChange"
            />
          </div>

          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-600" for="start_date">Start Date</label>
            <input
              id="start_date"
              type="date"
              class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              v-model="startDateInput"
            />
          </div>

          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-600" for="end_date">End Date</label>
            <input
              id="end_date"
              type="date"
              class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              v-model="endDateInput"
            />
          </div>

          <div class="flex items-end">
            <button
              type="button"
              class="inline-flex w-full justify-center rounded bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
              @click="applyFilters"
            >
              Apply filters
            </button>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto rounded border border-slate-600 bg-white">
        <table class="w-full border-collapse text-xs text-slate-900" style="table-layout: auto;">
          <thead>
            <tr class="bg-sky-100 text-slate-900">
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">TANGGAL</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">TRANSAKSI</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">SOURCE DOCUMENTS</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">EXPIRED</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">QTY IN</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">QTY OUT</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">SALDO</th>
            </tr>
          </thead>
          <tbody>
            <tr class="bg-sky-50 text-slate-900">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ formatDateShort(startDate) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">Saldo Awal</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">-</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">-</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">-</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">-</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(openingBalance) }}</td>
            </tr>
            <tr v-if="!paginatedRows.length">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-6 text-center text-slate-400" colspan="7">
                Tidak ada data untuk filter yang dipilih.
              </td>
            </tr>
            <tr
              v-for="(row, index) in paginatedRows"
              :key="index"
              :class="(index % 2 === 0 ? 'bg-white' : 'bg-slate-50') + ' text-slate-900'"
              class="hover:bg-blue-50"
            >
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ formatDateShort(row.transaction_date) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.trans || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.source_document || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ formatDateShort(row.expired) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.qty_in) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.qty_out) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.saldo) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="bg-slate-200 text-slate-900">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-bold" colspan="4">TOTAL</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-mono font-bold text-slate-900">{{ formatNumber(totalIn) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-mono font-bold text-slate-900">{{ formatNumber(totalOut) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-mono font-bold text-slate-900">{{ formatNumber(finalSaldo) }}</td>
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
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
  rows:              { type: Array,    default: () => [] },
  customers:         { type: Array,    default: () => [] },
  products:          { type: Array,    default: () => [] },
  selectedCustomerId:{ type: [String, Number], default: null },
  selectedProductId: { type: [String, Number], default: null },
  startDate:         { type: String,   default: '' },
  endDate:           { type: String,   default: '' },
  customerName:      { type: String,   default: 'Customer' },
  productName:       { type: String,   default: 'Product' },
  openingBalance:    { type: Number,   default: 0 },
  currentPage:       { type: Number,   default: 1 },
  perPage:           { type: Number,   default: 25 },
  totalRows:         { type: Number,   default: 0 },
  totalIn:           { type: Number,   default: 0 },
  totalOut:          { type: Number,   default: 0 },
  finalSaldo:        { type: Number,   default: 0 },
});

const allRows       = computed(() => props.rows || []);
const paginatedRows = computed(() => allRows.value);
const totalPages    = computed(() => Math.max(1, Math.ceil(props.totalRows / props.perPage)));
function toYmd(date) {
  const year  = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day   = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

const todayDate        = new Date();
const defaultEndDate   = toYmd(todayDate);
const defaultStartDate = toYmd(new Date(todayDate.getFullYear(), todayDate.getMonth() - 1, todayDate.getDate()));

const startDate     = computed(() => props.startDate || defaultStartDate);
const endDate       = computed(() => props.endDate || defaultEndDate);

const startDateInput = ref(startDate.value);
const endDateInput   = ref(endDate.value);

watch(() => props.startDate, (value) => { if (value) startDateInput.value = value; });
watch(() => props.endDate,   (value) => { if (value) endDateInput.value   = value; });

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
  if (startDateInput.value) params.set('start_date', startDateInput.value);
  if (endDateInput.value) params.set('end_date', endDateInput.value);
  return `/gmisl/cross-odoo/stock-card/export?${params.toString()}`;
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

function formatDateShort(v) {
  if (!v) return '-';
  const d = new Date(v);
  return Number.isNaN(d.getTime()) ? v : d.toLocaleDateString('id-ID', { year:'numeric', month:'2-digit', day:'2-digit' });
}
function formatNumber(v) {
  if (v === null || v === undefined || v === '') return '-';
  return Number(v).toLocaleString('id-ID', { minimumFractionDigits:0, maximumFractionDigits:2 });
}

function getDateValues() {
  return {
    start_date: startDateInput.value || undefined,
    end_date:   endDateInput.value   || undefined,
  };
}

function buildParams(overrides = {}) {
  return {
    customer_id: localCustomerId.value ?? undefined,
    product_id:  localProductId.value  ?? undefined,
    ...getDateValues(),
    page: props.currentPage,
    ...overrides,
  };
}

function reload(params, only) {
  router.get('/gmisl/cross-odoo/stock-card', params, {
    preserveState: true,
    preserveScroll: true,
    only,
  });
}

const ONLY_FILTER = ['rows','selectedCustomerId','selectedProductId','startDate','endDate','customerName','productName','openingBalance','currentPage','perPage','totalRows','totalIn','totalOut','finalSaldo'];

function onCustomerChange(value) {
  localCustomerId.value = value || null;
  const first = availableProducts.value[0];
  localProductId.value = first ? first.product_id : null;
}

function onProductChange(value) {
  localProductId.value = value || null;
}

function onDateChange() {
  reload(buildParams({ page: 1 }), ONLY_FILTER);
}

function applyFilters() {
  onDateChange();
}

function changePage(p) {
  const safe = Math.max(1, Math.min(p, totalPages.value));
  if (safe === props.currentPage) return;
  reload(buildParams({ page: safe }), ONLY_FILTER);
}
</script>