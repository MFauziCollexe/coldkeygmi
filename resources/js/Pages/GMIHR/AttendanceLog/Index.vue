<template>
  <AppLayout>
    <div class="space-y-6 p-4 md:p-6">
      <div>
        <h2 class="text-2xl font-bold">Attendance Logs</h2>
        <p class="text-slate-400 text-sm">
          Perbandingan absensi fingerprint dengan roster karyawan.
        </p>
      </div>

      <div class="rounded-lg border border-slate-700 bg-slate-800 p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <p class="text-sm font-semibold text-slate-200">Ringkasan</p>
            <p class="text-xs text-slate-400">Jumlah per status berdasarkan hasil evaluasi attendance.</p>
          </div>
          <div class="text-left sm:text-right">
            <p class="text-[11px] text-slate-400">Total</p>
            <p class="text-lg font-semibold text-slate-100">{{ summaryTotal }}</p>
          </div>
        </div>

        <div v-if="!summaryBars.length" class="mt-4 text-sm text-slate-400">
          Tidak ada ringkasan untuk filter ini.
        </div>

        <div v-else class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-12">
          <div class="lg:col-span-8">
            <div class="flex min-h-56 flex-wrap items-end gap-2 overflow-x-auto pb-2">
              <div
                v-for="bar in summaryBars"
                :key="bar.key"
                class="flex w-14 shrink-0 flex-col items-center justify-end"
                :title="`${bar.label}: ${bar.value}`"
              >
                <div class="text-[11px] text-slate-200 mb-1 tabular-nums">{{ bar.value }}</div>
                <div
                  class="w-full rounded-t border border-slate-700 shadow-[0_0_0_1px_rgba(255,255,255,0.05)]"
                  :class="bar.colorClass"
                  :style="{ height: `${barHeightPx(bar.value)}px` }"
                ></div>
                <div class="w-full text-[11px] text-slate-300 mt-1 text-center leading-tight truncate">
                  {{ bar.shortLabel }}
                </div>
              </div>
            </div>
          </div>

          <div class="rounded-lg border border-slate-700 bg-slate-900/30 p-3 lg:col-span-4">
            <p class="text-sm font-semibold text-slate-200">Keterangan (Periode)</p>
            <p class="text-[11px] text-slate-400 mt-0.5">
              Minimal {{ Number(props.monthlyInsights?.min_count || 5) }}x. Terlambat dan Tidak Masuk per bulan (top nama).
            </p>

            <div class="mt-3 space-y-3">
              <div v-for="m in monthlyInsightsMonths" :key="m.month_key" class="border-t border-slate-700/60 pt-2 first:border-t-0 first:pt-0">
                <p class="text-xs font-semibold text-slate-200">{{ m.month_label }}</p>

                <div class="mt-1 text-[12px] text-slate-300">
                  <span class="font-semibold text-amber-200">Terlambat:&nbsp;</span>
                  <span class="text-slate-400">{{ Number(m?.late?.people || 0) }} orang</span>
                </div>
                <div class="text-[11px] text-slate-400 leading-snug">
                  {{ formatPeopleList(m?.late?.top, m?.late?.others) }}
                </div>

                <div class="mt-2 text-[12px] text-slate-300">
                  <span class="font-semibold text-rose-200">Tidak Masuk:&nbsp;</span>
                  <span class="text-slate-400">{{ Number(m?.absent?.people || 0) }} orang</span>
                </div>
                <div class="text-[11px] text-slate-400 leading-snug">
                  {{ formatPeopleList(m?.absent?.top, m?.absent?.others) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-slate-700 bg-slate-800 p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
          <div class="md:col-span-2">
            <label class="text-xs text-slate-300">Tanggal Dari</label>
            <input
              v-model="form.date_from"
              type="date"
              class="mt-1 w-full rounded bg-slate-900 border border-slate-600 px-3 py-2 text-sm"
            />
          </div>
          <div class="md:col-span-2">
            <label class="text-xs text-slate-300">Sampai</label>
            <input
              v-model="form.date_to"
              type="date"
              class="mt-1 w-full rounded bg-slate-900 border border-slate-600 px-3 py-2 text-sm"
            />
          </div>
          <div class="md:col-span-2">
            <label class="text-xs text-slate-300">Status</label>
            <select v-model="form.status" class="mt-1 w-full rounded bg-slate-900 border border-slate-600 px-3 py-2 text-sm">
              <option value="all">Semua</option>
              <option value="on_time">On Time</option>
              <option value="terlambat">Terlambat</option>
              <option value="tidak_masuk">Tidak Masuk</option>
              <option value="tidak_scan_masuk">Tidak Scan masuk</option>
              <option value="tidak_scan_pulang">Tidak Scan pulang</option>
              <option value="izin">Izin</option>
              <option value="sakit">Sakit</option>
              <option value="cuti">Cuti</option>
              <option value="dinas_luar">Dinas Luar</option>
              <option value="off">OFF</option>
              <option value="libur_nasional">Libur Nasional</option>
              <option value="cek_lagi">Cek Lagi</option>
              <option value="no_roster">Tanpa Roster</option>
            </select>
          </div>
          <div class="md:col-span-3">
            <label class="text-xs text-slate-300">Cari PIN / Nama</label>
            <input v-model="form.q" type="text" class="mt-1 w-full rounded bg-slate-900 border border-slate-600 px-3 py-2 text-sm" placeholder="contoh: 25111724 / EKO" />
          </div>
          <div class="md:col-span-3 flex items-end">
            <div class="flex w-full flex-col gap-2 xl:flex-row">
              <button class="w-full xl:flex-1 px-3 py-2 rounded bg-sky-600 hover:bg-sky-500 text-sm font-semibold" @click="applyFilters">
                Tampilkan
              </button>
              <button class="w-full xl:w-auto px-3 py-2 rounded bg-emerald-600 hover:bg-emerald-500 text-sm font-semibold" @click="exportExcel">
                Export Excel
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-slate-700 bg-slate-800 p-4">
        <div v-if="!attendanceLogs.data?.length" class="text-sm text-slate-400">
          Tidak ada data untuk filter ini.
        </div>

        <div v-else class="hidden overflow-auto lg:block">
          <table class="w-full text-sm">
            <thead class="border-b border-slate-700 text-slate-400">
              <tr>
                <th class="text-left py-2 pr-3">No</th>
                <th class="text-left py-2 pr-3">PIN</th>
                <th class="text-left py-2 pr-3">Nama</th>
                <th class="text-left py-2 pr-3">NRP</th>
                <th class="text-left py-2 pr-3">Department</th>
                <th class="text-left py-2 pr-3">Absensi</th>
                <th class="text-left py-2 pr-3">Terlambat</th>
                <th class="text-left py-2 pr-3">Absen</th>
                <th class="text-left py-2">Lain-lain</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(group, idx) in employeeGroups" :key="group.key">
                <tr class="border-b border-slate-700/50 cursor-pointer hover:bg-slate-700/30" @click="toggleGroup(group.key)">
                  <td class="py-2 pr-3">
                    <span class="inline-flex items-center gap-2">
                      <span class="text-slate-300">{{ isGroupExpanded(group.key) ? '▾' : '▸' }}</span>
                      <span>{{ idx + 1 }}</span>
                    </span>
                  </td>
                  <td class="py-2 pr-3">{{ group.pin }}</td>
                  <td class="py-2 pr-3">{{ group.name }}</td>
                  <td class="py-2 pr-3">{{ group.nrp }}</td>
                  <td class="py-2 pr-3">{{ group.departmentName || '-' }}</td>
                  <td class="py-2 pr-3">{{ group.totalAbsensi }}</td>
                  <td class="py-2 pr-3">
                    <span class="inline-flex min-w-[2rem] justify-center px-2 py-0.5 rounded-md text-xs font-semibold border bg-amber-500/20 text-amber-200 border-amber-400/40">
                      {{ group.totalTerlambat }}
                    </span>
                  </td>
                  <td class="py-2 pr-3">
                    <span class="inline-flex min-w-[2rem] justify-center px-2 py-0.5 rounded-md text-xs font-semibold border bg-rose-600/20 text-rose-200 border-rose-500/40">
                      {{ group.totalAbsen }}
                    </span>
                  </td>
                  <td class="py-2">
                    <span class="inline-flex min-w-[2rem] justify-center px-2 py-0.5 rounded-md text-xs font-semibold border bg-orange-500/20 text-orange-200 border-orange-400/40">
                      {{ group.totalLain }}
                    </span>
                  </td>
                </tr>

                <tr v-if="isGroupExpanded(group.key)" class="border-b border-slate-700/50 bg-slate-900/30">
                  <td colspan="9" class="py-3">
                    <div class="overflow-auto">
                      <table class="w-full text-sm">
                        <thead class="border-b border-slate-700 text-slate-400">
                          <tr>
                            <th class="text-left py-2 pr-3">Tanggal</th>
                            <th class="text-left py-2 pr-3">PIN</th>
                            <th class="text-left py-2 pr-3">Nama</th>
                            <th class="text-left py-2 pr-3">Shift</th>
                            <th class="text-left py-2 pr-3">Hari</th>
                            <th class="text-left py-2 pr-3">Jadwal</th>
                            <th class="text-left py-2 pr-3">Masuk</th>
                            <th class="text-left py-2 pr-3">Pulang</th>
                            <th class="text-left py-2 pr-3">Lembur</th>
                            <th class="text-left py-2">Status</th>
                            <th v-if="canShowCorrectionColumn" class="text-left py-2">Koreksi</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="row in group.rows" :key="`${row.log_date}-${row.pin}-${row.status}`" class="border-b border-slate-700/50">
                            <td class="py-2 pr-3">{{ row.log_date || '-' }}</td>
                            <td class="py-2 pr-3">{{ row.pin || '-' }}</td>
                            <td class="py-2 pr-3">{{ row.name || '-' }}</td>
                            <td class="py-2 pr-3">{{ row.shift_code || (row.is_off ? 'OFF' : '-') }}</td>
                            <td class="py-2 pr-3">{{ formatDayName(row.log_date) }}</td>
                            <td class="py-2 pr-3">{{ formatSchedule(row.start_time, row.end_time) }}</td>
                            <td class="py-2 pr-3">{{ formatTimeOnly(row.first_scan) }}</td>
                            <td class="py-2 pr-3">{{ formatTimeOnly(row.last_scan) }}</td>
                            <td class="py-2 pr-3">
                              <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold border bg-cyan-500/20 text-cyan-200 border-cyan-400/40">
                                {{ row.overtime_form_label || '-' }}
                              </span>
                            </td>
                            <td class="py-2">
                              <div class="flex items-center gap-2">
                                <span :class="statusPillClass(getDisplayExpected(row))" class="inline-flex items-center gap-2 px-2.5 py-1 rounded text-xs font-semibold border">
                                  <span :class="statusDotClass(getDisplayExpected(row))" class="inline-block w-2 h-2 rounded-sm"></span>
                                  {{ getDisplayExpected(row) }}
                                </span>
                              </div>
                            </td>
                            <td v-if="canShowCorrectionColumn" class="py-2">
                              <div class="flex flex-col gap-1.5">
                                <div v-if="row.correction" class="flex flex-wrap items-center gap-2">
                                  <span
                                    :class="correctionPillClass(row.correction.status)"
                                    class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold border"
                                    :title="row.correction.rejection_reason || ''"
                                  >
                                    {{ correctionLabel(row.correction.status) }}
                                  </span>
                                  <span class="text-[11px] text-slate-400">
                                    {{ formatTimeOnly(row.correction.first_scan) }} - {{ formatTimeOnly(row.correction.last_scan) }}
                                  </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                  <button
                                    v-if="canShowCorrectionColumn"
                                    type="button"
                                    class="px-2.5 py-1 rounded bg-indigo-600 hover:bg-indigo-500 text-[12px] font-semibold"
                                    @click="openCorrectionSwal(row)"
                                  >
                                    Koreksi
                                  </button>

                                  <span v-if="!canShowCorrectionColumn && !row.correction" class="text-[11px] text-slate-500">-</span>
                                </div>
                              </div>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <div v-if="attendanceLogs.data?.length" class="space-y-3 lg:hidden">
          <div
            v-for="(group, idx) in employeeGroups"
            :key="`mobile-${group.key}`"
            class="rounded-xl border border-slate-700 bg-slate-900/40 p-4"
          >
            <button
              type="button"
              class="flex w-full items-start justify-between gap-3 text-left"
              @click="toggleGroup(group.key)"
            >
              <div>
                <div class="text-sm text-slate-400">#{{ idx + 1 }} · {{ group.pin }}</div>
                <div class="font-semibold text-white">{{ group.name }}</div>
                <div class="text-sm text-slate-400">NRP: {{ group.nrp }}</div>
                <div class="text-sm text-slate-400">Department: {{ group.departmentName || '-' }}</div>
              </div>
              <div class="text-right">
                <div class="text-xs text-slate-400">
                  {{ isGroupExpanded(group.key) ? 'Sembunyikan' : 'Lihat' }}
                </div>
                <div class="mt-1 text-lg text-slate-300">{{ isGroupExpanded(group.key) ? '▾' : '▸' }}</div>
              </div>
            </button>

            <div class="mt-4 grid grid-cols-3 gap-2 text-center text-sm">
              <div class="rounded-lg border border-slate-700 bg-slate-800/80 p-2">
                <div class="text-[11px] text-slate-400">Absensi</div>
                <div class="font-semibold text-white">{{ group.totalAbsensi }}</div>
              </div>
              <div class="rounded-lg border border-amber-400/30 bg-amber-500/10 p-2">
                <div class="text-[11px] text-amber-200">Terlambat</div>
                <div class="font-semibold text-amber-100">{{ group.totalTerlambat }}</div>
              </div>
              <div class="rounded-lg border border-rose-400/30 bg-rose-500/10 p-2">
                <div class="text-[11px] text-rose-200">Absen/Lain</div>
                <div class="font-semibold text-rose-100">{{ group.totalAbsen + group.totalLain }}</div>
              </div>
            </div>

            <div v-if="isGroupExpanded(group.key)" class="mt-4 overflow-hidden rounded-lg border border-slate-700 bg-slate-800/60">
              <div
                v-for="row in group.rows"
                :key="`mobile-row-${row.log_date}-${row.pin}-${row.status}`"
                class="border-b border-slate-700 p-3 last:border-b-0"
              >
                <div class="mb-3 flex items-start justify-between gap-3">
                  <div>
                    <div class="font-semibold text-white">{{ row.log_date || '-' }}</div>
                    <div class="text-xs text-slate-400">{{ formatDayName(row.log_date) }}</div>
                  </div>
                  <span :class="statusPillClass(getDisplayExpected(row))" class="inline-flex items-center gap-2 rounded border px-2.5 py-1 text-xs font-semibold">
                    <span :class="statusDotClass(getDisplayExpected(row))" class="inline-block h-2 w-2 rounded-sm"></span>
                    {{ getDisplayExpected(row) }}
                  </span>
                </div>

                <div class="space-y-2 text-sm">
                  <div class="flex items-start justify-between gap-3">
                    <div class="text-slate-400">Shift / Hari</div>
                    <div class="text-right">{{ row.shift_code || (row.is_off ? 'OFF' : '-') }} / {{ formatDayName(row.log_date) }}</div>
                  </div>
                  <div class="flex items-start justify-between gap-3">
                    <div class="text-slate-400">Jadwal</div>
                    <div class="text-right">{{ formatSchedule(row.start_time, row.end_time) }}</div>
                  </div>
                  <div class="flex items-start justify-between gap-3">
                    <div class="text-slate-400">Masuk</div>
                    <div class="text-right">{{ formatTimeOnly(row.first_scan) }}</div>
                  </div>
                  <div class="flex items-start justify-between gap-3">
                    <div class="text-slate-400">Pulang</div>
                    <div class="text-right">{{ formatTimeOnly(row.last_scan) }}</div>
                  </div>
                  <div class="flex items-start justify-between gap-3">
                    <div class="text-slate-400">Lembur</div>
                    <div class="text-right">
                      <span class="inline-flex rounded-md border border-cyan-400/40 bg-cyan-500/20 px-2 py-0.5 text-xs font-semibold text-cyan-200">
                        {{ row.overtime_form_label || '-' }}
                      </span>
                    </div>
                  </div>
                  <div v-if="canShowCorrectionColumn" class="flex items-start justify-between gap-3">
                    <div class="text-slate-400">Koreksi</div>
                    <div v-if="row.correction" class="space-y-1 text-right">
                      <span
                        :class="correctionPillClass(row.correction.status)"
                        class="inline-flex rounded-md border px-2 py-0.5 text-xs font-semibold"
                        :title="row.correction.rejection_reason || ''"
                      >
                        {{ correctionLabel(row.correction.status) }}
                      </span>
                      <div class="text-[11px] text-slate-400">
                        {{ formatTimeOnly(row.correction.first_scan) }} - {{ formatTimeOnly(row.correction.last_scan) }}
                      </div>
                    </div>
                    <div v-else class="text-right text-slate-500">-</div>
                  </div>
                </div>

                <div v-if="canShowCorrectionColumn" class="mt-4">
                  <button
                    type="button"
                    class="w-full rounded bg-indigo-600 px-3 py-2 text-[12px] font-semibold hover:bg-indigo-500"
                    @click="openCorrectionSwal(row)"
                  >
                    Koreksi
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="attendanceLogs.last_page > 1" class="mt-4 flex justify-end border-t border-slate-700 pt-4 text-sm">
          <Pagination :paginator="attendanceLogs" :onPageChange="goToPage" />
        </div>


      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  attendanceLogs: {
    type: Object,
    default: () => ({
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 2000,
    }),
  },
  summary: {
    type: Object,
    default: () => ({}),
  },
  monthlyInsights: {
    type: Object,
    default: () => ({ months: [] }),
  },
  filters: {
    type: Object,
    default: () => ({
      date_from: '',
      date_to: '',
      status: 'all',
      q: '',
      per_page: 50,
    }),
  },
  canManageCorrections: {
    type: Boolean,
    default: false,
  },
});

const page = usePage();

const today = new Date();
const currentMonthStart = new Date(today.getFullYear(), today.getMonth(), 1);
const currentMonthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);

function formatDateInputValue(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

const form = reactive({
  date_from: String(props.filters.date_from || formatDateInputValue(currentMonthStart)),
  date_to: String(props.filters.date_to || formatDateInputValue(currentMonthEnd)),
  status: String(props.filters.status || 'all'),
  q: String(props.filters.q || ''),
  per_page: Number(props.filters.per_page || 2000),
});

const attendanceRows = ref(cloneAttendanceRows(props.attendanceLogs?.data || []));

watch(
  () => props.attendanceLogs?.data,
  (rows) => {
    attendanceRows.value = cloneAttendanceRows(rows || []);
  },
  { immediate: true }
);

const canManageCorrections = props.canManageCorrections === true;
const currentUser = computed(() => page.props.auth?.user || {});
const isCurrentUserItDepartment = computed(() => {
  const departmentCode = String(
    currentUser.value?.department?.code
    || currentUser.value?.department_code
    || currentUser.value?.departmentCode
    || ''
  ).trim().toUpperCase();
  const departmentName = String(
    currentUser.value?.department?.name
    || currentUser.value?.department_name
    || currentUser.value?.departmentName
    || ''
  ).trim().toUpperCase();

  return departmentCode === 'IT'
    || departmentName === 'IT'
    || departmentName.includes('INFORMATION TECHNOLOGY');
});
const canShowCorrectionColumn = computed(() => canManageCorrections && isCurrentUserItDepartment.value);
const MIN_HIGHLIGHT_COUNT = 5;

const summaryTotal = computed(() => Number(props.summary?.total || 0));

const monthlyInsightsMonths = computed(() => Array.isArray(props.monthlyInsights?.months) ? props.monthlyInsights.months : []);

function formatPeopleList(items, others) {
  const list = Array.isArray(items) ? items : [];
  const text = list
    .filter((it) => String(it?.name || '').trim() !== '')
    .map((it) => `${it.name} (${Number(it.count || 0)}x)`)
    .join(', ');
  if (!text) return 'Belum ada';
  if (Number(others || 0) > 0) return `${text} (+${others} orang lagi)`;
  return text;
}

function summaryColorClass(label) {
  const value = String(label || '').toLowerCase().trim();
  if (value === 'on time') return 'bg-emerald-400 border-emerald-200';
  if (value === 'terlambat') return 'bg-amber-400 border-amber-200';
  if (value === 'tidak masuk') return 'bg-rose-500 border-rose-200';
  if (value === 'tidak scan masuk') return 'bg-orange-400 border-orange-200';
  if (value === 'tidak scan pulang') return 'bg-orange-400 border-orange-200';
  if (value === 'off') return 'bg-slate-400 border-slate-200';
  if (value === 'cek lagi') return 'bg-pink-400 border-pink-200';
  if (value === 'libur nasional') return 'bg-slate-300 border-slate-200';
  if (value === 'izin') return 'bg-violet-400 border-violet-200';
  if (value === 'sakit') return 'bg-red-400 border-red-200';
  if (value === 'cuti') return 'bg-sky-400 border-sky-200';
  if (value === 'dinas luar') return 'bg-teal-400 border-teal-200';
  return 'bg-indigo-400 border-indigo-200';
}

function shortLabel(label) {
  const raw = String(label || '').trim();
  if (raw.toLowerCase() === 'tidak scan masuk') return 'No In';
  if (raw.toLowerCase() === 'tidak scan pulang') return 'No Out';
  if (raw.toLowerCase() === 'libur nasional') return 'Libur';
  if (raw.toLowerCase() === 'tidak masuk') return 'Absen';
  if (raw.toLowerCase() === 'terlambat') return 'Late';
  if (raw.toLowerCase() === 'on time') return 'On Time';
  if (raw.toLowerCase() === 'cek lagi') return 'Cek';
  if (raw.toLowerCase() === 'dinas luar') return 'DL';
  return raw.length > 10 ? raw.slice(0, 10) + '…' : raw;
}

const summaryBars = computed(() => {
  const expectedCounts = props.summary?.expected_counts || {};
  const entries = Object.entries(expectedCounts)
    .map(([label, value]) => ({
      label: String(label || '').trim(),
      value: Number(value || 0),
    }))
    .filter((item) => item.label !== '' && Number.isFinite(item.value) && item.value > 0)
    // Hide OFF from graph (still kept in data/filters if needed elsewhere).
    .filter((item) => item.label.toLowerCase().trim() !== 'off')
    // Hide Libur Nasional from graph.
    .filter((item) => item.label.toLowerCase().trim() !== 'libur nasional');

  const preferredOrder = [
    'On Time',
    'Terlambat',
    'Tidak Masuk',
    'Tidak Scan masuk',
    'Tidak Scan pulang',
    'Izin',
    'Sakit',
    'Cuti',
    'Dinas Luar',
    'Cek Lagi',
  ].map((v) => v.toLowerCase());

  entries.sort((a, b) => {
    const ai = preferredOrder.indexOf(a.label.toLowerCase());
    const bi = preferredOrder.indexOf(b.label.toLowerCase());
    if (ai !== -1 || bi !== -1) {
      if (ai === -1) return 1;
      if (bi === -1) return -1;
      return ai - bi;
    }
    if (b.value !== a.value) return b.value - a.value;
    return a.label.localeCompare(b.label);
  });

  return entries.map((item) => ({
    key: item.label.toLowerCase(),
    label: item.label,
    shortLabel: shortLabel(item.label),
    value: item.value,
    colorClass: summaryColorClass(item.label),
  }));
});

const summaryMaxValue = computed(() => {
  if (!summaryBars.value.length) return 0;
  return Math.max(...summaryBars.value.map((b) => b.value));
});

function barHeightPx(value) {
  const max = summaryMaxValue.value || 0;
  if (max <= 0) return 2;
  const chartHeight = 160; // px
  const h = Math.round((Number(value || 0) / max) * chartHeight);
  return Math.max(2, h);
}

const expandedGroups = reactive({});
const hiddenEmployeeNames = new Set([
  'ari budi',
  'daud setiawan',
  'heriyant',
  'm ali g',
  'm ramli',
]);
const hiddenEmployeeNamePrefixes = [
  'tholut',
  'yunanda',
];

function normalizeEmployeeName(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/[^a-z0-9\s]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function shouldHideEmployee(name) {
  const normalizedName = normalizeEmployeeName(name);
  if (!normalizedName) return false;
  if (hiddenEmployeeNames.has(normalizedName)) return true;
  return hiddenEmployeeNamePrefixes.some((prefix) => normalizedName.startsWith(prefix));
}

const employeeGroups = computed(() => {
  const grouped = new Map();
  const rows = attendanceRows.value || [];

  for (const row of rows) {
    const pin = String(row?.pin || '-');
    const name = String(row?.name || '-');
    const key = `${pin}|${name}`;

    if (!grouped.has(key)) {
      grouped.set(key, {
        key,
        pin,
        name,
        nrp: String(row?.nrp || row?.employee_nrp || row?.pin || '-'),
        departmentName: String(row?.department_name || '-'),
        totalAbsensi: 0,
        totalTerlambat: 0,
        totalAbsen: 0,
        totalLain: 0,
        rows: [],
      });
    }

    const item = grouped.get(key);
    item.totalAbsensi += 1;
    item.rows.push(row);

    const expected = String(getDisplayExpected(row) || '').toLowerCase().trim();
    if (expected === 'terlambat') {
      item.totalTerlambat += 1;
    } else if (expected === 'tidak masuk') {
      item.totalAbsen += 1;
    } else if (expected === 'tidak scan masuk' || expected === 'tidak scan pulang' || expected === 'cek lagi') {
      item.totalLain += 1;
    }
  }

  return Array.from(grouped.values())
    .filter((group) => !shouldHideEmployee(group.name))
    .map((group) => ({
      ...group,
      rows: [...group.rows].sort((a, b) => String(a.log_date || '').localeCompare(String(b.log_date || ''))),
    }))
    .sort((a, b) => {
      if (a.name === b.name) return a.pin.localeCompare(b.pin);
      return a.name.localeCompare(b.name);
    });
});

const topAttendanceInsights = computed(() => {
  const groups = employeeGroups.value;

  const listBy = (key) => {
    const people = groups
      .map((group) => ({
        name: String(group?.name || '-'),
        count: Number(group?.[key] || 0),
      }))
      .filter((person) => person.count >= MIN_HIGHLIGHT_COUNT)
      .sort((a, b) => {
        if (b.count === a.count) return a.name.localeCompare(b.name);
        return b.count - a.count;
      });

    return {
      names: people,
      count: people.length,
    };
  };

  return {
    terlambat: listBy('totalTerlambat'),
    absen: listBy('totalAbsen'),
  };
});

function topNamesText(item) {
  const names = Array.isArray(item?.names)
    ? item.names.filter((person) => String(person?.name || '').trim() !== '')
    : [];
  if (!names.length) return 'Belum ada';
  return names.map((person) => `${person.name} (${person.count}x)`).join(', ');
}

function isGroupExpanded(key) {
  return expandedGroups[key] === true;
}

function toggleGroup(key) {
  expandedGroups[key] = !expandedGroups[key];
}

function applyFilters() {
  router.get('/attendance-log', {
    date_from: form.date_from,
    date_to: form.date_to,
    status: form.status,
    q: form.q,
    per_page: form.per_page,
    page: 1,
  }, {
    preserveState: true,
    replace: true,
  });
}

function goToPage(page) {
  router.get('/attendance-log', {
    date_from: form.date_from,
    date_to: form.date_to,
    status: form.status,
    q: form.q,
    per_page: form.per_page,
    page,
  }, {
    preserveState: true,
    replace: true,
  });
}

function exportExcel() {
  const params = new URLSearchParams({
    date_from: String(form.date_from || ''),
    date_to: String(form.date_to || ''),
    status: String(form.status || 'all'),
    q: String(form.q || ''),
    per_page: String(form.per_page || 2000),
    export: '1',
  });
  window.open(`/attendance-log?${params.toString()}`, '_blank');
}

function formatSchedule(start, end) {
  if (!start || !end) return '-';
  return `${String(start).slice(0, 5)} - ${String(end).slice(0, 5)}`;
}

function formatTimeOnly(value) {
  if (!value) return '-';
  const parts = String(value).split(' ');
  const time = parts.length >= 2 ? parts[1] : String(value).slice(0, 8);
  return String(time).slice(0, 5) || '-';
}

function formatDayName(dateValue) {
  if (!dateValue) return '-';
  const date = new Date(`${dateValue}T00:00:00`);
  if (Number.isNaN(date.getTime())) return '-';
  return date.toLocaleDateString('id-ID', { weekday: 'long' });
}

function isSunday(dateValue) {
  if (!dateValue) return false;
  const date = new Date(`${dateValue}T00:00:00`);
  if (Number.isNaN(date.getTime())) return false;
  return date.getDay() === 0;
}

function getDisplayExpected(row) {
  const raw = String(row?.expected || '').trim();
  const lower = raw.toLowerCase();
  const isNoRoster = lower === 'tanpa roster' || lower === 'no roster' || lower === 'no_roster';
  if (isNoRoster && isSunday(row?.log_date)) return 'OFF';
  return raw || '-';
}

async function openCorrectionSwal(row) {
  const firstDefault = formatTimeOnly(row.first_scan) !== '-' ? formatTimeOnly(row.first_scan) : '';
  const lastDefault = formatTimeOnly(row.last_scan) !== '-' ? formatTimeOnly(row.last_scan) : '';
  const noteDefault = row.correction?.note || '';
  const safeFirst = escapeHtmlValue(firstDefault);
  const safeLast = escapeHtmlValue(lastDefault);
  const safeNote = escapeHtmlValue(noteDefault);

  const result = await Swal.fire({
    title: `Koreksi ${row.name || '-'} (${row.pin || '-'})`,
    html: `
      <div style="display:grid;grid-template-columns:1fr;gap:8px;text-align:left">
        <label style="font-size:12px;color:#94a3b8">Scan Masuk (HH:mm)</label>
        <input id="swal-first-time" type="time" class="swal2-input" style="margin:0;height:40px" value="${safeFirst}">
        <label style="font-size:12px;color:#94a3b8">Scan Pulang (HH:mm)</label>
        <input id="swal-last-time" type="time" class="swal2-input" style="margin:0;height:40px" value="${safeLast}">
        <label style="font-size:12px;color:#94a3b8">Catatan</label>
        <input id="swal-note" type="text" class="swal2-input" style="margin:0;height:40px" value="${safeNote}">
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Simpan Koreksi',
    cancelButtonText: 'Batal',
    preConfirm: () => {
      const first = normalizePromptTime(document.getElementById('swal-first-time')?.value || '');
      const last = normalizePromptTime(document.getElementById('swal-last-time')?.value || '');
      const note = String(document.getElementById('swal-note')?.value || '').trim();
      if (!first && !last) {
        Swal.showValidationMessage('Isi minimal salah satu jam koreksi.');
        return false;
      }
      return { first, last, note };
    },
  });

  if (!result.isConfirmed || !result.value) return;

  router.post('/attendance-log/corrections', {
    log_date: row.log_date,
    pin: row.pin,
    start_time: row.start_time || null,
    end_time: row.end_time || null,
    corrected_first_time: result.value.first,
    corrected_last_time: result.value.last,
    note: result.value.note || null,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      applyLocalCorrection(row, result.value);
    },
    onError: (errors) => {
      const messages = Object.values(errors || {})
        .flat()
        .map((message) => String(message || '').trim())
        .filter(Boolean);

      Swal.fire({
        icon: 'error',
        title: 'Koreksi Gagal',
        text: messages[0] || 'Data koreksi belum berhasil disimpan.',
      });
    },
  });
}

function normalizePromptTime(value) {
  const v = String(value || '').trim();
  if (!v) return null;
  const match = /^([01]\d|2[0-3]):([0-5]\d)$/.test(v);
  return match ? v : null;
}

function cloneAttendanceRows(rows) {
  return Array.isArray(rows)
    ? rows.map((row) => ({
      ...row,
      correction: row?.correction ? { ...row.correction } : null,
    }))
    : [];
}

function applyLocalCorrection(targetRow, payload) {
  const row = attendanceRows.value.find((item) =>
    String(item?.log_date || '') === String(targetRow?.log_date || '')
      && String(item?.pin || '') === String(targetRow?.pin || '')
  );

  if (!row) {
    return;
  }

  const firstScan = payload.first
    ? buildClientCorrectionDateTime(row.log_date, payload.first, row.start_time, false)
    : null;
  const lastScan = payload.last
    ? buildClientCorrectionDateTime(row.log_date, payload.last, row.start_time, true)
    : null;

  row.first_scan = firstScan;
  row.last_scan = lastScan;
  row.scan_time = firstScan ? String(firstScan).split(' ')[1] || null : null;
  row.correction = {
    ...(row.correction || {}),
    status: 'approved',
    first_scan: firstScan,
    last_scan: lastScan,
    note: payload.note || null,
    rejection_reason: null,
  };

  const evaluation = evaluateCorrectedRowStatus(row, firstScan, lastScan);
  row.expected = evaluation.expected;
  row.status = evaluation.status;
  row.reason = 'Koreksi attendance diterapkan.';
}

function buildClientCorrectionDateTime(logDate, timeHm, startTime, isLastScan) {
  if (!logDate || !timeHm) return null;

  const base = `${logDate} ${timeHm}:00`;
  if (!isLastScan || !startTime) {
    return base;
  }

  const startHm = String(startTime).slice(0, 5);
  if (timeHm >= startHm) {
    return base;
  }

  const date = new Date(`${logDate}T00:00:00`);
  if (Number.isNaN(date.getTime())) {
    return base;
  }

  date.setDate(date.getDate() + 1);
  return `${formatDateInputValue(date)} ${timeHm}:00`;
}

function evaluateCorrectedRowStatus(row, firstScan, lastScan) {
  const lockedExpected = String(row?.expected || '').toLowerCase();
  if (['off', 'libur nasional', 'izin', 'sakit', 'cuti', 'dinas luar'].includes(lockedExpected)) {
    return {
      expected: row.expected,
      status: row.status || 'matched',
    };
  }

  if (!firstScan && !lastScan) {
    return { expected: 'Tidak Masuk', status: 'missing_scan' };
  }

  if (!firstScan && lastScan) {
    return { expected: 'Tidak Scan masuk', status: 'missing_checkin' };
  }

  if (firstScan && !lastScan) {
    if (isLateBeyondTolerance(row?.start_time, firstScan)) {
      return { expected: 'Terlambat', status: 'time_mismatch' };
    }

    return { expected: 'Tidak Scan pulang', status: 'missing_checkout' };
  }

  if (isLateBeyondTolerance(row?.start_time, firstScan)) {
    return { expected: 'Terlambat', status: 'time_mismatch' };
  }

  return { expected: 'On Time', status: 'matched' };
}

function isLateBeyondTolerance(startTime, scanDateTime) {
  if (!startTime || !scanDateTime) return false;

  const startHm = String(startTime).slice(0, 5);
  const scanHm = formatTimeOnly(scanDateTime);
  if (startHm === '-' || scanHm === '-') return false;

  const [startHour, startMinute] = startHm.split(':').map(Number);
  const [scanHour, scanMinute] = scanHm.split(':').map(Number);
  const startTotal = (startHour * 60) + startMinute;
  const scanTotal = (scanHour * 60) + scanMinute;

  return (scanTotal - startTotal) > 10;
}

function escapeHtmlValue(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function correctionLabel(status) {
  if (status === 'approved') return 'Approved';
  if (status === 'rejected') return 'Rejected';
  if (status === 'pending') return 'Pending';
  return '-';
}

function correctionPillClass(status) {
  if (status === 'approved') return 'bg-emerald-600/20 text-emerald-200 border-emerald-500/40';
  if (status === 'rejected') return 'bg-rose-600/20 text-rose-200 border-rose-500/40';
  if (status === 'pending') return 'bg-amber-500/20 text-amber-200 border-amber-400/40';
  return 'bg-slate-600/20 text-slate-200 border-slate-500/30';
}

function statusPillClass(expected) {
  const value = String(expected || '').toLowerCase();
  if (value === 'on time') return 'bg-emerald-600/20 text-emerald-200 border-emerald-500/40';
  if (value === 'terlambat') return 'bg-amber-500/20 text-amber-200 border-amber-400/40';
  if (value === 'tidak masuk') return 'bg-rose-600/20 text-rose-200 border-rose-500/40';
  if (value === 'tidak scan pulang') return 'bg-orange-500/20 text-orange-200 border-orange-400/40';
  if (value === 'tidak scan masuk') return 'bg-orange-500/20 text-orange-200 border-orange-400/40';
  if (value === 'izin') return 'bg-violet-500/20 text-violet-200 border-violet-400/40';
  if (value === 'sakit') return 'bg-red-500/20 text-red-200 border-red-400/40';
  if (value === 'cuti') return 'bg-sky-500/20 text-sky-200 border-sky-400/40';
  if (value === 'dinas luar') return 'bg-teal-500/20 text-teal-200 border-teal-400/40';
  if (value === 'cek lagi') return 'bg-pink-500/20 text-pink-200 border-pink-400/40';
  if (value === 'off') return 'bg-slate-500/20 text-slate-200 border-slate-400/30';
  if (value === 'libur nasional') return 'bg-cyan-500/20 text-cyan-200 border-cyan-400/40';
  return 'bg-slate-600/20 text-slate-200 border-slate-500/30';
}

function statusDotClass(expected) {
  const value = String(expected || '').toLowerCase();
  if (value === 'on time') return 'bg-emerald-400';
  if (value === 'terlambat') return 'bg-amber-300';
  if (value === 'tidak masuk') return 'bg-rose-400';
  if (value === 'tidak scan pulang') return 'bg-orange-300';
  if (value === 'tidak scan masuk') return 'bg-orange-300';
  if (value === 'izin') return 'bg-violet-300';
  if (value === 'sakit') return 'bg-red-300';
  if (value === 'cuti') return 'bg-sky-300';
  if (value === 'dinas luar') return 'bg-teal-300';
  if (value === 'cek lagi') return 'bg-pink-300';
  if (value === 'off') return 'bg-slate-300';
  if (value === 'libur nasional') return 'bg-cyan-300';
  return 'bg-slate-300';
}

function statusLabel(status) {
  if (status === 'matched') return 'Sesuai Roster';
  if (status === 'missing_scan') return 'Belum Scan';
  if (status === 'missing_checkout') return 'Belum Scan Pulang';
  if (status === 'time_mismatch') return 'Jam Tidak Sesuai';
  if (status === 'izin') return 'Izin';
  if (status === 'sakit') return 'Sakit';
  if (status === 'cuti') return 'Cuti';
  if (status === 'dinas_luar') return 'Dinas Luar';
  if (status === 'offday') return 'OFF';
  if (status === 'holiday_national') return 'Libur Nasional';
  if (status === 'offday_scan') return 'OFF tapi Scan';
  if (status === 'no_roster') return 'Tanpa Roster';
  return status || '-';
}

function statusClass(status) {
  if (status === 'matched') return 'bg-emerald-600/30 text-emerald-200';
  if (status === 'missing_scan') return 'bg-rose-600/30 text-rose-200';
  if (status === 'missing_checkout') return 'bg-red-600/30 text-red-200';
  if (status === 'time_mismatch') return 'bg-orange-600/30 text-orange-200';
  if (status === 'izin') return 'bg-violet-600/30 text-violet-200';
  if (status === 'sakit') return 'bg-red-600/30 text-red-200';
  if (status === 'cuti') return 'bg-sky-600/30 text-sky-200';
  if (status === 'dinas_luar') return 'bg-teal-600/30 text-teal-200';
  if (status === 'offday') return 'bg-slate-600/30 text-slate-200';
  if (status === 'holiday_national') return 'bg-cyan-600/30 text-cyan-200';
  if (status === 'offday_scan') return 'bg-amber-600/30 text-amber-200';
  if (status === 'no_roster') return 'bg-purple-600/30 text-purple-200';
  return 'bg-slate-600/30 text-slate-200';
}
</script>
