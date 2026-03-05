<template>
  <AppLayout>
    <div class="p-6 max-w-6xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Create Customer</h2>
        <Link href="/master-data/customer" class="text-indigo-400">Back to list</Link>
      </div>

      <form @submit.prevent="submit" class="bg-slate-800 rounded p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-sm mb-1">Type</label>
            <div class="flex gap-4">
              <label class="inline-flex items-center gap-2">
                <input v-model="form.customer_type" type="radio" value="individual" />
                <span>Individual</span>
              </label>
              <label class="inline-flex items-center gap-2">
                <input v-model="form.customer_type" type="radio" value="company" />
                <span>Company</span>
              </label>
            </div>
            <div v-if="form.errors.customer_type" class="text-red-400 text-sm mt-1">{{ form.errors.customer_type }}</div>
          </div>

          <div>
            <label class="block text-sm mb-1">Customer Name</label>
            <input v-model="form.name" type="text" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
            <div v-if="form.errors.name" class="text-red-400 text-sm mt-1">{{ form.errors.name }}</div>
          </div>

          <div>
            <label class="block text-sm mb-1">Logo</label>
            <input type="file" accept="image/*" @change="onFileChange" class="w-full text-sm" />
            <div v-if="form.errors.logo_image" class="text-red-400 text-sm mt-1">{{ form.errors.logo_image }}</div>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm mb-1">Address Line</label>
            <textarea v-model="form.address_line_1" rows="3" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700"></textarea>
          </div>

          <div>
            <label class="block text-sm mb-1">Citty</label>
            <input v-model="form.city" type="text" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
          </div>
          <div>
            <label class="block text-sm mb-1">State</label>
            <input v-model="form.state" type="text" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
          </div>
          <div>
            <label class="block text-sm mb-1">Country</label>
            <input v-model="form.country" type="text" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
          </div>
          <div>
            <label class="block text-sm mb-1">ZIP</label>
            <input v-model="form.zip" type="text" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
          </div>
          <div>
            <label class="block text-sm mb-1">Phone</label>
            <input v-model="form.phone" type="text" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
          </div>
          <div>
            <label class="block text-sm mb-1">Email</label>
            <input v-model="form.email" type="email" class="w-full px-3 py-2 rounded bg-slate-900 border border-slate-700" />
            <div v-if="form.errors.email" class="text-red-400 text-sm mt-1">{{ form.errors.email }}</div>
          </div>
          <div class="flex items-end gap-4">
            <label class="inline-flex items-center gap-2">
              <input v-model="form.is_active" type="checkbox" />
              <span>Active</span>
            </label>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-700 flex justify-end gap-3">
          <Link href="/master-data/customer" class="px-4 py-2 rounded bg-slate-700 text-white hover:bg-slate-600">Cancel</Link>
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
  customer_type: 'company',
  name: '',
  address_line_1: '',
  city: '',
  state: '',
  country: '',
  zip: '',
  phone: '',
  email: '',
  is_active: true,
  logo_image: null,
});

function onFileChange(event) {
  form.logo_image = event.target.files?.[0] || null;
}

function submit() {
  form.post('/master-data/customer', { forceFormData: true });
}
</script>
