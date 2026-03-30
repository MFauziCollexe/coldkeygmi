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
          <div class="text-xs text-slate-600">Kartu checklist personal hygiene karyawan</div>
        </div>
      </div>

      <div class="w-full max-w-[16rem] space-y-3 text-sm">
        <div class="grid grid-cols-[auto_auto_auto] gap-x-2">
          <span>No. Dokumen</span>
          <span>:</span>
          <span>FRM.HSE.09.01</span>
        </div>

        <button
          type="button"
          class="w-full rounded border border-sky-300 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700 transition hover:bg-sky-100"
          @click="employeeModalOpen = true"
        >
          Generate 1 Bulan Full
        </button>
      </div>
    </div>

    <div class="mb-4 bg-black px-4 py-2 text-center text-3xl font-bold text-white">
      KARTU CHECKLIST PERSONAL HYGIENE
    </div>

    <div class="overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <tbody>
          <tr>
            <td class="w-40 border border-black px-3 py-2 font-semibold">Tahun</td>
            <td class="border border-black px-3 py-2">
              <input
                :value="entry.form.year"
                type="text"
                readonly
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
              />
            </td>
            <td class="w-40 border border-black px-3 py-2 font-semibold">Bulan</td>
            <td class="border border-black px-3 py-2">
              <input
                :value="entry.form.period"
                type="month"
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
                @input="$emit('update-general-field', 'period', $event.target.value)"
              />
            </td>
          </tr>
          <tr>
            <td class="border border-black px-3 py-2 font-semibold">Nama Karyawan</td>
            <td colspan="3" class="border border-black px-3 py-2">
              <input
                :value="entry.form.employee_name"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
                @input="$emit('update-general-field', 'employee_name', $event.target.value)"
              />
            </td>
          </tr>
          <tr>
            <td class="border border-black px-3 py-2 font-semibold">Jenis Kelamin</td>
            <td colspan="3" class="border border-black px-3 py-2">
              <div class="flex flex-wrap gap-6">
                <label class="inline-flex items-center gap-2">
                  <input
                    :checked="entry.form.gender === 'male'"
                    type="checkbox"
                    class="h-4 w-4 border border-slate-400"
                    @change="$emit('update-general-field', 'gender', entry.form.gender === 'male' ? '' : 'male')"
                  />
                  <span>Laki-Laki</span>
                </label>
                <label class="inline-flex items-center gap-2">
                  <input
                    :checked="entry.form.gender === 'female'"
                    type="checkbox"
                    class="h-4 w-4 border border-slate-400"
                    @change="$emit('update-general-field', 'gender', entry.form.gender === 'female' ? '' : 'female')"
                  />
                  <span>Perempuan</span>
                </label>
              </div>
            </td>
          </tr>
          <tr>
            <td class="border border-black px-3 py-2 font-semibold">NIK</td>
            <td colspan="3" class="border border-black px-3 py-2">
              <input
                :value="entry.form.nik"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
                @input="$emit('update-general-field', 'nik', $event.target.value)"
              />
            </td>
          </tr>
          <tr>
            <td class="border border-black px-3 py-2 font-semibold">Bagian</td>
            <td colspan="3" class="border border-black px-3 py-2">
              <input
                :value="entry.form.bagian"
                type="text"
                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 focus:outline-none focus:ring-0"
                @input="$emit('update-general-field', 'bagian', $event.target.value)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="!generatedEmployees.length" class="mt-4 overflow-x-auto border border-black">
      <table class="min-w-full border-collapse text-sm">
        <thead>
          <tr class="bg-black text-white">
            <th rowspan="2" class="min-w-[220px] border border-white px-3 py-2 text-center">PARAMETER</th>
            <th :colspan="days.length" class="border border-white px-3 py-2 text-center">TANGGAL</th>
          </tr>
          <tr class="bg-black text-white">
            <th
              v-for="day in days"
              :key="day.key"
              class="border border-white px-2 py-2 text-center"
              :class="day.isSunday || day.isHoliday || day.isLeave ? 'bg-red-600 text-white' : ''"
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
            <td class="border border-black px-3 py-2 font-semibold">
              {{ row.name }}
            </td>
            <td
              v-for="day in days"
              :key="`${row.id}-${day.day}`"
              class="border border-black p-0 text-center"
              :class="day.isSunday || day.isHoliday || day.isLeave ? 'bg-red-100' : ''"
            >
              <button
                type="button"
                class="flex h-10 w-full items-center justify-center text-sm font-semibold"
                :class="day.isSunday || day.isHoliday || day.isLeave ? 'text-red-700' : 'text-slate-900'"
                :disabled="day.isSunday || day.isHoliday || day.isLeave"
                @click="$emit('toggle-day', row, day.day)"
              >
                <span v-if="row.days?.[day.day] === 'yes'">✓</span>
                <span v-else-if="row.days?.[day.day] === 'no'" class="text-rose-600">✕</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 flex justify-end">
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

    <div v-if="generatedEmployees.length" class="mt-4 space-y-6">
      <div class="rounded border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
        Generate bulanan sudah dibuat untuk <span class="font-semibold">{{ generatedEmployees.length }}</span> karyawan aktif.
        Kotak merah untuk Minggu, tanggal merah, dan cuti dibiarkan kosong.
      </div>

      <div
        v-for="employee in generatedEmployees"
        :key="employee.employee_id"
        class="overflow-x-auto border border-black"
      >
        <div class="grid gap-2 border-b border-black bg-slate-100 px-3 py-2 text-sm font-semibold md:grid-cols-4">
          <div>Nama: {{ employee.name || '-' }}</div>
          <div>NIK: {{ employee.nik || '-' }}</div>
          <div>Gender: {{ employee.gender || '-' }}</div>
          <div>Position: {{ employee.position || '-' }}</div>
        </div>

        <table class="min-w-full border-collapse text-sm">
          <thead>
            <tr class="bg-black text-white">
              <th rowspan="2" class="min-w-[220px] border border-white px-3 py-2 text-center">PARAMETER</th>
              <th :colspan="days.length" class="border border-white px-3 py-2 text-center">TANGGAL</th>
            </tr>
            <tr class="bg-black text-white">
              <th
                v-for="day in days"
                :key="`${employee.employee_id}-${day.key}`"
                class="border border-white px-2 py-2 text-center"
                :class="isGeneratedRedDay(day, employee) ? 'bg-red-600 text-white' : ''"
              >
                {{ day.day }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in employee.rows"
              :key="`${employee.employee_id}-${row.id}`"
            >
              <td class="border border-black px-3 py-2 font-semibold">
                {{ row.name }}
              </td>
              <td
                v-for="day in days"
                :key="`${employee.employee_id}-${row.id}-${day.day}`"
                class="border border-black p-0 text-center"
                :class="isGeneratedRedDay(day, employee) ? 'bg-red-100' : ''"
              >
                <button
                  type="button"
                  class="flex h-10 w-full items-center justify-center text-sm font-semibold"
                  :class="isGeneratedRedDay(day, employee) ? 'text-red-700' : 'text-slate-900'"
                  :disabled="isGeneratedRedDay(day, employee)"
                  @click="$emit('toggle-generated-day', employee.employee_id, row.id, day.day)"
                >
                  <span v-if="row.days?.[day.day] === 'yes'">âœ“</span>
                  <span v-else-if="row.days?.[day.day] === 'no'" class="text-rose-600">âœ•</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div
      v-if="employeeModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
      @click.self="employeeModalOpen = false"
    >
      <div class="w-full max-w-5xl rounded-xl border border-slate-300 bg-white p-4 shadow-2xl">
        <div class="mb-4 flex items-center justify-between gap-4">
          <div>
            <div class="text-lg font-semibold text-slate-900">Daftar Karyawan</div>
            <div class="text-sm text-slate-600">{{ employees.length }} employee tersedia untuk generate bulanan.</div>
          </div>

          <button
            type="button"
            class="rounded bg-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-300"
            @click="employeeModalOpen = false"
          >
            Close
          </button>
        </div>

        <div class="max-h-[70vh] overflow-auto rounded border border-slate-300">
          <table class="min-w-full border-collapse text-sm">
            <thead class="sticky top-0 bg-slate-100">
              <tr>
                <th class="border border-slate-300 px-3 py-2 text-left">No</th>
                <th class="border border-slate-300 px-3 py-2 text-left">NIK</th>
                <th class="border border-slate-300 px-3 py-2 text-left">Nama</th>
                <th class="border border-slate-300 px-3 py-2 text-left">Gender</th>
                <th class="border border-slate-300 px-3 py-2 text-left">Position</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(employee, index) in employees"
                :key="employee.id || `${employee.nik}-${index}`"
                class="odd:bg-white even:bg-slate-50"
              >
                <td class="border border-slate-200 px-3 py-2">{{ index + 1 }}</td>
                <td class="border border-slate-200 px-3 py-2">{{ employee.nik || '-' }}</td>
                <td class="border border-slate-200 px-3 py-2">{{ employee.name || '-' }}</td>
                <td class="border border-slate-200 px-3 py-2">{{ employee.gender || '-' }}</td>
                <td class="border border-slate-200 px-3 py-2">{{ employee.position || '-' }}</td>
              </tr>
              <tr v-if="!employees.length">
                <td colspan="5" class="border border-slate-200 px-3 py-6 text-center text-slate-500">
                  Belum ada employee yang tersedia.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex justify-end gap-3">
          <button
            type="button"
            class="rounded bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-300"
            @click="employeeModalOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="rounded bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-500"
            @click="handleGenerateFullMonth"
          >
            Generate
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const employeeModalOpen = ref(false);

function handleGenerateFullMonth() {
  employeeModalOpen.value = false;
  emit('generate-full-month');
}

function isGeneratedRedDay(day, employee) {
  return Boolean(day?.isSunday || day?.isHoliday || employee?.leave_days?.includes(day?.day));
}

defineProps({
  entry: {
    type: Object,
    required: true,
  },
  rows: {
    type: Array,
    required: true,
  },
  days: {
    type: Array,
    required: true,
  },
  canApproveEntry: {
    type: Boolean,
    required: true,
  },
  employees: {
    type: Array,
    default: () => [],
  },
  generatedEmployees: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits([
  'approve',
  'update-general-field',
  'toggle-day',
  'toggle-generated-day',
  'generate-full-month',
]);
</script>
