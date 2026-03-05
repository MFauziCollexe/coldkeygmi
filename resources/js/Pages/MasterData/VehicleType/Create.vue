<template>
  <AppLayout>
    <div class="p-6 max-w-3xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Tambah Jenis Kendaraan</h2>
        <Link href="/master-data/vehicle-type" class="text-indigo-400">Back to list</Link>
      </div>

      <form @submit.prevent="submit" class="bg-slate-800 rounded p-6 space-y-4">
        <div>
          <label class="block text-sm mb-1">Nama Jenis Kendaraan</label>
          <input v-model="form.name" type="text" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
          <div v-if="form.errors.name" class="text-red-400 text-sm mt-1">{{ form.errors.name }}</div>
        </div>

        <div>
          <label class="inline-flex items-center gap-2">
            <input v-model="form.is_active" type="checkbox" />
            <span>Aktif</span>
          </label>
        </div>

        <div class="pt-4 border-t border-slate-700 flex justify-end gap-3">
          <Link href="/master-data/vehicle-type" class="px-4 py-2 rounded bg-slate-700 text-white hover:bg-slate-600">Cancel</Link>
          <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700" :disabled="form.processing">Save</button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
  name: '',
  is_active: true,
});

function submit() {
  form.post('/master-data/vehicle-type');
}
</script>

