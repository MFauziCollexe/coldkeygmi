import { computed } from 'vue'
import { cctvChecklistGroupOptions, createCctvChecklistSections, formatDateInputDisplay, getCctvChecklistGroupLabel } from '../checklistConfig'

export function useCctvChecklist(entry, { showQrScanner }) {
  const isCctvChecklist = computed(() => entry.value?.template_id === 'cctv')
  const cctvApprovedGroups = computed(() => Array.isArray(entry.value?.form?.approved_areas) ? entry.value.form.approved_areas : [])
  const cctvTargetKey = computed(() => String(entry.value?.form?.selected_area || '').trim())
  const currentCctvSection = computed(() => (entry.value?.form?.sections || []).find((section) => section.id === cctvTargetKey.value) || null)
  const currentCctvBarcode = computed(() => entry.value?.form?.area_barcodes?.[cctvTargetKey.value] || '')
  const currentCctvNote = computed(() => entry.value?.form?.area_notes?.[cctvTargetKey.value] || '')
  const cctvNoteLabel = computed(() => `Keterangan ${getCctvChecklistGroupLabel(cctvTargetKey.value)}`)
  const currentCctvPhotos = computed(() => {
    const form = entry.value?.form || {}
    const paths = normalizePhotoBucket(form.area_photo_paths?.[cctvTargetKey.value])
    const urls = normalizePhotoBucket(form.area_photo_urls?.[cctvTargetKey.value])
    const names = normalizePhotoBucket(form.area_photo_names?.[cctvTargetKey.value])
    return Array.from({ length: Math.max(paths.length, urls.length, names.length) }, (_, index) => ({
      path: paths[index] || '',
      url: urls[index] || '',
      name: names[index] || '',
    })).filter((photo) => photo.url || photo.path)
  })

  function normalizePhotoBucket(bucket) {
    if (Array.isArray(bucket)) return bucket.filter((item) => String(item || '').trim() !== '')
    const value = String(bucket || '').trim()
    return value ? [value] : []
  }

  const cctvValidation = computed(() => {
    const items = currentCctvSection.value?.items || []
    const statuses = items.map((item) => item.status || '')
    return {
      allAnswersFilled: items.length > 0 && statuses.every((status) => status === 'yes' || status === 'no'),
      hasNoAnswer: statuses.includes('no'),
      hasRequiredNote: Boolean(String(currentCctvNote.value).trim()),
    }
  })

  const canScanCctvChecklist = computed(() => {
    if (!isCctvChecklist.value || !showQrScanner.value || !entry.value) return false
    const validation = cctvValidation.value
    return Boolean(entry.value.form.date_value && cctvTargetKey.value)
      && !cctvApprovedGroups.value.includes(cctvTargetKey.value)
      && validation.allAnswersFilled
      && (!validation.hasNoAnswer || validation.hasRequiredNote)
      && !String(currentCctvBarcode.value).trim()
  })

  function updateCctvDate(value) {
    if (!isCctvChecklist.value) return
    entry.value.form.date_value = value
    entry.value.form.date = formatDateInputDisplay(value)
    entry.value.form.approved_areas = []
    entry.value.form.area_barcodes = {}
    entry.value.form.area_notes = {}
    entry.value.form.area_scan_dates = {}
    entry.value.form.approved = false
    entry.value.form.sections = createCctvChecklistSections()
  }

  function updateCctvGroup(value) {
    if (isCctvChecklist.value) entry.value.form.selected_area = value
  }

  function updateCctvNote(value) {
    if (!isCctvChecklist.value || !cctvTargetKey.value) return
    entry.value.form.area_notes = { ...(entry.value.form.area_notes || {}), [cctvTargetKey.value]: String(value || '') }
  }

  function cycleCctvRowStatus(sectionId, itemId) {
    if (!isCctvChecklist.value || cctvApprovedGroups.value.includes(cctvTargetKey.value)) return
    entry.value.form.sections = (entry.value.form.sections || []).map((section) => {
      if (section.id !== sectionId) return section
      return {
        ...section,
        items: section.items.map((item) => {
          if (item.id !== itemId) return item
          const value = item.status || ''
          return { ...item, status: value === '' ? 'yes' : value === 'yes' ? 'no' : '' }
        }),
      }
    })
  }

  return {
    isCctvChecklist,
    cctvChecklistGroupOptions,
    cctvApprovedGroups,
    cctvTargetKey,
    currentCctvSection,
    currentCctvBarcode,
    currentCctvNote,
    currentCctvPhotos,
    cctvNoteLabel,
    cctvValidation,
    canScanCctvChecklist,
    getCctvChecklistGroupLabel,
    updateCctvDate,
    updateCctvGroup,
    updateCctvNote,
    cycleCctvRowStatus,
  }
}
