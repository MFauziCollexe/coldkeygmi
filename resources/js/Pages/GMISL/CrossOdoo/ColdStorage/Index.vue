<template>
  <AppLayout>
    <main class="min-h-full p-4 text-slate-100 md:p-6">
      <div class="mx-auto max-w-375 space-y-5">
        <header class="flex flex-col gap-4 border-b border-white/10 pb-5 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <p class="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-sky-300">GMISL / Cross Odoo</p>
            <h1 class="text-2xl font-bold tracking-tight text-white md:text-3xl">Kapasitas Cold Storage</h1>
            <p class="mt-1 text-sm text-slate-400">Visualisasi lokasi dan okupansi slot dari master location Odoo.</p>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-right text-xs text-slate-500">Data Odoo diperbarui<br><span class="font-medium text-slate-300">{{ updatedAt }}</span></span>
            <button type="button" class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm font-semibold text-slate-200 transition hover:border-sky-500 hover:text-white disabled:opacity-50" :disabled="refreshing" @click="refreshData">
              {{ refreshing ? 'Memuat...' : 'Refresh' }}
            </button>
          </div>
        </header>

        <section class="grid grid-cols-2 gap-3 xl:grid-cols-4" aria-label="Ringkasan kapasitas">
          <article class="border-l-2 border-slate-500 bg-[#15181d] px-4 py-3.5">
            <p class="text-sm font-medium text-slate-400">Total Kapasitas</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-0.5">
              <p class="text-4xl font-bold tabular-nums text-white">{{ formatNumber(totals.capacity) }}</p>
              <span class="ml-auto flex flex-col items-end text-right text-sm text-slate-400"><span class="text-4xl font-bold tabular-nums text-white">{{ storages.length }}</span><span>Cold Storage</span></span>
            </div>
          </article>
          <article class="border-l-2 border-sky-400 bg-[#15181d] px-4 py-3.5">
            <p class="text-sm font-medium text-slate-400">Slot Terpakai</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-0.5">
              <p class="text-4xl font-bold tabular-nums text-sky-300">{{ formatNumber(totals.used) }}</p>
              <span class="ml-auto flex flex-col items-end text-right text-sm text-slate-400"><span class="text-4xl font-bold tabular-nums text-sky-300">{{ formatPercent(totals.occupancy) }}</span><span>tingkat okupansi</span></span>
            </div>
          </article>
          <article class="border-l-2 border-emerald-400 bg-[#15181d] px-4 py-3.5">
            <p class="text-sm font-medium text-slate-400">Slot Tersedia</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-0.5">
              <p class="text-4xl font-bold tabular-nums text-emerald-300">{{ formatNumber(totals.available) }}</p>
              <p class="ml-auto text-right text-sm text-slate-400">Slot internal tanpa stok</p>
            </div>
          </article>
          <article class="border-l-2 border-red-400 bg-[#15181d] px-4 py-3.5">
            <p class="text-sm font-medium text-slate-400">Status Kritis</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-0.5">
              <p class="text-4xl font-bold tabular-nums text-red-300">{{ totals.critical }}</p>
              <span class="ml-auto flex flex-col items-end text-right text-sm text-slate-400"><span class="text-4xl font-bold tabular-nums text-red-300">85%</span><span>Cold Storage dengan okupansi ≥</span></span>
            </div>
          </article>
        </section>

        <section class="flex flex-col gap-4 border-y border-white/10 py-3 sm:flex-row sm:items-center">
          <div class="flex flex-wrap items-center gap-2" aria-label="Filter status">
            <span class="mr-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Filter</span>
            <button v-for="filter in statusFilters" :key="filter.value" type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold transition" :class="statusFilter === filter.value ? 'bg-sky-400 text-slate-950' : 'text-slate-300 hover:bg-white/10'" @click="statusFilter = filter.value">
              {{ filter.label }}
            </button>
          </div>
        </section>

        <section class="border border-slate-800 bg-[#12151a] p-4 sm:p-5" aria-labelledby="chart-title">
          <div class="mb-4 flex flex-wrap items-start justify-between gap-2">
            <div>
              <h2 id="chart-title" class="text-lg font-semibold text-white">Perbandingan Kapasitas per Cold Storage</h2>
              <p class="mt-1 text-xs text-slate-500">Pilih batang untuk melihat slot yang terisi.</p>
            </div>
            <div class="flex items-center gap-4 text-xs text-slate-400">
              <span class="inline-flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-sm bg-sky-300"></i>Slot Terpakai</span>
              <span class="inline-flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-sm bg-slate-700"></i>Sisa Kapasitas</span>
            </div>
          </div>

          <div v-if="filteredStorages.length" class="overflow-x-auto">
            <div class="relative grid min-w-155 grid-flow-col auto-cols-[minmax(42px,1fr)] items-end gap-3 px-2 pb-2 pt-5" :style="{ height: '255px' }">
              <div class="pointer-events-none absolute inset-x-2 top-5 bottom-7 flex flex-col justify-between border-b border-slate-700/70">
                <div v-for="tick in 4" :key="tick" class="border-t border-dashed border-slate-800"></div>
              </div>
              <button v-for="storage in filteredStorages" :key="storage.id" type="button" class="group relative z-10 flex h-full min-w-0 flex-col items-center justify-end gap-2 rounded-sm px-1 text-center focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300" :aria-label="`${storage.name}, ${storage.used} dari ${storage.capacity} slot terpakai`" :title="customerBreakdownTitle(storage)" @click="openStorage(storage)">
                <span class="text-[10px] tabular-nums text-slate-400 transition group-hover:text-white">{{ storage.used }}/{{ storage.capacity }}</span>
                <div class="relative flex w-full max-w-10 flex-1 items-end justify-center">
                  <div class="relative w-full rounded-t-sm bg-slate-700/80 transition group-hover:bg-slate-600" :style="{ height: `${Math.max(8, storage.capacity / maxCapacity * 100)}%` }">
                    <div class="absolute inset-x-0 bottom-0 rounded-t-sm bg-sky-300 transition group-hover:bg-sky-200" :style="{ height: `${storage.occupancy}%` }"></div>
                  </div>
                </div>
                <span class="whitespace-nowrap text-[10px] font-medium text-slate-400 group-hover:text-white">{{ storage.name }}</span>
              </button>
            </div>
          </div>
          <p v-else class="py-12 text-center text-sm text-slate-500">Tidak ada Cold Storage yang cocok dengan filter.</p>

          <section class="mt-5 border-t border-slate-800 pt-4" aria-labelledby="cards-title">
            <div class="mb-3 flex items-end justify-between">
              <div>
                <h3 id="cards-title" class="text-lg font-semibold text-white">Cold Storage</h3>
                <p class="mt-1 text-xs text-slate-500">Klik kartu untuk melihat rincian slot terisi.</p>
              </div>
              <span class="text-xs text-slate-500">{{ filteredStorages.length }} lokasi</span>
            </div>
            <div v-if="filteredStorages.length" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
              <button v-for="storage in filteredStorages" :key="storage.id" type="button" class="flex h-72 flex-col border border-slate-800 bg-[#15181d] p-4 text-left transition hover:border-slate-600 hover:bg-[#191d23] focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300" @click="openStorage(storage)">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <h4 class="text-base font-semibold text-white">{{ storage.name }}</h4>
                    <p class="mt-0.5 text-[11px] text-slate-500">{{ storage.completeName }}</p>
                  </div>
                  <span class="rounded-full px-2 py-1 text-[10px] font-bold uppercase tracking-wide" :class="statusClasses(storage.status)">{{ statusLabel(storage.status) }}</span>
                </div>
                <div class="mt-4 flex items-end justify-between">
                  <span class="text-xs text-slate-400">Okupansi</span>
                  <span class="text-lg font-bold tabular-nums" :class="statusTextClass(storage.status)">{{ formatPercent(storage.occupancy) }}</span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-800">
                  <div class="h-full rounded-full transition-all" :class="progressClasses(storage.status)" :style="{ width: `${storage.occupancy}%` }"></div>
                </div>
                <div class="mt-2 flex justify-between text-xs tabular-nums text-slate-400">
                  <span>{{ formatNumber(storage.used) }} / {{ formatNumber(storage.capacity) }} slot</span>
                  <span>{{ formatNumber(storage.available) }} tersedia</span>
                </div>
                <div class="mt-auto border-t border-slate-800 pt-2.5">
                  <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500">Customer per CS</p>
                  <div v-if="storage.customerOccupancy?.length" class="space-y-1">
                    <div v-for="customer in storage.customerOccupancy.slice(0, 3)" :key="customer.customer" class="flex items-center justify-between gap-2 text-[11px]">
                      <span class="flex min-w-0 items-center gap-1.5 text-slate-400" :title="customer.customer"><i class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: customerColor(customer.customer) }" aria-hidden="true"></i><span class="truncate">{{ customer.customer }}</span></span>
                      <span class="shrink-0 font-semibold tabular-nums text-sky-300">{{ formatPercent(customer.percentage) }}</span>
                    </div>
                    <p v-if="storage.customerOccupancy.length > 3" class="pt-0.5 text-[10px] font-medium text-slate-500">+{{ storage.customerOccupancy.length - 3 }} customer</p>
                  </div>
                  <p v-else class="text-[11px] text-slate-500">Belum ada customer pada slot terisi.</p>
                </div>
              </button>
            </div>
            <p v-else class="border border-slate-800 bg-[#12151a] py-8 text-center text-sm text-slate-500">Tidak ada Cold Storage yang cocok dengan filter.</p>
          </section>

          <section class="mt-5 border-t border-slate-800 pt-4" aria-labelledby="customer-share-title">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
              <div>
                <h3 id="customer-share-title" class="text-base font-semibold text-white">Okupansi per Customer</h3>
                <p class="mt-1 text-xs text-slate-500">Porsi slot customer dibanding total kapasitas seluruh Cold Storage.</p>
              </div>
              <span class="text-xs tabular-nums text-slate-500">{{ formatNumber(totals.capacity) }} total slot</span>
            </div>
            <div v-if="customerOccupancy.length" class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
              <div v-for="customer in customerOccupancy" :key="customer.customer" class="min-w-0">
                <div class="mb-1.5 flex items-center justify-between gap-3 text-xs">
                  <span class="flex min-w-0 items-center gap-2 truncate font-medium text-slate-300" :title="customer.customer"><i class="h-2.5 w-2.5 shrink-0 rounded-full ring-1 ring-white/10" :style="{ backgroundColor: customerColor(customer.customer) }" aria-hidden="true"></i>{{ customer.customer }}</span>
                  <span class="shrink-0 text-right tabular-nums text-slate-400">{{ formatNumber(customer.occupiedSlots) }} slot · <strong class="text-sky-300">{{ formatPercent(customer.percentage) }}</strong></span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-slate-800">
                  <div class="h-full rounded-full bg-sky-300" :style="{ width: `${Math.min(100, customer.percentage)}%` }"></div>
                </div>
              </div>
            </div>
            <p v-else class="py-5 text-center text-sm text-slate-500">Belum ada slot terisi yang memiliki customer/owner.</p>
          </section>
        </section>

        <p v-if="!storages.length" class="border border-amber-900/70 bg-amber-950/30 px-4 py-3 text-sm text-amber-200">
          Master location Odoo belum memiliki lokasi internal di bawah root Cold Storage (CS).
        </p>
      </div>

      <div v-if="selectedStorage" class="fixed inset-0 z-50 flex items-end justify-center bg-black/70 p-0 backdrop-blur-sm sm:items-center sm:p-5" @click.self="selectedStorage = null" @keydown.esc="selectedStorage = null">
        <section role="dialog" aria-modal="true" :aria-label="`Rincian ${selectedStorage.name}`" class="flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden border border-slate-700 bg-[#11151a] shadow-2xl sm:rounded-lg">
          <header class="flex shrink-0 items-start justify-between border-b border-slate-800 px-5 py-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-widest text-sky-300">Rincian lokasi</p>
              <h2 class="mt-1 text-xl font-bold text-white">{{ selectedStorage.name }}</h2>
              <p class="mt-1 text-xs text-slate-500">{{ selectedStorage.completeName }}</p>
            </div>
            <button type="button" aria-label="Tutup rincian" class="rounded-md border border-slate-700 px-2.5 py-1.5 text-sm text-slate-300 hover:bg-slate-800 hover:text-white" @click="selectedStorage = null">Tutup</button>
          </header>
          <div class="shrink-0 border-b border-slate-800 p-4 sm:px-5 sm:py-4">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
              <article class="border-l-2 border-slate-500 bg-[#171c22] px-3 py-2.5">
                <p class="text-[11px] text-slate-500">Total Slot</p>
                <p class="mt-1 text-lg font-bold tabular-nums text-white">{{ formatNumber(selectedStorage.capacity) }}</p>
              </article>
              <article class="border-l-2 border-sky-400 bg-[#171c22] px-3 py-2.5">
                <p class="text-[11px] text-slate-500">Terpakai</p>
                <p class="mt-1 text-lg font-bold tabular-nums text-sky-300">{{ formatNumber(selectedStorage.used) }}</p>
              </article>
              <article class="border-l-2 border-emerald-400 bg-[#171c22] px-3 py-2.5">
                <p class="text-[11px] text-slate-500">Tersedia</p>
                <p class="mt-1 text-lg font-bold tabular-nums text-emerald-300">{{ formatNumber(selectedStorage.available) }}</p>
              </article>
              <article class="border-l-2 bg-[#171c22] px-3 py-2.5" :class="selectedStorage.status === 'high' ? 'border-red-400' : selectedStorage.status === 'normal' ? 'border-blue-400' : 'border-emerald-400'">
                <p class="text-[11px] text-slate-500">Okupansi</p>
                <p class="mt-1 text-lg font-bold tabular-nums" :class="statusTextClass(selectedStorage.status)">{{ formatPercent(selectedStorage.occupancy) }}</p>
              </article>
            </div>
          </div>
          <div class="shrink-0 border-b border-slate-800 px-4 py-3 sm:px-5">
            <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter status slot">
              <span class="mr-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Tampilkan</span>
              <button v-for="filter in slotFilters" :key="filter.value" type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold transition" :class="slotFilter === filter.value ? 'bg-sky-400 text-slate-950' : 'text-slate-300 hover:bg-white/10'" @click="slotFilter = filter.value">
                {{ filter.label }} <span class="ml-1 tabular-nums opacity-75">{{ slotFilterCounts[filter.value] }}</span>
              </button>
            </div>
          </div>
          <div v-if="visibleSelectedSlots.length" class="flex min-h-0 flex-1 flex-col">
            <div class="shrink-0 bg-[#11151a] px-4 pt-3 sm:px-5">
              <table class="w-full table-fixed border-collapse text-left text-[11px] uppercase tracking-wide text-slate-400">
                <colgroup><col class="w-[55%]"><col class="w-[22.5%]"><col class="w-[22.5%]"></colgroup>
                <thead><tr>
                  <th class="border-b border-slate-800 bg-[#171c22] px-3 py-2">Slot</th>
                  <th class="border-b border-slate-800 bg-[#171c22] px-3 py-2 text-right">Qty On Hand</th>
                  <th class="border-b border-slate-800 bg-[#171c22] px-3 py-2 text-right">Reserved</th>
                </tr></thead>
              </table>
            </div>
            <div class="min-h-0 flex-1 overflow-auto px-4 pb-4 sm:px-5 sm:pb-5">
              <table class="w-full table-fixed border-collapse text-left text-sm">
                <colgroup><col class="w-[55%]"><col class="w-[22.5%]"><col class="w-[22.5%]"></colgroup>
                <tbody>
                  <tr v-for="slot in visibleSelectedSlots" :key="slot.id" class="border-t border-slate-800 text-slate-200">
                    <td class="truncate px-3 py-2"><span class="font-medium text-white">{{ slot.name }}</span><span v-if="slot.completeName !== slot.name" class="ml-2 text-xs text-slate-500">{{ slot.completeName }}</span></td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ formatNumber(slot.quantity) }}</td>
                    <td class="px-3 py-2 text-right tabular-nums text-slate-400">{{ formatNumber(slot.reserved) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <p v-else class="flex-1 py-12 text-center text-sm text-slate-500">{{ slotFilter === 'unused' ? 'Tidak ada slot kosong pada lokasi ini.' : 'Tidak ada slot terpakai pada lokasi ini.' }}</p>
        </section>
      </div>
    </main>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  storages: { type: Array, default: () => [] },
  customerOccupancy: { type: Array, default: () => [] },
  updatedAt: { type: String, default: '' },
});

const statusFilter = ref('all');
const selectedStorage = ref(null);
const slotFilter = ref('used');
const refreshing = ref(false);

const statusFilters = [
  { value: 'all', label: 'Semua Unit' },
  { value: 'high', label: 'Tinggi (≥85%)' },
  { value: 'normal', label: 'Normal (50–84%)' },
  { value: 'low', label: 'Rendah (<50%)' },
];

const customerColors = [
  '#38bdf8', '#34d399', '#fbbf24', '#fb7185', '#a78bfa',
  '#2dd4bf', '#f97316', '#60a5fa', '#e879f9', '#a3e635',
];

const slotFilters = [
  { value: 'used', label: 'Terpakai' },
  { value: 'unused', label: 'Belum terpakai' },
  { value: 'all', label: 'Semua' },
];

const storages = computed(() => props.storages || []);
const customerOccupancy = computed(() => props.customerOccupancy || []);
const filteredStorages = computed(() => statusFilter.value === 'all'
  ? storages.value
  : storages.value.filter((storage) => storage.status === statusFilter.value));
const visibleSelectedSlots = computed(() => {
  if (!selectedStorage.value) return [];
  if (slotFilter.value === 'used') return selectedStorage.value.slots.filter((slot) => Number(slot.quantity) > 0);
  if (slotFilter.value === 'unused') return selectedStorage.value.slots.filter((slot) => Number(slot.quantity) <= 0);
  return selectedStorage.value.slots;
});
const slotFilterCounts = computed(() => {
  const slots = selectedStorage.value?.slots || [];
  const used = slots.filter((slot) => Number(slot.quantity) > 0).length;
  return { used, unused: slots.length - used, all: slots.length };
});
const maxCapacity = computed(() => Math.max(1, ...filteredStorages.value.map((storage) => Number(storage.capacity || 0))));
const totals = computed(() => {
  const capacity = storages.value.reduce((sum, storage) => sum + Number(storage.capacity || 0), 0);
  const used = storages.value.reduce((sum, storage) => sum + Number(storage.used || 0), 0);
  return {
    capacity,
    used,
    available: Math.max(0, capacity - used),
    occupancy: capacity > 0 ? used / capacity * 100 : 0,
    critical: storages.value.filter((storage) => storage.status === 'high').length,
  };
});

function formatNumber(value) {
  return Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
}

function formatPercent(value) {
  return `${Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 1 })}%`;
}

function customerColor(customerName) {
  let hash = 0;
  for (const character of String(customerName || '').toLowerCase()) {
    hash = (hash * 31 + character.codePointAt(0)) >>> 0;
  }

  return customerColors[hash % customerColors.length];
}

function statusLabel(status) {
  return status === 'high' ? 'Kritis' : status === 'normal' ? 'Normal' : 'Tersedia';
}

function statusClasses(status) {
  return status === 'high'
    ? 'bg-red-950 text-red-300'
    : status === 'normal'
      ? 'bg-blue-950 text-blue-300'
      : 'bg-emerald-950 text-emerald-300';
}

function statusTextClass(status) {
  return status === 'high' ? 'text-red-300' : status === 'normal' ? 'text-blue-300' : 'text-emerald-300';
}

function progressClasses(status) {
  return status === 'high' ? 'bg-red-400' : status === 'normal' ? 'bg-blue-400' : 'bg-emerald-400';
}

function customerBreakdownTitle(storage) {
  const customers = storage.customerOccupancy || [];
  if (customers.length === 0) {
    return `${storage.name}\nBelum ada stok dengan customer/owner pada unit ini.`;
  }

  return [
    `${storage.name} · Customer memakai kapasitas unit`,
    ...customers.map((customer) => `${customer.customer}: ${formatPercent(customer.percentage)} (${formatNumber(customer.occupiedSlots)} slot)`),
  ].join('\n');
}

function openStorage(storage) {
  selectedStorage.value = storage;
  slotFilter.value = 'used';
}

function refreshData() {
  refreshing.value = true;
  router.reload({
    only: ['storages', 'customerOccupancy', 'updatedAt'],
    preserveScroll: true,
    onFinish: () => { refreshing.value = false; },
  });
}
</script>