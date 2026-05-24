import { computed } from 'vue'
import { rebuildWasteTransportRows } from '../checklistConfig'

export function useWasteTransport(entry) {
  const isWasteTransport = computed(() => entry.value?.template_id === 'pengangkutan_sampah_pt_sier')

  const wasteTransportRows = computed(() => {
    if (!isWasteTransport.value || !entry.value) return []
    return Array.isArray(entry.value.form.rows) ? entry.value.form.rows : []
  })

  const wasteTransportApprovedDays = computed(() => {
    if (!isWasteTransport.value || !entry.value) return []
    return Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days : []
  })

  const nextPendingWasteTransportDay = computed(() => {
    if (!isWasteTransport.value || !entry.value) return null
    return wasteTransportRows.value.find((row) => {
      if (wasteTransportApprovedDays.value.includes(row.day)) return false
      return Boolean(row.pickup_time || row.handover_name || row.collector_name || row.collector_photo_name)
    }) || null
  })

  function updateWasteTransportRow(day, field, value) {
    if (!entry.value || !isWasteTransport.value) return
    entry.value.form.rows = wasteTransportRows.value.map((row) => (row.day === day ? { ...row, [field]: value } : row))
  }

  function rebuildWasteTransportEntryRows() {
    if (!entry.value || !isWasteTransport.value) return
    entry.value.form.rows = rebuildWasteTransportRows(entry.value.form.period, entry.value.form.rows || [])
  }

  return { isWasteTransport, wasteTransportRows, wasteTransportApprovedDays, nextPendingWasteTransportDay, updateWasteTransportRow, rebuildWasteTransportEntryRows }
}
