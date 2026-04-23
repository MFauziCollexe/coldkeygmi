<template>
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Merge PDF</h1>
        <p class="mt-1 text-gray-500">Gabungkan beberapa file PDF menjadi satu dokumen.</p>
      </div>

      <div
        v-if="feedback.message"
        :class="[
          'rounded-lg border px-4 py-3 text-sm',
          feedback.type === 'error'
            ? 'border-red-200 bg-red-50 text-red-700'
            : 'border-green-200 bg-green-50 text-green-700'
        ]"
      >
        {{ feedback.message }}
      </div>

      <div class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="space-y-4">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Upload Files</h2>
            <p class="text-sm text-gray-500">Minimal 2 file PDF. Urutan file akan mengikuti daftar di bawah.</p>
          </div>

          <div
            class="rounded-lg border-2 border-dashed border-gray-300 p-8 text-center transition hover:border-gray-400"
            @click="openPicker"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            :class="isDragging ? 'border-blue-500 bg-blue-50' : 'bg-white'"
          >
            <input ref="fileInput" type="file" multiple accept=".pdf,application/pdf" class="hidden" @change="handleSelect" />
            <button type="button" class="text-blue-600 hover:text-blue-700" @click.stop="openPicker">
              Klik untuk pilih file atau drag-and-drop ke sini
            </button>
          </div>

          <div v-if="selectedFiles.length > 0" class="space-y-2">
            <div class="text-sm font-medium text-gray-900">Urutan Merge</div>

            <div class="space-y-2">
              <div
                v-for="(file, index) in selectedFiles"
                :key="`${file.name}-${index}`"
                class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm"
              >
                <div class="min-w-0 flex-1 pr-4">
                  <div
                    class="font-medium text-gray-900 break-all"
                    :title="file.name"
                  >
                    {{ index + 1 }}. {{ file.name }}
                  </div>
                  <div class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</div>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                  <button type="button" class="text-gray-600 hover:text-gray-800" :disabled="index === 0" @click="moveFile(index, -1)">
                    Up
                  </button>
                  <button type="button" class="text-gray-600 hover:text-gray-800" :disabled="index === selectedFiles.length - 1" @click="moveFile(index, 1)">
                    Down
                  </button>
                  <button type="button" class="text-red-600 hover:text-red-700" @click="removeFile(index)">
                    Hapus
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <button
              type="button"
              class="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-400"
              :disabled="selectedFiles.length < 2 || isSubmitting"
              @click="submitMerge"
            >
              {{ isSubmitting ? 'Memproses...' : 'Merge PDF' }}
            </button>
            <button v-if="selectedFiles.length > 0" type="button" class="text-gray-600 hover:text-gray-800" @click="selectedFiles = []">
              Clear
            </button>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-6 py-4">
          <h2 class="text-lg font-semibold text-gray-900">Merge History</h2>
        </div>

        <div v-if="jobs.data.length === 0" class="px-6 py-8 text-center text-gray-500">
          Belum ada job merge PDF.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Files</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Output</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="job in jobs.data" :key="job.id">
                <td class="px-6 py-4 text-sm text-gray-700">
                  <div
                    v-for="name in job.input_filenames"
                    :key="name"
                    class="break-all text-gray-900"
                    :title="name"
                  >
                    {{ name }}
                  </div>
                  <div v-if="job.error_message" class="mt-1 text-xs text-red-600">{{ job.error_message }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  <div>{{ job.output_filename || '-' }}</div>
                  <div class="text-xs text-gray-500">{{ job.output_size ? formatFileSize(job.output_size) : '' }}</div>
                </td>
                <td class="px-6 py-4 text-sm">
                  <span
                    :class="[
                      'inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize',
                      {
                        'bg-green-100 text-green-800': job.status === 'completed',
                        'bg-blue-100 text-blue-800': job.status === 'processing',
                        'bg-yellow-100 text-yellow-800': job.status === 'pending',
                        'bg-red-100 text-red-800': job.status === 'failed'
                      }
                    ]"
                  >
                    {{ job.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">{{ formatDate(job.created_at) }}</td>
                <td class="px-6 py-4 text-sm">
                  <div class="flex items-center gap-3">
                    <button v-if="job.status === 'completed'" type="button" class="text-blue-600 hover:text-blue-700" @click="downloadJob(job)">
                      Download
                    </button>
                    <button type="button" class="text-red-600 hover:text-red-700" @click="deleteJob(job)">
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="jobs.links.length > 2" class="border-t border-gray-200 px-6 py-4">
          <div class="flex flex-wrap items-center justify-center gap-1">
            <Link
              v-for="link in jobs.links"
              :key="link.label"
              :href="link.url || '#'"
              :class="[
                'rounded-lg px-3 py-2 text-sm font-medium',
                link.active ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100',
                !link.url && 'pointer-events-none opacity-50'
              ]"
              preserve-scroll
              v-html="link.label"
            />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import axios from 'axios';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  jobs: { type: Object, required: true },
});

const fileInput = ref(null);
const selectedFiles = ref([]);
const isDragging = ref(false);
const isSubmitting = ref(false);
const feedback = ref({ type: 'success', message: '' });

function openPicker() {
  fileInput.value?.click();
}

function setFeedback(type, message) {
  feedback.value = { type, message };
}

function assignFiles(files) {
  const pdfFiles = files.filter((file) => file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'));
  selectedFiles.value = pdfFiles;

  if (files.length !== pdfFiles.length) {
    setFeedback('error', 'Beberapa file diabaikan karena bukan PDF.');
  }
}

function handleSelect(event) {
  assignFiles(Array.from(event.target.files || []));
  if (fileInput.value) {
    fileInput.value.value = '';
  }
}

function handleDrop(event) {
  isDragging.value = false;
  assignFiles(Array.from(event.dataTransfer?.files || []));
}

function moveFile(index, direction) {
  const newIndex = index + direction;
  if (newIndex < 0 || newIndex >= selectedFiles.value.length) {
    return;
  }

  const nextFiles = [...selectedFiles.value];
  [nextFiles[index], nextFiles[newIndex]] = [nextFiles[newIndex], nextFiles[index]];
  selectedFiles.value = nextFiles;
}

function removeFile(index) {
  selectedFiles.value.splice(index, 1);
}

function formatFileSize(bytes) {
  const numericBytes = Number(bytes || 0);
  if (!numericBytes) return '0 B';
  const units = ['B', 'KB', 'MB', 'GB'];
  const idx = Math.min(Math.floor(Math.log(numericBytes) / Math.log(1024)), units.length - 1);
  const value = numericBytes / (1024 ** idx);
  return `${value.toFixed(value >= 10 || idx === 0 ? 0 : 2)} ${units[idx]}`;
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString('id-ID') : '-';
}

async function refreshPage() {
  router.reload({ only: ['jobs'], preserveState: true, preserveScroll: true });
}

async function submitMerge() {
  isSubmitting.value = true;
  setFeedback('success', '');

  const formData = new FormData();
  selectedFiles.value.forEach((file) => formData.append('files[]', file));

  try {
    const response = await axios.post('/gmisl/tools/merge-pdf/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    setFeedback('success', response.data?.message || 'Merge PDF berhasil diproses.');
    selectedFiles.value = [];
    await refreshPage();
  } catch (error) {
    setFeedback('error', error.response?.data?.message || 'Merge PDF gagal diproses.');
    await refreshPage();
  } finally {
    isSubmitting.value = false;
  }
}

async function downloadJob(job) {
  const response = await axios.get(`/gmisl/tools/merge-pdf/${job.id}/download`, { responseType: 'blob' });
  const url = window.URL.createObjectURL(response.data);
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', job.output_filename || 'merged.pdf');
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
}

async function deleteJob(job) {
  if (!window.confirm('Hapus job merge PDF ini?')) return;
  await axios.delete(`/gmisl/tools/merge-pdf/${job.id}`);
  setFeedback('success', 'Job merge PDF berhasil dihapus.');
  await refreshPage();
}
</script>
