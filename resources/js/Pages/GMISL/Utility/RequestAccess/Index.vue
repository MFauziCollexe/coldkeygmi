<template>
  <AppLayout>
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Request Access</h2>
        <div class="flex items-center gap-2">
          <input v-model="filters.search" @input="onSearchInput" placeholder="Search requests..." class="px-3 py-2 rounded bg-slate-800 text-sm" />
          <select v-model="filters.status" @change="fetch" class="px-3 py-2 rounded bg-slate-800 text-sm">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="processed">Processed</option>
          </select>
          <select v-model="filters.type" @change="fetch" class="px-3 py-2 rounded bg-slate-800 text-sm">
            <option value="">All Types</option>
            <option value="existing_user">Existing User</option>
            <option value="new_user">New User</option>
          </select>
          <Link href="/request-access/create" class="bg-indigo-600 px-4 py-2 rounded text-white">New Request</Link>
        </div>
      </div>

      <div class="bg-slate-800 rounded p-4">
        <table class="w-full table-auto">
          <thead>
            <tr class="text-left text-slate-400">
              <th class="py-2">#</th>
              <th>Type</th>
              <th>User / Target</th>
              <th>Modules</th>
              <th>Status</th>
              <th>Created By</th>
              <th>Created</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="req in requests.data" :key="req.id" class="border-t border-slate-700">
              <td class="py-3">{{ req.request_number }}</td>
              <td>
                <span :class="getTypeClass(req.type)">
                  {{ req.type === 'existing_user' ? 'Existing User' : 'New User' }}
                </span>
              </td>
              <td>
                <div v-if="req.type === 'existing_user' && req.user">
                  {{ req.user.name }}
                </div>
                <div v-else-if="req.type === 'new_user'">
                  {{ req.target_user_name }} ({{ req.target_user_email }})
                </div>
                <div v-else>-</div>
              </td>
              <td>
                <div class="flex flex-wrap gap-1">
                  <span v-for="mod in formatModules(req.module_keys)" :key="mod" class="bg-slate-700 px-2 py-1 rounded text-xs">
                    {{ mod }}
                  </span>
                </div>
              </td>
              <td>
                <span :class="getStatusClass(req.status)">
                  {{ req.status }}
                </span>
              </td>
              <td>{{ req.creator ? req.creator.name : '-' }}</td>
              <td>{{ new Date(req.created_at).toLocaleString() }}</td>
              <td class="text-right">
                <Link :href="`/request-access/${req.id}`" class="text-indigo-400">View</Link>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="!requests.data || requests.data.length === 0" class="text-center py-8 text-slate-400">
          No requests found.
        </div>

        <div class="mt-4">
          <Pagination :paginator="requests" :onPageChange="goToPage" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({ requests: Object, filters: Object });

const requests = computed(() => props.requests);

const filters = reactive({
  search: props.filters.search || '',
  status: props.filters.status || '',
  type: props.filters.type || '',
});

let searchTimer = null;
function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetch(), 350);
}

function fetch() {
  const params = {};
  if (filters.search) params.search = filters.search;
  if (filters.status) params.status = filters.status;
  if (filters.type) params.type = filters.type;
  router.get('/request-access', params, { preserveState: true, preserveScroll: true });
}

function goToPage(pageNum) {
  const params = {};
  if (filters.search) params.search = filters.search;
  if (filters.status) params.status = filters.status;
  if (filters.type) params.type = filters.type;
  router.get('/request-access', { ...params, page: pageNum }, { preserveState: true, preserveScroll: true });
}

function next() {
  if (requests.value.next_page_url) goToPage(requests.value.current_page + 1);
}

function prev() {
  if (requests.value.prev_page_url) goToPage(requests.value.current_page - 1);
}

function getTypeClass(type) {
  const colors = {
    'existing_user': 'bg-blue-600 text-white px-2 py-1 rounded text-xs',
    'new_user': 'bg-purple-600 text-white px-2 py-1 rounded text-xs',
  };
  return colors[type] || 'bg-slate-600 text-white px-2 py-1 rounded text-xs';
}

function getStatusClass(status) {
  const colors = {
    'pending': 'bg-yellow-600 text-white px-2 py-1 rounded text-xs',
    'approved': 'bg-blue-600 text-white px-2 py-1 rounded text-xs',
    'rejected': 'bg-red-600 text-white px-2 py-1 rounded text-xs',
    'processed': 'bg-green-600 text-white px-2 py-1 rounded text-xs',
  };
  return colors[status] || 'bg-slate-600 text-white px-2 py-1 rounded text-xs';
}

function formatModules(moduleKeys) {
  if (!moduleKeys || !Array.isArray(moduleKeys)) return ['-'];
  // Convert underscores to dots and capitalize words
  return moduleKeys.map(key => key.replace(/_/g, '.'));
}
</script>
