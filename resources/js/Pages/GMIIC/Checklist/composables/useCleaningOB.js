import { computed } from 'vue'
import { cleaningOBShiftOptions, getCleaningOBShiftLabel, rebuildCleaningOBSections, formatDateInputDisplay } from '../checklistConfig'

export function useCleaningOB(entry, { currentUser, showQrScanner }) {
  const isCleaningOB = computed(() => entry.value?.template_id === 'jadwal_cleaning_ob')

  const cleaningOBApprovedAreas = computed(() => {
    if (!isCleaningOB.value || !entry.value) return []
    return Array.isArray(entry.value.form.approved_areas) ? entry.value.form.approved_areas : []
  })

  const currentCleaningOBSections = computed(() => {
    if (!isCleaningOB.value || !entry.value) return []
    const shiftId = String(entry.value.form.selected_shift || '').trim()
    const shift = (entry.value.form.sections || []).find((s) => s.id === shiftId)
    return shift?.sections || []
  })

  const cleaningOBTargetKey = computed(() => {
    if (!isCleaningOB.value || !entry.value) return ''
    return String(entry.value.form.selected_shift || '').trim()
  })

  const currentCleaningOBBarcode = computed(() => {
    if (!isCleaningOB.value || !entry.value) return ''
    return entry.value.form.area_barcodes?.[cleaningOBTargetKey.value] || ''
  })

  const cleaningOBNoteLabel = computed(() => {
    if (!isCleaningOB.value || !entry.value) return 'Keterangan'
    return `Keterangan ${getCleaningOBShiftLabel(entry.value.form.selected_shift)}`
  })

  const cleaningOBNote = computed({
    get() {
      if (!isCleaningOB.value || !entry.value) return ''
      return entry.value.form.area_notes?.[cleaningOBTargetKey.value] || ''
    },
    set(value) {
      if (!isCleaningOB.value || !entry.value) return
      entry.value.form.area_notes = { ...(entry.value.form.area_notes || {}), [cleaningOBTargetKey.value]: value }
    },
  })

  const cleaningOBValidation = computed(() => {
    if (!isCleaningOB.value || !entry.value) return { allAnswersFilled: false, hasNoAnswer: false, hasRequiredNote: false }
    const activeItems = currentCleaningOBSections.value.flatMap((section) => section.items || [])
    const statuses = activeItems.map((row) => row.status || '')
    const allAnswersFilled = activeItems.length > 0 && statuses.every((status) => status === 'yes' || status === 'no')
    const hasNoAnswer = statuses.includes('no')
    const hasRequiredNote = String(cleaningOBNote.value || '').trim() !== ''
    return { allAnswersFilled, hasNoAnswer, hasRequiredNote }
  })

  const canScanCleaningOB = computed(() => {
    if (!isCleaningOB.value || !entry.value || !showQrScanner.value) return false
    const selectedShift = String(entry.value.form.selected_shift || '').trim()
    return Boolean(String(entry.value.form.date_value || '').trim()) && Boolean(selectedShift)
      && !cleaningOBApprovedAreas.value.includes(selectedShift)
      && cleaningOBValidation.value.allAnswersFilled
      && (!cleaningOBValidation.value.hasNoAnswer || cleaningOBValidation.value.hasRequiredNote)
      && !String(currentCleaningOBBarcode.value || '').trim()
  })

  function findOpenCleaningOBDraft(entries = []) {
    return (Array.isArray(entries) ? entries : []).find((c) => c?.template_id === 'jadwal_cleaning_ob' && !Boolean(c?.form?.approved)) || null
  }

  function updateCleaningOBDate(value) {
    if (!entry.value || !isCleaningOB.value) return
    entry.value.form.date_value = value
    entry.value.form.date = formatDateInputDisplay(value)
    entry.value.form.approved_areas = []
    entry.value.form.area_barcodes = {}
    entry.value.form.area_notes = {}
    entry.value.form.area_scan_dates = {}
    entry.value.form.approved = false
    entry.value.form.sections = rebuildCleaningOBSections([])
  }

  function updateCleaningOBShift(value) {
    if (!entry.value || !isCleaningOB.value) return
    entry.value.form.selected_shift = value
  }

  function updateCleaningOBNote(value) {
    if (!entry.value || !isCleaningOB.value || !cleaningOBTargetKey.value) return
    entry.value.form.area_notes = { ...(entry.value.form.area_notes || {}), [cleaningOBTargetKey.value]: String(value || '') }
  }

  function cycleCleaningOBRowStatus(sectionId, itemId) {
    if (!entry.value || !isCleaningOB.value) return
    if (cleaningOBApprovedAreas.value.includes(String(entry.value.form.selected_shift || '').trim())) return
    entry.value.form.sections = (entry.value.form.sections || []).map((shift) => {
      if (shift.id !== entry.value.form.selected_shift) return shift
      return {
        ...shift,
        sections: (shift.sections || []).map((section) => {
          if (section.id !== sectionId) return section
          return {
            ...section,
            items: (section.items || []).map((item) => {
              if (item.id !== itemId) return item
              const currentValue = item.status || ''
              const nextValue = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : ''
              return { ...item, status: nextValue }
            }),
          }
        }),
      }
    })
  }

  return {
    isCleaningOB, cleaningOBApprovedAreas,
    currentCleaningOBSections, cleaningOBTargetKey, currentCleaningOBBarcode,
    cleaningOBNoteLabel, cleaningOBNote, cleaningOBValidation,
    canScanCleaningOB, cleaningOBShiftOptions, getCleaningOBShiftLabel,
    findOpenCleaningOBDraft, updateCleaningOBDate, updateCleaningOBShift,
    updateCleaningOBNote, cycleCleaningOBRowStatus,
  }
}
