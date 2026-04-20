<template>
  <div class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
    <div class="mb-4 flex items-start justify-between gap-4">
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
          <div class="text-xs text-slate-600">Kartu pemeliharaan APAR, smoke detector, dan fire alarm</div>
        </div>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
        <div class="flex w-[132px] flex-col gap-1">
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

        <button
          type="button"
          :disabled="!canApproveEntry"
          class="rounded px-4 py-2 text-sm font-semibold transition"
          :class="canApproveEntry
            ? 'bg-amber-500 text-white hover:bg-amber-400'
            : 'cursor-not-allowed bg-slate-300 text-slate-500'"
          @click="$emit('approve')"
        >
          Approval
        </button>
      </div>
    </div>

    <div class="mb-4 grid gap-3 rounded border border-slate-300 bg-slate-50 p-3 xl:grid-cols-[auto_auto_1fr] xl:items-center">
      <div class="flex items-center gap-3 text-sm">
        <span class="w-20">Jenis Kartu</span>
        <span>:</span>
        <div class="relative">
          <select
            :value="entry.form.card_type"
            class="w-52 appearance-none rounded border border-slate-300 bg-white px-2 py-1 pr-10 text-sm text-slate-900"
            @change="$emit('update-card-type', $event.target.value)"
          >
            <option
              v-for="option in cardOptions"
              :key="option.id"
              :value="option.id"
            >
              {{ option.name }}
            </option>
          </select>
          <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-slate-600">
            ▼
          </span>
        </div>
      </div>

      <div class="flex items-center gap-3 text-sm">
        <span class="w-20">Bulan Aktif</span>
        <span>:</span>
        <div class="relative">
          <select
            :value="activeMonth"
            class="w-40 appearance-none rounded border border-slate-300 bg-white px-2 py-1 pr-10 text-sm text-slate-900"
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
          <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-slate-600">
            ▼
          </span>
        </div>
      </div>

      <div class="text-xs text-slate-600 xl:text-right">
        Klik kolom bulan aktif untuk ganti status item: kosong -> centang -> silang.
      </div>
    </div>

    <div class="overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <tbody>
          <tr>
            <td class="border border-black px-2 py-1 text-center text-base font-bold">
              {{ cardTitle }}
            </td>
          </tr>
          <tr>
            <td class="border border-black px-2 py-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-semibold">Lokasi:</span>
                <div class="relative">
                  <select
                    :value="entry.form.location"
                    class="w-64 appearance-none rounded border border-slate-300 bg-white px-2 py-1 pr-10 text-sm text-slate-900"
                    @change="$emit('update-location', $event.target.value)"
                  >
                    <option
                      v-for="location in locationOptions"
                      :key="location.id"
                      :value="location.id"
                    >
                      {{ location.name }}
                    </option>
                  </select>
                  <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-slate-600">
                    ▼
                  </span>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-5 overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <thead>
          <tr>
            <th rowspan="2" class="w-72 border border-black px-2 py-1 text-center">Item Check</th>
            <th colspan="12" class="border border-black px-2 py-1 text-center">Tahun</th>
          </tr>
          <tr>
            <th
              v-for="month in months"
              :key="month.key"
              class="w-16 border border-black px-2 py-1 text-center"
              :class="month.key === activeMonth ? 'bg-amber-100' : ''"
            >
              {{ month.label }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in entry.form.rows"
            :key="row.id"
          >
            <td class="border border-black px-2 py-1 font-semibold">{{ row.name }}</td>
            <td
              v-for="month in months"
              :key="`${row.id}-${month.key}`"
              class="border border-black p-0 text-center"
              :class="month.key === activeMonth ? 'bg-amber-50' : ''"
            >
              <button
                type="button"
                class="flex h-9 w-full items-center justify-center text-sm font-semibold"
                :disabled="month.key !== activeMonth || isActiveMonthApproved"
                @click="$emit('cycle-month-answer', row, month.key)"
              >
                <span v-if="row.months?.[month.key] === 'yes'">✓</span>
                <span v-else-if="row.months?.[month.key] === 'no'" class="text-rose-600">✕</span>
              </button>
            </td>
          </tr>
          <tr>
            <td class="border border-black px-2 py-1 font-semibold">Tanggal</td>
            <td
              v-for="month in months"
              :key="`tanggal-${month.key}`"
              class="border border-black px-2 py-1 text-center text-xs"
              :class="month.key === activeMonth ? 'bg-amber-50' : ''"
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
    </div>
  </div>
</template>

<script setup>
defineProps({
  entry: {
    type: Object,
    required: true,
  },
  cardOptions: {
    type: Array,
    required: true,
  },
  cardTitle: {
    type: String,
    required: true,
  },
  locationOptions: {
    type: Array,
    required: true,
  },
  months: {
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
  monthNote: {
    type: String,
    required: true,
  },
  currentBarcode: {
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
  isActiveMonthApproved: {
    type: Boolean,
    required: true,
  },
});

defineEmits([
  'approve',
  'scan-barcode',
  'update-card-type',
  'update-location',
  'set-active-month',
  'cycle-month-answer',
  'update-month-note',
]);
</script>
