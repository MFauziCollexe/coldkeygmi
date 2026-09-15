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
        <div class="text-sm text-slate-400">
          Total: <span class="font-semibold text-slate-200">{{ totalRows }}</span> data
        </div>
      </div>

      <div class="mb-4 rounded border border-slate-300 bg-slate-50 p-4">
        <div class="grid gap-3 sm:grid-cols-5">
          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-600" for="customer_id">Customer</label>
            <select
              id="customer_id"
              class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              :value="localCustomerId"
              @change="onCustomerChange"
            >
              <option v-for="customer in customers" :key="customer.customer_id" :value="customer.customer_id">
                {{ customer.customer_name }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-600" for="product_id">Product</label>
            <select
              id="product_id"
              class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              :value="localProductId"
              @change="onProductChange"
            >
              <option v-for="product in availableProducts" :key="product.product_id" :value="product.product_id">
                {{ product.default_code ? product.default_code + ' - ' : '' }}{{ product.product_name }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-600" for="start_date">Start Date</label>
            <input
              id="start_date"
              type="date"
              class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              :value="startDate"
            />
          </div>

          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-600" for="end_date">End Date</label>
            <input
              id="end_date"
              type="date"
              class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              :value="endDate"
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
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-center font-semibold">No</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">CUSTOMER</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">NAMA BARANG</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">TRANS</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">TANGGAL</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">DONE_QTY</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">SRC_USAGE</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">DEST_USAGE</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">TOTAL_STOCK_MOVEMENT</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">SALDO_AWAL</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">QTY_IN</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">QTY_OUT</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!paginatedRows.length">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-6 text-center text-slate-400" colspan="12">
                Tidak ada data untuk filter yang dipilih.
              </td>
            </tr>
            <tr
              v-for="(row, index) in paginatedRows"
              :key="index"
              :class="(index % 2 === 0 ? 'bg-white' : 'bg-slate-50') + ' text-slate-900'"
              class="hover:bg-blue-50"
            >
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-center text-slate-900">{{ (currentPage - 1) * perPage + index + 1 }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.customer || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.product_name || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.trans || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ formatDateShort(row.transaction_date) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.done_qty) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.src_usage || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.dest_usage || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.total_movement) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.saldo_awal) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.qty_in) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.qty_out) }}</td>
            </tr>
          </tbody>
          <tfoot v-if="paginatedRows.length">
            <tr class="bg-sky-50 font-semibold text-slate-900">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right" colspan="7">Total Halaman</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-mono">{{ formatNumber(pageTotalDone) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-mono">{{ formatNumber(pageTotalMovement) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-mono">{{ formatNumber(pageTotalSaldoAwal) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-mono">{{ formatNumber(pageTotalIn) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-mono">{{ formatNumber(pageTotalOut) }}</td>
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

const props = defineProps({
  rows:              { type: Array,    default: () => [] },
  customers:         { type: Array,    default: () => [] },
  products:          { type: Array,    default: () => [] },
  selectedCustomerId:{ type: [String, Number], default: null },
  selectedProductId: { type: [String, Number], default: null },
  startDate:         { type: String,   default: '2026-01-01' },
  endDate:           { type: String,   default: '2026-12-31' },
  customerName:      { type: String,   default: 'Customer' },
  productName:       { type: String,   default: 'Product' },
  currentPage:       { type: Number,   default: 1 },
  perPage:           { type: Number,   default: 25 },
  totalRows:         { type: Number,   default: 0 },
});

const allRows       = computed(() => props.rows || []);
const paginatedRows = computed(() => allRows.value);
const totalPages    = computed(() => Math.max(1, Math.ceil(props.totalRows / props.perPage)));
const startDate     = computed(() => props.startDate || '2026-01-01');
const endDate       = computed(() => props.endDate || '2026-12-31');

const availableProducts = computed(() => {
  const cid = Number(localCustomerId.value ?? -1);
  return (props.products || []).filter(p => Number(p.customer_id) === cid);
});

const localCustomerId = ref(props.selectedCustomerId);
const localProductId  = ref(props.selectedProductId);

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

function sumField(rows, field) { return rows.reduce((acc, r) => acc + (Number(r[field]) || 0), 0); }

const pageTotalDone     = computed(() => sumField(paginatedRows.value, 'done_qty'));
const pageTotalMovement = computed(() => sumField(paginatedRows.value, 'total_movement'));
const pageTotalSaldoAwal= computed(() => sumField(paginatedRows.value, 'saldo_awal'));
const pageTotalIn       = computed(() => sumField(paginatedRows.value, 'qty_in'));
const pageTotalOut      = computed(() => sumField(paginatedRows.value, 'qty_out'));

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
  const sd = document.getElementById('start_date');
  const ed = document.getElementById('end_date');
  return {
    start_date: sd?.value || props.startDate,
    end_date:   ed?.value || props.endDate,
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

const ONLY_FILTER = ['rows','selectedCustomerId','selectedProductId','startDate','endDate','customerName','productName','currentPage','perPage','totalRows'];

function onCustomerChange(e) {
  localCustomerId.value = Number(e.target.value) || null;
  const first = availableProducts.value[0];
  localProductId.value = first ? first.product_id : null;
}

function onProductChange(e) {
  localProductId.value = Number(e.target.value) || null;
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