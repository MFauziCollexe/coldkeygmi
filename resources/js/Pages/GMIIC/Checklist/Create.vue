<template>
  <AppLayout>
    <div class="p-6">
      <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 class="text-2xl font-bold">New Checklist</h2>
          <Link href="/gmiic/checklist" class="text-sm text-indigo-400 hover:underline">&lt; Back to List</Link>
        </div>

        <div class="w-full sm:w-[360px]">
          <SearchableSelect
            v-model="selectedChecklist"
            :options="checklistOptions"
            option-value="id"
            option-label="name"
            placeholder="Pilih Checklist..."
            empty-label="Pilih Checklist"
            input-class="w-full bg-slate-800 text-sm border-slate-700 rounded"
            button-class="border-0 border-l !border-slate-700 rounded-r !bg-slate-800 text-slate-100"
          />
        </div>
      </div>

      <div
        v-if="selectedChecklist && !supportedTemplates.includes(selectedChecklist)"
        class="rounded bg-slate-800 p-4 text-sm text-amber-300"
      >
        Template detail saat ini baru tersedia untuk checklist `Kotak P3K`, `Kebersihan dan Sanitasi (Non-Warehouse Area)`, `APAR / Smoke Detector / Fire Alarm`, `Pengangkutan Sampah PT SIER`, `Kebersihan dan Sanitasi (Warehouse Area)`, `Personal Hygiene Karyawan`, dan `Sarana dan Prasarana`.
      </div>

      <div
        v-else-if="!selectedChecklist"
        class="rounded bg-slate-800 p-8 text-center text-slate-400"
      >
        Pilih checklist terlebih dahulu untuk membuka form.
      </div>

      <KotakP3KTemplate
        v-else-if="entry && isKotakP3K"
        :entry="entry"
        :can-scan-barcode="kotakP3KMonthValidation.canScan"
        :can-approve-entry="canApproveEntry"
        :location-menu-open="locationMenuOpen"
        :location-options="locationOptions"
        :get-location-label="getLocationLabel"
        :months="kotakP3KMonths"
        :active-month="activeKotakP3KMonth"
        :active-month-label="getKotakP3KMonthLabel(activeKotakP3KMonth)"
        :current-barcode="currentKotakP3KBarcode"
        :month-note="kotakP3KMonthNote"
        :is-active-month-approved="isActiveKotakP3KMonthApproved"
        :is-active-month-locked="isActiveKotakP3KMonthLocked"
        :active-month-status-label="kotakP3KActiveMonthStatusLabel"
        :approval-button-label="kotakP3KApprovalButtonLabel"
        @approve="approveChecklist"
        @scan-barcode="scanBarcode"
        @toggle-location-menu="toggleLocationMenu"
        @select-location="selectLocation"
        @set-active-month="setKotakP3KActiveMonth"
        @cycle-month-answer="cycleKotakP3KMonthAnswer"
        @update-month-note="updateKotakP3KMonthNote"
      />

      <FireSafetyTemplate
        v-else-if="entry && isFireSafety"
        :entry="entry"
        :card-options="fireSafetyCardOptions"
        :card-title="fireSafetyCardTitle"
        :location-options="fireSafetyLocationOptions"
        :months="kotakP3KMonths"
        :active-month="activeFireSafetyMonth"
        :active-month-label="getKotakP3KMonthLabel(activeFireSafetyMonth)"
        :month-note="fireSafetyMonthNote"
        :current-barcode="currentFireSafetyBarcode"
        :can-scan-barcode="canScanFireSafety"
        :can-approve-entry="canApproveEntry"
        :is-active-month-approved="isActiveFireSafetyMonthApproved"
        @approve="approveChecklist"
        @scan-barcode="scanFireSafetyBarcode"
        @update-card-type="updateFireSafetyCardType"
        @update-location="updateFireSafetyLocation"
        @set-active-month="setFireSafetyActiveMonth"
        @cycle-month-answer="cycleFireSafetyMonthAnswer"
        @update-month-note="updateFireSafetyMonthNote"
      />

      <NonWarehouseSanitationTemplate
        v-else-if="entry && isSanitation"
        :entry="entry"
        :rows="currentSanitationRows"
        :approved-days="sanitationApprovedDays"
        :current-area-scan="currentSanitationScan"
        :next-pending-day="nextPendingSanitationDay"
        :can-scan-area="canScanSanitationArea"
        :can-approve-entry="canApproveEntry"
        :sanitation-days="sanitationDays"
        :sanitation-area-options="sanitationAreaOptions"
        @approve="approveChecklist"
        @scan-area="scanSanitationArea"
        @toggle-day="toggleSanitationDay"
      />

      <WasteTransportTemplate
        v-else-if="entry && isWasteTransport"
        :entry="entry"
        :rows="wasteTransportRows"
        :period-label="formatMonthYearDisplay(entry.form.period)"
        :approved-days="wasteTransportApprovedDays"
        :can-approve-entry="canApproveEntry"
        @approve="approveChecklist"
        @update-row="updateWasteTransportRow"
        @open-camera="openWasteTransportCamera"
      />

      <WarehouseSanitationTemplate
        v-else-if="entry && isWarehouseSanitation"
        :entry="entry"
        :warehouse-area-options="warehouseAreaOptions"
        :can-approve-entry="canApproveEntry"
        :approval-button-label="warehouseApprovalButtonLabel"
        @approve="approveChecklist"
        @update-frequency="updateWarehouseFrequency"
        @toggle-area="toggleWarehouseArea"
        @update-general-field="updateWarehouseGeneralField"
        @set-area-row-status="setWarehouseAreaRowStatus"
        @set-area-row-note="setWarehouseAreaRowNote"
        @set-ice-control-status="setWarehouseIceControlStatus"
        @set-ice-control-note="setWarehouseIceControlNote"
        @set-cleaning-material-field="setWarehouseCleaningMaterialField"
      />

      <SaranaPrasaranaTemplate
        v-else-if="entry && isSaranaPrasarana"
        :entry="entry"
        :area-options="saranaPrasaranaAreaOptions"
        :current-section="currentSaranaPrasaranaSection"
        :days="saranaPrasaranaDays"
        :approved-days="saranaPrasaranaApprovedDays"
        :next-pending-day="nextPendingSaranaPrasaranaDay"
        :can-approve-entry="canApproveEntry"
        @approve="approveChecklist"
        @update-period="updateSaranaPrasaranaPeriod"
        @update-area="updateSaranaPrasaranaArea"
        @cycle-day="cycleSaranaPrasaranaDay"
      />

      <PersonalHygieneTemplate
        v-else-if="entry && isPersonalHygiene"
        :entry="entry"
        :rows="personalHygieneRows"
        :days="personalHygieneDays"
        :generated-employees="generatedPersonalHygieneEmployees"
        :employees="props.employees"
        :can-approve-entry="canApproveEntry"
        @approve="approveChecklist"
        @update-general-field="updatePersonalHygieneField"
        @toggle-day="togglePersonalHygieneDay"
        @toggle-generated-day="toggleGeneratedPersonalHygieneDay"
        @generate-full-month="generatePersonalHygieneFullMonth"
      />

      <div
        v-if="scannerModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
        @click.self="closeScannerModal"
      >
        <div class="w-full max-w-xl rounded-xl border border-slate-700 bg-slate-900 p-4 shadow-2xl">
          <div class="mb-4 flex items-center justify-between gap-4">
            <div>
              <h3 class="text-lg font-semibold text-white">Scan Barcode</h3>
              <p class="text-sm text-slate-400">Gunakan kamera HP atau laptop untuk membaca barcode lokasi.</p>
            </div>

            <button
              type="button"
              class="rounded bg-slate-700 px-3 py-2 text-sm text-white hover:bg-slate-600"
              @click="closeScannerModal"
            >
              Close
            </button>
          </div>

          <div v-if="scannerError" class="mb-3 rounded border border-rose-700 bg-rose-950/40 px-3 py-2 text-sm text-rose-200">
            {{ scannerError }}
          </div>

          <div v-if="scannerLoading" class="mb-3 text-sm text-slate-400">
            Menyiapkan kamera...
          </div>

          <div id="barcode-scanner-region" class="min-h-[320px] overflow-hidden rounded-lg border border-slate-700 bg-black"></div>
        </div>
      </div>

      <div
        v-if="photoModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
        @click.self="closePhotoModal"
      >
        <div class="w-full max-w-xl rounded-xl border border-slate-700 bg-slate-900 p-4 shadow-2xl">
          <div class="mb-4 flex items-center justify-between gap-4">
            <div>
              <h3 class="text-lg font-semibold text-white">Ambil Foto Petugas Pengangkut</h3>
              <p class="text-sm text-slate-400">Gunakan kamera HP atau laptop, lalu ambil foto langsung.</p>
            </div>

            <button
              type="button"
              class="rounded bg-slate-700 px-3 py-2 text-sm text-white hover:bg-slate-600"
              @click="closePhotoModal"
            >
              Close
            </button>
          </div>

          <div v-if="photoError" class="mb-3 rounded border border-rose-700 bg-rose-950/40 px-3 py-2 text-sm text-rose-200">
            {{ photoError }}
          </div>

          <div v-if="photoLoading" class="mb-3 text-sm text-slate-400">
            Menyiapkan kamera...
          </div>

          <div class="overflow-hidden rounded-lg border border-slate-700 bg-black">
            <video
              ref="photoVideoRef"
              autoplay
              playsinline
              muted
              class="min-h-[320px] w-full bg-black object-cover"
            ></video>
          </div>

          <div class="mt-4 flex justify-end gap-3">
            <button
              type="button"
              class="rounded bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-600"
              @click="closePhotoModal"
            >
              Batal
            </button>
            <button
              type="button"
              :disabled="photoLoading"
              class="rounded bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-500 disabled:cursor-not-allowed disabled:bg-slate-600"
              @click="captureWasteTransportPhoto"
            >
              Ambil Foto
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import FireSafetyTemplate from './Templates/FireSafetyTemplate.vue';
import KotakP3KTemplate from './Templates/KotakP3KTemplate.vue';
import NonWarehouseSanitationTemplate from './Templates/NonWarehouseSanitationTemplate.vue';
import SaranaPrasaranaTemplate from './Templates/SaranaPrasaranaTemplate.vue';
import WasteTransportTemplate from './Templates/WasteTransportTemplate.vue';
import WarehouseSanitationTemplate from './Templates/WarehouseSanitationTemplate.vue';
import PersonalHygieneTemplate from './Templates/PersonalHygieneTemplate.vue';
import {
  buildWarehouseAreaRows,
  checklistOptions,
  createFireSafetyEntry,
  createFireSafetyLocationState,
  createKotakP3KEntry,
  createNonWarehouseSanitationEntry,
  createPersonalHygieneEntry,
  createSaranaPrasaranaEntry,
  createWasteTransportEntry,
  createWarehouseSanitationEntry,
  fireSafetyCardOptions,
  formatDayMonthDisplay,
  formatDateDisplay,
  formatDateTimeDisplay,
  formatMonthYearDisplay,
  formatShortDateDisplay,
  getFireSafetyCardTitle,
  getFireSafetyLocationOptions,
  getFireSafetyRecordKey,
  getDaysInPeriod,
  getKotakP3KMonthLabel,
  getLocationLabel,
  kotakP3KMonths,
  locationOptions,
  personalHygieneRows as personalHygieneRowTemplates,
  rebuildFireSafetyRows,
  rebuildPersonalHygieneRows,
  rebuildAllSanitationRowsByArea,
  rebuildSaranaPrasaranaSections,
  rebuildSanitationRows,
  rebuildWasteTransportRows,
  saranaPrasaranaAreaOptions,
  sanitationAreaOptions,
  toPeriodValue,
  warehouseAreaOptions,
} from './checklistConfig';
import { findChecklistEntry, loadChecklistEntries, upsertChecklistEntry } from './checklistStorage';

const supportedTemplates = ['kotak_p3k', 'non_warehouse_sanitation', 'apar_smoke_detector_fire_alarm', 'pengangkutan_sampah_pt_sier', 'warehouse_sanitation_1', 'personal_hygiene_karyawan', 'sarana_dan_prasarana'];

const props = defineProps({
  selectedTemplate: {
    type: String,
    default: '',
  },
  entryId: {
    type: String,
    default: '',
  },
  holidayDates: {
    type: Array,
    default: () => [],
  },
  leaveDatesByNik: {
    type: Object,
    default: () => ({}),
  },
  employees: {
    type: Array,
    default: () => [],
  },
  checklistAbilities: {
    type: Object,
    default: () => ({}),
  },
});

const page = usePage();
const selectedChecklist = ref(props.selectedTemplate || '');
const entry = ref(createInitialEntry());
const locationMenuOpen = ref(false);
const scannerModalOpen = ref(false);
const scannerLoading = ref(false);
const scannerError = ref('');
const scannerMode = ref('');
const photoModalOpen = ref(false);
const photoLoading = ref(false);
const photoError = ref('');
const photoCaptureDay = ref(null);
const photoVideoRef = ref(null);

let html5QrcodeInstance = null;
let scannerStarting = false;
let scannerFinishing = false;
let photoStream = null;

const currentUser = computed(() => page.props.auth?.user || null);
const checklistAbilities = computed(() => props.checklistAbilities || page.props.checklistAbilities || {});
const canApproveKotakP3KHse = computed(() => Boolean(checklistAbilities.value.kotak_p3k_hse_approve));
const canApproveWarehouseFinal = computed(() => Boolean(checklistAbilities.value.warehouse_final_approve));

const isKotakP3K = computed(() => selectedChecklist.value === 'kotak_p3k');
const isSanitation = computed(() => selectedChecklist.value === 'non_warehouse_sanitation');
const isFireSafety = computed(() => selectedChecklist.value === 'apar_smoke_detector_fire_alarm');
const isWasteTransport = computed(() => selectedChecklist.value === 'pengangkutan_sampah_pt_sier');
const isWarehouseSanitation = computed(() => selectedChecklist.value === 'warehouse_sanitation_1');
const isPersonalHygiene = computed(() => selectedChecklist.value === 'personal_hygiene_karyawan');
const isSaranaPrasarana = computed(() => selectedChecklist.value === 'sarana_dan_prasarana');

const saranaPrasaranaDays = computed(() => {
  if (!isSaranaPrasarana.value || !entry.value) {
    return [];
  }

  return getDaysInPeriod(entry.value.form.period);
});

const saranaPrasaranaApprovedDays = computed(() => {
  if (!isSaranaPrasarana.value || !entry.value) {
    return [];
  }

  const areaId = String(entry.value.form.selected_area || '').trim();
  const approvedDaysByArea = entry.value.form.approved_days_by_area || {};

  return Array.isArray(approvedDaysByArea?.[areaId]) ? approvedDaysByArea[areaId] : [];
});

const currentSaranaPrasaranaSection = computed(() => {
  if (!isSaranaPrasarana.value || !entry.value) {
    return null;
  }

  const areaId = String(entry.value.form.selected_area || '').trim();
  return (entry.value.form.sections || []).find((section) => section.id === areaId) || null;
});

const nextPendingSaranaPrasaranaDay = computed(() => {
  if (!isSaranaPrasarana.value || !entry.value) {
    return null;
  }

  return saranaPrasaranaDays.value.find((day) => !day.isSunday && !saranaPrasaranaApprovedDays.value.includes(day.day)) || null;
});

const activeKotakP3KMonth = computed(() => {
  if (!isKotakP3K.value || !entry.value) {
    return 'jan';
  }

  return entry.value.form.active_month || 'jan';
});

const activeFireSafetyMonth = computed(() => {
  if (!isFireSafety.value || !entry.value) {
    return 'jan';
  }

  return entry.value.form.active_month || 'jan';
});

const fireSafetyCardTitle = computed(() => {
  if (!isFireSafety.value || !entry.value) {
    return 'KARTU PEMELIHARAAN';
  }

  return getFireSafetyCardTitle(entry.value.form.card_type);
});

const fireSafetyLocationOptions = computed(() => {
  if (!isFireSafety.value || !entry.value) {
    return [];
  }

  return getFireSafetyLocationOptions(entry.value.form.card_type);
});

const fireSafetyMonthNote = computed({
  get() {
    if (!isFireSafety.value || !entry.value) {
      return '';
    }

    return entry.value.form.monthly_notes?.[activeFireSafetyMonth.value] || '';
  },
  set(value) {
    if (!isFireSafety.value || !entry.value) {
      return;
    }

    entry.value.form.monthly_notes = {
      ...(entry.value.form.monthly_notes || {}),
      [activeFireSafetyMonth.value]: value,
    };
  },
});

const currentFireSafetyBarcode = computed(() => {
  if (!isFireSafety.value || !entry.value) {
    return '';
  }

  return entry.value.form.monthly_barcodes?.[activeFireSafetyMonth.value] || '';
});

const fireSafetyApprovedMonths = computed(() => {
  if (!isFireSafety.value || !entry.value) {
    return [];
  }

  return Array.isArray(entry.value.form.approved_months) ? entry.value.form.approved_months : [];
});

const isActiveFireSafetyMonthApproved = computed(() => {
  return fireSafetyApprovedMonths.value.includes(activeFireSafetyMonth.value);
});

const fireSafetyMonthValidation = computed(() => {
  if (!isFireSafety.value || !entry.value) {
    return {
      allAnswersFilled: false,
      hasNoAnswer: false,
      hasRequiredNote: false,
    };
  }

  const answers = (entry.value.form.rows || []).map((row) => row.months?.[activeFireSafetyMonth.value] || '');
  const allAnswersFilled = answers.length > 0 && answers.every((answer) => answer === 'yes' || answer === 'no');
  const hasNoAnswer = answers.includes('no');
  const hasRequiredNote = String(fireSafetyMonthNote.value || '').trim() !== '';

  return {
    allAnswersFilled,
    hasNoAnswer,
    hasRequiredNote,
  };
});

const canScanFireSafety = computed(() => {
  if (!isFireSafety.value || !entry.value || isActiveFireSafetyMonthApproved.value) {
    return false;
  }

  return fireSafetyMonthValidation.value.allAnswersFilled
    && (!fireSafetyMonthValidation.value.hasNoAnswer || fireSafetyMonthValidation.value.hasRequiredNote)
    && !String(currentFireSafetyBarcode.value || '').trim();
});

const kotakP3KMonthNote = computed({
  get() {
    if (!isKotakP3K.value || !entry.value) {
      return '';
    }

    return entry.value.form.monthly_notes?.[activeKotakP3KMonth.value] || '';
  },
  set(value) {
    if (!isKotakP3K.value || !entry.value) {
      return;
    }

    entry.value.form.monthly_notes = {
      ...(entry.value.form.monthly_notes || {}),
      [activeKotakP3KMonth.value]: value,
    };
  },
});

const currentKotakP3KBarcode = computed(() => {
  if (!isKotakP3K.value || !entry.value) {
    return '';
  }

  return entry.value.form.monthly_barcodes?.[activeKotakP3KMonth.value] || '';
});

const kotakP3KApprovedMonths = computed(() => {
  if (!isKotakP3K.value || !entry.value) {
    return [];
  }

  return Array.isArray(entry.value.form.approved_months) ? entry.value.form.approved_months : [];
});

const kotakP3KSubmittedMonths = computed(() => {
  if (!isKotakP3K.value || !entry.value) {
    return [];
  }

  return Array.isArray(entry.value.form.submitted_months) ? entry.value.form.submitted_months : [];
});

const isActiveKotakP3KMonthApproved = computed(() => {
  return kotakP3KApprovedMonths.value.includes(activeKotakP3KMonth.value);
});

const isActiveKotakP3KMonthSubmitted = computed(() => {
  return kotakP3KSubmittedMonths.value.includes(activeKotakP3KMonth.value);
});

const isActiveKotakP3KMonthLocked = computed(() => {
  return isActiveKotakP3KMonthSubmitted.value || isActiveKotakP3KMonthApproved.value;
});

const kotakP3KActiveMonthStatusLabel = computed(() => {
  if (isActiveKotakP3KMonthApproved.value) {
    return 'Approved';
  }

  if (isActiveKotakP3KMonthSubmitted.value) {
    return 'Waiting HSE';
  }

  return 'Pending';
});

const kotakP3KApprovalButtonLabel = computed(() => {
  if (!isKotakP3K.value || !entry.value) {
    return 'Approval';
  }

  if (isActiveKotakP3KMonthApproved.value) {
    return 'Approved';
  }

  if (isActiveKotakP3KMonthSubmitted.value) {
    return canApproveKotakP3KHse.value ? 'Approval HSE' : 'Menunggu HSE';
  }

  return 'Approval';
});

const kotakP3KMonthValidation = computed(() => {
  if (!isKotakP3K.value || !entry.value) {
    return {
      allAnswersFilled: false,
      hasNoAnswer: false,
      hasRequiredNote: false,
      canScan: false,
    };
  }

  const answers = entry.value.form.items.map((item) => item.months?.[activeKotakP3KMonth.value] || '');
  const allAnswersFilled = answers.every((answer) => answer === 'yes' || answer === 'no');
  const hasNoAnswer = answers.includes('no');
  const hasRequiredNote = String(kotakP3KMonthNote.value || '').trim() !== '';
  const canScan = !isActiveKotakP3KMonthLocked.value
    && allAnswersFilled
    && (!hasNoAnswer || hasRequiredNote);

  return {
    allAnswersFilled,
    hasNoAnswer,
    hasRequiredNote,
    canScan,
  };
});

const sanitationDays = computed(() => {
  if (!isSanitation.value || !entry.value) {
    return [];
  }

  return getDaysInPeriod(entry.value.form.period);
});

const currentSanitationRows = computed(() => {
  if (!isSanitation.value || !entry.value) {
    return [];
  }

  const areaRows = entry.value.form.rows_by_area?.[entry.value.form.area];
  return Array.isArray(areaRows) ? areaRows : [];
});

const sanitationApprovedDays = computed(() => {
  if (!isSanitation.value || !entry.value) {
    return [];
  }

  return Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days : [];
});

const currentSanitationScan = computed(() => {
  if (!isSanitation.value || !entry.value || !nextPendingSanitationDay.value) {
    return null;
  }

  const dayScans = entry.value.form.area_scans_by_day?.[nextPendingSanitationDay.value.day] || {};
  return dayScans?.[entry.value.form.area] || null;
});

const currentSanitationAreaCompleted = computed(() => {
  if (!isSanitation.value || !entry.value || !nextPendingSanitationDay.value || !currentSanitationRows.value.length) {
    return false;
  }

  return currentSanitationRows.value.every((row) => Boolean(row.days?.[nextPendingSanitationDay.value.day]));
});

const canScanSanitationArea = computed(() => {
  if (!isSanitation.value || !entry.value || !nextPendingSanitationDay.value) {
    return false;
  }

  if (currentSanitationScan.value?.barcode) {
    return false;
  }

  return currentSanitationAreaCompleted.value;
});

const wasteTransportRows = computed(() => {
  if (!isWasteTransport.value || !entry.value) {
    return [];
  }

  return Array.isArray(entry.value.form.rows) ? entry.value.form.rows : [];
});

const wasteTransportApprovedDays = computed(() => {
  if (!isWasteTransport.value || !entry.value) {
    return [];
  }

  return Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days : [];
});

const personalHygieneRows = computed(() => {
  if (!isPersonalHygiene.value || !entry.value) {
    return [];
  }

  return Array.isArray(entry.value.form.rows) ? entry.value.form.rows : [];
});

const generatedPersonalHygieneEmployees = computed(() => {
  if (!isPersonalHygiene.value || !entry.value) {
    return [];
  }

  return Array.isArray(entry.value.form.generated_employees) ? entry.value.form.generated_employees : [];
});

const personalHygieneDays = computed(() => {
  if (!isPersonalHygiene.value || !entry.value) {
    return [];
  }

  const holidayDates = new Set((props.holidayDates || []).map((value) => String(value)));
  const employeeNik = String(entry.value.form.nik || '').trim();
  const leaveDates = new Set(
    Array.isArray(props.leaveDatesByNik?.[employeeNik])
      ? props.leaveDatesByNik[employeeNik].map((value) => String(value))
      : []
  );

  return getDaysInPeriod(entry.value.form.period).map((day) => ({
    ...day,
    isHoliday: holidayDates.has(day.date),
    isLeave: leaveDates.has(day.date),
  }));
});

const nextPendingWasteTransportDay = computed(() => {
  if (!isWasteTransport.value || !entry.value) {
    return null;
  }

  return wasteTransportRows.value.find((row) => {
    if (wasteTransportApprovedDays.value.includes(row.day)) {
      return false;
    }

    return Boolean(row.pickup_time || row.handover_name || row.collector_name || row.collector_photo_name);
  }) || null;
});

const sanitationCompletedDays = computed(() => {
  if (!isSanitation.value || !sanitationDays.value.length || !entry.value) {
    return [];
  }

  return sanitationDays.value.filter((day) => {
    if (day.isSunday) {
      return false;
    }

    return sanitationAreaOptions.every((area) => {
      const areaRows = entry.value.form.rows_by_area?.[area.id] || [];

      if (!areaRows.length) {
        return false;
      }

      return areaRows.every((row) => Boolean(row.days?.[day.day]));
    });
  });
});

const nextPendingSanitationDay = computed(() => {
  if (!isSanitation.value || !entry.value) {
    return null;
  }

  return sanitationDays.value.find((day) => {
    if (day.isSunday || sanitationApprovedDays.value.includes(day.day)) {
      return false;
    }

    return sanitationAreaOptions.some((area) => {
      const areaRows = entry.value.form.rows_by_area?.[area.id] || [];
      return areaRows.some((row) => Boolean(row.days?.[day.day]));
    });
  }) || null;
});

const warehousePreparedApproved = computed(() => {
  if (!isWarehouseSanitation.value || !entry.value) {
    return false;
  }

  return Boolean(entry.value.form.verification?.prepared_date);
});

const warehouseManagerApproved = computed(() => {
  if (!isWarehouseSanitation.value || !entry.value) {
    return false;
  }

  return Boolean(entry.value.form.verification?.verified_date);
});

const warehouseApprovalButtonLabel = computed(() => {
  if (!isWarehouseSanitation.value || !entry.value) {
    return 'Approval';
  }

  if (warehouseManagerApproved.value) {
    return 'Approved';
  }

  if (!warehousePreparedApproved.value) {
    return 'Approval Petugas';
  }

  return canApproveWarehouseFinal.value ? 'Approval Manager / HSE' : 'Menunggu Manager / HSE';
});

const canApproveEntry = computed(() => {
  if (!entry.value) {
    return false;
  }

  if (isKotakP3K.value) {
    if (isActiveKotakP3KMonthApproved.value) {
      return false;
    }

    if (isActiveKotakP3KMonthSubmitted.value) {
      return canApproveKotakP3KHse.value;
    }

    return kotakP3KMonthValidation.value.canScan && String(currentKotakP3KBarcode.value || '').trim() !== '';
  }

  if (isSanitation.value) {
    if (!nextPendingSanitationDay.value) {
      return false;
    }

    const pendingDay = nextPendingSanitationDay.value.day;
    const allAreasScanned = sanitationAreaOptions.every((area) => {
      const dayScans = entry.value.form.area_scans_by_day?.[pendingDay] || {};
      return Boolean(dayScans?.[area.id]?.barcode);
    });

    return allAreasScanned && sanitationCompletedDays.value.some((day) => day.day === pendingDay);
  }

  if (isFireSafety.value) {
    if (isActiveFireSafetyMonthApproved.value) {
      return false;
    }

    return Boolean(
      String(entry.value.form.card_type || '').trim()
      && String(entry.value.form.location || '').trim()
    ) && fireSafetyMonthValidation.value.allAnswersFilled
      && String(currentFireSafetyBarcode.value || '').trim() !== ''
      && (!fireSafetyMonthValidation.value.hasNoAnswer || fireSafetyMonthValidation.value.hasRequiredNote);
  }

  if (isWasteTransport.value) {
    if (!nextPendingWasteTransportDay.value) {
      return false;
    }

    return Boolean(
      nextPendingWasteTransportDay.value.pickup_time
      && nextPendingWasteTransportDay.value.handover_name
      && nextPendingWasteTransportDay.value.collector_name
      && nextPendingWasteTransportDay.value.collector_photo_name
    );
  }

  if (isWarehouseSanitation.value) {
    const hasValidSchedule = entry.value.form.frequency === 'monthly'
      ? Boolean(String(entry.value.form.period || '').trim())
      : Boolean(String(entry.value.form.date || '').trim());

    const generalCompleted = Boolean(
      hasValidSchedule
      && Array.isArray(entry.value.form.selected_areas)
      && entry.value.form.selected_areas.length
      && String(entry.value.form.room_temperature || '').trim()
      && String(entry.value.form.petugas || '').trim()
      && String(entry.value.form.hse || '').trim()
    );

    const areaRowsCompleted = (entry.value.form.area_rows || []).every((row) => (
      row.clean_condition && row.no_ice_pooling && row.no_odor
    ));

    const iceControlCompleted = (entry.value.form.ice_control_rows || []).every((row) => row.status);

    const cleaningMaterialCompleted = (entry.value.form.cleaning_material_rows || []).every((row) => (
      String(row.material_name || '').trim() && row.halal && row.dosage
    ));

    const formCompleted = generalCompleted && areaRowsCompleted && iceControlCompleted && cleaningMaterialCompleted;

    if (!formCompleted || warehouseManagerApproved.value) {
      return false;
    }

    if (!warehousePreparedApproved.value) {
      return true;
    }

    return canApproveWarehouseFinal.value;
  }

  if (isPersonalHygiene.value) {
    if (generatedPersonalHygieneEmployees.value.length > 0) {
      return true;
    }

    const generalCompleted = Boolean(
      String(entry.value.form.year || '').trim()
      && String(entry.value.form.period || '').trim()
      && String(entry.value.form.employee_name || '').trim()
      && String(entry.value.form.gender || '').trim()
      && String(entry.value.form.nik || '').trim()
      && String(entry.value.form.bagian || '').trim()
    );

    const hasAnyCheckedDay = personalHygieneRows.value.some((row) => (
      Object.values(row.days || {}).some(Boolean)
    ));

    return generalCompleted && hasAnyCheckedDay;
  }

  if (isSaranaPrasarana.value) {
    if (!nextPendingSaranaPrasaranaDay.value) {
      return false;
    }

    return Boolean(String(entry.value.form.period || '').trim())
      && Boolean(String(entry.value.form.selected_area || '').trim())
      && (currentSaranaPrasaranaSection.value?.items || []).every((item) => Boolean(item.days?.[nextPendingSaranaPrasaranaDay.value.day]));
  }

  return false;
});

function createEntryByTemplate(templateId) {
  const userName = page.props.auth?.user?.name || 'User Login';

  if (templateId === 'kotak_p3k') {
    return createKotakP3KEntry(userName);
  }

  if (templateId === 'non_warehouse_sanitation') {
    return createNonWarehouseSanitationEntry(userName);
  }

  if (templateId === 'apar_smoke_detector_fire_alarm') {
    return createFireSafetyEntry(userName);
  }

  if (templateId === 'pengangkutan_sampah_pt_sier') {
    return createWasteTransportEntry(userName);
  }

  if (templateId === 'warehouse_sanitation_1') {
    return createWarehouseSanitationEntry(userName);
  }

  if (templateId === 'personal_hygiene_karyawan') {
    return createPersonalHygieneEntry(userName);
  }

  if (templateId === 'sarana_dan_prasarana') {
    return createSaranaPrasaranaEntry(userName);
  }

  return null;
}

function resolvePersonalHygieneBagianByNik(nik) {
  const normalizedNik = String(nik || '').trim();
  if (!normalizedNik) {
    return '';
  }

  const matchedEmployee = (props.employees || []).find((employee) => String(employee?.nik || '').trim() === normalizedNik);
  return matchedEmployee?.bagian || matchedEmployee?.position || '';
}

function persistCurrentFireSafetyState() {
  if (!entry.value || !isFireSafety.value) {
    return;
  }

  const cardType = String(entry.value.form.card_type || '').trim();
  const locationId = String(entry.value.form.location || '').trim();
  if (!cardType || !locationId) {
    return;
  }

  const recordKey = getFireSafetyRecordKey(cardType, locationId);
  entry.value.form.location_records = {
    ...(entry.value.form.location_records || {}),
    [recordKey]: createFireSafetyLocationState(cardType, {
      approved_months: entry.value.form.approved_months || [],
      monthly_notes: entry.value.form.monthly_notes || {},
      monthly_barcodes: entry.value.form.monthly_barcodes || {},
      monthly_check_dates: entry.value.form.monthly_check_dates || {},
      rows: entry.value.form.rows || [],
    }),
  };
}

function loadFireSafetyState(cardType, locationId) {
  if (!entry.value || !isFireSafety.value) {
    return;
  }

  const recordKey = getFireSafetyRecordKey(cardType, locationId);
  const currentState = entry.value.form.location_records?.[recordKey] || createFireSafetyLocationState(cardType);

  entry.value.form.approved_months = [...(currentState.approved_months || [])];
  entry.value.form.monthly_notes = {
    ...(currentState.monthly_notes || {}),
  };
  entry.value.form.monthly_barcodes = {
    ...(currentState.monthly_barcodes || {}),
  };
  entry.value.form.monthly_check_dates = {
    ...(currentState.monthly_check_dates || {}),
  };
  entry.value.form.rows = rebuildFireSafetyRows(cardType, currentState.rows || []);
}

function hydrateFireSafetyEntry(savedEntry) {
  if (savedEntry?.template_id !== 'apar_smoke_detector_fire_alarm') {
    return savedEntry;
  }

  const cardType = String(savedEntry?.form?.card_type || 'fire_alarm').trim() || 'fire_alarm';
  const locationId = String(savedEntry?.form?.location || '').trim()
    || (getFireSafetyLocationOptions(cardType)[0]?.id || '');
  const recordKey = getFireSafetyRecordKey(cardType, locationId);

  const nextEntry = {
    ...savedEntry,
    form: {
      ...savedEntry.form,
      card_type: cardType,
      location: locationId,
      location_records: {
        ...(savedEntry?.form?.location_records || {}),
      },
    },
  };

  if (!nextEntry.form.location_records[recordKey]) {
    nextEntry.form.location_records[recordKey] = createFireSafetyLocationState(cardType, {
      approved_months: savedEntry?.form?.approved_months || [],
      monthly_notes: savedEntry?.form?.monthly_notes || {},
      monthly_barcodes: savedEntry?.form?.monthly_barcodes || {},
      monthly_check_dates: savedEntry?.form?.monthly_check_dates || {},
      rows: savedEntry?.form?.rows || [],
    });
  }

  const loadedState = nextEntry.form.location_records[recordKey];

  nextEntry.form.approved_months = [...(loadedState.approved_months || [])];
  nextEntry.form.monthly_notes = {
    ...(loadedState.monthly_notes || {}),
  };
  nextEntry.form.monthly_barcodes = {
    ...(loadedState.monthly_barcodes || {}),
  };
  nextEntry.form.monthly_check_dates = {
    ...(loadedState.monthly_check_dates || {}),
  };
  nextEntry.form.rows = rebuildFireSafetyRows(cardType, loadedState.rows || []);

  return nextEntry;
}

function hydratePersonalHygieneEntry(savedEntry) {
  if (savedEntry?.template_id !== 'personal_hygiene_karyawan') {
    return savedEntry;
  }

  const nextBagian = String(savedEntry?.form?.bagian || '').trim() || resolvePersonalHygieneBagianByNik(savedEntry?.form?.nik);

  return {
    ...savedEntry,
    form: {
      ...savedEntry.form,
      bagian: nextBagian,
    },
  };
}

function hydrateSaranaPrasaranaEntry(savedEntry) {
  if (savedEntry?.template_id !== 'sarana_dan_prasarana') {
    return savedEntry;
  }

  const period = String(savedEntry?.form?.period || '').trim() || toPeriodValue(new Date());

  return {
    ...savedEntry,
    form: {
      ...savedEntry.form,
      period,
      selected_area: String(savedEntry?.form?.selected_area || '').trim() || savedEntry?.form?.sections?.[0]?.id || '',
      approved_days_by_area: saranaPrasaranaAreaOptions.reduce((result, area) => {
        result[area.id] = Array.isArray(savedEntry?.form?.approved_days_by_area?.[area.id])
          ? savedEntry.form.approved_days_by_area[area.id]
          : (Array.isArray(savedEntry?.form?.approved_days) && area.id === (savedEntry?.form?.selected_area || savedEntry?.form?.sections?.[0]?.id)
            ? savedEntry.form.approved_days
            : []);
        return result;
      }, {}),
      sections: rebuildSaranaPrasaranaSections(period, savedEntry?.form?.sections || []),
    },
  };
}

function createInitialEntry() {
  if (props.entryId) {
    const savedEntry = findChecklistEntry(props.entryId);
    if (savedEntry) {
      const fireSafetyHydratedEntry = hydrateFireSafetyEntry(savedEntry);
      const saranaPrasaranaHydratedEntry = hydrateSaranaPrasaranaEntry(fireSafetyHydratedEntry);
      const hydratedEntry = hydratePersonalHygieneEntry(saranaPrasaranaHydratedEntry);
      selectedChecklist.value = hydratedEntry.template_id;
      return hydratedEntry;
    }
  }

  return createEntryByTemplate(selectedChecklist.value);
}

function refreshEntry() {
  if (props.entryId && entry.value?.id === props.entryId && entry.value?.template_id === selectedChecklist.value) {
    return;
  }

  entry.value = createEntryByTemplate(selectedChecklist.value);
}

function approveChecklist() {
  if (!entry.value || !canApproveEntry.value) {
    return;
  }

  const now = new Date();
  const approverName = currentUser.value?.name || 'User Login';

  if (isKotakP3K.value) {
    if (isActiveKotakP3KMonthSubmitted.value) {
      entry.value.form.approved_months = [
        ...new Set([...(entry.value.form.approved_months || []), activeKotakP3KMonth.value]),
      ];
      entry.value.form.submitted_months = (entry.value.form.submitted_months || []).filter(
        (month) => month !== activeKotakP3KMonth.value
      );
      entry.value.form.monthly_hse_approved_by = {
        ...(entry.value.form.monthly_hse_approved_by || {}),
        [activeKotakP3KMonth.value]: approverName,
      };
      entry.value.form.approved = true;
    } else {
      entry.value.form.submitted_months = [
        ...new Set([...(entry.value.form.submitted_months || []), activeKotakP3KMonth.value]),
      ];
      entry.value.form.approved = false;
    }

    upsertChecklistEntry(entry.value);
    router.visit('/gmiic/checklist');
    return;
  }

  if (isFireSafety.value) {
    persistCurrentFireSafetyState();
    entry.value.form.approved_months = [
      ...new Set([...(entry.value.form.approved_months || []), activeFireSafetyMonth.value]),
    ];
    entry.value.form.monthly_check_dates = {
      ...(entry.value.form.monthly_check_dates || {}),
      [activeFireSafetyMonth.value]: formatDayMonthDisplay(now),
    };
    entry.value.form.approved = true;
    persistCurrentFireSafetyState();
    upsertChecklistEntry(entry.value);
    router.visit('/gmiic/checklist');
    return;
  }

  if (isSanitation.value && nextPendingSanitationDay.value) {
    entry.value.form.approved_days = [
      ...new Set([...(entry.value.form.approved_days || []), nextPendingSanitationDay.value.day]),
    ].sort((a, b) => a - b);
  }

  if (isWasteTransport.value && nextPendingWasteTransportDay.value) {
    entry.value.form.approved_days = [
      ...new Set([...(entry.value.form.approved_days || []), nextPendingWasteTransportDay.value.day]),
    ].sort((a, b) => a - b);
  }

  if (isWarehouseSanitation.value) {
    entry.value.form.verification = {
      ...(entry.value.form.verification || {}),
      ...(warehousePreparedApproved.value
        ? {
            verified_name: approverName,
            verified_signature: buildDigitalSignature(approverName),
            verified_date: formatDateDisplay(now),
          }
        : {
            prepared_name: approverName,
            prepared_signature: buildDigitalSignature(approverName),
            prepared_date: formatDateDisplay(now),
          }),
    };

    entry.value.form.approved = warehousePreparedApproved.value;
    upsertChecklistEntry(entry.value);
    router.visit('/gmiic/checklist');
    return;
  }

  if (isSaranaPrasarana.value && nextPendingSaranaPrasaranaDay.value) {
    const selectedArea = String(entry.value.form.selected_area || '').trim();
    const currentApprovedDays = Array.isArray(entry.value.form.approved_days_by_area?.[selectedArea])
      ? entry.value.form.approved_days_by_area[selectedArea]
      : [];

    entry.value.form.approved_days_by_area = {
      ...(entry.value.form.approved_days_by_area || {}),
      [selectedArea]: [
        ...new Set([...currentApprovedDays, nextPendingSaranaPrasaranaDay.value.day]),
      ].sort((a, b) => a - b),
    };
  }

  entry.value.form.approved = true;
  upsertChecklistEntry(entry.value);
  router.visit('/gmiic/checklist');
}

async function scanBarcode() {
  if (!entry.value || !isKotakP3K.value || !kotakP3KMonthValidation.value.canScan) {
    return;
  }

  scannerMode.value = 'kotak_p3k';
  scannerError.value = '';
  scannerLoading.value = true;
  scannerModalOpen.value = true;

  await nextTick();
  await startBarcodeScanner();
}

async function scanSanitationArea() {
  if (!entry.value || !isSanitation.value || !nextPendingSanitationDay.value) {
    return;
  }

  scannerMode.value = 'sanitation_area';
  scannerError.value = '';
  scannerLoading.value = true;
  scannerModalOpen.value = true;

  await nextTick();
  await startBarcodeScanner();
}

async function scanFireSafetyBarcode() {
  if (!entry.value || !isFireSafety.value || !canScanFireSafety.value) {
    return;
  }

  scannerMode.value = 'fire_safety';
  scannerError.value = '';
  scannerLoading.value = true;
  scannerModalOpen.value = true;

  await nextTick();
  await startBarcodeScanner();
}

function toggleLocationMenu() {
  locationMenuOpen.value = !locationMenuOpen.value;
}

function selectLocation(locationId) {
  if (entry.value && isKotakP3K.value) {
    entry.value.form.location = locationId;
  }
  locationMenuOpen.value = false;
}

function setKotakP3KActiveMonth(monthKey) {
  if (!entry.value || !isKotakP3K.value) {
    return;
  }

  entry.value.form.active_month = monthKey;
}

function setFireSafetyActiveMonth(monthKey) {
  if (!entry.value || !isFireSafety.value) {
    return;
  }

  entry.value.form.active_month = monthKey;
}

function cycleKotakP3KMonthAnswer(item, monthKey) {
  if (!item?.months || isActiveKotakP3KMonthApproved.value || monthKey !== activeKotakP3KMonth.value) {
    return;
  }

  const currentValue = item.months?.[monthKey] || '';
  const nextValue = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : '';

  item.months = {
    ...item.months,
    [monthKey]: nextValue,
  };
}

function updateKotakP3KMonthNote(value) {
  kotakP3KMonthNote.value = value;
}

function updateFireSafetyCardType(cardType) {
  if (!entry.value || !isFireSafety.value) {
    return;
  }

  persistCurrentFireSafetyState();
  const nextLocation = getFireSafetyLocationOptions(cardType)[0]?.id || '';

  entry.value.form.card_type = cardType;
  entry.value.form.location = nextLocation;
  loadFireSafetyState(cardType, nextLocation);
}

function updateFireSafetyLocation(locationId) {
  if (!entry.value || !isFireSafety.value) {
    return;
  }

  persistCurrentFireSafetyState();
  entry.value.form.location = locationId;
  loadFireSafetyState(entry.value.form.card_type, locationId);
}

function cycleFireSafetyMonthAnswer(row, monthKey) {
  if (!row?.months || !isFireSafety.value || isActiveFireSafetyMonthApproved.value || monthKey !== activeFireSafetyMonth.value) {
    return;
  }

  const currentValue = row.months?.[monthKey] || '';
  const nextValue = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : '';

  row.months = {
    ...row.months,
    [monthKey]: nextValue,
  };
}

function updateFireSafetyMonthNote(value) {
  fireSafetyMonthNote.value = value;
}

function updateWasteTransportRow(day, field, value) {
  if (!entry.value || !isWasteTransport.value) {
    return;
  }

  entry.value.form.rows = wasteTransportRows.value.map((row) => (
    row.day === day ? { ...row, [field]: value } : row
  ));
}

function updatePersonalHygieneField(field, value) {
  if (!entry.value || !isPersonalHygiene.value) {
    return;
  }

  if (field === 'period') {
    entry.value.form.period = value;
    entry.value.form.year = String(value || '').split('-')[0] || entry.value.form.year;
    return;
  }

  entry.value.form[field] = value;
}

function updateSaranaPrasaranaPeriod(value) {
  if (!entry.value || !isSaranaPrasarana.value) {
    return;
  }

  entry.value.form.period = value;
  entry.value.form.approved_days_by_area = saranaPrasaranaAreaOptions.reduce((result, area) => {
    result[area.id] = [];
    return result;
  }, {});
  entry.value.form.sections = rebuildSaranaPrasaranaSections(value, []);
}

function updateSaranaPrasaranaArea(value) {
  if (!entry.value || !isSaranaPrasarana.value) {
    return;
  }

  entry.value.form.selected_area = value;
}

function cycleSaranaPrasaranaDay(sectionId, itemId, day) {
  if (!entry.value || !isSaranaPrasarana.value || saranaPrasaranaApprovedDays.value.includes(day)) {
    return;
  }

  entry.value.form.sections = (entry.value.form.sections || []).map((section) => {
    if (section.id !== sectionId) {
      return section;
    }

    return {
      ...section,
      items: (section.items || []).map((item) => {
        if (item.id !== itemId) {
          return item;
        }

        const currentValue = item.days?.[day] || '';
        const nextValue = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : '';

        return {
          ...item,
          days: {
            ...(item.days || {}),
            [day]: nextValue,
          },
        };
      }),
    };
  });
}

function createGeneratedPersonalHygieneEmployees(periodValue, existingEmployees = []) {
  const days = getDaysInPeriod(periodValue);
  const holidayDates = new Set((props.holidayDates || []).map((value) => String(value)));
  const sourceEmployees = existingEmployees.length
    ? existingEmployees.map((employee) => ({
        id: employee.employee_id,
        nik: employee.nik,
        name: employee.name,
        gender: employee.gender,
        position: employee.position,
      }))
    : (props.employees || []);

  return sourceEmployees.map((employee, employeeIndex) => {
    const employeeNik = String(employee?.nik || '').trim();
    const leaveDates = new Set(
      Array.isArray(props.leaveDatesByNik?.[employeeNik])
        ? props.leaveDatesByNik[employeeNik].map((value) => String(value))
        : []
    );

    const leaveDays = days
      .filter((day) => leaveDates.has(day.date))
      .map((day) => day.day);

    const existingEmployee = existingEmployees.find((item) => String(item.employee_id) === String(employee?.id));

    return {
      employee_id: employee?.id ?? `generated-${employeeIndex + 1}`,
      nik: employeeNik,
      name: employee?.name || '-',
      gender: employee?.gender || '-',
      bagian: employee?.bagian || '-',
      position: employee?.position || '-',
      leave_days: leaveDays,
      rows: personalHygieneRowTemplates.map((rowTemplate, rowIndex) => {
        const existingRow = existingEmployee?.rows?.find((row) => row.id === rowTemplate.id);
        const nextDays = days.reduce((result, day) => {
          const isRedDay = day.isSunday || holidayDates.has(day.date) || leaveDates.has(day.date);
          const defaultValue = ['plester_perban_in', 'plester_perban_out'].includes(rowTemplate.id) ? 'no' : 'yes';
          result[day.day] = isRedDay
            ? ''
            : (existingRow?.days?.[day.day] || defaultValue);
          return result;
        }, {});

        return {
          no: rowIndex + 1,
          id: rowTemplate.id,
          name: rowTemplate.name,
          days: nextDays,
        };
      }),
    };
  });
}

function normalizePersonalHygieneGender(value) {
  const normalizedValue = String(value || '').trim().toLowerCase();

  if (['male', 'laki-laki', 'laki laki', 'pria'].includes(normalizedValue)) {
    return 'male';
  }

  if (['female', 'perempuan', 'wanita'].includes(normalizedValue)) {
    return 'female';
  }

  return '';
}

function createGeneratedPersonalHygieneRows(periodValue, employeeNik, existingRows = []) {
  const days = getDaysInPeriod(periodValue);
  const holidayDates = new Set((props.holidayDates || []).map((value) => String(value)));
  const normalizedNik = String(employeeNik || '').trim();
  const leaveDates = new Set(
    Array.isArray(props.leaveDatesByNik?.[normalizedNik])
      ? props.leaveDatesByNik[normalizedNik].map((value) => String(value))
      : []
  );

  return personalHygieneRowTemplates.map((rowTemplate, rowIndex) => {
    const existingRow = existingRows.find((row) => row.id === rowTemplate.id);
    const nextDays = days.reduce((result, day) => {
      const isRedDay = day.isSunday || holidayDates.has(day.date) || leaveDates.has(day.date);
      const defaultValue = ['plester_perban_in', 'plester_perban_out'].includes(rowTemplate.id) ? 'no' : 'yes';
      result[day.day] = isRedDay
        ? ''
        : (existingRow?.days?.[day.day] || defaultValue);
      return result;
    }, {});

    return {
      no: rowIndex + 1,
      id: rowTemplate.id,
      name: rowTemplate.name,
      days: nextDays,
    };
  });
}

function generatePersonalHygieneFullMonth() {
  if (!entry.value || !isPersonalHygiene.value) {
    return;
  }

  const targetPeriod = entry.value.form.period || toPeriodValue(new Date());
  const targetYear = String(targetPeriod || '').split('-')[0] || String(new Date().getFullYear());
  const existingEntries = loadChecklistEntries().filter((savedEntry) => {
    return savedEntry?.template_id === 'personal_hygiene_karyawan'
      && String(savedEntry?.form?.period || '') === targetPeriod;
  });
  let createdCount = 0;
  let skippedCount = 0;

  (props.employees || []).forEach((employee, index) => {
    const employeeNik = String(employee?.nik || '').trim();
    const existingEntry = existingEntries.find((savedEntry) => {
      return String(savedEntry?.form?.nik || '').trim() === employeeNik;
    });

    if (existingEntry) {
      skippedCount += 1;
      return;
    }

    const nextEntry = createPersonalHygieneEntry(currentUser.value?.name || 'User Login');
    nextEntry.id = `personal_hygiene_karyawan-${Date.now()}-${index + 1}`;
    nextEntry.created_at = formatDateTimeDisplay(new Date());
    nextEntry.form.year = targetYear;
    nextEntry.form.period = targetPeriod;
    nextEntry.form.employee_name = employee?.name || '';
    nextEntry.form.gender = normalizePersonalHygieneGender(employee?.gender);
    nextEntry.form.nik = employeeNik;
    nextEntry.form.bagian = employee?.bagian || employee?.position || '';
    nextEntry.form.approved = true;
    nextEntry.form.generated_at = formatDateTimeDisplay(new Date());
    nextEntry.form.rows = createGeneratedPersonalHygieneRows(
      targetPeriod,
      employeeNik,
      []
    );
    nextEntry.form.generated_employees = [];

    upsertChecklistEntry(nextEntry);
    createdCount += 1;
  });

  if (typeof window !== 'undefined') {
    if (createdCount === 0 && skippedCount > 0) {
      window.alert(`Semua checklist personal hygiene periode ${targetPeriod} sudah tersedia. Tidak ada data baru yang digenerate.`);
      return;
    }

    if (skippedCount > 0) {
      window.alert(`${createdCount} checklist berhasil digenerate. ${skippedCount} checklist dilewati karena sudah ada di periode yang sama.`);
    }
  }

  router.visit('/gmiic/checklist');
}

function togglePersonalHygieneDay(row, day) {
  if (!row?.days || !isPersonalHygiene.value) {
    return;
  }

  const currentValue = row.days[day] || '';
  row.days[day] = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : '';
}

function toggleGeneratedPersonalHygieneDay(employeeId, rowId, day) {
  if (!entry.value || !isPersonalHygiene.value) {
    return;
  }

  entry.value.form.generated_employees = generatedPersonalHygieneEmployees.value.map((employee) => {
    if (String(employee.employee_id) !== String(employeeId)) {
      return employee;
    }

    return {
      ...employee,
      rows: (employee.rows || []).map((row) => {
        if (row.id !== rowId) {
          return row;
        }

        const currentValue = row.days?.[day] || '';
        const nextValue = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : '';

        return {
          ...row,
          days: {
            ...(row.days || {}),
            [day]: nextValue,
          },
        };
      }),
    };
  });
}

function toggleWarehouseArea(areaId) {
  if (!entry.value || !isWarehouseSanitation.value) {
    return;
  }

  const selectedAreas = Array.isArray(entry.value.form.selected_areas) ? entry.value.form.selected_areas : [];
  const exists = selectedAreas.includes(areaId);

  entry.value.form.selected_areas = exists
    ? selectedAreas.filter((item) => item !== areaId)
    : [...selectedAreas, areaId];
}

function updateWarehouseGeneralField(field, value) {
  if (!entry.value || !isWarehouseSanitation.value) {
    return;
  }

  entry.value.form[field] = value;
}

function updateWarehouseFrequency(value) {
  if (!entry.value || !isWarehouseSanitation.value) {
    return;
  }

  entry.value.form.frequency = value;
  entry.value.form.area_rows = buildWarehouseAreaRows(
    value,
    entry.value.form.area_rows || []
  );

  if (value === 'monthly' && !String(entry.value.form.period || '').trim()) {
    entry.value.form.period = toPeriodValue(new Date());
  }

  if (value === 'daily' && !String(entry.value.form.date || '').trim()) {
    entry.value.form.date = formatDateDisplay(new Date());
  }
}

function buildDigitalSignature(name) {
  const initials = String(name || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 3)
    .map((part) => part.charAt(0).toUpperCase())
    .join('');

  return initials || 'DS';
}

function setWarehouseAreaRowStatus(rowId, field, value) {
  if (!entry.value || !isWarehouseSanitation.value) {
    return;
  }

  entry.value.form.area_rows = (entry.value.form.area_rows || []).map((row) => (
    row.id === rowId ? { ...row, [field]: value } : row
  ));
}

function setWarehouseAreaRowNote(rowId, value) {
  setWarehouseAreaRowStatus(rowId, 'note', value);
}

function setWarehouseIceControlStatus(rowId, value) {
  if (!entry.value || !isWarehouseSanitation.value) {
    return;
  }

  entry.value.form.ice_control_rows = (entry.value.form.ice_control_rows || []).map((row) => (
    row.id === rowId ? { ...row, status: value } : row
  ));
}

function setWarehouseIceControlNote(rowId, value) {
  if (!entry.value || !isWarehouseSanitation.value) {
    return;
  }

  entry.value.form.ice_control_rows = (entry.value.form.ice_control_rows || []).map((row) => (
    row.id === rowId ? { ...row, note: value } : row
  ));
}

function setWarehouseCleaningMaterialField(rowId, field, value) {
  if (!entry.value || !isWarehouseSanitation.value) {
    return;
  }

  entry.value.form.cleaning_material_rows = (entry.value.form.cleaning_material_rows || []).map((row) => (
    row.id === rowId ? { ...row, [field]: value } : row
  ));
}

async function openWasteTransportCamera(day) {
  if (!entry.value || !isWasteTransport.value) {
    return;
  }

  photoCaptureDay.value = day;
  photoError.value = '';
  photoLoading.value = true;
  photoModalOpen.value = true;

  await nextTick();
  await startPhotoCamera();
}

function toggleSanitationDay(row, day) {
  if (!row?.days || sanitationApprovedDays.value.includes(day)) {
    return;
  }

  row.days[day] = !row.days[day];
}

function handleOutsideLocationMenu(event) {
  if (!event.target.closest('[data-location-menu-root]')) {
    locationMenuOpen.value = false;
  }
}

function readFileAsDataUrl(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();

    reader.onload = () => resolve(String(reader.result || ''));
    reader.onerror = () => reject(new Error('Foto gagal dibaca.'));

    reader.readAsDataURL(file);
  });
}

async function startPhotoCamera() {
  try {
    await stopPhotoCamera();

    if (!navigator.mediaDevices?.getUserMedia) {
      throw new Error('Browser ini tidak mendukung akses kamera.');
    }

    photoStream = await navigator.mediaDevices.getUserMedia({
      audio: false,
      video: {
        facingMode: { ideal: 'environment' },
      },
    });

    if (photoVideoRef.value) {
      photoVideoRef.value.srcObject = photoStream;
      await photoVideoRef.value.play();
    }

    photoError.value = '';
  } catch (error) {
    photoError.value = normalizeScannerError(error).replace('Scanner barcode', 'Kamera foto');
    await stopPhotoCamera();
  } finally {
    photoLoading.value = false;
  }
}

async function captureWasteTransportPhoto() {
  if (!entry.value || !isWasteTransport.value || !photoVideoRef.value || !photoCaptureDay.value) {
    return;
  }

  const video = photoVideoRef.value;
  const width = video.videoWidth || 1280;
  const height = video.videoHeight || 720;
  const canvas = document.createElement('canvas');

  canvas.width = width;
  canvas.height = height;

  const context = canvas.getContext('2d');
  if (!context) {
    photoError.value = 'Foto gagal diproses.';
    return;
  }

  context.drawImage(video, 0, 0, width, height);

  const preview = canvas.toDataURL('image/jpeg', 0.9);
  const fileName = `foto-pengangkut-hari-${photoCaptureDay.value}.jpg`;

  entry.value.form.rows = wasteTransportRows.value.map((row) => (
    row.day === photoCaptureDay.value
      ? {
          ...row,
          collector_photo_name: fileName,
          collector_photo_preview: preview,
        }
      : row
  ));

  await closePhotoModal();
}

async function startBarcodeScanner() {
  if (scannerStarting) {
    return;
  }

  scannerStarting = true;

  try {
    await stopBarcodeScanner();

    const scannerModule = await import('html5-qrcode');
    const { Html5Qrcode } = scannerModule;

    html5QrcodeInstance = new Html5Qrcode('barcode-scanner-region');

    const cameras = await Html5Qrcode.getCameras();
    if (!cameras.length) {
      throw new Error('Kamera tidak ditemukan pada perangkat ini.');
    }

    const preferredCamera = choosePreferredCamera(cameras);

    await html5QrcodeInstance.start(
      preferredCamera.id,
      {
        fps: 10,
        qrbox: { width: 280, height: 140 },
        aspectRatio: 1.777778,
      },
      async (decodedText) => {
        if (scannerFinishing) {
          return;
        }

        scannerFinishing = true;

        if (entry.value) {
          if (scannerMode.value === 'kotak_p3k') {
            entry.value.form.barcode = decodedText;
            entry.value.form.monthly_barcodes = {
              ...(entry.value.form.monthly_barcodes || {}),
              [activeKotakP3KMonth.value]: decodedText,
            };
            entry.value.form.monthly_check_dates = {
              ...(entry.value.form.monthly_check_dates || {}),
              [activeKotakP3KMonth.value]: formatShortDateDisplay(new Date()),
            };
          }

          if (scannerMode.value === 'sanitation_area' && nextPendingSanitationDay.value) {
            const day = nextPendingSanitationDay.value.day;
            const dayScans = entry.value.form.area_scans_by_day?.[day] || {};

            entry.value.form.area_scans_by_day = {
              ...(entry.value.form.area_scans_by_day || {}),
              [day]: {
                ...dayScans,
                [entry.value.form.area]: {
                  barcode: decodedText,
                  scanned_at: formatDateTimeDisplay(new Date()),
                },
              },
            };
          }

          if (scannerMode.value === 'fire_safety') {
            entry.value.form.monthly_barcodes = {
              ...(entry.value.form.monthly_barcodes || {}),
              [activeFireSafetyMonth.value]: decodedText,
            };
            persistCurrentFireSafetyState();
          }
        }

        await closeScannerModal();
        scannerFinishing = false;
      },
      () => {}
    );

    scannerError.value = '';
  } catch (error) {
    scannerError.value = normalizeScannerError(error);
    await stopBarcodeScanner();
  } finally {
    scannerLoading.value = false;
    scannerStarting = false;
  }
}

function choosePreferredCamera(cameras) {
  const scored = [...cameras].sort((a, b) => scoreCameraLabel(b.label) - scoreCameraLabel(a.label));
  return scored[0];
}

function scoreCameraLabel(label) {
  const text = String(label || '').toLowerCase();
  let score = 0;

  if (text.includes('back') || text.includes('rear') || text.includes('environment')) score += 20;
  if (text.includes('front') || text.includes('user')) score -= 5;

  return score;
}

function normalizeScannerError(error) {
  const message = String(error?.message || error || '');
  const lowered = message.toLowerCase();

  if (lowered.includes('permission')) {
    return 'Izin kamera ditolak. Izinkan akses kamera lalu coba lagi.';
  }

  if (lowered.includes('secure context') || lowered.includes('https')) {
    return 'Kamera membutuhkan koneksi aman (HTTPS) atau localhost.';
  }

  return message || 'Scanner barcode gagal dijalankan.';
}

async function closeScannerModal() {
  scannerModalOpen.value = false;
  scannerLoading.value = false;
  scannerMode.value = '';
  await stopBarcodeScanner();
}

async function closePhotoModal() {
  photoModalOpen.value = false;
  photoLoading.value = false;
  photoError.value = '';
  photoCaptureDay.value = null;
  await stopPhotoCamera();
}

async function stopBarcodeScanner() {
  if (!html5QrcodeInstance) {
    return;
  }

  try {
    if (html5QrcodeInstance.isScanning) {
      await html5QrcodeInstance.stop();
    }
  } catch (error) {
  }

  try {
    await html5QrcodeInstance.clear();
  } catch (error) {
  }

  html5QrcodeInstance = null;
}

async function stopPhotoCamera() {
  if (photoVideoRef.value?.srcObject) {
    photoVideoRef.value.srcObject = null;
  }

  if (photoStream) {
    photoStream.getTracks().forEach((track) => track.stop());
  }

  photoStream = null;
}

function rebuildSanitationEntryRows() {
  if (!entry.value || !isSanitation.value) {
    return;
  }

  entry.value.form.rows_by_area = rebuildAllSanitationRowsByArea(
    entry.value.form.period,
    entry.value.form.rows_by_area || {}
  );

  if (!Array.isArray(entry.value.form.rows_by_area?.[entry.value.form.area])) {
    entry.value.form.rows_by_area = {
      ...entry.value.form.rows_by_area,
      [entry.value.form.area]: rebuildSanitationRows(
        entry.value.form.area,
        entry.value.form.period,
        []
      ),
    };
  }
}

function rebuildWasteTransportEntryRows() {
  if (!entry.value || !isWasteTransport.value) {
    return;
  }

  entry.value.form.rows = rebuildWasteTransportRows(
    entry.value.form.period,
    entry.value.form.rows || []
  );
}

function rebuildPersonalHygieneEntryRows() {
  if (!entry.value || !isPersonalHygiene.value) {
    return;
  }

  entry.value.form.rows = rebuildPersonalHygieneRows(
    entry.value.form.period,
    entry.value.form.rows || []
  );

  if (generatedPersonalHygieneEmployees.value.length > 0) {
    entry.value.form.generated_employees = createGeneratedPersonalHygieneEmployees(
      entry.value.form.period,
      generatedPersonalHygieneEmployees.value
    );
  }
}

function syncWarehouseAreaRows() {
  if (!entry.value || !isWarehouseSanitation.value) {
    return;
  }

  entry.value.form.area_rows = buildWarehouseAreaRows(
    entry.value.form.frequency || 'daily',
    entry.value.form.area_rows || []
  );
}

onMounted(() => {
  document.addEventListener('click', handleOutsideLocationMenu);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideLocationMenu);
  stopBarcodeScanner();
  stopPhotoCamera();
});

watch(selectedChecklist, () => {
  locationMenuOpen.value = false;
  refreshEntry();
});

watch(
  () => [entry.value?.form?.area, entry.value?.form?.period, selectedChecklist.value],
  () => {
    rebuildSanitationEntryRows();
    rebuildWasteTransportEntryRows();
    rebuildPersonalHygieneEntryRows();
    syncWarehouseAreaRows();
  },
  { immediate: true }
);
</script>
