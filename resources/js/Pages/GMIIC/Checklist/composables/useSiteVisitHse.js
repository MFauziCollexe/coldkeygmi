import { computed } from 'vue'
import { siteVisitHseAreaOptions, getSiteVisitHseAreaLabel, rebuildSiteVisitHseSections, formatDateInputDisplay } from '../checklistConfig'

export function useSiteVisitHse(entry, { currentUser, showQrScanner }) {
  const isSiteVisitHse = computed(() => entry.value?.template_id === 'site_visit_hse')

  const siteVisitHseApprovedAreas = computed(() => {
    if (!isSiteVisitHse.value || !entry.value) return []
    return Array.isArray(entry.value.form.approved_areas) ? entry.value.form.approved_areas : []
  })

  const currentSiteVisitHseSection = computed(() => {
    if (!isSiteVisitHse.value || !entry.value) return null
    const areaId = String(entry.value.form.selected_area || '').trim()
    return (entry.value.form.sections || []).find((section) => section.id === areaId) || null
  })

  const siteVisitHseTargetKey = computed(() => {
    if (!isSiteVisitHse.value || !entry.value) return ''
    return String(entry.value.form.selected_area || '').trim()
  })

  const currentSiteVisitHseBarcode = computed(() => {
    if (!isSiteVisitHse.value || !entry.value) return ''
    return entry.value.form.area_barcodes?.[siteVisitHseTargetKey.value] || ''
  })

  const siteVisitHseNoteLabel = computed(() => {
    if (!isSiteVisitHse.value || !entry.value) return 'Keterangan'
    return `Keterangan ${getSiteVisitHseAreaLabel(entry.value.form.selected_area)}`
  })

  const siteVisitHseNote = computed({
    get() {
      if (!isSiteVisitHse.value || !entry.value) return ''
      return entry.value.form.area_notes?.[siteVisitHseTargetKey.value] || ''
    },
    set(value) {
      if (!isSiteVisitHse.value || !entry.value) return
      entry.value.form.area_notes = { ...(entry.value.form.area_notes || {}), [siteVisitHseTargetKey.value]: value }
    },
  })

  const siteVisitHseValidation = computed(() => {
    if (!isSiteVisitHse.value || !entry.value) return { allAnswersFilled: false, hasNoAnswer: false, hasRequiredNote: false }
    const activeRows = currentSiteVisitHseSection.value?.items || []
    const statuses = activeRows.map((row) => row.status || '')
    const allAnswersFilled = activeRows.length > 0 && statuses.every((status) => status === 'yes' || status === 'no')
    const hasNoAnswer = statuses.includes('no')
    const hasRequiredNote = String(siteVisitHseNote.value || '').trim() !== ''
    return { allAnswersFilled, hasNoAnswer, hasRequiredNote }
  })

  const canScanSiteVisitHse = computed(() => {
    if (!isSiteVisitHse.value || !entry.value || !showQrScanner.value) return false
    const selectedArea = String(entry.value.form.selected_area || '').trim()
    return Boolean(String(entry.value.form.date_value || '').trim()) && Boolean(selectedArea)
      && !siteVisitHseApprovedAreas.value.includes(selectedArea)
      && siteVisitHseValidation.value.allAnswersFilled
      && (!siteVisitHseValidation.value.hasNoAnswer || siteVisitHseValidation.value.hasRequiredNote)
      && !String(currentSiteVisitHseBarcode.value || '').trim()
  })

  function updateSiteVisitHseDate(value) {
    if (!entry.value || !isSiteVisitHse.value) return
    entry.value.form.date_value = value
    entry.value.form.date = formatDateInputDisplay(value)
    entry.value.form.approved_areas = []
    entry.value.form.area_barcodes = {}
    entry.value.form.area_notes = {}
    entry.value.form.area_scan_dates = {}
    entry.value.form.approved = false
    entry.value.form.sections = rebuildSiteVisitHseSections([])
  }

  function updateSiteVisitHseArea(value) {
    if (!entry.value || !isSiteVisitHse.value) return
    entry.value.form.selected_area = value
  }

  function updateSiteVisitHseNote(value) {
    if (!entry.value || !isSiteVisitHse.value || !siteVisitHseTargetKey.value) return
    entry.value.form.area_notes = { ...(entry.value.form.area_notes || {}), [siteVisitHseTargetKey.value]: String(value || '') }
  }

  function cycleSiteVisitHseRowStatus(sectionId, itemId) {
    if (!entry.value || !isSiteVisitHse.value) return
    if (siteVisitHseApprovedAreas.value.includes(String(entry.value.form.selected_area || '').trim())) return
    entry.value.form.sections = (entry.value.form.sections || []).map((section) => {
      if (section.id !== sectionId) return section
      return { ...section, items: (section.items || []).map((item) => {
        if (item.id !== itemId) return item
        const currentValue = item.status || ''
        const nextValue = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : ''
        return { ...item, status: nextValue }
      }) }
    })
  }

  return {
    isSiteVisitHse, siteVisitHseApprovedAreas, currentSiteVisitHseSection,
    siteVisitHseTargetKey, currentSiteVisitHseBarcode, siteVisitHseNoteLabel,
    siteVisitHseNote, siteVisitHseValidation, canScanSiteVisitHse,
    siteVisitHseAreaOptions, getSiteVisitHseAreaLabel,
    updateSiteVisitHseDate, updateSiteVisitHseArea, updateSiteVisitHseNote,
    cycleSiteVisitHseRowStatus,
  }
}
