<template>
  <AppLayout>
    <div class="p-4 sm:p-6">
      <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Activity Logs</h2>
          <p class="mt-1 text-sm text-slate-400 sm:hidden">Pantau aktivitas penting sistem dari Control Panel.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
          <button
            type="button"
            class="px-3 py-2 rounded bg-rose-600 text-sm font-semibold text-white hover:bg-rose-500"
            @click="clearLogs"
          >
            Hapus Semua Log
          </button>
          <input v-model="filters.search" @input="onSearchInput" placeholder="Search logs..." class="w-full px-3 py-2 rounded bg-slate-800 text-sm sm:w-48" />
          <div class="w-full sm:w-44">
            <SearchableSelect
              v-model="filters.table_name"
              :options="tableNames"
              placeholder="All Tables"
              empty-label="All Tables"
              input-class="bg-slate-800 text-sm"
              @update:modelValue="fetch"
            />
          </div>
          <div class="w-full sm:w-44">
            <SearchableSelect
              v-model="filters.action"
              :options="actions"
              placeholder="All Actions"
              empty-label="All Actions"
              input-class="bg-slate-800 text-sm"
              @update:modelValue="fetch"
            />
          </div>
          <div class="w-full sm:w-52">
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
        <div class="space-y-3 lg:hidden">
          <div v-for="log in logs.data" :key="log.id" class="rounded-lg border border-slate-700 bg-slate-900/40 p-4">
            <div class="mb-3 flex items-start justify-between gap-3">
              <div>
                <div class="text-xs text-slate-400">#{{ log.id }}</div>
                <div class="mt-1 text-sm font-semibold text-slate-100">{{ log.table_name }}</div>
                <div class="mt-1 text-xs text-slate-400">{{ log.user_email || '-' }}</div>
              </div>
              <span :class="actionClass(log.action)" class="px-2 py-1 rounded text-xs font-semibold">
                {{ log.action }}
              </span>
            </div>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between gap-4">
                <span class="text-slate-400">IP Address</span>
                <span class="text-right text-slate-200">{{ log.ip_address || '-' }}</span>
              </div>
              <div class="flex justify-between gap-4">
                <span class="text-slate-400">Created</span>
                <span class="text-right text-slate-200">{{ log.created_date ? new Date(log.created_date).toLocaleString() : '-' }}</span>
              </div>
              <div>
                <div class="text-slate-400">Description</div>
                <div class="mt-1 text-slate-200">{{ log.description || '-' }}</div>
              </div>
            </div>
            <button @click="viewDetails(log)" class="mt-4 w-full rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500">
              View Details
            </button>
          </div>
        </div>

        <div class="hidden lg:block overflow-x-auto">
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
        </div>

        <!-- Details Modal -->
        <div v-if="selectedLog" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" @click="selectedLog = null">
          <div class="bg-slate-800 rounded-lg p-4 sm:p-6 max-w-2xl w-full max-h-[80vh] overflow-auto" @click.stop>
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xl font-bold">Log Details</h3>
              <button @click="selectedLog = null" class="text-slate-400 hover:text-white">&times;</button>
            </div>
            
            <div class="space-y-4">
              <div class="grid gap-4 sm:grid-cols-2">
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

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
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
import { swalConfirm } from '@/Utils/swalConfirm';

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

async function clearLogs() {
  const ok = await swalConfirm({
    title: 'Hapus Semua Log?',
    text: 'Yakin ingin menghapus semua activity log?',
    confirmButtonText: 'Hapus',
    confirmButtonColor: '#dc2626',
  });

  if (!ok) {
    return;
  }

  router.delete('/control-panel/logs/clear', {
    preserveScroll: true,
  });
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
