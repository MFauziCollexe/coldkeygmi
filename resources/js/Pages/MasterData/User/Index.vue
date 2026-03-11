<template>
  <AppLayout>
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Users</h2>
        <div class="flex items-center gap-2">
          <input v-model="filters.search" @input="onSearchInput" placeholder="Search users..." class="px-3 py-2 rounded bg-slate-800 text-sm" />
          <select v-model="filters.status" @change="fetch" class="px-3 py-2 rounded bg-slate-800 text-sm">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="deactivated">Deactivated</option>
          </select>
          <div class="w-48">
            <SearchableSelect
              v-model="filters.department"
              :options="departments"
              option-value="name"
              option-label="name"
              placeholder="All Departments"
              empty-label="All Departments"
              input-class="bg-slate-800 text-sm"
              @update:modelValue="fetch"
            />
          </div>
          <Link href="/master-data/user/create" class="bg-indigo-600 px-4 py-2 rounded text-white">Add User</Link>
        </div>
      </div>

      <div v-if="$page.props.flash?.success" class="bg-green-600/20 border border-green-600 text-green-400 px-4 py-2 rounded mb-4">
        {{ $page.props.flash.success }}
      </div>

      <div v-if="$page.props.errors?.error" class="bg-red-600/20 border border-red-600 text-red-400 px-4 py-2 rounded mb-4">
        {{ $page.props.errors.error }}
      </div>

      <div class="bg-slate-800 rounded p-4">
        <table class="w-full table-auto">
          <thead>
            <tr class="text-left text-slate-400">
              <th class="py-2">#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Department</th>
              <th>Job Position</th>
              <th>Work Position</th>
              <th>Status</th>
              <th>Admin</th>
              <th>Created</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(user, index) in users.data" :key="user.id" class="border-t border-slate-700">
              <td class="py-3">{{ (users.current_page - 1) * users.per_page + index + 1 }}</td>
              <td>{{ user.first_name }} {{ user.last_name }}</td>
              <td>{{ user.email }}</td>
              <td>{{ user.department || '-' }}</td>
              <td>{{ user.job_position || '-' }}</td>
              <td>{{ user.work_position || '-' }}</td>
              <td>
                <span :class="user.status === 'active' ? 'text-green-400' : 'text-red-400'">
                  {{ user.status }}
                </span>
              </td>
              <td>
                <span :class="user.is_admin ? 'text-yellow-400' : 'text-slate-400'">
                  {{ user.is_admin ? 'Yes' : 'No' }}
                </span>
              </td>
              <td>{{ formatDate(user.created_date) }}</td>
              <td class="flex gap-2">
                <Link :href="`/master-data/user/${user.id}/edit`" class="text-indigo-400 hover:text-indigo-300">Edit</Link>
                <button @click="deleteUser(user)" class="text-red-400 hover:text-red-300">Delete</button>
              </td>
            </tr>
            <tr v-if="users.data.length === 0">
              <td colspan="10" class="py-4 text-center text-slate-400">No users found.</td>
            </tr>
          </tbody>
        </table>

        <div class="mt-4 flex items-center justify-between">
          <button @click="prev" :disabled="!users.prev_page_url" class="px-3 py-1 bg-slate-700 rounded mr-2 disabled:opacity-50">Prev</button>
          <span class="text-sm text-slate-400">Page {{ users.current_page }} of {{ users.last_page }}</span>
          <button @click="next" :disabled="!users.next_page_url" class="px-3 py-1 bg-slate-700 rounded disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

import Swal from 'sweetalert2';

const props = defineProps({
  users: Object,
  filters: Object,
  departments: Array,
});

const users = reactive(props.users);

const filters = reactive({
  search: props.filters.search || '',
  status: props.filters.status || '',
  department: props.filters.department || '',
});

const departments = props.departments || [];

let searchTimer = null;
function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetch(), 350);
}

function fetch() {
  const params = {};
  if (filters.search) params.search = filters.search;
  if (filters.status) params.status = filters.status;
  if (filters.department) params.department = filters.department;
  router.get('/master-data/user', params, { preserveState: true, preserveScroll: true });
}

function goToPage(pageNum) {
  const params = { page: pageNum };
  if (filters.search) params.search = filters.search;
  if (filters.status) params.status = filters.status;
  if (filters.department) params.department = filters.department;
  router.get('/master-data/user', params, { preserveState: true, preserveScroll: true });
}

function next() {
  if (users.next_page_url) goToPage(users.current_page + 1);
}

function prev() {
  if (users.prev_page_url) goToPage(users.current_page - 1);
}

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

function deleteUser(user) {
  if (confirm(`Are you sure you want to delete ${user.first_name} ${user.last_name}?`)) {
    router.delete(`/master-data/user/${user.id}`, {
      onSuccess: () => {
        // Reload to get updated list
      },
    });
  }
}
</script>
