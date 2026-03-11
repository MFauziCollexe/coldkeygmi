<template>
  <AppLayout>
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Customer</h2>
        <div class="flex items-center gap-2">
          <input v-model="filters.search" @input="onSearchInput" placeholder="Search customer..." class="px-3 py-2 rounded bg-slate-800 text-sm" />
          <select v-model="filters.customer_type" @change="fetch" class="px-3 py-2 rounded bg-slate-800 text-sm">
            <option value="">All Type</option>
            <option value="company">Company</option>
            <option value="individual">Individual</option>
          </select>
          <Link href="/master-data/customer/create" class="bg-indigo-600 px-4 py-2 rounded text-white">New Customer</Link>
        </div>
      </div>

      <div class="bg-slate-800 rounded p-4 overflow-auto">
        <table class="w-full table-auto">
          <thead>
            <tr class="text-left text-slate-400">
              <th class="py-2">Logo</th>
              <th>Name</th>
              <th>Type</th>
              <th>Phone</th>
              <th>Email</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!customers.data || customers.data.length === 0" class="border-t border-slate-700">
              <td colspan="6" class="py-8 text-center text-slate-400">No customers found.</td>
            </tr>
            <tr v-for="item in customers.data" :key="item.id" class="border-t border-slate-700 text-sm">
              <td class="py-3">
                <img v-if="item.logo_image_url" :src="item.logo_image_url" class="w-10 h-10 object-cover rounded border border-slate-600" />
                <span v-else>-</span>
              </td>
              <td>{{ item.name }}</td>
              <td class="capitalize">{{ item.customer_type }}</td>
              <td>{{ item.phone || '-' }}</td>
              <td>{{ item.email || '-' }}</td>
              <td class="text-right whitespace-nowrap">
                <Link :href="`/master-data/customer/${item.id}/edit`" class="text-indigo-400 mr-2">Edit</Link>
                <button type="button" @click="destroy(item.id)" class="text-red-400">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="mt-4">
          <button @click="prev" :disabled="!customers.prev_page_url" class="px-3 py-1 bg-slate-700 rounded mr-2">Prev</button>
          <button @click="next" :disabled="!customers.next_page_url" class="px-3 py-1 bg-slate-700 rounded">Next</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  customers: Object,
  filters: Object,
});

const customers = reactive(props.customers);
const filters = reactive({
  search: props.filters?.search || '',
  customer_type: props.filters?.customer_type || '',
});

let searchTimer = null;
function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetch(), 300);
}

function fetch(page = 1) {
  const params = {};
  if (filters.search) params.search = filters.search;
  if (filters.customer_type) params.customer_type = filters.customer_type;
  if (page > 1) params.page = page;
  router.get('/master-data/customer', params, { preserveState: true, preserveScroll: true });
}

function next() {
  if (customers.next_page_url) fetch(customers.current_page + 1);
}

function prev() {
  if (customers.prev_page_url) fetch(customers.current_page - 1);
}

function destroy(id) {
  if (!confirm('Delete customer ini?')) return;
  router.delete(`/master-data/customer/${id}`);
}
</script>
