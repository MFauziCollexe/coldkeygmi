<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Cross Odoo - Stock Card</h2>
          <p class="text-sm text-slate-400">
            Menampilkan stock card Odoo untuk customer
            <span class="font-semibold text-slate-200">{{ customerLabel }}</span>
            dan product
            <span class="font-semibold text-slate-200">{{ productLabel }}</span>.
          </p>
        </div>
      </div>

      <div class="overflow-x-auto rounded border border-slate-700 bg-slate-900 p-4">
        <table class="min-w-full table-auto text-sm text-left text-slate-100">
          <thead>
            <tr class="bg-slate-800 text-slate-300">
              <th class="border border-slate-700 px-3 py-2">Tanggal</th>
              <th class="border border-slate-700 px-3 py-2">Owner</th>
              <th class="border border-slate-700 px-3 py-2">Product</th>
              <th class="border border-slate-700 px-3 py-2">Code</th>
              <th class="border border-slate-700 px-3 py-2">Lot</th>
              <th class="border border-slate-700 px-3 py-2">Exp</th>
              <th class="border border-slate-700 px-3 py-2">Movement</th>
              <th class="border border-slate-700 px-3 py-2">From</th>
              <th class="border border-slate-700 px-3 py-2">To</th>
              <th class="border border-slate-700 px-3 py-2">Qty In</th>
              <th class="border border-slate-700 px-3 py-2">Qty Out</th>
              <th class="border border-slate-700 px-3 py-2">Balance</th>
              <th class="border border-slate-700 px-3 py-2">Sack</th>
              <th class="border border-slate-700 px-3 py-2">Pallet</th>
              <th class="border border-slate-700 px-3 py-2">Vehicle No</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!rows.length" class="bg-slate-950 text-slate-400">
              <td class="border border-slate-700 px-3 py-4 text-center" colspan="12">
                Tidak ada data untuk filter yang dipilih.
              </td>
            </tr>
            <tr v-for="(row, index) in rows" :key="index" class="odd:bg-slate-950 even:bg-slate-900">
              <td class="border border-slate-700 px-3 py-2">{{ formatDate(row.transaction_date) }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.owner_name || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.product_name || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.product_code || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.lot_number || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.expiration_date || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.movement_type || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.source_location || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.destination_location || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2 text-right">{{ formatNumber(row.qty_in) }}</td>
              <td class="border border-slate-700 px-3 py-2 text-right">{{ formatNumber(row.qty_out) }}</td>
              <td class="border border-slate-700 px-3 py-2 text-right">{{ formatNumber(row.running_balance) }}</td>
              <td class="border border-slate-700 px-3 py-2 text-right">{{ formatNumber(row.x_studio_total_in_sack) }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.gmi_pallet_assigned || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.x_studio_no_kendaraan || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  rows: {
    type: Array,
    default: () => [],
  },
  customerName: {
    type: String,
    default: 'Customer',
  },
  productName: {
    type: String,
    default: 'Product',
  },
});

const rows = props.rows || [];
const customerLabel = props.customerName || 'Customer';
const productLabel = props.productName || 'Product';

function formatDate(value) {
  if (!value) {
    return '-';
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return date.toLocaleString('id-ID', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function formatNumber(value) {
  if (value === null || value === undefined) {
    return '-';
  }

  return Number(value).toLocaleString('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });
}
</script>
