<template>
  <AppLayout>
    <div class="max-w-2xl p-4 md:p-6">
      <h2 class="text-2xl font-bold mb-4">Ajukan Permintaan Cuti/Izin</h2>

        <form @submit.prevent="submit" class="space-y-4 rounded bg-slate-800 p-4">
          <div v-if="canSelectEmployee" class="relative group">
            <SearchableSelect
            v-model="form.employee_id"
            :options="employees"
            option-value="id"
            option-label="label"
            placeholder=" "
            empty-label="Pilih Karyawan"
            input-class="w-full h-[52px] pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100 placeholder-transparent"
            button-class="h-[52px] border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
          />
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              (form.employee_id
                ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
              'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
            ]"
          >
            Karyawan
          </label>
          <div v-if="form.errors.employee_id" class="text-red-400 text-sm mt-1">{{ form.errors.employee_id }}</div>
        </div>

        <div class="relative group">
          <SearchableSelect
            v-model="form.type"
            :options="requestTypeOptions"
            option-value="value"
            option-label="label"
            placeholder=" "
            empty-label="Jenis Permintaan"
            input-class="w-full h-[52px] pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100 placeholder-transparent"
            button-class="h-[52px] border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
          />
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              (form.type
                ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
              'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
            ]"
          >
            Jenis Permintaan
          </label>
          <div v-if="form.errors.type" class="text-red-400 text-sm mt-1">{{ form.errors.type }}</div>
        </div>

        <div class="relative group">
          <EnhancedDatePicker
            v-model="form.start_date"
            placeholder=" "
            input-class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
          />
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              (form.start_date
                ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
              'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
            ]"
          >
            Tanggal Mulai
          </label>
          <div v-if="form.errors.start_date" class="text-red-400 text-sm mt-1">{{ form.errors.start_date }}</div>
        </div>

        <div class="relative group">
          <EnhancedDatePicker
            v-model="form.end_date"
            placeholder=" "
            input-class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
          />
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              (form.end_date
                ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
              'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
            ]"
          >
            Tanggal Selesai
          </label>
          <div v-if="form.errors.end_date" class="text-red-400 text-sm mt-1">{{ form.errors.end_date }}</div>
        </div>

        <div class="relative">
          <textarea
            v-model="form.reason"
            rows="3"
            placeholder=" "
            class="peer w-full px-3 pt-6 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
          ></textarea>
          <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-4 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">
            Alasan
          </label>
          <div v-if="form.errors.reason" class="text-red-400 text-sm mt-1">{{ form.errors.reason }}</div>
        </div>

        <div>
          <label class="block text-sm text-slate-300">Attachment</label>
          <div
            class="cursor-pointer rounded-lg border-2 border-dashed p-5 text-center transition md:p-8"
            :class="dragActive ? 'border-indigo-500 bg-slate-700/40' : 'border-slate-600'"
            @click="clickFileInput"
            @dragover.prevent="onDragOver"
            @dragleave.prevent="onDragLeave"
            @drop.prevent="onDrop"
          >
            <p class="text-slate-300 font-medium mb-2">Upload attachment</p>
            <p class="text-slate-500 text-sm mb-4">Format: JPG, PNG, WEBP, PDF (max 5MB)</p>
            <p class="text-indigo-300 text-sm">Klik area ini atau drag-and-drop beberapa file</p>
            <input
              ref="fileInput"
              type="file"
              accept="image/*,application/pdf,.pdf"
              multiple
              class="hidden"
              @change="onImageChange"
            />
          </div>
          <div v-if="form.errors.attachment_images" class="text-red-400 text-sm mt-1">
            {{ form.errors.attachment_images }}
          </div>
          <div v-if="form.attachment_images.length" class="mt-2 space-y-2">
            <div
              v-for="(file, index) in form.attachment_images"
              :key="`${file.name}-${index}`"
              class="flex items-center justify-between bg-slate-900 p-2 rounded"
            >
              <span class="text-sm truncate pr-3">{{ file.name }}</span>
              <button type="button" @click="removeImage(index)" class="text-red-400">Remove</button>
            </div>
          </div>
        </div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <button type="button" @click="cancel" class="rounded bg-slate-700 px-4 py-2 text-slate-300">
            Batal
          </button>
          <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-white">
            Simpan
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
  employees: { type: Array, default: () => [] },
  canSelectEmployee: { type: Boolean, default: false },
  canSubmitForOthers: { type: Boolean, default: false },
  defaultEmployeeId: { type: [String, Number], default: '' },
});

const requestTypeOptions = [
  { value: 'cuti', label: 'Cuti' },
  { value: 'izin', label: 'Izin' },
  { value: 'sakit', label: 'Sakit' },
  { value: 'dinas_luar', label: 'Dinas Luar' },
];

const form = useForm({
  employee_id: props.canSelectEmployee
    ? (props.canSubmitForOthers ? '' : (props.defaultEmployeeId || ''))
    : '',
  type: '',
  start_date: '',
  end_date: '',
  reason: '',
  attachment_images: [],
});

const fileInput = ref(null);
const dragActive = ref(false);

function submit() {
  if (!form.type || !form.start_date || !form.end_date || !form.reason) {
    alert('Mohon lengkapi semua field');
    return;
  }
  
  form.post('/leave-permission', {
    forceFormData: true,
    onSuccess: () => {
      router.get('/leave-permission');
    },
    onError: () => {
      const firstError = Object.values(form.errors || {})[0];
      if (firstError) alert(String(firstError));
    },
  });
}

function addFiles(fileList) {
  const nextFiles = Array.from(fileList || []);
  if (!nextFiles.length) return;
  form.attachment_images = [...form.attachment_images, ...nextFiles];
}

function onImageChange(event) {
  addFiles(event.target.files);
  if (fileInput.value) {
    fileInput.value.value = '';
  }
}

function removeImage(index) {
  form.attachment_images = form.attachment_images.filter((_, fileIndex) => fileIndex !== index);
}

function clickFileInput() {
  if (fileInput.value) {
    fileInput.value.click();
  }
}

function onDragOver() {
  dragActive.value = true;
}

function onDragLeave() {
  dragActive.value = false;
}

function onDrop(event) {
  dragActive.value = false;
  addFiles(event.dataTransfer?.files || []);
}

function cancel() {
  router.get('/leave-permission');
}
</script>
