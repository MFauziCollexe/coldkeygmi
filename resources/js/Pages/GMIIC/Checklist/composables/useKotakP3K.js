import { computed } from 'vue'
import { getDaysInPeriod, getKotakP3KMonthLabel, kotakP3KMonths, getLocationLabel, getLocationBarcodeAliases, locationOptions } from '../checklistConfig'

export function useKotakP3K(entry, { showQrScanner }) {
  const isKotakP3K = computed(() => entry.value?.template_id === 'kotak_p3k')

  const activeKotakP3KMonth = computed(() => {
    if (!isKotakP3K.value || !entry.value) return 'jan'
    return entry.value.form.active_month || 'jan'
  })

  const kotakP3KApprovedMonths = computed(() => {
    if (!isKotakP3K.value || !entry.value) return []
    return Array.isArray(entry.value.form.approved_months) ? entry.value.form.approved_months : []
  })

  const kotakP3KSubmittedMonths = computed(() => {
    if (!isKotakP3K.value || !entry.value) return []
    return Array.isArray(entry.value.form.submitted_months) ? entry.value.form.submitted_months : []
  })

  const isActiveKotakP3KMonthApproved = computed(() => kotakP3KApprovedMonths.value.includes(activeKotakP3KMonth.value))
  const isActiveKotakP3KMonthSubmitted = computed(() => kotakP3KSubmittedMonths.value.includes(activeKotakP3KMonth.value))
  const isActiveKotakP3KMonthLocked = computed(() => isActiveKotakP3KMonthSubmitted.value || isActiveKotakP3KMonthApproved.value)

  const kotakP3KActiveMonthStatusLabel = computed(() => {
    if (isActiveKotakP3KMonthApproved.value) return 'Approved'
    if (isActiveKotakP3KMonthSubmitted.value) return 'Waiting HSE'
    return 'Pending'
  })

  const kotakP3KMonthNote = computed({
    get() {
      if (!isKotakP3K.value || !entry.value) return ''
      return entry.value.form.monthly_notes?.[activeKotakP3KMonth.value] || ''
    },
    set(value) {
      if (!isKotakP3K.value || !entry.value) return
      entry.value.form.monthly_notes = { ...(entry.value.form.monthly_notes || {}), [activeKotakP3KMonth.value]: value }
    },
  })

  const currentKotakP3KBarcode = computed(() => {
    if (!isKotakP3K.value || !entry.value) return ''
    return entry.value.form.monthly_barcodes?.[activeKotakP3KMonth.value] || ''
  })

  const kotakP3KMonthValidation = computed(() => {
    if (!isKotakP3K.value || !entry.value) return { allAnswersFilled: false, hasNoAnswer: false, hasRequiredNote: false, canScan: false }
    const answers = entry.value.form.items.map((item) => item.months?.[activeKotakP3KMonth.value] || '')
    const allAnswersFilled = answers.every((answer) => answer === 'yes' || answer === 'no')
    const hasNoAnswer = answers.includes('no')
    const hasRequiredNote = String(kotakP3KMonthNote.value || '').trim() !== ''
    const canScan = !isActiveKotakP3KMonthLocked.value && showQrScanner.value && allAnswersFilled && (!hasNoAnswer || hasRequiredNote)
    return { allAnswersFilled, hasNoAnswer, hasRequiredNote, canScan }
  })

  const kotakP3KApprovalButtonLabel = computed(() => {
    if (!isKotakP3K.value || !entry.value) return 'Approval'
    if (isActiveKotakP3KMonthApproved.value) return 'Approved'
    if (isActiveKotakP3KMonthSubmitted.value) return 'Approval HSE'
    return 'Approval'
  })

  function toggleLocationMenu(locationMenuOpen) {
    locationMenuOpen.value = !locationMenuOpen.value
  }

  function selectLocation(locationId) {
    if (entry.value && isKotakP3K.value) entry.value.form.location = locationId
  }

  function setKotakP3KActiveMonth(monthKey) {
    if (!entry.value || !isKotakP3K.value) return
    entry.value.form.active_month = monthKey
  }

  function cycleKotakP3KMonthAnswer(item, monthKey) {
    if (!item?.months || isActiveKotakP3KMonthApproved.value || monthKey !== activeKotakP3KMonth.value) return
    const currentValue = item.months?.[monthKey] || ''
    const nextValue = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : ''
    item.months = { ...item.months, [monthKey]: nextValue }
  }

  function updateKotakP3KMonthNote(value) { kotakP3KMonthNote.value = value }

  return {
    isKotakP3K, activeKotakP3KMonth, kotakP3KApprovedMonths, kotakP3KSubmittedMonths,
    isActiveKotakP3KMonthApproved, isActiveKotakP3KMonthSubmitted, isActiveKotakP3KMonthLocked,
    kotakP3KActiveMonthStatusLabel, kotakP3KMonthNote, currentKotakP3KBarcode,
    kotakP3KMonthValidation, kotakP3KApprovalButtonLabel,
    kotakP3KMonths, locationOptions, getLocationLabel, getKotakP3KMonthLabel,
    toggleLocationMenu, selectLocation, setKotakP3KActiveMonth,
    cycleKotakP3KMonthAnswer, updateKotakP3KMonthNote,
  }
}
