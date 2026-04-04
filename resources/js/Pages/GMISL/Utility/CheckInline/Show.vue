<template>
  <AppLayout>
    <div class="mx-auto max-w-5xl p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <h2 class="text-2xl font-bold">Check Inline Detail</h2>
        <Link href="/check-inline" class="text-indigo-400">Back to list</Link>
      </div>

      <div class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
        <div class="space-y-3 md:grid md:grid-cols-2 md:gap-4 md:space-y-0">
          <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3 md:block md:border-b-0 md:pb-0">
            <div class="text-slate-400 text-sm">ID</div>
            <div class="max-w-[62%] text-right md:max-w-none md:text-left">{{ row.id }}</div>
          </div>
          <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3 md:block md:border-b-0 md:pb-0">
            <div class="text-slate-400 text-sm">User</div>
            <div class="max-w-[62%] text-right md:max-w-none md:text-left">{{ row.user_name || '-' }}</div>
          </div>
          <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3 md:block md:border-b-0 md:pb-0">
            <div class="text-slate-400 text-sm">Customer</div>
            <div class="max-w-[62%] text-right md:max-w-none md:text-left">{{ row.customer || '-' }}</div>
          </div>
          <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3 md:block md:border-b-0 md:pb-0">
            <div class="text-slate-400 text-sm">PO</div>
            <div class="max-w-[62%] text-right md:max-w-none md:text-left">{{ row.po || '-' }}</div>
          </div>
          <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3 md:block md:border-b-0 md:pb-0">
            <div class="text-slate-400 text-sm">Qty</div>
            <div class="max-w-[62%] text-right md:max-w-none md:text-left">{{ row.qty ?? '-' }}</div>
          </div>
          <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3 md:block md:border-b-0 md:pb-0">
            <div class="text-slate-400 text-sm">Exp</div>
            <div class="max-w-[62%] text-right md:max-w-none md:text-left">{{ formatDate(row.exp) }}</div>
          </div>
          <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3 md:block md:border-b-0 md:pb-0">
            <div class="text-slate-400 text-sm">Batch</div>
            <div class="max-w-[62%] text-right md:max-w-none md:text-left">{{ row.batch || '-' }}</div>
          </div>
          <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3 md:block md:border-b-0 md:pb-0">
            <div class="text-slate-400 text-sm">Date</div>
            <div class="max-w-[62%] text-right md:max-w-none md:text-left">{{ formatDate(row.date) }}</div>
          </div>
        </div>

        <div>
          <div class="text-slate-400 text-sm mb-2">Images</div>
          <div v-if="row.image_urls && row.image_urls.length" class="flex flex-wrap gap-3">
            <button
              v-for="(url, idx) in row.image_urls"
              :key="url"
              type="button"
              @click="openImage(url)"
              class="overflow-hidden rounded border border-slate-600 hover:border-indigo-400"
              :title="`Image ${idx + 1}`"
            >
              <img :src="url" :alt="`Image ${idx + 1}`" class="h-16 w-16 object-cover md:h-20 md:w-20" />
            </button>
          </div>
          <div v-else>-</div>
        </div>
      </div>

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
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  row: Object,
});

const showImageModal = ref(false);
const selectedImage = ref('');

function formatDate(value) {
  if (!value) return '-';
  return new Date(value).toLocaleDateString('id-ID');
}

function openImage(url) {
  selectedImage.value = url;
  showImageModal.value = true;
}

function closeImage() {
  showImageModal.value = false;
  selectedImage.value = '';
}
</script>
