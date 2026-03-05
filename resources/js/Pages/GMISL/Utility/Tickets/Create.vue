<template>
  <AppLayout>
    <div class="p-6 max-w-2xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Create Ticket</h2>
        <Link href="/tickets" class="text-indigo-400 hover:underline text-sm">← Back to List</Link>
      </div>

      <form @submit.prevent="submit" class="space-y-4 bg-slate-800 p-4 rounded">
        <div class="relative">
          <input
            v-model="form.title"
            placeholder=" "
            class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700"
          />
          <label
            class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
          >
            Title
          </label>
        </div>

        <div class="relative">
          <textarea
            v-model="form.description"
            rows="4"
            placeholder=" "
            class="peer w-full px-3 pt-6 pb-2 rounded-lg bg-slate-800 border border-slate-700"
          ></textarea>
          <label
            class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-4 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
          >
            Description
          </label>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="relative group">
            <EnhancedDatePicker
              v-model="form.deadline"
              placeholder=" "
              input-class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
            />
            <label
              :class="[
                'pointer-events-none absolute left-3 z-10 transition-all',
                (form.deadline
                  ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                  : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
              ]"
            >
              Deadline
            </label>
          </div>
          <div class="relative group">
            <SearchableSelect
              v-model="form.department_id"
              :options="departments"
              option-value="id"
              option-label="name"
              placeholder=" "
              empty-label="Select Department"
              input-class="w-full pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100"
              button-class="border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
            />
            <label
              :class="[
                'pointer-events-none absolute left-3 z-10 transition-all',
                (form.department_id
                  ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                  : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
              ]"
            >
              Assign to Department
            </label>
          </div>
        </div>

        <div>
          <label class="block text-sm text-slate-300">Attachments</label>
          <div
            class="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition"
            :class="dragActive ? 'border-indigo-500 bg-slate-700/40' : 'border-slate-600'"
            @click="clickFileInput"
            @dragover.prevent="onDragOver"
            @dragleave.prevent="onDragLeave"
            @drop.prevent="onDrop"
          >
            <p class="text-slate-300 font-medium mb-2">Upload file attachments</p>
            <p class="text-slate-500 text-sm mb-4">Format: image, PDF, DOC, DOCX, XLS, XLSX</p>
            <p class="text-indigo-300 text-sm">Klik area ini atau drag-and-drop file</p>
            <input
              ref="fileInput"
              type="file"
              multiple
              accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
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

        <div class="flex justify-end">
          <button type="submit" class="bg-indigo-600 px-4 py-2 rounded text-white" :disabled="form.processing">
            Create Ticket
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
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({ departments: Array });

const form = useForm({
  title: '',
  description: '',
  deadline: '',
  department_id: '',
  attachments: [],
});

const fileInput = ref(null);
const dragActive = ref(false);

function clickFileInput() {
  if (fileInput.value) {
    fileInput.value.click();
  }
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
  if (!form.title || !form.description || !form.deadline || !form.department_id) {
    alert('Please fill all required fields');
    return;
  }
  
  form.post('/tickets');
}
</script>
