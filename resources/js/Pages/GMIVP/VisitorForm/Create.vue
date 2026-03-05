<template>
  <AppLayout>
    <div class="p-6 max-w-2xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Create Visitor Form</h2>
        <Link href="/gmi-visitor-permit/visitor-form" class="text-indigo-400 hover:underline text-sm">
          Back to List
        </Link>
      </div>

      <form class="space-y-4 bg-slate-800 p-4 rounded" @submit.prevent="submit">
        <div class="grid grid-cols-1 gap-4">
          <div class="relative group">
            <EnhancedDatePicker v-model="form.visit_date" placeholder=" " input-class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label :class="['pointer-events-none absolute left-3 z-10 transition-all', form.visit_date ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2' : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2','group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2']">Tanggal</label>
          </div>
          <div class="relative">
            <input v-model="form.visitor_name" placeholder=" " class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">Nama</label>
          </div>
          <div class="relative">
            <input v-model="form.from" placeholder=" " class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">From</label>
          </div>
          <div class="relative">
            <input v-model="form.identity_no" placeholder=" " class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">Tanda Pengenal</label>
          </div>
          <div class="relative">
            <select v-model="form.host_user_id" class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-100">
              <option value="">Pilih User</option>
              <option v-for="user in hostUsers" :key="user.id" :value="String(user.id)">{{ user.name }}</option>
            </select>
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2">Menemui</label>
          </div>
        </div>
        <div class="relative">
            <input v-model="form.purpose" placeholder=" " class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">Keperluan</label>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="relative">
            <input v-model="form.appointment_time" type="time" class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2">Waktu Perjanjian</label>
          </div>
          <div class="relative">
            <input v-model="form.check_out" type="time" class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2">Waktu Keluar</label>
          </div>
        </div>

        <div>
          <label class="block text-sm text-slate-300">Image</label>
          <div
            class="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition"
            :class="dragActive ? 'border-indigo-500 bg-slate-700/40' : 'border-slate-600'"
            @click="clickFileInput"
            @dragover.prevent="onDragOver"
            @dragleave.prevent="onDragLeave"
            @drop.prevent="onDrop"
          >
            <p class="text-slate-300 font-medium mb-2">Drag & drop image di sini (multi file)</p>
            <p class="text-indigo-300 text-sm">atau klik untuk pilih beberapa file</p>
            <input
              ref="fileInput"
              type="file"
              multiple
              accept="image/*"
              @change="handleFileUpload"
              class="hidden"
            />
          </div>

          <div v-if="form.attachments && form.attachments.length > 0" class="mt-2">
            <div v-for="(file, idx) in form.attachments" :key="idx" class="flex items-center justify-between bg-slate-900 p-2 rounded mt-1">
              <span class="text-sm">{{ file.name }}</span>
              <button type="button" @click="removeFile(idx)" class="text-red-400">Remove</button>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2">
          <Link href="/gmi-visitor-permit/visitor-form" class="bg-slate-600 px-4 py-2 rounded text-white">Cancel</Link>
          <button type="submit" class="bg-indigo-600 px-4 py-2 rounded text-white" :disabled="form.processing">Simpan Visitor</button>
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

defineProps({
  hostUsers: {
    type: Array,
    default: () => [],
  },
});

const form = useForm({
  visitor_name: '',
  from: '',
  identity_no: '',
  purpose: '',
  host_user_id: '',
  visit_date: '',
  appointment_time: '',
  check_out: '',
  attachments: [],
});

const fileInput = ref(null);
const dragActive = ref(false);

function clickFileInput() {
  if (fileInput.value) fileInput.value.click();
}

function handleFileUpload(e) {
  const files = Array.from(e.target.files || []);
  form.attachments = [...form.attachments, ...files];
  e.target.value = '';
}

function onDragOver() {
  dragActive.value = true;
}

function onDragLeave() {
  dragActive.value = false;
}

function onDrop(e) {
  dragActive.value = false;
  const files = Array.from(e.dataTransfer?.files || []);
  form.attachments = [...form.attachments, ...files];
}

function removeFile(idx) {
  form.attachments.splice(idx, 1);
}

function submit() {
  form.post('/gmi-visitor-permit/visitor-form', {
    preserveScroll: true,
  });
}
</script>
