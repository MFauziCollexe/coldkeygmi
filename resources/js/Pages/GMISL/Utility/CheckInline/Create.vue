<template>
  <AppLayout>
    <div class="p-6 max-w-4xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Create Check Inline</h2>
        <Link href="/check-inline" class="text-indigo-400">Back to list</Link>
      </div>

      <form @submit.prevent="submit" class="bg-slate-800 rounded p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="relative">
            <input
              v-model="form.customer"
              type="text"
              placeholder=" "
              class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
            />
            <label
              class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
            >
              Customer
            </label>
            <div v-if="form.errors.customer" class="text-red-400 text-sm mt-1">{{ form.errors.customer }}</div>
          </div>
          <div class="relative">
            <input
              v-model="form.po"
              type="text"
              placeholder=" "
              class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
            />
            <label
              class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
            >
              PO
            </label>
            <div v-if="form.errors.po" class="text-red-400 text-sm mt-1">{{ form.errors.po }}</div>
          </div>
          <div class="relative">
            <input
              v-model="form.qty"
              type="number"
              step="0.01"
              min="0"
              placeholder=" "
              class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
            />
            <label
              class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
            >
              Qty
            </label>
            <div v-if="form.errors.qty" class="text-red-400 text-sm mt-1">{{ form.errors.qty }}</div>
          </div>
          <div class="relative group">
            <EnhancedDatePicker
              v-model="form.exp"
              placeholder=" "
              input-class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
            />
            <label
              :class="[
                'pointer-events-none absolute left-3 z-10 transition-all',
                (form.exp
                  ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                  : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
              ]"
            >
              Exp
            </label>
            <div v-if="form.errors.exp" class="text-red-400 text-sm mt-1">{{ form.errors.exp }}</div>
          </div>
          <div class="relative">
            <input
              v-model="form.batch"
              type="text"
              placeholder=" "
              class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
            />
            <label
              class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
            >
              Batch
            </label>
            <div v-if="form.errors.batch" class="text-red-400 text-sm mt-1">{{ form.errors.batch }}</div>
          </div>
          <div class="relative group">
            <EnhancedDatePicker
              v-model="form.date"
              placeholder=" "
              input-class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
            />
            <label
              :class="[
                'pointer-events-none absolute left-3 z-10 transition-all',
                (form.date
                  ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                  : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
              ]"
            >
              Date
            </label>
            <div v-if="form.errors.date" class="text-red-400 text-sm mt-1">{{ form.errors.date }}</div>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm mb-1">Image</label>
            <div
              class="border-2 border-dashed rounded-lg p-5 text-center cursor-pointer transition"
              :class="dragActive ? 'border-indigo-500 bg-slate-700/40' : 'border-slate-600'"
              @click="openFileDialog"
              @dragover.prevent="onDragOver"
              @dragleave.prevent="onDragLeave"
              @drop.prevent="onDrop"
            >
              <p class="text-slate-300 text-sm">Drag & drop image di sini (multi file)</p>
              <p class="text-slate-500 text-xs mt-1">atau klik untuk pilih beberapa file</p>
              <div v-if="selectedFileNames.length" class="mt-2 text-indigo-300 text-xs space-y-1">
                <p v-for="name in selectedFileNames" :key="name">{{ name }}</p>
              </div>
            </div>
            <input ref="imageInput" type="file" accept="image/*" multiple @change="onFileChange" class="hidden" />
            <div v-if="form.errors.images" class="text-red-400 text-sm mt-1">{{ form.errors.images }}</div>
            <div v-if="form.errors['images.0']" class="text-red-400 text-sm mt-1">{{ form.errors['images.0'] }}</div>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-700 flex justify-end gap-3">
          <Link href="/check-inline" class="px-4 py-2 rounded bg-slate-700 text-white hover:bg-slate-600">
            Cancel
          </Link>
          <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700" :disabled="form.processing">
            Save
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';

const imageInput = ref(null);
const dragActive = ref(false);
const selectedFileNames = ref([]);

const form = useForm({
  customer: '',
  po: '',
  qty: '',
  exp: '',
  batch: '',
  date: '',
  images: [],
});

function onFileChange(event) {
  const files = Array.from(event.target.files || []);
  setImages(files);
}

function openFileDialog() {
  if (imageInput.value) imageInput.value.click();
}

function onDragOver() {
  dragActive.value = true;
}

function onDragLeave() {
  dragActive.value = false;
}

function onDrop(event) {
  dragActive.value = false;
  const files = Array.from(event.dataTransfer?.files || []);
  setImages(files);
}

function setImages(files) {
  form.images = files;
  selectedFileNames.value = files.map(file => file.name);
}

function submit() {
  form.post('/check-inline');
}
</script>
