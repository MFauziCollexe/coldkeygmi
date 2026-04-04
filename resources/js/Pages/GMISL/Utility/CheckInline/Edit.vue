<template>
  <AppLayout>
    <div class="mx-auto max-w-4xl p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <h2 class="text-2xl font-bold">Edit Check Inline</h2>
        <Link href="/check-inline" class="text-indigo-400">Back to list</Link>
      </div>

      <form @submit.prevent="submit" class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
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
              class="cursor-pointer rounded-lg border-2 border-dashed p-4 text-center transition md:p-5"
              :class="dragActive ? 'border-indigo-500 bg-slate-700/40' : 'border-slate-600'"
              @click="openFileDialog"
              @dragover.prevent="onDragOver"
              @dragleave.prevent="onDragLeave"
              @drop.prevent="onDrop"
            >
              <p class="text-slate-300 text-sm">Drag & drop image di sini (multi file)</p>
              <p class="text-slate-500 text-xs mt-1">atau klik untuk pilih beberapa file</p>
            </div>
            <input ref="imageInput" type="file" accept="image/*" multiple @change="onFileChange" class="hidden" />
            <div v-if="form.errors.images" class="text-red-400 text-sm mt-1">{{ form.errors.images }}</div>
            <div v-if="form.errors['images.0']" class="text-red-400 text-sm mt-1">{{ form.errors['images.0'] }}</div>

            <div class="mt-4">
              <div class="text-sm text-slate-300 mb-2">Existing Images</div>
              <div v-if="existingImageItems.length" class="grid grid-cols-2 gap-3 md:grid-cols-3">
                <div
                  v-for="img in existingImageItems"
                  :key="img.localKey"
                  class="bg-slate-900 p-2 rounded cursor-pointer hover:bg-slate-800 relative"
                  @click="openImage(img.url)"
                >
                  <img :src="img.url" alt="Existing image" class="w-full h-24 object-cover rounded" />
                  <p class="text-xs text-slate-400 mt-1 truncate">{{ getFileNameFromUrl(img.url) }}</p>
                  <button
                    type="button"
                    @click.stop="removeExistingImage(img.localKey)"
                    class="absolute top-1 right-1 px-2 py-1 rounded bg-red-600 text-white text-xs"
                    title="Delete image"
                  >
                    Delete
                  </button>
                </div>
              </div>
              <div v-else class="text-slate-500 text-sm">No existing images.</div>
            </div>

            <div class="mt-4">
              <div class="text-sm text-slate-300 mb-2">New Images</div>
              <div v-if="newImageItems.length" class="grid grid-cols-2 gap-3 md:grid-cols-3">
                <div
                  v-for="img in newImageItems"
                  :key="img.localKey"
                  class="bg-slate-900 p-2 rounded cursor-pointer hover:bg-slate-800 relative"
                  @click="openImage(img.url)"
                >
                  <img :src="img.url" alt="New image" class="w-full h-24 object-cover rounded" />
                  <p class="text-xs text-slate-400 mt-1 truncate">{{ img.file?.name || 'image' }}</p>
                  <button
                    type="button"
                    @click.stop="removeNewImage(img.localKey)"
                    class="absolute top-1 right-1 px-2 py-1 rounded bg-red-600 text-white text-xs"
                    title="Delete image"
                  >
                    Delete
                  </button>
                </div>
              </div>
              <div v-else class="text-slate-500 text-sm">No new images selected.</div>
            </div>
          </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-700 pt-4 sm:flex-row sm:justify-end">
          <Link href="/check-inline" class="rounded bg-slate-700 px-4 py-2 text-center text-white hover:bg-slate-600">
            Cancel
          </Link>
          <button
            type="submit"
            class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
            :disabled="form.processing"
          >
            Save Changes
          </button>
        </div>
      </form>

      <div
        v-if="showImageModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
        @click="closeImage"
      >
        <div class="max-h-[90vh] max-w-5xl" @click.stop>
          <img :src="selectedImage" alt="Preview" class="max-h-[90vh] w-auto rounded" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';

const props = defineProps({
  row: Object,
  existingImages: Array,
});

const imageInput = ref(null);
const dragActive = ref(false);
const showImageModal = ref(false);
const selectedImage = ref('');

const existingImageItems = ref(
  (props.existingImages || []).map((img, idx) => ({
    id: img.id,
    url: img.url,
    localKey: `existing-${img.id ?? idx}`,
  })),
);
const newImageItems = ref([]);

const form = useForm({
  customer: props.row?.customer || '',
  po: props.row?.po || '',
  qty: props.row?.qty ?? '',
  exp: toInputDate(props.row?.exp),
  batch: props.row?.batch || '',
  date: toInputDate(props.row?.date),
  images: [],
  keep_existing_image_ids: existingImageItems.value.map(img => img.id).filter(Boolean),
});

function toInputDate(value) {
  if (!value) return '';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return '';
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${d.getFullYear()}-${mm}-${dd}`;
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
  appendNewImages(files);
}

function onFileChange(event) {
  const files = Array.from(event.target.files || []);
  appendNewImages(files);
  event.target.value = '';
}

function appendNewImages(files) {
  if (!files.length) return;

  const mapped = files.map((file, idx) => ({
    file,
    url: URL.createObjectURL(file),
    localKey: `new-${Date.now()}-${idx}`,
  }));

  newImageItems.value = [...newImageItems.value, ...mapped];
  syncFormImages();
}

async function removeExistingImage(localKey) {
  const result = await Swal.fire({
    title: 'Delete image?',
    text: 'Gambar ini akan dihapus dari data.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel',
  });

  if (!result.isConfirmed) return;

  existingImageItems.value = existingImageItems.value.filter(img => img.localKey !== localKey);
  syncKeepExisting();
}

async function removeNewImage(localKey) {
  const result = await Swal.fire({
    title: 'Delete image?',
    text: 'Gambar baru ini akan dihapus dari daftar upload.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel',
  });

  if (!result.isConfirmed) return;

  const target = newImageItems.value.find(img => img.localKey === localKey);
  if (target?.url) URL.revokeObjectURL(target.url);
  newImageItems.value = newImageItems.value.filter(img => img.localKey !== localKey);
  syncFormImages();
}

function syncKeepExisting() {
  form.keep_existing_image_ids = existingImageItems.value.map(img => img.id).filter(Boolean);
}

function syncFormImages() {
  form.images = newImageItems.value.map(img => img.file);
}

function submit() {
  syncKeepExisting();
  syncFormImages();
  form.transform(data => ({ ...data, _method: 'put' })).post(`/check-inline/${props.row.id}`, {
    forceFormData: true,
  });
}

function openImage(url) {
  selectedImage.value = url;
  showImageModal.value = true;
}

function closeImage() {
  showImageModal.value = false;
  selectedImage.value = '';
}

function getFileNameFromUrl(url) {
  try {
    return decodeURIComponent(url.split('/').pop() || 'image');
  } catch {
    return 'image';
  }
}
</script>
