<template>
  <AppLayout>
    <div class="p-6">
      <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 class="text-2xl font-bold">New Checklist</h2>
          <Link href="/gmiic/checklist" class="text-sm text-indigo-400 hover:underline">&lt; Back to List</Link>
          <div v-if="selectedChecklist" class="mt-1 text-xs" :class="saveStateClass">
            {{ saveStateLabel }}
          </div>
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
        Template detail saat ini baru tersedia untuk checklist `Kotak P3K`, `Kebersihan dan Sanitasi (Non-Warehouse Area)`, `APAR / Smoke Detector / Fire Alarm`, `Pengangkutan Sampah PT SIER`, `Kebersihan dan Sanitasi (Warehouse Area)`, `Personal Hygiene Karyawan`, `Sarana dan Prasarana`, `Patroli Security`, `Site Visit HSE`, dan `Site Visit Maintenance`.
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
        :note="sanitationNote"
        :note-label="sanitationNoteLabel"
        :can-edit-note="canEditSanitationNote"
        :sanitation-days="sanitationDays"
        :sanitation-area-options="sanitationAreaOptions"
        @approve="approveChecklist"
        @scan-area="scanSanitationArea"
        @toggle-day="toggleSanitationDay"
        @update-note="updateSanitationNote"
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
        :current-barcode="currentWarehouseBarcode"
        :scan-date="currentWarehouseScanDate"
        :can-scan-barcode="canScanWarehouseBarcode"
        :approval-button-label="warehouseApprovalButtonLabel"
        @approve="approveChecklist"
        @scan-barcode="scanWarehouseBarcode"
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
        :current-area-scan="currentSaranaPrasaranaScan"
        :next-pending-day="nextPendingSaranaPrasaranaDay"
        :can-scan-area="canScanSaranaPrasaranaArea"
        :can-approve-entry="canApproveEntry"
        @approve="approveChecklist"
        @scan-area="scanSaranaPrasaranaArea"
        @update-period="updateSaranaPrasaranaPeriod"
        @update-area="updateSaranaPrasaranaArea"
        @cycle-day="cycleSaranaPrasaranaDay"
      />

      <PatroliSecurityTemplate
        v-else-if="entry && isPatroliSecurity"
        :entry="entry"
        :area-options="patroliSecurityAreaOptions"
        :current-section="currentPatroliSecuritySection"
        :approved-areas="patroliSecurityApprovedAreas"
        :is-area-approved="patroliSecurityApprovedAreas.includes(entry.form.selected_area)"
        :note="patroliSecurityNote"
        :note-label="patroliSecurityNoteLabel"
        :current-barcode="currentPatroliSecurityBarcode"
        :can-scan-barcode="canScanPatroliSecurity"
        :can-approve-entry="canApproveEntry"
        :current-photos="currentPatroliSecurityPhotos"
        :photo-uploading="patroliSecurityPhotoUploading"
        :photo-error="patroliSecurityPhotoError"
        @approve="approveChecklist"
        @scan-barcode="scanPatroliSecurityBarcode"
        @update-date="updatePatroliSecurityDate"
        @update-area="updatePatroliSecurityArea"
        @cycle-row-status="cyclePatroliSecurityRowStatus"
        @update-note="updatePatroliSecurityNote"
        @open-camera="openPatroliSecurityCamera"
        @remove-photo="removePatroliSecurityPhoto"
      />

      <SiteVisitHseTemplate
        v-else-if="entry && isSiteVisitHse"
        :entry="entry"
        :area-options="siteVisitHseAreaOptions"
        :current-section="currentSiteVisitHseSection"
        :approved-areas="siteVisitHseApprovedAreas"
        :is-area-approved="siteVisitHseApprovedAreas.includes(entry.form.selected_area)"
        :note="siteVisitHseNote"
        :note-label="siteVisitHseNoteLabel"
        :current-barcode="currentSiteVisitHseBarcode"
        :can-scan-barcode="canScanSiteVisitHse"
        :can-approve-entry="canApproveEntry"
        @approve="approveChecklist"
        @scan-barcode="scanSiteVisitHseBarcode"
        @update-date="updateSiteVisitHseDate"
        @update-area="updateSiteVisitHseArea"
        @cycle-row-status="cycleSiteVisitHseRowStatus"
        @update-note="updateSiteVisitHseNote"
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

      <SiteVisitMaintenanceTemplate
        v-else-if="entry && isSiteVisitMaintenance"
        :entry="entry"
        :type-options="maintenanceVisitTypeOptions"
        :current-type-meta="maintenanceTypeMeta"
        :daily-area-options="maintenanceDailyAreaOptions"
        :sections="maintenanceSections"
        :rows="maintenanceRows"
        :note="maintenanceNote"
        :note-label="maintenanceNoteLabel"
        :current-barcode="currentMaintenanceBarcode"
        :can-scan-barcode="canScanMaintenance"
        :can-approve-entry="canApproveEntry"
        @approve="approveChecklist"
        @scan-barcode="scanMaintenanceBarcode"
        @update-type="updateMaintenanceVisitType"
        @update-date="updateMaintenanceVisitDate"
        @update-period="updateMaintenanceVisitPeriod"
        @update-area="updateMaintenanceVisitArea"
        @cycle-row-status="cycleMaintenanceRowStatus"
        @update-note="updateMaintenanceNote"
      />

      <div
        v-if="scannerModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
        @click.self="closeScannerModal"
      >
        <div class="w-full max-w-xl rounded-xl border border-slate-700 bg-slate-900 p-4 shadow-2xl">
          <div class="mb-4 flex items-center justify-between gap-4">
            <div>
              <h3 class="text-lg font-semibold text-white">Scan QRCode</h3>
              <p class="text-sm text-slate-400">Gunakan kamera HP atau laptop untuk membaca QRCode lokasi.</p>
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
              <h3 class="text-lg font-semibold text-white">{{ photoModalTitle }}</h3>
              <p class="text-sm text-slate-400">{{ photoModalDescription }}</p>
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
              @click="capturePhoto"
            >
              {{ photoCaptureButtonLabel }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import axios from 'axios';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import FireSafetyTemplate from './Templates/FireSafetyTemplate.vue';
import KotakP3KTemplate from './Templates/KotakP3KTemplate.vue';
import NonWarehouseSanitationTemplate from './Templates/NonWarehouseSanitationTemplate.vue';
import PatroliSecurityTemplate from './Templates/PatroliSecurityTemplate.vue';
import SaranaPrasaranaTemplate from './Templates/SaranaPrasaranaTemplate.vue';
import SiteVisitHseTemplate from './Templates/SiteVisitHseTemplate.vue';
import SiteVisitMaintenanceTemplate from './Templates/SiteVisitMaintenanceTemplate.vue';
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
  createPatroliSecurityEntry,
  createPersonalHygieneEntry,
  createSaranaPrasaranaEntry,
  createSiteVisitHseEntry,
  createSiteVisitMaintenanceEntry,
  createWasteTransportEntry,
  createWarehouseSanitationEntry,
  fireSafetyCardOptions,
  formatDayMonthDisplay,
  formatDateInputDisplay,
  formatDateDisplay,
  formatDateTimeDisplay,
  formatMonthYearDisplay,
  formatShortDateDisplay,
  formatWeekDisplay,
  getMaintenanceDailyAreaLabel,
  getFireSafetyCardTitle,
  getFireSafetyLocationLabel,
  getFireSafetyLocationOptions,
  getFireSafetyRecordKey,
  getDaysInPeriod,
  getKotakP3KMonthLabel,
  getLocationLabel,
  getLocationBarcodeAliases,
  getPatroliSecurityBarcodeAliases,
  getPatroliSecurityAreaLabel,
  getSanitationAreaLabel,
  getSanitationAreaBarcodeAliases,
  kotakP3KMonths,
  locationOptions,
  maintenanceDailyAreaOptions,
  maintenanceVisitTypeOptions,
  patroliSecurityAreaOptions,
  personalHygieneRows as personalHygieneRowTemplates,
  rebuildMaintenanceDailySections,
  rebuildMaintenanceWeeklyRows,
  rebuildPatroliSecuritySections,
  rebuildFireSafetyRows,
  rebuildPersonalHygieneRows,
  rebuildAllSanitationRowsByArea,
  rebuildSaranaPrasaranaSections,
  rebuildSiteVisitHseSections,
  rebuildSanitationRows,
  rebuildWasteTransportRows,
  getMaintenanceVisitTypeMeta,
  getSaranaPrasaranaAreaLabel,
  getSiteVisitHseAreaLabel,
  saranaPrasaranaAreaOptions,
  siteVisitHseAreaOptions,
  sanitationAreaOptions,
  toDateInputValue,
  toPeriodValue,
  toWeekInputValue,
  warehouseAreaOptions,
} from './checklistConfig';
const supportedTemplates = ['kotak_p3k', 'non_warehouse_sanitation', 'apar_smoke_detector_fire_alarm', 'pengangkutan_sampah_pt_sier', 'warehouse_sanitation_1', 'personal_hygiene_karyawan', 'sarana_dan_prasarana', 'patroli_security', 'site_visit_hse', 'site_visit_maintenance'];

const props = defineProps({
  selectedTemplate: {
    type: String,
    default: '',
  },
  entryId: {
    type: String,
    default: '',
  },
  savedEntry: {
    type: Object,
    default: null,
  },
  existingEntries: {
    type: Array,
    default: () => [],
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
const photoCaptureMode = ref('');
const photoVideoRef = ref(null);
const knownChecklistEntries = ref(Array.isArray(props.existingEntries) ? [...props.existingEntries] : []);
const saveState = ref('idle');
const saveError = ref('');

let html5QrcodeInstance = null;
let scannerStarting = false;
let scannerFinishing = false;
let photoStream = null;
let saveRequestSequence = 0;
let lastSavedEntrySignature = '';

const currentUser = computed(() => page.props.auth?.user || null);
const patroliSecurityOverlayAddressLines = [
  'Jl. Rungkut Industri Raya II',
  'No.45 B, Kali Rungkut, Kec.',
  'Rungkut, Kota SBY, Jawa',
  'Timur 60293, Indonesia',
];
const checklistAbilities = computed(() => props.checklistAbilities || page.props.checklistAbilities || {});
const canApproveKotakP3KHse = computed(() => Boolean(checklistAbilities.value.kotak_p3k_hse_approve));
const canApproveWarehouseFinal = computed(() => Boolean(checklistAbilities.value.warehouse_final_approve));
const saveStateLabel = computed(() => {
  if (!selectedChecklist.value) {
    return '';
  }

  if (saveState.value === 'saving') {
    return 'Menyimpan ke database...';
  }

  if (saveState.value === 'saved') {
    return 'Tersimpan di database';
  }

  if (saveState.value === 'error') {
    return saveError.value || 'Gagal menyimpan ke database.';
  }

  if (saveState.value === 'dirty') {
    return 'Perubahan belum tersimpan';
  }

  return 'Belum ada perubahan';
});
const saveStateClass = computed(() => {
  if (saveState.value === 'error') {
    return 'text-rose-300';
  }

  if (saveState.value === 'saved') {
    return 'text-emerald-300';
  }

  if (saveState.value === 'saving') {
    return 'text-sky-300';
  }

  return 'text-slate-400';
});

function cloneChecklistEntry(targetEntry) {
  return JSON.parse(JSON.stringify(targetEntry || null));
}

function buildEntrySignature(targetEntry) {
  if (!targetEntry) {
    return '';
  }

  try {
    return JSON.stringify(cloneChecklistEntry(targetEntry));
  } catch (error) {
    return '';
  }
}

function upsertKnownChecklistEntry(savedEntry) {
  if (!savedEntry?.id) {
    return;
  }

  const nextEntry = cloneChecklistEntry(savedEntry);
  const existingIndex = knownChecklistEntries.value.findIndex((item) => item?.id === nextEntry.id);

  if (existingIndex === -1) {
    knownChecklistEntries.value = [nextEntry, ...knownChecklistEntries.value];
    return;
  }

  const nextEntries = [...knownChecklistEntries.value];
  nextEntries[existingIndex] = nextEntry;
  knownChecklistEntries.value = nextEntries;
}

function upsertKnownChecklistEntries(entries = []) {
  entries.forEach((savedEntry) => upsertKnownChecklistEntry(savedEntry));
}

function syncCurrentEntryUrl(targetEntry = entry.value) {
  if (typeof window === 'undefined' || !targetEntry?.id || !targetEntry?.template_id) {
    return;
  }

  const params = new URLSearchParams({
    template: String(targetEntry.template_id),
    entry_id: String(targetEntry.id),
  });

  window.history.replaceState({}, '', `/gmiic/checklist/create?${params.toString()}`);
}

async function persistChecklistEntry(targetEntry = entry.value, options = {}) {
  if (!targetEntry?.id || !targetEntry?.template_id || !supportedTemplates.includes(targetEntry.template_id)) {
    return targetEntry;
  }

  const normalizedEntry = cloneChecklistEntry(targetEntry);
  const signature = buildEntrySignature(normalizedEntry);

  if (!options.force && signature !== '' && signature === lastSavedEntrySignature) {
    return normalizedEntry;
  }

  const requestId = ++saveRequestSequence;
  saveState.value = 'saving';
  saveError.value = '';

  try {
    const response = await axios.post('/gmiic/checklist/entries/save', {
      entry: normalizedEntry,
    });
    const savedEntry = response.data?.entry || normalizedEntry;

    upsertKnownChecklistEntry(savedEntry);
    lastSavedEntrySignature = buildEntrySignature(savedEntry);

    if (entry.value?.id === savedEntry.id) {
      entry.value = hydrateChecklistEntry(savedEntry);
    }

    syncCurrentEntryUrl(savedEntry);

    if (requestId === saveRequestSequence) {
      saveState.value = 'saved';
    }

    return savedEntry;
  } catch (error) {
    if (requestId === saveRequestSequence) {
      saveState.value = 'error';
      saveError.value = error?.response?.data?.message || 'Gagal menyimpan checklist.';
    }

    throw error;
  }
}

async function persistChecklistEntries(entries = []) {
  const normalizedEntries = entries
    .map((targetEntry) => cloneChecklistEntry(targetEntry))
    .filter((targetEntry) => targetEntry?.id && targetEntry?.template_id);

  if (!normalizedEntries.length) {
    return [];
  }

  saveState.value = 'saving';
  saveError.value = '';

  try {
    const response = await axios.post('/gmiic/checklist/entries/bulk-save', {
      entries: normalizedEntries,
    });
    const savedEntries = Array.isArray(response.data?.entries) ? response.data.entries : normalizedEntries;
    upsertKnownChecklistEntries(savedEntries);
    if (entry.value?.id) {
      const currentSavedEntry = savedEntries.find((savedEntry) => savedEntry?.id === entry.value?.id);
      if (currentSavedEntry) {
        entry.value = hydrateChecklistEntry(currentSavedEntry);
        lastSavedEntrySignature = buildEntrySignature(currentSavedEntry);
        syncCurrentEntryUrl(currentSavedEntry);
      }
    }
    saveState.value = 'saved';
    return savedEntries;
  } catch (error) {
    saveState.value = 'error';
    saveError.value = error?.response?.data?.message || 'Gagal menyimpan checklist.';
    throw error;
  }
}

function syncSaveStateWithEntry() {
  if (!entry.value?.id || !supportedTemplates.includes(entry.value?.template_id || '')) {
    saveState.value = 'idle';
    return;
  }

  const signature = buildEntrySignature(entry.value);
  saveState.value = signature !== '' && signature === lastSavedEntrySignature ? 'saved' : 'dirty';
}
const photoModalTitle = computed(() => {
  if (photoCaptureMode.value === 'patroli_security') {
    return 'Ambil Foto Patroli Security';
  }

  return 'Ambil Foto Petugas Pengangkut';
});

const photoModalDescription = computed(() => {
  if (photoCaptureMode.value === 'patroli_security') {
    return 'Gunakan kamera HP atau laptop, lalu ambil foto area patroli secara langsung.';
  }

  return 'Gunakan kamera HP atau laptop, lalu ambil foto langsung.';
});

const photoCaptureButtonLabel = computed(() => {
  if (photoCaptureMode.value === 'patroli_security') {
    return 'Capture & Upload';
  }

  return 'Ambil Foto';
});

const isKotakP3K = computed(() => selectedChecklist.value === 'kotak_p3k');
const isSanitation = computed(() => selectedChecklist.value === 'non_warehouse_sanitation');
const isFireSafety = computed(() => selectedChecklist.value === 'apar_smoke_detector_fire_alarm');
const isWasteTransport = computed(() => selectedChecklist.value === 'pengangkutan_sampah_pt_sier');
const isWarehouseSanitation = computed(() => selectedChecklist.value === 'warehouse_sanitation_1');
const isPersonalHygiene = computed(() => selectedChecklist.value === 'personal_hygiene_karyawan');
const isSaranaPrasarana = computed(() => selectedChecklist.value === 'sarana_dan_prasarana');
const isPatroliSecurity = computed(() => selectedChecklist.value === 'patroli_security');
const isSiteVisitHse = computed(() => selectedChecklist.value === 'site_visit_hse');
const isSiteVisitMaintenance = computed(() => selectedChecklist.value === 'site_visit_maintenance');

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

const currentSaranaPrasaranaScan = computed(() => {
  if (!isSaranaPrasarana.value || !entry.value || !nextPendingSaranaPrasaranaDay.value) {
    return null;
  }

  const dayScans = entry.value.form.area_scans_by_day?.[nextPendingSaranaPrasaranaDay.value.day] || {};
  return dayScans?.[entry.value.form.selected_area] || null;
});

const patroliSecurityApprovedAreas = computed(() => {
  if (!isPatroliSecurity.value || !entry.value) {
    return [];
  }

  return Array.isArray(entry.value.form.approved_areas) ? entry.value.form.approved_areas : [];
});

const currentPatroliSecuritySection = computed(() => {
  if (!isPatroliSecurity.value || !entry.value) {
    return null;
  }

  const areaId = String(entry.value.form.selected_area || '').trim();
  return (entry.value.form.sections || []).find((section) => section.id === areaId) || null;
});

const patroliSecurityTargetKey = computed(() => {
  if (!isPatroliSecurity.value || !entry.value) {
    return '';
  }

  return String(entry.value.form.selected_area || '').trim();
});

const currentPatroliSecurityBarcode = computed(() => {
  if (!isPatroliSecurity.value || !entry.value) {
    return '';
  }

  return entry.value.form.area_barcodes?.[patroliSecurityTargetKey.value] || '';
});

const patroliSecurityNoteLabel = computed(() => {
  if (!isPatroliSecurity.value || !entry.value) {
    return 'Keterangan';
  }

  return `Keterangan ${getPatroliSecurityAreaLabel(entry.value.form.selected_area)}`;
});

const patroliSecurityNote = computed({
  get() {
    if (!isPatroliSecurity.value || !entry.value) {
      return '';
    }

    return entry.value.form.area_notes?.[patroliSecurityTargetKey.value] || '';
  },
  set(value) {
    if (!isPatroliSecurity.value || !entry.value) {
      return;
    }

    entry.value.form.area_notes = {
      ...(entry.value.form.area_notes || {}),
      [patroliSecurityTargetKey.value]: value,
    };
  },
});

const patroliSecurityPhotoUploading = ref(false);
const patroliSecurityPhotoError = ref('');

function normalizePatroliSecurityPhotoBucket(bucket) {
  if (Array.isArray(bucket)) {
    return bucket.filter((item) => String(item || '').trim() !== '');
  }

  const single = String(bucket || '').trim();
  return single ? [single] : [];
}

const currentPatroliSecurityPhotoPaths = computed(() => {
  if (!isPatroliSecurity.value || !entry.value) {
    return [];
  }

  return normalizePatroliSecurityPhotoBucket(entry.value.form.area_photo_paths?.[patroliSecurityTargetKey.value]);
});

const currentPatroliSecurityPhotoUrls = computed(() => {
  if (!isPatroliSecurity.value || !entry.value) {
    return [];
  }

  return normalizePatroliSecurityPhotoBucket(entry.value.form.area_photo_urls?.[patroliSecurityTargetKey.value]);
});

const currentPatroliSecurityPhotoNames = computed(() => {
  if (!isPatroliSecurity.value || !entry.value) {
    return [];
  }

  return normalizePatroliSecurityPhotoBucket(entry.value.form.area_photo_names?.[patroliSecurityTargetKey.value]);
});

const currentPatroliSecurityPhotos = computed(() => {
  const paths = currentPatroliSecurityPhotoPaths.value;
  const urls = currentPatroliSecurityPhotoUrls.value;
  const names = currentPatroliSecurityPhotoNames.value;
  const length = Math.max(paths.length, urls.length, names.length);

  return Array.from({ length }, (_, index) => ({
    path: paths[index] || '',
    url: urls[index] || '',
    name: names[index] || '',
  })).filter((photo) => String(photo.url || photo.path || '').trim() !== '');
});

const patroliSecurityValidation = computed(() => {
  if (!isPatroliSecurity.value || !entry.value) {
    return {
      allAnswersFilled: false,
      hasNoAnswer: false,
      hasRequiredNote: false,
    };
  }

  const activeRows = currentPatroliSecuritySection.value?.items || [];
  const statuses = activeRows.map((row) => row.status || '');
  const allAnswersFilled = activeRows.length > 0 && statuses.every((status) => status === 'yes' || status === 'no');
  const hasNoAnswer = statuses.includes('no');
  const hasRequiredNote = String(patroliSecurityNote.value || '').trim() !== '';

  return {
    allAnswersFilled,
    hasNoAnswer,
    hasRequiredNote,
  };
});

const canScanPatroliSecurity = computed(() => {
  if (!isPatroliSecurity.value || !entry.value) {
    return false;
  }

  const selectedArea = String(entry.value.form.selected_area || '').trim();

  return Boolean(String(entry.value.form.date_value || '').trim())
    && Boolean(selectedArea)
    && !patroliSecurityApprovedAreas.value.includes(selectedArea)
    && patroliSecurityValidation.value.allAnswersFilled
    && (!patroliSecurityValidation.value.hasNoAnswer || patroliSecurityValidation.value.hasRequiredNote)
    && !String(currentPatroliSecurityBarcode.value || '').trim();
});

const siteVisitHseApprovedAreas = computed(() => {
  if (!isSiteVisitHse.value || !entry.value) {
    return [];
  }

  return Array.isArray(entry.value.form.approved_areas) ? entry.value.form.approved_areas : [];
});

const currentSiteVisitHseSection = computed(() => {
  if (!isSiteVisitHse.value || !entry.value) {
    return null;
  }

  const areaId = String(entry.value.form.selected_area || '').trim();
  return (entry.value.form.sections || []).find((section) => section.id === areaId) || null;
});

const siteVisitHseTargetKey = computed(() => {
  if (!isSiteVisitHse.value || !entry.value) {
    return '';
  }

  return String(entry.value.form.selected_area || '').trim();
});

const currentSiteVisitHseBarcode = computed(() => {
  if (!isSiteVisitHse.value || !entry.value) {
    return '';
  }

  return entry.value.form.area_barcodes?.[siteVisitHseTargetKey.value] || '';
});

const siteVisitHseNoteLabel = computed(() => {
  if (!isSiteVisitHse.value || !entry.value) {
    return 'Keterangan';
  }

  return `Keterangan ${getSiteVisitHseAreaLabel(entry.value.form.selected_area)}`;
});

const siteVisitHseNote = computed({
  get() {
    if (!isSiteVisitHse.value || !entry.value) {
      return '';
    }

    return entry.value.form.area_notes?.[siteVisitHseTargetKey.value] || '';
  },
  set(value) {
    if (!isSiteVisitHse.value || !entry.value) {
      return;
    }

    entry.value.form.area_notes = {
      ...(entry.value.form.area_notes || {}),
      [siteVisitHseTargetKey.value]: value,
    };
  },
});

const siteVisitHseValidation = computed(() => {
  if (!isSiteVisitHse.value || !entry.value) {
    return {
      allAnswersFilled: false,
      hasNoAnswer: false,
      hasRequiredNote: false,
    };
  }

  const activeRows = currentSiteVisitHseSection.value?.items || [];
  const statuses = activeRows.map((row) => row.status || '');
  const allAnswersFilled = activeRows.length > 0 && statuses.every((status) => status === 'yes' || status === 'no');
  const hasNoAnswer = statuses.includes('no');
  const hasRequiredNote = String(siteVisitHseNote.value || '').trim() !== '';

  return {
    allAnswersFilled,
    hasNoAnswer,
    hasRequiredNote,
  };
});

const canScanSiteVisitHse = computed(() => {
  if (!isSiteVisitHse.value || !entry.value) {
    return false;
  }

  const selectedArea = String(entry.value.form.selected_area || '').trim();

  return Boolean(String(entry.value.form.date_value || '').trim())
    && Boolean(selectedArea)
    && !siteVisitHseApprovedAreas.value.includes(selectedArea)
    && siteVisitHseValidation.value.allAnswersFilled
    && (!siteVisitHseValidation.value.hasNoAnswer || siteVisitHseValidation.value.hasRequiredNote)
    && !String(currentSiteVisitHseBarcode.value || '').trim();
});

const maintenanceTypeMeta = computed(() => {
  if (!isSiteVisitMaintenance.value || !entry.value) {
    return getMaintenanceVisitTypeMeta('maintenance_harian');
  }

  return getMaintenanceVisitTypeMeta(entry.value.form.visit_type);
});

const maintenanceSections = computed(() => {
  if (!isSiteVisitMaintenance.value || !entry.value) {
    return [];
  }

  const selectedArea = String(entry.value.form.selected_area || '').trim();
  const sections = Array.isArray(entry.value.form.sections) ? entry.value.form.sections : [];

  if (entry.value.form.visit_type !== 'maintenance_harian') {
    return sections;
  }

  return sections.filter((section) => String(section.area_id || '').trim() === selectedArea);
});

const maintenanceRows = computed(() => {
  if (!isSiteVisitMaintenance.value || !entry.value) {
    return [];
  }

  return Array.isArray(entry.value.form.rows) ? entry.value.form.rows : [];
});

const maintenanceScanTargetKey = computed(() => {
  if (!isSiteVisitMaintenance.value || !entry.value) {
    return '';
  }

  return entry.value.form.visit_type === 'maintenance_mingguan'
    ? 'lantai_1_area_belakang'
    : String(entry.value.form.selected_area || '').trim();
});

const currentMaintenanceBarcode = computed(() => {
  if (!isSiteVisitMaintenance.value || !entry.value) {
    return '';
  }

  return entry.value.form.area_barcodes?.[maintenanceScanTargetKey.value] || '';
});

const maintenanceNoteLabel = computed(() => {
  if (!isSiteVisitMaintenance.value || !entry.value) {
    return 'Keterangan';
  }

  return entry.value.form.visit_type === 'maintenance_mingguan'
    ? 'Keterangan Mingguan'
    : `Keterangan ${getMaintenanceDailyAreaLabel(entry.value.form.selected_area)}`;
});

const maintenanceNote = computed({
  get() {
    if (!isSiteVisitMaintenance.value || !entry.value) {
      return '';
    }

    return entry.value.form.area_notes?.[maintenanceScanTargetKey.value] || '';
  },
  set(value) {
    if (!isSiteVisitMaintenance.value || !entry.value) {
      return;
    }

    entry.value.form.area_notes = {
      ...(entry.value.form.area_notes || {}),
      [maintenanceScanTargetKey.value]: value,
    };
  },
});

const maintenanceValidation = computed(() => {
  if (!isSiteVisitMaintenance.value || !entry.value) {
    return {
      allAnswersFilled: false,
      hasNoAnswer: false,
      hasRequiredNote: false,
    };
  }

  const visitType = String(entry.value.form.visit_type || '').trim();
  const activeRows = visitType === 'maintenance_mingguan'
    ? maintenanceRows.value
    : maintenanceSections.value.flatMap((section) => section.items || []);

  const statuses = activeRows.map((row) => row.status || '');
  const allAnswersFilled = activeRows.length > 0 && statuses.every((status) => status === 'yes' || status === 'no');
  const hasNoAnswer = statuses.includes('no');
  const hasRequiredNote = String(maintenanceNote.value || '').trim() !== '';

  return {
    allAnswersFilled,
    hasNoAnswer,
    hasRequiredNote,
  };
});

const canScanMaintenance = computed(() => {
  if (!isSiteVisitMaintenance.value || !entry.value) {
    return false;
  }

  const visitType = String(entry.value.form.visit_type || '').trim();
  if (!visitType) {
    return false;
  }

  const hasSchedule = visitType === 'maintenance_mingguan'
    ? Boolean(String(entry.value.form.period_value || '').trim())
    : Boolean(String(entry.value.form.date_value || '').trim()) && Boolean(String(entry.value.form.selected_area || '').trim());

  return hasSchedule
    && maintenanceValidation.value.allAnswersFilled
    && (!maintenanceValidation.value.hasNoAnswer || maintenanceValidation.value.hasRequiredNote)
    && !String(currentMaintenanceBarcode.value || '').trim();
});

const nextPendingSaranaPrasaranaDay = computed(() => {
  if (!isSaranaPrasarana.value || !entry.value) {
    return null;
  }

  return saranaPrasaranaDays.value.find((day) => !day.isSunday && !saranaPrasaranaApprovedDays.value.includes(day.day)) || null;
});

const canScanSaranaPrasaranaArea = computed(() => {
  if (!isSaranaPrasarana.value || !entry.value || !nextPendingSaranaPrasaranaDay.value) {
    return false;
  }

  if (currentSaranaPrasaranaScan.value?.barcode) {
    return false;
  }

  return Boolean(String(entry.value.form.period || '').trim())
    && Boolean(String(entry.value.form.selected_area || '').trim())
    && (currentSaranaPrasaranaSection.value?.items || []).every((item) => Boolean(item.days?.[nextPendingSaranaPrasaranaDay.value.day]));
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

const sanitationNoteTargetKey = computed(() => {
  if (!isSanitation.value || !entry.value || !nextPendingSanitationDay.value) {
    return '';
  }

  return [
    String(entry.value.form.period || '').trim(),
    String(nextPendingSanitationDay.value.day || '').trim(),
    String(entry.value.form.area || '').trim(),
  ].join('__');
});

const sanitationNote = computed(() => {
  if (!isSanitation.value || !entry.value || !sanitationNoteTargetKey.value) {
    return '';
  }

  return entry.value.form.area_notes?.[sanitationNoteTargetKey.value] || '';
});

const sanitationNoteLabel = computed(() => {
  if (!isSanitation.value || !entry.value || !nextPendingSanitationDay.value) {
    return 'Keterangan';
  }

  return `Keterangan Hari ${nextPendingSanitationDay.value.day} - ${getSanitationAreaLabel(entry.value.form.area)}`;
});

const canEditSanitationNote = computed(() => {
  return Boolean(isSanitation.value && entry.value && nextPendingSanitationDay.value);
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

const currentWarehouseBarcode = computed(() => {
  if (!isWarehouseSanitation.value || !entry.value) {
    return '';
  }

  return String(entry.value.form.barcode || '').trim();
});

const currentWarehouseScanDate = computed(() => {
  if (!isWarehouseSanitation.value || !entry.value) {
    return '';
  }

  return String(entry.value.form.scan_date || '').trim();
});

const canScanWarehouseBarcode = computed(() => {
  if (!isWarehouseSanitation.value || !entry.value || warehousePreparedApproved.value) {
    return false;
  }

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

  return generalCompleted
    && areaRowsCompleted
    && iceControlCompleted
    && cleaningMaterialCompleted
    && !currentWarehouseBarcode.value;
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
      return Boolean(currentWarehouseBarcode.value);
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
      && (currentSaranaPrasaranaSection.value?.items || []).every((item) => Boolean(item.days?.[nextPendingSaranaPrasaranaDay.value.day]))
      && Boolean(currentSaranaPrasaranaScan.value?.barcode);
  }

  if (isSiteVisitMaintenance.value) {
    const visitType = String(entry.value.form.visit_type || '').trim();
    const hasSchedule = visitType === 'maintenance_mingguan'
      ? Boolean(String(entry.value.form.period_value || '').trim())
      : Boolean(String(entry.value.form.date_value || '').trim());

    return Boolean(visitType)
      && hasSchedule
      && (visitType === 'maintenance_mingguan' || Boolean(String(entry.value.form.selected_area || '').trim()))
      && Boolean(String(currentMaintenanceBarcode.value || '').trim())
      && maintenanceValidation.value.allAnswersFilled
      && (!maintenanceValidation.value.hasNoAnswer || maintenanceValidation.value.hasRequiredNote);
  }

  if (isSiteVisitHse.value) {
    const selectedArea = String(entry.value.form.selected_area || '').trim();

    return Boolean(String(entry.value.form.date_value || '').trim())
      && Boolean(selectedArea)
      && !siteVisitHseApprovedAreas.value.includes(selectedArea)
      && Boolean(String(currentSiteVisitHseBarcode.value || '').trim())
      && siteVisitHseValidation.value.allAnswersFilled
      && (!siteVisitHseValidation.value.hasNoAnswer || siteVisitHseValidation.value.hasRequiredNote);
  }

  if (isPatroliSecurity.value) {
    const selectedArea = String(entry.value.form.selected_area || '').trim();

    return Boolean(String(entry.value.form.date_value || '').trim())
      && Boolean(selectedArea)
      && !patroliSecurityApprovedAreas.value.includes(selectedArea)
      && Boolean(String(currentPatroliSecurityBarcode.value || '').trim())
      && patroliSecurityValidation.value.allAnswersFilled
      && (!patroliSecurityValidation.value.hasNoAnswer || patroliSecurityValidation.value.hasRequiredNote);
  }

  return false;
});

function findOpenPatroliSecurityDraft(entries = []) {
  const candidates = Array.isArray(entries) ? entries : [];

  return candidates.find((candidate) => {
    if (candidate?.template_id !== 'patroli_security') {
      return false;
    }

    if (Boolean(candidate?.form?.approved)) {
      return false;
    }

    return true;
  }) || null;
}

function createEntryByTemplate(templateId, options = {}) {
  const userName = page.props.auth?.user?.name || 'User Login';
  const continuableEntry = options.continuableEntry || null;

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

  if (templateId === 'patroli_security') {
    if (continuableEntry) {
      return hydrateChecklistEntry(continuableEntry);
    }

    return createPatroliSecurityEntry(userName);
  }

  if (templateId === 'site_visit_hse') {
    return createSiteVisitHseEntry(userName);
  }

  if (templateId === 'site_visit_maintenance') {
    return createSiteVisitMaintenanceEntry(userName);
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
      area_scans_by_day: {
        ...(savedEntry?.form?.area_scans_by_day || {}),
      },
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

function hydratePatroliSecurityEntry(savedEntry) {
  if (savedEntry?.template_id !== 'patroli_security') {
    return savedEntry;
  }

  const now = new Date();
  const nextDateValue = String(savedEntry?.form?.date_value || '').trim() || toDateInputValue(now);
  const nextSelectedArea = String(savedEntry?.form?.selected_area || '').trim()
    || savedEntry?.form?.sections?.[0]?.id
    || patroliSecurityAreaOptions[0]?.id
    || '';

  return {
    ...savedEntry,
    form: {
      ...savedEntry.form,
      date_value: nextDateValue,
      date: savedEntry?.form?.date || formatDateInputDisplay(nextDateValue),
      selected_area: nextSelectedArea,
      approved_areas: Array.isArray(savedEntry?.form?.approved_areas) ? savedEntry.form.approved_areas : [],
      area_barcodes: {
        ...(savedEntry?.form?.area_barcodes || {}),
      },
      area_notes: {
        ...(savedEntry?.form?.area_notes || {}),
      },
      area_photo_paths: {
        ...(savedEntry?.form?.area_photo_paths || {}),
      },
      area_photo_urls: {
        ...(savedEntry?.form?.area_photo_urls || {}),
      },
      area_photo_names: {
        ...(savedEntry?.form?.area_photo_names || {}),
      },
      area_scan_dates: {
        ...(savedEntry?.form?.area_scan_dates || {}),
      },
      document_no: savedEntry?.form?.document_no || 'FRM.HSE.15.02',
      rev: savedEntry?.form?.rev || '00',
      effective_date: savedEntry?.form?.effective_date || '22 Desember 2025',
      page: savedEntry?.form?.page || '1 dari 1',
      sections: rebuildPatroliSecuritySections(savedEntry?.form?.sections || []),
    },
  };
}

function hydrateSiteVisitHseEntry(savedEntry) {
  if (savedEntry?.template_id !== 'site_visit_hse') {
    return savedEntry;
  }

  const now = new Date();
  const nextDateValue = String(savedEntry?.form?.date_value || '').trim() || toDateInputValue(now);
  const nextSelectedArea = String(savedEntry?.form?.selected_area || '').trim()
    || savedEntry?.form?.sections?.[0]?.id
    || siteVisitHseAreaOptions[0]?.id
    || '';

  return {
    ...savedEntry,
    form: {
      ...savedEntry.form,
      date_value: nextDateValue,
      date: savedEntry?.form?.date || formatDateInputDisplay(nextDateValue),
      selected_area: nextSelectedArea,
      approved_areas: Array.isArray(savedEntry?.form?.approved_areas) ? savedEntry.form.approved_areas : [],
      area_barcodes: {
        ...(savedEntry?.form?.area_barcodes || {}),
      },
      area_notes: {
        ...(savedEntry?.form?.area_notes || {}),
      },
      area_scan_dates: {
        ...(savedEntry?.form?.area_scan_dates || {}),
      },
      document_no: savedEntry?.form?.document_no || 'FRM.HSE.15.01',
      rev: savedEntry?.form?.rev || '00',
      effective_date: savedEntry?.form?.effective_date || '22 Desember 2025',
      page: savedEntry?.form?.page || '1 dari 1',
      sections: rebuildSiteVisitHseSections(savedEntry?.form?.sections || []),
    },
  };
}

function hydrateSiteVisitMaintenanceEntry(savedEntry) {
  if (savedEntry?.template_id !== 'site_visit_maintenance') {
    return savedEntry;
  }

  const visitType = String(savedEntry?.form?.visit_type || '').trim() || 'maintenance_harian';
  const typeMeta = getMaintenanceVisitTypeMeta(visitType);
  const now = new Date();
  const nextDateValue = String(savedEntry?.form?.date_value || '').trim() || toDateInputValue(now);
  const nextPeriodValue = String(savedEntry?.form?.period_value || '').trim() || toWeekInputValue(now);
  const nextSelectedArea = String(savedEntry?.form?.selected_area || '').trim() || 'lantai_1_area_belakang';

  return {
    ...savedEntry,
    form: {
      ...savedEntry.form,
      visit_type: visitType,
      document_no: savedEntry?.form?.document_no || typeMeta.document_no,
      rev: savedEntry?.form?.rev || '00',
      effective_date: savedEntry?.form?.effective_date || '22 Desember 2025',
      page: savedEntry?.form?.page || '1 dari 1',
      selected_area: nextSelectedArea,
      area_barcodes: {
        ...(savedEntry?.form?.area_barcodes || {}),
      },
      area_scan_dates: {
        ...(savedEntry?.form?.area_scan_dates || {}),
      },
      area_notes: {
        ...(savedEntry?.form?.area_notes || {}),
      },
      date_value: nextDateValue,
      date: visitType === 'maintenance_harian'
        ? (savedEntry?.form?.date || formatDateInputDisplay(nextDateValue))
        : '',
      period_value: nextPeriodValue,
      period: visitType === 'maintenance_mingguan'
        ? (savedEntry?.form?.period || formatWeekDisplay(nextPeriodValue))
        : '',
      sections: rebuildMaintenanceDailySections(savedEntry?.form?.sections || []),
      rows: rebuildMaintenanceWeeklyRows(savedEntry?.form?.rows || []),
    },
  };
}

function hydrateChecklistEntry(savedEntry) {
  const fireSafetyHydratedEntry = hydrateFireSafetyEntry(savedEntry);
  const saranaPrasaranaHydratedEntry = hydrateSaranaPrasaranaEntry(fireSafetyHydratedEntry);
  const patroliSecurityHydratedEntry = hydratePatroliSecurityEntry(saranaPrasaranaHydratedEntry);
  const personalHygieneHydratedEntry = hydratePersonalHygieneEntry(patroliSecurityHydratedEntry);
  const siteVisitHseHydratedEntry = hydrateSiteVisitHseEntry(personalHygieneHydratedEntry);

  return hydrateSiteVisitMaintenanceEntry(siteVisitHseHydratedEntry);
}

function createInitialEntry() {
  if (props.entryId && props.savedEntry) {
    const hydratedEntry = hydrateChecklistEntry(props.savedEntry);
    selectedChecklist.value = hydratedEntry.template_id;
    return hydratedEntry;
  }

  const continuablePatroliSecurityEntry = selectedChecklist.value === 'patroli_security'
    ? findOpenPatroliSecurityDraft(props.existingEntries)
    : null;

  return createEntryByTemplate(selectedChecklist.value, {
    continuableEntry: continuablePatroliSecurityEntry,
  });
}

function refreshEntry() {
  if (props.entryId && entry.value?.id === props.entryId && entry.value?.template_id === selectedChecklist.value) {
    return;
  }

  const continuablePatroliSecurityEntry = selectedChecklist.value === 'patroli_security'
    ? findOpenPatroliSecurityDraft(knownChecklistEntries.value)
    : null;

  entry.value = createEntryByTemplate(selectedChecklist.value, {
    continuableEntry: continuablePatroliSecurityEntry,
  });

  if (continuablePatroliSecurityEntry && entry.value?.id === continuablePatroliSecurityEntry.id) {
    syncCurrentEntryUrl(entry.value);
  }
}

const autoOpenedPatroliSecurityDraft = !props.entryId
  && entry.value?.template_id === 'patroli_security'
  && findOpenPatroliSecurityDraft(props.existingEntries)?.id === entry.value?.id;

lastSavedEntrySignature = buildEntrySignature(entry.value);
if ((props.entryId && props.savedEntry) || autoOpenedPatroliSecurityDraft) {
  saveState.value = 'saved';
}

if (autoOpenedPatroliSecurityDraft) {
  syncCurrentEntryUrl(entry.value);
}

async function approveChecklist() {
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

    await persistChecklistEntry(entry.value, { force: true });
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
    await persistChecklistEntry(entry.value, { force: true });
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
    await persistChecklistEntry(entry.value, { force: true });
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

  if (isPatroliSecurity.value) {
    const selectedArea = String(entry.value.form.selected_area || '').trim();

    entry.value.form.approved_areas = [
      ...new Set([...(entry.value.form.approved_areas || []), selectedArea]),
    ];
    entry.value.form.approved = patroliSecurityAreaOptions.every((area) => entry.value.form.approved_areas.includes(area.id));
    await persistChecklistEntry(entry.value, { force: true });
    return;
  }

  if (isSiteVisitHse.value) {
    const selectedArea = String(entry.value.form.selected_area || '').trim();

    entry.value.form.approved_areas = [
      ...new Set([...(entry.value.form.approved_areas || []), selectedArea]),
    ];
    entry.value.form.approved = siteVisitHseAreaOptions.every((area) => entry.value.form.approved_areas.includes(area.id));
    await persistChecklistEntry(entry.value, { force: true });
    return;
  }

  entry.value.form.approved = true;
  await persistChecklistEntry(entry.value, { force: true });
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

async function scanSaranaPrasaranaArea() {
  if (!entry.value || !isSaranaPrasarana.value || !canScanSaranaPrasaranaArea.value) {
    return;
  }

  scannerMode.value = 'sarana_prasarana';
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

async function scanWarehouseBarcode() {
  if (!entry.value || !isWarehouseSanitation.value || !canScanWarehouseBarcode.value) {
    return;
  }

  scannerMode.value = 'warehouse_sanitation';
  scannerError.value = '';
  scannerLoading.value = true;
  scannerModalOpen.value = true;

  await nextTick();
  await startBarcodeScanner();
}

async function scanMaintenanceBarcode() {
  if (!entry.value || !isSiteVisitMaintenance.value || !canScanMaintenance.value) {
    return;
  }

  scannerMode.value = 'site_visit_maintenance';
  scannerError.value = '';
  scannerLoading.value = true;
  scannerModalOpen.value = true;

  await nextTick();
  await startBarcodeScanner();
}

async function scanSiteVisitHseBarcode() {
  if (!entry.value || !isSiteVisitHse.value || !canScanSiteVisitHse.value) {
    return;
  }

  scannerMode.value = 'site_visit_hse';
  scannerError.value = '';
  scannerLoading.value = true;
  scannerModalOpen.value = true;

  await nextTick();
  await startBarcodeScanner();
}

async function scanPatroliSecurityBarcode() {
  if (!entry.value || !isPatroliSecurity.value || !canScanPatroliSecurity.value) {
    return;
  }

  scannerMode.value = 'patroli_security';
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
  entry.value.form.area_scans_by_day = {};
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

function updatePatroliSecurityDate(value) {
  if (!entry.value || !isPatroliSecurity.value) {
    return;
  }

  entry.value.form.date_value = value;
  entry.value.form.date = formatDateInputDisplay(value);
  entry.value.form.approved_areas = [];
  entry.value.form.area_barcodes = {};
  entry.value.form.area_notes = {};
  entry.value.form.area_scan_dates = {};
  entry.value.form.approved = false;
  entry.value.form.sections = rebuildPatroliSecuritySections([]);
}

function updatePatroliSecurityArea(value) {
  if (!entry.value || !isPatroliSecurity.value) {
    return;
  }

  entry.value.form.selected_area = value;
}

function updatePatroliSecurityNote(value) {
  if (!entry.value || !isPatroliSecurity.value || !patroliSecurityTargetKey.value) {
    return;
  }

  entry.value.form.area_notes = {
    ...(entry.value.form.area_notes || {}),
    [patroliSecurityTargetKey.value]: String(value || ''),
  };
}

function updatePatroliSecurityPhotoState(payload = {}) {
  if (!entry.value || !isPatroliSecurity.value || !patroliSecurityTargetKey.value) {
    return;
  }

  const targetKey = patroliSecurityTargetKey.value;
  const nextPaths = {
    ...(entry.value.form.area_photo_paths || {}),
  };
  const nextUrls = {
    ...(entry.value.form.area_photo_urls || {}),
  };
  const nextNames = {
    ...(entry.value.form.area_photo_names || {}),
  };

  const pathBucket = normalizePatroliSecurityPhotoBucket(nextPaths[targetKey]);
  const urlBucket = normalizePatroliSecurityPhotoBucket(nextUrls[targetKey]);
  const nameBucket = normalizePatroliSecurityPhotoBucket(nextNames[targetKey]);

  if (typeof payload.removeIndex === 'number') {
    pathBucket.splice(payload.removeIndex, 1);
    urlBucket.splice(payload.removeIndex, 1);
    nameBucket.splice(payload.removeIndex, 1);
  } else if (!payload.clear) {
    pathBucket.push(payload.path || '');
    urlBucket.push(payload.url || '');
    nameBucket.push(payload.name || '');
  }

  if (payload.clear || pathBucket.length === 0) {
    delete nextPaths[targetKey];
    delete nextUrls[targetKey];
    delete nextNames[targetKey];
  } else {
    nextPaths[targetKey] = pathBucket;
    nextUrls[targetKey] = urlBucket;
    nextNames[targetKey] = nameBucket;
  }

  entry.value.form.area_photo_paths = nextPaths;
  entry.value.form.area_photo_urls = nextUrls;
  entry.value.form.area_photo_names = nextNames;
}

async function uploadPatroliSecurityPhoto(file) {
  if (!entry.value || !isPatroliSecurity.value || !patroliSecurityTargetKey.value || !file) {
    return;
  }

  patroliSecurityPhotoUploading.value = true;
  patroliSecurityPhotoError.value = '';

  try {
    const formData = new FormData();
    formData.append('photo', file);

    const response = await axios.post('/gmiic/checklist/patroli-security/photo', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    updatePatroliSecurityPhotoState({
      path: response.data?.path || '',
      url: response.data?.url || '',
      name: response.data?.original_name || file.name || '',
    });
    syncSaveStateWithEntry();
  } catch (error) {
    patroliSecurityPhotoError.value = error?.response?.data?.message || 'Foto gagal di-upload.';
  } finally {
    patroliSecurityPhotoUploading.value = false;
  }
}

async function removePatroliSecurityPhoto(index) {
  if (!entry.value || !isPatroliSecurity.value || !patroliSecurityTargetKey.value) {
    return;
  }

  patroliSecurityPhotoError.value = '';
  const photo = currentPatroliSecurityPhotos.value[Number(index)];
  if (!photo) {
    return;
  }

  try {
    if (String(photo.path || '').trim()) {
      await axios.delete('/gmiic/checklist/patroli-security/photo', {
        data: {
          path: photo.path,
        },
      });
    }

    updatePatroliSecurityPhotoState({ removeIndex: Number(index) });
    syncSaveStateWithEntry();
  } catch (error) {
    patroliSecurityPhotoError.value = error?.response?.data?.message || 'Foto gagal dihapus.';
  }
}

function updateSiteVisitHseDate(value) {
  if (!entry.value || !isSiteVisitHse.value) {
    return;
  }

  entry.value.form.date_value = value;
  entry.value.form.date = formatDateInputDisplay(value);
  entry.value.form.approved_areas = [];
  entry.value.form.area_barcodes = {};
  entry.value.form.area_notes = {};
  entry.value.form.area_scan_dates = {};
  entry.value.form.approved = false;
  entry.value.form.sections = rebuildSiteVisitHseSections([]);
}

function updateSiteVisitHseArea(value) {
  if (!entry.value || !isSiteVisitHse.value) {
    return;
  }

  entry.value.form.selected_area = value;
}

function updateSiteVisitHseNote(value) {
  if (!entry.value || !isSiteVisitHse.value || !siteVisitHseTargetKey.value) {
    return;
  }

  entry.value.form.area_notes = {
    ...(entry.value.form.area_notes || {}),
    [siteVisitHseTargetKey.value]: String(value || ''),
  };
}

function updateSanitationNote(value) {
  if (!entry.value || !isSanitation.value || !sanitationNoteTargetKey.value) {
    return;
  }

  entry.value.form.area_notes = {
    ...(entry.value.form.area_notes || {}),
    [sanitationNoteTargetKey.value]: String(value || ''),
  };
}

function updateMaintenanceVisitType(value) {
  if (!entry.value || !isSiteVisitMaintenance.value) {
    return;
  }

  const visitType = String(value || '').trim() || 'maintenance_harian';
  const typeMeta = getMaintenanceVisitTypeMeta(visitType);

  entry.value.form.visit_type = visitType;
  entry.value.form.document_no = typeMeta.document_no;
  entry.value.form.selected_area = visitType === 'maintenance_harian'
    ? (entry.value.form.selected_area || 'lantai_1_area_belakang')
    : 'lantai_1_area_belakang';
  entry.value.form.date = visitType === 'maintenance_harian'
    ? formatDateInputDisplay(entry.value.form.date_value || toDateInputValue(new Date()))
    : '';
  entry.value.form.period = visitType === 'maintenance_mingguan'
    ? formatWeekDisplay(entry.value.form.period_value || toWeekInputValue(new Date()))
    : '';
  entry.value.form.sections = rebuildMaintenanceDailySections(entry.value.form.sections || []);
  entry.value.form.rows = rebuildMaintenanceWeeklyRows(entry.value.form.rows || []);
}

function updateMaintenanceVisitDate(value) {
  if (!entry.value || !isSiteVisitMaintenance.value) {
    return;
  }

  entry.value.form.date_value = value;
  entry.value.form.date = formatDateInputDisplay(value);
}

function updateMaintenanceVisitPeriod(value) {
  if (!entry.value || !isSiteVisitMaintenance.value) {
    return;
  }

  entry.value.form.period_value = value;
  entry.value.form.period = formatWeekDisplay(value);
}

function updateMaintenanceVisitArea(value) {
  if (!entry.value || !isSiteVisitMaintenance.value) {
    return;
  }

  entry.value.form.selected_area = value;
}

function getNextMaintenanceStatus(currentValue) {
  if (currentValue === 'yes') {
    return 'no';
  }

  if (currentValue === 'no') {
    return '';
  }

  return 'yes';
}

function cycleMaintenanceRowStatus(payload) {
  if (!entry.value || !isSiteVisitMaintenance.value || !payload?.rowId) {
    return;
  }

  if (entry.value.form.visit_type === 'maintenance_mingguan') {
    entry.value.form.rows = (entry.value.form.rows || []).map((row) => (
      row.id === payload.rowId ? { ...row, status: getNextMaintenanceStatus(row.status) } : row
    ));
    return;
  }

  entry.value.form.sections = (entry.value.form.sections || []).map((section) => (
    section.id === payload.sectionId
      ? {
          ...section,
          items: (section.items || []).map((item) => (
            item.id === payload.rowId ? { ...item, status: getNextMaintenanceStatus(item.status) } : item
          )),
        }
      : section
  ));
}

function updateMaintenanceNote(value) {
  if (!entry.value || !isSiteVisitMaintenance.value || !maintenanceScanTargetKey.value) {
    return;
  }

  entry.value.form.area_notes = {
    ...(entry.value.form.area_notes || {}),
    [maintenanceScanTargetKey.value]: String(value || ''),
  };
}

function cycleSiteVisitHseRowStatus(sectionId, itemId) {
  if (!entry.value || !isSiteVisitHse.value) {
    return;
  }

  if (siteVisitHseApprovedAreas.value.includes(String(entry.value.form.selected_area || '').trim())) {
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

        const currentValue = item.status || '';
        const nextValue = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : '';

        return {
          ...item,
          status: nextValue,
        };
      }),
    };
  });
}

function cyclePatroliSecurityRowStatus(sectionId, itemId) {
  if (!entry.value || !isPatroliSecurity.value) {
    return;
  }

  if (patroliSecurityApprovedAreas.value.includes(String(entry.value.form.selected_area || '').trim())) {
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

        const currentValue = item.status || '';
        const nextValue = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : '';

        return {
          ...item,
          status: nextValue,
        };
      }),
    };
  });
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

async function generatePersonalHygieneFullMonth() {
  if (!entry.value || !isPersonalHygiene.value) {
    return;
  }

  const targetPeriod = entry.value.form.period || toPeriodValue(new Date());
  const targetYear = String(targetPeriod || '').split('-')[0] || String(new Date().getFullYear());
  const existingEntries = knownChecklistEntries.value.filter((savedEntry) => {
    return savedEntry?.template_id === 'personal_hygiene_karyawan'
      && String(savedEntry?.form?.period || '') === targetPeriod;
  });
  let createdCount = 0;
  let skippedCount = 0;
  const entriesToSave = [];

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

    entriesToSave.push(nextEntry);
    createdCount += 1;
  });

  if (entriesToSave.length) {
    await persistChecklistEntries(entriesToSave);
  }

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

  if (field === 'period' || field === 'date') {
    entry.value.form.barcode = '';
    entry.value.form.scan_date = '';
  }
}

function updateWarehouseFrequency(value) {
  if (!entry.value || !isWarehouseSanitation.value) {
    return;
  }

  entry.value.form.frequency = value;
  entry.value.form.barcode = '';
  entry.value.form.scan_date = '';
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

  photoCaptureMode.value = 'waste_transport';
  photoCaptureDay.value = day;
  photoError.value = '';
  photoLoading.value = true;
  photoModalOpen.value = true;

  await nextTick();
  await startPhotoCamera();
}

async function openPatroliSecurityCamera() {
  if (!entry.value || !isPatroliSecurity.value || !patroliSecurityTargetKey.value) {
    return;
  }

  photoCaptureMode.value = 'patroli_security';
  photoCaptureDay.value = null;
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

function normalizeBarcodeText(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, ' ')
    .trim()
    .replace(/\s+/g, ' ');
}

function isBarcodeTextMatchingExpected(decodedText, expectedValues = []) {
  const normalizedDecoded = normalizeBarcodeText(decodedText);
  if (!normalizedDecoded) {
    return false;
  }

  return expectedValues.some((value) => {
    const normalizedExpected = normalizeBarcodeText(value);
    if (!normalizedExpected) {
      return false;
    }

    return normalizedDecoded === normalizedExpected
      || normalizedDecoded.includes(normalizedExpected)
      || normalizedExpected.includes(normalizedDecoded);
  });
}

function getExpectedBarcodeValuesForCurrentScannerMode() {
  if (!entry.value) {
    return [];
  }

  if (scannerMode.value === 'kotak_p3k') {
    return getLocationBarcodeAliases(entry.value.form.location);
  }

  if (scannerMode.value === 'fire_safety') {
    return getLocationBarcodeAliases(entry.value.form.location)
      .concat(getFireSafetyLocationLabel(entry.value.form.card_type, entry.value.form.location));
  }

  if (scannerMode.value === 'sanitation_area') {
    return getSanitationAreaBarcodeAliases(entry.value.form.area);
  }

  if (scannerMode.value === 'sarana_prasarana') {
    return [getSaranaPrasaranaAreaLabel(entry.value.form.selected_area)];
  }

  if (scannerMode.value === 'site_visit_hse') {
    return [getSiteVisitHseAreaLabel(entry.value.form.selected_area)];
  }

  if (scannerMode.value === 'patroli_security') {
    return getPatroliSecurityBarcodeAliases(entry.value.form.selected_area);
  }

  if (scannerMode.value === 'site_visit_maintenance') {
    return [getMaintenanceDailyAreaLabel(maintenanceScanTargetKey.value)];
  }

  if (scannerMode.value === 'warehouse_sanitation') {
    return warehouseAreaOptions
      .filter((area) => (entry.value.form.selected_areas || []).includes(area.id))
      .map((area) => area.name);
  }

  return [];
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

function canvasToJpegFile(canvas, fileName) {
  return new Promise((resolve, reject) => {
    canvas.toBlob((blob) => {
      if (!blob) {
        reject(new Error('Foto gagal diproses.'));
        return;
      }

      resolve(new File([blob], fileName, { type: 'image/jpeg' }));
    }, 'image/jpeg', 0.9);
  });
}

function formatPatroliSecurityOverlayTime(date = new Date()) {
  return new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  }).format(date).replace('.', ':');
}

function formatPatroliSecurityOverlayDay(date = new Date()) {
  const formatted = new Intl.DateTimeFormat('id-ID', { weekday: 'long' }).format(date);
  return formatted ? formatted.charAt(0).toUpperCase() + formatted.slice(1) : '-';
}

function formatPatroliSecurityOverlayDate(date = new Date()) {
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = String(date.getFullYear());

  return `${day}-${month}-${year}`;
}

function getPatroliSecurityPersonnelName() {
  const aliasName = String(currentUser.value?.alias_name || '').trim();
  if (aliasName) {
    return aliasName;
  }

  return String(currentUser.value?.name || 'Personil Security').trim() || 'Personil Security';
}

function drawPatroliSecurityDivider(context, x, y, width, dashWidth, gapWidth) {
  context.save();
  context.strokeStyle = 'rgba(255, 255, 255, 0.9)';
  context.lineWidth = Math.max(2, dashWidth * 0.18);
  context.setLineDash([dashWidth, gapWidth]);
  context.beginPath();
  context.moveTo(x, y);
  context.lineTo(x + width, y);
  context.stroke();
  context.restore();
}

function drawPatroliSecurityMapPin(context, x, y, size) {
  context.save();
  context.fillStyle = '#ffffff';
  context.beginPath();
  context.arc(x, y - size * 0.1, size * 0.32, Math.PI, 0);
  context.lineTo(x + size * 0.24, y + size * 0.08);
  context.quadraticCurveTo(x, y + size * 0.62, x - size * 0.24, y + size * 0.08);
  context.closePath();
  context.fill();

  context.fillStyle = 'rgba(52, 92, 191, 0.85)';
  context.beginPath();
  context.arc(x, y - size * 0.12, size * 0.11, 0, Math.PI * 2);
  context.fill();
  context.restore();
}

function drawPatroliSecurityPersonIcon(context, x, y, size) {
  context.save();
  context.fillStyle = '#ffffff';
  context.beginPath();
  context.arc(x, y - size * 0.32, size * 0.2, 0, Math.PI * 2);
  context.fill();
  context.beginPath();
  context.arc(x, y + size * 0.18, size * 0.34, Math.PI, 0);
  context.lineTo(x + size * 0.34, y + size * 0.5);
  context.lineTo(x - size * 0.34, y + size * 0.5);
  context.closePath();
  context.fill();
  context.restore();
}

function drawPatroliSecurityShieldIcon(context, x, y, size) {
  context.save();
  context.strokeStyle = '#cfd5df';
  context.lineWidth = Math.max(2, size * 0.09);
  context.beginPath();
  context.moveTo(x, y - size * 0.48);
  context.lineTo(x + size * 0.34, y - size * 0.24);
  context.lineTo(x + size * 0.24, y + size * 0.24);
  context.quadraticCurveTo(x, y + size * 0.54, x - size * 0.24, y + size * 0.24);
  context.lineTo(x - size * 0.34, y - size * 0.24);
  context.closePath();
  context.stroke();

  context.beginPath();
  context.moveTo(x - size * 0.12, y + size * 0.02);
  context.lineTo(x - size * 0.01, y + size * 0.14);
  context.lineTo(x + size * 0.17, y - size * 0.1);
  context.stroke();
  context.restore();
}

function drawWrappedPatroliSecurityText(context, textLines, x, startY, maxWidth, lineHeight) {
  let cursorY = startY;

  textLines.forEach((line) => {
    const words = String(line || '').split(/\s+/).filter(Boolean);
    let currentLine = '';

    words.forEach((word) => {
      const candidate = currentLine ? `${currentLine} ${word}` : word;
      if (context.measureText(candidate).width <= maxWidth || currentLine === '') {
        currentLine = candidate;
        return;
      }

      context.fillText(currentLine, x, cursorY);
      cursorY += lineHeight;
      currentLine = word;
    });

    if (currentLine) {
      context.fillText(currentLine, x, cursorY);
      cursorY += lineHeight;
    }
  });

  return cursorY;
}

function applyPatroliSecurityPhotoOverlay(canvas, capturedAt = new Date()) {
  const context = canvas.getContext('2d');
  if (!context) {
    throw new Error('Overlay foto gagal diproses.');
  }

  const width = canvas.width;
  const height = canvas.height;
  const cardWidth = Math.max(180, Math.min(270, Math.round(width * 0.29)));
  const scale = cardWidth / 230;
  const cardHeight = Math.round(214 * scale);
  const cardX = Math.round(18 * scale);
  const cardY = height - cardHeight - Math.round(18 * scale);
  const headerHeight = Math.round(34 * scale);
  const sidePadding = Math.round(10 * scale);
  const dividerWidth = cardWidth - sidePadding * 2;
  const timeText = formatPatroliSecurityOverlayTime(capturedAt);
  const dayText = formatPatroliSecurityOverlayDay(capturedAt);
  const dateText = formatPatroliSecurityOverlayDate(capturedAt);
  const personnelText = `Personil: ${getPatroliSecurityPersonnelName()}`;
  const verifiedText = 'Diverifikasi oleh Marki';

  context.save();

  context.fillStyle = 'rgba(19, 26, 38, 0.74)';
  context.fillRect(cardX, cardY, cardWidth, cardHeight);

  context.strokeStyle = 'rgba(72, 111, 212, 0.95)';
  context.lineWidth = Math.max(2, scale * 1.4);
  context.strokeRect(cardX, cardY, cardWidth, cardHeight);

  context.fillStyle = '#2f5fc5';
  context.fillRect(cardX, cardY, cardWidth, headerHeight);
  context.fillStyle = '#ffffff';
  context.font = `700 ${Math.round(14 * scale)}px Arial`;
  context.textBaseline = 'middle';
  context.fillText('SECURITY GMI', cardX + sidePadding, cardY + headerHeight / 2);

  const timeBaselineY = cardY + headerHeight + Math.round(46 * scale);
  context.fillStyle = '#ffffff';
  context.textBaseline = 'alphabetic';
  context.font = `700 ${Math.round(28 * scale)}px Arial`;
  context.fillText(timeText, cardX + sidePadding, timeBaselineY);

  const timeMetrics = context.measureText(timeText);
  const separatorX = cardX + sidePadding + timeMetrics.width + Math.round(8 * scale);
  context.strokeStyle = 'rgba(255, 255, 255, 0.95)';
  context.lineWidth = Math.max(2, scale * 1.1);
  context.beginPath();
  context.moveTo(separatorX, cardY + headerHeight + Math.round(10 * scale));
  context.lineTo(separatorX, cardY + headerHeight + Math.round(52 * scale));
  context.stroke();

  const dayDateX = separatorX + Math.round(8 * scale);
  context.font = `700 ${Math.round(12 * scale)}px Arial`;
  context.fillText(dayText, dayDateX, cardY + headerHeight + Math.round(24 * scale));
  context.font = `700 ${Math.round(11 * scale)}px Arial`;
  context.fillText(dateText, dayDateX, cardY + headerHeight + Math.round(42 * scale));

  drawPatroliSecurityDivider(
    context,
    cardX + sidePadding,
    cardY + headerHeight + Math.round(64 * scale),
    dividerWidth,
    Math.max(4, Math.round(5 * scale)),
    Math.max(3, Math.round(3 * scale)),
  );

  const mapIconSize = Math.round(12 * scale);
  const mapIconX = cardX + sidePadding + Math.round(5 * scale);
  const addressX = cardX + sidePadding + Math.round(22 * scale);
  let addressY = cardY + headerHeight + Math.round(80 * scale);
  drawPatroliSecurityMapPin(context, mapIconX, addressY - Math.round(6 * scale), mapIconSize);
  context.font = `700 ${Math.round(9.5 * scale)}px Arial`;
  context.fillStyle = '#ffffff';
  addressY = drawWrappedPatroliSecurityText(
    context,
    patroliSecurityOverlayAddressLines,
    addressX,
    addressY,
    cardX + cardWidth - addressX - sidePadding,
    Math.round(12 * scale),
  );

  const personIconSize = Math.round(11 * scale);
  const personRowY = addressY + Math.round(4 * scale);
  drawPatroliSecurityPersonIcon(context, mapIconX, personRowY - Math.round(2 * scale), personIconSize);
  context.font = `700 ${Math.round(9.5 * scale)}px Arial`;
  context.fillText(personnelText, addressX, personRowY + Math.round(3 * scale));

  drawPatroliSecurityDivider(
    context,
    cardX + sidePadding,
    cardY + cardHeight - Math.round(26 * scale),
    dividerWidth,
    Math.max(4, Math.round(5 * scale)),
    Math.max(3, Math.round(3 * scale)),
  );

  const shieldIconSize = Math.round(10 * scale);
  const verifiedY = cardY + cardHeight - Math.round(10 * scale);
  drawPatroliSecurityShieldIcon(context, mapIconX, verifiedY - Math.round(2 * scale), shieldIconSize);
  context.fillStyle = '#cfd5df';
  context.font = `700 ${Math.round(8.5 * scale)}px Arial`;
  context.fillText(verifiedText, addressX, verifiedY + Math.round(2 * scale));

  context.restore();
}

async function capturePhoto() {
  if (!entry.value || !photoVideoRef.value) {
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

  try {
    if (photoCaptureMode.value === 'waste_transport') {
      if (!isWasteTransport.value || !photoCaptureDay.value) {
        return;
      }

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
      return;
    }

    if (photoCaptureMode.value === 'patroli_security') {
      if (!isPatroliSecurity.value || !patroliSecurityTargetKey.value) {
        return;
      }

      applyPatroliSecurityPhotoOverlay(canvas, new Date());

      const selectedAreaLabel = getPatroliSecurityAreaLabel(entry.value.form.selected_area)
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
      const file = await canvasToJpegFile(
        canvas,
        `patroli-security-${selectedAreaLabel || 'area'}-${Date.now()}.jpg`
      );

      await uploadPatroliSecurityPhoto(file);
      await closePhotoModal();
    }
  } catch (error) {
    photoError.value = error?.message || 'Foto gagal diproses.';
  }
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
    const viewportWidth = typeof window !== 'undefined' ? window.innerWidth : 1280;
    const viewportHeight = typeof window !== 'undefined' ? window.innerHeight : 720;
    const qrboxWidth = Math.min(Math.max(Math.floor(viewportWidth * 0.82), 320), 460);
    const qrboxHeight = Math.min(Math.max(Math.floor(viewportHeight * 0.34), 180), 280);

    await html5QrcodeInstance.start(
      preferredCamera.id,
      {
        fps: 10,
        qrbox: { width: qrboxWidth, height: qrboxHeight },
        aspectRatio: 1.777778,
      },
      async (decodedText) => {
        if (scannerFinishing) {
          return;
        }

        scannerFinishing = true;

        const expectedBarcodeValues = getExpectedBarcodeValuesForCurrentScannerMode();
        const shouldValidateArea = expectedBarcodeValues.length > 0;
        if (shouldValidateArea && !isBarcodeTextMatchingExpected(decodedText, expectedBarcodeValues)) {
          scannerError.value = `Area QRCode tidak sesuai. Harus sesuai area aktif: ${expectedBarcodeValues[0]}.`;
          window.alert(`QRCode salah area.\nArea aktif: ${expectedBarcodeValues[0]}\nQRCode terbaca: ${decodedText}`);
          scannerFinishing = false;
          return;
        }

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

          if (scannerMode.value === 'sarana_prasarana' && nextPendingSaranaPrasaranaDay.value) {
            const day = nextPendingSaranaPrasaranaDay.value.day;
            const dayScans = entry.value.form.area_scans_by_day?.[day] || {};

            entry.value.form.area_scans_by_day = {
              ...(entry.value.form.area_scans_by_day || {}),
              [day]: {
                ...dayScans,
                [entry.value.form.selected_area]: {
                  barcode: decodedText,
                  scanned_at: formatDateTimeDisplay(new Date()),
                },
              },
            };
          }

          if (scannerMode.value === 'warehouse_sanitation') {
            entry.value.form.barcode = decodedText;
            entry.value.form.scan_date = formatShortDateDisplay(new Date());
          }

          if (scannerMode.value === 'site_visit_maintenance') {
            entry.value.form.area_barcodes = {
              ...(entry.value.form.area_barcodes || {}),
              [maintenanceScanTargetKey.value]: decodedText,
            };
            entry.value.form.area_scan_dates = {
              ...(entry.value.form.area_scan_dates || {}),
              [maintenanceScanTargetKey.value]: formatShortDateDisplay(new Date()),
            };
          }

          if (scannerMode.value === 'site_visit_hse') {
            entry.value.form.area_barcodes = {
              ...(entry.value.form.area_barcodes || {}),
              [siteVisitHseTargetKey.value]: decodedText,
            };
            entry.value.form.area_scan_dates = {
              ...(entry.value.form.area_scan_dates || {}),
              [siteVisitHseTargetKey.value]: formatShortDateDisplay(new Date()),
            };
          }

          if (scannerMode.value === 'patroli_security') {
            entry.value.form.area_barcodes = {
              ...(entry.value.form.area_barcodes || {}),
              [patroliSecurityTargetKey.value]: decodedText,
            };
            entry.value.form.area_scan_dates = {
              ...(entry.value.form.area_scan_dates || {}),
              [patroliSecurityTargetKey.value]: formatShortDateDisplay(new Date()),
            };
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

  return message || 'Scanner QRCode gagal dijalankan.';
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
  photoCaptureMode.value = '';
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
  saveError.value = '';
  saveState.value = 'idle';
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

watch(
  entry,
  () => {
    syncSaveStateWithEntry();
  },
  { deep: true }
);
</script>
