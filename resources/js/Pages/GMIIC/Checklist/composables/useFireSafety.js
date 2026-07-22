import { computed } from 'vue'
import { getDaysInPeriod, getFireSafetyCardTitle, getFireSafetyLocationLabel, getFireSafetyLocationOptions, getFireSafetyRecordKey, createFireSafetyLocationState, rebuildFireSafetyRows, fireSafetyCardOptions, formatDayMonthDisplay } from '../checklistConfig'

export function useFireSafety(entry, { showQrScanner }) {
  const isFireSafety = computed(() => entry.value?.template_id === 'apar_smoke_detector_fire_alarm')

  const activeFireSafetyMonth = computed(() => {
    if (!isFireSafety.value || !entry.value) return 'jan'
    return entry.value.form.active_month || 'jan'
  })

  const fireSafetyCardTitle = computed(() => {
    if (!isFireSafety.value || !entry.value) return 'KARTU PEMELIHARAAN'
    return getFireSafetyCardTitle(entry.value.form.card_type)
  })

  const fireSafetyLocationOptions = computed(() => {
    if (!isFireSafety.value || !entry.value) return []
    return getFireSafetyLocationOptions(entry.value.form.card_type)
  })

  const fireSafetyMonthNote = computed({
    get() {
      if (!isFireSafety.value || !entry.value) return ''
      return entry.value.form.monthly_notes?.[activeFireSafetyMonth.value] || ''
    },
    set(value) {
      if (!isFireSafety.value || !entry.value) return
      entry.value.form.monthly_notes = { ...(entry.value.form.monthly_notes || {}), [activeFireSafetyMonth.value]: value }
    },
  })

  const currentFireSafetyBarcode = computed(() => {
    if (!isFireSafety.value || !entry.value) return ''
    return entry.value.form.monthly_barcodes?.[activeFireSafetyMonth.value] || ''
  })

  const fireSafetyApprovedMonths = computed(() => {
    if (!isFireSafety.value || !entry.value) return []
    return Array.isArray(entry.value.form.approved_months) ? entry.value.form.approved_months : []
  })

  const fireSafetySubmittedMonths = computed(() => {
    if (!isFireSafety.value || !entry.value) return []
    return Array.isArray(entry.value.form.submitted_months) ? entry.value.form.submitted_months : []
  })

  const isActiveFireSafetyMonthApproved = computed(() => fireSafetyApprovedMonths.value.includes(activeFireSafetyMonth.value))

  const isActiveFireSafetyMonthSubmitted = computed(() => fireSafetySubmittedMonths.value.includes(activeFireSafetyMonth.value))

  const fireSafetyMonthValidation = computed(() => {
    if (!isFireSafety.value || !entry.value) return { allAnswersFilled: false, hasNoAnswer: false, hasRequiredNote: false }
    const answers = (entry.value.form.rows || []).map((row) => row.months?.[activeFireSafetyMonth.value] || '')
    const allAnswersFilled = answers.length > 0 && answers.every((answer) => answer === 'yes' || answer === 'no')
    const hasNoAnswer = answers.includes('no')
    const hasRequiredNote = String(fireSafetyMonthNote.value || '').trim() !== ''
    return { allAnswersFilled, hasNoAnswer, hasRequiredNote }
  })

  const canScanFireSafety = computed(() => {
    if (!isFireSafety.value || !entry.value || isActiveFireSafetyMonthApproved.value || !showQrScanner.value) return false
    return fireSafetyMonthValidation.value.allAnswersFilled
      && (!fireSafetyMonthValidation.value.hasNoAnswer || fireSafetyMonthValidation.value.hasRequiredNote)
      && !String(currentFireSafetyBarcode.value || '').trim()
  })

  function persistCurrentFireSafetyState() {
    if (!entry.value || !isFireSafety.value) return
    const cardType = String(entry.value.form.card_type || '').trim()
    const locationId = String(entry.value.form.location || '').trim()
    if (!cardType || !locationId) return
    const recordKey = getFireSafetyRecordKey(cardType, locationId)
    entry.value.form.location_records = {
      ...(entry.value.form.location_records || {}),
      [recordKey]: createFireSafetyLocationState(cardType, {
        submitted_months: entry.value.form.submitted_months || [],
        approved_months: entry.value.form.approved_months || [],
        monthly_hse_approved_by: entry.value.form.monthly_hse_approved_by || {},
        monthly_notes: entry.value.form.monthly_notes || {},
        monthly_barcodes: entry.value.form.monthly_barcodes || {},
        monthly_check_dates: entry.value.form.monthly_check_dates || {},
        rows: entry.value.form.rows || [],
      }),
    }
  }

  function loadFireSafetyState(cardType, locationId) {
    if (!entry.value || !isFireSafety.value) return
    const recordKey = getFireSafetyRecordKey(cardType, locationId)
    const currentState = entry.value.form.location_records?.[recordKey] || createFireSafetyLocationState(cardType)
    entry.value.form.submitted_months = [...(currentState.submitted_months || [])]
    entry.value.form.approved_months = [...(currentState.approved_months || [])]
    entry.value.form.monthly_hse_approved_by = { ...(currentState.monthly_hse_approved_by || {}) }
    entry.value.form.monthly_notes = { ...(currentState.monthly_notes || {}) }
    entry.value.form.monthly_barcodes = { ...(currentState.monthly_barcodes || {}) }
    entry.value.form.monthly_check_dates = { ...(currentState.monthly_check_dates || {}) }
    entry.value.form.rows = rebuildFireSafetyRows(cardType, currentState.rows || [])
  }

  function updateFireSafetyCardType(cardType) {
    if (!entry.value || !isFireSafety.value) return
    persistCurrentFireSafetyState()
    const nextLocation = getFireSafetyLocationOptions(cardType)[0]?.id || ''
    entry.value.form.card_type = cardType
    entry.value.form.location = nextLocation
    loadFireSafetyState(cardType, nextLocation)
  }

  function updateFireSafetyLocation(locationId) {
    if (!entry.value || !isFireSafety.value) return
    persistCurrentFireSafetyState()
    entry.value.form.location = locationId
    loadFireSafetyState(entry.value.form.card_type, locationId)
  }

  function cycleFireSafetyMonthAnswer(row, monthKey) {
    if (!row?.months || !isFireSafety.value || isActiveFireSafetyMonthApproved.value || isActiveFireSafetyMonthSubmitted.value || monthKey !== activeFireSafetyMonth.value) return
    const currentValue = row.months?.[monthKey] || ''
    const nextValue = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : ''
    row.months = { ...row.months, [monthKey]: nextValue }
  }

  function updateFireSafetyMonthNote(value) { fireSafetyMonthNote.value = value }
  function setFireSafetyActiveMonth(monthKey) {
    if (!entry.value || !isFireSafety.value) return
    entry.value.form.active_month = monthKey
  }

  return {
    isFireSafety, activeFireSafetyMonth, fireSafetyCardTitle, fireSafetyLocationOptions,
    fireSafetyMonthNote, currentFireSafetyBarcode, fireSafetyApprovedMonths, fireSafetySubmittedMonths,
    isActiveFireSafetyMonthApproved, isActiveFireSafetyMonthSubmitted, fireSafetyMonthValidation, canScanFireSafety,
    fireSafetyCardOptions,
    persistCurrentFireSafetyState, updateFireSafetyCardType, updateFireSafetyLocation,
    cycleFireSafetyMonthAnswer, updateFireSafetyMonthNote, setFireSafetyActiveMonth,
    getFireSafetyLocationLabel,
  }
}
