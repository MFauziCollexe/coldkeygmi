<template>
  <AppLayout>
    <div class="p-6">
      <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 class="text-2xl font-bold">New Checklist</h2>
          <Link href="/gmiic/checklist" class="text-sm text-indigo-400 hover:underline">&lt; Back to List</Link>
          <div v-if="selectedChecklist" class="mt-1 text-xs" :class="saveStateClass">{{ saveStateLabel }}</div>
        </div>
        <div class="w-full sm:w-[360px]">
          <SearchableSelect v-model="selectedChecklist" :options="availableChecklistOptions" option-value="id" option-label="name" placeholder="Pilih Checklist..." empty-label="Pilih Checklist" input-class="w-full bg-slate-800 text-sm border-slate-700 rounded" button-class="border-0 border-l !border-slate-700 rounded-r !bg-slate-800 text-slate-100" />
        </div>
      </div>

      <div v-if="!availableChecklistOptions.length" class="rounded bg-slate-800 p-4 text-sm text-amber-300">Belum ada template checklist yang tersedia untuk departement Anda.</div>
      <div v-else-if="selectedChecklist && !canUseSelectedChecklist" class="rounded bg-slate-800 p-4 text-sm text-amber-300">Template checklist ini tidak tersedia untuk departement Anda.</div>
      <div v-else-if="!selectedChecklist" class="rounded bg-slate-800 p-8 text-center text-slate-400">Pilih checklist terlebih dahulu untuk membuka form.</div>

      <component :is="currentTemplateComp" v-if="entry && currentTemplateComp" v-bind="currentTemplateProps" />

      <!-- QR Scanner Modal -->
      <div v-if="scannerModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" @click.self="closeScannerModal">
        <div class="w-full max-w-xl rounded-xl border border-slate-700 bg-slate-900 p-4 shadow-2xl">
          <div class="mb-4 flex items-center justify-between gap-4">
            <div>
              <h3 class="text-lg font-semibold text-white">Scan QRCode</h3>
              <p class="text-sm text-slate-400">Gunakan kamera HP atau laptop untuk membaca QRCode lokasi.</p>
            </div>
            <button type="button" class="rounded bg-slate-700 px-3 py-2 text-sm text-white hover:bg-slate-600" @click="closeScannerModal">Close</button>
          </div>
          <div v-if="scannerError" class="mb-3 rounded border border-rose-700 bg-rose-950/40 px-3 py-2 text-sm text-rose-200">{{ scannerError }}</div>
          <div v-if="scannerLoading" class="mb-3 text-sm text-slate-400">Menyiapkan kamera...</div>
          <div id="barcode-scanner-region" class="min-h-[320px] overflow-hidden rounded-lg border border-slate-700 bg-black"></div>
        </div>
      </div>

      <!-- Photo Modal -->
      <div v-if="photoModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" @click.self="closePhotoModal">
        <div class="w-full max-w-xl rounded-xl border border-slate-700 bg-slate-900 p-4 shadow-2xl">
          <div class="mb-4 flex items-center justify-between gap-4">
            <div>
              <h3 class="text-lg font-semibold text-white">{{ photoModalTitle }}</h3>
              <p class="text-sm text-slate-400">{{ photoModalDescription }}</p>
            </div>
            <button type="button" class="rounded bg-slate-700 px-3 py-2 text-sm text-white hover:bg-slate-600" @click="closePhotoModal">Close</button>
          </div>
          <div v-if="photoError" class="mb-3 rounded border border-rose-700 bg-rose-950/40 px-3 py-2 text-sm text-rose-200">{{ photoError }}</div>
          <div v-if="photoLoading" class="mb-3 text-sm text-slate-400">Menyiapkan kamera...</div>
          <div class="overflow-hidden rounded-lg border border-slate-700 bg-black">
            <video ref="photoVideoRef" autoplay playsinline muted class="min-h-[320px] w-full bg-black object-cover"></video>
          </div>
          <div class="mt-4 flex justify-end gap-3">
            <button type="button" class="rounded bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-600" @click="closePhotoModal">Batal</button>
            <button type="button" :disabled="photoLoading" class="rounded bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-500 disabled:cursor-not-allowed disabled:bg-slate-600" @click="capturePhoto">{{ photoCaptureButtonLabel }}</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import {
  checklistOptions, createKotakP3KEntry, createNonWarehouseSanitationEntry, createFireSafetyEntry,
  createWasteTransportEntry, createWarehouseSanitationEntry, createPersonalHygieneEntry,
  createSaranaPrasaranaEntry, createPatroliSecurityEntry, createSiteVisitHseEntry,
  createSiteVisitMaintenanceEntry, createGensetRunningEntry, createRunningGensetEntry,
  createKompresorHarianEntry, createChargerBateraiEntry, createChecklistBateraiEntry,
  createCleaningOBEntry, formatMonthYearDisplay, formatDateTimeDisplay, formatDateDisplay,
  formatShortDateDisplay, getKotakP3KMonthLabel, getLocationBarcodeAliases,
  getPatroliSecurityBarcodeAliases, getSanitationAreaBarcodeAliases, kotakP3KMonths,
} from './checklistConfig'
import { supportedTemplatesList, getTemplateComponent } from './composables/useChecklistRegistry'
import { useScanner } from './composables/useScanner'
import { usePhotoCapture } from './composables/usePhotoCapture'
import { usePersistence, hydrateChecklistEntry } from './composables/usePersistence'
import { useKotakP3K } from './composables/useKotakP3K'
import { useFireSafety } from './composables/useFireSafety'
import { useSanitation } from './composables/useSanitation'
import { useWasteTransport } from './composables/useWasteTransport'
import { useWarehouseSanitation } from './composables/useWarehouseSanitation'
import { usePersonalHygiene } from './composables/usePersonalHygiene'
import { useSaranaPrasarana } from './composables/useSaranaPrasarana'
import { usePatroliSecurity } from './composables/usePatroliSecurity'
import { useSiteVisitHse } from './composables/useSiteVisitHse'
import { useSiteVisitMaintenance } from './composables/useSiteVisitMaintenance'
import { useGensetRunning } from './composables/useGensetRunning'
import { useRunningGenset } from './composables/useRunningGenset'
import { useKompresorHarian } from './composables/useKompresorHarian'
import { useChargerBaterai } from './composables/useChargerBaterai'
import { useChecklistBaterai } from './composables/useChecklistBaterai'
import { useCleaningOB } from './composables/useCleaningOB'

const props = defineProps({
  selectedTemplate: { type: String, default: '' },
  entryId: { type: String, default: '' },
  savedEntry: { type: Object, default: null },
  existingEntries: { type: Array, default: () => [] },
  holidayDates: { type: Array, default: () => [] },
  leaveDatesByNik: { type: Object, default: () => ({}) },
  employees: { type: Array, default: () => [] },
  checklistAbilities: { type: Object, default: () => ({}) },
  checklistSettings: { type: Object, default: () => ({}) },
  checklistTemplatePermissions: { type: Object, default: () => ({}) },
})

const page = usePage()
const selectedChecklist = ref(props.selectedTemplate || '')
const entry = ref(null)
const locationMenuOpen = ref(false)

const currentUser = computed(() => page.props.auth?.user || null)
const checklistSettings = computed(() => props.checklistSettings || page.props.checklistSettings || {})
const checklistTemplatePermissions = computed(() => props.checklistTemplatePermissions || page.props.checklistTemplatePermissions || {})
const availableChecklistOptions = computed(() => checklistOptions.filter((option) => Boolean(checklistTemplatePermissions.value?.[option.id]?.view)))
const currentChecklistTemplatePermissions = computed(() => checklistTemplatePermissions.value?.[selectedChecklist.value] || {})
const canUseSelectedChecklist = computed(() => supportedTemplatesList.includes(selectedChecklist.value) && Boolean(currentChecklistTemplatePermissions.value.view))
const canApproveCurrentTemplate = computed(() => Boolean(currentChecklistTemplatePermissions.value.approve))
const showQrScanner = computed(() => !Boolean(checklistSettings.value.qr_bypass_enabled))
const checklistAbilities = computed(() => props.checklistAbilities || page.props.checklistAbilities || {})
const canApproveKotakP3KHse = computed(() => Boolean(checklistAbilities.value.kotak_p3k_hse_approve))
const canApproveWarehouseFinal = computed(() => Boolean(checklistAbilities.value.warehouse_final_approve))

const supportedTemplates = supportedTemplatesList

// ─── Composables ────────────────────────────────────────────
const persistence = usePersistence({ props, selectedChecklist, entry, supportedTemplates })
const { knownChecklistEntries, saveState, saveError, saveStateLabel, saveStateClass, cloneChecklistEntry, buildEntrySignature, upsertKnownChecklistEntry, upsertKnownChecklistEntries, syncCurrentEntryUrl, persistChecklistEntry, persistChecklistEntries, syncSaveStateWithEntry, buildDigitalSignature } = persistence

const kotakP3K = useKotakP3K(entry, { showQrScanner })
const fireSafety = useFireSafety(entry, { showQrScanner })
const sanitation = useSanitation(entry, { currentUser, canApproveCurrentTemplate, showQrScanner })
const wasteTransport = useWasteTransport(entry)
const warehouse = useWarehouseSanitation(entry, { canApproveWarehouseFinal, showQrScanner })
const personalHygiene = usePersonalHygiene(entry, { props, currentUser })
const saranaPrasarana = useSaranaPrasarana(entry, { showQrScanner })
const patroliSecurity = usePatroliSecurity(entry, { currentUser, showQrScanner })
const siteVisitHse = useSiteVisitHse(entry, { currentUser, showQrScanner })
const siteVisitMaintenance = useSiteVisitMaintenance(entry, { showQrScanner })
const gensetRunning = useGensetRunning(entry, { currentUser, showQrScanner })
const runningGenset = useRunningGenset(entry, { currentUser, showQrScanner })
const kompresorHarian = useKompresorHarian(entry)
const chargerBaterai = useChargerBaterai(entry)
const checklistBaterai = useChecklistBaterai(entry)
const cleaningOB = useCleaningOB(entry, { currentUser, showQrScanner })

const scanner = useScanner({
  entry, showQrScanner,
  isKotakP3K: kotakP3K.isKotakP3K,
  isGensetRunning: gensetRunning.isGensetRunning,
  isRunningGenset: runningGenset.isRunningGenset,
  isSanitation: sanitation.isSanitation,
  isSaranaPrasarana: saranaPrasarana.isSaranaPrasarana,
  isFireSafety: fireSafety.isFireSafety,
  isWarehouseSanitation: warehouse.isWarehouseSanitation,
  isSiteVisitMaintenance: siteVisitMaintenance.isSiteVisitMaintenance,
  isSiteVisitHse: siteVisitHse.isSiteVisitHse,
  isPatroliSecurity: patroliSecurity.isPatroliSecurity,
  isCleaningOB: cleaningOB.isCleaningOB,
  kotakP3KMonthValidation: kotakP3K.kotakP3KMonthValidation,
  gensetRunningValidation: gensetRunning.gensetRunningValidation,
  runningGensetValidation: runningGenset.runningGensetValidation,
  canScanSaranaPrasaranaArea: saranaPrasarana.canScanSaranaPrasaranaArea,
  canScanFireSafety: fireSafety.canScanFireSafety,
  canScanWarehouseBarcode: warehouse.canScanWarehouseBarcode,
  canScanMaintenance: siteVisitMaintenance.canScanMaintenance,
  canScanSiteVisitHse: siteVisitHse.canScanSiteVisitHse,
  canScanPatroliSecurity: patroliSecurity.canScanPatroliSecurity,
  canScanCleaningOB: cleaningOB.canScanCleaningOB,
  nextPendingSanitationDay: sanitation.nextPendingSanitationDay,
  nextPendingSaranaPrasaranaDay: saranaPrasarana.nextPendingSaranaPrasaranaDay,
  activeKotakP3KMonth: kotakP3K.activeKotakP3KMonth,
  activeFireSafetyMonth: fireSafety.activeFireSafetyMonth,
  maintenanceScanTargetKey: siteVisitMaintenance.maintenanceScanTargetKey,
  siteVisitHseTargetKey: siteVisitHse.siteVisitHseTargetKey,
  patroliSecurityTargetKey: patroliSecurity.patroliSecurityTargetKey,
  cleaningOBTargetKey: cleaningOB.cleaningOBTargetKey,
  formatShortDateDisplay, formatDateTimeDisplay,
  getLocationBarcodeAliases,
  getPatroliSecurityBarcodeAliases,
  getFireSafetyLocationLabel: fireSafety.getFireSafetyLocationLabel,
  getSanitationAreaBarcodeAliases,
  getSaranaPrasaranaAreaLabel: saranaPrasarana.getSaranaPrasaranaAreaLabel,
  getSiteVisitHseAreaLabel: siteVisitHse.getSiteVisitHseAreaLabel,
  getMaintenanceDailyAreaLabel: siteVisitMaintenance.getMaintenanceDailyAreaLabel,
  warehouseAreaOptions: warehouse.warehouseAreaOptions,
  persistCurrentFireSafetyState: fireSafety.persistCurrentFireSafetyState,
})

const photo = usePhotoCapture({
  entry,
  isWasteTransport: wasteTransport.isWasteTransport,
  isPatroliSecurity: patroliSecurity.isPatroliSecurity,
  isSiteVisitMaintenance: siteVisitMaintenance.isSiteVisitMaintenance,
  patroliSecurityTargetKey: patroliSecurity.patroliSecurityTargetKey,
  maintenanceScanTargetKey: siteVisitMaintenance.maintenanceScanTargetKey,
  wasteTransportRows: wasteTransport.wasteTransportRows,
  currentPatroliSecurityPhotos: patroliSecurity.currentPatroliSecurityPhotos,
  getPatroliSecurityAreaLabel: patroliSecurity.getPatroliSecurityAreaLabel,
  patroliSecurityOverlayAddressLines: patroliSecurity.patroliSecurityOverlayAddressLines,
  currentUser,
})

const { scannerModalOpen, scannerLoading, scannerError, closeScannerModal } = scanner
const { photoModalOpen, photoLoading, photoError, photoVideoRef, photoModalTitle, photoModalDescription, photoCaptureButtonLabel, capturePhoto, closePhotoModal } = photo

entry.value = createInitialEntry()

// ─── Dynamic Template Component ─────────────────────────────
const currentTemplateComp = computed(() => entry.value ? getTemplateComponent(entry.value.template_id) : null)

const currentTemplateProps = computed(() => {
  if (!entry.value) return {}
  const tid = entry.value.template_id

  if (tid === 'kotak_p3k') return {
    entry: entry.value, canScanBarcode: kotakP3K.kotakP3KMonthValidation.value.canScan,
    canApproveEntry: canApproveEntry.value, locationMenuOpen: locationMenuOpen.value,
    locationOptions: kotakP3K.locationOptions, getLocationLabel: kotakP3K.getLocationLabel,
    months: kotakP3K.kotakP3KMonths, activeMonth: kotakP3K.activeKotakP3KMonth.value,
    activeMonthLabel: kotakP3K.getKotakP3KMonthLabel(kotakP3K.activeKotakP3KMonth.value),
    currentBarcode: kotakP3K.currentKotakP3KBarcode.value, monthNote: kotakP3K.kotakP3KMonthNote.value,
    isActiveMonthApproved: kotakP3K.isActiveKotakP3KMonthApproved.value,
    isActiveMonthLocked: kotakP3K.isActiveKotakP3KMonthLocked.value,
    activeMonthStatusLabel: kotakP3K.kotakP3KActiveMonthStatusLabel.value,
    approvalButtonLabel: kotakP3K.kotakP3KApprovalButtonLabel.value,
    showQrScanner: showQrScanner.value,
    onApprove: approveChecklist, onScanBarcode: scanner.scanBarcode,
    onToggleLocationMenu: () => { locationMenuOpen.value = !locationMenuOpen.value },
    onSelectLocation: (id) => { if (entry.value) entry.value.form.location = id; locationMenuOpen.value = false },
    onSetActiveMonth: kotakP3K.setKotakP3KActiveMonth,
    onCycleMonthAnswer: kotakP3K.cycleKotakP3KMonthAnswer,
    onUpdateMonthNote: kotakP3K.updateKotakP3KMonthNote,
  }

  if (tid === 'apar_smoke_detector_fire_alarm') return {
    entry: entry.value, cardOptions: fireSafety.fireSafetyCardOptions,
    cardTitle: fireSafety.fireSafetyCardTitle.value,
    locationOptions: fireSafety.fireSafetyLocationOptions.value,
    months: kotakP3KMonths,
    activeMonth: fireSafety.activeFireSafetyMonth.value,
    activeMonthLabel: getKotakP3KMonthLabel(fireSafety.activeFireSafetyMonth.value),
    monthNote: fireSafety.fireSafetyMonthNote.value,
    currentBarcode: fireSafety.currentFireSafetyBarcode.value,
    canScanBarcode: fireSafety.canScanFireSafety.value,
    canApproveEntry: canApproveEntry.value,
    isActiveMonthApproved: fireSafety.isActiveFireSafetyMonthApproved.value,
    showQrScanner: showQrScanner.value,
    onApprove: approveChecklist, onScanBarcode: scanner.scanFireSafetyBarcode,
    onUpdateCardType: fireSafety.updateFireSafetyCardType,
    onUpdateLocation: fireSafety.updateFireSafetyLocation,
    onSetActiveMonth: fireSafety.setFireSafetyActiveMonth,
    onCycleMonthAnswer: fireSafety.cycleFireSafetyMonthAnswer,
    onUpdateMonthNote: fireSafety.updateFireSafetyMonthNote,
  }

  if (tid === 'non_warehouse_sanitation') return {
    entry: entry.value, rows: sanitation.currentSanitationRows.value,
    approvedDays: sanitation.sanitationApprovedDays.value,
    currentAreaScan: sanitation.currentSanitationScan.value,
    nextPendingDay: sanitation.nextPendingSanitationDay.value,
    canScanArea: sanitation.canScanSanitationArea.value,
    canApproveEntry: canApproveEntry.value,
    approvalButtonLabel: sanitation.sanitationApprovalButtonLabel.value,
    note: sanitation.sanitationNote.value, noteLabel: sanitation.sanitationNoteLabel.value,
    canEditNote: sanitation.canEditSanitationNote.value,
    sanitationDays: sanitation.sanitationDays.value,
    sanitationAreaOptions: sanitation.sanitationAreaOptions,
    showQrScanner: showQrScanner.value,
    onApprove: approveChecklist, onScanArea: scanner.scanSanitationArea,
    onToggleDay: sanitation.toggleSanitationDay,
    onUpdateNote: (v) => sanitation.updateSanitationNote(v, entry),
  }

  if (tid === 'pengangkutan_sampah_pt_sier') return {
    entry: entry.value, rows: wasteTransport.wasteTransportRows.value,
    periodLabel: formatMonthYearDisplay(entry.value.form.period),
    approvedDays: wasteTransport.wasteTransportApprovedDays.value,
    canApproveEntry: canApproveEntry.value,
    onApprove: approveChecklist, onUpdateRow: wasteTransport.updateWasteTransportRow,
    onOpenCamera: photo.openWasteTransportCamera,
  }

  if (tid === 'warehouse_sanitation_1') return {
    entry: entry.value, warehouseAreaOptions: warehouse.warehouseAreaOptions,
    canApproveEntry: canApproveEntry.value,
    currentBarcode: warehouse.currentWarehouseBarcode.value,
    scanDate: warehouse.currentWarehouseScanDate.value,
    canScanBarcode: warehouse.canScanWarehouseBarcode.value,
    approvalButtonLabel: warehouse.warehouseApprovalButtonLabel.value,
    showQrScanner: showQrScanner.value,
    onApprove: approveChecklist, onScanBarcode: scanner.scanWarehouseBarcode,
    onUpdateFrequency: warehouse.updateWarehouseFrequency,
    onToggleArea: warehouse.toggleWarehouseArea,
    onUpdateGeneralField: warehouse.updateWarehouseGeneralField,
    onSetAreaRowStatus: warehouse.setWarehouseAreaRowStatus,
    onSetAreaRowNote: warehouse.setWarehouseAreaRowNote,
    onSetIceControlStatus: warehouse.setWarehouseIceControlStatus,
    onSetIceControlNote: warehouse.setWarehouseIceControlNote,
    onSetCleaningMaterialField: warehouse.setWarehouseCleaningMaterialField,
  }

  if (tid === 'personal_hygiene_karyawan') return {
    entry: entry.value, rows: personalHygiene.personalHygieneRows.value,
    days: personalHygiene.personalHygieneDays.value,
    generatedEmployees: personalHygiene.generatedPersonalHygieneEmployees.value,
    employees: props.employees, canApproveEntry: canApproveEntry.value,
    onApprove: approveChecklist, onUpdateGeneralField: personalHygiene.updatePersonalHygieneField,
    onToggleDay: personalHygiene.togglePersonalHygieneDay,
    onToggleGeneratedDay: personalHygiene.toggleGeneratedPersonalHygieneDay,
    onGenerateFullMonth: () => personalHygiene.generatePersonalHygieneFullMonth({ persistChecklistEntries, knownChecklistEntries: knownChecklistEntries.value }),
  }

  if (tid === 'sarana_dan_prasarana') return {
    entry: entry.value, areaOptions: saranaPrasarana.saranaPrasaranaAreaOptions,
    currentSection: saranaPrasarana.currentSaranaPrasaranaSection.value,
    days: saranaPrasarana.saranaPrasaranaDays.value,
    approvedDays: saranaPrasarana.saranaPrasaranaApprovedDays.value,
    currentAreaScan: saranaPrasarana.currentSaranaPrasaranaScan.value,
    nextPendingDay: saranaPrasarana.nextPendingSaranaPrasaranaDay.value,
    canScanArea: saranaPrasarana.canScanSaranaPrasaranaArea.value,
    canApproveEntry: canApproveEntry.value,
    showQrScanner: showQrScanner.value,
    onApprove: approveChecklist, onScanArea: scanner.scanSaranaPrasaranaArea,
    onUpdatePeriod: saranaPrasarana.updateSaranaPrasaranaPeriod,
    onUpdateArea: saranaPrasarana.updateSaranaPrasaranaArea,
    onCycleDay: saranaPrasarana.cycleSaranaPrasaranaDay,
  }

  if (tid === 'patroli_security') return {
    entry: entry.value, areaOptions: patroliSecurity.patroliSecurityAreaOptions,
    currentSection: patroliSecurity.currentPatroliSecuritySection.value,
    approvedAreas: patroliSecurity.patroliSecurityApprovedAreas.value,
    isAreaApproved: patroliSecurity.patroliSecurityApprovedAreas.value.includes(entry.value.form.selected_area),
    note: patroliSecurity.patroliSecurityNote.value,
    noteLabel: patroliSecurity.patroliSecurityNoteLabel.value,
    currentBarcode: patroliSecurity.currentPatroliSecurityBarcode.value,
    canScanBarcode: patroliSecurity.canScanPatroliSecurity.value,
    canApproveEntry: canApproveEntry.value,
    currentPhotos: patroliSecurity.currentPatroliSecurityPhotos.value,
    photoUploading: photo.patroliSecurityPhotoUploading.value,
    photoError: patroliSecurity.patroliSecurityPhotoError.value,
    showQrScanner: showQrScanner.value,
    onApprove: approveChecklist, onScanBarcode: scanner.scanPatroliSecurityBarcode,
    onUpdateDate: patroliSecurity.updatePatroliSecurityDate,
    onUpdateArea: patroliSecurity.updatePatroliSecurityArea,
    onCycleRowStatus: patroliSecurity.cyclePatroliSecurityRowStatus,
    onUpdateNote: patroliSecurity.updatePatroliSecurityNote,
    onOpenCamera: photo.openPatroliSecurityCamera,
    onRemovePhoto: photo.removePatroliSecurityPhoto,
  }

  if (tid === 'jadwal_cleaning_ob') return {
    entry: entry.value, shiftOptions: cleaningOB.cleaningOBShiftOptions,
    currentSections: cleaningOB.currentCleaningOBSections.value,
    approvedAreas: cleaningOB.cleaningOBApprovedAreas.value,
    isAreaApproved: cleaningOB.cleaningOBApprovedAreas.value.includes(entry.value.form.selected_shift),
    note: cleaningOB.cleaningOBNote.value,
    noteLabel: cleaningOB.cleaningOBNoteLabel.value,
    currentBarcode: cleaningOB.currentCleaningOBBarcode.value,
    canScanBarcode: cleaningOB.canScanCleaningOB.value,
    canApproveEntry: canApproveEntry.value,
    showQrScanner: showQrScanner.value,
    onApprove: approveChecklist, onScanBarcode: scanner.scanCleaningOBBarcode,
    onUpdateDate: cleaningOB.updateCleaningOBDate,
    onUpdateShift: cleaningOB.updateCleaningOBShift,
    onCycleRowStatus: cleaningOB.cycleCleaningOBRowStatus,
    onUpdateNote: cleaningOB.updateCleaningOBNote,
  }

  if (tid === 'site_visit_hse') return {
    entry: entry.value, areaOptions: siteVisitHse.siteVisitHseAreaOptions,
    currentSection: siteVisitHse.currentSiteVisitHseSection.value,
    approvedAreas: siteVisitHse.siteVisitHseApprovedAreas.value,
    isAreaApproved: siteVisitHse.siteVisitHseApprovedAreas.value.includes(entry.value.form.selected_area),
    note: siteVisitHse.siteVisitHseNote.value, noteLabel: siteVisitHse.siteVisitHseNoteLabel.value,
    currentBarcode: siteVisitHse.currentSiteVisitHseBarcode.value,
    canScanBarcode: siteVisitHse.canScanSiteVisitHse.value,
    canApproveEntry: canApproveEntry.value,
    showQrScanner: showQrScanner.value,
    onApprove: approveChecklist, onScanBarcode: scanner.scanSiteVisitHseBarcode,
    onUpdateDate: siteVisitHse.updateSiteVisitHseDate,
    onUpdateArea: siteVisitHse.updateSiteVisitHseArea,
    onCycleRowStatus: siteVisitHse.cycleSiteVisitHseRowStatus,
    onUpdateNote: siteVisitHse.updateSiteVisitHseNote,
  }

  if (tid === 'site_visit_maintenance') return {
    entry: entry.value, typeOptions: siteVisitMaintenance.maintenanceVisitTypeOptions,
    currentTypeMeta: siteVisitMaintenance.maintenanceTypeMeta.value,
    dailyAreaOptions: siteVisitMaintenance.maintenanceDailyAreaOptions,
    sections: siteVisitMaintenance.maintenanceSections.value,
    rows: siteVisitMaintenance.maintenanceRows.value,
    note: siteVisitMaintenance.maintenanceNote.value,
    noteLabel: siteVisitMaintenance.maintenanceNoteLabel.value,
    currentBarcode: siteVisitMaintenance.currentMaintenanceBarcode.value,
    canScanBarcode: siteVisitMaintenance.canScanMaintenance.value,
    canApproveEntry: canApproveEntry.value,
    approvalDisabledReason: maintenanceApprovalDisabledReason.value,
    currentPhotos: siteVisitMaintenance.currentMaintenancePhotos.value,
    photoUploading: photo.maintenancePhotoUploading.value,
    photoError: siteVisitMaintenance.maintenancePhotoError.value,
    showQrScanner: showQrScanner.value,
    onApprove: approveChecklist, onScanBarcode: scanner.scanMaintenanceBarcode,
    onUpdateType: siteVisitMaintenance.updateMaintenanceVisitType,
    onUpdateDate: siteVisitMaintenance.updateMaintenanceVisitDate,
    onUpdatePeriod: siteVisitMaintenance.updateMaintenanceVisitPeriod,
    onUpdateArea: siteVisitMaintenance.updateMaintenanceVisitArea,
    onCycleRowStatus: siteVisitMaintenance.cycleMaintenanceRowStatus,
    onUpdateNote: siteVisitMaintenance.updateMaintenanceNote,
    onOpenCamera: photo.openMaintenanceCamera,
    onRemovePhoto: photo.removeMaintenancePhoto,
  }

  if (tid === 'genset_running') return {
    entry: entry.value, rows: gensetRunning.gensetRunningRows.value,
    note: gensetRunning.gensetRunningNote.value,
    currentBarcode: gensetRunning.currentGensetRunningBarcode.value,
    scanDate: gensetRunning.currentGensetRunningScanDate.value,
    canScanBarcode: gensetRunning.gensetRunningValidation.value.canScan,
    canApproveEntry: canApproveEntry.value,
    isApproved: gensetRunning.isGensetRunningApproved.value,
    statusLabel: gensetRunning.gensetRunningStatusLabel.value,
    showQrScanner: showQrScanner.value,
    onApprove: approveChecklist, onScanBarcode: scanner.scanGensetRunningBarcode,
    onUpdatePeriod: gensetRunning.updateGensetRunningPeriod,
    onCycleRowStatus: gensetRunning.cycleGensetRunningRowStatus,
    onUpdateNote: gensetRunning.updateGensetRunningNote,
  }

  if (tid === 'running_genset') return {
    entry: entry.value, rows: runningGenset.runningGensetRows.value,
    note: runningGenset.runningGensetNote.value,
    currentBarcode: runningGenset.currentRunningGensetBarcode.value,
    scanDate: runningGenset.currentRunningGensetScanDate.value,
    canScanBarcode: runningGenset.runningGensetValidation.value.canScan,
    canApproveEntry: canApproveEntry.value,
    isApproved: runningGenset.isRunningGensetApproved.value,
    showQrScanner: showQrScanner.value,
    onApprove: approveChecklist, onScanBarcode: scanner.scanRunningGensetBarcode,
    onUpdateDate: runningGenset.updateRunningGensetDate,
    onUpdateHourMeter: runningGenset.updateRunningGensetHourMeter,
    onCycleRowStatus: runningGenset.cycleRunningGensetRowStatus,
    onUpdateNote: runningGenset.updateRunningGensetNote,
  }

  if (tid === 'kompresor_harian') return {
    entry: entry.value, rows: kompresorHarian.kompresorHarianRows.value,
    activeRow: kompresorHarian.activeKompresorRow.value,
    activeDay: kompresorHarian.activeKompresorDay.value,
    isActiveDayApproved: kompresorHarian.isActiveKompresorDayApproved.value,
    canApproveEntry: canApproveEntry.value,
    note: kompresorHarian.kompresorHarianNote.value,
    approvedDays: kompresorHarian.kompresorApprovedDays.value,
    onApprove: approveChecklist, onUpdateField: kompresorHarian.updateKompresorHarianField,
    onUpdateCheckHeader: kompresorHarian.updateKompresorCheckHeader,
    onUpdateRowField: kompresorHarian.updateKompresorRowField,
    onCycleRowSymbol: kompresorHarian.cycleKompresorRowSymbol,
    onUpdateNote: kompresorHarian.updateKompresorHarianNote,
    onSetActiveDay: kompresorHarian.setActiveKompresorDay,
  }

  if (tid === 'charger_baterai') return {
    entry: entry.value, rows: chargerBaterai.chargerBateraiRows.value,
    activeRow: chargerBaterai.activeChargerBateraiRow.value,
    activeDay: chargerBaterai.activeChargerBateraiDay.value,
    isActiveDayApproved: chargerBaterai.isActiveChargerBateraiDayApproved.value,
    canApproveEntry: canApproveEntry.value,
    note: chargerBaterai.chargerBateraiNote.value,
    approvedDays: chargerBaterai.chargerBateraiApprovedDays.value,
    onApprove: approveChecklist, onUpdateField: chargerBaterai.updateChargerBateraiField,
    onUpdateRowField: chargerBaterai.updateChargerBateraiRowField,
    onCycleRowSymbol: chargerBaterai.cycleChargerBateraiRowSymbol,
    onUpdateNote: chargerBaterai.updateChargerBateraiNote,
    onSetActiveDay: chargerBaterai.setActiveChargerBateraiDay,
  }

  if (tid === 'checklist_baterai') return {
    entry: entry.value, rows: checklistBaterai.checklistBateraiRows.value,
    activeRow: checklistBaterai.activeChecklistBateraiRow.value,
    activeDay: checklistBaterai.activeChecklistBateraiDay.value,
    isActiveDayApproved: checklistBaterai.isActiveChecklistBateraiDayApproved.value,
    canApproveEntry: canApproveEntry.value,
    note: checklistBaterai.checklistBateraiNote.value,
    approvedDays: checklistBaterai.checklistBateraiApprovedDays.value,
    onApprove: approveChecklist, onUpdateField: checklistBaterai.updateChecklistBateraiField,
    onCycleRowSymbol: checklistBaterai.cycleChecklistBateraiRowSymbol,
    onUpdateNote: checklistBaterai.updateChecklistBateraiNote,
    onSetActiveDay: checklistBaterai.setActiveChecklistBateraiDay,
  }

  return {}
})

// ─── canApproveEntry ────────────────────────────────────────
function isFilled(value) {
  return String(value ?? '').trim() !== ''
}

function isKompresorDailyRowComplete(row) {
  return Boolean(row?.status_mesin)
    && Boolean(row?.visual_bersih)
    && Boolean(row?.visual_kotor)
    && isFilled(row?.tek_suct)
    && isFilled(row?.tek_disch)
    && isFilled(row?.delta_tekanan_oli)
    && isFilled(row?.check_1)
    && isFilled(row?.check_2)
    && isFilled(row?.check_3)
    && isFilled(row?.check_4)
    && Boolean(row?.tambah_grease_motor)
    && isFilled(row?.tambah_oli)
    && isFilled(row?.hours_meter)
}

function hasKompresorDailyNoAnswer(row) {
  return row?.status_mesin === 'no'
    || row?.visual_bersih === 'no'
    || row?.visual_kotor === 'no'
    || row?.tambah_grease_motor === 'no'
}

function isChargerBateraiRowComplete(row) {
  return Boolean(row?.switch_on_off)
    && Boolean(row?.kondisi_fisik)
    && Boolean(row?.kabel_konektor)
    && Boolean(row?.legrand)
    && Boolean(row?.display_charger)
    && Boolean(row?.temuan)
    && isFilled(row?.tindakan)
}

function hasChargerBateraiNoAnswer(row) {
  return row?.switch_on_off === 'no'
    || row?.kondisi_fisik === 'no'
    || row?.kabel_konektor === 'no'
    || row?.legrand === 'no'
    || row?.display_charger === 'no'
    || row?.temuan === 'no'
}

function isChecklistBateraiRowComplete(row) {
  return Boolean(row?.level_elektrolit)
    && Boolean(row?.kabel_konektor)
    && Boolean(row?.cover_pelampung)
    && Boolean(row?.kebersihan_baterai)
    && Boolean(row?.voltage_dc)
}

function hasChecklistBateraiNoAnswer(row) {
  return row?.level_elektrolit === 'no'
    || row?.kabel_konektor === 'no'
    || row?.cover_pelampung === 'no'
    || row?.kebersihan_baterai === 'no'
    || row?.voltage_dc === 'no'
}

const canApproveEntry = computed(() => {
  if (!entry.value) return false
  const tid = entry.value.template_id

  if (tid === 'kotak_p3k') {
    if (!canApproveCurrentTemplate.value) return false
    if (kotakP3K.isActiveKotakP3KMonthApproved.value) return false
    if (kotakP3K.isActiveKotakP3KMonthSubmitted.value) return canApproveKotakP3KHse.value
    return kotakP3K.kotakP3KMonthValidation.value.allAnswersFilled
      && (!kotakP3K.kotakP3KMonthValidation.value.hasNoAnswer || kotakP3K.kotakP3KMonthValidation.value.hasRequiredNote)
      && (showQrScanner.value ? String(kotakP3K.currentKotakP3KBarcode.value || '').trim() !== '' : true)
  }

  if (tid === 'genset_running') {
    if (!canApproveCurrentTemplate.value || gensetRunning.isGensetRunningApproved.value) return false
    return gensetRunning.gensetRunningValidation.value.allAnswersFilled
      && (!gensetRunning.gensetRunningValidation.value.hasNoAnswer || gensetRunning.gensetRunningValidation.value.hasRequiredNote)
      && (showQrScanner.value ? String(gensetRunning.currentGensetRunningBarcode.value || '').trim() !== '' : true)
  }

  if (tid === 'running_genset') {
    if (!canApproveCurrentTemplate.value || runningGenset.isRunningGensetApproved.value) return false
    return Boolean(String(entry.value?.form?.date_value || '').trim())
      && runningGenset.runningGensetValidation.value.hasHourMeter
      && runningGenset.runningGensetValidation.value.allAnswersFilled
      && (!runningGenset.runningGensetValidation.value.hasNoAnswer || runningGenset.runningGensetValidation.value.hasRequiredNote)
      && (showQrScanner.value ? String(runningGenset.currentRunningGensetBarcode.value || '').trim() !== '' : true)
  }

  if (tid === 'kompresor_harian') {
    if (!canApproveCurrentTemplate.value) return false
    const hasHeader = Boolean(String(entry.value?.form?.period || '').trim()) && Boolean(String(entry.value?.form?.compressor_no || '').trim())
    if (!hasHeader) return false
    const rows = Array.isArray(entry.value.form.rows) ? entry.value.form.rows : []
    const approvedDays = Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days.map(Number) : []
    const activeDay = Number(entry.value.form.active_day) || Number(rows[0]?.day) || 1
    const activeRow = rows.find((row) => Number(row.day) === activeDay)
    if (!activeRow || approvedDays.includes(activeDay) || !isKompresorDailyRowComplete(activeRow)) return false
    return !hasKompresorDailyNoAnswer(activeRow) || isFilled(entry.value.form.note)
  }

  if (tid === 'charger_baterai') {
    if (!canApproveCurrentTemplate.value) return false
    const hasHeader = Boolean(String(entry.value?.form?.period || '').trim()) && Boolean(String(entry.value?.form?.serial_no || '').trim())
    if (!hasHeader) return false
    const rows = Array.isArray(entry.value.form.rows) ? entry.value.form.rows : []
    const approvedDays = Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days.map(Number) : []
    const activeDay = Number(entry.value.form.active_day) || Number(rows[0]?.day) || 1
    const activeRow = rows.find((row) => Number(row.day) === activeDay)
    if (!activeRow || approvedDays.includes(activeDay) || !isChargerBateraiRowComplete(activeRow)) return false
    return !hasChargerBateraiNoAnswer(activeRow) || isFilled(entry.value.form.note)
  }

  if (tid === 'checklist_baterai') {
    if (!canApproveCurrentTemplate.value) return false
    const hasHeader = Boolean(String(entry.value?.form?.period || '').trim()) && Boolean(String(entry.value?.form?.battery_no || '').trim())
    if (!hasHeader) return false
    const rows = Array.isArray(entry.value.form.rows) ? entry.value.form.rows : []
    const approvedDays = Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days.map(Number) : []
    const activeDay = Number(entry.value.form.active_day) || Number(rows[0]?.day) || 1
    const activeRow = rows.find((row) => Number(row.day) === activeDay)
    if (!activeRow || approvedDays.includes(activeDay) || !isChecklistBateraiRowComplete(activeRow)) return false
    return !hasChecklistBateraiNoAnswer(activeRow) || isFilled(entry.value.form.note)
  }

  if (tid === 'non_warehouse_sanitation') {
    if (!sanitation.nextPendingSanitationDay.value) return false
    if (sanitation.isNextPendingSanitationDaySubmitted.value) return sanitation.canApprovePendingSanitationSubmission.value
    const pendingDay = sanitation.nextPendingSanitationDay.value.day
    const allAreasScanned = sanitation.sanitationAreaOptions.every((area) => {
      const dayScans = entry.value.form.area_scans_by_day?.[pendingDay] || {}
      return Boolean(dayScans?.[area.id]?.barcode)
    })
    return Boolean(currentChecklistTemplatePermissions.value.view)
      && (showQrScanner.value ? allAreasScanned : true)
      && sanitation.sanitationCompletedDays.value.some((day) => day.day === pendingDay)
  }

  if (tid === 'apar_smoke_detector_fire_alarm') {
    if (!canApproveCurrentTemplate.value || fireSafety.isActiveFireSafetyMonthApproved.value) return false
    return Boolean(String(entry.value.form.card_type || '').trim() && String(entry.value.form.location || '').trim())
      && fireSafety.fireSafetyMonthValidation.value.allAnswersFilled
      && (showQrScanner.value ? String(fireSafety.currentFireSafetyBarcode.value || '').trim() !== '' : true)
      && (!fireSafety.fireSafetyMonthValidation.value.hasNoAnswer || fireSafety.fireSafetyMonthValidation.value.hasRequiredNote)
  }

  if (tid === 'pengangkutan_sampah_pt_sier') {
    if (!canApproveCurrentTemplate.value || !wasteTransport.nextPendingWasteTransportDay.value) return false
    const d = wasteTransport.nextPendingWasteTransportDay.value
    return Boolean(d.pickup_time && d.handover_name && d.collector_name && d.collector_photo_name)
  }

  if (tid === 'warehouse_sanitation_1') {
    if (!canApproveCurrentTemplate.value) return false
    const hasValidSchedule = entry.value.form.frequency === 'monthly' ? Boolean(String(entry.value.form.period || '').trim()) : Boolean(String(entry.value.form.date || '').trim())
    const generalCompleted = Boolean(hasValidSchedule && Array.isArray(entry.value.form.selected_areas) && entry.value.form.selected_areas.length && String(entry.value.form.room_temperature || '').trim() && String(entry.value.form.petugas || '').trim() && String(entry.value.form.hse || '').trim())
    const areaRowsCompleted = (entry.value.form.area_rows || []).every((row) => row.clean_condition && row.no_ice_pooling && row.no_odor)
    const iceControlCompleted = (entry.value.form.ice_control_rows || []).every((row) => row.status)
    const cleaningMaterialCompleted = (entry.value.form.cleaning_material_rows || []).every((row) => String(row.material_name || '').trim() && row.halal && row.dosage)
    const formCompleted = generalCompleted && areaRowsCompleted && iceControlCompleted && cleaningMaterialCompleted
    if (!formCompleted || warehouse.warehouseManagerApproved.value) return false
    if (!warehouse.warehousePreparedApproved.value) return showQrScanner.value ? Boolean(warehouse.currentWarehouseBarcode.value) : true
    return canApproveWarehouseFinal.value
  }

  if (tid === 'personal_hygiene_karyawan') {
    if (!canApproveCurrentTemplate.value) return false
    if (personalHygiene.generatedPersonalHygieneEmployees.value.length > 0) return true
    const generalCompleted = Boolean(String(entry.value.form.year || '').trim() && String(entry.value.form.period || '').trim() && String(entry.value.form.employee_name || '').trim() && String(entry.value.form.gender || '').trim() && String(entry.value.form.nik || '').trim() && String(entry.value.form.bagian || '').trim())
    const hasAnyCheckedDay = personalHygiene.personalHygieneRows.value.some((row) => Object.values(row.days || {}).some(Boolean))
    return generalCompleted && hasAnyCheckedDay
  }

  if (tid === 'sarana_dan_prasarana') {
    if (!canApproveCurrentTemplate.value) return false
    if (!saranaPrasarana.nextPendingSaranaPrasaranaDay.value) return false
    return Boolean(String(entry.value.form.period || '').trim()) && Boolean(String(entry.value.form.selected_area || '').trim())
      && (saranaPrasarana.currentSaranaPrasaranaSection.value?.items || []).every((item) => Boolean(item.days?.[saranaPrasarana.nextPendingSaranaPrasaranaDay.value.day]))
      && (showQrScanner.value ? Boolean(saranaPrasarana.currentSaranaPrasaranaScan.value?.barcode) : true)
  }

  if (tid === 'site_visit_maintenance') {
    if (!canApproveCurrentTemplate.value) return false
    const visitType = String(entry.value.form.visit_type || '').trim()
    const hasSchedule = visitType === 'maintenance_mingguan' ? Boolean(String(entry.value.form.period_value || '').trim()) : Boolean(String(entry.value.form.date_value || '').trim())
    return Boolean(visitType) && hasSchedule
      && (visitType === 'maintenance_mingguan' || Boolean(String(entry.value.form.selected_area || '').trim()))
      && (showQrScanner.value ? Boolean(String(siteVisitMaintenance.currentMaintenanceBarcode.value || '').trim()) : true)
      && siteVisitMaintenance.maintenanceValidation.value.allAnswersFilled
      && (!siteVisitMaintenance.maintenanceValidation.value.hasNoAnswer || siteVisitMaintenance.maintenanceValidation.value.hasRequiredNote)
  }

  if (tid === 'site_visit_hse') {
    if (!canApproveCurrentTemplate.value) return false
    const selectedArea = String(entry.value.form.selected_area || '').trim()
    return Boolean(String(entry.value.form.date_value || '').trim()) && Boolean(selectedArea)
      && !siteVisitHse.siteVisitHseApprovedAreas.value.includes(selectedArea)
      && (showQrScanner.value ? Boolean(String(siteVisitHse.currentSiteVisitHseBarcode.value || '').trim()) : true)
      && siteVisitHse.siteVisitHseValidation.value.allAnswersFilled
      && (!siteVisitHse.siteVisitHseValidation.value.hasNoAnswer || siteVisitHse.siteVisitHseValidation.value.hasRequiredNote)
  }

  if (tid === 'patroli_security') {
    if (!canApproveCurrentTemplate.value) return false
    const selectedArea = String(entry.value.form.selected_area || '').trim()
    return Boolean(String(entry.value.form.date_value || '').trim()) && Boolean(selectedArea)
      && !patroliSecurity.patroliSecurityApprovedAreas.value.includes(selectedArea)
      && (showQrScanner.value ? Boolean(String(patroliSecurity.currentPatroliSecurityBarcode.value || '').trim()) : true)
      && patroliSecurity.patroliSecurityValidation.value.allAnswersFilled
      && (!patroliSecurity.patroliSecurityValidation.value.hasNoAnswer || patroliSecurity.patroliSecurityValidation.value.hasRequiredNote)
  }

  if (tid === 'jadwal_cleaning_ob') {
    if (!canApproveCurrentTemplate.value) return false
    const selectedShift = String(entry.value.form.selected_shift || '').trim()
    return Boolean(String(entry.value.form.date_value || '').trim()) && Boolean(selectedShift)
      && !cleaningOB.cleaningOBApprovedAreas.value.includes(selectedShift)
      && (showQrScanner.value ? Boolean(String(cleaningOB.currentCleaningOBBarcode.value || '').trim()) : true)
      && cleaningOB.cleaningOBValidation.value.allAnswersFilled
      && (!cleaningOB.cleaningOBValidation.value.hasNoAnswer || cleaningOB.cleaningOBValidation.value.hasRequiredNote)
  }

  return canApproveCurrentTemplate.value
})

// ─── approveChecklist ───────────────────────────────────────
const maintenanceApprovalDisabledReason = computed(() => {
  if (!entry.value || entry.value.template_id !== 'site_visit_maintenance' || canApproveEntry.value) return ''
  if (!canApproveCurrentTemplate.value) return 'Akun ini belum memiliki akses approve untuk template maintenance.'

  const visitType = String(entry.value.form.visit_type || '').trim()
  const hasSchedule = visitType === 'maintenance_mingguan'
    ? Boolean(String(entry.value.form.period_value || '').trim())
    : Boolean(String(entry.value.form.date_value || '').trim())

  if (!visitType) return 'Jenis checklist belum dipilih.'
  if (!hasSchedule) return visitType === 'maintenance_mingguan' ? 'Periode belum dipilih.' : 'Tanggal belum dipilih.'
  if (visitType === 'maintenance_harian' && !String(entry.value.form.selected_area || '').trim()) return 'Area belum dipilih.'
  if (showQrScanner.value && !String(siteVisitMaintenance.currentMaintenanceBarcode.value || '').trim()) return 'QRCode area aktif belum discan.'
  if (!siteVisitMaintenance.maintenanceValidation.value.allAnswersFilled) return 'Semua kondisi checklist harus diisi.'
  if (siteVisitMaintenance.maintenanceValidation.value.hasNoAnswer && !siteVisitMaintenance.maintenanceValidation.value.hasRequiredNote) return 'Isi keterangan jika ada item bertanda silang.'

  return 'Belum memenuhi syarat approval.'
})

async function approveChecklist() {
  if (!entry.value || !canApproveEntry.value) return
  const now = new Date()
  const approverName = currentUser.value?.name || 'User Login'
  const tid = entry.value.template_id

  if (tid === 'kotak_p3k') {
    const activeMonth = kotakP3K.activeKotakP3KMonth.value
    if (kotakP3K.isActiveKotakP3KMonthSubmitted.value) {
      entry.value.form.approved_months = [...new Set([...(entry.value.form.approved_months || []), activeMonth])]
      entry.value.form.submitted_months = (entry.value.form.submitted_months || []).filter((m) => m !== activeMonth)
      entry.value.form.monthly_hse_approved_by = { ...(entry.value.form.monthly_hse_approved_by || {}), [activeMonth]: approverName }
      entry.value.form.approved = true
    } else {
      entry.value.form.submitted_months = [...new Set([...(entry.value.form.submitted_months || []), activeMonth])]
      entry.value.form.approved = false
    }
    await persistChecklistEntry(entry.value, { force: true, approvalAction: true })
    return
  }

  if (tid === 'apar_smoke_detector_fire_alarm') {
    fireSafety.persistCurrentFireSafetyState()
    entry.value.form.approved_months = [...new Set([...(entry.value.form.approved_months || []), fireSafety.activeFireSafetyMonth.value])]
    entry.value.form.monthly_check_dates = { ...(entry.value.form.monthly_check_dates || {}), [fireSafety.activeFireSafetyMonth.value]: formatDateDisplay(now) }
    entry.value.form.approved = true
    fireSafety.persistCurrentFireSafetyState()
    await persistChecklistEntry(entry.value, { force: true, approvalAction: true })
    return
  }

  if (tid === 'genset_running') { entry.value.form.approved = true; await persistChecklistEntry(entry.value, { force: true, approvalAction: true }); return }

  if (tid === 'non_warehouse_sanitation' && sanitation.nextPendingSanitationDay.value) {
    const pendingDay = sanitation.nextPendingSanitationDay.value.day
    const currentRequests = { ...(entry.value.form.approval_requests_by_day || {}) }
    if (sanitation.isNextPendingSanitationDaySubmitted.value) {
      entry.value.form.approved_days = [...new Set([...(entry.value.form.approved_days || []), pendingDay])].sort((a, b) => a - b)
      entry.value.form.submitted_days = (entry.value.form.submitted_days || []).filter((d) => d !== pendingDay)
      currentRequests[pendingDay] = { ...(currentRequests[pendingDay] || {}), approved_by_id: currentUser.value?.id || null, approved_by_name: approverName, approved_at: formatDateTimeDisplay(now) }
      entry.value.form.approval_requests_by_day = currentRequests
      entry.value.form.approved = sanitation.isSanitationChecklistFullyApproved(entry.value)
      await persistChecklistEntry(entry.value, { force: true, approvalAction: true })
    } else {
      entry.value.form.submitted_days = [...new Set([...(entry.value.form.submitted_days || []), pendingDay])].sort((a, b) => a - b)
      currentRequests[pendingDay] = { ...(currentRequests[pendingDay] || {}), submitted_by_id: currentUser.value?.id || null, submitted_by_name: approverName, submitted_at: formatDateTimeDisplay(now), approved_by_id: null, approved_by_name: null, approved_at: null }
      entry.value.form.approval_requests_by_day = currentRequests
      entry.value.form.approved = false
      await persistChecklistEntry(entry.value, { force: true, approvalAction: false })
    }
    return
  }

  if (tid === 'pengangkutan_sampah_pt_sier' && wasteTransport.nextPendingWasteTransportDay.value) {
    entry.value.form.approved_days = [...new Set([...(entry.value.form.approved_days || []), wasteTransport.nextPendingWasteTransportDay.value.day])].sort((a, b) => a - b)
  }

  if (tid === 'warehouse_sanitation_1') {
    entry.value.form.verification = { ...(entry.value.form.verification || {}), ...(warehouse.warehousePreparedApproved.value ? { verified_name: approverName, verified_signature: buildDigitalSignature(approverName), verified_date: formatDateDisplay(now) } : { prepared_name: approverName, prepared_signature: buildDigitalSignature(approverName), prepared_date: formatDateDisplay(now) }) }
    entry.value.form.approved = warehouse.warehousePreparedApproved.value
    await persistChecklistEntry(entry.value, { force: true, approvalAction: true })
    return
  }

  if (tid === 'sarana_dan_prasarana' && saranaPrasarana.nextPendingSaranaPrasaranaDay.value) {
    const selectedArea = String(entry.value.form.selected_area || '').trim()
    const currentApprovedDays = Array.isArray(entry.value.form.approved_days_by_area?.[selectedArea]) ? entry.value.form.approved_days_by_area[selectedArea] : []
    entry.value.form.approved_days_by_area = { ...(entry.value.form.approved_days_by_area || {}), [selectedArea]: [...new Set([...currentApprovedDays, saranaPrasarana.nextPendingSaranaPrasaranaDay.value.day])].sort((a, b) => a - b) }
  }

  if (tid === 'patroli_security') {
    const selectedArea = String(entry.value.form.selected_area || '').trim()
    entry.value.form.approved_areas = [...new Set([...(entry.value.form.approved_areas || []), selectedArea])]
    entry.value.form.approved = patroliSecurity.patroliSecurityAreaOptions.every((area) => entry.value.form.approved_areas.includes(area.id))
    await persistChecklistEntry(entry.value, { force: true, approvalAction: true })
    return
  }

  if (tid === 'jadwal_cleaning_ob') {
    const selectedShift = String(entry.value.form.selected_shift || '').trim()
    entry.value.form.approved_areas = [...new Set([...(entry.value.form.approved_areas || []), selectedShift])]
    entry.value.form.approved = cleaningOB.cleaningOBShiftOptions.every((shift) => entry.value.form.approved_areas.includes(shift.id))
    await persistChecklistEntry(entry.value, { force: true, approvalAction: true })
    return
  }

  if (tid === 'site_visit_hse') {
    const selectedArea = String(entry.value.form.selected_area || '').trim()
    entry.value.form.approved_areas = [...new Set([...(entry.value.form.approved_areas || []), selectedArea])]
    entry.value.form.approved = siteVisitHse.siteVisitHseAreaOptions.every((area) => entry.value.form.approved_areas.includes(area.id))
    await persistChecklistEntry(entry.value, { force: true, approvalAction: true })
    return
  }

  if (tid === 'kompresor_harian') {
    const approvedDays = Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days.map(Number) : []
    const rows = Array.isArray(entry.value.form.rows) ? entry.value.form.rows : []
    const activeDay = Number(entry.value.form.active_day) || Number(rows[0]?.day) || 1
    const activeRow = rows.find((row) => Number(row.day) === activeDay)
    if (activeRow && !approvedDays.includes(activeDay) && isKompresorDailyRowComplete(activeRow)) {
      entry.value.form.approved_days = [...new Set([...approvedDays, activeDay])].sort((a, b) => a - b)
      entry.value.form.approved = true
    }
    const savedApprovedDays = [...(entry.value.form.approved_days || [])].map(Number)
    await persistChecklistEntry(entry.value, { force: true, approvalAction: true })
    if (entry.value) entry.value.form.approved_days = savedApprovedDays
    return
  }

  if (tid === 'charger_baterai') {
    const approvedDays = Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days.map(Number) : []
    const rows = Array.isArray(entry.value.form.rows) ? entry.value.form.rows : []
    const activeDay = Number(entry.value.form.active_day) || Number(rows[0]?.day) || 1
    const activeRow = rows.find((row) => Number(row.day) === activeDay)
    if (activeRow && !approvedDays.includes(activeDay) && isChargerBateraiRowComplete(activeRow)) {
      entry.value.form.approved_days = [...new Set([...approvedDays, activeDay])].sort((a, b) => a - b)
      entry.value.form.approved = true
    }
    const savedApprovedDays = [...(entry.value.form.approved_days || [])].map(Number)
    await persistChecklistEntry(entry.value, { force: true, approvalAction: true })
    if (entry.value) entry.value.form.approved_days = savedApprovedDays
    return
  }

  if (tid === 'checklist_baterai') {
    const approvedDays = Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days.map(Number) : []
    const rows = Array.isArray(entry.value.form.rows) ? entry.value.form.rows : []
    const activeDay = Number(entry.value.form.active_day) || Number(rows[0]?.day) || 1
    const activeRow = rows.find((row) => Number(row.day) === activeDay)
    if (activeRow && !approvedDays.includes(activeDay) && isChecklistBateraiRowComplete(activeRow)) {
      entry.value.form.approved_days = [...new Set([...approvedDays, activeDay])].sort((a, b) => a - b)
      entry.value.form.approved = true
    }
    const savedApprovedDays = [...(entry.value.form.approved_days || [])].map(Number)
    await persistChecklistEntry(entry.value, { force: true, approvalAction: true })
    if (entry.value) entry.value.form.approved_days = savedApprovedDays
    return
  }

  entry.value.form.approved = true
  await persistChecklistEntry(entry.value, { force: true, approvalAction: true })
}

// ─── Entry Creation & Hydration ─────────────────────────────
function createEntryByTemplate(templateId, options = {}) {
  const userName = page.props.auth?.user?.name || 'User Login'
  const continuableEntry = options.continuableEntry || null
  const handlers = {
    kotak_p3k: () => createKotakP3KEntry(userName),
    non_warehouse_sanitation: () => continuableEntry ? hydrateChecklistEntry(continuableEntry) : createNonWarehouseSanitationEntry(userName),
    apar_smoke_detector_fire_alarm: () => createFireSafetyEntry(userName),
    pengangkutan_sampah_pt_sier: () => createWasteTransportEntry(userName),
    warehouse_sanitation_1: () => createWarehouseSanitationEntry(userName),
    personal_hygiene_karyawan: () => createPersonalHygieneEntry(userName),
    sarana_dan_prasarana: () => createSaranaPrasaranaEntry(userName),
    patroli_security: () => continuableEntry ? hydrateChecklistEntry(continuableEntry) : createPatroliSecurityEntry(userName),
    jadwal_cleaning_ob: () => continuableEntry ? hydrateChecklistEntry(continuableEntry) : createCleaningOBEntry(userName),
    site_visit_hse: () => createSiteVisitHseEntry(userName),
    site_visit_maintenance: () => createSiteVisitMaintenanceEntry(userName),
    genset_running: () => createGensetRunningEntry(userName),
    running_genset: () => createRunningGensetEntry(userName),
    kompresor_harian: () => createKompresorHarianEntry(userName),
    charger_baterai: () => createChargerBateraiEntry(userName),
    checklist_baterai: () => createChecklistBateraiEntry(userName),
  }
  return (handlers[templateId] || (() => null))()
}

function createInitialEntry() {
  if (props.entryId && props.savedEntry) {
    const hydratedEntry = hydrateChecklistEntry(props.savedEntry)
    selectedChecklist.value = hydratedEntry.template_id
    return hydratedEntry
  }
  const continuableSanitationEntry = selectedChecklist.value === 'non_warehouse_sanitation' ? sanitation.findOpenSanitationDraft(props.existingEntries) : null
  const continuablePatroliEntry = selectedChecklist.value === 'patroli_security' ? patroliSecurity.findOpenPatroliSecurityDraft(props.existingEntries) : null
  const continuableCleaningOBEntry = selectedChecklist.value === 'jadwal_cleaning_ob' ? cleaningOB.findOpenCleaningOBDraft(props.existingEntries) : null
  return createEntryByTemplate(selectedChecklist.value, { continuableEntry: continuableSanitationEntry || continuablePatroliEntry || continuableCleaningOBEntry })
}

function refreshEntry() {
  if (props.entryId && entry.value?.id === props.entryId && entry.value?.template_id === selectedChecklist.value) return
  const continuableSanitationEntry = selectedChecklist.value === 'non_warehouse_sanitation' ? sanitation.findOpenSanitationDraft(knownChecklistEntries.value) : null
  const continuablePatroliEntry = selectedChecklist.value === 'patroli_security' ? patroliSecurity.findOpenPatroliSecurityDraft(knownChecklistEntries.value) : null
  const continuableCleaningOBEntry = selectedChecklist.value === 'jadwal_cleaning_ob' ? cleaningOB.findOpenCleaningOBDraft(knownChecklistEntries.value) : null
  entry.value = createEntryByTemplate(selectedChecklist.value, { continuableEntry: continuableSanitationEntry || continuablePatroliEntry || continuableCleaningOBEntry })
  if ((continuableSanitationEntry && entry.value?.id === continuableSanitationEntry.id) || (continuablePatroliEntry && entry.value?.id === continuablePatroliEntry.id) || (continuableCleaningOBEntry && entry.value?.id === continuableCleaningOBEntry.id)) syncCurrentEntryUrl(entry.value)
}

// ─── Initial Save State ──────────────────────────────────────
if (entry.value?.id && supportedTemplates.includes(entry.value.template_id)) {
  saveState.value = 'saved'
}
if (entry.value?.id) syncCurrentEntryUrl(entry.value)

// ─── Handle Outside Click ───────────────────────────────────
function handleOutsideLocationMenu(event) {
  if (!event.target.closest('[data-location-menu-root]')) locationMenuOpen.value = false
}

// ─── Lifecycle ──────────────────────────────────────────────
onMounted(() => document.addEventListener('click', handleOutsideLocationMenu))
onBeforeUnmount(() => { document.removeEventListener('click', handleOutsideLocationMenu); scanner.stopBarcodeScanner(); photo.stopPhotoCamera() })

watch(selectedChecklist, () => { locationMenuOpen.value = false; saveError.value = ''; saveState.value = 'idle'; refreshEntry() })

watch(() => [entry.value?.form?.area, entry.value?.form?.period, selectedChecklist.value], () => {
  sanitation.rebuildSanitationEntryRows()
  wasteTransport.rebuildWasteTransportEntryRows()
  personalHygiene.rebuildPersonalHygieneEntryRows()
  warehouse.syncWarehouseAreaRows()
}, { immediate: true })

watch(entry, () => syncSaveStateWithEntry(), { deep: true })
</script>
