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
              <div class="text-xl font-bold leading-tight">JADWAL CLEANING OB</div>
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
          <span class="text-base sm:min-w-24 sm:text-lg">Shift:</span>
          <select
            :value="entry.form.selected_shift"
            class="w-full max-w-[280px] rounded border border-slate-400 bg-white px-3 py-2 text-sm font-normal text-slate-900 sm:max-w-none"
            @change="$emit('update-shift', $event.target.value)"
          >
            <option
              v-for="shift in shiftOptions"
              :key="shift.id"
              :value="shift.id"
            >
              {{ shift.name }}
            </option>
          </select>
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
          {{ showQrScanner ? (currentBarcode || 'QRCode shift aktif belum discan.') : 'Approve dapat langsung dilakukan.' }}
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
          <template v-if="currentSections.length">
            <template
              v-for="section in currentSections"
              :key="section.id"
            >
              <tr>
                <td colspan="3" class="border border-black bg-slate-50 px-2 py-2 text-sm font-bold sm:text-base">
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
                  :disabled="approvedAreas.includes(entry.form.selected_shift)"
                  @click="$emit('cycle-row-status', section.id, item.id)"
                >
                  <span v-if="item.status === 'yes'">✓</span>
                  <span v-else-if="item.status === 'no'" class="text-rose-600">✕</span>
                </button>
              </td>
            </tr>
          </template>
          </template>
          <tr v-else>
            <td colspan="3" class="border border-black px-2 py-4 text-center text-slate-500">
              Pilih shift terlebih dahulu.
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
        placeholder="Isi catatan / temuan untuk shift aktif..."
        @input="$emit('update-note', $event.target.value)"
      ></textarea>
      <div class="mt-2 text-xs text-slate-600">
        Isi catatan ini jika ada item bertanda silang.
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  entry: {
    type: Object,
    required: true,
  },
  shiftOptions: {
    type: Array,
    required: true,
  },
  currentSections: {
    type: Array,
    default: () => [],
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
})

defineEmits(['approve', 'scan-barcode', 'update-date', 'update-shift', 'cycle-row-status', 'update-note'])
</script>
