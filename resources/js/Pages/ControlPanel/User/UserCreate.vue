<template>
  <AppLayout>
    <div class="p-6">
      <div class="flex items-center gap-2 mb-6">
        <Link href="/control-panel/user" class="text-slate-400 hover:text-white">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
          </svg>
        </Link>
        <h2 class="text-2xl font-bold">Create User</h2>
      </div>

      <div class="bg-slate-800 rounded p-6 max-w-2xl">
        <form @submit.prevent="submit">
          <div class="mb-4">
            <label class="block text-sm text-slate-400 mb-1">Name *</label>
            <input v-model="form.name" type="text" class="w-full px-3 py-2 rounded bg-slate-700 text-white" required />
            <div v-if="errors.name" class="text-red-400 text-sm mt-1">{{ errors.name }}</div>
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
            <input v-model="form.password" type="password" class="w-full px-3 py-2 rounded bg-slate-700 text-white" required />
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
              v-model="form.department_id"
              :options="departments"
              option-value="id"
              option-label="name"
              placeholder="Select Department"
              empty-label="Select Department"
              input-class="bg-slate-700 text-white"
              @update:modelValue="onDepartmentChange"
            />
          </div>

          <div class="mb-4">
            <label class="block text-sm text-slate-400 mb-1">Position</label>
            <SearchableSelect
              v-model="form.position_id"
              :options="filteredPositions"
              option-value="id"
              option-label="name"
              placeholder="Select Position"
              empty-label="Select Position"
              input-class="bg-slate-700 text-white"
            />
          </div>

          <div class="mb-6">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.is_admin" type="checkbox" class="w-4 h-4 rounded bg-slate-700 text-indigo-600" />
              <span class="text-sm text-slate-300">Is Admin</span>
            </label>
          </div>

          <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 px-4 py-2 rounded text-white hover:bg-indigo-700">Create User</button>
            <Link href="/control-panel/user" class="px-4 py-2 rounded bg-slate-700 text-slate-300 hover:bg-slate-600">Cancel</Link>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
  departments: Array,
  positions: Array,
  errors: Object,
});

const form = reactive({
  name: '',
  account: '',
  email: '',
  password: '',
  status: 'active',
  department_id: '',
  position_id: '',
  is_admin: false,
});

const departments = props.departments || [];
const positions = props.positions || [];

const filteredPositions = computed(() => {
  if (!form.department_id) return positions;
  return positions.filter(p => p.department_id === form.department_id);
});

function onDepartmentChange() {
  form.position_id = '';
}

function submit() {
  Inertia.post('/control-panel/user', form, {
    onError: (errors) => {
    },
  });
}
</script>
