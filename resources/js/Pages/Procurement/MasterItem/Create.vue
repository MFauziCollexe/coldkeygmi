<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mx-auto max-w-4xl space-y-4">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h2 class="text-2xl font-bold">Create Master Item</h2>
            <p class="text-sm text-slate-400">Simpan detail barang agar bisa dipakai berulang di PR.</p>
          </div>
          <Link href="/master-data/master-item" class="text-sm text-indigo-400">Back to list</Link>
        </div>

        <form @submit.prevent="submit" class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
          <MasterItemForm :form="form" :item-type-options="itemTypeOptions" :unit-options="unitOptions" />
          <div class="flex flex-col-reverse gap-3 border-t border-slate-700 pt-4 sm:flex-row sm:justify-end">
            <Link href="/master-data/master-item" class="rounded bg-slate-700 px-4 py-2 text-center text-white hover:bg-slate-600">Cancel</Link>
            <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700" :disabled="form.processing">
              {{ form.processing ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import MasterItemForm from './Partials/MasterItemForm.vue';

defineProps({
  itemTypeOptions: { type: Array, default: () => [] },
  unitOptions: { type: Array, default: () => [] },
});

const form = useForm({
  item_code: '',
  item_name: '',
  description_of_goods: '',
  item_type: '',
  unit: '',
  default_price: '',
  is_active: true,
});

function submit() {
  form.post('/master-data/master-item');
}
</script>
