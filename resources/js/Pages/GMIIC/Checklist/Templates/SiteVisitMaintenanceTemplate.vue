<template>
  <div class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
    <div class="mb-5 overflow-x-auto border border-black">
      <table class="w-full table-fixed border-collapse text-xs sm:text-sm">
        <tbody>
          <tr>
            <td rowspan="2" class="w-24 border border-black px-2 py-3 text-center sm:w-36 sm:px-3">
              <img
                src="/image/logo-gmi-clean.png"
                alt="PT. Golden Multi Indotama"
                class="mx-auto h-12 w-12 object-contain sm:h-16 sm:w-16"
              />
            </td>
            <td colspan="2" class="border border-black px-2 py-2 text-center text-sm font-bold sm:px-3 sm:text-2xl">
              PT GOLDEN MULTI INDOTAMA
            </td>
          </tr>
          <tr>
            <td class="border border-black px-2 py-3 text-center sm:w-80 sm:px-3">
              <div class="text-sm font-bold leading-tight sm:text-xl">{{ currentTypeMeta.title }}</div>
            </td>
            <td class="border border-black p-0 align-top">
              <table class="w-full border-collapse text-[11px] sm:text-sm">
                <tbody>
                  <tr>
                    <td class="w-24 border border-black px-2 py-1 sm:w-40">Doc. No.</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.document_no }}</td>
                  </tr>
                  <tr>
                    <td class="border border-black px-2 py-1">Rev.</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.rev }}</td>
                  </tr>
                  <tr>
                    <td class="border border-black px-2 py-1">Tanggal Efektif</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.effective_date }}</td>
                  </tr>
                  <tr>
                    <td class="border border-black px-2 py-1">Halaman</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.page }}</td>
                  </tr>
                </tbody>
              </table>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
      <div class="flex flex-col gap-3 font-semibold lg:flex-row lg:flex-wrap lg:items-center">
        <div class="grid grid-cols-[72px_minmax(0,1fr)] items-center gap-2 sm:flex sm:items-center sm:gap-3">
          <span class="text-base sm:min-w-24 sm:text-lg">Jenis:</span>
          <select
            :value="entry.form.visit_type"
            class="w-full max-w-[260px] rounded border border-slate-400 bg-white px-3 py-2 text-sm font-normal text-slate-900 sm:max-w-none"
            @change="$emit('update-type', $event.target.value)"
          >
            <option
              v-for="option in typeOptions"
              :key="option.id"
              :value="option.id"
            >
              {{ option.name }}
            </option>
          </select>
        </div>

        <div class="grid grid-cols-[72px_minmax(0,1fr)] items-center gap-2 sm:flex sm:items-center sm:gap-3">
          <span class="text-base sm:min-w-24 sm:text-lg">{{ currentTypeMeta.schedule_label }}:</span>
          <input
            v-if="entry.form.visit_type === 'maintenance_harian'"
            :value="entry.form.date_value"
            type="date"
            class="w-full max-w-[220px] rounded border border-slate-400 bg-white px-3 py-2 text-sm text-slate-900 sm:max-w-none"
            @input="$emit('update-date', $event.target.value)"
          />
          <input
            v-else
            :value="entry.form.period_value"
            type="week"
            class="w-full max-w-[220px] rounded border border-slate-400 bg-white px-3 py-2 text-sm text-slate-900 sm:max-w-none"
            @input="$emit('update-period', $event.target.value)"
          />
        </div>

        <div class="grid grid-cols-[72px_minmax(0,1fr)] items-center gap-2 sm:flex sm:items-center sm:gap-3">
          <span class="text-base sm:min-w-24 sm:text-lg">Area:</span>
          <select
            v-if="entry.form.visit_type === 'maintenance_harian'"
            :value="entry.form.selected_area"
            class="w-full max-w-[240px] rounded border border-slate-400 bg-white px-3 py-2 text-sm font-normal text-slate-900 sm:max-w-none"
            @change="$emit('update-area', $event.target.value)"
          >
            <option
              v-for="area in dailyAreaOptions"
              :key="area.id"
              :value="area.id"
            >
              {{ area.name }}
            </option>
          </select>
          <div v-else class="text-sm font-normal text-slate-900">
            Lantai 1 Belakang
          </div>
        </div>
      </div>

      <div class="flex flex-col gap-1">
        <div class="flex flex-wrap items-start gap-2">
          <button
            v-if="showQrScanner"
            type="button"
            class="w-[132px] rounded px-4 py-2 text-sm font-semibold transition"
            :disabled="!canScanBarcode"
            :class="!canScanBarcode
              ? 'cursor-not-allowed bg-slate-300 text-slate-500 hover:bg-slate-300'
              : 'bg-sky-600 text-white hover:bg-sky-500'"
            @click="$emit('scan-barcode')"
          >
            QRCode
          </button>
          <div v-else class="flex h-10 w-[132px] items-center text-xs text-slate-600">
            Mode tanpa QRCode aktif.
          </div>

          <button
            type="button"
            :disabled="!canApproveEntry"
            class="w-[96px] rounded px-4 py-2 text-sm font-semibold transition"
            :class="canApproveEntry
              ? 'bg-amber-500 text-white hover:bg-amber-400'
              : 'cursor-not-allowed bg-slate-300 text-slate-500'"
            @click="$emit('approve')"
          >
            Approval
          </button>
        </div>

        <div class="max-w-[132px] text-xs text-slate-600">
          {{ showQrScanner ? (currentBarcode || 'QRCode area aktif belum discan.') : 'Approve dapat langsung dilakukan.' }}
        </div>
      </div>
    </div>

    <div class="border border-black">
      <table class="w-full table-fixed border-collapse text-xs sm:text-sm sm:table-auto">
        <thead>
          <tr class="bg-slate-100">
            <th class="w-10 border border-black px-1 py-2 text-center sm:w-12 sm:px-2">No</th>
            <th class="border border-black px-2 py-2 text-center sm:min-w-[420px]">ITEM</th>
            <th class="w-[84px] border border-black px-1 py-2 text-center text-[11px] leading-tight whitespace-normal sm:min-w-[220px] sm:px-2 sm:text-sm">
              Kondisi
            </th>
          </tr>
        </thead>
        <tbody>
          <template v-if="entry.form.visit_type === 'maintenance_harian'">
            <template
              v-for="section in sections"
              :key="section.id"
            >
              <tr class="bg-slate-50">
                <td colspan="3" class="border border-black px-2 py-2 text-sm font-bold sm:text-base">
                  {{ section.title }}
                </td>
              </tr>
              <tr
                v-for="item in section.items"
                :key="item.id"
              >
                <td class="border border-black px-1 py-1 text-center align-top sm:px-2">{{ item.no }}</td>
                <td class="border border-black px-2 py-1 leading-snug break-words">{{ item.name }}</td>
                <td class="border border-black p-0 text-center">
                  <button
                    type="button"
                    class="flex h-10 w-full items-center justify-center text-lg font-semibold leading-none sm:h-11 sm:text-xl"
                    @click="$emit('cycle-row-status', { sectionId: section.id, rowId: item.id })"
                  >
                    <span v-if="item.status === 'yes'">&#10003;</span>
                    <span v-else-if="item.status === 'no'" class="text-rose-600">&#10005;</span>
                  </button>
                </td>
              </tr>
            </template>
          </template>

          <template v-else>
            <tr
              v-for="row in rows"
              :key="row.id"
            >
              <td class="border border-black px-1 py-1 text-center align-top sm:px-2">{{ row.no }}</td>
              <td class="border border-black px-2 py-1 leading-snug break-words">{{ row.name }}</td>
              <td class="border border-black p-0 text-center">
                <button
                  type="button"
                  class="flex h-10 w-full items-center justify-center text-lg font-semibold leading-none sm:h-11 sm:text-xl"
                  @click="$emit('cycle-row-status', { rowId: row.id })"
                >
                  <span v-if="row.status === 'yes'">&#10003;</span>
                  <span v-else-if="row.status === 'no'" class="text-rose-600">&#10005;</span>
                </button>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <div class="mt-4 rounded border border-slate-300 bg-slate-50 p-3">
      <div class="mb-2 text-sm font-semibold">{{ noteLabel }}</div>
      <textarea
        :value="note"
        rows="4"
        class="w-full rounded border border-slate-400 bg-slate-100 px-3 py-2 text-sm text-slate-900"
        placeholder="Isi catatan / temuan untuk area atau frekuensi aktif..."
        @input="$emit('update-note', $event.target.value)"
      ></textarea>
      <div class="mt-2 text-xs text-slate-600">
        Isi catatan ini jika ada item bertanda silang.
      </div>
    </div>

    <div class="mt-4 rounded border border-slate-300 bg-slate-50 p-3">
      <div class="mb-2 flex items-center justify-between gap-3">
        <div class="text-sm font-semibold">Foto Area</div>
        <div class="text-xs text-slate-600">{{ currentPhotos.length }} foto</div>
      </div>

      <div class="flex flex-col gap-3">
        <button
          type="button"
          class="inline-flex w-fit items-center rounded px-4 py-2 text-sm font-semibold transition"
          :disabled="photoUploading"
          :class="photoUploading
            ? 'cursor-not-allowed bg-slate-300 text-slate-500'
            : 'bg-sky-600 text-white hover:bg-sky-500'"
          @click="$emit('open-camera')"
        >
          {{ photoUploading ? 'Uploading...' : 'Ambil Foto' }}
        </button>

        <div v-if="currentPhotos.length" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
          <div
            v-for="(photo, index) in currentPhotos"
            :key="`${photo.path || photo.url || 'photo'}-${index}`"
            class="overflow-hidden rounded border border-slate-300 bg-white p-2"
          >
            <button
              type="button"
              class="block w-full"
              @click="openPhotoPreview(photo, index)"
            >
              <img
                :src="photo.url"
                :alt="photo.name || `Foto maintenance ${index + 1}`"
                class="h-40 w-full rounded object-cover"
              />
            </button>
            <div class="mt-2 flex items-start justify-between gap-2">
              <div class="min-w-0 text-xs text-slate-600">
                <div class="truncate">{{ photo.name || `Foto ${index + 1}` }}</div>
              </div>
              <button
                type="button"
                class="shrink-0 text-xs font-semibold text-rose-600 hover:text-rose-500"
                @click="$emit('remove-photo', index)"
              >
                Hapus
              </button>
            </div>
          </div>
        </div>

        <div v-if="photoError" class="rounded border border-rose-300 bg-rose-50 px-3 py-2 text-xs text-rose-700">
          {{ photoError }}
        </div>

        <div class="text-xs text-slate-600">
          Foto akan langsung dibuka dari kamera lalu di-upload ke server.
        </div>
      </div>
    </div>

    <div
      v-if="previewPhoto"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
      @click.self="closePhotoPreview"
    >
      <div class="w-full max-w-5xl rounded-xl bg-slate-900 p-4 shadow-2xl">
        <div class="mb-4 flex items-center justify-between gap-3">
          <div class="min-w-0">
            <h3 class="truncate text-lg font-semibold text-white">{{ previewPhoto.name || 'Foto Maintenance' }}</h3>
          </div>
          <button
            type="button"
            class="rounded bg-slate-700 px-3 py-2 text-sm text-white hover:bg-slate-600"
            @click="closePhotoPreview"
          >
            Close
          </button>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-700 bg-black">
          <img
            :src="previewPhoto.url"
            :alt="previewPhoto.name || 'Foto Maintenance'"
            class="max-h-[72vh] w-full object-contain"
          />
        </div>

        <div class="mt-4 flex justify-end">
          <a
            :href="previewPhoto.url"
            :download="previewPhoto.name || 'foto-maintenance.jpg'"
            class="rounded bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500"
          >
            Download
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  entry: {
    type: Object,
    required: true,
  },
  typeOptions: {
    type: Array,
    required: true,
  },
  currentTypeMeta: {
    type: Object,
    required: true,
  },
  dailyAreaOptions: {
    type: Array,
    default: () => [],
  },
  sections: {
    type: Array,
    default: () => [],
  },
  rows: {
    type: Array,
    default: () => [],
  },
  note: {
    type: String,
    default: '',
  },
  noteLabel: {
    type: String,
    default: 'Keterangan',
  },
  currentBarcode: {
    type: String,
    default: '',
  },
  showQrScanner: {
    type: Boolean,
    default: true,
  },
  canScanBarcode: {
    type: Boolean,
    required: true,
  },
  canApproveEntry: {
    type: Boolean,
    required: true,
  },
  currentPhotos: {
    type: Array,
    default: () => [],
  },
  photoUploading: {
    type: Boolean,
    default: false,
  },
  photoError: {
    type: String,
    default: '',
  },
});

const previewPhoto = ref(null);

function openPhotoPreview(photo, index) {
  if (!photo?.url) {
    return;
  }

  previewPhoto.value = {
    ...photo,
    name: photo.name || `Foto maintenance ${Number(index) + 1}`,
  };
}

function closePhotoPreview() {
  previewPhoto.value = null;
}

defineEmits([
  'approve',
  'update-type',
  'update-date',
  'update-period',
  'update-area',
  'scan-barcode',
  'cycle-row-status',
  'update-note',
  'open-camera',
  'remove-photo',
]);
</script>
