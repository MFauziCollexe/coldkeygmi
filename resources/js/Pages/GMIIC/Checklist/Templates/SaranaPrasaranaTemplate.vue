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
              <div class="text-xl font-bold leading-tight">CHECKLIST</div>
              <div class="text-xl font-bold leading-tight">SARANA DAN PRASARANA</div>
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

    <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
      <div class="flex flex-col gap-3 text-lg font-semibold sm:flex-row sm:items-center">
        <div class="flex items-center gap-3">
          <span class="min-w-24">Periode:</span>
          <input
            :value="entry.form.period"
            type="month"
            class="rounded border border-slate-400 bg-white px-3 py-2 text-sm text-slate-900"
            @input="$emit('update-period', $event.target.value)"
          />
        </div>

        <div class="flex items-center gap-3">
          <span class="min-w-24">Area:</span>
          <select
            :value="entry.form.selected_area"
            class="rounded border border-slate-400 bg-white px-3 py-2 text-sm font-normal text-slate-900"
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

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <button
          v-if="showQrScanner"
          type="button"
          :disabled="!canScanArea"
          class="rounded px-4 py-2 text-sm font-semibold transition"
          :class="canScanArea
            ? 'bg-sky-600 text-white hover:bg-sky-500'
            : 'cursor-not-allowed bg-slate-300 text-slate-500'"
          @click="$emit('scan-area')"
        >
          Scan Area
        </button>
        <div v-else class="text-xs text-slate-600">
          Mode tanpa QRCode aktif.
        </div>

        <ApprovalButton
          :is-ready="localSaranaPrasaranaApprovalReady"
          :disabled="!localSaranaPrasaranaApprovalReady"
          label="Approval"
          button-class="w-[96px]"
          :tooltip="localSaranaPrasaranaApprovalReady ? 'Approval siap' : 'Lengkapi semua isian atau isi catatan jika ada silang.'"
          @click="handleApproveClick"
        />
      </div>
    </div>

    <div class="overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <thead>
          <tr class="bg-slate-100">
            <th class="w-12 border border-black px-2 py-2 text-center">No</th>
            <th class="min-w-[360px] border border-black px-2 py-2 text-center">ITEM</th>
            <th
              v-for="day in days"
              :key="day.key"
              class="w-12 border border-black px-2 py-2 text-center"
              :class="day.isSunday ? 'bg-red-600 text-white' : approvedDays.includes(day.day) ? 'bg-emerald-200' : ''"
            >
              {{ day.day }}
            </th>
          </tr>
        </thead>
        <tbody>
          <template v-if="currentSection">
            <tr class="bg-slate-50">
              <td colspan="100" class="border border-black px-2 py-2 text-base font-bold">
                {{ currentSection.title }}
              </td>
            </tr>
            <tr
              v-for="item in currentSection.items"
              :key="item.id"
            >
              <td class="border border-black px-2 py-1 text-center">{{ item.no }}</td>
              <td class="border border-black px-2 py-1">{{ item.name }}</td>
              <td
                v-for="day in days"
                :key="`${item.id}-${day.day}`"
                class="border border-black p-0 text-center"
                :class="day.isSunday ? 'bg-red-600' : approvedDays.includes(day.day) ? 'bg-emerald-100' : ''"
              >
                <button
                  v-if="!day.isSunday"
                  type="button"
                  class="flex h-9 w-9 items-center justify-center text-base font-semibold"
                  :disabled="approvedDays.includes(day.day)"
                  @click="$emit('cycle-day', currentSection.id, item.id, day.day)"
                >
                  <span v-if="item.days?.[day.day] === 'yes'">✓</span>
                  <span v-else-if="item.days?.[day.day] === 'no'" class="text-rose-600">✕</span>
                </button>
                <div v-else class="h-9 w-9"></div>
              </td>
            </tr>
          </template>
          <tr v-else>
            <td colspan="100" class="border border-black px-2 py-4 text-center text-slate-500">
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
                :alt="photo.name || `Foto sarana prasarana ${index + 1}`"
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
            <h3 class="truncate text-lg font-semibold text-white">{{ previewPhoto.name || 'Foto Sarana dan Prasarana' }}</h3>
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
            :alt="previewPhoto.name || 'Foto Sarana dan Prasarana'"
            class="max-h-[72vh] w-full object-contain"
          />
        </div>

        <div class="mt-4 flex justify-end">
          <a
            :href="previewPhoto.url"
            :download="previewPhoto.name || 'foto-sarana-prasarana.jpg'"
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
import { computed, ref } from 'vue';
import ApprovalButton from '../Components/ApprovalButton.vue';

const props = defineProps({
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
  days: {
    type: Array,
    required: true,
  },
  approvedDays: {
    type: Array,
    required: true,
  },
  currentAreaScan: {
    type: Object,
    default: null,
  },
  nextPendingDay: {
    type: Object,
    default: null,
  },
  showQrScanner: {
    type: Boolean,
    default: true,
  },
  canScanArea: {
    type: Boolean,
    required: true,
  },
  canApproveEntry: {
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
  isAreaApproved: {
    type: Boolean,
    default: false,
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

const localSaranaPrasaranaApprovalReady = computed(() => {
  if (!props.entry || props.entry.template_id !== 'sarana_dan_prasarana') return false
  const selectedArea = String(props.entry.form.selected_area || '').trim()
  if (!selectedArea || !String(props.entry.form.period || '').trim()) return false
  if (!props.nextPendingDay) return false

  const items = props.currentSection?.items || []
  if (!items.length) return false

  const pendingDay = props.nextPendingDay.day
  const statuses = items.map((item) => String(item.days?.[pendingDay] || '').trim())
  const allAnswersFilled = statuses.every((status) => status === 'yes' || status === 'no')
  if (!allAnswersFilled) return false

  const hasNoAnswer = statuses.includes('no')
  const hasRequiredNote = String(props.note || '').trim() !== ''
  if (hasNoAnswer && !hasRequiredNote) return false

  if (props.showQrScanner && !String(props.currentAreaScan?.barcode || '').trim()) return false
  return true
})

const previewPhoto = ref(null);

function handleApproveClick() {
  if (localSaranaPrasaranaApprovalReady.value) {
    emit('approve')
  }
}

function openPhotoPreview(photo, index) {
  if (!photo?.url) return;
  previewPhoto.value = {
    ...photo,
    name: photo.name || `Foto sarana prasarana ${Number(index) + 1}`,
  };
}

function closePhotoPreview() {
  previewPhoto.value = null;
}

const emit = defineEmits(['approve', 'update-period', 'update-area', 'cycle-day', 'scan-area', 'update-note', 'open-camera', 'remove-photo']);
</script>
