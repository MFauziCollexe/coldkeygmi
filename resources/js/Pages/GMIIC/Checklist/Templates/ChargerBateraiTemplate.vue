<template>
  <div class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
    <div class="mb-5 overflow-x-auto border border-black">
      <table class="w-full table-fixed border-collapse text-xs sm:text-sm">
        <tbody>
          <tr>
            <td colspan="3" class="border border-black px-3 py-3 text-center text-lg font-bold sm:text-2xl">
              CHECKLIST CHARGER BATERAI
            </td>
          </tr>
          <tr>
            <td colspan="2" class="border border-black px-2 py-2">
              <div class="grid grid-cols-[80px_20px_minmax(0,1fr)] items-center gap-2">
                <span class="font-semibold">SN</span>
                <span>:</span>
                <input
                  :value="entry.form.serial_no"
                  type="text"
                  class="w-full border-0 bg-transparent text-sm text-slate-900 focus:outline-none focus:ring-0"
                  :disabled="entry.form.approved"
                  placeholder="Isi serial number"
                  @input="$emit('update-field', 'serial_no', $event.target.value)"
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
          <span class="text-base sm:min-w-24 sm:text-lg">PIC:</span>
          <div class="text-sm font-normal text-slate-900">{{ entry.form.pic }}</div>
        </div>
      </div>

      <button
        type="button"
        :disabled="!canApproveEntry"
        class="w-[104px] rounded px-4 py-2 text-sm font-semibold transition"
        :class="canApproveEntry
          ? 'bg-amber-500 text-white hover:bg-amber-400'
          : 'cursor-not-allowed bg-slate-300 text-slate-500'"
        @click="handleApproveClick"
      >
        Approval
      </button>
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
                  <span v-else-if="activeRow?.[item.key] === 'minus'" class="text-slate-600">-</span>
                </button>
                <input
                  v-else
                  :value="activeRow?.[item.key] || ''"
                  type="text"
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
        :disabled="entry.form.approved"
        placeholder="Isi catatan jika ada item bertanda silang."
        @input="$emit('update-note', $event.target.value)"
      ></textarea>
      <div class="mt-2 text-xs text-slate-600">
        Catatan wajib diisi bila ada item dengan tanda silang.
      </div>
    </div>
  </div>
</template>

<script setup>
const sections = [
  {
    id: 'pengecekan',
    title: 'A. PENGECEKAN',
    items: [
      { no: 1, key: 'switch_on_off', label: 'SWITCH ON/OF', type: 'symbol' },
      { no: 2, key: 'kondisi_fisik', label: 'KONDISI FISIK', type: 'symbol' },
      { no: 3, key: 'kabel_konektor', label: 'KABEL & KONEKTOR', type: 'symbol' },
      { no: 4, key: 'legrand', label: 'LEGRAND', type: 'symbol' },
      { no: 5, key: 'display_charger', label: 'DISPLAY CHARGER', type: 'symbol' },
    ],
  },
  {
    id: 'temuan',
    title: 'B. TEMUAN',
    items: [{ no: 1, key: 'temuan', label: 'TEMUAN', type: 'symbol' }],
  },
  {
    id: 'tindakan',
    title: 'C. TINDAKAN',
    items: [{ no: 1, key: 'tindakan', label: 'TINDAKAN', type: 'text' }],
  },
]

const props = defineProps({
  entry: { type: Object, required: true },
  rows: { type: Array, required: true },
  activeRow: { type: Object, default: null },
  activeDay: { type: Number, default: 1 },
  isActiveDayApproved: { type: Boolean, default: false },
  note: { type: String, default: '' },
  canApproveEntry: { type: Boolean, required: true },
  approvedDays: { type: Array, default: () => [] },
})

const emit = defineEmits([
  'approve',
  'update-field',
  'update-row-field',
  'cycle-row-symbol',
  'set-active-day',
  'update-note',
])

function handleApproveClick() {
  if (props.canApproveEntry) emit('approve')
}
</script>
