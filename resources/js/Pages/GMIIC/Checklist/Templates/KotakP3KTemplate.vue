<template>
  <div class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
    <div class="mb-4 flex items-start gap-4">
      <div class="flex h-16 w-16 items-center justify-center rounded border border-slate-300 bg-white">
        <img
          src="/image/logo-gmi-clean.png"
          alt="PT. Golden Multi Indotama"
          class="h-12 w-12 object-contain"
        />
      </div>
      <div class="space-y-1">
        <div class="text-base font-semibold">PT. GOLDEN MULTI INDOTAMA</div>
        <div class="text-xs text-slate-600">Check sheet kotak P3K</div>
      </div>
    </div>

    <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
      <div class="w-full max-w-[18rem] space-y-2 text-sm">
        <div class="flex items-center gap-3">
          <span class="w-24">Bulan Aktif</span>
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
        <div class="text-xs text-slate-600">
          Klik kolom bulan aktif untuk ganti status item: kosong -> centang -> silang.
        </div>
      </div>

      <div class="flex-1">
        <div class="mx-auto max-w-2xl rounded-xl border-2 border-black px-8 py-2 text-center">
          <div class="text-xl font-semibold">Check Sheet</div>
          <div class="text-4xl font-bold">Kotak P3K</div>
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
            Scan Barcode
          </button>
          <div class="text-xs text-slate-600">
            {{ currentBarcode || `Barcode bulan ${activeMonthLabel} belum discan.` }}
          </div>
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
          {{ approvalButtonLabel }}
        </button>
      </div>
    </div>

    <div class="overflow-visible border border-black">
      <table class="min-w-full border-collapse text-sm">
        <tbody>
          <tr>
            <td class="w-[25%] border border-black px-2 py-1">Lokasi</td>
            <td class="w-[25%] border border-black px-2 py-1 font-semibold">
              <div class="relative" data-location-menu-root>
                <button
                  type="button"
                  class="block w-full bg-transparent p-0 text-left"
                  @click="$emit('toggle-location-menu')"
                >
                  <span>{{ getLocationLabel(entry.form.location) }}</span>
                </button>

                <div
                  v-if="locationMenuOpen"
                  class="absolute left-0 right-0 top-full z-20 mt-1 overflow-hidden rounded border border-slate-300 bg-white shadow-lg"
                >
                  <button
                    v-for="location in locationOptions"
                    :key="location.id"
                    type="button"
                    class="block w-full border-b border-slate-200 px-3 py-2 text-left text-sm transition last:border-b-0 hover:bg-slate-100"
                    :class="location.id === entry.form.location ? 'bg-slate-100 font-semibold' : ''"
                    @click="$emit('select-location', location.id)"
                  >
                    {{ location.name }}
                  </button>
                </div>
              </div>
            </td>
            <td class="w-[25%] border border-black text-center">
              <div class="text-xs font-semibold">Approved</div>
            </td>
            <td class="w-[25%] border border-black text-center">
              <div class="text-xs font-semibold">Prepared</div>
            </td>
          </tr>
          <tr>
            <td class="border border-black px-2 py-1">No. / Tipe Kotak</td>
            <td class="border border-black px-2 py-1 font-semibold">{{ entry.form.box_type }}</td>
            <td class="border border-black px-2 py-1 text-center text-xs">
              {{ activeMonthStatusLabel }}
            </td>
            <td class="border border-black px-2 py-1 text-center text-xs">
              {{ entry.form.pic }}
            </td>
          </tr>
          <tr>
            <td class="border border-black px-2 py-1">PIC</td>
            <td class="border border-black px-2 py-1 font-semibold">{{ entry.form.pic }}</td>
            <td class="border border-black px-2 py-1">No. Doc : {{ entry.form.document_no }}</td>
            <td class="border border-black px-2 py-1">Rev : {{ entry.form.rev }}</td>
          </tr>
          <tr>
            <td class="border border-black px-2 py-1">Tahun</td>
            <td class="border border-black px-2 py-1 font-semibold">{{ entry.form.year }}</td>
            <td class="border border-black px-2 py-1">Date : {{ entry.form.date }}</td>
            <td class="border border-black px-2 py-1">Page : {{ entry.form.page }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <thead>
          <tr class="bg-slate-200">
            <th class="w-12 border border-black px-2 py-1 text-center">No</th>
            <th class="w-[28rem] border border-black px-2 py-1 text-left">Item Check</th>
            <th class="w-20 border border-black px-2 py-1 text-center">Jumlah</th>
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
            v-for="(item, index) in entry.form.items"
            :key="item.id"
          >
            <td class="border border-black px-2 py-1 text-center">{{ index + 1 }}</td>
            <td class="border border-black px-2 py-1">{{ item.name }}</td>
            <td class="border border-black px-2 py-1 text-center">{{ item.quantity }}</td>
            <td
              v-for="month in months"
              :key="`${item.id}-${month.key}`"
              class="border border-black p-0 text-center"
              :class="month.key === activeMonth ? 'bg-amber-50' : ''"
            >
              <button
                type="button"
                class="flex h-10 w-full items-center justify-center text-sm font-semibold"
                :disabled="month.key !== activeMonth || isActiveMonthLocked"
                @click="$emit('cycle-month-answer', item, month.key)"
              >
                <span v-if="item.months?.[month.key] === 'yes'">✓</span>
                <span v-else-if="item.months?.[month.key] === 'no'" class="text-rose-600">✕</span>
              </button>
            </td>
          </tr>
          <tr>
            <td colspan="3" class="border border-black px-2 py-1 text-right font-semibold">Tanggal Check</td>
            <td
              v-for="month in months"
              :key="`check-date-${month.key}`"
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
          :disabled="isActiveMonthLocked"
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
  canScanBarcode: {
    type: Boolean,
    required: true,
  },
  canApproveEntry: {
    type: Boolean,
    required: true,
  },
  locationMenuOpen: {
    type: Boolean,
    required: true,
  },
  locationOptions: {
    type: Array,
    required: true,
  },
  getLocationLabel: {
    type: Function,
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
  currentBarcode: {
    type: String,
    required: true,
  },
  monthNote: {
    type: String,
    required: true,
  },
  isActiveMonthApproved: {
    type: Boolean,
    required: true,
  },
  isActiveMonthLocked: {
    type: Boolean,
    required: true,
  },
  activeMonthStatusLabel: {
    type: String,
    required: true,
  },
  approvalButtonLabel: {
    type: String,
    required: true,
  },
});

defineEmits([
  'approve',
  'scan-barcode',
  'toggle-location-menu',
  'select-location',
  'set-active-month',
  'cycle-month-answer',
  'update-month-note',
]);
</script>
