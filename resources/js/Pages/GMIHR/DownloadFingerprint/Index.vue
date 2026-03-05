<template>
  <AppLayout>
    <div class="p-6 max-w-6xl">
      <h2 class="text-2xl font-bold mb-4">Download FingerPrint</h2>

      <div class="bg-slate-800 p-4 rounded-lg border border-slate-700">
        <div class="mb-3">
          <button
            class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-medium"
            @click="openPopup"
          >
            Download scanlog
          </button>
        </div>

        <div class="overflow-auto">
          <table class="w-full text-sm border-collapse">
            <thead>
              <tr class="bg-slate-900 text-slate-300">
                <th class="px-3 py-2 text-left border border-slate-700 w-20">Pilih</th>
                <th class="px-3 py-2 text-left border border-slate-700">Nama</th>
                <th class="px-3 py-2 text-left border border-slate-700">SN</th>
                <th class="px-3 py-2 text-left border border-slate-700">No Mesin</th>
                <th class="px-3 py-2 text-left border border-slate-700">Koneksi</th>
                <th class="px-3 py-2 text-left border border-slate-700">Nama Akun</th>
                <th class="px-3 py-2 text-left border border-slate-700">Server Cloud</th>
                <th class="px-3 py-2 text-left border border-slate-700">IP Address / URL</th>
                <th class="px-3 py-2 text-left border border-slate-700">Layar</th>
              </tr>
            </thead>
            <tbody>
              <tr class="border-b border-slate-700">
                <td class="px-3 py-2 border border-slate-700">
                  <input type="checkbox" checked />
                </td>
                <td class="px-3 py-2 border border-slate-700">Mesin 1</td>
                <td class="px-3 py-2 border border-slate-700">FIO66207023190107</td>
                <td class="px-3 py-2 border border-slate-700">1</td>
                <td class="px-3 py-2 border border-slate-700">Ethernet</td>
                <td class="px-3 py-2 border border-slate-700">-</td>
                <td class="px-3 py-2 border border-slate-700">-</td>
                <td class="px-3 py-2 border border-slate-700">192.168.8.169</td>
                <td class="px-3 py-2 border border-slate-700">TFT</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-if="showPopup" class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
      <div class="w-full max-w-5xl bg-slate-800 text-slate-100 rounded-lg border border-slate-700 shadow-2xl">
        <div class="p-3 border-b border-slate-700 flex items-center justify-between">
          <h3 class="font-semibold">Download scanlog</h3>
          <button class="px-2 py-1 text-sm rounded-lg border border-slate-600 bg-slate-700 hover:bg-slate-600" @click="closePopup">X</button>
        </div>

        <div class="p-3 space-y-3">
          <div class="grid grid-cols-[170px_1fr] gap-2 items-center">
            <span class="text-indigo-300 font-semibold">Download scanlog :</span>
            <div class="flex items-center gap-3">
              <div class="relative group">
                <SearchableSelect
                  v-model="selectedMonth"
                  :options="monthSelectOptions"
                  option-value="value"
                  option-label="label"
                  placeholder=" "
                  empty-label="Pilih Bulan"
                  input-class="w-48 h-[46px] !pl-3 !pr-10 !pt-3 !pb-3 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100"
                  button-class="border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
                />
                <label
                  :class="[
                    'pointer-events-none absolute left-3 z-10 transition-all',
                    (selectedMonth
                      ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                      : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                    'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
                  ]"
                >
                  Bulan
                </label>
              </div>
              <div class="relative group">
                <SearchableSelect
                  v-model="selectedYear"
                  :options="yearSelectOptions"
                  option-value="value"
                  option-label="label"
                  placeholder=" "
                  empty-label="Pilih Tahun"
                  input-class="w-32 h-[46px] !pl-3 !pr-10 !pt-3 !pb-3 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100"
                  button-class="border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
                />
                <label
                  :class="[
                    'pointer-events-none absolute left-3 z-10 transition-all',
                    (selectedYear
                      ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                      : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                    'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
                  ]"
                >
                  Tahun
                </label>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-[170px_1fr_auto_auto] gap-2 items-center">
            <button
              class="h-[42px] px-4 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white"
              @click="onDownloadClick"
            >
              Download scanlog
            </button>
            <div class="flex-1 h-[42px] rounded-lg border border-slate-700 overflow-hidden bg-slate-800">
              <div class="h-full bg-sky-500 transition-all duration-300" style="width: 0%"></div>
            </div>
            <span class="w-12 -ml-1 text-left text-slate-300">0/0</span>
            <button
              class="h-[42px] px-4 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-100"
            >
              Stop
            </button>
          </div>
        </div>

        <div class="px-3 pb-3">
          <div class="border border-slate-700 rounded overflow-x-auto overflow-y-hidden">
            <table class="w-full min-w-max text-sm">
              <thead class="bg-slate-900 text-slate-300">
                <tr>
                  <th class="text-left px-3 py-2 border-r border-slate-700 w-16">No</th>
                  <th class="text-left px-3 py-2 border-r border-slate-700 min-w-[220px]">Nama</th>
                  <th
                    v-for="day in dayColumns"
                    :key="`day-num-${day.day}`"
                    class="text-center px-2 py-1 border-r border-b border-slate-700 min-w-[44px]"
                  >
                    {{ day.day }}
                  </th>
                </tr>
                <tr>
                  <th class="px-3 py-1 border-r border-slate-700"></th>
                  <th class="px-3 py-1 border-r border-slate-700"></th>
                  <th
                    v-for="day in dayColumns"
                    :key="`day-name-${day.day}`"
                    class="text-center px-2 py-1 border-r border-slate-700 text-xs text-slate-400"
                  >
                    {{ day.dayName }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td :colspan="2 + dayColumns.length" class="px-3 py-16 text-center text-slate-400 bg-slate-800">Belum ada data preview.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="p-3 border-t border-slate-700">
          <button class="h-[42px] px-4 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-100">Simpan log</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Swal from 'sweetalert2';

const showPopup = ref(false);
const selectedYear = ref(null);
const selectedMonth = ref(null);

const yearOptions = computed(() => {
  const years = [];
  for (let y = 2025; y <= 2030; y++) years.push(y);
  return years;
});

const yearSelectOptions = computed(() => yearOptions.value.map((y) => ({ value: y, label: String(y) })));
const monthSelectOptions = computed(() => monthOptions.value.map((m) => ({ value: m.value, label: m.label })));
const dayNames = ['Mgg', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

const monthOptions = computed(() => {
  const allMonths = [
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' },
  ];
  if (Number(selectedYear.value) === 2025) {
    return allMonths.filter((m) => m.value >= 11);
  }
  return allMonths;
});

const dayColumns = computed(() => {
  const year = Number(selectedYear.value || 0);
  const month = Number(selectedMonth.value || 0);
  if (!year || !month) return [];

  // Cut off period: 25 previous month until 26 selected month.
  const startDate = new Date(year, month - 2, 25);
  const endDate = new Date(year, month - 1, 26);
  const days = [];
  const cursor = new Date(startDate);

  while (cursor <= endDate) {
    const weekday = cursor.getDay();
    days.push({
      dateKey: cursor.toISOString().slice(0, 10),
      day: cursor.getDate(),
      dayName: dayNames[weekday],
    });
    cursor.setDate(cursor.getDate() + 1);
  }

  return days;
});

watch(selectedYear, (year) => {
  if (Number(year) === 2025 && Number(selectedMonth.value) < 11) {
    selectedMonth.value = 11;
  }
});

function openPopup() {
  showPopup.value = true;
}

function closePopup() {
  showPopup.value = false;
}

function onDownloadClick() {
  if (!selectedYear.value || !selectedMonth.value) {
    Swal.fire({
      title: 'Info',
      text: 'Pilih bulan dan tahun terlebih dahulu',
      width: 260,
      padding: '0.6rem',
      customClass: {
        popup: 'swal-mini-popup',
        title: 'swal-mini-title',
        htmlContainer: 'swal-mini-text',
        confirmButton: 'swal-mini-btn',
      },
      confirmButtonColor: '#4f46e5',
    });
    return;
  }

  const year = Number(selectedYear.value || 0);
  const month = Number(selectedMonth.value || 0);
  const isBeforeMarch2026 = year < 2026 || (year === 2026 && month < 3);
  const isMarch2026OrAfter = year > 2026 || (year === 2026 && month >= 3);

  if (isBeforeMarch2026) {
    Swal.fire({
      title: 'Info',
      text: 'Data sudah pernah di download',
      width: 260,
      padding: '0.6rem',
      customClass: {
        popup: 'swal-mini-popup',
        title: 'swal-mini-title',
        htmlContainer: 'swal-mini-text',
        confirmButton: 'swal-mini-btn',
      },
      confirmButtonColor: '#4f46e5',
    });
    return;
  }

  if (isMarch2026OrAfter) {
    Swal.fire({
      title: 'Info',
      text: 'Data hanya bisa di download waktu cut off',
      width: 260,
      padding: '0.6rem',
      customClass: {
        popup: 'swal-mini-popup',
        title: 'swal-mini-title',
        htmlContainer: 'swal-mini-text',
        confirmButton: 'swal-mini-btn',
      },
      confirmButtonColor: '#4f46e5',
    });
    return;
  }
}

</script>

<style scoped>
:deep(.swal-mini-popup) {
  border-radius: 10px !important;
}

:deep(.swal-mini-popup .swal2-icon) {
  margin: 0.35rem auto 0.25rem !important;
}

:deep(.swal-mini-popup .swal2-icon.swal2-info) {
  width: 2.6em !important;
  height: 2.6em !important;
  border-width: 0.14em !important;
  font-size: 1.1rem !important;
  line-height: 2.6em !important;
}

:deep(.swal-mini-popup .swal2-icon.swal2-info .swal2-icon-content) {
  font-size: 1.5rem !important;
}

:deep(.swal-mini-title) {
  font-size: 1.05rem !important;
  margin: 0.2rem 0 0.2rem !important;
}

:deep(.swal-mini-text) {
  font-size: 0.9rem !important;
  margin: 0 !important;
}

:deep(.swal-mini-btn) {
  font-size: 0.9rem !important;
  padding: 0.4rem 1.1rem !important;
  border-radius: 8px !important;
}
</style>
