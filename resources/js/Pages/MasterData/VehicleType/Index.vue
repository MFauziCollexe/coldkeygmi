<template>
  <AppLayout>
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Jenis Kendaraan</h2>
        <div class="flex items-center gap-2">
          <input v-model="filters.search" @input="onSearchInput" placeholder="Cari jenis kendaraan..." class="px-3 py-2 rounded bg-slate-800 text-sm" />
          <select v-model="filters.is_active" @change="fetch" class="px-3 py-2 rounded bg-slate-800 text-sm">
            <option value="">Semua Status</option>
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
          <Link href="/master-data/vehicle-type/create" class="bg-indigo-600 px-4 py-2 rounded text-white">Tambah Jenis</Link>
        </div>
      </div>

      <div class="bg-slate-800 rounded p-4 overflow-auto">
        <table class="w-full table-auto">
          <thead>
            <tr class="text-left text-slate-400">
              <th class="py-2">Nama</th>
              <th>Status</th>
              <th class="text-right"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!vehicleTypes.data || vehicleTypes.data.length === 0" class="border-t border-slate-700">
              <td colspan="3" class="py-8 text-center text-slate-400">Tidak ada data.</td>
            </tr>
            <tr v-for="item in vehicleTypes.data" :key="item.id" class="border-t border-slate-700 text-sm">
              <td class="py-3">{{ item.name }}</td>
              <td>
                <span :class="item.is_active ? 'text-green-400' : 'text-red-400'">
                  {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td class="text-right whitespace-nowrap">
                <Link :href="`/master-data/vehicle-type/${item.id}/edit`" class="text-indigo-400 mr-2">Edit</Link>
                <button type="button" @click="destroy(item.id)" class="text-red-400">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="mt-4">
          <button @click="prev" :disabled="!vehicleTypes.prev_page_url" class="px-3 py-1 bg-slate-700 rounded mr-2">Prev</button>
          <button @click="next" :disabled="!vehicleTypes.next_page_url" class="px-3 py-1 bg-slate-700 rounded">Next</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  vehicleTypes: Object,
  filters: Object,
});

const vehicleTypes = reactive(props.vehicleTypes);
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
  Inertia.get('/master-data/vehicle-type', params, { preserveState: true, preserveScroll: true });
}

function next() {
  if (vehicleTypes.next_page_url) fetch(vehicleTypes.current_page + 1);
}

function prev() {
  if (vehicleTypes.prev_page_url) fetch(vehicleTypes.current_page - 1);
}

function destroy(id) {
  if (!confirm('Hapus jenis kendaraan ini?')) return;
  Inertia.delete(`/master-data/vehicle-type/${id}`);
}
</script>

