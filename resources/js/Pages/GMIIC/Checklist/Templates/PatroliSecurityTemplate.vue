<template>
  <div class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
    <div class="mb-5 overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <tbody>
          <tr>
            <td rowspan="2" class="w-36 border border-black px-3 py-3 text-center">
              <img
                src="/image/logo-gmi-clean.png"
                alt="PT. Golden Multi Indotama"
                class="mx-auto h-16 w-16 object-contain"
              />
            </td>
            <td colspan="2" class="border border-black px-3 py-2 text-center text-2xl font-bold">
              PT GOLDEN MULTI INDOTAMA
            </td>
          </tr>
          <tr>
            <td class="w-80 border border-black px-3 py-3 text-center">
              <div class="text-xl font-bold leading-tight">CHECKLIST PATROLI SECURITY</div>
            </td>
            <td class="border border-black p-0 align-top">
              <table class="min-w-full border-collapse text-sm">
                <tbody>
                  <tr>
                    <td class="w-40 border border-black px-2 py-1">Doc. No.</td>
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
      <div class="flex flex-col gap-3 font-semibold lg:flex-row lg:items-center">
        <div class="grid grid-cols-[72px_minmax(0,1fr)] items-center gap-2 sm:flex sm:items-center sm:gap-3">
          <span class="text-base sm:min-w-24 sm:text-lg">Tanggal:</span>
          <input
            :value="entry.form.date_value"
            type="date"
            class="w-full max-w-[220px] rounded border border-slate-400 bg-white px-3 py-2 text-sm text-slate-900 sm:max-w-none"
            @input="$emit('update-date', $event.target.value)"
          />
        </div>

        <div class="grid grid-cols-[72px_minmax(0,1fr)] items-center gap-2 sm:flex sm:items-center sm:gap-3">
          <span class="text-base sm:min-w-24 sm:text-lg">Area:</span>
          <select
            :value="entry.form.selected_area"
            class="w-full max-w-[240px] rounded border border-slate-400 bg-white px-3 py-2 text-sm font-normal text-slate-900 sm:max-w-none"
            @change="$emit('update-area', $event.target.value)"
          >
            <option
              v-for="area in areaOptions"
              :key="area.id"
              :value="area.id"
            >
              {{ area.name }}
            </option>
          </select>
        </div>
      </div>

      <div class="flex flex-col gap-1">
        <div class="flex flex-wrap items-start gap-2">
          <button
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
          {{ currentBarcode || 'QRCode area aktif belum discan.' }}
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
          <template v-if="currentSection">
            <tr class="bg-slate-50">
              <td colspan="3" class="border border-black px-2 py-2 text-sm font-bold sm:text-base">
                {{ currentSection.title }}
              </td>
            </tr>
            <tr
              v-for="item in currentSection.items"
              :key="item.id"
            >
              <td class="border border-black px-1 py-1 text-center align-top sm:px-2">{{ item.no }}</td>
              <td class="border border-black px-2 py-1 leading-snug break-words">{{ item.name }}</td>
              <td class="border border-black p-0 text-center">
                <button
                  type="button"
                  class="flex h-10 w-full items-center justify-center text-lg font-semibold leading-none sm:h-11 sm:text-xl"
                  :class="item.status && isLandscapeOrientation ? 'transform rotate-90' : ''"
                  :disabled="approvedAreas.includes(entry.form.selected_area)"
                  @click="$emit('cycle-row-status', currentSection.id, item.id)"
                >
                  <span v-if="item.status === 'yes'">✓</span>
                  <span v-else-if="item.status === 'no'" class="text-rose-600">✕</span>
                </button>
              </td>
            </tr>
          </template>
          <tr v-else>
            <td colspan="3" class="border border-black px-2 py-4 text-center text-slate-500">
              Pilih area terlebih dahulu.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 rounded border border-slate-300 bg-slate-50 p-3">
      <div class="mb-2 text-sm font-semibold">{{ noteLabel }}</div>
      <textarea
        :value="note"
        rows="4"
        class="w-full rounded border border-slate-400 bg-slate-100 px-3 py-2 text-sm text-slate-900"
        :disabled="isAreaApproved"
        placeholder="Isi catatan / temuan untuk area aktif..."
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
                :alt="photo.name || `Foto patroli security ${index + 1}`"
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
            <h3 class="truncate text-lg font-semibold text-white">{{ previewPhoto.name || 'Foto Patroli Security' }}</h3>
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
            :alt="previewPhoto.name || 'Foto Patroli Security'"
            class="max-h-[72vh] w-full object-contain"
          />
        </div>

        <div class="mt-4 flex justify-end">
          <a
            :href="previewPhoto.url"
            :download="previewPhoto.name || 'foto-patroli-security.jpg'"
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
import { onBeforeUnmount, onMounted, ref } from 'vue';

defineProps({
  entry: {
    type: Object,
    required: true,
  },
  areaOptions: {
    type: Array,
    required: true,
  },
  currentSection: {
    type: Object,
    default: null,
  },
  approvedAreas: {
    type: Array,
    required: true,
  },
  isAreaApproved: {
    type: Boolean,
    required: true,
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
const isLandscapeOrientation = ref(false);
let orientationMediaQuery = null;

function updateOrientationState() {
  if (typeof window === 'undefined') {
    return;
  }

  if (window.matchMedia) {
    isLandscapeOrientation.value = window.matchMedia('(orientation: landscape)').matches;
    return;
  }

  isLandscapeOrientation.value = window.innerWidth > window.innerHeight;
}

function openPhotoPreview(photo, index) {
  if (!photo?.url) {
    return;
  }

  previewPhoto.value = {
    ...photo,
    name: photo.name || `Foto patroli security ${Number(index) + 1}`,
  };
}

function closePhotoPreview() {
  previewPhoto.value = null;
}

onMounted(() => {
  if (typeof window === 'undefined') {
    return;
  }

  updateOrientationState();

  if (window.matchMedia) {
    orientationMediaQuery = window.matchMedia('(orientation: landscape)');

    if (typeof orientationMediaQuery.addEventListener === 'function') {
      orientationMediaQuery.addEventListener('change', updateOrientationState);
    } else if (typeof orientationMediaQuery.addListener === 'function') {
      orientationMediaQuery.addListener(updateOrientationState);
    }
  }

  window.addEventListener('orientationchange', updateOrientationState);
  window.addEventListener('resize', updateOrientationState);
});

onBeforeUnmount(() => {
  if (orientationMediaQuery) {
    if (typeof orientationMediaQuery.removeEventListener === 'function') {
      orientationMediaQuery.removeEventListener('change', updateOrientationState);
    } else if (typeof orientationMediaQuery.removeListener === 'function') {
      orientationMediaQuery.removeListener(updateOrientationState);
    }
  }

  if (typeof window !== 'undefined') {
    window.removeEventListener('orientationchange', updateOrientationState);
    window.removeEventListener('resize', updateOrientationState);
  }
});

defineEmits(['approve', 'scan-barcode', 'update-date', 'update-area', 'cycle-row-status', 'update-note', 'open-camera', 'remove-photo']);
</script>
