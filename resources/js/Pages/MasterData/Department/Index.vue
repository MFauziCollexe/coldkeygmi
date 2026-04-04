<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-bold">Departments</h2>
        <Link href="/master-data/department/create" class="bg-indigo-600 px-4 py-2 rounded text-white">Add Department</Link>
      </div>

      <div class="bg-slate-800 rounded p-4">
        <div class="hidden overflow-auto lg:block">
        <table class="w-full table-auto">
          <thead>
            <tr class="text-left text-slate-400">
              <th class="py-2">#</th>
              <th>Code</th>
              <th>Name</th>
              <th>Description</th>
              <th>Status</th>
              <th>Created</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="department in departments.data" :key="department.id" class="border-t border-slate-700">
              <td class="py-3">{{ department.id }}</td>
              <td>{{ department.code }}</td>
              <td>{{ department.name }}</td>
              <td>{{ department.description || '-' }}</td>
              <td>
                <span :class="department.is_active ? 'text-green-400' : 'text-red-400'">
                  {{ department.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td>{{ new Date(department.created_at).toLocaleDateString() }}</td>
              <td class="text-right">
                <Link :href="`/master-data/department/${department.id}/edit`" class="text-indigo-400 mr-3">Edit</Link>
                <button @click="deleteDepartment(department.id)" class="text-red-400">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-700 lg:hidden">
          <div v-for="department in departments.data" :key="`mobile-${department.id}`" class="border-b border-slate-700 bg-slate-900/30 p-4 last:border-b-0">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="truncate font-semibold text-white">{{ department.name }}</div>
                <div class="text-sm text-slate-400">{{ department.code }}</div>
              </div>
              <span :class="department.is_active ? 'text-green-400' : 'text-red-400'">
                {{ department.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <div class="mt-3 space-y-2 text-sm">
              <div class="flex items-start justify-between gap-3">
                <div class="text-slate-400">Description</div>
                <div class="text-right">{{ department.description || '-' }}</div>
              </div>
              <div class="flex items-start justify-between gap-3">
                <div class="text-slate-400">Created</div>
                <div class="text-right">{{ new Date(department.created_at).toLocaleDateString() }}</div>
              </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
              <Link :href="`/master-data/department/${department.id}/edit`" class="inline-flex items-center justify-center rounded bg-indigo-600 px-3 py-2 text-sm text-white">Edit</Link>
              <button @click="deleteDepartment(department.id)" class="rounded bg-rose-600 px-3 py-2 text-sm text-white">Delete</button>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <Pagination :paginator="departments" :onPageChange="goToPage" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { swalConfirm } from '@/Utils/swalConfirm';

const props = defineProps({ departments: Object });

const departments = computed(() => props.departments);

function next() {
  if (departments.value.next_page_url) {
    router.get(departments.value.next_page_url, {}, { preserveState: true, preserveScroll: true });
  }
}

function prev() {
  if (departments.value.prev_page_url) {
    router.get(departments.value.prev_page_url, {}, { preserveState: true, preserveScroll: true });
  }
}

async function deleteDepartment(id) {
  const ok = await swalConfirm({
    title: 'Delete Department',
    text: 'Are you sure you want to delete this department?',
    confirmButtonText: 'Delete',
    confirmButtonColor: '#dc2626',
  });
  if (!ok) return;

  router.delete(`/master-data/department/${id}`, {
    onSuccess: () => {
      // Will refresh automatically
    },
  });
}

function goToPage(page) {
  router.get('/master-data/department', { page }, { preserveState: true, preserveScroll: true });
}
</script>
