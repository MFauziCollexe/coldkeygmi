<template>
  <AppLayout>
    <div class="p-4 sm:p-6">
      <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Users</h2>
          <p class="mt-1 text-sm text-slate-400 sm:hidden">
            Kelola akun user dari Control Panel.
          </p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
          <input
            v-model="filters.search"
            @input="onSearchInput"
            placeholder="Search users..."
            class="w-full rounded bg-slate-800 px-3 py-2 text-sm sm:w-56"
          />
          <select v-model="filters.status" @change="fetch" class="w-full rounded bg-slate-800 px-3 py-2 text-sm sm:w-40">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="deactivated">Deactivated</option>
          </select>
          <div class="w-full sm:w-48">
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
          <Link href="/control-panel/user/create" class="inline-flex items-center justify-center rounded bg-indigo-600 px-4 py-2 text-white">
            Add User
          </Link>
        </div>
      </div>

      <div v-if="$page.props.flash?.success" class="bg-green-600/20 border border-green-600 text-green-400 px-4 py-2 rounded mb-4">
        {{ $page.props.flash.success }}
      </div>

      <div v-if="$page.props.errors?.error" class="bg-red-600/20 border border-red-600 text-red-400 px-4 py-2 rounded mb-4">
        {{ $page.props.errors.error }}
      </div>

      <div class="rounded bg-slate-800 p-4">
        <div class="space-y-3 lg:hidden">
          <div
            v-for="(user, index) in users.data"
            :key="user.id"
            class="rounded-lg border border-slate-700 bg-slate-900/40 p-4"
          >
            <div class="mb-3 flex items-start justify-between gap-3">
              <div>
                <div class="text-xs text-slate-400">
                  #{{ (users.current_page - 1) * users.per_page + index + 1 }}
                </div>
                <div class="text-base font-semibold text-slate-100">{{ user.account }}</div>
                <div class="mt-1 break-all text-sm text-slate-300">{{ user.email }}</div>
              </div>
              <div class="shrink-0 rounded-full px-2 py-1 text-xs font-semibold" :class="user.status === 'active' ? 'bg-green-600/15 text-green-400' : 'bg-red-600/15 text-red-400'">
                {{ user.status }}
              </div>
            </div>

            <div class="space-y-2 text-sm">
              <div class="flex justify-between gap-4">
                <span class="text-slate-400">Department</span>
                <span class="text-right text-slate-200">{{ user.department?.name || '-' }}</span>
              </div>
              <div class="flex justify-between gap-4">
                <span class="text-slate-400">Position</span>
                <span class="text-right text-slate-200">{{ user.position?.name || '-' }}</span>
              </div>
              <div class="flex justify-between gap-4">
                <span class="text-slate-400">Admin</span>
                <span :class="user.is_admin ? 'text-yellow-400' : 'text-slate-300'">
                  {{ user.is_admin ? 'Yes' : 'No' }}
                </span>
              </div>
              <div class="flex justify-between gap-4">
                <span class="text-slate-400">Created</span>
                <span class="text-right text-slate-200">{{ formatDate(user.created_date) }}</span>
              </div>
            </div>

            <div class="mt-4 flex gap-2">
              <Link
                :href="`/control-panel/user/${user.id}/edit`"
                class="flex-1 rounded bg-indigo-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-indigo-500"
              >
                Edit
              </Link>
              <button
                @click="deleteUser(user)"
                class="flex-1 rounded bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-500"
              >
                Delete
              </button>
            </div>
          </div>

          <div v-if="users.data.length === 0" class="rounded-lg border border-dashed border-slate-700 px-4 py-8 text-center text-slate-400">
            No users found.
          </div>
        </div>

        <div class="hidden lg:block overflow-x-auto">
        <table class="w-full table-auto">
          <thead>
            <tr class="text-left text-slate-400">
              <th class="py-2">#</th>
              <th>Account</th>
              <th>Email</th>
              <th>Department</th>
              <th>Position</th>
              <th>Status</th>
              <th>Admin</th>
              <th>Created</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(user, index) in users.data" :key="user.id" class="border-t border-slate-700">
              <td class="py-3">{{ (users.current_page - 1) * users.per_page + index + 1 }}</td>
              <td>{{ user.account }}</td>
              <td>{{ user.email }}</td>
              <td>{{ user.department?.name || '-' }}</td>
              <td>{{ user.position?.name || '-' }}</td>
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
                <Link :href="`/control-panel/user/${user.id}/edit`" class="text-indigo-400 hover:text-indigo-300">Edit</Link>
                <button @click="deleteUser(user)" class="text-red-400 hover:text-red-300">Delete</button>
              </td>
            </tr>
            <tr v-if="users.data.length === 0">
              <td colspan="9" class="py-4 text-center text-slate-400">No users found.</td>
            </tr>
          </tbody>
        </table>
        </div>

        <div class="mt-4">
          <Pagination :paginator="users" :onPageChange="goToPage" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Pagination from '@/Components/Pagination.vue';
import { swalConfirm } from '@/Utils/swalConfirm';

const props = defineProps({
  users: Object,
  filters: Object,
  departments: Array,
});

const users = computed(() => props.users);

const filters = reactive({
  search: props.filters.search || '',
  status: props.filters.status || '',
  department_id: props.filters.department_id || '',
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
  if (filters.department_id) params.department_id = filters.department_id;
  router.get('/control-panel/user', params, { preserveState: true, preserveScroll: true });
}

function goToPage(pageNum) {
  const params = { page: pageNum };
  if (filters.search) params.search = filters.search;
  if (filters.status) params.status = filters.status;
  if (filters.department_id) params.department_id = filters.department_id;
  router.get('/control-panel/user', params, { preserveState: true, preserveScroll: true });
}

function next() {
  if (users.value.next_page_url) goToPage(users.value.current_page + 1);
}

function prev() {
  if (users.value.prev_page_url) goToPage(users.value.current_page - 1);
}

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

async function deleteUser(user) {
  const ok = await swalConfirm({
    title: 'Delete User',
    text: `Are you sure you want to delete ${user.first_name} ${user.last_name}?`,
    confirmButtonText: 'Delete',
    confirmButtonColor: '#dc2626',
  });
  if (!ok) return;

  router.delete(`/control-panel/user/${user.id}`, {
    onSuccess: () => {
      // Reload to get updated list
    },
  });
}
</script>
