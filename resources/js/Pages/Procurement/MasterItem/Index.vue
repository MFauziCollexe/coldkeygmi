<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Procurement Master Item</h2>
          <p class="text-sm text-slate-400">Master barang untuk dipakai pada Purchase Requisition.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
          <input v-model="filters.search" @input="onSearchInput" placeholder="Cari kode, nama, deskripsi..." class="rounded bg-slate-800 px-3 py-2 text-sm" />
          <select v-model="filters.is_active" @change="fetch" class="rounded bg-slate-800 px-3 py-2 text-sm">
            <option value="">Semua Status</option>
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
          <Link href="/master-data/master-item/create" class="rounded bg-indigo-600 px-4 py-2 text-white">Tambah Master Item</Link>
        </div>
      </div>

      <div class="rounded bg-slate-800 p-4">
        <div class="hidden overflow-auto lg:block">
          <table class="w-full table-auto">
            <thead>
              <tr class="text-left text-slate-400">
                <th class="py-2">Kode</th>
                <th>Nama</th>
                <th>Description of Goods</th>
                <th>Type Item</th>
                <th>Unit</th>
                <th>Status</th>
                <th class="text-right"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!items.data?.length" class="border-t border-slate-700">
                <td colspan="7" class="py-8 text-center text-slate-400">Tidak ada data.</td>
              </tr>
              <tr v-for="item in items.data" :key="item.id" class="border-t border-slate-700 text-sm">
                <td class="py-3 font-semibold text-white">{{ item.item_code }}</td>
                <td>{{ item.item_name }}</td>
                <td class="max-w-md">{{ item.description_of_goods }}</td>
                <td>{{ item.item_type || '-' }}</td>
                <td>{{ item.unit }}</td>
                <td>
                  <span :class="item.is_active ? 'text-green-400' : 'text-red-400'">
                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td class="text-right whitespace-nowrap">
                  <Link :href="`/master-data/master-item/${item.id}/edit`" class="mr-2 text-indigo-400">Edit</Link>
                  <button type="button" class="text-red-400" @click="destroy(item.id)">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-700 lg:hidden">
          <div v-if="!items.data?.length" class="py-8 text-center text-slate-400">Tidak ada data.</div>
          <div v-for="item in items.data" :key="`mobile-${item.id}`" class="border-b border-slate-700 bg-slate-900/30 p-4 last:border-b-0">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-semibold text-white">{{ item.item_code }}</div>
                <div class="text-sm text-slate-300">{{ item.item_name }}</div>
              </div>
              <span :class="item.is_active ? 'text-green-400' : 'text-red-400'">
                {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </div>
            <div class="mt-3 space-y-1 text-sm text-slate-300">
              <div>{{ item.description_of_goods }}</div>
              <div>Type Item: {{ item.item_type || '-' }}</div>
              <div>Unit: {{ item.unit }}</div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
              <Link :href="`/master-data/master-item/${item.id}/edit`" class="inline-flex items-center justify-center rounded bg-indigo-600 px-3 py-2 text-sm text-white">Edit</Link>
              <button type="button" class="rounded bg-rose-600 px-3 py-2 text-sm text-white" @click="destroy(item.id)">Delete</button>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <Pagination :paginator="items" :onPageChange="fetch" />
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
  items: Object,
  filters: Object,
});

const items = computed(() => props.items);
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
  router.get('/master-data/master-item', params, { preserveState: true, preserveScroll: true });
}

async function destroy(id) {
  const ok = await swalConfirm({
    title: 'Hapus Data',
    text: 'Hapus master item ini?',
    confirmButtonText: 'Hapus',
    confirmButtonColor: '#dc2626',
  });
  if (!ok) return;

  router.delete(`/master-data/master-item/${id}`);
}
</script>
