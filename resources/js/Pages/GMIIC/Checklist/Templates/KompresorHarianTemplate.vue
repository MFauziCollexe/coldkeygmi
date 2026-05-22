<template>
  <div class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
    <div class="overflow-hidden border border-black">
      <table class="w-full border-collapse text-xs sm:text-sm table-fixed">
        <tbody>
          <tr>
            <td
              colspan="14"
              class="border border-black px-3 py-3 text-center text-lg font-bold"
            >
              CHECKLIST HARIAN KOMPRESOR {{ entry.form.year }}
            </td>
          </tr>

          <tr>
            <td colspan="6" class="border border-black px-2 py-2">
              <div class="grid grid-cols-[160px_20px_minmax(0,1fr)] items-center gap-2">
                <span class="font-semibold">KOMPRESOR NO</span>
                <span>:</span>

                <input
                  :value="entry.form.compressor_no"
                  type="text"
                  class="w-full border-0 bg-transparent text-sm text-slate-900 focus:outline-none focus:ring-0"
                  :disabled="entry.form.approved"
                  placeholder="Isi nomor kompresor"
                  @input="$emit('update-field', 'compressor_no', $event.target.value)"
                />
              </div>
            </td>

            <td colspan="6" class="border border-black px-3 py-2 text-center font-semibold">
              LOKASI : {{ entry.form.location }}
            </td>

            <td colspan="2" class="border border-black px-3 py-2 text-center font-semibold">
              {{ entry.form.document_no }}
            </td>
          </tr>

          <tr>
            <td colspan="6" class="border border-black px-2 py-2">
              <div class="grid grid-cols-[160px_20px_minmax(0,1fr)] items-center gap-2">
                <span class="font-semibold">BULAN, TAHUN</span>
                <span>:</span>

                <input
                  :value="entry.form.period"
                  type="month"
                  class="w-full border-0 bg-transparent text-sm text-slate-900 focus:outline-none focus:ring-0"
                  :disabled="entry.form.approved"
                  @input="$emit('update-field', 'period', $event.target.value)"
                />
              </div>
            </td>

            <td colspan="8" class="border border-black px-3 py-2">
              <div class="flex items-center justify-end gap-3">
                <div class="text-xs text-slate-600">
                  PIC: {{ entry.form.pic }}
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
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 overflow-x-auto border border-black">
      <table class="w-full min-w-[1600px] border-collapse text-xs sm:text-sm">
        <thead>
          <tr class="bg-slate-100">
            <th rowspan="2" class="border border-black px-2 py-2 text-center font-bold">NO</th>
            <th rowspan="2" class="border border-black px-2 py-2 text-center font-bold">TANGGAL</th>
            <th rowspan="2" class="border border-black px-2 py-2 text-center font-bold">STATUS MESIN (ON/OFF)</th>
            <th colspan="2" class="border border-black px-2 py-2 text-center font-bold">VISUAL</th>
            <th rowspan="2" class="border border-black px-2 py-2 text-center font-bold">TEK. SUCT. (Mpa)</th>
            <th rowspan="2" class="border border-black px-2 py-2 text-center font-bold">TEK DISCH (Mpa)</th>
            <th rowspan="2" class="border border-black px-2 py-2 text-center font-bold">DELTA TEKANAN OLI (Mpa)</th>
            <th colspan="4" class="border border-black px-2 py-2 text-center font-bold">PENGECEKAN</th>
            <th colspan="2" class="border border-black px-2 py-2 text-center font-bold">PERLAKUAN</th>
            <th rowspan="2" class="border border-black px-2 py-2 text-center font-bold">HOURS METER</th>
          </tr>
          <tr class="bg-slate-50">
            <th class="border border-black px-2 py-2 text-center font-semibold">BERSIH</th>
            <th class="border border-black px-2 py-2 text-center font-semibold">KOTOR</th>
            <th
              v-for="field in checkHeaderFields"
              :key="field.key"
              class="border border-black px-2 py-2 text-center font-semibold"
            >
              <input
                :value="entry.form.check_headers?.[field.key] || ''"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-0 text-center text-xs font-semibold text-slate-900 focus:outline-none focus:ring-0 sm:text-sm"
                :disabled="entry.form.approved"
                :placeholder="field.placeholder"
                @input="$emit('update-check-header', field.key, $event.target.value)"
              />
            </th>
            <th class="border border-black px-2 py-2 text-center font-semibold">TAMBAH GREASE MOTOR</th>
            <th class="border border-black px-2 py-2 text-center font-semibold">TAMBAH OLI (LITER)</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in rows"
            :key="row.day"
          >
            <td class="border border-black px-2 py-1 text-center">{{ row.day }}</td>
            <td class="border border-black px-2 py-1 text-center">{{ formatDayCell(row.date) }}</td>
            <td class="border border-black px-2 py-1">
              <input
                :value="row.status_mesin"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-1 text-center text-sm text-slate-900 focus:outline-none focus:ring-0"
                :disabled="entry.form.approved"
                placeholder="ON / OFF"
                @input="$emit('update-row-field', row.day, 'status_mesin', $event.target.value)"
              />
            </td>
            <td class="border border-black p-0 text-center">
              <button
                type="button"
                class="flex h-10 w-full items-center justify-center text-base font-semibold"
                :disabled="entry.form.approved"
                @click="$emit('cycle-row-symbol', row.day, 'visual_bersih')"
              >
                <span v-if="row.visual_bersih === 'yes'">&#10003;</span>
                <span v-else-if="row.visual_bersih === 'no'" class="text-rose-600">&#10005;</span>
                <span v-else-if="row.visual_bersih === 'minus'" class="text-slate-600">-</span>
              </button>
            </td>
            <td class="border border-black p-0 text-center">
              <button
                type="button"
                class="flex h-10 w-full items-center justify-center text-base font-semibold"
                :disabled="entry.form.approved"
                @click="$emit('cycle-row-symbol', row.day, 'visual_kotor')"
              >
                <span v-if="row.visual_kotor === 'yes'">&#10003;</span>
                <span v-else-if="row.visual_kotor === 'no'" class="text-rose-600">&#10005;</span>
                <span v-else-if="row.visual_kotor === 'minus'" class="text-slate-600">-</span>
              </button>
            </td>
            <td class="border border-black px-2 py-1">
              <input
                :value="row.tek_suct"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-1 text-center text-sm text-slate-900 focus:outline-none focus:ring-0"
                :disabled="entry.form.approved"
                @input="$emit('update-row-field', row.day, 'tek_suct', $event.target.value)"
              />
            </td>
            <td class="border border-black px-2 py-1">
              <input
                :value="row.tek_disch"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-1 text-center text-sm text-slate-900 focus:outline-none focus:ring-0"
                :disabled="entry.form.approved"
                @input="$emit('update-row-field', row.day, 'tek_disch', $event.target.value)"
              />
            </td>
            <td class="border border-black px-2 py-1">
              <input
                :value="row.delta_tekanan_oli"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-1 text-center text-sm text-slate-900 focus:outline-none focus:ring-0"
                :disabled="entry.form.approved"
                @input="$emit('update-row-field', row.day, 'delta_tekanan_oli', $event.target.value)"
              />
            </td>
            <td
              v-for="field in checkHeaderFields"
              :key="`${row.day}-${field.key}`"
              class="border border-black px-2 py-1"
            >
              <input
                :value="row[field.key]"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-1 text-center text-sm text-slate-900 focus:outline-none focus:ring-0"
                :disabled="entry.form.approved"
                @input="$emit('update-row-field', row.day, field.key, $event.target.value)"
              />
            </td>
            <td class="border border-black p-0 text-center">
              <button
                type="button"
                class="flex h-10 w-full items-center justify-center text-base font-semibold"
                :disabled="entry.form.approved"
                @click="$emit('cycle-row-symbol', row.day, 'tambah_grease_motor')"
              >
                <span v-if="row.tambah_grease_motor === 'yes'">&#10003;</span>
                <span v-else-if="row.tambah_grease_motor === 'no'" class="text-rose-600">&#10005;</span>
                <span v-else-if="row.tambah_grease_motor === 'minus'" class="text-slate-600">-</span>
              </button>
            </td>
            <td class="border border-black p-0 text-center">
              <button
                type="button"
                class="flex h-10 w-full items-center justify-center text-base font-semibold"
                :disabled="entry.form.approved"
                @click="$emit('cycle-row-symbol', row.day, 'tambah_oli')"
              >
                <span v-if="row.tambah_oli === 'yes'">&#10003;</span>
                <span v-else-if="row.tambah_oli === 'no'" class="text-rose-600">&#10005;</span>
                <span v-else-if="row.tambah_oli === 'minus'" class="text-slate-600">-</span>
              </button>
            </td>
            <td class="border border-black px-2 py-1">
              <input
                :value="row.hours_meter"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-1 text-center text-sm text-slate-900 focus:outline-none focus:ring-0"
                :disabled="entry.form.approved"
                @input="$emit('update-row-field', row.day, 'hours_meter', $event.target.value)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
const checkHeaderFields = [
  { key: 'check_1', placeholder: 'Header 1' },
  { key: 'check_2', placeholder: 'Header 2' },
  { key: 'check_3', placeholder: 'Header 3' },
  { key: 'check_4', placeholder: 'Header 4' },
];

defineProps({
  entry: {
    type: Object,
    required: true,
  },
  rows: {
    type: Array,
    required: true,
  },
  canApproveEntry: {
    type: Boolean,
    required: true,
  },
});

defineEmits([
  'approve',
  'update-field',
  'update-check-header',
  'update-row-field',
  'cycle-row-symbol',
]);

function formatDayCell(value) {
  const normalized = String(value || '').trim();
  if (!normalized) {
    return '-';
  }

  const [, , day] = normalized.split('-');
  return day || '-';
}
</script>
