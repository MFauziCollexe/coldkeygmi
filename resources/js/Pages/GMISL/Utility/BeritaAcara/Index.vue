<template>
  <AppLayout>
    <div class="p-6 space-y-4">
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Berita Acara</h2>
          <p class="text-slate-400 text-sm">Buat, simpan, dan download PDF Berita Acara.</p>
        </div>
        <Link
          href="/gmisl/utility/berita-acara/create"
          class="inline-flex items-center justify-center px-4 py-2 rounded bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold"
        >
          Buat BA
        </Link>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
          <div class="md:col-span-6">
            <label class="text-xs text-slate-300">Cari (No Dokumen / No BA / Tempat)</label>
            <input
              v-model="form.search"
              type="text"
              class="mt-1 w-full rounded bg-slate-900 border border-slate-600 px-3 py-2 text-sm"
              placeholder="contoh: DOC-2026-03-0001 / BA-2026-03-0001 / Gudang"
              @keyup.enter="applyFilters"
            />
          </div>
          <div class="md:col-span-3 flex gap-2">
            <button class="w-full px-3 py-2 rounded bg-sky-600 hover:bg-sky-500 text-sm font-semibold" @click="applyFilters">
              Tampilkan
            </button>
            <button class="w-full px-3 py-2 rounded bg-slate-700 hover:bg-slate-600 text-sm font-semibold" @click="resetFilters">
              Reset
            </button>
          </div>
        </div>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
        <div v-if="!items.data?.length" class="text-sm text-slate-400">
          Belum ada Berita Acara.
        </div>

        <div v-else class="overflow-auto">
          <table class="w-full text-sm">
            <thead class="border-b border-slate-700 text-slate-400">
              <tr>
                <th class="text-left py-2 pr-3">No. Dokumen</th>
                <th class="text-left py-2 pr-3">No BA</th>
                <th class="text-left py-2 pr-3">Tanggal Dibuat</th>
                <th class="text-left py-2 pr-3">Tanggal Kejadian</th>
                <th class="text-left py-2 pr-3">Tempat</th>
                <th class="text-left py-2 pr-3">Customer</th>
                <th class="text-left py-2 pr-3">Divisi</th>
                <th class="text-right py-2">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in items.data" :key="row.id" class="border-b border-slate-700/50">
                <td class="py-2 pr-3 font-mono text-[12px] text-slate-200">{{ row.document_number || '-' }}</td>
                <td class="py-2 pr-3 font-mono text-[12px] text-slate-200">{{ row.number || '-' }}</td>
                <td class="py-2 pr-3">{{ formatDate(row.letter_date) }}</td>
                <td class="py-2 pr-3">{{ formatDate(row.event_date) }}</td>
                <td class="py-2 pr-3 text-slate-200">{{ row.event_location || '-' }}</td>
                <td class="py-2 pr-3 text-slate-200">{{ row.customer?.name || '-' }}</td>
                <td class="py-2 pr-3 text-slate-200">{{ row.department?.name || '-' }}</td>
                <td class="py-2 text-right">
                  <Link :href="`/gmisl/utility/berita-acara/${row.id}`" class="text-indigo-300 hover:text-indigo-200">Detail</Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="items.last_page > 1" class="pt-4 mt-4 border-t border-slate-700 flex items-center justify-end text-sm">
          <Pagination :paginator="items" :onPageChange="goToPage" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  items: { type: Object, required: true },
  filters: { type: Object, default: () => ({ search: '' }) },
});

const items = computed(() => props.items);

const form = reactive({
  search: String(props.filters?.search || ''),
});

function applyFilters() {
  router.get('/gmisl/utility/berita-acara', { search: form.search || undefined }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
  form.search = '';
  router.get('/gmisl/utility/berita-acara', {}, { preserveState: true, preserveScroll: true });
}

function goToPage(page) {
  router.get('/gmisl/utility/berita-acara', { search: form.search || undefined, page }, { preserveState: true, preserveScroll: true });
}

function formatDate(value) {
  if (!value) return '-';
  const date = new Date(String(value));
  if (Number.isNaN(date.getTime())) return '-';
  return date.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
}
</script>
