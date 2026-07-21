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

      <div class="rounded border border-slate-300 bg-white p-6 shadow-sm">
        <p class="mb-4 text-sm text-slate-500">Klik tombol di bawah untuk menguji koneksi ke database PostgreSQL.</p>

        <button
          type="button"
          class="inline-flex items-center justify-center rounded bg-indigo-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-600 disabled:cursor-not-allowed disabled:bg-indigo-400"
          :disabled="pgLoading"
          @click="testPostgresConnection"
        >
          <span v-if="pgLoading">Menghubungi PostgreSQL...</span>
          <span v-else>Test Koneksi PostgreSQL</span>
        </button>

        <div v-if="pgFeedback.message" :class="['mt-4 rounded-lg border px-4 py-3 text-sm', pgFeedback.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700']">
          {{ pgFeedback.message }}
          <ul v-if="pgFeedback.details" class="mt-2 list-inside list-disc text-xs text-slate-600">
            <li>Host: {{ pgFeedback.details.host }}:{{ pgFeedback.details.port }}</li>
            <li>Database: {{ pgFeedback.details.database }}</li>
            <li>Username: {{ pgFeedback.details.username }}</li>
            <li>Server Version: {{ pgFeedback.details.server_version }}</li>
            <li>Response Time: {{ pgFeedback.details.response_time_ms }}ms</li>
          </ul>
        </div>
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
const pgLoading = ref(false);
const pgFeedback = ref({ message: '', type: 'success', details: null });

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

async function testPostgresConnection() {
  pgLoading.value = true;
  pgFeedback.value = { message: '', type: 'success', details: null };

  try {
    const response = await axios.post('/control-panel/tools/test-postgres');
    const data = response.data || {};

    if (data.success) {
      pgFeedback.value = { message: data.message || 'Koneksi berhasil.', type: 'success', details: data.details || null };
    } else {
      pgFeedback.value = { message: data.message || 'Gagal koneksi.', type: 'error', details: null };
    }
  } catch (error) {
    const message = error.response?.data?.message || error.message || 'Gagal koneksi ke PostgreSQL.';
    pgFeedback.value = { message, type: 'error', details: null };
  } finally {
    pgLoading.value = false;
  }
}
</script>
