import { ref, computed } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import {
  toDateInputValue, toPeriodValue, toWeekInputValue, formatWeekDisplay, formatDateDisplay,
  formatDateInputDisplay, formatDayMonthDisplay, formatDateTimeDisplay, formatShortDateDisplay,
  rebuildPatroliSecuritySections, rebuildFireSafetyRows, rebuildSaranaPrasaranaSections,
  rebuildSiteVisitHseSections, rebuildMaintenanceDailySections, rebuildMaintenanceWeeklyRows,
  rebuildGensetRunningRows, rebuildRunningGensetRows, rebuildKompresorDailyRows, rebuildChargerBateraiRows, rebuildChecklistBateraiRows,
  getMaintenanceVisitTypeMeta, getFireSafetyLocationOptions, getFireSafetyRecordKey,
  createFireSafetyLocationState, saranaPrasaranaAreaOptions, patroliSecurityAreaOptions,
  siteVisitHseAreaOptions,
} from '../checklistConfig'

function hydrateFireSafetyEntry(savedEntry) {
  if (savedEntry?.template_id !== 'apar_smoke_detector_fire_alarm') return savedEntry
  const cardType = String(savedEntry?.form?.card_type || 'fire_alarm').trim() || 'fire_alarm'
  const locationId = String(savedEntry?.form?.location || '').trim() || (getFireSafetyLocationOptions(cardType)[0]?.id || '')
  const recordKey = getFireSafetyRecordKey(cardType, locationId)
  const nextEntry = { ...savedEntry, form: { ...savedEntry.form, card_type: cardType, location: locationId, location_records: { ...(savedEntry?.form?.location_records || {}) } } }
  if (!nextEntry.form.location_records[recordKey]) {
    nextEntry.form.location_records[recordKey] = createFireSafetyLocationState(cardType, {
      approved_months: savedEntry?.form?.approved_months || [], monthly_notes: savedEntry?.form?.monthly_notes || {},
      monthly_barcodes: savedEntry?.form?.monthly_barcodes || {}, monthly_check_dates: savedEntry?.form?.monthly_check_dates || {},
      rows: savedEntry?.form?.rows || [],
    })
  }
  const loadedState = nextEntry.form.location_records[recordKey]
  nextEntry.form.approved_months = [...(loadedState.approved_months || [])]
  nextEntry.form.monthly_notes = { ...(loadedState.monthly_notes || {}) }
  nextEntry.form.monthly_barcodes = { ...(loadedState.monthly_barcodes || {}) }
  nextEntry.form.monthly_check_dates = { ...(loadedState.monthly_check_dates || {}) }
  nextEntry.form.rows = rebuildFireSafetyRows(cardType, loadedState.rows || [])
  return nextEntry
}

function hydrateSanitationEntry(savedEntry) {
  if (savedEntry?.template_id !== 'non_warehouse_sanitation') return savedEntry
  const legacyBackItems = new Set(['Ruang Mesin', 'Ruang Kontrol', 'Ruang Baterai'])
  const rowsByArea = { ...(savedEntry?.form?.rows_by_area || {}) }
  const legacyOutsideRows = Array.isArray(rowsByArea.area_luar_bangunan) ? rowsByArea.area_luar_bangunan : []
  if (!Array.isArray(rowsByArea.lantai_1_depan) && legacyOutsideRows.length) {
    rowsByArea.lantai_1_depan = legacyOutsideRows.filter((row) => !legacyBackItems.has(String(row?.name || '').trim()))
  }
  if (!Array.isArray(rowsByArea.lantai_1_belakang) && legacyOutsideRows.length) {
    rowsByArea.lantai_1_belakang = legacyOutsideRows.filter((row) => legacyBackItems.has(String(row?.name || '').trim()))
  }
  const normalizedArea = String(savedEntry?.form?.area || '').trim() === 'area_luar_bangunan' ? 'lantai_1_depan' : String(savedEntry?.form?.area || '').trim()
  return { ...savedEntry, form: { ...savedEntry.form, area: normalizedArea || 'lantai_1', rows_by_area: rowsByArea } }
}

function hydrateSaranaPrasaranaEntry(savedEntry) {
  if (savedEntry?.template_id !== 'sarana_dan_prasarana') return savedEntry
  const period = String(savedEntry?.form?.period || '').trim() || toPeriodValue(new Date())
  return { ...savedEntry, form: { ...savedEntry.form, period, selected_area: String(savedEntry?.form?.selected_area || '').trim() || savedEntry?.form?.sections?.[0]?.id || '', area_scans_by_day: { ...(savedEntry?.form?.area_scans_by_day || {}) }, approved_days_by_area: saranaPrasaranaAreaOptions.reduce((result, area) => { result[area.id] = Array.isArray(savedEntry?.form?.approved_days_by_area?.[area.id]) ? savedEntry.form.approved_days_by_area[area.id] : (Array.isArray(savedEntry?.form?.approved_days) && area.id === (savedEntry?.form?.selected_area || savedEntry?.form?.sections?.[0]?.id) ? savedEntry.form.approved_days : []); return result }, {}), sections: rebuildSaranaPrasaranaSections(period, savedEntry?.form?.sections || []) } }
}

function hydratePatroliSecurityEntry(savedEntry) {
  if (savedEntry?.template_id !== 'patroli_security') return savedEntry
  const now = new Date()
  const nextDateValue = String(savedEntry?.form?.date_value || '').trim() || toDateInputValue(now)
  const nextSelectedArea = String(savedEntry?.form?.selected_area || '').trim() || savedEntry?.form?.sections?.[0]?.id || patroliSecurityAreaOptions[0]?.id || ''
  return { ...savedEntry, form: { ...savedEntry.form, date_value: nextDateValue, date: savedEntry?.form?.date || formatDateInputDisplay(nextDateValue), selected_area: nextSelectedArea, approved_areas: Array.isArray(savedEntry?.form?.approved_areas) ? savedEntry.form.approved_areas : [], area_barcodes: { ...(savedEntry?.form?.area_barcodes || {}) }, area_notes: { ...(savedEntry?.form?.area_notes || {}) }, area_photo_paths: { ...(savedEntry?.form?.area_photo_paths || {}) }, area_photo_urls: { ...(savedEntry?.form?.area_photo_urls || {}) }, area_photo_names: { ...(savedEntry?.form?.area_photo_names || {}) }, area_scan_dates: { ...(savedEntry?.form?.area_scan_dates || {}) }, document_no: savedEntry?.form?.document_no || 'FRM.HSE.15.02', rev: savedEntry?.form?.rev || '00', effective_date: savedEntry?.form?.effective_date || '22 Desember 2025', page: savedEntry?.form?.page || '1 dari 1', sections: rebuildPatroliSecuritySections(savedEntry?.form?.sections || []) } }
}

function hydratePersonalHygieneEntry(savedEntry) {
  if (savedEntry?.template_id !== 'personal_hygiene_karyawan') return savedEntry
  const nextBagian = String(savedEntry?.form?.bagian || '').trim()
  return { ...savedEntry, form: { ...savedEntry.form, bagian: nextBagian } }
}

function hydrateSiteVisitHseEntry(savedEntry) {
  if (savedEntry?.template_id !== 'site_visit_hse') return savedEntry
  const now = new Date()
  const nextDateValue = String(savedEntry?.form?.date_value || '').trim() || toDateInputValue(now)
  const nextSelectedArea = String(savedEntry?.form?.selected_area || '').trim() || savedEntry?.form?.sections?.[0]?.id || siteVisitHseAreaOptions[0]?.id || ''
  return { ...savedEntry, form: { ...savedEntry.form, date_value: nextDateValue, date: savedEntry?.form?.date || formatDateInputDisplay(nextDateValue), selected_area: nextSelectedArea, approved_areas: Array.isArray(savedEntry?.form?.approved_areas) ? savedEntry.form.approved_areas : [], area_barcodes: { ...(savedEntry?.form?.area_barcodes || {}) }, area_notes: { ...(savedEntry?.form?.area_notes || {}) }, area_scan_dates: { ...(savedEntry?.form?.area_scan_dates || {}) }, document_no: savedEntry?.form?.document_no || 'FRM.HSE.15.01', rev: savedEntry?.form?.rev || '00', effective_date: savedEntry?.form?.effective_date || '22 Desember 2025', page: savedEntry?.form?.page || '1 dari 1', sections: rebuildSiteVisitHseSections(savedEntry?.form?.sections || []) } }
}

function hydrateSiteVisitMaintenanceEntry(savedEntry) {
  if (savedEntry?.template_id !== 'site_visit_maintenance') return savedEntry
  const visitType = String(savedEntry?.form?.visit_type || '').trim() || 'maintenance_harian'
  const typeMeta = getMaintenanceVisitTypeMeta(visitType)
  const now = new Date()
  const nextDateValue = String(savedEntry?.form?.date_value || '').trim() || toDateInputValue(now)
  const nextPeriodValue = String(savedEntry?.form?.period_value || '').trim() || toWeekInputValue(now)
  const nextSelectedArea = String(savedEntry?.form?.selected_area || '').trim() || 'lantai_1_area_belakang'
  return { ...savedEntry, form: { ...savedEntry.form, visit_type: visitType, document_no: savedEntry?.form?.document_no || typeMeta.document_no, rev: savedEntry?.form?.rev || '00', effective_date: savedEntry?.form?.effective_date || '22 Desember 2025', page: savedEntry?.form?.page || '1 dari 1', selected_area: nextSelectedArea, area_barcodes: { ...(savedEntry?.form?.area_barcodes || {}) }, area_scan_dates: { ...(savedEntry?.form?.area_scan_dates || {}) }, area_notes: { ...(savedEntry?.form?.area_notes || {}) }, date_value: nextDateValue, date: visitType === 'maintenance_harian' ? (savedEntry?.form?.date || formatDateInputDisplay(nextDateValue)) : '', period_value: nextPeriodValue, period: visitType === 'maintenance_mingguan' ? (savedEntry?.form?.period || formatWeekDisplay(nextPeriodValue)) : '', sections: rebuildMaintenanceDailySections(savedEntry?.form?.sections || []), rows: rebuildMaintenanceWeeklyRows(savedEntry?.form?.rows || []) } }
}

function hydrateGensetRunningEntry(savedEntry) {
  if (savedEntry?.template_id !== 'genset_running') return savedEntry
  const now = new Date()
  const legacySections = Array.isArray(savedEntry?.form?.sections) ? savedEntry.form.sections : []
  const legacyActiveMonth = String(savedEntry?.form?.active_month || '').trim() || 'jan'
  const legacyRows = rebuildGensetRunningRows(Array.isArray(savedEntry?.form?.rows) && savedEntry.form.rows.length ? savedEntry.form.rows : legacySections.flatMap((section) => (section.items || []).map((item) => { const monthAnswers = item?.months?.[legacyActiveMonth] || {}; const status = ['m4', 'm3', 'm2', 'm1'].map((weekKey) => monthAnswers?.[weekKey] || '').find((value) => value === 'yes' || value === 'no') || ''; return { id: item.id, status } })))
  const fallbackPeriodValue = toWeekInputValue(now)
  return { ...savedEntry, form: { ...savedEntry.form, period_value: String(savedEntry?.form?.period_value || '').trim() || fallbackPeriodValue, period: String(savedEntry?.form?.period || '').trim() || formatWeekDisplay(fallbackPeriodValue), selected_area: String(savedEntry?.form?.selected_area || '').trim() || 'genset', pic: savedEntry?.form?.pic || 'User Login', date: String(savedEntry?.form?.date || '').trim() || formatDateDisplay(now), document_no: String(savedEntry?.form?.document_no || '').trim() || 'DF-GMI-MTC-04', rev: String(savedEntry?.form?.rev || '').trim() || '00', effective_date: String(savedEntry?.form?.effective_date || '').trim() || '22 Desember 2025', page: String(savedEntry?.form?.page || '').trim() || '1', approved: Boolean(savedEntry?.form?.approved), area_barcodes: { genset: '', ...(savedEntry?.form?.area_barcodes || {}) }, area_scan_dates: { genset: '', ...(savedEntry?.form?.area_scan_dates || {}) }, area_notes: { genset: '', ...(savedEntry?.form?.area_notes || {}) }, rows: legacyRows } }
}

function hydrateRunningGensetEntry(savedEntry) {
  if (savedEntry?.template_id !== 'running_genset') return savedEntry
  const now = new Date()
  const fallbackDateValue = toDateInputValue(now)
  return { ...savedEntry, form: { ...savedEntry.form, selected_area: String(savedEntry?.form?.selected_area || '').trim() || 'genset', pic: savedEntry?.form?.pic || 'User Login', approved: Boolean(savedEntry?.form?.approved), document_no: String(savedEntry?.form?.document_no || '').trim() || 'FRM.MTC.01.03', rev: String(savedEntry?.form?.rev || '').trim() || '00', effective_date: String(savedEntry?.form?.effective_date || '').trim() || '22 Desember 2025', page: String(savedEntry?.form?.page || '').trim() || '1 dari 1', date_value: String(savedEntry?.form?.date_value || '').trim() || fallbackDateValue, date: String(savedEntry?.form?.date || '').trim() || formatDateInputDisplay(fallbackDateValue), hour_meter: String(savedEntry?.form?.hour_meter || ''), area_barcodes: { genset: '', ...(savedEntry?.form?.area_barcodes || {}) }, area_scan_dates: { genset: '', ...(savedEntry?.form?.area_scan_dates || {}) }, area_notes: { genset: '', ...(savedEntry?.form?.area_notes || {}) }, rows: rebuildRunningGensetRows(savedEntry?.form?.rows || []) } }
}

function hydrateKompresorHarianEntry(savedEntry) {
  if (savedEntry?.template_id !== 'kompresor_harian') return savedEntry
  const period = String(savedEntry?.form?.period || '').trim() || toPeriodValue(new Date())
  const [year] = period.split('-')
  const rows = rebuildKompresorDailyRows(period, savedEntry?.form?.rows || [])
  const activeDay = Number(savedEntry?.form?.active_day) || Number(String(savedEntry?.form?.date_value || '').split('-')[2]) || Number(rows[0]?.day) || 1
  return { ...savedEntry, form: { ...savedEntry.form, period, year: String(savedEntry?.form?.year || '').trim() || year || String(new Date().getFullYear()), active_day: activeDay, compressor_no: String(savedEntry?.form?.compressor_no || ''), location: String(savedEntry?.form?.location || '').trim() || 'GOLDEN MULTI INDOTAMA', pic: savedEntry?.form?.pic || 'User Login', document_no: String(savedEntry?.form?.document_no || '').trim() || 'DF-GMI-MTC-06', approved: Boolean(savedEntry?.form?.approved), approved_days: Array.isArray(savedEntry?.form?.approved_days) ? savedEntry.form.approved_days : [], note: String(savedEntry?.form?.note || ''), check_headers: { check_1: 'TEMP SUCT (deg C)', check_2: 'TEMP DISCH (deg C)', check_3: 'TEMP OLI (deg C)', check_4: 'LEVE OLI (%)', ...(savedEntry?.form?.check_headers || {}) }, rows } }
}

function hydrateChargerBateraiEntry(savedEntry) {
  if (savedEntry?.template_id !== 'charger_baterai') return savedEntry
  const period = String(savedEntry?.form?.period || '').trim() || toPeriodValue(new Date())
  const [year] = period.split('-')
  const rows = rebuildChargerBateraiRows(period, savedEntry?.form?.rows || [])
  const activeDay = Number(savedEntry?.form?.active_day) || Number(String(savedEntry?.form?.date_value || '').split('-')[2]) || Number(rows[0]?.day) || 1
  return { ...savedEntry, form: { ...savedEntry.form, period, year: String(savedEntry?.form?.year || '').trim() || year || String(new Date().getFullYear()), active_day: activeDay, serial_no: String(savedEntry?.form?.serial_no || ''), pic: savedEntry?.form?.pic || 'User Login', document_no: String(savedEntry?.form?.document_no || '').trim() || 'DF-GMI-MTC-08', approved: Boolean(savedEntry?.form?.approved), approved_days: Array.isArray(savedEntry?.form?.approved_days) ? savedEntry.form.approved_days : [], note: String(savedEntry?.form?.note || ''), rows } }
}

function hydrateChecklistBateraiEntry(savedEntry) {
  if (savedEntry?.template_id !== 'checklist_baterai') return savedEntry
  const period = String(savedEntry?.form?.period || '').trim() || toPeriodValue(new Date())
  const [year] = period.split('-')
  const rows = rebuildChecklistBateraiRows(period, savedEntry?.form?.rows || [])
  const activeDay = Number(savedEntry?.form?.active_day) || Number(String(savedEntry?.form?.date_value || '').split('-')[2]) || Number(rows[0]?.day) || 1
  return { ...savedEntry, form: { ...savedEntry.form, period, year: String(savedEntry?.form?.year || '').trim() || year || String(new Date().getFullYear()), active_day: activeDay, battery_no: String(savedEntry?.form?.battery_no || ''), pic: savedEntry?.form?.pic || 'User Login', document_no: String(savedEntry?.form?.document_no || '').trim() || 'DF-GMI-MTC-09', approved: Boolean(savedEntry?.form?.approved), approved_days: Array.isArray(savedEntry?.form?.approved_days) ? savedEntry.form.approved_days : [], note: String(savedEntry?.form?.note || ''), rows } }
}

function hydrateChecklistEntry(savedEntry) {
  if (!savedEntry) return savedEntry
  let hydrated = { ...savedEntry }
  const hydrators = [hydrateFireSafetyEntry, hydrateSanitationEntry, hydrateSaranaPrasaranaEntry, hydratePatroliSecurityEntry, hydratePersonalHygieneEntry, hydrateSiteVisitHseEntry, hydrateSiteVisitMaintenanceEntry, hydrateGensetRunningEntry, hydrateRunningGensetEntry, hydrateKompresorHarianEntry, hydrateChargerBateraiEntry, hydrateChecklistBateraiEntry]
  hydrators.forEach((hydrate) => { hydrated = hydrate(hydrated) })
  return hydrated
}

export function usePersistence({ props, selectedChecklist, entry, supportedTemplates }) {
  const page = usePage()
  const knownChecklistEntries = ref(Array.isArray(props.existingEntries) ? [...props.existingEntries] : [])
  const saveState = ref('idle')
  const saveError = ref('')
  let saveRequestSequence = 0
  let lastSavedEntrySignature = ''

  const saveStateLabel = computed(() => {
    if (!selectedChecklist.value) return ''
    if (saveState.value === 'saving') return 'Menyimpan ke database...'
    if (saveState.value === 'saved') return 'Tersimpan di database'
    if (saveState.value === 'error') return saveError.value || 'Gagal menyimpan ke database.'
    if (saveState.value === 'dirty') return 'Perubahan belum tersimpan'
    return 'Belum ada perubahan'
  })

  const saveStateClass = computed(() => {
    if (saveState.value === 'error') return 'text-rose-300'
    if (saveState.value === 'saved') return 'text-emerald-300'
    if (saveState.value === 'saving') return 'text-sky-300'
    return 'text-slate-400'
  })

  function cloneChecklistEntry(targetEntry) { return JSON.parse(JSON.stringify(targetEntry || null)) }

  function buildEntrySignature(targetEntry) {
    if (!targetEntry) return ''
    try { return JSON.stringify(cloneChecklistEntry(targetEntry)) } catch { return '' }
  }

  function upsertKnownChecklistEntry(savedEntry) {
    if (!savedEntry?.id) return
    const nextEntry = cloneChecklistEntry(savedEntry)
    const idx = knownChecklistEntries.value.findIndex((item) => item?.id === nextEntry.id)
    if (idx === -1) { knownChecklistEntries.value = [nextEntry, ...knownChecklistEntries.value]; return }
    const next = [...knownChecklistEntries.value]
    next[idx] = nextEntry
    knownChecklistEntries.value = next
  }

  function upsertKnownChecklistEntries(entries = []) { entries.forEach((e) => upsertKnownChecklistEntry(e)) }

  function syncCurrentEntryUrl(targetEntry = entry.value) {
    if (typeof window === 'undefined' || !targetEntry?.id || !targetEntry?.template_id) return
    const params = new URLSearchParams({ template: String(targetEntry.template_id), entry_id: String(targetEntry.id) })
    window.history.replaceState({}, '', `/gmiic/checklist/create?${params.toString()}`)
  }

  async function persistChecklistEntry(targetEntry = entry.value, options = {}) {
    if (!targetEntry?.template_id || !supportedTemplates.includes(targetEntry.template_id)) return targetEntry
    const normalizedEntry = cloneChecklistEntry(targetEntry)
    const signature = buildEntrySignature(normalizedEntry)
    if (!options.force && signature !== '' && signature === lastSavedEntrySignature) return normalizedEntry
    const requestId = ++saveRequestSequence
    saveState.value = 'saving'
    saveError.value = ''
    try {
      const response = await axios.post('/gmiic/checklist/entries/save', { entry: normalizedEntry, approval_action: Boolean(options.approvalAction) })
      const savedEntry = response.data?.entry || normalizedEntry
      upsertKnownChecklistEntry(savedEntry)
      lastSavedEntrySignature = buildEntrySignature(savedEntry)
      if (entry.value?.id === savedEntry.id) entry.value = hydrateChecklistEntry(savedEntry)
      syncCurrentEntryUrl(savedEntry)
      if (requestId === saveRequestSequence) saveState.value = 'saved'
      return savedEntry
    } catch (error) {
      if (requestId === saveRequestSequence) { saveState.value = 'error'; saveError.value = error?.response?.data?.message || 'Gagal menyimpan checklist.' }
      throw error
    }
  }

  async function persistChecklistEntries(entries = []) {
    const normalizedEntries = entries.map((e) => cloneChecklistEntry(e)).filter((e) => e?.id && e?.template_id)
    if (!normalizedEntries.length) return []
    saveState.value = 'saving'
    saveError.value = ''
    try {
      const response = await axios.post('/gmiic/checklist/entries/bulk-save', { entries: normalizedEntries })
      const savedEntries = Array.isArray(response.data?.entries) ? response.data.entries : normalizedEntries
      upsertKnownChecklistEntries(savedEntries)
      if (entry.value?.id) {
        const currentSavedEntry = savedEntries.find((e) => e?.id === entry.value?.id)
        if (currentSavedEntry) { entry.value = hydrateChecklistEntry(currentSavedEntry); lastSavedEntrySignature = buildEntrySignature(currentSavedEntry); syncCurrentEntryUrl(currentSavedEntry) }
      }
      saveState.value = 'saved'
      return savedEntries
    } catch (error) { saveState.value = 'error'; saveError.value = error?.response?.data?.message || 'Gagal menyimpan checklist.'; throw error }
  }

  function syncSaveStateWithEntry() {
    if (!entry.value?.id || !supportedTemplates.includes(entry.value?.template_id || '')) { saveState.value = 'idle'; return }
    const sig = buildEntrySignature(entry.value)
    saveState.value = sig !== '' && sig === lastSavedEntrySignature ? 'saved' : 'dirty'
  }

  function buildDigitalSignature(name) {
    const initials = String(name || '').trim().split(/\s+/).filter(Boolean).slice(0, 3).map((part) => part.charAt(0).toUpperCase()).join('')
    return initials || 'DS'
  }

  return {
    knownChecklistEntries, saveState, saveError, saveStateLabel, saveStateClass,
    cloneChecklistEntry, buildEntrySignature, upsertKnownChecklistEntry, upsertKnownChecklistEntries,
    syncCurrentEntryUrl, persistChecklistEntry, persistChecklistEntries, syncSaveStateWithEntry,
    buildDigitalSignature, hydrateChecklistEntry,
  }
}

export { hydrateChecklistEntry }
