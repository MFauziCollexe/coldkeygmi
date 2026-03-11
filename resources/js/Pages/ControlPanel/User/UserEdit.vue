<template>
  <AppLayout>
    <div class="p-6 max-w-2xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Edit User</h2>
        <Link href="/control-panel/user" class="text-indigo-400 hover:underline text-sm">← Back to List</Link>
      </div>

      <form @submit.prevent="submit" class="space-y-4 bg-slate-800 p-4 rounded">
        <div class="relative">
          <input
            v-model="form.name"
            placeholder=" "
            class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-100"
            required
          />
          <label
            class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
          >
            Name *
          </label>
          <div v-if="errors.name" class="text-red-400 text-sm mt-1">{{ errors.name }}</div>
        </div>

        <div class="relative">
          <input
            v-model="form.account"
            placeholder=" "
            class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-100"
            required
          />
          <label
            class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
          >
            Account *
          </label>
          <div v-if="errors.account" class="text-red-400 text-sm mt-1">{{ errors.account }}</div>
        </div>

        <div class="relative">
          <input
            v-model="form.email"
            type="email"
            placeholder=" "
            class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-100"
            required
          />
          <label
            class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
          >
            Email *
          </label>
          <div v-if="errors.email" class="text-red-400 text-sm mt-1">{{ errors.email }}</div>
        </div>

        <div class="relative">
          <input
            v-model="form.password"
            type="password"
            placeholder=" "
            class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-100"
          />
          <label
            class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
          >
            Password (leave blank to keep current)
          </label>
          <div v-if="errors.password" class="text-red-400 text-sm mt-1">{{ errors.password }}</div>
        </div>

        <div class="relative group">
          <select
            v-model="form.status"
            class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-100"
            required
          >
            <option value="active">Active</option>
            <option value="deactivated">Deactivated</option>
          </select>
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              (form.status
                ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
              'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
            ]"
          >
            Status *
          </label>
          <div v-if="errors.status" class="text-red-400 text-sm mt-1">{{ errors.status }}</div>
        </div>

        <div class="relative group">
          <SearchableSelect
            v-model="form.department_id"
            :options="departments"
            option-value="id"
            option-label="name"
            placeholder=" "
            empty-label="Select Department"
            input-class="w-full pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100"
            button-class="border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
            @update:modelValue="onDepartmentChange"
          />
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              (form.department_id
                ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
              'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
            ]"
          >
            Department
          </label>
          <div v-if="errors.department_id" class="text-red-400 text-sm mt-1">{{ errors.department_id }}</div>
        </div>

        <div class="relative group">
          <SearchableSelect
            v-model="form.position_id"
            :options="filteredPositions"
            option-value="id"
            option-label="name"
            placeholder=" "
            empty-label="Select Position"
            input-class="w-full pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100"
            button-class="border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
          />
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              (form.position_id
                ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
              'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
            ]"
          >
            Position
          </label>
          <div v-if="errors.position_id" class="text-red-400 text-sm mt-1">{{ errors.position_id }}</div>
        </div>

        <div class="pt-2">
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.is_admin" type="checkbox" class="w-4 h-4 rounded bg-slate-700 text-indigo-600" />
            <span class="text-sm text-slate-300">Is Admin</span>
          </label>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="submit" class="bg-indigo-600 px-4 py-2 rounded text-white hover:bg-indigo-700">
            Update User
          </button>
          <Link href="/control-panel/user" class="px-4 py-2 rounded bg-slate-700 text-slate-300 hover:bg-slate-600">
            Cancel
          </Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
  user: Object,
  departments: Array,
  positions: Array,
  errors: Object,
});

const userData = props.user || {};
const departments = props.departments || [];
const positions = props.positions || [];

const form = reactive({
  name: userData.name || '',
  account: userData.account || '',
  email: userData.email || '',
  password: '',
  status: userData.status || 'active',
  department_id: userData.department_id || userData.department?.id || '',
  position_id: userData.position_id || userData.position?.id || '',
  is_admin: userData.is_admin || false,
});

const filteredPositions = computed(() => {
  if (!form.department_id) return positions;
  return positions.filter(p => p.department_id === form.department_id);
});

function onDepartmentChange() {
  form.position_id = '';
}

function submit() {
  router.put(`/control-panel/user/${userData.id}`, form, {
    onError: (errors) => {
    },
  });
}
</script>
