<template>
  <AppLayout>
    <div class="p-6 max-w-2xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Edit Leave & Permission</h2>
        <Link :href="`/leave-permission/${leavePermission.id}`" class="text-indigo-400 hover:underline text-sm">
          Kembali ke detail
        </Link>
      </div>

      <form @submit.prevent="submit" class="space-y-4 bg-slate-800 p-4 rounded">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="rounded-lg border border-slate-700 bg-slate-900/40 px-4 py-3">
            <div class="text-sm text-slate-400">Karyawan</div>
            <div class="mt-1 text-slate-100">{{ leavePermission.employee?.name || leavePermission.user?.name || '-' }}</div>
          </div>
          <div class="rounded-lg border border-slate-700 bg-slate-900/40 px-4 py-3">
            <div class="text-sm text-slate-400">Department</div>
            <div class="mt-1 text-slate-100">{{ leavePermission.employee?.department?.name || leavePermission.user?.department?.name || '-' }}</div>
          </div>
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
            class="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition"
            :class="dragActive ? 'border-indigo-500 bg-slate-700/40' : 'border-slate-600'"
            @click="clickFileInput"
            @dragover.prevent="onDragOver"
            @dragleave.prevent="onDragLeave"
            @drop.prevent="onDrop"
          >
            <p class="text-slate-300 font-medium mb-2">Upload attachment baru</p>
            <p class="text-slate-500 text-sm mb-4">Format: JPG, PNG, WEBP, PDF (max 5MB)</p>
            <p class="text-indigo-300 text-sm">Klik area ini atau drag-and-drop file</p>
            <input
              ref="fileInput"
              type="file"
              accept="image/*,application/pdf,.pdf"
              class="hidden"
              @change="onImageChange"
            />
          </div>
          <div v-if="form.errors.attachment_image" class="text-red-400 text-sm mt-1">
            {{ form.errors.attachment_image }}
          </div>

          <div v-if="currentAttachmentUrl && !form.attachment_image && !form.remove_attachment" class="mt-2 rounded bg-slate-900 p-3">
            <div class="flex items-center justify-between gap-3">
              <button type="button" class="text-indigo-300 hover:text-indigo-200 text-sm" @click="openAttachment">
                Lihat attachment saat ini
              </button>
              <button type="button" class="text-red-400 hover:text-red-300 text-sm" @click="removeCurrentAttachment">
                Hapus attachment
              </button>
            </div>
          </div>

          <div v-if="form.attachment_image" class="mt-2">
            <div class="flex items-center justify-between bg-slate-900 p-2 rounded mt-1">
              <span class="text-sm">{{ form.attachment_image.name }}</span>
              <button type="button" @click="removeNewImage" class="text-red-400">Remove</button>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2">
          <Link :href="`/leave-permission/${leavePermission.id}`" class="px-4 py-2 rounded bg-slate-700 text-slate-300">
            Batal
          </Link>
          <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white" :disabled="form.processing">
            Simpan Perubahan
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

const props = defineProps({
  leavePermission: {
    type: Object,
    required: true,
  },
});

const requestTypeOptions = [
  { value: 'cuti', label: 'Cuti' },
  { value: 'izin', label: 'Izin' },
  { value: 'sakit', label: 'Sakit' },
  { value: 'dinas_luar', label: 'Dinas Luar' },
];

const form = useForm({
  type: props.leavePermission.type || '',
  start_date: normalizeDate(props.leavePermission.start_date),
  end_date: normalizeDate(props.leavePermission.end_date),
  reason: props.leavePermission.reason || '',
  attachment_image: null,
  remove_attachment: false,
});

const fileInput = ref(null);
const dragActive = ref(false);
const currentAttachmentUrl = ref(props.leavePermission.image_url || '');

function normalizeDate(value) {
  if (!value) return '';
  return String(value).slice(0, 10);
}

function submit() {
  form
    .transform((data) => ({
      ...data,
      _method: 'put',
    }))
    .post(`/leave-permission/${props.leavePermission.id}`, {
      forceFormData: true,
      onError: () => {
        const firstError = Object.values(form.errors || {})[0];
        if (firstError) alert(String(firstError));
      },
    });
}

function onImageChange(event) {
  form.attachment_image = event.target.files?.[0] || null;
  if (form.attachment_image) {
    form.remove_attachment = false;
  }
}

function removeNewImage() {
  form.attachment_image = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
}

function removeCurrentAttachment() {
  currentAttachmentUrl.value = '';
  form.remove_attachment = true;
  form.attachment_image = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
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
  const files = Array.from(event.dataTransfer?.files || []);
  form.attachment_image = files.length > 0 ? files[0] : null;
  if (form.attachment_image) {
    form.remove_attachment = false;
  }
}

function openAttachment() {
  const url = String(currentAttachmentUrl.value || '');
  if (!url) return;

  window.open(url, '_blank', 'noopener');
}
</script>
