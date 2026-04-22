<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Compress PDF</h1>
          <p class="mt-1 text-gray-500">Upload, kompres, lalu download PDF hasilnya dari satu halaman.</p>
        </div>
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

      <div v-if="stats" class="grid grid-cols-1 gap-4 md:grid-cols-5">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
          <p class="text-sm text-gray-500">Total Jobs</p>
          <p class="text-2xl font-bold text-gray-900">{{ stats.total_jobs }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
          <p class="text-sm text-gray-500">Completed</p>
          <p class="text-2xl font-bold text-green-600">{{ stats.completed }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
          <p class="text-sm text-gray-500">Processing</p>
          <p class="text-2xl font-bold text-blue-600">{{ stats.processing }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
          <p class="text-sm text-gray-500">Failed</p>
          <p class="text-2xl font-bold text-red-600">{{ stats.failed }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
          <p class="text-sm text-gray-500">Avg Compression</p>
          <p class="text-2xl font-bold text-indigo-600">{{ stats.avg_compression_ratio }}%</p>
        </div>
      </div>

      <div class="rounded-lg border-2 border-dashed border-gray-300 bg-white p-8">
        <div class="space-y-4">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Upload PDF</h2>
            <p class="text-sm text-gray-500">Pilih satu atau beberapa file PDF. Batas maksimal 100 MB per file.</p>
          </div>

          <div class="space-y-3">
            <div
              :class="[
                'rounded-lg border-2 border-dashed p-8 text-center transition',
                isDragging
                  ? 'border-blue-500 bg-blue-50'
                  : 'border-gray-300 bg-white hover:border-gray-400'
              ]"
              @click="openFilePicker"
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="handleDrop"
            >
              <input
                ref="fileInput"
                type="file"
                multiple
                accept=".pdf,application/pdf"
                class="hidden"
                @change="handleFileSelect"
              />

              <button
                type="button"
                class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700"
                @click.stop="openFilePicker"
              >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Klik untuk pilih file atau drag-and-drop ke area ini</span>
              </button>

              <p class="mt-2 text-xs text-gray-500">Format yang didukung: PDF</p>
            </div>

            <div v-if="selectedFiles.length > 0" class="space-y-2">
              <div class="text-sm font-medium text-gray-900">
                File Terpilih ({{ selectedFiles.length }})
              </div>

              <div class="max-h-40 space-y-1 overflow-y-auto">
                <div
                  v-for="(file, index) in selectedFiles"
                  :key="`${file.name}-${index}`"
                  class="flex items-center justify-between rounded-lg bg-gray-50 p-2 text-sm text-gray-700"
                >
                  <div class="min-w-0">
                    <div class="truncate font-medium">{{ file.name }}</div>
                    <div class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</div>
                  </div>

                  <button
                    type="button"
                    class="text-red-600 hover:text-red-700"
                    @click="removeSelectedFile(index)"
                  >
                    Hapus
                  </button>
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-900">Compression Level</label>

              <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <button
                  v-for="level in compressionLevels"
                  :key="level.value"
                  type="button"
                  :class="[
                    'rounded-lg border-2 p-3 text-left text-sm font-medium transition',
                    compressionLevel === level.value
                      ? 'border-blue-600 bg-blue-50 text-blue-700'
                      : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'
                  ]"
                  @click="compressionLevel = level.value"
                >
                  <div class="font-semibold">{{ level.label }}</div>
                  <div class="mt-1 text-xs text-gray-500">{{ level.desc }}</div>
                </button>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-400"
                :disabled="selectedFiles.length === 0 || isUploading"
                @click="submitUpload"
              >
                <svg v-if="isUploading" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                </svg>
                <span>{{ isUploading ? 'Memproses...' : 'Upload & Compress' }}</span>
              </button>

              <button
                v-if="selectedFiles.length > 0"
                type="button"
                class="text-gray-600 hover:text-gray-700"
                @click="clearSelectedFiles"
              >
                Clear
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-6 py-4">
          <h2 class="text-lg font-semibold text-gray-900">Compression History</h2>
        </div>

        <div v-if="jobs.data.length === 0" class="px-6 py-8 text-center">
          <p class="text-gray-500">Belum ada job kompresi.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b border-gray-200 bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Filename</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Original Size</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Compressed Size</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Compression</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Actions</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
              <tr v-for="job in jobs.data" :key="job.id" class="align-top hover:bg-gray-50">
                <td class="px-6 py-4 text-sm text-gray-900">
                  <div class="font-medium">{{ job.original_filename }}</div>
                  <div v-if="job.error_message" class="mt-1 max-w-md text-xs text-red-600">
                    {{ job.error_message }}
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  {{ formatFileSize(job.original_size) }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  {{ job.compressed_size ? formatFileSize(job.compressed_size) : '-' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  <span v-if="job.status === 'completed'" class="font-semibold text-green-600">
                    {{ formatRatio(job.compression_ratio) }}
                  </span>
                  <span v-else>-</span>
                </td>
                <td class="px-6 py-4 text-sm">
                  <span
                    :class="[
                      'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold capitalize',
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
                <td class="px-6 py-4 text-sm text-gray-700">
                  {{ formatDate(job.created_at) }}
                </td>
                <td class="px-6 py-4 text-sm">
                  <div class="flex flex-wrap items-center gap-3">
                    <button
                      v-if="job.status === 'completed'"
                      type="button"
                      class="text-blue-600 hover:text-blue-700"
                      @click="downloadFile(job)"
                    >
                      Download
                    </button>

                    <button
                      type="button"
                      class="text-red-600 hover:text-red-700"
                      @click="deleteJob(job)"
                    >
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
                link.active
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100',
                !link.url && 'pointer-events-none opacity-50'
              ]"
              preserve-scroll
              v-html="sanitizePaginationLabel(link.label)"
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
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  jobs: {
    type: Object,
    required: true,
  },
});

const fileInput = ref(null);
const selectedFiles = ref([]);
const isDragging = ref(false);
const isUploading = ref(false);
const compressionLevel = ref('medium');
const stats = ref(null);
const feedback = ref({
  type: 'success',
  message: '',
});
const poller = ref(null);

const compressionLevels = [
  { value: 'low', label: 'Low', desc: 'Kualitas terbaik, ukuran file lebih besar.' },
  { value: 'medium', label: 'Medium', desc: 'Seimbang untuk kebanyakan dokumen.' },
  { value: 'high', label: 'High', desc: 'Ukuran file sekecil mungkin.' },
];

function sanitizePaginationLabel(label) {
  return label.replaceAll('&laquo;', '&laquo;').replaceAll('&raquo;', '&raquo;');
}

function setFeedback(type, message) {
  feedback.value = { type, message };
}

function openFilePicker() {
  fileInput.value?.click();
}

function dedupeFiles(files) {
  const seen = new Set();

  return files.filter((file) => {
    const key = `${file.name}-${file.size}-${file.lastModified}`;

    if (seen.has(key)) {
      return false;
    }

    seen.add(key);
    return true;
  });
}

function assignSelectedFiles(files) {
  const pdfFiles = dedupeFiles(
    files.filter((file) => file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'))
  );

  selectedFiles.value = pdfFiles;

  if (files.length > 0 && pdfFiles.length === 0) {
    setFeedback('error', 'Hanya file PDF yang bisa diproses.');
  } else if (files.length !== pdfFiles.length) {
    setFeedback('error', 'Beberapa file diabaikan karena bukan PDF.');
  }
}

function handleFileSelect(event) {
  const files = Array.from(event.target.files || []);
  assignSelectedFiles(files);

  if (fileInput.value) {
    fileInput.value.value = '';
  }
}

function handleDrop(event) {
  isDragging.value = false;
  const files = Array.from(event.dataTransfer?.files || []);
  assignSelectedFiles(files);
}

function removeSelectedFile(index) {
  selectedFiles.value.splice(index, 1);
}

function clearSelectedFiles() {
  selectedFiles.value = [];
}

function formatFileSize(bytes) {
  const numericBytes = Number(bytes || 0);

  if (!numericBytes) {
    return '0 B';
  }

  const units = ['B', 'KB', 'MB', 'GB'];
  const index = Math.min(Math.floor(Math.log(numericBytes) / Math.log(1024)), units.length - 1);
  const value = numericBytes / (1024 ** index);

  return `${value.toFixed(value >= 10 || index === 0 ? 0 : 2)} ${units[index]}`;
}

function formatRatio(value) {
  return `${Number(value || 0).toFixed(2)}%`;
}

function formatDate(value) {
  if (!value) {
    return '-';
  }

  return new Date(value).toLocaleString('id-ID');
}

async function refreshPage() {
  await loadStats();

  router.reload({
    only: ['jobs'],
    preserveScroll: true,
    preserveState: true,
  });
}

async function submitUpload() {
  if (selectedFiles.value.length === 0) {
    return;
  }

  isUploading.value = true;
  setFeedback('success', '');

  const formData = new FormData();
  selectedFiles.value.forEach((file) => {
    formData.append('files[]', file);
  });
  formData.append('compression_level', compressionLevel.value);

  try {
    const response = await axios.post('/gmisl/tools/compress-pdf/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    setFeedback('success', response.data?.message || 'File berhasil diproses.');
    clearSelectedFiles();
    await refreshPage();
  } catch (error) {
    const message = error.response?.data?.message || 'Upload gagal diproses.';
    const failedFiles = error.response?.data?.failed_files || [];

    if (failedFiles.length > 0) {
      setFeedback('error', `${message} ${failedFiles[0].filename}: ${failedFiles[0].message}`);
    } else {
      setFeedback('error', message);
    }

    await refreshPage();
  } finally {
    isUploading.value = false;
  }
}

async function downloadFile(job) {
  try {
    const response = await axios.get(`/gmisl/tools/compress-pdf/${job.id}/download`, {
      responseType: 'blob',
    });

    const url = window.URL.createObjectURL(response.data);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', job.compressed_filename || 'compressed.pdf');
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    setFeedback('error', error.response?.data?.message || 'Download gagal.');
  }
}

async function deleteJob(job) {
  if (!window.confirm(`Hapus job "${job.original_filename}"?`)) {
    return;
  }

  try {
    await axios.delete(`/gmisl/tools/compress-pdf/${job.id}`);
    setFeedback('success', 'Job berhasil dihapus.');
    await refreshPage();
  } catch (error) {
    setFeedback('error', error.response?.data?.message || 'Delete gagal.');
  }
}

async function loadStats() {
  try {
    const response = await axios.get('/gmisl/tools/compress-pdf-stats');
    stats.value = response.data;
  } catch (error) {
    setFeedback('error', 'Gagal memuat statistik modul Compress PDF.');
  }
}

function startPolling() {
  const hasActiveJobs = props.jobs.data.some((job) => ['pending', 'processing'].includes(job.status));

  if (!hasActiveJobs) {
    return;
  }

  poller.value = window.setInterval(() => {
    refreshPage();
  }, 5000);
}

onMounted(async () => {
  await loadStats();
  startPolling();
});

onBeforeUnmount(() => {
  if (poller.value) {
    window.clearInterval(poller.value);
  }
});
</script>
