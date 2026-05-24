import { computed } from 'vue'
import { warehouseAreaOptions, buildWarehouseAreaRows, toPeriodValue, formatDateDisplay } from '../checklistConfig'

export function useWarehouseSanitation(entry, { canApproveWarehouseFinal, showQrScanner }) {
  const isWarehouseSanitation = computed(() => entry.value?.template_id === 'warehouse_sanitation_1')

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
    isWarehouseSanitation, warehousePreparedApproved, warehouseManagerApproved,
    currentWarehouseBarcode, currentWarehouseScanDate, canScanWarehouseBarcode,
    warehouseApprovalButtonLabel, warehouseAreaOptions,
    toggleWarehouseArea, updateWarehouseGeneralField, updateWarehouseFrequency,
    setWarehouseAreaRowStatus, setWarehouseAreaRowNote, setWarehouseIceControlStatus,
    setWarehouseIceControlNote, setWarehouseCleaningMaterialField, syncWarehouseAreaRows,
  }
}
