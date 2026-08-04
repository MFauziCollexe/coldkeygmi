<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Cross Odoo - Rekap Inbound</h2>
          <p class="text-sm text-slate-400">
            Menampilkan ringkasan inbound stock berdasarkan receipt incoming.
          </p>
        </div>
        <div class="text-sm text-slate-400">
          Total: <span class="font-semibold text-slate-200">{{ rows.length }}</span> data
        </div>
      </div>

      <div class="mb-4 rounded border border-slate-300 bg-slate-50 p-4">
        <form method="get" class="grid gap-3 md:grid-cols-3">
          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-600" for="owner_id">Customer</label>
            <select
              id="owner_id"
              name="owner_id"
              class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
              :value="selectedOwnerId ?? ''"
            >
              <option value="">Semua Customer</option>
              <option v-for="owner in owners" :key="owner.owner_id" :value="owner.owner_id">{{ owner.owner_name }}</option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-600" for="product_id">Product</label>
            <select
              id="product_id"
              name="product_id"
              class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
              :value="selectedProductId ?? ''"
            >
              <option value="">Semua Product</option>
              <option v-for="product in products" :key="product.product_id" :value="product.product_id">{{ product.default_code }} - {{ product.product_name }}</option>
            </select>
          </div>

          <div class="flex items-end">
            <button type="submit" class="inline-flex w-full justify-center rounded bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Apply filters</button>
          </div>
        </form>
      </div>

      <div class="overflow-x-auto rounded border border-slate-600 bg-white">
        <table class="w-full border-collapse text-xs text-slate-900" style="table-layout: auto;">
          <thead>
            <tr class="bg-sky-100 text-slate-900">
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Tanggal</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">KD Gudang</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">KD Customer</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">NM Customer</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">No Receipt</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">No PO</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">No Mobil</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">KD Barang</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">NM Barang</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-right font-semibold">Qty</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Lot</th>
              <th class="whitespace-nowrap border border-slate-300 px-2 py-1.5 text-left font-semibold">Expired Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!rows.length">
              <td colspan="12" class="border border-slate-300 px-2 py-6 text-center text-slate-400">Tidak ada data.</td>
            </tr>
            <tr v-for="(row, index) in rows" :key="index" :class="index % 2 === 0 ? 'bg-white' : 'bg-slate-50'">
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.tanggal || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.kd_gudang || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.kd_customer || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.nm_customer || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.no_receipt || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.no_po || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.no_mobil || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 font-mono text-[11px] text-slate-900">{{ row.kd_barang || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.nm_barang || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-right font-mono text-slate-900">{{ formatNumber(row.qty) }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.lot || '-' }}</td>
              <td class="whitespace-nowrap border border-slate-300 px-2 py-1 text-slate-900">{{ row.expired_date || '-' }}</td>
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
  owners: {
    type: Array,
    default: () => [],
  },
  products: {
    type: Array,
    default: () => [],
  },
  selectedOwnerId: {
    type: [String, Number],
    default: null,
  },
  selectedProductId: {
    type: [String, Number],
    default: null,
  },
});

const formatNumber = (value) => {
  if (value == null || value === '') {
    return '-';
  }

  return Number(value).toLocaleString('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });
};
</script>
