<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <!-- Header -->
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Cross Odoo - Stock Card</h2>
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

      <!-- Filters -->
      <div class="mb-4 rounded border border-slate-300 bg-slate-50 p-4">
        <form ref="filterForm" method="get" class="grid gap-3 sm:grid-cols-4" @change="submitFilters">
          <input type="hidden" name="page" :value="currentPage" />
          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-600" for="owner_id">Owner</label>
            <select
              id="owner_id"
              name="owner_id"
              class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              :value="selectedOwnerId"
              @change="submitFilters"
            >
              <option v-for="owner in owners" :key="owner.owner_id" :value="owner.owner_id">
                {{ owner.owner_name }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-600" for="start_date">Start Date</label>
            <input
              id="start_date"
              name="start_date"
              type="date"
              class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              :value="startDate"
            />
          </div>

          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-600" for="end_date">End Date</label>
            <input
              id="end_date"
              name="end_date"
              type="date"
              class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              :value="endDate"
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

      <!-- Excel-like Table -->
      <div class="overflow-x-auto rounded border border-slate-600 bg-white">
        <table class="w-full border-collapse text-xs text-slate-900" style="table-layout: auto;">
          <thead>
            <tr class="bg-sky-100 text-slate-900">
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-center font-semibold">No</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Tanggal</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Owner</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Product</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Code</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Document</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Reference</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Origin</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Movement Type</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Direction</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">From</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">To</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Lot</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Exp</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Package</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Pallet</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">Sack</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">Qty In</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">Qty Out</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">Net Change</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">Balance</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">Closing</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Vehicle No</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!paginatedRows.length">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-6 text-center text-slate-400" colspan="16">
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
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ formatDate(row.transaction_date) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.owner_name || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.product_name || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 font-mono text-[11px] text-slate-900">{{ row.product_code || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.document_number || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.reference || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.origin || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">
                <span :class="movementClass(row.movement_type)" class="inline-block rounded px-1.5 py-0.5 text-[10px] font-semibold leading-tight">
                  {{ row.movement_type || '-' }}
                </span>
              </td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.movement_direction || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.source_location || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.destination_location || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.lot_number || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ formatDateShort(row.expiration_date) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.package_name || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 font-mono text-[11px] text-slate-900">{{ row.pallet || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.sack) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.qty_in) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.qty_out) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.net_change) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono font-semibold text-slate-900">{{ formatNumber(row.running_balance) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.closing_balance) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.vehicle || '-' }}</td>
            </tr>
          </tbody>
          <tfoot v-if="paginatedRows.length">
            <tr class="bg-sky-50 font-semibold text-slate-900">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right" colspan="16">Total Halaman</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-mono">{{ formatNumber(pageTotalSack) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-mono">{{ formatNumber(pageTotalIn) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-mono">{{ formatNumber(pageTotalOut) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5"></td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5"></td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5"></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Pagination -->
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
const totalRows = computed(() => Number(props.totalRows || 0));

const perPage = ref(props.perPage ?? 25);
const currentPage = ref(props.currentPage ?? 1);

const totalPages = computed(() => Math.max(1, Math.ceil(totalRows.value / perPage.value)));
const paginatedRows = computed(() => allRows.value);

const visiblePages = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

  const pages = [];
  pages.push(1);
  if (current > 3) pages.push('...');
  for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
    pages.push(i);
  }
  if (current < total - 2) pages.push('...');
  pages.push(total);
  return pages;
});

function sumField(rows, field) {
  return rows.reduce((acc, row) => acc + (Number(row[field]) || 0), 0);
}

const pageTotalIn = computed(() => sumField(paginatedRows.value, 'qty_in'));
const pageTotalOut = computed(() => sumField(paginatedRows.value, 'qty_out'));
const pageTotalSack = computed(() => sumField(paginatedRows.value, 'sack'));
const grandTotalIn = computed(() => sumField(allRows.value, 'qty_in'));
const grandTotalOut = computed(() => sumField(allRows.value, 'qty_out'));
const grandTotalSack = computed(() => sumField(allRows.value, 'sack'));

const movementColors = {
  'RECEIPT': 'bg-emerald-100 text-emerald-700',
  'DELIVERY': 'bg-sky-100 text-sky-700',
  'CUSTOMER RETURN': 'bg-amber-100 text-amber-700',
  'VENDOR RETURN': 'bg-orange-100 text-orange-700',
  'TRANSFER': 'bg-violet-100 text-violet-700',
  'ADJUSTMENT IN': 'bg-teal-100 text-teal-700',
  'ADJUSTMENT OUT': 'bg-rose-100 text-rose-700',
};

function movementClass(type) {
  return movementColors[String(type || '').toUpperCase()] || 'bg-slate-100 text-slate-600';
}

function formatDate(value) {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString('id-ID', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function formatDateShort(value) {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });
}

function submitFilters() {
  currentPage.value = 1;
  submitForm();
}

function changePage(page) {
  const safePage = Math.max(1, Math.min(page, totalPages.value));
  if (safePage === currentPage.value) {
    return;
  }
  currentPage.value = safePage;
  submitForm();
}

function submitForm() {
  if (filterForm.value) {
    filterForm.value.submit();
  }
}

const filterForm = ref(null);

function formatNumber(value) {
  if (value === null || value === undefined) return '-';
  return Number(value).toLocaleString('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });
}
</script>
