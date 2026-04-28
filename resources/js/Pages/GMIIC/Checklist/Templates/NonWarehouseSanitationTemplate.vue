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
        <div class="text-xs text-slate-600">Checklist kebersihan dan sanitasi non-warehouse area</div>
      </div>
    </div>

    <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
      <div class="w-full max-w-[18rem] space-y-2 text-sm">
        <div class="flex items-center gap-3">
          <span class="w-20">Periode</span>
          <span>:</span>
          <input
            v-model="entry.form.period"
            type="month"
            class="w-44 rounded border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900"
          />
        </div>
        <div class="flex items-center gap-3">
          <span class="w-20">Area</span>
          <span>:</span>
          <div class="relative">
            <select
              v-model="entry.form.area"
              class="w-44 appearance-none rounded border border-slate-300 bg-white px-2 py-1 pr-10 text-sm text-slate-900"
            >
              <option
                v-for="area in sanitationAreaOptions"
                :key="area.id"
                :value="area.id"
              >
                {{ area.name }}
              </option>
            </select>
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-slate-600">
              ▼
            </span>
          </div>
        </div>
      </div>

      <div class="flex-1">
        <div class="mx-auto max-w-xl rounded-xl border-2 border-black px-8 py-3 text-center text-2xl font-bold">
          CHECKLIST SANITASI
        </div>
      </div>

      <div class="w-full max-w-[16rem] text-right text-sm">
        Doc.No.: {{ entry.form.document_no }}, Rev.{{ entry.form.rev }}
      </div>
    </div>

    <div class="mb-4 flex items-center justify-end">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <div class="text-right text-xs text-slate-600">
          <div>Hari proses: {{ nextPendingDay ? nextPendingDay.day : '-' }}</div>
          <div v-if="currentAreaScan">
            Area ini sudah discan {{ currentAreaScan.scanned_at }}
          </div>
          <div v-else>
            Area ini belum discan
          </div>
        </div>

        <button
          type="button"
          :disabled="!canScanArea"
          class="rounded px-4 py-2 text-sm font-semibold transition"
          :class="canScanArea
            ? 'bg-sky-600 text-white hover:bg-sky-500'
            : 'cursor-not-allowed bg-slate-300 text-slate-500'"
          @click="$emit('scan-area')"
        >
          Scan Area
        </button>

        <button
          type="button"
          :disabled="!canApproveEntry"
          class="rounded px-4 py-2 text-sm font-semibold transition"
          :class="canApproveEntry
            ? 'bg-amber-500 text-white hover:bg-amber-400'
            : 'cursor-not-allowed bg-slate-300 text-slate-500'"
          @click="$emit('approve')"
        >
          {{ approvalButtonLabel }}
        </button>
      </div>
    </div>

    <div class="overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <thead>
          <tr class="bg-slate-200">
            <th class="border border-black px-2 py-1 text-left font-normal">Cleaning Items</th>
            <th
              v-for="day in sanitationDays"
              :key="day.key"
              class="border border-black px-2 py-1 text-center font-normal"
              :class="day.isSunday ? 'bg-red-600 text-white' : approvedDays.includes(day.day) ? 'bg-emerald-200' : ''"
            >
              {{ day.day }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in rows"
            :key="row.id"
          >
            <td class="border border-black px-2 py-1 whitespace-nowrap">{{ row.name }}</td>
            <td
              v-for="day in sanitationDays"
              :key="`${row.id}-${day.day}`"
              class="border border-black p-0 text-center"
              :class="day.isSunday ? 'bg-red-600' : approvedDays.includes(day.day) ? 'bg-emerald-100' : ''"
            >
              <button
                v-if="!day.isSunday"
                type="button"
                class="flex h-9 w-9 items-center justify-center text-base text-black"
                :disabled="approvedDays.includes(day.day)"
                @click="$emit('toggle-day', row, day.day)"
              >
                {{ row.days?.[day.day] ? '✓' : '' }}
              </button>
              <div v-else class="h-9 w-9"></div>
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
        :disabled="!canEditNote"
        placeholder="Isi catatan / temuan untuk hari proses dan area aktif..."
        @input="$emit('update-note', $event.target.value)"
      ></textarea>
      <div class="mt-2 text-xs text-slate-600">
        Isi keterangan ini jika ada temuan pada area yang sedang diproses.
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
  rows: {
    type: Array,
    required: true,
  },
  approvedDays: {
    type: Array,
    required: true,
  },
  currentAreaScan: {
    type: Object,
    default: null,
  },
  nextPendingDay: {
    type: Object,
    default: null,
  },
  canScanArea: {
    type: Boolean,
    required: true,
  },
  canApproveEntry: {
    type: Boolean,
    required: true,
  },
  approvalButtonLabel: {
    type: String,
    default: 'Approval',
  },
  note: {
    type: String,
    default: '',
  },
  noteLabel: {
    type: String,
    default: 'Keterangan',
  },
  canEditNote: {
    type: Boolean,
    required: true,
  },
  sanitationDays: {
    type: Array,
    required: true,
  },
  sanitationAreaOptions: {
    type: Array,
    required: true,
  },
});

defineEmits(['approve', 'toggle-day', 'scan-area', 'update-note']);
</script>
