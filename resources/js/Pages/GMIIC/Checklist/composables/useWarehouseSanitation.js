import { computed } from 'vue'
import { warehouseAreaOptions, buildWarehouseAreaRows, toPeriodValue, formatDateDisplay } from '../checklistConfig'

export function useWarehouseSanitation(entry, { canApproveWarehouseFinal, showQrScanner }) {
  const isWarehouseSanitation = computed(() => entry.value?.template_id === 'warehouse_sanitation_1')

  const warehouseTargetKey = computed(() => {
    if (!isWarehouseSanitation.value || !entry.value) return ''
    return 'warehouse_sanitation'
  })

  const warehousePreparedApproved = computed(() => {
    if (!isWarehouseSanitation.value || !entry.value) return false
    return Boolean(entry.value.form.verification?.prepared_date)
  })

  const warehouseManagerApproved = computed(() => {
    if (!isWarehouseSanitation.value || !entry.value) return false
    return Boolean(entry.value.form.verification?.verified_date)
  })

  const currentWarehouseBarcode = computed(() => {
    if (!isWarehouseSanitation.value || !entry.value) return ''
    return String(entry.value.form.barcode || '').trim()
  })

  const currentWarehouseScanDate = computed(() => {
    if (!isWarehouseSanitation.value || !entry.value) return ''
    return String(entry.value.form.scan_date || '').trim()
  })

  const canScanWarehouseBarcode = computed(() => {
    if (!isWarehouseSanitation.value || !entry.value || warehousePreparedApproved.value || !showQrScanner.value) return false
    const hasValidSchedule = entry.value.form.frequency === 'monthly' ? Boolean(String(entry.value.form.period || '').trim()) : Boolean(String(entry.value.form.date || '').trim())
    const generalCompleted = Boolean(hasValidSchedule && Array.isArray(entry.value.form.selected_areas) && entry.value.form.selected_areas.length && String(entry.value.form.room_temperature || '').trim() && String(entry.value.form.petugas || '').trim() && String(entry.value.form.hse || '').trim())
    const areaRowsCompleted = (entry.value.form.area_rows || []).every((row) => row.clean_condition && row.no_ice_pooling && row.no_odor)
    const iceControlCompleted = (entry.value.form.ice_control_rows || []).every((row) => row.status)
    const cleaningMaterialCompleted = (entry.value.form.cleaning_material_rows || []).every((row) => String(row.material_name || '').trim() && row.halal && row.dosage)
    return generalCompleted && areaRowsCompleted && iceControlCompleted && cleaningMaterialCompleted && !currentWarehouseBarcode.value
  })

  const warehouseApprovalButtonLabel = computed(() => {
    if (!isWarehouseSanitation.value || !entry.value) return 'Approval'
    if (warehouseManagerApproved.value) return 'Approved'
    if (!warehousePreparedApproved.value) return 'Approval Petugas'
    return canApproveWarehouseFinal.value ? 'Approval Manager / HSE' : 'Menunggu Manager / HSE'
  })

  const warehouseNoteLabel = computed(() => {
    if (!isWarehouseSanitation.value || !entry.value) return 'Keterangan'
    return 'Keterangan'
  })

  const warehouseNote = computed({
    get() {
      if (!isWarehouseSanitation.value || !entry.value) return ''
      return entry.value.form.area_notes?.[warehouseTargetKey.value] || ''
    },
    set(value) {
      if (!isWarehouseSanitation.value || !entry.value) return
      entry.value.form.area_notes = {
        ...(entry.value.form.area_notes || {}),
        [warehouseTargetKey.value]: value
      }
    },
  })

  const warehouseValidation = computed(() => {
    if (!isWarehouseSanitation.value || !entry.value) return { allAnswersFilled: false, hasNoAnswer: false, hasRequiredNote: false }
    const areaRows = entry.value.form.area_rows || []
    const iceRows = entry.value.form.ice_control_rows || []
    const materialRows = entry.value.form.cleaning_material_rows || []
    const hasValidSchedule = entry.value.form.frequency === 'monthly' ? Boolean(String(entry.value.form.period || '').trim()) : Boolean(String(entry.value.form.date || '').trim())
    const generalCompleted = Boolean(hasValidSchedule && Array.isArray(entry.value.form.selected_areas) && entry.value.form.selected_areas.length && String(entry.value.form.room_temperature || '').trim() && String(entry.value.form.petugas || '').trim() && String(entry.value.form.hse || '').trim())
    const areaRowsCompleted = areaRows.length > 0 && areaRows.every((row) => row.clean_condition && row.no_ice_pooling && row.no_odor)
    const iceControlCompleted = iceRows.length > 0 && iceRows.every((row) => row.status)
    const cleaningMaterialCompleted = materialRows.length > 0 && materialRows.every((row) => String(row.material_name || '').trim() && row.halal && row.dosage)
    const allAnswersFilled = generalCompleted && areaRowsCompleted && iceControlCompleted && cleaningMaterialCompleted
    const areaStatuses = areaRows.flatMap((row) => [row.clean_condition, row.no_ice_pooling, row.no_odor])
    const iceStatuses = iceRows.map((row) => row.status)
    const hasNoAnswer = [...areaStatuses, ...iceStatuses].some((s) => s === 'no' || s === 'tidak_sesuai')
    const hasRequiredNote = String(warehouseNote.value || '').trim() !== ''
    return { allAnswersFilled, hasNoAnswer, hasRequiredNote }
  })

  const currentWarehousePhotos = computed(() => {
    if (!isWarehouseSanitation.value || !entry.value) return []
    const paths = normalizeWarehousePhotoBucket(entry.value.form.area_photo_paths?.[warehouseTargetKey.value])
    const urls = normalizeWarehousePhotoBucket(entry.value.form.area_photo_urls?.[warehouseTargetKey.value])
    const names = normalizeWarehousePhotoBucket(entry.value.form.area_photo_names?.[warehouseTargetKey.value])
    const length = Math.max(paths.length, urls.length, names.length)
    return Array.from({ length }, (_, i) => ({ path: paths[i] || '', url: urls[i] || '', name: names[i] || '' })).filter((p) => String(p.url || p.path || '').trim() !== '')
  })

  function normalizeWarehousePhotoBucket(bucket) {
    if (Array.isArray(bucket)) return bucket.filter((item) => String(item || '').trim() !== '')
    const single = String(bucket || '').trim()
    return single ? [single] : []
  }

  function toggleWarehouseArea(areaId) {
    if (!entry.value || !isWarehouseSanitation.value) return
    const selectedAreas = Array.isArray(entry.value.form.selected_areas) ? entry.value.form.selected_areas : []
    const exists = selectedAreas.includes(areaId)
    entry.value.form.selected_areas = exists ? selectedAreas.filter((item) => item !== areaId) : [...selectedAreas, areaId]
  }

  function updateWarehouseGeneralField(field, value) {
    if (!entry.value || !isWarehouseSanitation.value) return
    entry.value.form[field] = value
    if (field === 'period' || field === 'date') { entry.value.form.barcode = ''; entry.value.form.scan_date = '' }
  }

  function updateWarehouseFrequency(value) {
    if (!entry.value || !isWarehouseSanitation.value) return
    entry.value.form.frequency = value
    entry.value.form.barcode = ''
    entry.value.form.scan_date = ''
    entry.value.form.area_rows = buildWarehouseAreaRows(value, entry.value.form.area_rows || [])
    if (value === 'monthly' && !String(entry.value.form.period || '').trim()) entry.value.form.period = toPeriodValue(new Date())
    if (value === 'daily' && !String(entry.value.form.date || '').trim()) entry.value.form.date = formatDateDisplay(new Date())
  }

  function setWarehouseAreaRowStatus(rowId, field, value) {
    if (!entry.value || !isWarehouseSanitation.value) return
    entry.value.form.area_rows = (entry.value.form.area_rows || []).map((row) => (row.id === rowId ? { ...row, [field]: value } : row))
  }

  function setWarehouseAreaRowNote(rowId, value) { setWarehouseAreaRowStatus(rowId, 'note', value) }

  function setWarehouseIceControlStatus(rowId, value) {
    if (!entry.value || !isWarehouseSanitation.value) return
    entry.value.form.ice_control_rows = (entry.value.form.ice_control_rows || []).map((row) => (row.id === rowId ? { ...row, status: value } : row))
  }

  function setWarehouseIceControlNote(rowId, value) {
    if (!entry.value || !isWarehouseSanitation.value) return
    entry.value.form.ice_control_rows = (entry.value.form.ice_control_rows || []).map((row) => (row.id === rowId ? { ...row, note: value } : row))
  }

  function setWarehouseCleaningMaterialField(rowId, field, value) {
    if (!entry.value || !isWarehouseSanitation.value) return
    entry.value.form.cleaning_material_rows = (entry.value.form.cleaning_material_rows || []).map((row) => (row.id === rowId ? { ...row, [field]: value } : row))
  }

  function syncWarehouseAreaRows() {
    if (!entry.value || !isWarehouseSanitation.value) return
    entry.value.form.area_rows = buildWarehouseAreaRows(entry.value.form.frequency || 'daily', entry.value.form.area_rows || [])
  }

  return {
    isWarehouseSanitation, warehouseTargetKey, warehousePreparedApproved, warehouseManagerApproved,
    currentWarehouseBarcode, currentWarehouseScanDate, canScanWarehouseBarcode,
    warehouseApprovalButtonLabel, warehouseAreaOptions,
    warehouseNoteLabel, warehouseNote, warehouseValidation, currentWarehousePhotos,
    normalizeWarehousePhotoBucket,
    toggleWarehouseArea, updateWarehouseGeneralField, updateWarehouseFrequency,
    setWarehouseAreaRowStatus, setWarehouseAreaRowNote, setWarehouseIceControlStatus,
    setWarehouseIceControlNote, setWarehouseCleaningMaterialField, syncWarehouseAreaRows,
  }
}
