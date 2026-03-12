<template>
  <AppLayout>
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Check Inline</h2>
        <div class="flex items-center gap-2">
          <input
            v-model="filters.search"
            @input="onSearchInput"
            placeholder="Search..."
            class="w-56 px-3 py-2 rounded bg-slate-800 text-sm"
          />
          <Link href="/check-inline/create" class="bg-indigo-600 px-4 py-2 rounded text-white whitespace-nowrap">
            Create
          </Link>
        </div>
      </div>

      <div class="bg-slate-800 rounded p-4">
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
                    class="border border-slate-600 rounded overflow-hidden hover:border-indigo-400"
                    title="Preview image"
                  >
                    <img :src="row.image_urls[0]" alt="Image" class="w-12 h-12 object-cover" />
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

        <div class="mt-4">
          <Pagination :paginator="rows" :onPageChange="fetch" />
        </div>
      </div>

      <div
        v-if="showImageModal"
        class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4"
        @click="closeImage"
      >
        <div class="max-w-5xl max-h-[90vh]" @click.stop>
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
