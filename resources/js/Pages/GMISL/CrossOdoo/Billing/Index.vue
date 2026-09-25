<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
  rows: { type: Array, default: () => [] },
  customers: { type: Array, default: () => [] },
  products: { type: Array, default: () => [] },
  selectedCustomerId: { type: [String, Number], default: null },
  selectedProductId: { type: [String, Number], default: null },
  startDate: { type: String, default: '' },
  endDate: { type: String, default: '' },
  totalRows: { type: Number, default: 0 },
  dailySummaries: { type: Object, default: () => ({}) },
});

const localCustomerId = ref(props.selectedCustomerId);
const localProductId = ref(props.selectedProductId);
const startDateInput = ref(props.startDate);
const endDateInput = ref(props.endDate);
const expandedDates = ref(new Set());
const maximumRangeDays = 31;

const maximumEndDate = computed(() => {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(startDateInput.value)) return '';

  const start = new Date(`${startDateInput.value}T00:00:00`);
  if (Number.isNaN(start.getTime())) return '';
  start.setDate(start.getDate() + maximumRangeDays - 1);

  return [
    start.getFullYear(),
    String(start.getMonth() + 1).padStart(2, '0'),
    String(start.getDate()).padStart(2, '0'),
  ].join('-');
});

const customerOptions = computed(() => props.customers.map((customer) => ({
  customer_id: customer.customer_id,
  label: customer.customer_name,
})));

const productOptions = computed(() => {
  const customerId = Number(localCustomerId.value ?? -1);
  return props.products
    .filter((product) => Number(product.customer_id) === customerId)
    .map((product) => ({
      product_id: product.product_id,
      label: `${product.default_code ? `${product.default_code} - ` : ''}${product.product_name}`,
    }));
});

const groupedRows = computed(() => {
  const groups = new Map();

  Object.entries(props.dailySummaries).forEach(([date, summary]) => {
    groups.set(date, {
      date,
      rows: [],
      opening: Number(summary.opening ?? 0),
      in: Number(summary.in ?? 0),
      out: Number(summary.out ?? 0),
      closing: Number(summary.closing ?? 0),
    });
  });

  props.rows.forEach((row) => {
    if (!groups.has(row.Date)) {
      groups.set(row.Date, {
        date: row.Date,
        rows: [],
        opening: Number(props.dailySummaries[row.Date]?.opening ?? 0),
        in: Number(props.dailySummaries[row.Date]?.in ?? 0),
        out: Number(props.dailySummaries[row.Date]?.out ?? 0),
        closing: Number(props.dailySummaries[row.Date]?.closing ?? 0),
      });
    }

    const group = groups.get(row.Date);
    group.rows.push(row);
    if (!props.dailySummaries[row.Date]) {
      group.opening += Number(row['Saldo Awal'] || 0);
      group.in += Number(row.In || 0);
      group.out += Number(row.Out || 0);
      group.closing += Number(row['Saldo Akhir'] || 0);
    }
  });

  return Array.from(groups.values()).map((group) => ({
    ...group,
    locationCount: new Set(group.rows.map((row) => row.Location)).size,
  }));
});

function onCustomerChange(value) {
  localCustomerId.value = value || null;
  const firstProduct = productOptions.value[0];
  localProductId.value = firstProduct ? firstProduct.product_id : null;
}

function applyFilters() {
  if (!startDateInput.value || !endDateInput.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Tanggal belum lengkap',
      text: 'Silakan pilih tanggal mulai dan tanggal akhir.',
    });
    return;
  }

  const start = new Date(`${startDateInput.value}T00:00:00`);
  const end = new Date(`${endDateInput.value}T00:00:00`);
  const rangeDays = Math.floor((end - start) / 86400000) + 1;
  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || rangeDays < 1) {
    Swal.fire({
      icon: 'warning',
      title: 'Rentang tanggal tidak valid',
      text: 'Tanggal akhir harus sama atau setelah tanggal mulai.',
    });
    return;
  }

  if (rangeDays > maximumRangeDays) {
    Swal.fire({
      icon: 'warning',
      title: 'Rentang tanggal terlalu panjang',
      text: 'Data Billing maksimal dapat diambil selama 1 bulan atau 31 hari.',
    });
    return;
  }

  router.get('/gmisl/cross-odoo/billing', {
    customer_id: localCustomerId.value || undefined,
    product_id: localProductId.value || undefined,
    start_date: startDateInput.value || undefined,
    end_date: endDateInput.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
}

function formatNumber(value) {
  return Number(value || 0).toLocaleString('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });
}

function formatDetailDates(row = {}) {
  const times = Array.isArray(row.Times) ? row.Times : [];
  const formatted = times.map((value) => {
    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
    return match ? `${match[3]}/${match[2]}/${match[1].slice(-2)} ${match[4]}:${match[5]}` : String(value);
  });

  return formatted.length ? formatted : [`${row.Date || '-'} 00:00`];
}

function toggleDate(date) {
  const next = new Set(expandedDates.value);
  if (next.has(date)) next.delete(date);
  else next.add(date);
  expandedDates.value = next;
}
</script>

<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Cross Odoo - Billing</h2>
          <p class="text-sm text-slate-400">Daily stock movement ledger per location.</p>
        </div>
        <div class="text-sm text-slate-400">
          Total: <span class="font-semibold text-slate-200">{{ totalRows }}</span> data
        </div>
      </div>

      <div class="mb-4 rounded border border-slate-300 bg-slate-50 p-4">
        <div class="grid gap-3">
          <div class="flex items-center gap-3">
            <label class="w-28 shrink-0 text-xs font-semibold uppercase tracking-wider text-slate-600" for="billing_customer">Customer :</label>
            <SearchableSelect id="billing_customer" v-model="localCustomerId" variant="light" class="min-w-0 w-full sm:w-72" style="width: calc(70% - 208px)" :options="customerOptions" option-value="customer_id" option-label="label" placeholder="Ketik untuk mencari customer..." empty-label="Pilih customer" input-class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500" button-class="border-l border-slate-300" @update:modelValue="onCustomerChange" />
          </div>
          <div class="flex items-center gap-3">
            <label class="w-28 shrink-0 text-xs font-semibold uppercase tracking-wider text-slate-600" for="billing_product">Product :</label>
            <SearchableSelect id="billing_product" v-model="localProductId" variant="light" class="min-w-0 flex-none" style="width: calc(100% - 280px)" :options="productOptions" option-value="product_id" option-label="label" placeholder="Ketik untuk mencari product..." empty-label="Pilih product" input-class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500" button-class="border-l border-slate-300" />
          </div>
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label class="w-28 shrink-0 text-xs font-semibold uppercase tracking-wider text-slate-600" for="billing_start_date">Tanggal :</label>
            <input id="billing_start_date" v-model="startDateInput" type="date" class="min-w-0 flex-1 rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500" />
            <span class="text-sm text-slate-500">s/d</span>
            <input id="billing_end_date" v-model="endDateInput" :max="maximumEndDate" type="date" class="min-w-0 flex-1 rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500" />
            <button type="button" class="inline-flex w-full justify-center rounded bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 sm:w-36" @click="applyFilters()">Apply filter</button>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto rounded border border-slate-600 bg-white">
        <table class="w-full border-collapse text-xs text-slate-900">
          <thead>
            <tr class="bg-sky-100 text-slate-900">
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Date</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Owner</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Transaksi</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Destination package</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Kode barang</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Nama barang</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Source Document</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Expired</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Location</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">To</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">Saldo Awal</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">IN</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">OUT</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">Saldo Akhir</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!rows.length"><td colspan="14" class="border border-slate-300 px-2 py-6 text-center text-slate-400">Tidak ada data untuk filter yang dipilih.</td></tr>
            <template v-for="group in groupedRows" :key="group.date">
              <tr class="cursor-pointer bg-sky-50 text-slate-900 hover:bg-sky-100" @click="toggleDate(group.date)">
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 font-semibold">
                  <span class="mr-2 inline-block w-4 text-center">{{ expandedDates.has(group.date) ? '-' : '+' }}</span>
                  {{ group.date }}
                </td>
                <td colspan="9" class="border border-slate-300 px-2 py-1.5 font-semibold">Detail ({{ group.locationCount }} Location)</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">{{ formatNumber(group.opening) }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">{{ formatNumber(group.in) }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">{{ formatNumber(group.out) }}</td>
                <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">{{ formatNumber(group.closing) }}</td>
              </tr>
              <template v-if="expandedDates.has(group.date)">
                <tr class="bg-slate-100 text-slate-700">
                  <td class="border border-slate-300 px-2 py-1.5"></td>
                  <td class="border border-slate-300 px-2 py-1.5 font-semibold">Owner</td>
                  <td class="border border-slate-300 px-2 py-1.5 font-semibold">Transaksi</td>
                  <td class="border border-slate-300 px-2 py-1.5 font-semibold">Destination package</td>
                  <td class="border border-slate-300 px-2 py-1.5 font-semibold">Kode barang</td>
                  <td class="border border-slate-300 px-2 py-1.5 font-semibold">Nama barang</td>
                  <td class="border border-slate-300 px-2 py-1.5 font-semibold">Source Document</td>
                  <td class="border border-slate-300 px-2 py-1.5 font-semibold">Expired</td>
                  <td class="border border-slate-300 px-2 py-1.5 font-semibold">Location</td>
                  <td class="border border-slate-300 px-2 py-1.5 font-semibold">To</td>
                  <td class="border border-slate-300 px-2 py-1.5 text-right font-semibold">Saldo Awal</td>
                  <td class="border border-slate-300 px-2 py-1.5 text-right font-semibold">IN</td>
                  <td class="border border-slate-300 px-2 py-1.5 text-right font-semibold">OUT</td>
                  <td class="border border-slate-300 px-2 py-1.5 text-right font-semibold">Saldo Akhir</td>
                </tr>
                <tr v-for="(row, index) in group.rows" :key="`${group.date}-${row.Location}-${index}`" class="bg-white hover:bg-blue-50">
                  <td class="border border-slate-300 px-2 py-1.5">
                    <div v-for="timestamp in formatDetailDates(row)" :key="timestamp">{{ timestamp }}</div>
                  </td>
                  <td class="border border-slate-300 px-2 py-1.5">{{ row.Owner || '-' }}</td>
                  <td class="border border-slate-300 px-2 py-1.5">{{ row.Transaksi || '-' }}</td>
                  <td class="border border-slate-300 px-2 py-1.5">{{ row['Destination package'] || '-' }}</td>
                  <td class="border border-slate-300 px-2 py-1.5">{{ row['Kode barang'] || '-' }}</td>
                  <td class="border border-slate-300 px-2 py-1.5">{{ row['Nama barang'] || '-' }}</td>
                  <td class="border border-slate-300 px-2 py-1.5">{{ row['Source Document'] || '-' }}</td>
                  <td class="border border-slate-300 px-2 py-1.5">{{ row.Expired || '-' }}</td>
                  <td class="border border-slate-300 px-2 py-1.5">{{ row.Location || '-' }}</td>
                  <td class="border border-slate-300 px-2 py-1.5">{{ row.To || '-' }}</td>
                  <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right">{{ formatNumber(row['Saldo Awal']) }}</td>
                  <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right">{{ formatNumber(row.In) }}</td>
                  <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right">{{ formatNumber(row.Out) }}</td>
                  <td class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right">{{ formatNumber(row['Saldo Akhir']) }}</td>
                </tr>
              </template>
            </template>
          </tbody>
        </table>
      </div>

    </div>
  </AppLayout>
</template>
