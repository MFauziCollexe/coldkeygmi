<template>
  <div class="min-h-screen bg-slate-200 p-4 print:p-0 print:bg-white">
    <div class="max-w-4xl mx-auto flex items-center justify-between mb-3 print:hidden">
      <Link :href="`/gmisl/utility/berita-acara/${item.id}`" class="px-3 py-2 rounded bg-slate-800 hover:bg-slate-700 text-sm font-semibold text-white">
        Kembali
      </Link>
      <button class="px-3 py-2 rounded bg-indigo-600 hover:bg-indigo-500 text-sm font-semibold text-white" @click="doPrint">
        Print
      </button>
    </div>

    <div class="bg-white text-slate-900 rounded-lg border border-slate-200 p-8 shadow-sm max-w-4xl mx-auto print:rounded-none print:border-0 print:shadow-none">
      <div class="text-center font-semibold text-lg mb-2">Berita Acara</div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm mb-4">
        <div>No. Dokumen : <span class="font-semibold">{{ item.document_number || '-' }}</span></div>
        <div>No BA : <span class="font-semibold">{{ item.number || '-' }}</span></div>
        <div>Tanggal dibuat : <span class="font-semibold">{{ formatDateLong(item.letter_date) }}</span></div>
        <div>Tanggal kejadian : <span class="font-semibold">{{ formatDateLong(item.event_date) }}</span></div>
        <div>Tempat kejadian : <span class="font-semibold">{{ item.event_location || '-' }}</span></div>
        <div>Waktu kejadian : <span class="font-semibold">{{ formatTime(item.incident_time || item.start_time) }}</span></div>
        <div>Customer : <span class="font-semibold">{{ item.customer?.name || '-' }}</span></div>
        <div>Divisi : <span class="font-semibold">{{ item.department?.name || '-' }}</span></div>
        <div class="md:col-span-2">No Mobil : <span class="font-semibold">{{ item.vehicle_no || '-' }}</span></div>
      </div>
      <div class="border-b border-slate-300 mb-4"></div>

      <p class="text-sm leading-6 font-semibold">Kronologis Kejadian</p>
      <div class="mt-2 text-sm whitespace-pre-wrap leading-6">
        {{ item.chronology || '-' }}
      </div>

      <p class="text-sm leading-6 mt-4">
        Demikian berita acara ini dibuat dengan sebenarnya.<br />
        Atas perhatian dan kerjasamanya kami ucapkan terima kasih.
      </p>

    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  item: { type: Object, required: true },
});

function doPrint() {
  window.print();
}

function formatDateLong(value) {
  if (!value) return '-';
  const date = new Date(String(value));
  if (Number.isNaN(date.getTime())) return '-';
  return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
}

function formatTime(value) {
  if (!value) return '-';
  return String(value).slice(0, 5);
}

</script>

<style>
@media print {
  @page {
    size: A4;
    margin: 18mm;
  }
}
</style>
