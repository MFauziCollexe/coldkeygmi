<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Compress PDF</h1>
          <p class="mt-1 text-gray-500">Upload dan kompres file PDF dengan mudah</p>
        </div>
      </div>

      <!-- Stats -->
      <div v-if="stats" class="grid grid-cols-1 gap-4 md:grid-cols-4">
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
          <p class="text-sm text-gray-500">Avg Compression</p>
          <p class="text-2xl font-bold text-purple-600">{{ stats.avg_compression_ratio }}%</p>
        </div>
      </div>

      <!-- Upload Section -->
      <div class="rounded-lg border-2 border-dashed border-gray-300 bg-white p-8">
        <div class="space-y-4">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Upload PDF Files</h3>
            <p class="text-sm text-gray-500">Drag and drop files or click to select</p>
          </div>

          <!-- File Input -->
          <div class="space-y-3">
            <div
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="handleDrop"
              :class="[
                'rounded-lg border-2 border-dashed p-8 text-center transition',
                isDragging
                  ? 'border-blue-500 bg-blue-50'
                  : 'border-gray-300 bg-white hover:border-gray-400'
              ]"
            >
              <input
                ref="fileInput"
                type="file"
                multiple
                accept=".pdf"
                class="hidden"
                @change="handleFileSelect"
              />
              <button
                type="button"
                @click="fileInput?.$el?.click()"
                class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700"
              >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Click to select files or drag and drop</span>
              </button>
              <p class="mt-2 text-xs text-gray-500">Supported format: PDF (max 100MB per file)</p>
            </div>

            <!-- Selected Files Preview -->
            <div v-if="selectedFiles.length > 0" class="space-y-2">
              <div class="text-sm font-medium text-gray-900">
                Selected Files ({{ selectedFiles.length }})
              </div>
              <div class="max-h-40 space-y-1 overflow-y-auto">
                <div
                  v-for="(file, index) in selectedFiles"
                  :key="index"
                  class="flex items-center justify-between rounded-lg bg-gray-50 p-2 text-sm text-gray-700"
                >
                  <span>{{ file.name }}</span>
                  <button
                    type="button"
                    @click="selectedFiles.splice(index, 1)"
                    class="text-red-600 hover:text-red-700"
                  >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                      />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Compression Level -->
            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-900">Compression Level</label>
              <div class="grid grid-cols-3 gap-3">
                <button
                  v-for="level in compressionLevels"
                  :key="level.value"
                  @click="compressionLevel = level.value"
                  :class="[
                    'rounded-lg border-2 p-3 text-sm font-medium transition',
                    compressionLevel === level.value
                      ? 'border-blue-600 bg-blue-50 text-blue-600'
                      : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'
                  ]"
                >
                  <div class="font-semibold">{{ level.label }}</div>
                  <div class="text-xs text-gray-500">{{ level.desc }}</div>
                </button>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3">
              <button
                type="button"
                @click="submitUpload"
                :disabled="selectedFiles.length === 0 || isUploading"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-400"
              >
                <svg v-if="!isUploading" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M4.707 6.293a1 1 0 010 1.414l4.5 4.5a1 1 0 001.414 0l4.5-4.5a1 1 0 01-1.414-1.414L11 7.586V3a1 1 0 10-2 0v4.586L6.121 6.293a1 1 0 010-1.414z" />
                </svg>
                <svg v-else class="h-5 w-5 animate-spin" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M4.555 5.555a1 1 0 010-1.414l1.414-1.414a1 1 0 011.414 0l1.414 1.414a1 1 0 01-1.414 1.414l-.707-.707a1 1 0 00-1.414 0l-.707.707a1 1 0 010 1.414z" />
                </svg>
                {{ isUploading ? 'Uploading...' : 'Upload & Compress' }}
              </button>
              <button
                v-if="selectedFiles.length > 0"
                type="button"
                @click="selectedFiles = []"
                class="text-gray-600 hover:text-gray-700"
              >
                Clear
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Jobs List -->
      <div class="rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-6 py-4">
          <h2 class="text-lg font-semibold text-gray-900">Compression History</h2>
        </div>

        <div v-if="jobs.data.length === 0" class="px-6 py-8 text-center">
          <p class="text-gray-500">No compression jobs yet</p>
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
              <tr v-for="job in jobs.data" :key="job.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm text-gray-900">
                  <span class="truncate" :title="job.original_filename">
                    {{ job.original_filename }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  {{ formatFileSize(job.original_size) }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  {{ job.compressed_size ? formatFileSize(job.compressed_size) : '-' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  <span v-if="job.compression_ratio" class="font-semibold text-green-600">
                    {{ job.compression_ratio }}%
                  </span>
                  <span v-else>-</span>
                </td>
                <td class="px-6 py-4 text-sm">
                  <span
                    :class="[
                      'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold',
                      {
                        'bg-green-100 text-green-800': job.status === 'completed',
                        'bg-blue-100 text-blue-800': job.status === 'processing',
                        'bg-yellow-100 text-yellow-800': job.status === 'pending',
                        'bg-red-100 text-red-800': job.status === 'failed',
                      }
                    ]"
                  >
                    {{ job.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  {{ new Date(job.created_at).toLocaleDateString() }}
                </td>
                <td class="px-6 py-4 text-sm">
                  <div class="flex items-center gap-2">
                    <button
                      v-if="job.status === 'completed'"
                      @click="downloadFile(job)"
                      class="text-blue-600 hover:text-blue-700"
                      title="Download"
                    >
                      <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 12a1 1 0 011-1h.01a1 1 0 110 2H5a1 1 0 01-1-1zm12-1h.01a1 1 0 110 2H16a1 1 0 110-2zm-7.5-3.5a1 1 0 110-2 1 1 0 010 2z" />
                      </svg>
                    </button>
                    <button
                      @click="deleteJob(job)"
                      class="text-red-600 hover:text-red-700"
                      title="Delete"
                    >
                      <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="jobs.links.length > 2" class="border-t border-gray-200 px-6 py-4">
          <div class="flex items-center justify-center gap-1">
            <a
              v-for="link in jobs.links"
              :key="link.label"
              :href="link.url || '#'"
              :class="[
                'px-3 py-2 text-sm font-medium rounded-lg',
                link.active
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100 ' + (link.url ? 'cursor-pointer' : 'cursor-not-allowed opacity-50')
              ]"
            >
              {{ link.label.replace('&laquo;', '«').replace('&raquo;', '»') }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  jobs: Object,
});

const fileInput = ref(null);
const selectedFiles = ref([]);
const isDragging = ref(false);
const isUploading = ref(false);
const compressionLevel = ref('medium');
const stats = ref(null);

const compressionLevels = [
  { value: 'low', label: 'Low', desc: 'Best Quality' },
  { value: 'medium', label: 'Medium', desc: 'Balanced' },
  { value: 'high', label: 'High', desc: 'Maximum Compression' },
];

function handleFileSelect(event) {
  const files = Array.from(event.target.files || []);
  selectedFiles.value = files;
}

function handleDrop(event) {
  isDragging.value = false;
  const files = Array.from(event.dataTransfer.files).filter(f => f.type === 'application/pdf');
  selectedFiles.value = files;
}

function formatFileSize(bytes) {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

async function submitUpload() {
  if (selectedFiles.value.length === 0) return;

  isUploading.value = true;
  const formData = new FormData();
  
  selectedFiles.value.forEach(file => {
    formData.append('files[]', file);
  });
  formData.append('compression_level', compressionLevel.value);

  try {
    const response = await axios.post('/gmisl/tools/compress-pdf/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    alert('Files uploaded successfully!');
    selectedFiles.value = [];
    window.location.reload();
  } catch (error) {
    alert('Upload failed: ' + (error.response?.data?.message || error.message));
  } finally {
    isUploading.value = false;
  }
}

async function downloadFile(job) {
  try {
    const response = await axios.get(`/gmisl/tools/compress-pdf/${job.id}/download`, {
      responseType: 'blob',
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', job.compressed_filename);
    document.body.appendChild(link);
    link.click();
    link.parentNode.removeChild(link);
  } catch (error) {
    alert('Download failed: ' + error.message);
  }
}

async function deleteJob(job) {
  if (!confirm('Are you sure you want to delete this job?')) return;

  try {
    await axios.delete(`/gmisl/tools/compress-pdf/${job.id}`);
    alert('Job deleted successfully!');
    window.location.reload();
  } catch (error) {
    alert('Delete failed: ' + error.message);
  }
}

async function loadStats() {
  try {
    const response = await axios.get('/gmisl/tools/compress-pdf-stats');
    stats.value = response.data;
  } catch (error) {
    console.error('Failed to load stats:', error);
  }
}

onMounted(() => {
  loadStats();
});
</script>
