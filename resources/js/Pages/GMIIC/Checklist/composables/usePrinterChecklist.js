import { computed } from 'vue'
import { printerChecklistDeviceOptions, createPrinterChecklistSections, formatDateInputDisplay, getPrinterChecklistDeviceLabel } from '../checklistConfig'

export function usePrinterChecklist(entry, { showQrScanner }) {
  const isPrinterChecklist = computed(() => entry.value?.template_id === 'printer')
  const printerApprovedDevices = computed(() => Array.isArray(entry.value?.form?.approved_areas) ? entry.value.form.approved_areas : [])
  const printerTargetKey = computed(() => String(entry.value?.form?.selected_area || '').trim())
  const currentPrinterSection = computed(() => (entry.value?.form?.sections || []).find((section) => section.id === printerTargetKey.value) || null)
  const currentPrinterBarcode = computed(() => entry.value?.form?.area_barcodes?.[printerTargetKey.value] || '')
  const currentPrinterNote = computed(() => entry.value?.form?.area_notes?.[printerTargetKey.value] || '')
  const printerNoteLabel = computed(() => `Keterangan ${getPrinterChecklistDeviceLabel(printerTargetKey.value)}`)
  const currentPrinterPhotos = computed(() => {
    const form = entry.value?.form || {}
    const paths = normalizePhotoBucket(form.area_photo_paths?.[printerTargetKey.value])
    const urls = normalizePhotoBucket(form.area_photo_urls?.[printerTargetKey.value])
    const names = normalizePhotoBucket(form.area_photo_names?.[printerTargetKey.value])
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

  const printerValidation = computed(() => {
    const items = currentPrinterSection.value?.items || []
    const statuses = items.map((item) => item.status || '')
    return {
      allAnswersFilled: items.length > 0 && statuses.every((status) => status === 'yes' || status === 'no'),
      hasNoAnswer: statuses.includes('no'),
      hasRequiredNote: Boolean(String(currentPrinterNote.value).trim()),
    }
  })

  const canScanPrinterChecklist = computed(() => {
    if (!isPrinterChecklist.value || !showQrScanner.value || !entry.value) return false
    const validation = printerValidation.value
    return Boolean(entry.value.form.date_value && printerTargetKey.value)
      && !printerApprovedDevices.value.includes(printerTargetKey.value)
      && validation.allAnswersFilled
      && (!validation.hasNoAnswer || validation.hasRequiredNote)
      && !String(currentPrinterBarcode.value).trim()
  })

  function updatePrinterDate(value) {
    if (!isPrinterChecklist.value) return
    entry.value.form.date_value = value
    entry.value.form.date = formatDateInputDisplay(value)
    entry.value.form.approved_areas = []
    entry.value.form.area_barcodes = {}
    entry.value.form.area_notes = {}
    entry.value.form.area_scan_dates = {}
    entry.value.form.approved = false
    entry.value.form.sections = createPrinterChecklistSections()
  }

  function updatePrinterDevice(value) {
    if (isPrinterChecklist.value) entry.value.form.selected_area = value
  }

  function updatePrinterNote(value) {
    if (!isPrinterChecklist.value || !printerTargetKey.value) return
    entry.value.form.area_notes = { ...(entry.value.form.area_notes || {}), [printerTargetKey.value]: String(value || '') }
  }

  function cyclePrinterRowStatus(sectionId, itemId) {
    if (!isPrinterChecklist.value || printerApprovedDevices.value.includes(printerTargetKey.value)) return
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
    isPrinterChecklist,
    printerChecklistDeviceOptions,
    printerApprovedDevices,
    printerTargetKey,
    currentPrinterSection,
    currentPrinterBarcode,
    currentPrinterNote,
    currentPrinterPhotos,
    printerNoteLabel,
    printerValidation,
    canScanPrinterChecklist,
    getPrinterChecklistDeviceLabel,
    updatePrinterDate,
    updatePrinterDevice,
    updatePrinterNote,
    cyclePrinterRowStatus,
  }
}
