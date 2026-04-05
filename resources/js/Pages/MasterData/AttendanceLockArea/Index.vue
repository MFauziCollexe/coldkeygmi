<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-bold">Area Absensi</h2>
        <Link href="/master-data/attendance-lock-area/create" class="rounded bg-indigo-600 px-4 py-2 text-white">Add Area</Link>
      </div>

      <div class="rounded bg-slate-800 p-4">
        <div class="mb-4">
          <input
            v-model="search"
            type="text"
            placeholder="Cari area..."
            class="w-full rounded bg-slate-700 px-3 py-2 text-white sm:max-w-sm"
            @keyup.enter="applyFilters"
          />
        </div>

        <div class="hidden overflow-auto lg:block">
          <table class="w-full table-auto">
            <thead>
              <tr class="text-left text-slate-400">
                <th class="py-2">Nama</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Radius</th>
                <th>Status</th>
                <th>Description</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!areas.data?.length" class="border-t border-slate-700">
                <td colspan="7" class="py-8 text-center text-slate-400">Belum ada area absensi.</td>
              </tr>
              <tr v-for="area in areas.data" :key="area.id" class="border-t border-slate-700">
                <td class="py-3">{{ area.name }}</td>
                <td>{{ area.latitude }}</td>
                <td>{{ area.longitude }}</td>
                <td>{{ area.radius_meters }} m</td>
                <td>
                  <span :class="area.is_active ? 'text-green-400' : 'text-red-400'">
                    {{ area.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td>{{ area.description || '-' }}</td>
                <td class="text-right">
                  <Link :href="`/master-data/attendance-lock-area/${area.id}/edit`" class="mr-3 text-indigo-400">Edit</Link>
                  <button @click="deleteArea(area.id)" class="text-red-400">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-700 lg:hidden">
          <div v-if="!areas.data?.length" class="bg-slate-900/30 px-4 py-8 text-center text-slate-400">
            Belum ada area absensi.
          </div>
          <div v-for="area in areas.data" :key="`mobile-${area.id}`" class="border-b border-slate-700 bg-slate-900/30 p-4 last:border-b-0">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="truncate font-semibold text-white">{{ area.name }}</div>
                <div class="text-sm text-slate-400">{{ area.radius_meters }} m</div>
              </div>
              <span :class="area.is_active ? 'text-green-400' : 'text-red-400'">
                {{ area.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <div class="mt-3 space-y-2 text-sm">
              <div class="flex items-start justify-between gap-3">
                <div class="text-slate-400">Latitude</div>
                <div class="text-right">{{ area.latitude }}</div>
              </div>
              <div class="flex items-start justify-between gap-3">
                <div class="text-slate-400">Longitude</div>
                <div class="text-right">{{ area.longitude }}</div>
              </div>
              <div class="flex items-start justify-between gap-3">
                <div class="text-slate-400">Description</div>
                <div class="max-w-[62%] text-right">{{ area.description || '-' }}</div>
              </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
              <Link :href="`/master-data/attendance-lock-area/${area.id}/edit`" class="inline-flex items-center justify-center rounded bg-indigo-600 px-3 py-2 text-sm text-white">Edit</Link>
              <button @click="deleteArea(area.id)" class="rounded bg-rose-600 px-3 py-2 text-sm text-white">Delete</button>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <Pagination :paginator="areas" :onPageChange="goToPage" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { swalConfirm } from '@/Utils/swalConfirm';

const props = defineProps({
  areas: Object,
  filters: Object,
});

const areas = computed(() => props.areas);
const search = ref(props.filters?.search || '');

function applyFilters(page = 1) {
  router.get('/master-data/attendance-lock-area', { search: search.value || undefined, page }, { preserveState: true, preserveScroll: true });
}

function goToPage(page) {
  applyFilters(page);
}

async function deleteArea(id) {
  const ok = await swalConfirm({
    title: 'Delete Area',
    text: 'Are you sure you want to delete this attendance area?',
    confirmButtonText: 'Delete',
    confirmButtonColor: '#dc2626',
  });
  if (!ok) return;

  router.delete(`/master-data/attendance-lock-area/${id}`);
}
</script>
