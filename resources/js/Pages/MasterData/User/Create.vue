<template>
  <AppLayout>
    <div class="p-6">
      <div class="flex items-center gap-2 mb-6">
        <Link href="/master-data/user" class="text-slate-400 hover:text-white">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
          </svg>
        </Link>
        <h2 class="text-2xl font-bold">Create User</h2>
      </div>

      <div class="bg-slate-800 rounded p-6 max-w-2xl">
        <form @submit.prevent="submit">
          <div class="grid grid-cols-2 gap-4">
            <div class="mb-4">
              <label class="block text-sm text-slate-400 mb-1">First Name *</label>
              <input v-model="form.first_name" type="text" class="w-full px-3 py-2 rounded bg-slate-700 text-white" required />
              <div v-if="errors.first_name" class="text-red-400 text-sm mt-1">{{ errors.first_name }}</div>
            </div>
            <div class="mb-4">
              <label class="block text-sm text-slate-400 mb-1">Last Name *</label>
              <input v-model="form.last_name" type="text" class="w-full px-3 py-2 rounded bg-slate-700 text-white" required />
              <div v-if="errors.last_name" class="text-red-400 text-sm mt-1">{{ errors.last_name }}</div>
            </div>
          </div>

          <div class="mb-4">
            <label class="block text-sm text-slate-400 mb-1">Account *</label>
            <input v-model="form.account" type="text" class="w-full px-3 py-2 rounded bg-slate-700 text-white" required />
            <div v-if="errors.account" class="text-red-400 text-sm mt-1">{{ errors.account }}</div>
          </div>

          <div class="mb-4">
            <label class="block text-sm text-slate-400 mb-1">Email *</label>
            <input v-model="form.email" type="email" class="w-full px-3 py-2 rounded bg-slate-700 text-white" required />
            <div v-if="errors.email" class="text-red-400 text-sm mt-1">{{ errors.email }}</div>
          </div>

          <div class="mb-4">
            <label class="block text-sm text-slate-400 mb-1">Password *</label>
            <input v-model="form.password" type="password" class="w-full px-3 py-2 rounded bg-slate-700 text-white" required minlength="8" />
            <div v-if="errors.password" class="text-red-400 text-sm mt-1">{{ errors.password }}</div>
          </div>

          <div class="mb-4">
            <label class="block text-sm text-slate-400 mb-1">Status *</label>
            <select v-model="form.status" class="w-full px-3 py-2 rounded bg-slate-700 text-white" required>
              <option value="active">Active</option>
              <option value="deactivated">Deactivated</option>
            </select>
            <div v-if="errors.status" class="text-red-400 text-sm mt-1">{{ errors.status }}</div>
          </div>

          <div class="mb-4">
            <label class="block text-sm text-slate-400 mb-1">Department</label>
            <SearchableSelect
              v-model="form.department"
              :options="departments"
              option-value="name"
              option-label="name"
              placeholder="Select Department"
              empty-label="Select Department"
              input-class="bg-slate-700 text-white"
            />
          </div>

          <div class="mb-4">
            <label class="block text-sm text-slate-400 mb-1">Job Position</label>
            <input v-model="form.job_position" type="text" class="w-full px-3 py-2 rounded bg-slate-700 text-white" />
          </div>

          <div class="mb-4">
            <label class="block text-sm text-slate-400 mb-1">Work Position</label>
            <input v-model="form.work_position" type="text" class="w-full px-3 py-2 rounded bg-slate-700 text-white" />
          </div>

          <div class="mb-6">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.is_admin" type="checkbox" class="w-4 h-4 rounded bg-slate-700 text-indigo-600" />
              <span class="text-sm text-slate-300">Is Admin</span>
            </label>
          </div>

          <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 px-4 py-2 rounded text-white hover:bg-indigo-700">Create User</button>
            <Link href="/master-data/user" class="px-4 py-2 rounded bg-slate-700 text-slate-300 hover:bg-slate-600">Cancel</Link>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
  departments: Array,
  errors: Object,
});

const form = reactive({
  first_name: '',
  last_name: '',
  account: '',
  email: '',
  password: '',
  status: 'active',
  department: '',
  job_position: '',
  work_position: '',
  is_admin: false,
});

const departments = props.departments || [];

function submit() {
  router.post('/master-data/user', form, {
    onError: (errors) => {
    },
  });
}
</script>
