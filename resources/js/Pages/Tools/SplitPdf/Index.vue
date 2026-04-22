<template>
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Split PDF</h1>
        <p class="mt-1 text-gray-500">Pisahkan PDF per halaman atau berdasarkan range halaman tertentu.</p>
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
            <h2 class="text-lg font-semibold text-gray-900">Upload File</h2>
            <p class="text-sm text-gray-500">Pilih satu file PDF, lalu tentukan mode split.</p>
          </div>

          <div class="space-y-3">
            <input ref="fileInput" type="file" accept=".pdf,application/pdf" class="hidden" @change="handleSelect" />
            <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="openPicker">
              {{ selectedFile ? selectedFile.name : 'Pilih File PDF' }}
            </button>
            <div v-if="selectedFile" class="text-sm text-gray-500">{{ formatFileSize(selectedFile.size) }}</div>
          </div>

          <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <button
              type="button"
              :class="[
                'rounded-lg border-2 p-4 text-left',
                splitMode === 'all_pages' ? 'border-blue-600 bg-blue-50' : 'border-gray-200 bg-white'
              ]"
              @click="splitMode = 'all_pages'"
            >
              <div class="font-semibold text-gray-900">Per Halaman</div>
              <div class="mt-1 text-sm text-gray-500">Setiap halaman menjadi file terpisah.</div>
            </button>
            <button
              type="button"
              :class="[
                'rounded-lg border-2 p-4 text-left',
                splitMode === 'custom_ranges' ? 'border-blue-600 bg-blue-50' : 'border-gray-200 bg-white'
              ]"
              @click="splitMode = 'custom_ranges'"
            >
              <div class="font-semibold text-gray-900">Custom Range</div>
              <div class="mt-1 text-sm text-gray-500">Pisah berdasarkan rentang halaman yang Anda tentukan.</div>
            </button>
          </div>

          <div v-if="splitMode === 'custom_ranges'" class="space-y-3 rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
              <div class="text-sm font-medium text-gray-900">Page Ranges</div>
              <button type="button" class="text-sm text-blue-600 hover:text-blue-700" @click="addRange">
                Tambah Range
              </button>
            </div>

            <div v-for="(range, index) in ranges" :key="index" class="flex items-center gap-3">
              <input v-model.number="range.start" type="number" min="1" class="w-28 rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Start" />
              <span class="text-gray-500">s/d</span>
              <input v-model.number="range.end" type="number" min="1" class="w-28 rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="End" />
              <button type="button" class="text-red-600 hover:text-red-700" @click="removeRange(index)">
                Hapus
              </button>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <button
              type="button"
              class="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-400"
              :disabled="!selectedFile || isSubmitting"
              @click="submitSplit"
            >
              {{ isSubmitting ? 'Memproses...' : 'Split PDF' }}
            </button>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-6 py-4">
          <h2 class="text-lg font-semibold text-gray-900">Split History</h2>
        </div>

        <div v-if="jobs.data.length === 0" class="px-6 py-8 text-center text-gray-500">
          Belum ada job split PDF.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Source</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Mode</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Output</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="job in jobs.data" :key="job.id">
                <td class="px-6 py-4 text-sm text-gray-700">
                  <div class="font-medium">{{ job.original_filename }}</div>
                  <div v-if="job.page_ranges?.length" class="mt-1 text-xs text-gray-500">
                    {{ formatRanges(job.page_ranges) }}
                  </div>
                  <div v-if="job.error_message" class="mt-1 text-xs text-red-600">{{ job.error_message }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  {{ job.split_mode === 'all_pages' ? 'Per halaman' : 'Custom range' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  <div>{{ job.output_filename || '-' }}</div>
                  <div class="text-xs text-gray-500">
                    {{ job.output_count ? `${job.output_count} file` : '' }}
                    <span v-if="job.output_size">• {{ formatFileSize(job.output_size) }}</span>
                  </div>
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
const selectedFile = ref(null);
const splitMode = ref('all_pages');
const ranges = ref([{ start: 1, end: 1 }]);
const isSubmitting = ref(false);
const feedback = ref({ type: 'success', message: '' });

function openPicker() {
  fileInput.value?.click();
}

function setFeedback(type, message) {
  feedback.value = { type, message };
}

function handleSelect(event) {
  const [file] = Array.from(event.target.files || []);
  if (!file) return;

  if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
    setFeedback('error', 'Hanya file PDF yang bisa diproses.');
    return;
  }

  selectedFile.value = file;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
}

function addRange() {
  ranges.value.push({ start: 1, end: 1 });
}

function removeRange(index) {
  if (ranges.value.length === 1) {
    ranges.value = [{ start: 1, end: 1 }];
    return;
  }

  ranges.value.splice(index, 1);
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

function formatRanges(items) {
  return items.map((item) => `${item.start}-${item.end}`).join(', ');
}

async function refreshPage() {
  router.reload({ only: ['jobs'], preserveState: true, preserveScroll: true });
}

async function submitSplit() {
  if (!selectedFile.value) return;

  isSubmitting.value = true;
  setFeedback('success', '');

  const formData = new FormData();
  formData.append('file', selectedFile.value);
  formData.append('split_mode', splitMode.value);

  if (splitMode.value === 'custom_ranges') {
    ranges.value.forEach((range, index) => {
      formData.append(`ranges[${index}][start]`, String(range.start || ''));
      formData.append(`ranges[${index}][end]`, String(range.end || ''));
    });
  }

  try {
    const response = await axios.post('/gmisl/tools/split-pdf/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    setFeedback('success', response.data?.message || 'Split PDF berhasil diproses.');
    selectedFile.value = null;
    ranges.value = [{ start: 1, end: 1 }];
    splitMode.value = 'all_pages';
    await refreshPage();
  } catch (error) {
    setFeedback('error', error.response?.data?.message || 'Split PDF gagal diproses.');
    await refreshPage();
  } finally {
    isSubmitting.value = false;
  }
}

async function downloadJob(job) {
  const response = await axios.get(`/gmisl/tools/split-pdf/${job.id}/download`, { responseType: 'blob' });
  const url = window.URL.createObjectURL(response.data);
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', job.output_filename || 'split-output');
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
}

async function deleteJob(job) {
  if (!window.confirm('Hapus job split PDF ini?')) return;
  await axios.delete(`/gmisl/tools/split-pdf/${job.id}`);
  setFeedback('success', 'Job split PDF berhasil dihapus.');
  await refreshPage();
}
</script>
