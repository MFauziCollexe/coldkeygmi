<template>
  <AppLayout>
    <div class="p-6">
      <h2 class="text-2xl font-bold mb-4">Edit Position</h2>

      <div class="bg-slate-800 rounded p-6 max-w-2xl">
        <form @submit.prevent="submit">
          <div class="mb-4">
            <label class="block text-slate-400 mb-2">Code</label>
            <input v-model="form.code" type="text" class="w-full px-3 py-2 rounded bg-slate-700 text-white" required />
            <div v-if="errors.code" class="text-red-400 text-sm mt-1">{{ errors.code }}</div>
          </div>

          <div class="mb-4">
            <label class="block text-slate-400 mb-2">Name</label>
            <input v-model="form.name" type="text" class="w-full px-3 py-2 rounded bg-slate-700 text-white" required />
            <div v-if="errors.name" class="text-red-400 text-sm mt-1">{{ errors.name }}</div>
          </div>

          <div class="mb-4">
            <label class="block text-slate-400 mb-2">Department</label>
            <SearchableSelect
              v-model="form.department_id"
              :options="departments"
              option-value="id"
              option-label="name"
              placeholder="Select Department"
              empty-label="Select Department"
              input-class="bg-slate-700 text-white"
              required
            />
            <div v-if="errors.department_id" class="text-red-400 text-sm mt-1">{{ errors.department_id }}</div>
          </div>

          <div class="mb-4">
            <label class="block text-slate-400 mb-2">Description</label>
            <textarea v-model="form.description" class="w-full px-3 py-2 rounded bg-slate-700 text-white" rows="3"></textarea>
          </div>

          <div class="mb-4">
            <label class="flex items-center text-slate-400">
              <input v-model="form.is_active" type="checkbox" class="mr-2" />
              Active
            </label>
          </div>

          <div class="mb-4">
            <label class="flex items-center text-slate-400">
              <input v-model="form.is_manager" type="checkbox" class="mr-2" />
              Is Manager (can manage department tickets)
            </label>
          </div>

          <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 px-4 py-2 rounded text-white">Update</button>
            <Link href="/master-data/position" class="bg-slate-600 px-4 py-2 rounded text-white">Cancel</Link>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({ position: Object, departments: Array, errors: Object });

const form = reactive({
  code: props.position.code,
  name: props.position.name,
  department_id: props.position.department_id,
  description: props.position.description || '',
  is_active: props.position.is_active,
  is_manager: props.position.is_manager || false,
});

function submit() {
  Inertia.put(`/master-data/position/${props.position.id}`, form);
}
</script>
