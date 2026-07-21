<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Cross Odoo - Stock Card</h2>
          <p class="text-sm text-slate-400">
            Menampilkan daftar stock move line dari Odoo untuk customer <span class="font-semibold text-slate-200">BPK. RUMADI (BOYOLALI)</span>.
          </p>
        </div>
      </div>

      <div class="overflow-x-auto rounded border border-slate-700 bg-slate-900 p-4">
        <table class="min-w-full table-auto text-sm text-left text-slate-100">
          <thead>
            <tr class="bg-slate-800 text-slate-300">
              <th class="border border-slate-700 px-3 py-2">Tanggal</th>
              <th class="border border-slate-700 px-3 py-2">Reference</th>
              <th class="border border-slate-700 px-3 py-2">Customer</th>
              <th class="border border-slate-700 px-3 py-2">Product Code</th>
              <th class="border border-slate-700 px-3 py-2">Product Name</th>
              <th class="border border-slate-700 px-3 py-2">Lot</th>
              <th class="border border-slate-700 px-3 py-2">Qty In</th>
              <th class="border border-slate-700 px-3 py-2">Qty Out</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!rows.length" class="bg-slate-950 text-slate-400">
              <td class="border border-slate-700 px-3 py-4 text-center" colspan="8">
                Tidak ada data untuk customer yang dipilih.
              </td>
            </tr>
            <tr v-for="(row, index) in rows" :key="index" class="odd:bg-slate-950 even:bg-slate-900">
              <td class="border border-slate-700 px-3 py-2">{{ row.transaction_date }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.reference }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.customer }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.product_code }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.product_name }}</td>
              <td class="border border-slate-700 px-3 py-2">{{ row.lot_name || '-' }}</td>
              <td class="border border-slate-700 px-3 py-2 text-right">{{ formatNumber(row.qty_in) }}</td>
              <td class="border border-slate-700 px-3 py-2 text-right">{{ formatNumber(row.qty_out) }}</td>
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
});

const rows = props.rows || [];

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
