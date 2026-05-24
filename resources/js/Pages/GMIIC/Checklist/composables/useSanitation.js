import { computed } from 'vue'
import { getDaysInPeriod, getSanitationAreaLabel, sanitationAreaOptions, rebuildSanitationRows, rebuildAllSanitationRowsByArea } from '../checklistConfig'

export function useSanitation(entry, { currentUser, canApproveCurrentTemplate, showQrScanner }) {
  const isSanitation = computed(() => entry.value?.template_id === 'non_warehouse_sanitation')

  const sanitationDays = computed(() => {
    if (!isSanitation.value || !entry.value) return []
    return getDaysInPeriod(entry.value.form.period)
  })

  const currentSanitationRows = computed(() => {
    if (!isSanitation.value || !entry.value) return []
    const areaRows = entry.value.form.rows_by_area?.[entry.value.form.area]
    return Array.isArray(areaRows) ? areaRows : []
  })

  const sanitationApprovedDays = computed(() => {
    if (!isSanitation.value || !entry.value) return []
    return Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days : []
  })

  const sanitationSubmittedDays = computed(() => {
    if (!isSanitation.value || !entry.value) return []
    return Array.isArray(entry.value.form.submitted_days) ? entry.value.form.submitted_days : []
  })

  const currentSanitationApprovalRequest = computed(() => {
    if (!isSanitation.value || !entry.value || !nextPendingSanitationDay.value) return null
    return entry.value.form.approval_requests_by_day?.[nextPendingSanitationDay.value.day] || null
  })

  const nextPendingSanitationDay = computed(() => {
    if (!isSanitation.value || !entry.value) return null
    return sanitationDays.value.find((day) => {
      if (day.isSunday || sanitationApprovedDays.value.includes(day.day)) return false
      return sanitationAreaOptions.some((area) => {
        const areaRows = entry.value.form.rows_by_area?.[area.id] || []
        return areaRows.some((row) => Boolean(row.days?.[day.day]))
      })
    }) || null
  })

  const isNextPendingSanitationDaySubmitted = computed(() => {
    if (!isSanitation.value || !nextPendingSanitationDay.value) return false
    return sanitationSubmittedDays.value.includes(nextPendingSanitationDay.value.day)
  })

  const canApprovePendingSanitationSubmission = computed(() => {
    if (!isSanitation.value || !isNextPendingSanitationDaySubmitted.value || !canApproveCurrentTemplate.value) return false
    const currentUserId = Number(currentUser.value?.id || 0)
    const submittedById = Number(currentSanitationApprovalRequest.value?.submitted_by_id || 0)
    return currentUserId > 0 && currentUserId !== submittedById
  })

  const currentSanitationScan = computed(() => {
    if (!isSanitation.value || !entry.value || !nextPendingSanitationDay.value) return null
    const dayScans = entry.value.form.area_scans_by_day?.[nextPendingSanitationDay.value.day] || {}
    return dayScans?.[entry.value.form.area] || null
  })

  const currentSanitationAreaCompleted = computed(() => {
    if (!isSanitation.value || !entry.value || !nextPendingSanitationDay.value || !currentSanitationRows.value.length) return false
    return currentSanitationRows.value.every((row) => Boolean(row.days?.[nextPendingSanitationDay.value.day]))
  })

  const canScanSanitationArea = computed(() => {
    if (!isSanitation.value || !entry.value || !nextPendingSanitationDay.value || !showQrScanner.value) return false
    if (currentSanitationScan.value?.barcode) return false
    return currentSanitationAreaCompleted.value
  })

  const sanitationCompletedDays = computed(() => {
    if (!isSanitation.value || !sanitationDays.value.length || !entry.value) return []
    return sanitationDays.value.filter((day) => {
      if (day.isSunday) return false
      return sanitationAreaOptions.every((area) => {
        const areaRows = entry.value.form.rows_by_area?.[area.id] || []
        if (!areaRows.length) return false
        return areaRows.every((row) => Boolean(row.days?.[day.day]))
      })
    })
  })

  const sanitationApprovalButtonLabel = computed(() => {
    if (!isSanitation.value || !entry.value) return 'Approval'
    if (!nextPendingSanitationDay.value) return Boolean(entry.value.form.approved) ? 'Approved' : 'Approval'
    if (isNextPendingSanitationDaySubmitted.value) return canApprovePendingSanitationSubmission.value ? 'Approve HSE' : 'Menunggu HSE'
    return 'Kirim Approval'
  })

  const sanitationNoteTargetKey = computed(() => {
    if (!isSanitation.value || !entry.value || !nextPendingSanitationDay.value) return ''
    return [String(entry.value.form.period || '').trim(), String(nextPendingSanitationDay.value.day || '').trim(), String(entry.value.form.area || '').trim()].join('__')
  })

  const sanitationNote = computed(() => {
    if (!isSanitation.value || !entry.value || !sanitationNoteTargetKey.value) return ''
    return entry.value.form.area_notes?.[sanitationNoteTargetKey.value] || ''
  })

  const sanitationNoteLabel = computed(() => {
    if (!isSanitation.value || !entry.value || !nextPendingSanitationDay.value) return 'Keterangan'
    return `Keterangan Hari ${nextPendingSanitationDay.value.day} - ${getSanitationAreaLabel(entry.value.form.area)}`
  })

  const canEditSanitationNote = computed(() => Boolean(isSanitation.value && entry.value && nextPendingSanitationDay.value))

  function getSanitationBusinessDays(periodValue) {
    return getDaysInPeriod(periodValue).filter((day) => !day.isSunday).map((day) => day.day).sort((a, b) => a - b)
  }

  function isSanitationChecklistFullyApproved(targetEntry) {
    const businessDays = getSanitationBusinessDays(String(targetEntry?.form?.period || '').trim())
    const approvedDays = Array.isArray(targetEntry?.form?.approved_days) ? targetEntry.form.approved_days : []
    return businessDays.length > 0 && businessDays.every((day) => approvedDays.includes(day))
  }

  function findOpenSanitationDraft(entries = []) {
    return (Array.isArray(entries) ? entries : []).find((c) => c?.template_id === 'non_warehouse_sanitation' && !Boolean(c?.form?.approved) && !(Array.isArray(c?.form?.submitted_days) && c.form.submitted_days.length > 0)) || null
  }

  function toggleSanitationDay(row, day) {
    if (!row?.days || sanitationApprovedDays.value.includes(day)) return
    row.days[day] = !row.days[day]
  }

  function updateSanitationNote(value, entry) {
    if (!entry.value || !isSanitation.value || !sanitationNoteTargetKey.value) return
    entry.value.form.area_notes = { ...(entry.value.form.area_notes || {}), [sanitationNoteTargetKey.value]: String(value || '') }
  }

  function rebuildSanitationEntryRows() {
    if (!entry.value || !isSanitation.value) return
    entry.value.form.rows_by_area = rebuildAllSanitationRowsByArea(entry.value.form.period, entry.value.form.rows_by_area || {})
    if (!Array.isArray(entry.value.form.rows_by_area?.[entry.value.form.area])) {
      entry.value.form.rows_by_area = { ...entry.value.form.rows_by_area, [entry.value.form.area]: rebuildSanitationRows(entry.value.form.area, entry.value.form.period, []) }
    }
  }

  return {
    isSanitation, sanitationDays, currentSanitationRows, sanitationApprovedDays, sanitationSubmittedDays,
    currentSanitationApprovalRequest, nextPendingSanitationDay, isNextPendingSanitationDaySubmitted,
    canApprovePendingSanitationSubmission, currentSanitationScan, currentSanitationAreaCompleted,
    canScanSanitationArea, sanitationCompletedDays, sanitationApprovalButtonLabel,
    sanitationNoteTargetKey, sanitationNote, sanitationNoteLabel, canEditSanitationNote,
    sanitationAreaOptions, getSanitationAreaLabel, getSanitationBusinessDays,
    isSanitationChecklistFullyApproved, findOpenSanitationDraft,
    toggleSanitationDay, updateSanitationNote, rebuildSanitationEntryRows,
  }
}
