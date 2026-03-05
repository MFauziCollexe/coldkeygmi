<template>
  <AppLayout>
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Departments</h2>
        <Link href="/master-data/department/create" class="bg-indigo-600 px-4 py-2 rounded text-white">Add Department</Link>
      </div>

      <div class="bg-slate-800 rounded p-4">
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

        <div class="mt-4">
          <button @click="prev" :disabled="!departments.prev_page_url" class="px-3 py-1 bg-slate-700 rounded mr-2">Prev</button>
          <button @click="next" :disabled="!departments.next_page_url" class="px-3 py-1 bg-slate-700 rounded">Next</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Inertia } from '@inertiajs/inertia';

import Swal from 'sweetalert2';

const props = defineProps({ departments: Object });

const departments = reactive(props.departments);

function next() {
  if (departments.next_page_url) {
    Inertia.get(departments.next_page_url, {}, { preserveState: true, preserveScroll: true });
  }
}

function prev() {
  if (departments.prev_page_url) {
    Inertia.get(departments.prev_page_url, {}, { preserveState: true, preserveScroll: true });
  }
}

function deleteDepartment(id) {
  if (confirm('Are you sure you want to delete this department?')) {
    Inertia.delete(`/master-data/department/${id}`, {
      onSuccess: () => {
        // Will refresh automatically
      },
    });
  }
}
</script>
