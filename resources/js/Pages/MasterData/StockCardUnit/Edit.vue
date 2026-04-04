<template>
  <AppLayout>
    <div class="max-w-3xl p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-bold">Edit Satuan Stock Card</h2>
        <Link href="/master-data/stock-card-unit" class="text-indigo-400">Back to list</Link>
      </div>

      <form class="space-y-4 rounded bg-slate-800 p-4 md:p-6" @submit.prevent="submit">
        <div>
          <label class="mb-1 block text-sm">Nama Satuan</label>
          <input v-model="form.name" type="text" class="w-full rounded border border-slate-700 bg-slate-900 px-3 py-2" />
          <div v-if="form.errors.name" class="mt-1 text-sm text-red-400">{{ form.errors.name }}</div>
        </div>

        <label class="inline-flex items-center gap-2">
          <input v-model="form.is_active" type="checkbox" />
          <span>Aktif</span>
        </label>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-700 pt-4 sm:flex-row sm:justify-end">
          <Link href="/master-data/stock-card-unit" class="rounded bg-slate-700 px-4 py-2 text-white hover:bg-slate-600">Cancel</Link>
          <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700" :disabled="form.processing">Save Changes</button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  unit: Object,
});

const unit = props.unit || {};

const form = useForm({
  name: unit.name || '',
  is_active: unit.is_active !== false,
});

function submit() {
  form.put(`/master-data/stock-card-unit/${unit.id}`);
}
</script>
