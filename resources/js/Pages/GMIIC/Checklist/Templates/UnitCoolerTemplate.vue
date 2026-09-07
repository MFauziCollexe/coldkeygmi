<template>
  <div class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-4 text-sm font-semibold">
        <label class="flex items-center gap-2">
          Bulan:
          <input :value="entry.form.period" type="month" class="rounded border border-slate-400 bg-white px-2 py-1 text-sm text-slate-900" :disabled="entry.form.approved" @input="$emit('update-period', $event.target.value)" />
        </label>
        <span>Unit Cooler</span>
        <span class="font-normal">PIC: {{ entry.form.pic }}</span>
      </div>
      <ApprovalButton :is-ready="canApproveEntry" :disabled="!canApproveEntry" label="Approval" button-class="w-[104px]" @click="$emit('approve')" />
    </div>

    <div class="overflow-x-auto border border-black">
      <table class="w-full min-w-[1100px] table-fixed border-collapse text-xs">
        <thead>
          <tr class="bg-white font-bold">
            <th rowspan="2" class="w-10 border border-black px-1 py-1 text-center">TGL</th>
            <th v-for="group in unitGroups" :key="group.name" :colspan="group.units.length" class="border border-black px-1 py-1 text-center whitespace-nowrap">{{ group.name }}</th>
          </tr>
          <tr class="bg-white font-bold">
            <th v-for="unit in unitCoolerItems" :key="unit[0]" class="border border-black px-1 py-1 text-center">{{ unit[2] }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.day" :class="Number(row.day) === activeDay ? 'bg-sky-50' : ''">
            <td class="border border-black p-0 text-center">
              <button type="button" class="flex h-7 w-full items-center justify-center font-semibold" :class="Number(row.day) === activeDay ? 'bg-sky-200 text-sky-900' : 'hover:bg-slate-100'" :disabled="entry.form.approved" :title="`Lihat tanggal ${row.day}`" @click="$emit('set-active-day', row.day)">{{ row.day }}</button>
            </td>
            <td v-for="unit in unitCoolerItems" :key="`${row.day}-${unit[0]}`" class="border border-black p-0 text-center">
              <button type="button" class="flex h-7 w-full items-center justify-center text-sm font-bold leading-none"
                :title="cellTitle(row.day, unit[0])"
                :class="cellClass(row.day, unit[0])"
                :disabled="!isEditableDay(row.day)"
                @click="$emit('cycle-cell', row.day, unit[0])">
                <span v-if="row[unit[0]] === 'yes'" class="text-emerald-600">&#10003;</span>
                <span v-else-if="row[unit[0]] === 'no'" class="text-rose-600">&#10005;</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 rounded border border-slate-300 bg-slate-50 p-3">
      <div class="mb-2 text-sm font-semibold">Catatan / Temuan - Tanggal {{ activeDay }}</div>
      <textarea :value="note" rows="3" class="w-full rounded border border-slate-400 bg-slate-100 px-3 py-2 text-sm text-slate-900" :disabled="!isNoteEditable" :placeholder="notePlaceholder" @input="$emit('update-note', $event.target.value)"></textarea>
      <p v-if="noteReadonlyReason" class="mt-1 text-xs text-slate-500">{{ noteReadonlyReason }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import ApprovalButton from '../Components/ApprovalButton.vue';

const props = defineProps({
  entry: { type: Object, required: true },
  rows: { type: Array, required: true },
  unitCoolerItems: { type: Array, required: true },
  canApproveEntry: { type: Boolean, default: false },
  note: { type: String, default: '' },
  approvedDays: { type: Array, default: () => [] },
  activeDay: { type: Number, default: 1 },
  today: { type: Number, default: 1 },
});

const unitGroups = Array.from(
  props.unitCoolerItems.reduce((groups, unit) => {
    const name = unit[1];
    if (!groups.has(name)) groups.set(name, { name, units: [] });
    groups.get(name).units.push(unit);
    return groups;
  }, new Map()).values(),
);

function isEditableDay(day) {
  return Number(day) === Number(props.today) && !props.approvedDays.includes(Number(props.today));
}

function cellClass(day, key) {
  const numDay = Number(day);
  const isApprovedDay = props.approvedDays.includes(numDay);
  const isPast = numDay < Number(props.today);
  const isFuture = numDay > Number(props.today);
  const isEmpty = !String(props.rows.find((r) => Number(r.day) === numDay)?.[key] || '');
  if (isApprovedDay) return 'bg-slate-100 opacity-40';
  if (isPast) return isEmpty ? 'bg-slate-50 opacity-40' : 'bg-slate-50';
  if (isFuture) return 'bg-slate-50 opacity-40';
  return 'hover:bg-slate-100';
}

function cellTitle(day, key) {
  const numDay = Number(day);
  const val = props.rows.find((r) => Number(r.day) === numDay)?.[key] || '';
  const label = val === 'yes' ? 'OK' : val === 'no' ? 'Temuan' : 'Kosong';
  if (numDay === Number(props.today)) return `Klik untuk isi (${label})`;
  if (numDay < Number(props.today)) return `Hari lalu (read-only) - ${label}`;
  return `Tanggal ${numDay} belum bisa diisi (hari ini = ${props.today})`;
}

const isNoteEditable = computed(() => Number(props.activeDay) === Number(props.today) && !props.approvedDays.includes(Number(props.today)));

const noteReadonlyReason = computed(() => {
  if (Number(props.activeDay) !== Number(props.today)) {
    return Number(props.activeDay) < Number(props.today)
      ? 'Tanggal kemarin/lalu hanya bisa dilihat (read-only).'
      : 'Tanggal ini belum bisa diisi (hanya tanggal hari ini yang bisa diisi).';
  }
  if (props.approvedDays.includes(Number(props.today))) return 'Tanggal hari ini sudah di-approve.';
  return '';
});

const notePlaceholder = computed(() => {
  if (Number(props.activeDay) !== Number(props.today)) return 'Hari ini = ' + props.today + '. Pilih tanggal hari ini untuk mengisi catatan.';
  if (props.approvedDays.includes(Number(props.today))) return 'Tanggal ini sudah di-approve.';
  return 'Isi catatan jika ada temuan (wajib jika ada tanda silang).';
});

defineEmits(['approve', 'update-period', 'cycle-cell', 'update-note', 'set-active-day']);
</script>
