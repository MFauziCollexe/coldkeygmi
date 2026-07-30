<template>
  <div class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
    <div class="mb-5 overflow-x-auto border border-black">
      <table class="w-full table-fixed border-collapse text-xs sm:text-sm">
        <tbody>
          <tr>
            <td colspan="3" class="border border-black px-3 py-3 text-center text-lg font-bold sm:text-2xl">
              CHECKLIST HARIAN KOMPRESOR
            </td>
          </tr>
          <tr>
            <td colspan="2" class="border border-black px-2 py-2">
              <div class="grid grid-cols-[140px_20px_minmax(0,1fr)] items-center gap-2">
                <span class="font-semibold">KOMPRESOR NO</span>
                <span>:</span>
                <input
                  :value="entry.form.compressor_no"
                  type="text"
                  class="w-full border-0 bg-transparent text-sm text-slate-900 focus:outline-none focus:ring-0"
                  :disabled="entry.form.approved"
                  placeholder="Isi nomor kompresor"
                  @input="$emit('update-field', 'compressor_no', $event.target.value)"
                />
              </div>
            </td>
            <td class="border border-black px-3 py-2 text-center font-semibold">
              {{ entry.form.document_no }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
      <div class="flex flex-col gap-3 font-semibold lg:flex-row lg:flex-wrap lg:items-center">
        <div class="grid grid-cols-[100px_minmax(0,1fr)] items-center gap-2 sm:flex sm:items-center sm:gap-3">
          <span class="text-base sm:min-w-24 sm:text-lg">Tanggal:</span>
          <input
            :value="activeRow?.date || ''"
            type="date"
            class="w-full max-w-[220px] rounded border border-slate-400 bg-white px-3 py-2 text-sm text-slate-900"
            :min="rows[0]?.date || undefined"
            :max="rows[rows.length - 1]?.date || undefined"
            @input="$emit('set-active-day', $event.target.value)"
          />
        </div>

        <div class="grid grid-cols-[100px_minmax(0,1fr)] items-center gap-2 sm:flex sm:items-center sm:gap-3">
          <span class="text-base sm:min-w-24 sm:text-lg">Area:</span>
          <div class="text-sm font-normal text-slate-900">{{ entry.form.location }}</div>
        </div>

        <div class="grid grid-cols-[100px_minmax(0,1fr)] items-center gap-2 sm:flex sm:items-center sm:gap-3">
          <span class="text-base sm:min-w-24 sm:text-lg">PIC:</span>
          <div class="text-sm font-normal text-slate-900">{{ entry.form.pic }}</div>
        </div>
      </div>

      <div class="flex flex-col gap-1">
        <div class="flex flex-wrap items-start gap-2">
          <ApprovalButton
            :is-ready="canApproveEntry"
            :disabled="!canApproveEntry"
            label="Approval"
            button-class="w-[96px]"
            tooltip="Approval siap jika semua kondisi terpenuhi"
            @click="handleApproveClick"
          />
        </div>
        <div class="max-w-[220px] text-xs text-slate-600">
          {{ canApproveEntry ? 'Approve dapat langsung dilakukan.' : 'Lengkapi semua isian atau isi catatan jika ada silang.' }}
        </div>
      </div>
    </div>

    <div class="border border-black">
      <table class="w-full table-fixed border-collapse text-xs sm:text-sm sm:table-auto">
        <thead>
          <tr class="bg-slate-100">
            <th class="w-10 border border-black px-1 py-2 text-center sm:w-12 sm:px-2">No</th>
            <th class="border border-black px-2 py-2 text-center sm:min-w-[420px]">ITEM</th>
            <th class="w-[150px] border border-black px-1 py-2 text-center text-[11px] leading-tight sm:min-w-[180px] sm:px-2 sm:text-sm">
              Kondisi
            </th>
          </tr>
        </thead>
        <tbody>
          <template v-for="section in sections" :key="section.id">
            <tr class="bg-slate-50">
              <td colspan="3" class="border border-black px-2 py-2 text-sm font-bold sm:text-base">
                {{ section.title }}
              </td>
            </tr>
            <tr v-for="item in section.items" :key="item.key">
              <td class="border border-black px-1 py-1 text-center align-top sm:px-2">{{ item.no }}</td>
              <td class="border border-black px-2 py-1 leading-snug break-words">{{ item.label }}</td>
              <td class="border border-black p-0 text-center">
                <button
                  v-if="item.type === 'symbol'"
                  type="button"
                  :disabled="isActiveDayApproved"
                  class="flex h-10 w-full items-center justify-center text-base font-semibold leading-none sm:h-11 sm:text-lg"
                  @click="$emit('cycle-row-symbol', activeRow?.day, item.key)"
                >
                  <span v-if="activeRow?.[item.key] === 'yes'">&#10003;</span>
                  <span v-else-if="activeRow?.[item.key] === 'no'" class="text-rose-600">&#10005;</span>
                </button>
                <input
                  v-else
                  :value="activeRow?.[item.key] || ''"
                  :type="item.type === 'number' ? 'number' : 'text'"
                  :min="item.type === 'number' ? '0' : undefined"
                  :step="item.type === 'number' ? 'any' : undefined"
                  class="h-10 w-full border-0 bg-transparent px-2 text-center text-sm text-slate-900 focus:outline-none focus:ring-0 sm:h-11"
                  :disabled="isActiveDayApproved"
                  @input="$emit('update-row-field', activeRow?.day, item.key, $event.target.value)"
                />
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <div class="mt-4 rounded border border-slate-300 bg-slate-50 p-3">
      <div class="mb-2 text-sm font-semibold">Catatan / Temuan</div>
      <textarea
        :value="note"
        rows="4"
        class="w-full rounded border border-slate-400 bg-slate-100 px-3 py-2 text-sm text-slate-900"
        :disabled="entry.form.approved || isActiveDayApproved"
        placeholder="Isi catatan jika ada item bertanda silang."
        @input="$emit('update-note', $event.target.value)"
      ></textarea>
      <div class="mt-2 text-xs text-slate-600">
        Catatan wajib diisi bila ada item dengan tanda silang.
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
          :disabled="entry.form.approved || isActiveDayApproved || photoUploading"
          :class="entry.form.approved || isActiveDayApproved || photoUploading
            ? 'cursor-not-allowed bg-slate-300 text-slate-500'
            : 'bg-sky-600 text-white hover:bg-sky-500'"
          @click="onOpenCamera"
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
                :alt="photo.name || `Foto kompresor ${index + 1}`"
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
                :disabled="entry.form.approved || isActiveDayApproved"
                @click="onRemovePhoto(index)"
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
            <h3 class="truncate text-lg font-semibold text-white">{{ previewPhoto.name || 'Foto Kompresor' }}</h3>
          </div>
          <button type="button" class="rounded bg-slate-700 px-3 py-2 text-sm text-white hover:bg-slate-600" @click="closePhotoPreview">Close</button>
        </div>
        <div class="overflow-hidden rounded-lg border border-slate-700 bg-black">
          <img :src="previewPhoto.url" :alt="previewPhoto.name || 'Foto Kompresor'" class="max-h-[72vh] w-full object-contain" />
        </div>
        <div class="mt-4 flex justify-end">
          <a :href="previewPhoto.url" :download="previewPhoto.name || 'foto-kompresor.jpg'" class="rounded bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500">Download</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ApprovalButton from '../Components/ApprovalButton.vue';
const sections = [
  {
    id: 'status_mesin',
    title: 'A. STATUS MESIN',
    items: [{ no: 1, key: 'status_mesin', label: 'ON/OFF', type: 'symbol' }],
  },
  {
    id: 'visual',
    title: 'B. VISUAL',
    items: [
      { no: 1, key: 'visual_bersih', label: 'BERSIH', type: 'symbol' },
      { no: 2, key: 'visual_kotor', label: 'KOTOR', type: 'symbol' },
    ],
  },
  {
    id: 'pengecekan',
    title: 'C. PENGECEKAN',
    items: [
      { no: 1, key: 'tek_suct', label: 'TEK. SUCT (Mpa)', type: 'text' },
      { no: 2, key: 'tek_disch', label: 'TEK DISCH (Mpa)', type: 'text' },
      { no: 3, key: 'delta_tekanan_oli', label: 'DELTA TEKANAN OLI (Mpa)', type: 'text' },
      { no: 4, key: 'check_1', label: 'TEMP SUCT (deg C)', type: 'text' },
      { no: 5, key: 'check_2', label: 'TEMP DISCH (deg C)', type: 'text' },
      { no: 6, key: 'check_3', label: 'TEMP OLI (deg C)', type: 'text' },
      { no: 7, key: 'check_4', label: 'LEVE OLI (%)', type: 'text' },
    ],
  },
  {
    id: 'perlakuan',
    title: 'D. PERLAKUAN',
    items: [
      { no: 1, key: 'tambah_grease_motor', label: 'TAMBAH GREASE MOTOR', type: 'symbol' },
      { no: 2, key: 'tambah_oli', label: 'TAMBAH OLI (LITER)', type: 'number' },
    ],
  },
  {
    id: 'hours_meter',
    title: 'E. HOURS METER',
    items: [{ no: 1, key: 'hours_meter', label: 'HOURS METER', type: 'text' }],
  },
]

const props = defineProps({
  entry: { type: Object, required: true },
  rows: { type: Array, required: true },
  activeRow: { type: Object, default: null },
  activeDay: { type: Number, default: 1 },
  isActiveDayApproved: { type: Boolean, default: false },
  canApproveEntry: { type: Boolean, required: true },
  approvedDays: { type: Array, default: () => [] },
  note: { type: String, default: '' },
  currentPhotos: { type: Array, default: () => [] },
  photoUploading: { type: Boolean, default: false },
  photoError: { type: String, default: '' },
  onOpenCamera: { type: Function, default: () => {} },
  onRemovePhoto: { type: Function, default: () => {} },
})

const emit = defineEmits([
  'approve',
  'update-field',
  'update-row-field',
  'cycle-row-symbol',
  'set-active-day',
  'update-note',
])

const previewPhoto = ref(null)

function openPhotoPreview(photo, index) {
  if (!photo?.url) return
  previewPhoto.value = {
    ...photo,
    name: photo.name || `Foto Kompresor ${Number(index) + 1}`,
  }
}

function closePhotoPreview() {
  previewPhoto.value = null
}

function handleApproveClick() {
  if (props.canApproveEntry) emit('approve')
}
</script>
