<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <h2 class="text-2xl font-bold">Check Inline</h2>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
          <input
            v-model="filters.search"
            @input="onSearchInput"
            placeholder="Search..."
            class="w-full rounded bg-slate-800 px-3 py-2 text-sm sm:w-56"
          />
          <Link
            href="/check-inline/create"
            class="rounded bg-indigo-600 px-4 py-2 text-center text-white whitespace-nowrap"
          >
            Create
          </Link>
        </div>
      </div>

      <div class="rounded bg-slate-800 p-4">
        <div class="hidden lg:block">
          <table class="w-full table-fixed">
            <thead>
              <tr class="text-left text-slate-400 text-sm">
                <th class="py-2 w-12">ID</th>
                <th>Customer</th>
                <th>PO</th>
                <th class="w-20">Qty</th>
                <th>Exp</th>
                <th>Batch</th>
                <th class="w-28">Date</th>
                <th class="w-20">Image</th>
                <th class="w-20"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!rows.data || rows.data.length === 0" class="border-t border-slate-700">
                <td colspan="9" class="py-8 text-center text-slate-400">No data found.</td>
              </tr>
              <tr v-for="row in rows.data" :key="row.id" class="border-t border-slate-700 text-sm">
                <td class="py-3 pr-2">{{ row.id }}</td>
                <td class="pr-2 truncate">{{ row.customer || '-' }}</td>
                <td class="pr-2 truncate">{{ row.po || '-' }}</td>
                <td class="pr-2">{{ row.qty ?? '-' }}</td>
                <td class="pr-2">{{ formatDate(row.exp) }}</td>
                <td class="pr-2 truncate">{{ row.batch || '-' }}</td>
                <td class="pr-2">{{ formatDate(row.date) }}</td>
                <td>
                  <div v-if="row.image_urls && row.image_urls.length" class="flex">
                    <button
                      type="button"
                      @click="openImage(row.image_urls[0])"
                      class="overflow-hidden rounded border border-slate-600 hover:border-indigo-400"
                      title="Preview image"
                    >
                      <img :src="row.image_urls[0]" alt="Image" class="h-12 w-12 object-cover" />
                    </button>
                  </div>
                  <span v-else>-</span>
                </td>
                <td>
                  <Link :href="`/check-inline/${row.id}`" class="text-indigo-400">View</Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-700 lg:hidden">
          <div v-if="!rows.data || rows.data.length === 0" class="bg-slate-900/30 px-4 py-8 text-center text-slate-400">
            No data found.
          </div>
          <div
            v-for="row in rows.data"
            :key="row.id"
            class="border-b border-slate-700/60 bg-slate-900/30 p-4 last:border-b-0"
          >
            <div class="mb-3 flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="text-xs text-slate-400">Check Inline #{{ row.id }}</div>
                <div class="font-semibold text-white">{{ row.customer || '-' }}</div>
                <div class="text-sm text-slate-400">{{ row.po || 'Tanpa PO' }}</div>
              </div>
              <Link :href="`/check-inline/${row.id}`" class="shrink-0 text-sm text-indigo-400">View</Link>
            </div>

            <div class="space-y-2 text-sm">
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Qty</span>
                <span class="text-right">{{ row.qty ?? '-' }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Batch</span>
                <span class="max-w-[62%] text-right">{{ row.batch || '-' }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Exp</span>
                <span class="text-right">{{ formatDate(row.exp) }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Date</span>
                <span class="text-right">{{ formatDate(row.date) }}</span>
              </div>
            </div>

            <div class="mt-3">
              <div class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-400">Image</div>
              <button
                v-if="row.image_urls && row.image_urls.length"
                type="button"
                @click="openImage(row.image_urls[0])"
                class="overflow-hidden rounded border border-slate-600 hover:border-indigo-400"
                title="Preview image"
              >
                <img :src="row.image_urls[0]" alt="Image" class="h-16 w-16 object-cover" />
              </button>
              <div v-else class="text-sm text-slate-400">-</div>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <Pagination :paginator="rows" :onPageChange="fetch" />
        </div>
      </div>

      <div
        v-if="showImageModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
        @click="closeImage"
      >
        <div class="max-h-[90vh] max-w-5xl" @click.stop>
          <img :src="selectedImage" alt="Preview" class="max-h-[90vh] w-auto rounded" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  rows: Object,
  filters: Object,
});

const rows = computed(() => props.rows);
const filters = reactive({
  search: props.filters?.search || '',
});
const showImageModal = ref(false);
const selectedImage = ref('');

let searchTimer = null;
function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetch(), 300);
}

function fetch(page = 1) {
  const params = {};
  if (filters.search) params.search = filters.search;
  if (page > 1) params.page = page;
  router.get('/check-inline', params, { preserveState: true, preserveScroll: true });
}

function next() {
  if (rows.value.next_page_url) fetch(rows.value.current_page + 1);
}

function prev() {
  if (rows.value.prev_page_url) fetch(rows.value.current_page - 1);
}

function formatDate(value) {
  if (!value) return '-';
  return new Date(value).toLocaleDateString('id-ID');
}

function formatDateTime(value) {
  if (!value) return '-';
  return new Date(value).toLocaleString('id-ID');
}

function openImage(url) {
  selectedImage.value = url;
  showImageModal.value = true;
}

function closeImage() {
  showImageModal.value = false;
  selectedImage.value = '';
}
</script>
