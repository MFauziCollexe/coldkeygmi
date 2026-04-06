<template>
  <AppLayout>
    <div class="max-w-2xl p-4 sm:p-6">
      <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Edit User</h2>
          <p class="mt-1 text-sm text-slate-400 sm:hidden">Perbarui data akun user dari Control Panel.</p>
        </div>
        <Link href="/control-panel/user" class="text-sm text-indigo-400 hover:underline">Back to List</Link>
      </div>

      <form @submit.prevent="submit" class="space-y-4 rounded bg-slate-800 p-4">
        <div class="relative">
          <input
            v-model="form.name"
            placeholder=" "
            class="peer w-full rounded-lg border border-slate-700 bg-slate-800 px-3 pb-2 pt-5 text-slate-100"
            required
          />
          <label
            class="pointer-events-none absolute left-3 top-0 z-10 -translate-y-1/2 bg-slate-800 px-1 text-xs text-slate-300 transition-all peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:bg-slate-800 peer-focus:px-1 peer-focus:text-xs peer-focus:text-slate-200"
          >
            Name *
          </label>
          <div v-if="errors.name" class="mt-1 text-sm text-red-400">{{ errors.name }}</div>
        </div>

        <div class="relative">
          <input
            v-model="form.account"
            placeholder=" "
            class="peer w-full rounded-lg border border-slate-700 bg-slate-800 px-3 pb-2 pt-5 text-slate-100"
            required
          />
          <label
            class="pointer-events-none absolute left-3 top-0 z-10 -translate-y-1/2 bg-slate-800 px-1 text-xs text-slate-300 transition-all peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:bg-slate-800 peer-focus:px-1 peer-focus:text-xs peer-focus:text-slate-200"
          >
            Account *
          </label>
          <div v-if="errors.account" class="mt-1 text-sm text-red-400">{{ errors.account }}</div>
        </div>

        <div class="relative">
          <input
            v-model="form.email"
            type="email"
            placeholder=" "
            class="peer w-full rounded-lg border border-slate-700 bg-slate-800 px-3 pb-2 pt-5 text-slate-100"
            required
          />
          <label
            class="pointer-events-none absolute left-3 top-0 z-10 -translate-y-1/2 bg-slate-800 px-1 text-xs text-slate-300 transition-all peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:bg-slate-800 peer-focus:px-1 peer-focus:text-xs peer-focus:text-slate-200"
          >
            Email *
          </label>
          <div v-if="errors.email" class="mt-1 text-sm text-red-400">{{ errors.email }}</div>
        </div>

        <div class="relative">
          <input
            v-model="form.password"
            type="password"
            placeholder=" "
            class="peer w-full rounded-lg border border-slate-700 bg-slate-800 px-3 pb-2 pt-5 text-slate-100"
          />
          <label
            class="pointer-events-none absolute left-3 top-0 z-10 -translate-y-1/2 bg-slate-800 px-1 text-xs text-slate-300 transition-all peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:bg-slate-800 peer-focus:px-1 peer-focus:text-xs peer-focus:text-slate-200"
          >
            Password (leave blank to keep current)
          </label>
          <div v-if="errors.password" class="mt-1 text-sm text-red-400">{{ errors.password }}</div>
        </div>

        <div class="relative group">
          <select
            v-model="form.status"
            class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 pb-2 pt-5 text-slate-100"
            required
          >
            <option value="active">Active</option>
            <option value="deactivated">Deactivated</option>
          </select>
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              (form.status
                ? 'top-0 -translate-y-1/2 bg-slate-800 px-1 text-xs text-slate-300'
                : 'top-1/2 -translate-y-1/2 bg-transparent px-0 text-base text-slate-400'),
              'group-focus-within:top-0 group-focus-within:-translate-y-1/2 group-focus-within:bg-slate-800 group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200',
            ]"
          >
            Status *
          </label>
          <div v-if="errors.status" class="mt-1 text-sm text-red-400">{{ errors.status }}</div>
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
                ? 'top-0 -translate-y-1/2 bg-slate-800 px-1 text-xs text-slate-300'
                : 'top-1/2 -translate-y-1/2 bg-transparent px-0 text-base text-slate-400'),
              'group-focus-within:top-0 group-focus-within:-translate-y-1/2 group-focus-within:bg-slate-800 group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200',
            ]"
          >
            Department
          </label>
          <div v-if="errors.department_id" class="mt-1 text-sm text-red-400">{{ errors.department_id }}</div>
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
                ? 'top-0 -translate-y-1/2 bg-slate-800 px-1 text-xs text-slate-300'
                : 'top-1/2 -translate-y-1/2 bg-transparent px-0 text-base text-slate-400'),
              'group-focus-within:top-0 group-focus-within:-translate-y-1/2 group-focus-within:bg-slate-800 group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200',
            ]"
          >
            Position
          </label>
          <div v-if="errors.position_id" class="mt-1 text-sm text-red-400">{{ errors.position_id }}</div>
        </div>

        <div class="pt-2">
          <label class="flex cursor-pointer items-center gap-2">
            <input v-model="form.is_admin" type="checkbox" class="h-4 w-4 rounded bg-slate-700 text-indigo-600" />
            <span class="text-sm text-slate-300">Is Admin</span>
          </label>
        </div>

        <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
          <Link href="/control-panel/user" class="rounded bg-slate-700 px-4 py-2 text-center text-slate-300 hover:bg-slate-600">
            Cancel
          </Link>
          <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
            Update User
          </button>
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
    onError: () => {
    },
  });
}
</script>
