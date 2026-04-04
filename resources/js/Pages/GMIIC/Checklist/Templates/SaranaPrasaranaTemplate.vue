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
              <div class="text-xl font-bold leading-tight">CHECKLIST</div>
              <div class="text-xl font-bold leading-tight">SARANA DAN PRASARANA</div>
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

    <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
      <div class="flex flex-col gap-3 text-lg font-semibold sm:flex-row sm:items-center">
        <div class="flex items-center gap-3">
          <span class="min-w-24">Periode:</span>
          <input
            :value="entry.form.period"
            type="month"
            class="rounded border border-slate-400 bg-white px-3 py-2 text-sm text-slate-900"
            @input="$emit('update-period', $event.target.value)"
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

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <div class="text-right text-xs text-slate-600">
          <div>Hari proses: {{ nextPendingDay ? nextPendingDay.day : '-' }}</div>
          <div>Klik sel tanggal untuk ubah status: kosong -> centang -> silang.</div>
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

    <div class="overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <thead>
          <tr class="bg-slate-100">
            <th class="w-12 border border-black px-2 py-2 text-center">No</th>
            <th class="min-w-[360px] border border-black px-2 py-2 text-center">ITEM</th>
            <th
              v-for="day in days"
              :key="day.key"
              class="w-12 border border-black px-2 py-2 text-center"
              :class="day.isSunday ? 'bg-red-600 text-white' : approvedDays.includes(day.day) ? 'bg-emerald-200' : ''"
            >
              {{ day.day }}
            </th>
          </tr>
        </thead>
        <tbody>
          <template v-if="currentSection">
            <tr class="bg-slate-50">
              <td colspan="100" class="border border-black px-2 py-2 text-base font-bold">
                {{ currentSection.title }}
              </td>
            </tr>
            <tr
              v-for="item in currentSection.items"
              :key="item.id"
            >
              <td class="border border-black px-2 py-1 text-center">{{ item.no }}</td>
              <td class="border border-black px-2 py-1">{{ item.name }}</td>
              <td
                v-for="day in days"
                :key="`${item.id}-${day.day}`"
                class="border border-black p-0 text-center"
                :class="day.isSunday ? 'bg-red-600' : approvedDays.includes(day.day) ? 'bg-emerald-100' : ''"
              >
                <button
                  v-if="!day.isSunday"
                  type="button"
                  class="flex h-9 w-9 items-center justify-center text-base font-semibold"
                  :disabled="approvedDays.includes(day.day)"
                  @click="$emit('cycle-day', currentSection.id, item.id, day.day)"
                >
                  <span v-if="item.days?.[day.day] === 'yes'">✓</span>
                  <span v-else-if="item.days?.[day.day] === 'no'" class="text-rose-600">✕</span>
                </button>
                <div v-else class="h-9 w-9"></div>
              </td>
            </tr>
          </template>
          <tr v-else>
            <td colspan="100" class="border border-black px-2 py-4 text-center text-slate-500">
              Pilih area terlebih dahulu.
            </td>
          </tr>
        </tbody>
      </table>
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
  days: {
    type: Array,
    required: true,
  },
  approvedDays: {
    type: Array,
    required: true,
  },
  nextPendingDay: {
    type: Object,
    default: null,
  },
  canApproveEntry: {
    type: Boolean,
    required: true,
  },
});

defineEmits(['approve', 'update-period', 'update-area', 'cycle-day']);
</script>
