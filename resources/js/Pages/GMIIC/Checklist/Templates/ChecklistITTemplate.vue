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
              <div class="text-xl font-bold leading-tight">CHECKLIST IT</div>
            </td>
            <td class="border border-black p-0 align-top">
              <table class="min-w-full border-collapse text-sm">
                <tbody>
                  <tr>
                    <td class="w-40 border border-black px-2 py-1">Doc. No.</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.document_no || 'FRM.IT.01.01' }}</td>
                  </tr>
                  <tr>
                    <td class="border border-black px-2 py-1">Rev.</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.rev || '00' }}</td>
                  </tr>
                  <tr>
                    <td class="border border-black px-2 py-1">Tanggal Efektif</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.effective_date || '-' }}</td>
                  </tr>
                  <tr>
                    <td class="border border-black px-2 py-1">Halaman</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.page || 'Page 1 dari 1' }}</td>
                  </tr>
                </tbody>
              </table>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mb-4 rounded border border-black bg-slate-50 p-4">
      <div class="grid gap-4 lg:grid-cols-[1.2fr_1fr_1fr] lg:items-end">
        <div class="grid items-center gap-3 sm:grid-cols-[auto_minmax(0,1fr)]">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-600">Minggu</div>
          <input
            :value="entry.form.week_value || ''"
            type="week"
            class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:outline-none"
            @input="$emit('update-week', $event.target.value)"
          />
        </div>

        <div class="grid items-center gap-3 sm:grid-cols-[auto_minmax(0,1fr)]">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-600">PIC</div>
          <input
            :value="entry.form.pic"
            type="text"
            readonly
            class="w-full rounded border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-600 focus:outline-none"
          />
        </div>

        <div class="grid items-center gap-3 sm:grid-cols-[auto_minmax(0,1fr)]">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-600">Checklist</div>
          <select
            :value="entry.form.check_type || 'hardware'"
            class="w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:outline-none"
            @change="$emit('update-check-type', $event.target.value)"
          >
            <option value="hardware">Hardware</option>
            <option value="network">Network</option>
            <option value="cctv">CCTV</option>
            <option value="nvr">NVR</option>
            <option value="software">Software</option>
          </select>
        </div>
      </div>
    </div>

      <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
      <div class="text-sm text-slate-700">
        <div class="font-semibold">Status checklist aktif</div>
        <div class="text-slate-600">{{ currentCheckLabel }}</div>
      </div>
      <button
        type="button"
        :disabled="!isApproverEnabled"
        :class="isApproverEnabled
          ? 'rounded bg-amber-500 px-5 py-2 text-sm font-semibold text-white transition hover:bg-amber-400'
          : 'rounded px-5 py-2 text-sm font-semibold text-slate-700 bg-slate-300 cursor-not-allowed'"
        @click="$emit('approve')"
      >
        Approval
      </button>
    </div>

    <div class="rounded border border-slate-300 bg-slate-50 p-3">
      <div class="mb-3 text-sm font-semibold">Daftar pemeriksaan</div>
      <div class="overflow-x-auto">
        <table class="min-w-full table-auto border-collapse text-sm">
          <thead>
            <tr>
              <th class="w-12 border border-black bg-slate-100 px-3 py-2 text-left">No</th>
              <th class="border border-black bg-slate-100 px-3 py-2 text-left">Asset Code - Asset Name - User - Asset Type - Location</th>
              <th class="w-36 border border-black bg-slate-100 px-3 py-2 text-center">Power/Physical/Function</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in itemsList" :key="row.id" class="odd:bg-white even:bg-slate-50">
              <td class="border border-black px-3 py-2 align-top">{{ row.no }}</td>
              <td class="border border-black px-3 py-2 align-top">{{ row.name }}</td>
              <td class="border border-black p-0 text-center">
                <button
                  type="button"
                  class="flex h-10 w-full items-center justify-center text-lg font-semibold leading-none sm:h-11 sm:text-xl"
                  @click="() => emitCycle(row)"
                >
                  <span v-if="row.status === 'ok'">✓</span>
                  <span v-else-if="row.status === 'noted'" class="text-rose-600">✕</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-4 rounded border border-slate-300 bg-slate-50 p-3">
      <div class="mb-2 text-sm font-semibold">Catatan / Temuan</div>
      <textarea
        :value="entry.form.note || ''"
        rows="4"
        class="w-full rounded border border-slate-400 bg-slate-100 px-3 py-2 text-sm text-slate-900"
        placeholder="Isi catatan jika ada temuan..."
        @input="$emit('update-note', $event.target.value)"
      ></textarea>
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
                :alt="photo.name || `Foto checklist IT ${index + 1}`"
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
            <h3 class="truncate text-lg font-semibold text-white">{{ previewPhoto.name || 'Foto Checklist IT' }}</h3>
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
            :alt="previewPhoto.name || 'Foto Checklist IT'"
            class="max-h-[72vh] w-full object-contain"
          />
        </div>

        <div class="mt-4 flex justify-end">
          <a
            :href="previewPhoto.url"
            :download="previewPhoto.name || 'foto-checklist-it.jpg'"
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
import { computed, ref } from 'vue'

// checklistItems removed: using entry.form.check_items instead

const props = defineProps({
  entry: {
    type: Object,
    required: true,
  },
  canApproveEntry: {
    type: [Boolean, Object],
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
})

const emit = defineEmits(['approve', 'update-week', 'update-check-type', 'update-check-item', 'update-note', 'open-camera', 'remove-photo'])

const currentCheckLabel = computed(() => {
  const checkType = String(props.entry?.form?.check_type || 'hardware').trim()
  const labelMap = {
    hardware: 'Hardware',
    network: 'Network',
    cctv: 'CCTV',
    nvr: 'NVR',
    software: 'Software',
  }

  return `Jenis checklist aktif: ${labelMap[checkType] || 'Hardware'}`
})

const itemsList = computed(() => {
  const map = props.entry?.form?.check_items || {}
  return Object.keys(map).map((k) => ({ id: k, ...map[k] })).sort((a, b) => (Number(a.no) || 0) - (Number(b.no) || 0))
})

const previewPhoto = ref(null)

const isApproverEnabled = computed(() => {
  const v = props.canApproveEntry
  if (v && typeof v === 'object' && Object.prototype.hasOwnProperty.call(v, 'value')) return Boolean(v.value)
  return Boolean(v)
})

function emitCycle(row) {
  const current = String(row.status || '')
  const next = current === 'ok' ? 'noted' : current === 'noted' ? '' : 'ok'
  emit('update-check-item', row.id, next)
}

function openPhotoPreview(photo) {
  previewPhoto.value = photo
}

function closePhotoPreview() {
  previewPhoto.value = null
}
</script>
