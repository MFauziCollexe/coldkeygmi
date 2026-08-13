<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-bold">RCS</h2>
        <button
          type="button"
          @click="openModal"
          class="inline-flex items-center justify-center rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
        >
          Add RCS
        </button>
      </div>

      <div class="rounded-md border border-slate-200 bg-white p-10 text-center text-slate-400 shadow-sm">
        Halaman ini masih kosong.
      </div>
    </div>

    <!-- Modal Add -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
      @click.self="closeModal"
    >
      <div class="w-full max-w-md overflow-hidden rounded-xl border border-slate-300 bg-white p-5 shadow-2xl">
        <div class="mb-4 flex items-center justify-between gap-4">
          <h3 class="text-base font-semibold text-black">Tambah Data RCS</h3>
        </div>

        <form @submit.prevent="saveRecord" class="space-y-4">
          <div v-for="field in formFields" :key="field.key">
            <label class="mb-1 block text-sm font-medium text-slate-700">{{ field.label }}</label>
            <select
              v-if="field.options"
              v-model="form[field.key]"
              required
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option value="" disabled>Pilih {{ field.label }}</option>
              <option v-for="opt in field.options" :key="opt" :value="opt">{{ opt }}</option>
            </select>
            <input
              v-else
              v-model="form[field.key]"
              :type="field.type"
              required
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
          </div>

          <div class="flex items-center justify-end gap-2">
            <button
              type="button"
              class="rounded bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-300"
              @click="closeModal"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
            >
              Save
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const showModal = ref(false);
const formFields = [
  { key: 'po', label: 'PO', type: 'text' },
  { key: 'nopol', label: 'NoPol', type: 'text' },
  { key: 'driver', label: 'Driver', type: 'text' },
  { key: 'customer', label: 'Customer', type: 'text' },
  { key: 'transaksi', label: 'Transaksi', type: 'text', options: ['Inbound', 'Outbound'] },
];
const initialForm = () => ({ po: '', nopol: '', driver: '', customer: '', transaksi: '' });
const form = ref(initialForm());

function openModal() {
  form.value = initialForm();
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

function saveRecord() {
  showModal.value = false;
  form.value = initialForm();
}
</script>
