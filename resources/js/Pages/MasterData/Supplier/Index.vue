<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <h2 class="text-2xl font-bold">Supplier</h2>
        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
          <input v-model="filters.search" @input="onSearchInput" placeholder="Search supplier..." class="rounded bg-slate-800 px-3 py-2 text-sm" />
          <Link href="/master-data/supplier/create" class="rounded bg-indigo-600 px-4 py-2 text-white">New Supplier</Link>
        </div>
      </div>

      <div class="rounded bg-slate-800 p-4">
        <div class="hidden overflow-auto lg:block">
          <table class="w-full table-auto">
            <thead>
              <tr class="text-left text-slate-400">
                <th class="py-2">Name</th>
                <th>Code</th>
                <th>Contact</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!suppliers.data || suppliers.data.length === 0" class="border-t border-slate-700">
                <td colspan="7" class="py-8 text-center text-slate-400">No suppliers found.</td>
              </tr>
              <tr v-for="item in suppliers.data" :key="item.id" class="border-t border-slate-700 text-sm">
                <td class="py-3">{{ item.name }}</td>
                <td>{{ item.code || '-' }}</td>
                <td>{{ item.contact_person || '-' }}</td>
                <td>{{ item.phone || '-' }}</td>
                <td>{{ item.email || '-' }}</td>
                <td>
                  <span class="rounded px-2 py-1 text-xs" :class="item.is_active ? 'bg-green-600 text-white' : 'bg-slate-600 text-white'">
                    {{ item.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="text-right whitespace-nowrap">
                  <Link :href="`/master-data/supplier/${item.id}/edit`" class="mr-2 text-indigo-400">Edit</Link>
                  <button type="button" @click="destroy(item.id)" class="text-red-400">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-700 lg:hidden">
          <div v-if="!suppliers.data || suppliers.data.length === 0" class="py-8 text-center text-slate-400">No suppliers found.</div>
          <div v-for="item in suppliers.data" :key="`mobile-${item.id}`" class="border-b border-slate-700 bg-slate-900/30 p-4 last:border-b-0">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="truncate font-semibold text-white">{{ item.name }}</div>
                <div class="text-sm text-slate-400">{{ item.code || '-' }}</div>
              </div>
              <span class="rounded px-2 py-1 text-xs" :class="item.is_active ? 'bg-green-600 text-white' : 'bg-slate-600 text-white'">
                {{ item.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <div class="mt-3 space-y-2 text-sm">
              <div class="flex items-start justify-between gap-3">
                <div class="text-slate-400">Contact</div>
                <div class="text-right">{{ item.contact_person || '-' }}</div>
              </div>
              <div class="flex items-start justify-between gap-3">
                <div class="text-slate-400">Phone</div>
                <div class="text-right">{{ item.phone || '-' }}</div>
              </div>
              <div class="flex items-start justify-between gap-3">
                <div class="text-slate-400">Email</div>
                <div class="break-all text-right">{{ item.email || '-' }}</div>
              </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
              <Link :href="`/master-data/supplier/${item.id}/edit`" class="inline-flex items-center justify-center rounded bg-indigo-600 px-3 py-2 text-sm text-white">Edit</Link>
              <button type="button" @click="destroy(item.id)" class="rounded bg-rose-600 px-3 py-2 text-sm text-white">Delete</button>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <Pagination :paginator="suppliers" :onPageChange="fetch" />
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
  suppliers: Object,
  filters: Object,
});

const suppliers = computed(() => props.suppliers);
const filters = reactive({
  search: props.filters?.search || '',
});

let searchTimer = null;

function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetch(), 300);
}

function fetch(page = 1) {
  const params = {};
  if (filters.search) params.search = filters.search;
  if (page > 1) params.page = page;
  router.get('/master-data/supplier', params, { preserveState: true, preserveScroll: true });
}

async function destroy(id) {
  const ok = await swalConfirm({
    title: 'Delete Supplier',
    text: 'Delete supplier ini?',
    confirmButtonText: 'Delete',
    confirmButtonColor: '#dc2626',
  });

  if (!ok) return;

  router.delete(`/master-data/supplier/${id}`);
}
</script>
