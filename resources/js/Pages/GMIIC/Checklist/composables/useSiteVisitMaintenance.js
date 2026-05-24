import { computed, ref } from 'vue'
import { getMaintenanceVisitTypeMeta, getMaintenanceDailyAreaLabel, maintenanceVisitTypeOptions, maintenanceDailyAreaOptions, rebuildMaintenanceDailySections, rebuildMaintenanceWeeklyRows, formatDateInputDisplay, formatWeekDisplay, toDateInputValue, toWeekInputValue } from '../checklistConfig'

export function useSiteVisitMaintenance(entry, { showQrScanner }) {
  const isSiteVisitMaintenance = computed(() => entry.value?.template_id === 'site_visit_maintenance')

  const maintenanceTypeMeta = computed(() => {
    if (!isSiteVisitMaintenance.value || !entry.value) return getMaintenanceVisitTypeMeta('maintenance_harian')
    return getMaintenanceVisitTypeMeta(entry.value.form.visit_type)
  })

  const maintenanceSections = computed(() => {
    if (!isSiteVisitMaintenance.value || !entry.value) return []
    const selectedArea = String(entry.value.form.selected_area || '').trim()
    const sections = Array.isArray(entry.value.form.sections) ? entry.value.form.sections : []
    if (entry.value.form.visit_type !== 'maintenance_harian') return sections
    return sections.filter((section) => String(section.area_id || '').trim() === selectedArea)
  })

  const maintenanceRows = computed(() => {
    if (!isSiteVisitMaintenance.value || !entry.value) return []
    return Array.isArray(entry.value.form.rows) ? entry.value.form.rows : []
  })

  const maintenanceScanTargetKey = computed(() => {
    if (!isSiteVisitMaintenance.value || !entry.value) return ''
    return entry.value.form.visit_type === 'maintenance_mingguan' ? 'lantai_1_area_belakang' : String(entry.value.form.selected_area || '').trim()
  })

  const currentMaintenanceBarcode = computed(() => {
    if (!isSiteVisitMaintenance.value || !entry.value) return ''
    return entry.value.form.area_barcodes?.[maintenanceScanTargetKey.value] || ''
  })

  const maintenanceNoteLabel = computed(() => {
    if (!isSiteVisitMaintenance.value || !entry.value) return 'Keterangan'
    return entry.value.form.visit_type === 'maintenance_mingguan' ? 'Keterangan Mingguan' : `Keterangan ${getMaintenanceDailyAreaLabel(entry.value.form.selected_area)}`
  })

  const maintenanceNote = computed({
    get() {
      if (!isSiteVisitMaintenance.value || !entry.value) return ''
      return entry.value.form.area_notes?.[maintenanceScanTargetKey.value] || ''
    },
    set(value) {
      if (!isSiteVisitMaintenance.value || !entry.value) return
      entry.value.form.area_notes = { ...(entry.value.form.area_notes || {}), [maintenanceScanTargetKey.value]: value }
    },
  })

  const maintenanceValidation = computed(() => {
    if (!isSiteVisitMaintenance.value || !entry.value) return { allAnswersFilled: false, hasNoAnswer: false, hasRequiredNote: false }
    const visitType = String(entry.value.form.visit_type || '').trim()
    const activeRows = visitType === 'maintenance_mingguan' ? maintenanceRows.value : maintenanceSections.value.flatMap((section) => section.items || [])
    const statuses = activeRows.map((row) => row.status || '')
    const allAnswersFilled = activeRows.length > 0 && statuses.every((status) => status === 'yes' || status === 'no')
    const hasNoAnswer = statuses.includes('no')
    const hasRequiredNote = String(maintenanceNote.value || '').trim() !== ''
    return { allAnswersFilled, hasNoAnswer, hasRequiredNote }
  })

  const canScanMaintenance = computed(() => {
    if (!isSiteVisitMaintenance.value || !entry.value || !showQrScanner.value) return false
    const visitType = String(entry.value.form.visit_type || '').trim()
    if (!visitType) return false
    const hasSchedule = visitType === 'maintenance_mingguan' ? Boolean(String(entry.value.form.period_value || '').trim()) : Boolean(String(entry.value.form.date_value || '').trim()) && Boolean(String(entry.value.form.selected_area || '').trim())
    return hasSchedule && maintenanceValidation.value.allAnswersFilled && (!maintenanceValidation.value.hasNoAnswer || maintenanceValidation.value.hasRequiredNote) && !String(currentMaintenanceBarcode.value || '').trim()
  })

  const currentMaintenancePhotos = computed(() => {
    if (!isSiteVisitMaintenance.value || !entry.value) return []
    const paths = normalizeBucket(entry.value.form.area_photo_paths?.[maintenanceScanTargetKey.value])
    const urls = normalizeBucket(entry.value.form.area_photo_urls?.[maintenanceScanTargetKey.value])
    const names = normalizeBucket(entry.value.form.area_photo_names?.[maintenanceScanTargetKey.value])
    const length = Math.max(paths.length, urls.length, names.length)
    return Array.from({ length }, (_, i) => ({ path: paths[i] || '', url: urls[i] || '', name: names[i] || '' })).filter((p) => String(p.url || p.path || '').trim() !== '')
  })

  const maintenancePhotoUploading = ref(false)
  const maintenancePhotoError = ref('')

  function normalizeBucket(bucket) {
    if (Array.isArray(bucket)) return bucket.filter((item) => String(item || '').trim() !== '')
    const single = String(bucket || '').trim()
    return single ? [single] : []
  }

  function updateMaintenanceVisitType(value) {
    if (!entry.value || !isSiteVisitMaintenance.value) return
    const visitType = String(value || '').trim() || 'maintenance_harian'
    const typeMeta = getMaintenanceVisitTypeMeta(visitType)
    entry.value.form.visit_type = visitType
    entry.value.form.document_no = typeMeta.document_no
    entry.value.form.selected_area = visitType === 'maintenance_harian' ? (entry.value.form.selected_area || 'lantai_1_area_belakang') : 'lantai_1_area_belakang'
    entry.value.form.date = visitType === 'maintenance_harian' ? formatDateInputDisplay(entry.value.form.date_value || toDateInputValue(new Date())) : ''
    entry.value.form.period = visitType === 'maintenance_mingguan' ? formatWeekDisplay(entry.value.form.period_value || toWeekInputValue(new Date())) : ''
    entry.value.form.sections = rebuildMaintenanceDailySections(entry.value.form.sections || [])
    entry.value.form.rows = rebuildMaintenanceWeeklyRows(entry.value.form.rows || [])
  }

  function updateMaintenanceVisitDate(value) {
    if (!entry.value || !isSiteVisitMaintenance.value) return
    entry.value.form.date_value = value
    entry.value.form.date = formatDateInputDisplay(value)
  }

  function updateMaintenanceVisitPeriod(value) {
    if (!entry.value || !isSiteVisitMaintenance.value) return
    entry.value.form.period_value = value
    entry.value.form.period = formatWeekDisplay(value)
  }

  function updateMaintenanceVisitArea(value) {
    if (!entry.value || !isSiteVisitMaintenance.value) return
    entry.value.form.selected_area = value
  }

  function getNextMaintenanceStatus(currentValue) {
    if (currentValue === 'yes') return 'no'
    if (currentValue === 'no') return ''
    return 'yes'
  }

  function cycleMaintenanceRowStatus(payload) {
    if (!entry.value || !isSiteVisitMaintenance.value || !payload?.rowId) return
    if (entry.value.form.visit_type === 'maintenance_mingguan') {
      entry.value.form.rows = (entry.value.form.rows || []).map((row) => (row.id === payload.rowId ? { ...row, status: getNextMaintenanceStatus(row.status) } : row))
      return
    }
    entry.value.form.sections = (entry.value.form.sections || []).map((section) => (
      section.id === payload.sectionId ? { ...section, items: (section.items || []).map((item) => (item.id === payload.rowId ? { ...item, status: getNextMaintenanceStatus(item.status) } : item)) } : section
    ))
  }

  function updateMaintenanceNote(value) {
    if (!entry.value || !isSiteVisitMaintenance.value || !maintenanceScanTargetKey.value) return
    entry.value.form.area_notes = { ...(entry.value.form.area_notes || {}), [maintenanceScanTargetKey.value]: String(value || '') }
  }

  return {
    isSiteVisitMaintenance, maintenanceTypeMeta, maintenanceSections, maintenanceRows,
    maintenanceScanTargetKey, currentMaintenanceBarcode, maintenanceNoteLabel,
    maintenanceNote, maintenanceValidation, canScanMaintenance,
    currentMaintenancePhotos, maintenancePhotoUploading, maintenancePhotoError,
    maintenanceVisitTypeOptions, maintenanceDailyAreaOptions,
    getMaintenanceDailyAreaLabel,
    updateMaintenanceVisitType, updateMaintenanceVisitDate, updateMaintenanceVisitPeriod,
    updateMaintenanceVisitArea, cycleMaintenanceRowStatus, updateMaintenanceNote,
    normalizeBucket,
  }
}
