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
              <div class="text-xl font-bold leading-tight">CHECKLIST INSPEKSI LOKER</div>
            </td>
            <td class="border border-black p-0 align-top">
              <table class="min-w-full border-collapse text-sm">
                <tbody>
                  <tr>
                    <td class="w-40 border border-black px-2 py-1">Doc. No.</td>
                    <td class="border border-black px-2 py-1">{{ entry.form.document_no || 'FRM.HSE.XX.01' }}</td>
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
      <div class="grid gap-4 lg:grid-cols-[1.2fr_1fr_auto] lg:items-end">
        <div class="grid items-center gap-3 sm:grid-cols-[auto_minmax(0,1fr)]">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-600">Bulan</div>
          <input
            :value="entry.form.date_value"
            type="month"
            class="w-full border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:outline-none"
            @input="$emit('update-date', $event.target.value)"
          />
        </div>

        <div class="grid items-center gap-3 sm:grid-cols-[auto_minmax(0,1fr)]">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-600">PIC</div>
          <input
            :value="entry.form.pic"
            type="text"
            readonly
            class="w-full border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-600 focus:outline-none"
          />
        </div>

        <div class="flex justify-end">
          <ApprovalButton
            :is-ready="canApproveEntry"
            :disabled="!canApproveEntry"
            label="Approval"
            button-class="w-[132px]"
            :tooltip="approvalTooltip"
            @click="$emit('approve')"
          />
        </div>
      </div>
    </div>

    <div class="mb-3 flex items-center gap-3 px-3 text-sm text-slate-700">
      <label class="inline-flex cursor-pointer items-center gap-2 font-semibold">
        <input
          type="checkbox"
          :checked="isAllLockersChecked"
          class="h-4 w-4 rounded border-slate-400 text-slate-600 focus:ring-slate-500"
          @change="$emit('toggle-all-lockers')"
        />
        Centang Semua
      </label>
    </div>

    <div class="overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-[11px]">
        <thead>
          <tr class="bg-slate-100 text-left text-[10px] uppercase tracking-wide text-slate-700">
            <th class="sticky left-0 z-10 border border-black bg-slate-100 px-2 py-2">No.</th>
            <th class="sticky left-12 z-10 border border-black bg-slate-100 px-2 py-2 min-w-60">Parameter</th>
            <th v-for="locker in lockerNumbers" :key="locker" class="border border-black px-1 py-2 text-center">{{ locker }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in entry.form.rows" :key="row.key">
            <td class="sticky left-0 z-0 border border-black bg-white px-2 py-2 text-center">{{ row.no }}</td>
            <td class="sticky left-12 z-0 border border-black bg-white px-2 py-2">{{ row.label }}</td>
            <td
              v-for="locker in lockerNumbers"
              :key="`${row.key}-${locker}`"
              class="border border-black px-0 py-0 text-center"
            >
              <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded bg-white text-xs font-semibold leading-none text-slate-800 transition hover:bg-slate-200 focus:outline-none focus-visible:outline-none"
                @click="$emit('cycle-locker-status', row.no, locker)"
              >
                <span v-if="row.lockers?.[String(locker)] === 'yes'">✓</span>
                <span v-else-if="row.lockers?.[String(locker)] === 'no'" class="text-rose-600">✕</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-3 text-xs text-slate-600">
      Klik setiap kotak untuk mengubah status loker: <span class="font-semibold">Ya / Tidak / Kosong</span>.
    </div>
    <div class="mt-4 rounded border border-slate-300 bg-slate-50 p-3">
      <div class="mb-2 text-sm font-semibold">Keterangan</div>
      <textarea
        :value="entry.form.note || ''"
        rows="3"
        class="w-full rounded border border-slate-400 bg-slate-100 px-3 py-2 text-sm text-slate-900"
        placeholder="Isi catatan / temuan jika ada loker yang bertanda silang (✕)..."
        @input="$emit('update-note', $event.target.value)"
      ></textarea>
      <div class="mt-2 text-xs text-slate-600">
        Isi catatan ini jika ada loker yang bertanda silang (✕).
      </div>
    </div>

    <div class="mt-4 rounded border border-slate-300 bg-slate-50 p-3">
      <div class="mb-2 flex items-center justify-between gap-3">
        <div class="text-sm font-semibold">Foto Inspeksi Loker</div>
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
                :alt="photo.name || `Foto inspeksi loker ${index + 1}`"
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
            <h3 class="truncate text-lg font-semibold text-white">{{ previewPhoto.name || 'Foto Inspeksi Loker' }}</h3>
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
            :alt="previewPhoto.name || 'Foto Inspeksi Loker'"
            class="max-h-[72vh] w-full object-contain"
          />
        </div>

        <div class="mt-4 flex justify-end">
          <a
            :href="previewPhoto.url"
            :download="previewPhoto.name || 'foto-inspeksi-loker.jpg'"
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

const lockerNumbers = Array.from({ length: 32 }, (_, index) => index + 1)

const props = defineProps({
  entry: {
    type: Object,
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
})

const requiresNote = computed(() => {
  const rows = Array.isArray(props.entry.form.rows) ? props.entry.form.rows : []
  return rows.some((row) => Object.values(row.lockers || {}).some((value) => String(value || '') === 'no'))
})

const approvalTooltip = computed(() => {
  if (requiresNote.value && !String(props.entry.form.note || '').trim()) {
    return 'Approval memerlukan catatan karena ada loker yang bertanda silang (✕).'
  }
  return 'Approval siap jika semua kondisi terpenuhi.'
})

const previewPhoto = ref(null)

const isAllLockersChecked = computed(() => {
  const rows = Array.isArray(props.entry?.form?.rows) ? props.entry.form.rows : []
  if (!rows.length) return false
  const lockerKeys = rows[0]?.lockers ? Object.keys(rows[0].lockers) : []
  if (!lockerKeys.length) return false
  return rows.every((row) => lockerKeys.every((key) => row.lockers?.[key] === 'yes'))
})

function openPhotoPreview(photo, index) {
  if (!photo?.url) return
  previewPhoto.value = {
    ...photo,
    name: photo.name || `Foto inspeksi loker ${Number(index) + 1}`,
  }
}

function closePhotoPreview() {
  previewPhoto.value = null
}

defineEmits(['approve', 'update-date', 'update-pic', 'update-note', 'cycle-locker-status', 'toggle-all-lockers', 'open-camera', 'remove-photo'])
</script>
