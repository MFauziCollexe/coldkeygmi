<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <h2 class="text-2xl font-bold">Tickets</h2>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:flex lg:items-center">
          <input v-model="filters.search" @input="onSearchInput" placeholder="Search tickets..." class="w-full px-3 py-2 rounded bg-slate-800 text-sm lg:w-auto" />
          <select v-model="filters.status" @change="fetch" class="w-full px-3 py-2 rounded bg-slate-800 text-sm lg:w-auto">
            <option value="">All Statuses</option>
            <option value="Open">Open</option>
            <option value="In Progress">In Progress</option>
            <option value="On Hold">On Hold</option>
            <option value="Resolved">Resolved</option>
            <option value="Closed">Closed</option>
          </select>
          <div class="w-full lg:w-48">
            <SearchableSelect
              v-model="filters.department_id"
              :options="departments"
              option-value="id"
              option-label="name"
              placeholder="All Departments"
              empty-label="All Departments"
              input-class="bg-slate-800 text-sm"
              @update:modelValue="fetch"
            />
          </div>
          <Link href="/tickets/create" class="inline-flex items-center justify-center bg-indigo-600 px-4 py-2 rounded text-white">New Ticket</Link>
        </div>
      </div>

      <div class="bg-slate-800 rounded p-4">
        <div class="hidden overflow-x-auto lg:block">
        <table class="w-full table-auto">
          <thead>
            <tr class="text-left text-slate-400">
              <th class="py-2">#</th>
              <th>Image</th>
              <th>Title</th>
              <th>Requested By</th>
              <th>Created At</th>
              <th>Status</th>
              <th>Deadline</th>
              <th>Resolve Deadline</th>
              <th>Department</th>
              <th>Assigned To</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ticket in tickets.data" :key="ticket.id" class="border-t border-slate-700">
              <td class="py-3">{{ ticket.ticket_number }}</td>
              <td>
                <div v-if="ticket.attachments && ticket.attachments.length > 0" class="cursor-pointer">
                  <img 
                    :src="ticket.attachments[0].url" 
                    class="w-12 h-12 object-cover rounded"
                    @click="viewImage(ticket.attachments[0].url)"
                  />
                </div>
                <div v-else class="w-12 h-12 bg-slate-700 rounded flex items-center justify-center text-xs">-</div>
              </td>
              <td>{{ ticket.title }}</td>
              <td>{{ ticket.creator?.name || '-' }}</td>
              <td>{{ formatDateTime(ticket.created_at) }}</td>
              <td>
                <span :class="getStatusClass(ticket.status)">
                  {{ ticket.status.replace(/_/g, ' ').toUpperCase() }}
                </span>
              </td>
              <td :class="getDeadlineColorClass(ticket.deadline)">
                {{ ticket.deadline ? new Date(ticket.deadline).toLocaleDateString() : '-' }}
              </td>
              <td :class="getResolveDeadlineColorClass(ticket.resolve_deadline)">
                {{ ticket.resolve_deadline ? new Date(ticket.resolve_deadline).toLocaleDateString() : '-' }}
              </td>
              <td>{{ ticket.department?.name || '-' }}</td>
              <td>{{ ticket.assignee?.name || '-' }}</td>
              <td class="text-right">
                <Link :href="`/tickets/${ticket.id}`" class="text-indigo-400">View</Link>
              </td>
            </tr>
          </tbody>
        </table>
        </div>

        <div class="space-y-3 lg:hidden">
          <div
            v-for="ticket in tickets.data"
            :key="ticket.id"
            class="rounded-lg border border-slate-700 bg-slate-900/60 p-4"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="text-xs text-slate-400">{{ ticket.ticket_number }}</div>
                <div class="truncate font-semibold">{{ ticket.title }}</div>
              </div>
              <span :class="getStatusClass(ticket.status)">
                {{ ticket.status.replace(/_/g, ' ').toUpperCase() }}
              </span>
            </div>

            <div class="mt-3 grid grid-cols-[48px_1fr] gap-3">
              <div>
                <div v-if="ticket.attachments && ticket.attachments.length > 0" class="cursor-pointer">
                  <img
                    :src="ticket.attachments[0].url"
                    class="h-12 w-12 rounded object-cover"
                    @click="viewImage(ticket.attachments[0].url)"
                  />
                </div>
                <div v-else class="flex h-12 w-12 items-center justify-center rounded bg-slate-700 text-xs">-</div>
              </div>

              <div class="space-y-1 text-sm">
                <div><span class="text-slate-400">Requested By:</span> {{ ticket.creator?.name || '-' }}</div>
                <div><span class="text-slate-400">Created At:</span> {{ formatDateTime(ticket.created_at) }}</div>
                <div><span class="text-slate-400">Department:</span> {{ ticket.department?.name || '-' }}</div>
                <div><span class="text-slate-400">Assigned To:</span> {{ ticket.assignee?.name || '-' }}</div>
                <div :class="getDeadlineColorClass(ticket.deadline)">
                  <span class="text-slate-400">Deadline:</span>
                  {{ ticket.deadline ? new Date(ticket.deadline).toLocaleDateString() : '-' }}
                </div>
                <div :class="getResolveDeadlineColorClass(ticket.resolve_deadline)">
                  <span class="text-slate-400">Resolve Deadline:</span>
                  {{ ticket.resolve_deadline ? new Date(ticket.resolve_deadline).toLocaleDateString() : '-' }}
                </div>
              </div>
            </div>

            <div class="mt-3 flex justify-end">
              <Link :href="`/tickets/${ticket.id}`" class="text-indigo-400">View</Link>
            </div>
          </div>
        </div>

        <div v-if="!tickets.data || tickets.data.length === 0" class="text-center py-8 text-slate-400">
          No tickets found.
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
          <div class="text-sm text-slate-400">
            Showing {{ tickets.from || 0 }} to {{ tickets.to || 0 }} of {{ tickets.total || 0 }} tickets
          </div>
          <Pagination :paginator="tickets" :onPageChange="goToPage" />
        </div>
      </div>

      <!-- Image Preview Modal -->
      <div v-if="showImageModal" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50" @click="showImageModal = false">
        <div class="max-w-3xl" @click.stop>
          <img :src="previewImage" class="max-h-screen w-auto" />
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
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({ 
  tickets: Object, 
  departments: Array,
  filters: Object 
});

const tickets = computed(() => props.tickets || {});

const filters = reactive({
  search: props.filters.search || '',
  status: props.filters.status || '',
  department_id: props.filters.department_id || '',
});

const showImageModal = ref(false);
const previewImage = ref('');

let searchTimer = null;
function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetch(), 350);
}

function fetch() {
  const params = {};
  if (filters.search) params.search = filters.search;
  if (filters.status) params.status = filters.status;
  if (filters.department_id) params.department_id = filters.department_id;
  router.get('/tickets', params, { preserveState: true, preserveScroll: true });
}

function goToPage(pageNum) {
  const currentPage = tickets.value.current_page || 1;
  const lastPage = tickets.value.last_page || 1;
  if (pageNum < 1 || pageNum > lastPage || pageNum === currentPage) return;

  const params = {};
  if (filters.search) params.search = filters.search;
  if (filters.status) params.status = filters.status;
  if (filters.department_id) params.department_id = filters.department_id;
  router.get('/tickets', { ...params, page: pageNum }, { preserveState: true, preserveScroll: true });
}

function next() {
  if (tickets.value.next_page_url) goToPage((tickets.value.current_page || 1) + 1);
}

function prev() {
  if (tickets.value.prev_page_url) goToPage((tickets.value.current_page || 1) - 1);
}

const pageNumbers = computed(() => {
  const current = tickets.value.current_page || 1;
  const last = tickets.value.last_page || 1;
  const spread = 2;
  const start = Math.max(1, current - spread);
  const end = Math.min(last, current + spread);
  const pages = [];
  for (let i = start; i <= end; i++) pages.push(i);
  return pages;
});

function getStatusClass(status) {
  const colors = {
    'Open': 'bg-blue-600 text-white px-2 py-1 rounded text-xs',
    'In Progress': 'bg-yellow-600 text-white px-2 py-1 rounded text-xs',
    'On Hold': 'bg-orange-600 text-white px-2 py-1 rounded text-xs',
    'Resolved': 'bg-purple-600 text-white px-2 py-1 rounded text-xs',
    'Closed': 'bg-green-600 text-white px-2 py-1 rounded text-xs',
  };
  return colors[status] || 'bg-slate-600 text-white px-2 py-1 rounded text-xs';
}

function getDeadlineColorClass(deadline) {
  if (!deadline) return '';
  const today = new Date();
  const deadlineDate = new Date(deadline);
  const daysLeft = Math.ceil((deadlineDate - today) / (1000 * 60 * 60 * 24));
  
  if (daysLeft < 0) return 'text-red-400 font-bold'; // Overdue
  if (daysLeft <= 2) return 'text-yellow-400 font-bold'; // Within 2 days
  return 'text-green-400'; // Safe
}

function getResolveDeadlineColorClass(deadline) {
  return getDeadlineColorClass(deadline);
}

function formatDateTime(value) {
  if (!value) return '-';

  return new Date(value).toLocaleString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function viewImage(imageSrc) {
  previewImage.value = imageSrc;
  showImageModal.value = true;
}
</script>
