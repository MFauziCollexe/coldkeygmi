<template>
  <div v-if="rows.length > 0" class="bg-slate-800 rounded-lg p-4 border border-slate-700">
    <div v-if="showSummary" class="flex flex-wrap justify-between gap-3 mb-3 text-sm">
      <div class="text-slate-300">
        Total Preview: <span class="font-semibold text-white">{{ summary.total_preview_rows }}</span>
      </div>
      <div class="text-emerald-300">Valid: {{ summary.valid_rows }}</div>
      <div class="text-rose-300">Invalid: {{ summary.invalid_rows }}</div>
    </div>
    <div class="overflow-auto max-h-[620px] border border-slate-700">
      <table class="w-max min-w-full text-xs">
        <thead class="sticky top-0 z-10">
          <tr class="bg-yellow-300 text-slate-900">
            <th class="sticky left-0 z-30 bg-yellow-200 border border-slate-700 px-2 py-1 min-w-[120px]">NRP</th>
            <th class="sticky left-[120px] z-30 bg-yellow-200 border border-slate-700 px-2 py-1 min-w-[220px]">Nama</th>
            <th :colspan="columns.length" class="border border-slate-700 px-2 py-1 text-center font-bold text-base">
              {{ monthLabel.toUpperCase() }} {{ year }}
            </th>
          </tr>
          <tr class="bg-yellow-300 text-slate-900">
            <th class="sticky left-0 z-30 bg-yellow-200 border border-slate-700 px-2 py-1"></th>
            <th class="sticky left-[120px] z-30 bg-yellow-200 border border-slate-700 px-2 py-1"></th>
            <th
              v-for="col in columns"
              :key="`day-num-${col.day}`"
              class="border border-slate-700 px-2 py-1 text-center min-w-[38px]"
            >
              {{ col.day }}
            </th>
          </tr>
          <tr class="bg-yellow-100 text-slate-900">
            <th class="sticky left-0 z-30 bg-yellow-100 border border-slate-700 px-2 py-1"></th>
            <th class="sticky left-[120px] z-30 bg-yellow-100 border border-slate-700 px-2 py-1"></th>
            <th
              v-for="col in columns"
              :key="`day-name-${col.day}`"
              class="border border-slate-700 px-1 py-1 text-center min-w-[38px]"
            >
              {{ col.dayName }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="employee in matrix" :key="employee.employee_key">
            <td class="sticky left-0 z-20 border border-slate-700 px-2 py-1 bg-slate-100 text-slate-900 font-semibold whitespace-nowrap min-w-[120px]">
              {{ employee.employee_nrp || '-' }}
            </td>
            <td class="sticky left-[120px] z-20 border border-slate-700 px-2 py-1 bg-slate-100 text-slate-900 font-semibold whitespace-nowrap min-w-[220px]">
              {{ employee.employee_name }}
            </td>
            <td
              v-for="col in columns"
              :key="`${employee.employee_key}-${col.day}`"
              class="border border-slate-700 px-1 py-1 text-center font-semibold"
              :class="getShiftCellClass(employee.days[col.day]?.row?.shift_code, employee.days[col.day]?.row?.is_valid)"
            >
              <input
                v-if="employee.days[col.day]?.row && !readonly"
                :value="employee.days[col.day]?.row?.shift_code || ''"
                class="w-[36px] p-0 m-0 bg-transparent !bg-transparent border-0 !border-0 shadow-none !shadow-none ring-0 !ring-0 outline-none text-center text-inherit !text-inherit appearance-none"
                @input="emit('shift-input', employee.days[col.day].row, $event.target.value)"
              />
              <span v-else-if="employee.days[col.day]?.row">
                {{ employee.days[col.day]?.row?.shift_code || '' }}
              </span>
              <span v-else>&nbsp;</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  rows: {
    type: Array,
    default: () => [],
  },
  matrix: {
    type: Array,
    default: () => [],
  },
  summary: {
    type: Object,
    default: () => ({
      total_preview_rows: 0,
      valid_rows: 0,
      invalid_rows: 0,
    }),
  },
  columns: {
    type: Array,
    default: () => [],
  },
  monthLabel: {
    type: String,
    default: '',
  },
  year: {
    type: [Number, String],
    required: true,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
  showSummary: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['shift-input']);

function getShiftCellClass(code, isValid) {
  if (!code) return 'bg-slate-800 text-slate-300';
  if (!isValid) return 'bg-rose-700 text-white';
  if (code === 'OFF' || code === 'NONE') return 'bg-red-600 text-white';
  if (code === '0') return 'bg-indigo-700 text-white';
  if (code === '8') return 'bg-yellow-300 text-slate-900';
  if (code === '10') return 'bg-cyan-500 text-white';
  if (code === '13') return 'bg-lime-500 text-slate-900';
  if (code === '16') return 'bg-amber-700 text-white';
  return 'bg-blue-700 text-white';
}
</script>
