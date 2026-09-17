<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Cross Odoo - Test</h2>
          <p class="text-sm text-slate-400">
            Ambil data <span class="font-mono">product.product</span> dan
            <span class="font-mono">product.template</span> via XML-RPC.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center justify-center rounded bg-slate-900 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 disabled:opacity-60"
          :disabled="processing"
          @click="run"
        >
          {{ processing ? 'Mengambil data...' : 'Click Me' }}
        </button>
      </div>

      <div
        v-if="error"
        class="mb-4 rounded border border-rose-600 bg-rose-600/20 px-4 py-3 text-sm text-rose-300"
      >
        {{ error }}
      </div>

      <div class="mb-6">
        <h3 class="mb-2 text-lg font-semibold">
          product.product
          <span class="text-sm font-normal text-slate-400">({{ products.length }} data)</span>
        </h3>
        <div class="overflow-x-auto rounded border border-slate-600 bg-white">
          <table class="w-full border-collapse text-xs text-slate-900">
            <thead>
              <tr class="bg-sky-100">
                <th class="border border-slate-300 px-2 py-1.5 text-left font-semibold">ID</th>
                <th class="border border-slate-300 px-2 py-1.5 text-left font-semibold">Display Name</th>
                <th class="border border-slate-300 px-2 py-1.5 text-left font-semibold">Default Code</th>
                <th class="border border-slate-300 px-2 py-1.5 text-right font-semibold">Qty Available</th>
                <th class="border border-slate-300 px-2 py-1.5 text-right font-semibold">Lst Price</th>
                <th class="border border-slate-300 px-2 py-1.5 text-left font-semibold">Product Template</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!products.length">
                <td colspan="6" class="border border-slate-300 px-2 py-6 text-center text-slate-400">
                  Belum ada data. Klik tombol untuk mengambil data.
                </td>
              </tr>
              <tr v-for="row in products" :key="row.id" class="odd:bg-white even:bg-slate-50">
                <td class="border border-slate-300 px-2 py-1">{{ row.id }}</td>
                <td class="border border-slate-300 px-2 py-1">{{ row.display_name || '-' }}</td>
                <td class="border border-slate-300 px-2 py-1 font-mono">{{ row.default_code || '-' }}</td>
                <td class="border border-slate-300 px-2 py-1 text-right">{{ formatNumber(row.qty_available) }}</td>
                <td class="border border-slate-300 px-2 py-1 text-right">{{ formatNumber(row.lst_price) }}</td>
                <td class="border border-slate-300 px-2 py-1">{{ formatM2o(row.product_tmpl_id) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <h3 class="mb-2 text-lg font-semibold">
          product.template
          <span class="text-sm font-normal text-slate-400">({{ templates.length }} data)</span>
        </h3>
        <div class="overflow-x-auto rounded border border-slate-600 bg-white">
          <table class="w-full border-collapse text-xs text-slate-900">
            <thead>
              <tr class="bg-sky-100">
                <th class="border border-slate-300 px-2 py-1.5 text-left font-semibold">ID</th>
                <th class="border border-slate-300 px-2 py-1.5 text-left font-semibold">Name</th>
                <th class="border border-slate-300 px-2 py-1.5 text-left font-semibold">Type</th>
                <th class="border border-slate-300 px-2 py-1.5 text-left font-semibold">Category</th>
                <th class="border border-slate-300 px-2 py-1.5 text-right font-semibold">List Price</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!templates.length">
                <td colspan="5" class="border border-slate-300 px-2 py-6 text-center text-slate-400">
                  Belum ada data. Klik tombol untuk mengambil data.
                </td>
              </tr>
              <tr v-for="row in templates" :key="row.id" class="odd:bg-white even:bg-slate-50">
                <td class="border border-slate-300 px-2 py-1">{{ row.id }}</td>
                <td class="border border-slate-300 px-2 py-1">{{ row.name || '-' }}</td>
                <td class="border border-slate-300 px-2 py-1">{{ row.type || '-' }}</td>
                <td class="border border-slate-300 px-2 py-1">{{ formatM2o(row.categ_id) }}</td>
                <td class="border border-slate-300 px-2 py-1 text-right">{{ formatNumber(row.list_price) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  products: {
    type: Array,
    default: () => [],
  },
  templates: {
    type: Array,
    default: () => [],
  },
  error: {
    type: String,
    default: null,
  },
});

const processing = ref(false);

const run = () => {
  if (processing.value) {
    return;
  }

  processing.value = true;

  router.post(
    '/gmisl/cross-odoo/test/run',
    {},
    {
      preserveScroll: true,
      onFinish: () => {
        processing.value = false;
      },
    },
  );
};

const formatNumber = (value) => {
  if (value == null || value === '') {
    return '-';
  }

  return Number(value).toLocaleString('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });
};

const formatM2o = (value) => {
  if (Array.isArray(value) && value.length > 1) {
    return value[1];
  }

  return '-';
};
</script>
