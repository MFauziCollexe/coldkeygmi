<template>
  <AppLayout>
    <div class="p-6 space-y-4">
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Detail Berita Acara</h2>
          <p class="text-slate-400 text-sm">Preview sesuai template, siap cetak.</p>
        </div>
        <div class="flex gap-2">
          <Link href="/gmisl/utility/berita-acara" class="px-3 py-2 rounded bg-slate-700 hover:bg-slate-600 text-sm font-semibold">
            Kembali
          </Link>
          <button
            type="button"
            class="px-3 py-2 rounded bg-emerald-600 hover:bg-emerald-500 text-sm font-semibold text-white"
            @click="downloadPdf"
          >
            Download PDF
          </button>
          <button
            v-if="canDelete"
            type="button"
            class="px-3 py-2 rounded bg-rose-600 hover:bg-rose-500 text-sm font-semibold text-white"
            @click="deleteItem"
          >
            Hapus
          </button>
        </div>
      </div>

      <div class="bg-white text-slate-900 rounded-lg border border-slate-200 p-8 shadow-sm max-w-4xl">
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
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { swalConfirm } from '@/Utils/swalConfirm';

const props = defineProps({
  item: { type: Object, required: true },
  canDelete: { type: Boolean, default: false },
});

function downloadPdf() {
  window.open(`/gmisl/utility/berita-acara/${props.item.id}/pdf?ts=${Date.now()}`, '_blank', 'noopener');
}

async function deleteItem() {
  const ok = await swalConfirm({
    title: 'Hapus Berita Acara',
    text: 'Hapus Berita Acara ini?',
    confirmButtonText: 'Hapus',
    confirmButtonColor: '#dc2626',
  });
  if (!ok) return;

  router.delete(`/gmisl/utility/berita-acara/${props.item.id}`, {
    preserveScroll: true,
  });
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
