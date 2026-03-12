<template>
  <AppLayout>
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Employee</h2>
        <div class="flex gap-2">
          <!-- <button @click="deleteAll" class="bg-red-600 px-4 py-2 rounded text-white">Delete All</button> -->
          <Link href="/master-data/employee/create" class="bg-indigo-600 px-4 py-2 rounded text-white">Add Employee</Link>
        </div>
      </div>

      <!-- Search -->
      <div class="mb-4 flex gap-4">
        <form @submit.prevent="search" class="flex gap-2">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by Name or NIK..."
            class="px-4 py-2 border rounded w-64 bg-white dark:bg-gray-800"
          />
          <select v-model="statusFilter" class="px-4 py-2 border rounded bg-white dark:bg-gray-800">
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="resigned">Resigned</option>
          </select>
          <button type="submit" class="bg-indigo-600 px-4 py-2 rounded text-white">Search</button>
          <button type="button" @click="resetSearch" class="bg-gray-500 px-4 py-2 rounded text-white">Reset</button>
        </form>
      </div>

      <div class="bg-slate-800 rounded p-4 overflow-x-auto">
        <table class="w-full table-auto">
          <thead>
            <tr class="text-left text-slate-400">
              <th class="py-2">#</th>
              <th>NIK</th>
              <th>Name</th>
              <th>Department</th>
              <th>Position</th>
              <th>Work Group</th>
              <th>Join Date</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(emp, index) in employees.data" :key="emp.id" class="border-t border-slate-700">
              <td class="py-3">{{ (employees.current_page - 1) * employees.per_page + index + 1 }}</td>
              <td>{{ emp.nik || '-' }}</td>
              <td>{{ emp.name || '-' }}</td>
              <td>{{ emp.user?.department?.name || emp.department?.name || '-' }}</td>
              <td>{{ emp.user?.position?.name || emp.position?.name || '-' }}</td>
              <td>{{ formatWorkGroup(emp.work_group) }}</td>
              <td>{{ formatDate(emp.join_date) || '-' }}</td>
              <td>
                <span v-if="emp.employment_status === 'resigned'" class="text-red-400">
                  Non Active
                </span>
                <span v-else class="text-green-400">Active</span>
              </td>
              <td class="text-right">
                <Link
                  :href="`/master-data/employee/${emp.id}/edit`"
                  class="inline-flex items-center rounded bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700 mr-2"
                >
                  Edit
                </Link>
                <button
                  v-if="emp.employment_status !== 'resigned'"
                  @click="resignEmployee(emp.id)"
                  class="inline-flex items-center rounded bg-yellow-600 px-3 py-1 text-xs font-semibold text-white hover:bg-yellow-700 mr-2"
                >
                  Resign
                </button>
                <button
                  v-else
                  @click="cancelResignEmployee(emp.id)"
                  class="inline-flex items-center rounded bg-green-600 px-3 py-1 text-xs font-semibold text-white hover:bg-green-700 mr-2"
                >
                  Active
                </button>
                <button
                  type="button"
                  @click="deleteEmployee(emp.id)"
                  class="inline-flex items-center rounded bg-red-600 px-3 py-1 text-xs font-semibold text-white hover:bg-red-700"
                >
                  Delete
                </button>
              </td>
            </tr>
            <tr v-if="employees.data.length === 0">
              <td colspan="9" class="py-4 text-center text-slate-400">No employees found</td>
            </tr>
          </tbody>
        </table>

        <div class="mt-4">
          <Pagination :paginator="employees" :onPageChange="goToPage" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { swalConfirm } from '@/Utils/swalConfirm';

const props = defineProps({
  employees: Object,
  departments: Object,
  positions: Object,
  filters: Object
});

// Always read the latest paginator from Inertia props (don't copy into local reactive state).
const employees = computed(() => props.employees);
const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

function search() {
  router.get('/master-data/employee', 
    { search: searchQuery.value, status: statusFilter.value },
    { preserveState: true, preserveScroll: true }
  );
}

function resetSearch() {
  searchQuery.value = '';
  statusFilter.value = '';
  router.get('/master-data/employee', {}, { preserveState: true, preserveScroll: true });
}

function goToPage(page) {
  router.get('/master-data/employee', 
    { page: page, search: searchQuery.value, status: statusFilter.value },
    { preserveState: true, preserveScroll: true }
  );
}

async function deleteEmployee(id) {
  const ok = await swalConfirm({
    title: 'Delete Employee',
    text: 'Are you sure you want to delete this employee?',
    confirmButtonText: 'Delete',
    confirmButtonColor: '#dc2626',
  });
  if (!ok) return;

  router.delete(`/master-data/employee/${id}`, {
    onSuccess: () => {
      // Will refresh automatically
    },
  });
}

async function resignEmployee(id) {
  const ok = await swalConfirm({
    title: 'Resign Employee',
    text: 'Mark this employee as resigned?',
    confirmButtonText: 'Resign',
    confirmButtonColor: '#f59e0b',
  });
  if (!ok) return;

  router.put(`/master-data/employee/${id}/resign`, {}, {
    onSuccess: () => {
      // Will refresh automatically
    },
  });
}

async function cancelResignEmployee(id) {
  const ok = await swalConfirm({
    title: 'Cancel Resign',
    text: 'Kembalikan status karyawan menjadi Active?',
    confirmButtonText: 'Aktifkan',
    confirmButtonColor: '#16a34a',
  });
  if (!ok) return;

  router.put(`/master-data/employee/${id}/cancel-resign`, {}, {
    onSuccess: () => {
      // Will refresh automatically
    },
  });
}

async function deleteAll() {
  const ok = await swalConfirm({
    title: 'Delete All Employees',
    text: 'Are you sure you want to delete all employees?',
    confirmButtonText: 'Delete All',
    confirmButtonColor: '#dc2626',
  });
  if (!ok) return;

  router.delete('/master-data/employee/delete-all', {
    onSuccess: () => {
      // Will refresh automatically
    },
  });
}

function formatDate(date) {
  if (!date) return null;
  return new Date(date).toLocaleDateString('id-ID');
}

function formatWorkGroup(value) {
  if (value === 'office') return 'Office';
  if (value === 'operational') return 'Operational';
  return '-';
}
</script>
