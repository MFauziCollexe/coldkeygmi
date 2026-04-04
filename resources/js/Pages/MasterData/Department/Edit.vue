<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <h2 class="text-2xl font-bold mb-4">Edit Department</h2>

      <div class="max-w-2xl rounded bg-slate-800 p-4 md:p-6">
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
            <label class="block text-slate-400 mb-2">Description</label>
            <textarea v-model="form.description" class="w-full px-3 py-2 rounded bg-slate-700 text-white" rows="3"></textarea>
          </div>

          <div class="mb-4">
            <label class="flex items-center text-slate-400">
              <input v-model="form.is_active" type="checkbox" class="mr-2" />
              Active
            </label>
          </div>

          <div class="flex flex-col-reverse gap-2 sm:flex-row">
            <button type="submit" class="bg-indigo-600 px-4 py-2 rounded text-white">Update</button>
            <Link href="/master-data/department" class="bg-slate-600 px-4 py-2 rounded text-white">Cancel</Link>
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

const props = defineProps({ department: Object, errors: Object });

const form = reactive({
  code: props.department.code,
  name: props.department.name,
  description: props.department.description || '',
  is_active: props.department.is_active,
});

function submit() {
  router.put(`/master-data/department/${props.department.id}`, form);
}
</script>
