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
            <td class="w-[420px] border border-black px-3 py-3 text-center">
              <div class="text-xl font-bold leading-tight">{{ currentTypeMeta.title }}</div>
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

    <div class="mb-5 grid gap-3 xl:grid-cols-[max-content_max-content_minmax(0,1fr)] xl:grid-rows-2 xl:items-start xl:gap-x-4">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center xl:flex-nowrap">
        <span class="min-w-24 text-lg font-semibold">Frekuensi:</span>
        <select
          :value="entry.form.visit_type"
          class="rounded border border-slate-400 bg-white px-3 py-2 text-sm text-slate-900 xl:w-[230px]"
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

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center xl:flex-nowrap">
        <span class="min-w-16 text-lg font-semibold">Area:</span>
        <select
          v-if="entry.form.visit_type === 'maintenance_harian'"
          :value="entry.form.selected_area"
          class="rounded border border-slate-400 bg-white px-3 py-2 text-sm text-slate-900 xl:w-[200px]"
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
        <div v-else class="px-3 py-2 text-sm font-semibold text-slate-900">
          Lantai 1 Belakang
        </div>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-start xl:row-span-2 xl:min-w-0 xl:justify-self-end">
        <div class="flex min-w-0 flex-col gap-1 xl:w-[118px]">
          <button
            type="button"
            class="w-full rounded px-4 py-2 text-sm font-semibold transition"
            :disabled="!canScanBarcode"
            :class="!canScanBarcode
              ? 'cursor-not-allowed bg-slate-300 text-slate-500 hover:bg-slate-300'
              : 'bg-sky-600 text-white hover:bg-sky-500'"
            @click="$emit('scan-barcode')"
          >
            QRCode
          </button>
          <div class="text-xs text-slate-600">
            {{ currentBarcode || 'QRCode area aktif belum discan.' }}
          </div>
        </div>

        <div class="xl:w-[118px]">
          <button
            type="button"
            :disabled="!canApproveEntry"
            class="w-full rounded px-4 py-2 text-sm font-semibold transition"
            :class="canApproveEntry
              ? 'bg-amber-500 text-white hover:bg-amber-400'
              : 'cursor-not-allowed bg-slate-300 text-slate-500'"
            @click="$emit('approve')"
          >
            Approval
          </button>
        </div>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center xl:col-start-1 xl:row-start-2 xl:flex-nowrap">
        <span class="min-w-24 text-lg font-semibold">{{ currentTypeMeta.schedule_label }}:</span>
        <input
          v-if="entry.form.visit_type === 'maintenance_harian'"
          :value="entry.form.date_value"
          type="date"
          class="rounded border border-slate-400 bg-white px-3 py-2 text-sm text-slate-900 xl:w-[136px]"
          @input="$emit('update-date', $event.target.value)"
        />
        <input
          v-else
          :value="entry.form.period_value"
          type="week"
          class="rounded border border-slate-400 bg-white px-3 py-2 text-sm text-slate-900 xl:w-[180px]"
          @input="$emit('update-period', $event.target.value)"
        />
      </div>
    </div>

    <div class="overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <thead>
          <tr class="bg-slate-100">
            <th class="w-12 border border-black px-2 py-2 text-center">No</th>
            <th class="min-w-[520px] border border-black px-2 py-2 text-center">ITEM</th>
            <th class="min-w-[140px] border border-black px-2 py-2 text-center">Kondisi</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="entry.form.visit_type === 'maintenance_harian'">
            <template
              v-for="section in sections"
              :key="section.id"
            >
              <tr class="bg-slate-50">
                <td colspan="3" class="border border-black px-2 py-2 text-base font-bold">
                  {{ section.title }}
                </td>
              </tr>
              <tr
                v-for="item in section.items"
                :key="item.id"
              >
                <td class="border border-black px-2 py-1 text-center">{{ item.no }}</td>
                <td class="border border-black px-2 py-1">{{ item.name }}</td>
                <td class="border border-black p-0 text-center">
                  <button
                    type="button"
                    class="flex h-10 w-full items-center justify-center text-lg font-semibold"
                    @click="$emit('cycle-row-status', { sectionId: section.id, rowId: item.id })"
                  >
                    <span v-if="item.status === 'yes'">✓</span>
                    <span v-else-if="item.status === 'no'" class="text-rose-600">✕</span>
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
              <td class="border border-black px-2 py-1 text-center">{{ row.no }}</td>
              <td class="border border-black px-2 py-1">{{ row.name }}</td>
              <td class="border border-black p-0 text-center">
                <button
                  type="button"
                  class="flex h-10 w-full items-center justify-center text-lg font-semibold"
                  @click="$emit('cycle-row-status', { rowId: row.id })"
                >
                  <span v-if="row.status === 'yes'">✓</span>
                  <span v-else-if="row.status === 'no'" class="text-rose-600">✕</span>
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
  </div>
</template>

<script setup>
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
  canScanBarcode: {
    type: Boolean,
    required: true,
  },
  canApproveEntry: {
    type: Boolean,
    required: true,
  },
});

defineEmits([
  'approve',
  'update-type',
  'update-date',
  'update-period',
  'update-area',
  'scan-barcode',
  'cycle-row-status',
  'update-note',
]);
</script>
