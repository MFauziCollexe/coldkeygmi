<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <h2 class="mb-4 text-2xl font-bold">Edit Area Absensi</h2>

      <div class="max-w-2xl rounded bg-slate-800 p-4 md:p-6">
        <form @submit.prevent="submit">
          <div class="mb-4">
            <label class="mb-2 block text-slate-400">Nama Area</label>
            <input v-model="form.name" type="text" class="w-full rounded bg-slate-700 px-3 py-2 text-white" required />
            <div v-if="errors.name" class="mt-1 text-sm text-red-400">{{ errors.name }}</div>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-2 block text-slate-400">Latitude</label>
              <input v-model="form.latitude" type="number" step="0.0000001" class="w-full rounded bg-slate-700 px-3 py-2 text-white" required />
              <div v-if="errors.latitude" class="mt-1 text-sm text-red-400">{{ errors.latitude }}</div>
            </div>
            <div>
              <label class="mb-2 block text-slate-400">Longitude</label>
              <input v-model="form.longitude" type="number" step="0.0000001" class="w-full rounded bg-slate-700 px-3 py-2 text-white" required />
              <div v-if="errors.longitude" class="mt-1 text-sm text-red-400">{{ errors.longitude }}</div>
            </div>
          </div>

          <div class="mt-4">
            <label class="mb-2 block text-slate-400">Radius (meter)</label>
            <input v-model="form.radius_meters" type="number" min="10" class="w-full rounded bg-slate-700 px-3 py-2 text-white" required />
            <div v-if="errors.radius_meters" class="mt-1 text-sm text-red-400">{{ errors.radius_meters }}</div>
          </div>

          <div class="mt-4">
            <label class="mb-2 block text-slate-400">Description</label>
            <textarea v-model="form.description" rows="3" class="w-full rounded bg-slate-700 px-3 py-2 text-white"></textarea>
          </div>

          <div class="mb-4 mt-4">
            <label class="flex items-center text-slate-400">
              <input v-model="form.is_active" type="checkbox" class="mr-2" />
              Active
            </label>
          </div>

          <div class="flex flex-col-reverse gap-2 sm:flex-row">
            <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-white">Update</button>
            <Link href="/master-data/attendance-lock-area" class="rounded bg-slate-600 px-4 py-2 text-white">Cancel</Link>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  area: Object,
  errors: Object,
});

const form = reactive({
  name: props.area.name,
  latitude: props.area.latitude,
  longitude: props.area.longitude,
  radius_meters: props.area.radius_meters,
  description: props.area.description || '',
  is_active: props.area.is_active,
});

function submit() {
  router.put(`/master-data/attendance-lock-area/${props.area.id}`, form);
}
</script>
