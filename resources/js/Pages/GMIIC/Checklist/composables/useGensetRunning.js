import { computed } from 'vue'
import { rebuildGensetRunningRows, formatWeekDisplay, toWeekInputValue, formatDateDisplay } from '../checklistConfig'

export function useGensetRunning(entry, { currentUser, showQrScanner }) {
  const isGensetRunning = computed(() => entry.value?.template_id === 'genset_running')

  const gensetRunningRows = computed(() => {
    if (!isGensetRunning.value || !entry.value) return []
    return Array.isArray(entry.value.form.rows) ? entry.value.form.rows : []
  })

  const gensetRunningNote = computed({
    get() {
      if (!isGensetRunning.value || !entry.value) return ''
      return entry.value.form.area_notes?.genset || ''
    },
    set(value) {
      if (!isGensetRunning.value || !entry.value) return
      entry.value.form.area_notes = { ...(entry.value.form.area_notes || {}), genset: value }
    },
  })

  const currentGensetRunningBarcode = computed(() => {
    if (!isGensetRunning.value || !entry.value) return ''
    return entry.value.form.area_barcodes?.genset || ''
  })

  const currentGensetRunningScanDate = computed(() => {
    if (!isGensetRunning.value || !entry.value) return ''
    return entry.value.form.area_scan_dates?.genset || ''
  })

  const isGensetRunningApproved = computed(() => Boolean(entry.value?.form?.approved))
  const gensetRunningStatusLabel = computed(() => isGensetRunningApproved.value ? 'Approved' : 'Pending')

  const gensetRunningValidation = computed(() => {
    if (!isGensetRunning.value || !entry.value) return { allAnswersFilled: false, hasNoAnswer: false, hasRequiredNote: false, canScan: false }
    const answers = gensetRunningRows.value.map((row) => row.status || '')
    const allAnswersFilled = answers.length > 0 && answers.every((answer) => answer === 'yes' || answer === 'no')
    const hasNoAnswer = answers.includes('no')
    const hasRequiredNote = String(gensetRunningNote.value || '').trim() !== ''
    const canScan = !isGensetRunningApproved.value && showQrScanner.value && allAnswersFilled && (!hasNoAnswer || hasRequiredNote) && !String(currentGensetRunningBarcode.value || '').trim()
    return { allAnswersFilled, hasNoAnswer, hasRequiredNote, canScan }
  })

  function updateGensetRunningPeriod(value) {
    if (!entry.value || !isGensetRunning.value) return
    const nextValue = String(value || '').trim()
    entry.value.form.period_value = nextValue
    entry.value.form.period = nextValue ? formatWeekDisplay(nextValue) : ''
  }

  function updateGensetRunningNote(value) { gensetRunningNote.value = value }

  function cycleGensetRunningRowStatus({ rowId }) {
    if (!entry.value || !isGensetRunning.value || isGensetRunningApproved.value) return
    entry.value.form.rows = (entry.value.form.rows || []).map((row) => {
      if (row.id !== rowId) return row
      const currentValue = row.status || ''
      const nextValue = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : ''
      return { ...row, status: nextValue }
    })
  }

  return {
    isGensetRunning, gensetRunningRows, gensetRunningNote, currentGensetRunningBarcode,
    currentGensetRunningScanDate, isGensetRunningApproved, gensetRunningStatusLabel,
    gensetRunningValidation, updateGensetRunningPeriod, updateGensetRunningNote,
    cycleGensetRunningRowStatus,
  }
}
