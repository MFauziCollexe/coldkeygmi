<template>
  <AppLayout>
    <div class="space-y-4 p-4 md:p-6">
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Detail Berita Acara</h2>
          <p class="text-slate-400 text-sm">Preview sesuai template, siap cetak.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
          <Link href="/gmisl/utility/berita-acara" class="rounded bg-slate-700 px-3 py-2 text-center text-sm font-semibold hover:bg-slate-600">
            Kembali
          </Link>
          <button
            type="button"
            class="rounded bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
            @click="downloadPdf"
          >
            Download PDF
          </button>
          <button
            v-if="canDelete"
            type="button"
            class="rounded bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-500"
            @click="deleteItem"
          >
            Hapus
          </button>
        </div>
      </div>

      <div class="max-w-4xl rounded-lg border border-slate-200 bg-white p-4 text-slate-900 shadow-sm md:p-8">
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
        <div class="mb-4 border-b border-slate-300"></div>

        <p class="text-sm leading-6 font-semibold">Kronologis Kejadian</p>
        <div class="mt-2 text-sm leading-6 whitespace-pre-wrap">
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
