<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-bold">Listrik - Pencatatan</h2>
        <div class="flex flex-col gap-2 sm:flex-row">
          <button
            type="button"
            @click="openModal"
            class="inline-flex items-center justify-center rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
          >
            Tambah
          </button>
        </div>
      </div>

      <div class="mb-4">
        <form @submit.prevent="search" class="flex flex-col gap-3 lg:flex-row">
          <input v-model="bulan" type="month" class="w-full rounded border border-slate-300 px-4 py-2 bg-white text-black lg:max-w-xs" />
          <button type="submit" class="w-full rounded bg-indigo-600 px-4 py-2 text-white lg:w-auto">Tampilkan</button>
          <button type="button" @click="resetSearch" class="w-full rounded bg-gray-500 px-4 py-2 text-white lg:w-auto">Reset</button>
        </form>
      </div>

      <div class="mb-4">
        <div class="rounded-md border border-slate-200 bg-white overflow-hidden shadow-sm">
          <div class="bg-white">
            <ul class="flex">
              <li v-for="(tab, i) in lokasiTabs" :key="tab.value" class="-mb-px">
                <button
                  type="button"
                  @click="selectLokasi(tab.value)"
                  class="px-4 py-2 text-sm font-semibold border border-slate-200 bg-white transition"
                  :class="[
                    activeLokasi === tab.value ? 'border-b-0 text-slate-900' : 'text-slate-600 hover:text-slate-800',
                    i === 0 ? 'rounded-l-md' : '',
                    i === lokasiTabs.length - 1 ? 'rounded-r-md' : ''
                  ]"
                >
                  {{ tab.label }}
                </button>
              </li>
            </ul>
          </div>

          <div class="p-4 bg-white text-black">
            <div class="overflow-x-auto">
              <table class="min-w-full border-collapse text-sm">
                <thead>
                  <tr class="bg-slate-100">
                        <th class="border border-black px-2 py-1 text-center">#</th>
                        <th class="border border-black px-2 py-1 text-center">Tanggal</th>
                        <th class="border border-black px-2 py-1 text-center">Jam</th>
                        <th class="border border-black px-2 py-1 text-center">LbP</th>
                        <th class="border border-black px-2 py-1 text-center">WbP</th>
                        <th class="border border-black px-2 py-1 text-center">Total</th>
                        <th class="border border-black px-2 py-1 text-center">Kvarh</th>
                      </tr>
                </thead>
                <tbody>
                  <tr v-for="(r, idx) in records.data" :key="r.id">
                    <td class="border border-black px-2 py-1 text-center">{{ (records.current_page - 1) * records.per_page + idx + 1 }}</td>
                    <td class="border border-black px-2 py-1 text-center">{{ formatDate(r.tanggal) || '-' }}</td>
                    <td class="border border-black px-2 py-1 text-center">{{ r.jam || '-' }}</td>
                    <td class="border border-black px-2 py-1 text-right">{{ formatNumber(r.lbp) }}</td>
                    <td class="border border-black px-2 py-1 text-right">{{ formatNumber(r.wbp) }}</td>
                    <td class="border border-black px-2 py-1 text-right">{{ formatNumber(r.total) }}</td>
                    <td class="border border-black px-2 py-1 text-right">{{ formatNumber(r.kvarh) }}</td>
                  </tr>
                  <tr v-if="records.data.length === 0">
                    <td colspan="7" class="border border-black px-2 py-4 text-center text-slate-400">Tidak ada data</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-4">
              <Pagination :paginator="records" :onPageChange="goToPage" />
            </div>
          </div>
        </div>
      </div>

    <!-- Modal Tambah -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
      @click.self="closeModal"
    >
      <div class="w-full max-w-md overflow-hidden rounded-xl border border-slate-300 bg-white p-5 shadow-2xl">
        <div class="mb-4 flex items-center justify-between gap-4">
          <h3 class="text-base font-semibold text-black">Tambah Data Listrik</h3>
          <button
            type="button"
            class="rounded bg-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-800 hover:bg-slate-300"
            @click="closeModal"
          >
            Tutup
          </button>
        </div>

        <form @submit.prevent="saveRecord" class="space-y-4">
          <div class="col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Lokasi</label>
            <div>
              <select
                v-model="form.lokasi"
                required
                class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              >
                <option value="GMI">GMI</option>
                <option value="CRMI">CRMI</option>
                <option value="Office">Office</option>
              </select>
            </div>
            <p v-if="errors.lokasi" class="mt-1 text-xs text-red-600">{{ errors.lokasi }}</p>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div v-for="field in formFields" :key="field.key">
              <label class="mb-1 block text-sm font-medium text-slate-700">{{ field.label }}</label>
              <div>
                <input
                  v-model="form[field.key]"
                  type="number"
                  step="any"
                  required
                  class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                />
              </div>
              <p v-if="errors[field.key]" class="mt-1 text-xs text-red-600">{{ errors[field.key] }}</p>
            </div>
          </div>

          <p class="rounded bg-slate-100 px-3 py-2 text-xs text-slate-600">
            Tanggal dan Jam akan terisi otomatis (waktu sekarang) saat tombol Simpan ditekan.
          </p>

          <div class="flex items-center justify-end gap-2">
            <button
              type="button"
              class="rounded bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-300"
              @click="closeModal"
            >
              Batal
            </button>
            <button
              type="submit"
              class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
            >
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>
      </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({ records: Object, filters: Object });
const records = computed(() => props.records || { data: [] });
const errors = computed(() => usePage().props.errors || {});

function currentMonth() {
  const now = new Date();
  return `${now.getFullYear()}-${`${now.getMonth() + 1}`.padStart(2, '0')}`;
}
const bulan = ref(props.filters?.bulan || currentMonth());

const lokasiTabs = [
  { label: 'GMI', value: 'GMI' },
  { label: 'CRMI', value: 'CRMI' },
  { label: 'Office', value: 'Office' },
];
const activeLokasi = computed(() => props.filters?.lokasi || 'GMI');

function lokasiParam(value) {
  return value === 'all' ? '' : value;
}

function selectLokasi(value) {
  router.get(
    '/gmium/listrik',
    { lokasi: lokasiParam(value), bulan: bulan.value },
    { preserveState: true, preserveScroll: true },
  );
}

const showModal = ref(false);
const formFields = [
  { key: 'lbp', label: 'LbP' },
  { key: 'wbp', label: 'WbP' },
  { key: 'total', label: 'Total' },
  { key: 'kvarh', label: 'Kvarh' },
];
const form = ref({ lokasi: 'GMI', lbp: '', wbp: '', total: '', kvarh: '' });

function openModal() {
  form.value = { lokasi: 'GMI', lbp: '', wbp: '', total: '', kvarh: '' };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

function saveRecord() {
  router.post(
    '/gmium/listrik',
    {
      lokasi: form.value.lokasi,
      lbp: form.value.lbp,
      wbp: form.value.wbp,
      total: form.value.total,
      kvarh: form.value.kvarh === '' ? null : form.value.kvarh,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        showModal.value = false;
        form.value = { lokasi: 'GMI', lbp: '', wbp: '', total: '', kvarh: '' };
      },
    },
  );
}

function search() {
  router.get('/gmium/listrik', { bulan: bulan.value, lokasi: lokasiParam(activeLokasi.value) }, { preserveState: true, preserveScroll: true });
}

function resetSearch() {
  bulan.value = currentMonth();
  router.get('/gmium/listrik', { bulan: bulan.value, lokasi: lokasiParam(activeLokasi.value) }, { preserveState: true, preserveScroll: true });
}

function goToPage(page) {
  router.get('/gmium/listrik', { page: page, bulan: bulan.value, lokasi: lokasiParam(activeLokasi.value) }, { preserveState: true, preserveScroll: true });
}

function formatDate(d) {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('id-ID');
}

function formatNumber(v) {
  if (v === null || v === undefined || v === '') return '-';
  return Number(v).toLocaleString('id-ID', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
}
</script>
