<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div>
        <h2 class="text-2xl font-bold">The Days</h2>
        <p class="text-slate-400 text-sm">Kelola daftar hari libur nasional untuk Attendance Log.</p>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
        <form class="grid grid-cols-1 md:grid-cols-12 gap-3" @submit.prevent="saveHoliday">
          <div class="md:col-span-2">
            <div class="relative group">
              <EnhancedDatePicker
                v-model="form.holiday_date"
                placeholder=" "
                input-class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
              />
              <label
                :class="[
                  'pointer-events-none absolute left-3 z-10 transition-all',
                  (form.holiday_date
                    ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                    : 'px-0 text-sm text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                  'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
                ]"
              >
                Tanggal Libur
              </label>
            </div>
            <p v-if="errors.holiday_date" class="text-rose-300 text-xs mt-1">{{ errors.holiday_date }}</p>
          </div>
          <div class="md:col-span-2">
            <div class="relative">
              <input
                v-model="form.name"
                type="text"
                placeholder="Nama Hari Libur"
                class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 text-sm placeholder-transparent"
              />
              <label
                class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-sm peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
              >
                Nama Hari Libur
              </label>
            </div>
            <p v-if="errors.name" class="text-rose-300 text-xs mt-1">{{ errors.name }}</p>
          </div>
          <div class="md:col-span-2">
            <div class="relative group">
              <select
                v-model="form.scope_type"
                class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 text-sm appearance-none"
              >
                <option value="all">Semua</option>
                <option value="office">Office</option>
                <option value="operational">Operational</option>
              </select>
              <label
                :class="[
                  'pointer-events-none absolute left-3 z-10 transition-all',
                  (form.scope_type
                    ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                    : 'px-0 text-sm text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                  'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
                ]"
              >
                Berlaku Untuk
              </label>
            </div>
          </div>
          <div class="md:col-span-4">
            <div class="relative">
              <input
                v-model="form.notes"
                type="text"
                placeholder="Catatan"
                class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 text-sm placeholder-transparent"
              />
              <label
                class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
              >
                Catatan
              </label>
            </div>
          </div>
          <div class="md:col-span-2 flex items-end">
            <button class="w-full h-[52px] px-3 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-sm font-semibold">
              Simpan
            </button>
          </div>
        </form>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
          <h3 class="font-semibold">Daftar Hari Libur Nasional</h3>
          <input
            v-model="search"
            type="text"
            placeholder="Cari tanggal / nama libur"
            class="w-full md:w-80 rounded bg-slate-900 border border-slate-600 px-3 py-2 text-sm"
            @input="onSearch"
          />
        </div>

        <div v-if="!holidays.data?.length" class="text-sm text-slate-400">
          Belum ada hari libur nasional.
        </div>

        <div v-else class="overflow-auto">
          <table class="w-full text-sm">
            <thead class="border-b border-slate-700 text-slate-400">
              <tr>
                <th class="text-left py-2 pr-3">Tanggal</th>
                <th class="text-left py-2 pr-3">Hari Libur</th>
                <th class="text-left py-2 pr-3">Berlaku Untuk</th>
                <th class="text-left py-2 pr-3">Catatan</th>
                <th class="text-left py-2">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in holidays.data" :key="row.id" class="border-b border-slate-700/50">
                <td class="py-2 pr-3">{{ formatDate(row.holiday_date) }}</td>
                <td class="py-2 pr-3">{{ row.name }}</td>
                <td class="py-2 pr-3">{{ scopeLabel(row.scope_type) }}</td>
                <td class="py-2 pr-3">{{ row.notes || '-' }}</td>
                <td class="py-2">
                  <button class="px-2 py-1 rounded bg-rose-600 hover:bg-rose-500 text-xs font-semibold" @click="removeHoliday(row.id)">
                    Hapus
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="holidays.last_page > 1" class="pt-4 mt-4 border-t border-slate-700 flex items-center justify-end text-sm">
          <Pagination :paginator="holidays" :onPageChange="goToPage" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  holidays: {
    type: Object,
    default: () => ({
      data: [],
      current_page: 1,
      last_page: 1,
    }),
  },
  filters: {
    type: Object,
    default: () => ({ q: '' }),
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const form = reactive({
  holiday_date: '',
  name: '',
  scope_type: 'all',
  notes: '',
});

const search = ref(String(props.filters?.q || ''));
let searchTimer = null;

function saveHoliday() {
  router.post('/the-days', form, {
    preserveScroll: true,
    onSuccess: () => {
      form.holiday_date = '';
      form.name = '';
      form.scope_type = 'all';
      form.notes = '';
      Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Hari libur tersimpan.', timer: 1400, showConfirmButton: false });
    },
  });
}

function removeHoliday(id) {
  Swal.fire({
    title: 'Hapus hari libur?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Hapus',
    cancelButtonText: 'Batal',
  }).then((result) => {
    if (!result.isConfirmed) return;
    router.delete(`/the-days/${id}`, {
      preserveScroll: true,
      onSuccess: () => Swal.fire({ icon: 'success', title: 'Terhapus', timer: 1200, showConfirmButton: false }),
    });
  });
}

function onSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    router.get('/the-days', { q: search.value || '', page: 1 }, { preserveState: true, replace: true, preserveScroll: true });
  }, 300);
}

function goToPage(page) {
  router.get('/the-days', { q: search.value || '', page }, { preserveState: true, replace: true, preserveScroll: true });
}

function formatDate(value) {
  if (!value) return '-';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString('id-ID');
}

function scopeLabel(value) {
  const normalized = String(value || '').toLowerCase();
  if (normalized === 'office') return 'Office';
  if (normalized === 'operational') return 'Operational';
  return 'Semua';
}
</script>
