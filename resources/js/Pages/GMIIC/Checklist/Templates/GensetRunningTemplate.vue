<template>
  <div class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
    <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
      <div class="flex items-start gap-4">
        <div class="flex h-16 w-16 items-center justify-center rounded border border-slate-300 bg-white">
          <img
            src="/image/logo-gmi-clean.png"
            alt="PT. Golden Multi Indotama"
            class="h-12 w-12 object-contain"
          />
        </div>

        <div class="space-y-1">
          <div class="text-base font-semibold">PT. GOLDEN MULTI INDOTAMA</div>
          <div class="text-2xl font-bold">CHECKLIST PEMANASAN (RUNNING) GENSET {{ entry.form.year }}</div>
          <div class="text-xs text-slate-600">{{ entry.form.document_no }}</div>
        </div>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
        <div class="flex flex-col gap-1">
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Bulan Aktif</label>
          <select
            :value="activeMonth"
            class="w-40 rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
            @change="$emit('set-active-month', $event.target.value)"
          >
            <option
              v-for="month in months"
              :key="month.key"
              :value="month.key"
            >
              {{ month.label }}
            </option>
          </select>
        </div>

        <div v-if="showQrScanner" class="flex w-[132px] flex-col gap-1">
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
            {{ currentBarcode || `QRCode bulan ${activeMonthLabel} belum discan.` }}
          </div>
        </div>
        <div v-else class="flex w-[132px] flex-col justify-center text-xs text-slate-600">
          Mode tanpa QRCode aktif.
        </div>

        <button
          type="button"
          :disabled="!canApproveEntry"
          class="w-[132px] rounded px-4 py-2 text-sm font-semibold transition"
          :class="canApproveEntry
            ? 'bg-amber-500 text-white hover:bg-amber-400'
            : 'cursor-not-allowed bg-slate-300 text-slate-500'"
          @click="$emit('approve')"
        >
          Approval
        </button>
      </div>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3 text-sm md:grid-cols-4">
      <div>
        <div class="font-semibold">Tanggal</div>
        <div>{{ entry.form.date }}</div>
      </div>
      <div>
        <div class="font-semibold">PIC</div>
        <div>{{ entry.form.pic }}</div>
      </div>
      <div>
        <div class="font-semibold">Area</div>
        <div>GENSET</div>
      </div>
      <div>
        <div class="font-semibold">Status Bulan {{ activeMonthLabel }}</div>
        <div>{{ activeMonthStatusLabel }}</div>
      </div>
    </div>

    <div class="overflow-x-auto border border-black">
      <table class="min-w-[1600px] border-collapse text-xs sm:text-sm">
        <thead>
          <tr class="bg-slate-100">
            <th class="border border-black px-2 py-2 text-center font-bold" rowspan="2">ITEM</th>
            <th
              v-for="month in months"
              :key="month.key"
              class="border border-black px-2 py-1 text-center font-bold"
              colspan="4"
              :class="month.key === activeMonth ? 'bg-amber-100' : ''"
            >
              {{ month.label }}
            </th>
          </tr>
          <tr class="bg-slate-50">
            <template v-for="month in months" :key="`${month.key}-weeks`">
              <th
                v-for="week in weeks"
                :key="`${month.key}-${week.key}`"
                class="border border-black px-2 py-1 text-center font-semibold"
                :class="month.key === activeMonth ? 'bg-amber-50' : ''"
              >
                {{ week.label }}
              </th>
            </template>
          </tr>
        </thead>
        <tbody>
          <template v-for="section in entry.form.sections" :key="section.id">
            <tr class="bg-slate-200">
              <td class="border border-black px-2 py-2 font-bold" :colspan="1 + (months.length * weeks.length)">
                {{ section.title }}
              </td>
            </tr>
            <tr v-for="row in section.items" :key="row.id">
              <td class="border border-black px-2 py-1 font-medium">{{ row.name }}</td>
              <template v-for="month in months" :key="`${row.id}-${month.key}`">
                <td
                  v-for="week in weeks"
                  :key="`${row.id}-${month.key}-${week.key}`"
                  class="border border-black p-0 text-center"
                  :class="month.key === activeMonth ? 'bg-amber-50' : ''"
                >
                  <button
                    type="button"
                    class="flex h-9 w-full items-center justify-center text-sm font-semibold"
                    :disabled="month.key !== activeMonth || isActiveMonthApproved"
                    @click="$emit('cycle-week-answer', { sectionId: section.id, rowId: row.id, monthKey: month.key, weekKey: week.key })"
                  >
                    <span v-if="row.months?.[month.key]?.[week.key] === 'yes'">&#10003;</span>
                    <span v-else-if="row.months?.[month.key]?.[week.key] === 'no'" class="text-rose-600">&#10005;</span>
                  </button>
                </td>
              </template>
            </tr>
          </template>

          <tr>
            <td class="border border-black px-2 py-1 text-right font-semibold">Tanggal Check</td>
            <td
              v-for="month in months"
              :key="`check-date-${month.key}`"
              class="border border-black px-2 py-1 text-center text-xs font-semibold"
              :class="month.key === activeMonth ? 'bg-amber-50' : ''"
              colspan="4"
            >
              {{ entry.form.monthly_check_dates?.[month.key] || '' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 rounded border border-slate-300 bg-slate-50 p-3">
      <div class="mb-2 text-sm font-semibold">Keterangan Bulan {{ activeMonthLabel }}</div>
      <textarea
        :value="monthNote"
        rows="4"
        class="w-full rounded border border-slate-400 bg-slate-100 px-3 py-2 text-sm text-slate-900"
        :disabled="isActiveMonthApproved"
        placeholder="Isi catatan / temuan untuk bulan aktif..."
        @input="$emit('update-month-note', $event.target.value)"
      ></textarea>
      <div class="mt-2 text-xs text-slate-600">
        Jika ada tanda silang, isi catatan untuk bulan aktif sebelum approval.
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
  months: {
    type: Array,
    required: true,
  },
  weeks: {
    type: Array,
    required: true,
  },
  activeMonth: {
    type: String,
    required: true,
  },
  activeMonthLabel: {
    type: String,
    required: true,
  },
  canScanBarcode: {
    type: Boolean,
    required: true,
  },
  canApproveEntry: {
    type: Boolean,
    required: true,
  },
  currentBarcode: {
    type: String,
    default: "",
  },
  monthNote: {
    type: String,
    default: "",
  },
  isActiveMonthApproved: {
    type: Boolean,
    default: false,
  },
  activeMonthStatusLabel: {
    type: String,
    default: "Pending",
  },
  showQrScanner: {
    type: Boolean,
    default: true,
  },
});

defineEmits([
  "approve",
  "scan-barcode",
  "set-active-month",
  "cycle-week-answer",
  "update-month-note",
]);
</script>
