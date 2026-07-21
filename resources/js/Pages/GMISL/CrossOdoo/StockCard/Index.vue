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
              <th class="border border-slate-700 px-3 py-2">Reference</th>
              <th class="border border-slate-700 px-3 py-2">Picking</th>
              <th class="border border-slate-700 px-3 py-2">Customer</th>
              <th class="border border-slate-700 px-3 py-2">Product Code</th>
              <th class="border border-slate-700 px-3 py-2">Product Name</th>
              <th class="border border-slate-700 px-3 py-2">Lot</th>
              <th class="border border-slate-700 px-3 py-2">From</th>
              <th class="border border-slate-700 px-3 py-2">To</th>
              <th class="border border-slate-700 px-3 py-2">Qty Delivered</th>
              <th class="border border-slate-700 px-3 py-2">Qty Returned</th>
              <th class="border border-slate-700 px-3 py-2">Running Balance</th>
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
              <td class="border border-slate-700 px-3 py-2">{{ row.reference || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.picking_number || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.nama_customer || row.customer || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.kode_produk || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.nama_product || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.lot_number || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.from_location || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.to_location || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2 text-right">{{ formatNumber(row.qty_delivered) }}</td>
              <td class="border border-slate-700 px-3 py-2 text-right">{{ formatNumber(row.qty_returned) }}</td>
              <td class="border border-slate-700 px-3 py-2 text-right">{{ formatNumber(row.running_balance) }}</td>
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
