import { computed } from 'vue'
import { rebuildRunningGensetRows, formatDateInputDisplay } from '../checklistConfig'

export function useRunningGenset(entry, { currentUser, showQrScanner }) {
  const isRunningGenset = computed(() => entry.value?.template_id === 'running_genset')

  const runningGensetRows = computed(() => {
    if (!isRunningGenset.value || !entry.value) return []
    return Array.isArray(entry.value.form.rows) ? entry.value.form.rows : []
  })

  const runningGensetNote = computed({
    get() {
      if (!isRunningGenset.value || !entry.value) return ''
      return entry.value.form.area_notes?.genset || ''
    },
    set(value) {
      if (!isRunningGenset.value || !entry.value) return
      entry.value.form.area_notes = { ...(entry.value.form.area_notes || {}), genset: value }
    },
  })

  const currentRunningGensetBarcode = computed(() => {
    if (!isRunningGenset.value || !entry.value) return ''
    return entry.value.form.area_barcodes?.genset || ''
  })

  const currentRunningGensetScanDate = computed(() => {
    if (!isRunningGenset.value || !entry.value) return ''
    return entry.value.form.area_scan_dates?.genset || ''
  })

  const isRunningGensetApproved = computed(() => Boolean(entry.value?.form?.approved))

  const runningGensetValidation = computed(() => {
    if (!isRunningGenset.value || !entry.value) return { hasHourMeter: false, allAnswersFilled: false, hasNoAnswer: false, hasRequiredNote: false, canScan: false }
    const validStatuses = ['yes', 'no', 'minus']
    const statuses = runningGensetRows.value.map((row) => row.status || '')
    const hasHourMeter = String(entry.value.form.hour_meter || '').trim() !== ''
    const allAnswersFilled = statuses.length > 0 && statuses.every((status) => validStatuses.includes(status))
    const hasNoAnswer = statuses.includes('no')
    const hasRequiredNote = String(runningGensetNote.value || '').trim() !== ''
    const canScan = !isRunningGensetApproved.value && showQrScanner.value
      && Boolean(String(entry.value.form.date_value || '').trim()) && hasHourMeter
      && allAnswersFilled && (!hasNoAnswer || hasRequiredNote)
      && !String(currentRunningGensetBarcode.value || '').trim()
    return { hasHourMeter, allAnswersFilled, hasNoAnswer, hasRequiredNote, canScan }
  })

  function updateRunningGensetDate(value) {
    if (!entry.value || !isRunningGenset.value) return
    entry.value.form.date_value = value
    entry.value.form.date = formatDateInputDisplay(value)
    entry.value.form.area_barcodes = {}
    entry.value.form.area_scan_dates = {}
    entry.value.form.approved = false
  }

  function updateRunningGensetHourMeter(value) {
    if (!entry.value || !isRunningGenset.value) return
    entry.value.form.hour_meter = String(value || '')
  }

  function updateRunningGensetNote(value) { runningGensetNote.value = value }

  function cycleRunningGensetRowStatus({ rowId }) {
    if (!entry.value || !isRunningGenset.value || isRunningGensetApproved.value) return
    const statusOrder = ['', 'yes', 'no', 'minus']
    entry.value.form.rows = (entry.value.form.rows || []).map((row) => {
      if (row.id !== rowId) return row
      const currentValue = row.status || ''
      const currentIndex = statusOrder.indexOf(currentValue)
      const nextValue = statusOrder[(currentIndex + 1) % statusOrder.length]
      return { ...row, status: nextValue }
    })
  }

  return {
    isRunningGenset, runningGensetRows, runningGensetNote, currentRunningGensetBarcode,
    currentRunningGensetScanDate, isRunningGensetApproved, runningGensetValidation,
    updateRunningGensetDate, updateRunningGensetHourMeter, updateRunningGensetNote,
    cycleRunningGensetRowStatus,
  }
}
