<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <h2 class="text-2xl font-bold">Master Satuan Stock Card</h2>
        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
          <input v-model="filters.search" @input="onSearchInput" placeholder="Cari satuan..." class="rounded bg-slate-800 px-3 py-2 text-sm" />
          <select v-model="filters.is_active" @change="fetch" class="rounded bg-slate-800 px-3 py-2 text-sm">
            <option value="">Semua Status</option>
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
          <Link href="/master-data/stock-card-unit/create" class="rounded bg-indigo-600 px-4 py-2 text-white">Tambah Satuan</Link>
        </div>
      </div>

      <div class="rounded bg-slate-800 p-4">
        <div class="hidden overflow-auto lg:block">
        <table class="w-full table-auto">
          <thead>
            <tr class="text-left text-slate-400">
              <th class="py-2">Nama</th>
              <th>Status</th>
              <th class="text-right"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!units.data?.length" class="border-t border-slate-700">
              <td colspan="3" class="py-8 text-center text-slate-400">Tidak ada data.</td>
            </tr>
            <tr v-for="item in units.data" :key="item.id" class="border-t border-slate-700 text-sm">
              <td class="py-3">{{ item.name }}</td>
              <td>
                <span :class="item.is_active ? 'text-green-400' : 'text-red-400'">
                  {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td class="text-right whitespace-nowrap">
                <Link :href="`/master-data/stock-card-unit/${item.id}/edit`" class="mr-2 text-indigo-400">Edit</Link>
                <button type="button" class="text-red-400" @click="destroy(item.id)">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-700 lg:hidden">
          <div v-if="!units.data?.length" class="py-8 text-center text-slate-400">Tidak ada data.</div>
          <div v-for="item in units.data" :key="`mobile-${item.id}`" class="border-b border-slate-700 bg-slate-900/30 p-4 last:border-b-0">
            <div class="flex items-start justify-between gap-3">
              <div class="font-semibold text-white">{{ item.name }}</div>
              <span :class="item.is_active ? 'text-green-400' : 'text-red-400'">
                {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
              <Link :href="`/master-data/stock-card-unit/${item.id}/edit`" class="inline-flex items-center justify-center rounded bg-indigo-600 px-3 py-2 text-sm text-white">Edit</Link>
              <button type="button" class="rounded bg-rose-600 px-3 py-2 text-sm text-white" @click="destroy(item.id)">Delete</button>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <Pagination :paginator="units" :onPageChange="fetch" />
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
import { swalConfirm } from '@/Utils/swalConfirm';

const props = defineProps({
  units: Object,
  filters: Object,
});

const units = computed(() => props.units);
const filters = reactive({
  search: props.filters?.search || '',
  is_active: props.filters?.is_active || '',
});

let searchTimer = null;
function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetch(), 300);
}

function fetch(page = 1) {
  const params = {};
  if (filters.search) params.search = filters.search;
  if (filters.is_active !== '') params.is_active = filters.is_active;
  if (page > 1) params.page = page;
  router.get('/master-data/stock-card-unit', params, { preserveState: true, preserveScroll: true });
}

async function destroy(id) {
  const ok = await swalConfirm({
    title: 'Hapus Data',
    text: 'Hapus satuan ini?',
    confirmButtonText: 'Hapus',
    confirmButtonColor: '#dc2626',
  });
  if (!ok) return;

  router.delete(`/master-data/stock-card-unit/${id}`);
}
</script>
