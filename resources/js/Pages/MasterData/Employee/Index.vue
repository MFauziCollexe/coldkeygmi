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
              <th>Work Group</th>
              <th>Phone</th>
              <th>Address</th>
              <th>Bird Date</th>
              <th>Gender</th>
              <th>Join Date</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(emp, index) in employees.data" :key="emp.id" class="border-t border-slate-700">
              <td class="py-3">{{ (employees.current_page - 1) * employees.per_page + index + 1 }}</td>
              <td>{{ emp.nik || '-' }}</td>
              <td>{{ emp.name || '-' }}</td>
              <td>{{ formatWorkGroup(emp.work_group) }}</td>
              <td>{{ emp.phone || '-' }}</td>
              <td>{{ emp.address || '-' }}</td>
              <td>{{ formatDate(emp.birth_date) || '-' }}</td>
              <td>{{ emp.gender || '-' }}</td>
              <td>{{ formatDate(emp.join_date) || '-' }}</td>
              <td class="text-right">
                <Link :href="`/master-data/employee/${emp.id}/edit`" class="text-indigo-400 mr-3">Edit</Link>
                <button @click="deleteEmployee(emp.id)" class="text-red-400">Delete</button>
              </td>
            </tr>
            <tr v-if="employees.data.length === 0">
              <td colspan="11" class="py-4 text-center text-slate-400">No employees found</td>
            </tr>
          </tbody>
        </table>

        <div class="mt-4 flex justify-center gap-2">
          <button 
            v-for="page in employees.last_page" 
            :key="page"
            @click="goToPage(page)"
            :class="[
              'px-3 py-1 rounded',
              page === employees.current_page 
                ? 'bg-indigo-600 text-white' 
                : 'bg-slate-700 text-slate-300'
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Inertia } from '@inertiajs/inertia';

import Swal from 'sweetalert2';

const props = defineProps({
  employees: Object,
  departments: Object,
  positions: Object,
  filters: Object
});

const employees = reactive(props.employees);
const searchQuery = ref(props.filters?.search || '');

function search() {
  Inertia.get('/master-data/employee', 
    { search: searchQuery.value },
    { preserveState: true, preserveScroll: true }
  );
}

function resetSearch() {
  searchQuery.value = '';
  Inertia.get('/master-data/employee', {}, { preserveState: true, preserveScroll: true });
}

function goToPage(page) {
  Inertia.get('/master-data/employee', 
    { page: page, search: searchQuery.value },
    { preserveState: true, preserveScroll: true }
  );
}

function deleteEmployee(id) {
  if (confirm('Are you sure you want to delete this employee?')) {
    Inertia.delete(`/master-data/employee/${id}`, {
      onSuccess: () => {
        // Will refresh automatically
      },
    });
  }
}

function deleteAll() {
  if (confirm('Are you sure you want to delete all employees?')) {
    Inertia.delete('/master-data/employee/delete-all', {
      onSuccess: () => {
        // Will refresh automatically
      },
    });
  }
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
