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
              <div class="text-xl font-bold leading-tight">CHECKLIST SITE VISIT HSE</div>
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
      <div class="flex flex-col gap-3 text-lg font-semibold lg:flex-row lg:items-center">
        <div class="flex items-center gap-3">
          <span class="min-w-24">Tanggal:</span>
          <input
            :value="entry.form.date_value"
            type="date"
            class="rounded border border-slate-400 bg-white px-3 py-2 text-sm text-slate-900"
            @input="$emit('update-date', $event.target.value)"
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
            Scan Barcode
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
          {{ currentBarcode || 'Barcode area aktif belum discan.' }}
        </div>
      </div>
    </div>

    <div class="overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <thead>
          <tr class="bg-slate-100">
            <th class="w-12 border border-black px-2 py-2 text-center">No</th>
            <th class="min-w-[420px] border border-black px-2 py-2 text-center">ITEM</th>
            <th class="min-w-[180px] border border-black px-2 py-2 text-center">Kondisi</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="currentSection">
            <tr class="bg-slate-50">
              <td colspan="3" class="border border-black px-2 py-2 text-base font-bold">
                {{ currentSection.title }}
              </td>
            </tr>
            <tr
              v-for="item in currentSection.items"
              :key="item.id"
            >
              <td class="border border-black px-2 py-1 text-center">{{ item.no }}</td>
              <td class="border border-black px-2 py-1">{{ item.name }}</td>
              <td class="border border-black p-0 text-center">
                <button
                  type="button"
                  class="flex h-10 w-full items-center justify-center text-lg font-semibold"
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
  </div>
</template>

<script setup>
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
});

defineEmits(['approve', 'scan-barcode', 'update-date', 'update-area', 'cycle-row-status', 'update-note']);
</script>
