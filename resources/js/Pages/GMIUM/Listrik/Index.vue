<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-bold">Listrik - Pencatatan</h2>
        <div class="flex flex-col gap-2 sm:flex-row">
          <Link href="/gmium/listrik/create" class="inline-flex items-center justify-center rounded bg-indigo-600 px-4 py-2 text-white">Tambah</Link>
        </div>
      </div>

      <div class="mb-4">
        <form @submit.prevent="search" class="flex flex-col gap-3 lg:flex-row">
          <input v-model="searchQuery" type="text" placeholder="Search by date or note..." class="w-full rounded border px-4 py-2 bg-white dark:bg-gray-800 lg:max-w-sm" />
          <button type="submit" class="w-full rounded bg-indigo-600 px-4 py-2 text-white lg:w-auto">Search</button>
          <button type="button" @click="resetSearch" class="w-full rounded bg-gray-500 px-4 py-2 text-white lg:w-auto">Reset</button>
        </form>
      </div>

      <div class="bg-slate-800 rounded p-4">
        <div class="hidden overflow-x-auto lg:block">
          <table class="w-full table-auto">
            <thead>
              <tr class="text-left text-slate-400">
                <th class="py-2">#</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>LbP</th>
                <th>WbP</th>
                <th>Total</th>
                <th>Kvarh</th>
                <th>T.T</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(r, idx) in records.data" :key="r.id" class="border-t border-slate-700">
                <td class="py-3">{{ (records.current_page - 1) * records.per_page + idx + 1 }}</td>
                <td>{{ formatDate(r.tanggal) || '-' }}</td>
                <td>{{ r.jam || '-' }}</td>
                <td class="text-right">{{ formatNumber(r.lbp) }}</td>
                <td class="text-right">{{ formatNumber(r.wbp) }}</td>
                <td class="text-right">{{ formatNumber(r.total) }}</td>
                <td class="text-right">{{ r.kvarh ?? '-' }}</td>
                <td>{{ r.tt || '-' }}</td>
                <td class="text-right">
                  <Link :href="`/gmium/listrik/${r.id}/show`" class="inline-flex items-center rounded bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700 mr-2">Show</Link>
                </td>
              </tr>
              <tr v-if="records.data.length === 0">
                <td colspan="9" class="py-4 text-center text-slate-400">No records found</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-700 lg:hidden">
          <div v-for="r in records.data" :key="`mobile-${r.id}`" class="border-b border-slate-700 bg-slate-900/30 p-4 last:border-b-0">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="truncate font-semibold text-white">{{ formatDate(r.tanggal) }} - {{ r.jam }}</div>
                <div class="text-sm text-slate-400">Total: {{ formatNumber(r.total) }}</div>
              </div>
            </div>
            <div class="mt-3 space-y-2 text-sm">
              <div class="flex items-start justify-between gap-3"><div class="text-slate-400">LbP</div><div class="text-right">{{ formatNumber(r.lbp) }}</div></div>
              <div class="flex items-start justify-between gap-3"><div class="text-slate-400">WbP</div><div class="text-right">{{ formatNumber(r.wbp) }}</div></div>
              <div class="flex items-start justify-between gap-3"><div class="text-slate-400">Kvarh</div><div class="text-right">{{ r.kvarh ?? '-' }}</div></div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
              <Link :href="`/gmium/listrik/${r.id}/show`" class="inline-flex items-center justify-center rounded bg-indigo-600 px-3 py-2 text-sm text-white">Show</Link>
            </div>
          </div>
          <div v-if="records.data.length === 0" class="py-4 text-center text-slate-400">No records found</div>
        </div>

        <div class="mt-4">
          <Pagination :paginator="records" :onPageChange="goToPage" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({ records: Object, filters: Object });
const records = computed(() => props.records || { data: [] });
const searchQuery = ref(props.filters?.search || '');

function search() {
  router.get('/gmium/listrik', { search: searchQuery.value }, { preserveState: true, preserveScroll: true });
}

function resetSearch() {
  searchQuery.value = '';
  router.get('/gmium/listrik', {}, { preserveState: true, preserveScroll: true });
}

function goToPage(page) {
  router.get('/gmium/listrik', { page: page, search: searchQuery.value }, { preserveState: true, preserveScroll: true });
}

function formatDate(d) {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('id-ID');
}

function formatNumber(v) {
  if (v === null || v === undefined) return '-';
  return Number(v).toLocaleString('id-ID');
}
</script>
