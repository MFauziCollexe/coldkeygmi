<template>
  <div class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
    <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
      <div class="flex items-start gap-4">
        <div class="flex h-16 w-16 items-center justify-center rounded border border-slate-300 bg-white">
          <img
            src="/image/logo-gmi-clean.png"
            alt="PT. Golden Multi Indotama"
            class="h-12 w-12 object-contain"
          />
        </div>
        <div class="space-y-1">
          <div class="text-base font-semibold">PT. GOLDEN MULTI INDOTAMA</div>
          <div class="text-xs text-slate-600">Checklist monitoring pengangkutan sampah domestik</div>
        </div>
      </div>

      <div class="text-sm">
        <div class="grid grid-cols-[auto_auto_auto] gap-x-2">
          <span>No. Dokumen</span>
          <span>:</span>
          <span>FRM.HSE.07.02</span>
        </div>
      </div>
    </div>

    <div class="mb-4 text-center">
      <div class="text-2xl font-semibold italic">Checklist Monitoring Pengangkutan Limbah Non-B3 (Sampah Domestik)</div>
      <div class="text-3xl font-bold">{{ periodLabel }}</div>
    </div>

    <div class="mb-4 flex items-center justify-between gap-4">
      <div class="flex items-center gap-3 text-sm">
        <span>Periode</span>
        <span>:</span>
        <input
          v-model="entry.form.period"
          type="month"
          class="w-44 rounded border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900"
        />
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded bg-slate-600 px-4 py-2 text-sm font-semibold text-white transition"
          :class="!pendingDay || approvedDays.includes(pendingDay) ? 'cursor-not-allowed bg-slate-300 text-slate-500 hover:bg-slate-300' : 'hover:bg-slate-500'"
          :disabled="!pendingDay || approvedDays.includes(pendingDay)"
          :title="pendingDay ? `Ambil foto petugas pengangkut untuk tanggal ${pendingDay}` : 'Tidak ada tanggal yang menunggu'"
          @click="$emit('open-camera', pendingDay)"
        >
          Ambil Foto
        </button>
        <ApprovalButton
          :is-ready="canApproveEntry"
          :disabled="!canApproveEntry"
          label="Approval"
          button-class="w-[96px]"
          tooltip="Approval aktif jika petugas penyerahan, petugas pengangkut, dan foto sudah terisi"
          @click="$emit('approve')"
        />
      </div>
    </div>

      <div class="overflow-x-auto border border-black">
        <table class="min-w-full border-collapse text-sm">
          <colgroup>
            <col class="w-20" />
            <col class="w-44" />
            <col class="w-56" />
            <col class="w-56" />
            <col class="w-60" />
          </colgroup>
          <thead>
            <tr class="bg-slate-100">
            <th rowspan="2" class="border border-black px-2 py-1 text-center">Tanggal</th>
            <th rowspan="2" class="border border-black px-2 py-1 text-center">Waktu Pengangkutan</th>
            <th colspan="1" class="border border-black px-2 py-1 text-center">Petugas Penyerahan</th>
            <th colspan="2" class="border border-black px-2 py-1 text-center">Petugas Pengangkut</th>
          </tr>
          <tr class="bg-slate-100">
            <th class="border border-black px-2 py-1 text-center">Nama</th>
            <th class="border border-black px-2 py-1 text-center">Nama</th>
            <th class="border border-black px-2 py-1 text-center">Foto</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in rows"
            :key="row.day"
          >
            <td
              class="border border-black px-2 py-1 text-center"
              :class="approvedDays.includes(row.day) ? 'bg-emerald-100' : ''"
            >
              {{ row.day }}
            </td>
            <td class="border border-black px-2 py-1">
              <div class="flex items-center justify-center gap-1">
                <span
                  class="w-full px-0 py-1 text-center text-sm"
                  :class="row.pickup_time ? 'text-slate-900' : 'text-slate-400'"
                  :title="row.day === todayDay ? 'Waktu terisi otomatis realtime' : 'Waktu pengangkutan'"
                >
                  {{ row.pickup_time || '-' }}
                </span>
                <span
                  v-if="row.day === todayDay"
                  class="inline-block h-2 w-2 shrink-0 animate-pulse rounded-full bg-emerald-500"
                  title="Realtime"
                ></span>
              </div>
            </td>
            <td class="border border-black px-2 py-1">
              <input
                :value="row.handover_name"
                type="text"
                readonly
                class="w-full cursor-not-allowed border-0 bg-slate-50 px-0 py-1 text-sm text-slate-700 focus:outline-none focus:ring-0"
                :disabled="approvedDays.includes(row.day)"
                placeholder="Nama"
                title="Terisi otomatis pada tanggal hari ini"
              />
            </td>
            <td class="border border-black px-2 py-1">
              <input
                :value="row.collector_name"
                type="text"
                readonly
                class="w-full cursor-not-allowed border-0 bg-slate-50 px-0 py-1 text-sm text-slate-700 focus:outline-none focus:ring-0"
                :disabled="approvedDays.includes(row.day)"
                placeholder="Nama"
                title="Terisi otomatis pada tanggal hari ini"
              />
            </td>
            <td class="border border-black px-2 py-1">
              <div class="flex items-center gap-2">
                <div class="min-w-0 flex-1">
                  <div
                    v-if="row.collector_photo_preview"
                    class="mb-1 h-12 w-12 cursor-pointer overflow-hidden rounded border border-slate-300 bg-slate-100 hover:ring-2 hover:ring-sky-400"
                    title="Klik untuk melihat foto"
                    @click="openPhotoPreview(row)"
                  >
                    <img
                      :src="row.collector_photo_preview"
                      :alt="row.collector_photo_name || 'Foto petugas pengangkut'"
                      class="h-full w-full object-cover"
                    />
                  </div>
                  <span class="block truncate text-xs text-slate-700">{{ row.collector_photo_name || '-' }}</span>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-3 text-xs text-slate-600">
      Baris yang sudah di-approve akan terkunci. `Stempel` petugas pengangkut diganti dengan foto langsung dari kamera.
    </div>

    <!-- Photo Preview Modal -->
    <div
      v-if="previewPhoto"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
      @click.self="closePhotoPreview"
    >
      <div class="w-full max-w-2xl overflow-hidden rounded-xl border border-slate-300 bg-white p-3 shadow-2xl">
        <div class="mb-3 flex items-center justify-between gap-4">
          <div>
            <h3 class="text-base font-semibold text-black">Foto Petugas Pengangkut</h3>
            <p class="text-sm text-slate-500">{{ previewPhoto.name || `Hari ${previewPhoto.day}` }}</p>
          </div>
          <button
            type="button"
            class="rounded bg-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-800 hover:bg-slate-300"
            @click="closePhotoPreview"
          >
            Tutup
          </button>
        </div>
        <div class="max-h-[70vh] overflow-auto rounded border border-slate-300 bg-black">
          <img
            :src="previewPhoto.preview"
            :alt="previewPhoto.name || 'Foto petugas pengangkut'"
            class="mx-auto h-auto w-full object-contain"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import ApprovalButton from '../Components/ApprovalButton.vue';

const props = defineProps({
  entry: {
    type: Object,
    required: true,
  },
  rows: {
    type: Array,
    required: true,
  },
  periodLabel: {
    type: String,
    required: true,
  },
  approvedDays: {
    type: Array,
    required: true,
  },
  pendingDay: {
    type: Number,
    default: null,
  },
  canApproveEntry: {
    type: Boolean,
    required: true,
  },
});

defineEmits(['approve', 'update-row', 'open-camera']);

const todayDay = computed(() => {
  const period = props.entry?.form?.period;
  if (!period) return null;
  const now = new Date();
  const currentPeriod = `${now.getFullYear()}-${`${now.getMonth() + 1}`.padStart(2, '0')}`;
  return period === currentPeriod ? now.getDate() : null;
});

const previewPhoto = ref(null);

function openPhotoPreview(row) {
  if (!row?.collector_photo_preview) return;
  previewPhoto.value = {
    preview: row.collector_photo_preview,
    name: row.collector_photo_name || '',
    day: row.day,
  };
}

function closePhotoPreview() {
  previewPhoto.value = null;
}

function handlePreviewKeydown(event) {
  if (event.key === 'Escape') closePhotoPreview();
}

onMounted(() => document.addEventListener('keydown', handlePreviewKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', handlePreviewKeydown));
</script>
