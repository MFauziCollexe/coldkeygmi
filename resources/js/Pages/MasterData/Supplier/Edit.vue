<template>
  <AppLayout>
    <div class="max-w-6xl p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-bold">Edit Supplier</h2>
        <Link href="/master-data/supplier" class="text-indigo-400">Back to list</Link>
      </div>

      <form @submit.prevent="submit" class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm">Supplier Name</label>
            <input v-model="form.name" type="text" class="w-full rounded bg-slate-900 px-3 py-2 border border-slate-700" />
            <div v-if="form.errors.name" class="mt-1 text-sm text-red-400">{{ form.errors.name }}</div>
          </div>

          <div>
            <label class="mb-1 block text-sm">Code</label>
            <input v-model="form.code" type="text" class="w-full rounded bg-slate-900 px-3 py-2 border border-slate-700" />
            <div v-if="form.errors.code" class="mt-1 text-sm text-red-400">{{ form.errors.code }}</div>
          </div>

          <div>
            <label class="mb-1 block text-sm">Contact Person</label>
            <input v-model="form.contact_person" type="text" class="w-full rounded bg-slate-900 px-3 py-2 border border-slate-700" />
            <div v-if="form.errors.contact_person" class="mt-1 text-sm text-red-400">{{ form.errors.contact_person }}</div>
          </div>

          <div>
            <label class="mb-1 block text-sm">Phone</label>
            <input v-model="form.phone" type="text" class="w-full rounded bg-slate-900 px-3 py-2 border border-slate-700" />
            <div v-if="form.errors.phone" class="mt-1 text-sm text-red-400">{{ form.errors.phone }}</div>
          </div>

          <div>
            <label class="mb-1 block text-sm">Email</label>
            <input v-model="form.email" type="email" class="w-full rounded bg-slate-900 px-3 py-2 border border-slate-700" />
            <div v-if="form.errors.email" class="mt-1 text-sm text-red-400">{{ form.errors.email }}</div>
          </div>

          <div class="flex items-end gap-4">
            <label class="inline-flex items-center gap-2">
              <input v-model="form.is_active" type="checkbox" />
              <span>Active</span>
            </label>
          </div>

          <div class="md:col-span-2">
            <label class="mb-1 block text-sm">Address</label>
            <textarea v-model="form.address" rows="4" class="w-full rounded bg-slate-900 px-3 py-2 border border-slate-700"></textarea>
            <div v-if="form.errors.address" class="mt-1 text-sm text-red-400">{{ form.errors.address }}</div>
          </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-700 pt-4 sm:flex-row sm:justify-end">
          <Link href="/master-data/supplier" class="rounded bg-slate-700 px-4 py-2 text-white hover:bg-slate-600">Cancel</Link>
          <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700" :disabled="form.processing">Update</button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  supplier: Object,
});

const form = useForm({
  name: props.supplier?.name || '',
  code: props.supplier?.code || '',
  contact_person: props.supplier?.contact_person || '',
  phone: props.supplier?.phone || '',
  email: props.supplier?.email || '',
  address: props.supplier?.address || '',
  is_active: !!props.supplier?.is_active,
});

function submit() {
  form.put(`/master-data/supplier/${props.supplier.id}`);
}
</script>
