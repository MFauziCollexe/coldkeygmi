<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Cross Odoo - Rekap Outbound</h2>
          <p class="text-sm text-slate-400">
            Menampilkan rekap outbound Odoo untuk customer
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
            :aria-disabled="!filtersApplied"
            :class="['inline-flex items-center justify-center rounded bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700', !filtersApplied && 'pointer-events-none opacity-50']"
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
          </div>

          <div class="flex gap-3">
            <div class="flex min-w-0 flex-1 items-center gap-3">
            <label class="w-28 shrink-0 text-xs font-semibold uppercase tracking-wider text-slate-600" for="period">Periode :</label>
            <input
              id="period"
              type="month"
              class="min-w-0 flex-1 rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              v-model="periodInput"
            />
            </div>

            <div class="flex w-36 shrink-0 items-end">
            <button
              type="button"
              class="inline-flex w-full justify-center rounded bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
              @click="applyFilters"
            >
              Apply filter
            </button>
            </div>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto rounded border border-slate-600 bg-white">
        <table class="w-full border-collapse text-xs text-slate-900" style="table-layout: auto;">
          <thead>
            <tr class="bg-sky-100 text-slate-900">
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-center font-semibold">NO</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">TANGGAL</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">KD CUSTOMER</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">NM CUSTOMER</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">NO DELIVERY</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">SOURCE DOCUMENTS</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">NO MOBIL</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">KD BARANG</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">NM BARANG</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">QTY</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">QTY KG</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">UOM</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!paginatedRows.length">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-6 text-center text-slate-400" colspan="12">
                {{ filtersApplied ? 'Tidak ada data untuk filter yang dipilih.' : 'Pilih filter lalu klik Apply filter untuk memuat data.' }}
              </td>
            </tr>
            <template v-for="(row, index) in paginatedRows" :key="index">
              <tr v-if="row.is_subtotal && allItems" class="bg-slate-200 text-slate-900">
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 font-bold" colspan="9">SUBTOTAL - {{ row.nm_barang }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-bold text-slate-900">{{ formatNumber(row.qty) }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-bold text-slate-900">{{ formatNumber(row.qty_kg) }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5" colspan="1"></td>
              </tr>
              <tr
                v-else
                :class="(index % 2 === 0 ? 'bg-white' : 'bg-slate-50') + ' text-slate-900'"
                class="hover:bg-blue-50"
              >
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-center text-slate-900">{{ allItems ? row.__no : index + 1 }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ formatDateShort(row.tanggal) }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.kd_customer || '-' }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.nm_customer || '-' }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.no_delivery || '-' }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.source_documents || '-' }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.no_mobil || '-' }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.kd_barang || '-' }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.nm_barang || '-' }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right text-slate-900">{{ formatNumber(row.qty) }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right text-slate-900">{{ formatNumber(row.qty_kg) }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.uom || '-' }}</td>
              </tr>
            </template>
          </tbody>
          <tfoot>
            <tr class="bg-slate-200 text-slate-900">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-bold" colspan="9">TOTAL</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-bold text-slate-900">{{ formatNumber(totalQty) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-bold text-slate-900">{{ formatNumber(totalQtyKg) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5" colspan="1"></td>
            </tr>
          </tfoot>
        </table>
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
  rows:               { type: Array,    default: () => [] },
  filtersApplied:     { type: Boolean,  default: false },
  customers:          { type: Array,    default: () => [] },
  products:           { type: Array,    default: () => [] },
  selectedCustomerId: { type: [String, Number], default: null },
  selectedProductId:  { type: [String, Number], default: null },
  customerName:       { type: String,   default: 'Customer' },
  productName:        { type: String,   default: 'Product' },
  allItems:           { type: Boolean,  default: false },
  startDate:          { type: String,   default: '' },
  endDate:            { type: String,   default: '' },
  period:             { type: String,   default: '' },
  totalRows:          { type: Number,   default: 0 },
  totalQty:           { type: Number,   default: 0 },
  totalQtyKg:         { type: Number,   default: 0 },
});

const todayDate    = new Date();
const defaultPeriod = `${todayDate.getFullYear()}-${String(todayDate.getMonth() + 1).padStart(2, '0')}`;

const periodInput = ref(props.period || defaultPeriod);

watch(() => props.period, (value) => { if (value) periodInput.value = value; });

const customerOptions = computed(() =>
  (props.customers || []).map(c => ({ customer_id: c.customer_id, label: c.customer_name }))
);

const availableProducts = computed(() => {
  const cid = Number(localCustomerId.value ?? -1);
  const list = (props.products || [])
    .filter(p => Number(p.customer_id) === cid)
    .map(p => ({ product_id: p.product_id, label: (p.default_code ? p.default_code + ' - ' : '') + p.product_name }));
  return [{ product_id: 0, label: 'All Item' }, ...list];
});

const localCustomerId = ref(props.selectedCustomerId);
const localProductId  = ref(props.selectedProductId ?? 0);

const paginatedRows = computed(() => {
  if (!props.allItems) return props.rows || [];
  let no = 0;
  return (props.rows || []).map(row => (row.is_subtotal ? row : { ...row, __no: ++no }));
});

const exportUrl = computed(() => {
  if (!props.filtersApplied) return '#';
  const params = new URLSearchParams();
  if (localCustomerId.value !== null && localCustomerId.value !== undefined && localCustomerId.value !== '') params.set('customer_id', localCustomerId.value);
  if (Number(localProductId.value) > 0) params.set('product_id', localProductId.value);
  if (periodInput.value) params.set('period', periodInput.value);
  params.set('filters_applied', '1');
  return `/gmisl/cross-odoo/rekap-outbound/export?${params.toString()}`;
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

function buildParams(overrides = {}) {
  return {
    customer_id: localCustomerId.value ?? undefined,
    product_id:  Number(localProductId.value) > 0 ? localProductId.value : undefined,
    period:      periodInput.value     || undefined,
    filters_applied: 1,
    ...overrides,
  };
}

function reload(params, only) {
  router.get('/gmisl/cross-odoo/rekap-outbound', params, {
    preserveState: true,
    preserveScroll: true,
    only,
  });
}

const ONLY_FILTER = ['rows','filtersApplied','selectedCustomerId','selectedProductId','customerName','productName','period','allItems','totalRows','totalQty','totalQtyKg'];

function onCustomerChange(value) {
  localCustomerId.value = value || null;
  localProductId.value = 0;
}

function onProductChange(value) {
  localProductId.value = value || null;
}

function applyFilters() {
  reload(buildParams(), ONLY_FILTER);
}
</script>