<template>
  <AppLayout>
    <div class="space-y-6 p-4 sm:p-6">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-900">Control Panel Tools</h1>
          <p class="mt-1 text-sm text-slate-500">Kirim notifikasi WhatsApp test ke +6285743305897.</p>
        </div>
      </div>

      <div v-if="feedback.message" :class="['rounded-lg border px-4 py-3 text-sm', feedback.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700']">
        {{ feedback.message }}
      </div>

      <div class="rounded border border-slate-300 bg-white p-6 shadow-sm">
        <p class="mb-4 text-sm text-slate-500">Klik tombol di bawah untuk mengirim pesan WhatsApp test melalui layanan WhatsApp Business API.</p>

        <button
          type="button"
          class="inline-flex items-center justify-center rounded bg-slate-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-400"
          :disabled="loading"
          @click="sendTestMessage"
        >
          <span v-if="loading">Mengirim...</span>
          <span v-else>Kirim Test WhatsApp</span>
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const loading = ref(false);
const feedback = ref({ message: '', type: 'success' });

async function sendTestMessage() {
  loading.value = true;
  feedback.value = { message: '', type: 'success' };

  try {
    const response = await axios.post('/control-panel/tools/send-whatsapp-test');
    const data = response.data || {};

    if (data.success) {
      feedback.value = { message: data.message || 'Pesan WhatsApp berhasil dikirim.', type: 'success' };
    } else {
      feedback.value = { message: data.message || 'Gagal mengirim WhatsApp.', type: 'error' };
    }
  } catch (error) {
    const message = error.response?.data?.message || error.message || 'Gagal mengirim WhatsApp.';
    feedback.value = { message, type: 'error' };
  } finally {
    loading.value = false;
  }
}
</script>
