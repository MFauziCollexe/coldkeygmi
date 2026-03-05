<template>
  <AppLayout>
    <div class="p-6 max-w-5xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Check Inline Detail</h2>
        <Link href="/check-inline" class="text-indigo-400">Back to list</Link>
      </div>

      <div class="bg-slate-800 rounded p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <div class="text-slate-400 text-sm">ID</div>
            <div>{{ row.id }}</div>
          </div>
          <div>
            <div class="text-slate-400 text-sm">User</div>
            <div>{{ row.user_name || '-' }}</div>
          </div>
          <div>
            <div class="text-slate-400 text-sm">Customer</div>
            <div>{{ row.customer || '-' }}</div>
          </div>
          <div>
            <div class="text-slate-400 text-sm">PO</div>
            <div>{{ row.po || '-' }}</div>
          </div>
          <div>
            <div class="text-slate-400 text-sm">Qty</div>
            <div>{{ row.qty ?? '-' }}</div>
          </div>
          <div>
            <div class="text-slate-400 text-sm">Exp</div>
            <div>{{ formatDate(row.exp) }}</div>
          </div>
          <div>
            <div class="text-slate-400 text-sm">Batch</div>
            <div>{{ row.batch || '-' }}</div>
          </div>
          <div>
            <div class="text-slate-400 text-sm">Date</div>
            <div>{{ formatDate(row.date) }}</div>
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
              class="border border-slate-600 rounded overflow-hidden hover:border-indigo-400"
              :title="`Image ${idx + 1}`"
            >
              <img :src="url" :alt="`Image ${idx + 1}`" class="w-20 h-20 object-cover" />
            </button>
          </div>
          <div v-else>-</div>
        </div>
      </div>

      <div
        v-if="showImageModal"
        class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4"
        @click="closeImage"
      >
        <div class="max-w-5xl max-h-[90vh]" @click.stop>
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

