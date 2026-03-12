<template>
  <AppLayout>
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Activity Logs</h2>
        <div class="flex items-center gap-2">
          <input v-model="filters.search" @input="onSearchInput" placeholder="Search logs..." class="px-3 py-2 rounded bg-slate-800 text-sm" />
          <div class="w-44">
            <SearchableSelect
              v-model="filters.table_name"
              :options="tableNames"
              placeholder="All Tables"
              empty-label="All Tables"
              input-class="bg-slate-800 text-sm"
              @update:modelValue="fetch"
            />
          </div>
          <div class="w-44">
            <SearchableSelect
              v-model="filters.action"
              :options="actions"
              placeholder="All Actions"
              empty-label="All Actions"
              input-class="bg-slate-800 text-sm"
              @update:modelValue="fetch"
            />
          </div>
          <div class="w-52">
            <SearchableSelect
              v-model="filters.user_id"
              :options="users"
              option-value="user_id"
              option-label="user_email"
              placeholder="All Users"
              empty-label="All Users"
              input-class="bg-slate-800 text-sm"
              @update:modelValue="fetch"
            />
          </div>
        </div>
      </div>

      <div class="bg-slate-800 rounded p-4">
        <table class="w-full table-auto">
          <thead>
            <tr class="text-left text-slate-400">
              <th class="py-2">#</th>
              <th>Table</th>
              <th>Action</th>
              <th>User</th>
              <th>Description</th>
              <th>IP Address</th>
              <th>Created</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs.data" :key="log.id" class="border-t border-slate-700">
              <td class="py-3">{{ log.id }}</td>
              <td>{{ log.table_name }}</td>
              <td>
                <span :class="actionClass(log.action)" class="px-2 py-1 rounded text-xs font-semibold">
                  {{ log.action }}
                </span>
              </td>
              <td>{{ log.user_email || '-' }}</td>
              <td class="max-w-xs truncate" :title="log.description">{{ log.description || '-' }}</td>
              <td>{{ log.ip_address || '-' }}</td>
              <td>{{ log.created_date ? new Date(log.created_date).toLocaleString() : '-' }}</td>
              <td class="text-right">
                <button @click="viewDetails(log)" class="text-indigo-400">View</button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Details Modal -->
        <div v-if="selectedLog" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" @click="selectedLog = null">
          <div class="bg-slate-800 rounded-lg p-6 max-w-2xl w-full max-h-[80vh] overflow-auto" @click.stop>
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xl font-bold">Log Details</h3>
              <button @click="selectedLog = null" class="text-slate-400 hover:text-white">&times;</button>
            </div>
            
            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="text-slate-400 text-sm">ID</label>
                  <div class="font-semibold">{{ selectedLog.id }}</div>
                </div>
                <div>
                  <label class="text-slate-400 text-sm">Action</label>
                  <div><span :class="actionClass(selectedLog.action)" class="px-2 py-1 rounded text-xs font-semibold">{{ selectedLog.action }}</span></div>
                </div>
                <div>
                  <label class="text-slate-400 text-sm">Table</label>
                  <div class="font-semibold">{{ selectedLog.table_name }}</div>
                </div>
                <div>
                  <label class="text-slate-400 text-sm">Record ID</label>
                  <div class="font-semibold">{{ selectedLog.record_id }}</div>
                </div>
                <div>
                  <label class="text-slate-400 text-sm">User</label>
                  <div class="font-semibold">{{ selectedLog.user_email || '-' }}</div>
                </div>
                <div>
                  <label class="text-slate-400 text-sm">IP Address</label>
                  <div class="font-semibold">{{ selectedLog.ip_address || '-' }}</div>
                </div>
                <div class="col-span-2">
                  <label class="text-slate-400 text-sm">Description</label>
                  <div class="font-semibold">{{ selectedLog.description || '-' }}</div>
                </div>
                <div class="col-span-2">
                  <label class="text-slate-400 text-sm">Created</label>
                  <div class="font-semibold">{{ selectedLog.created_date ? new Date(selectedLog.created_date).toLocaleString() : '-' }}</div>
                </div>
                <div v-if="selectedLog.old_values" class="col-span-2">
                  <label class="text-slate-400 text-sm">Old Values</label>
                  <pre class="bg-slate-900 p-3 rounded text-xs overflow-auto max-h-40">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
                </div>
                <div v-if="selectedLog.new_values" class="col-span-2">
                  <label class="text-slate-400 text-sm">New Values</label>
                  <pre class="bg-slate-900 p-3 rounded text-xs overflow-auto max-h-40">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4 flex items-center justify-between">
          <div class="text-sm text-slate-400">
            Showing {{ logs.from || 0 }} to {{ logs.to || 0 }} of {{ logs.total || 0 }} entries
          </div>
          <Pagination :paginator="logs" :onPageChange="goToPage" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  logs: Object,
  filters: Object,
  tableNames: Array,
  actions: Array,
  users: Array,
});

const logs = computed(() => props.logs);
const tableNames = props.tableNames || [];
const actions = props.actions || [];
const users = props.users || [];

const filters = reactive({
  search: props.filters.search || '',
  table_name: props.filters.table_name || '',
  action: props.filters.action || '',
  user_id: props.filters.user_id || '',
});

const selectedLog = ref(null);

let searchTimer = null;
function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetch(), 350);
}

function fetch() {
  const params = {};
  if (filters.search) params.search = filters.search;
  if (filters.table_name) params.table_name = filters.table_name;
  if (filters.action) params.action = filters.action;
  if (filters.user_id) params.user_id = filters.user_id;
  router.get('/control-panel/logs', params, { preserveState: true, preserveScroll: true });
}

function goToPage(pageNum) {
  const params = {};
  if (filters.search) params.search = filters.search;
  if (filters.table_name) params.table_name = filters.table_name;
  if (filters.action) params.action = filters.action;
  if (filters.user_id) params.user_id = filters.user_id;
  router.get('/control-panel/logs', { ...params, page: pageNum }, { preserveState: true, preserveScroll: true });
}

function next() {
  if (logs.value.next_page_url) goToPage(logs.value.current_page + 1);
}

function prev() {
  if (logs.value.prev_page_url) goToPage(logs.value.current_page - 1);
}

function viewDetails(log) {
  selectedLog.value = log;
}

function actionClass(action) {
  const classes = {
    'created': 'bg-green-900 text-green-300',
    'insert': 'bg-green-900 text-green-300',
    'updated': 'bg-blue-900 text-blue-300',
    'update': 'bg-blue-900 text-blue-300',
    'deleted': 'bg-red-900 text-red-300',
    'delete': 'bg-red-900 text-red-300',
    'login': 'bg-purple-900 text-purple-300',
    'logout': 'bg-yellow-900 text-yellow-300',
  };
  return classes[action?.toLowerCase()] || 'bg-slate-700 text-slate-300';
}
</script>
