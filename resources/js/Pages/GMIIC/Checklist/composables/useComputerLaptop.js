import { computed } from 'vue'
import {
  computerLaptopAreaOptions,
  createComputerLaptopSections,
  formatDateInputDisplay,
  getComputerLaptopAreaLabel,
} from '../checklistConfig'

export function useComputerLaptop(entry, { showQrScanner }) {
  const isComputerLaptop = computed(() => entry.value?.template_id === 'komputer_laptop')

  const approvedAreas = computed(() => {
    if (!isComputerLaptop.value || !entry.value) return []
    return Array.isArray(entry.value.form.approved_areas) ? entry.value.form.approved_areas : []
  })

  const currentSection = computed(() => {
    if (!isComputerLaptop.value || !entry.value) return null
    const areaId = String(entry.value.form.selected_area || '').trim()
    return (entry.value.form.sections || []).find((section) => section.id === areaId) || null
  })

  const targetKey = computed(() => {
    if (!isComputerLaptop.value || !entry.value) return ''
    return String(entry.value.form.selected_area || '').trim()
  })

  const currentBarcode = computed(() => {
    if (!isComputerLaptop.value || !entry.value) return ''
    return entry.value.form.area_barcodes?.[targetKey.value] || ''
  })

  const noteLabel = computed(() => {
    if (!isComputerLaptop.value || !entry.value) return 'Keterangan'
    return `Keterangan ${getComputerLaptopAreaLabel(entry.value.form.selected_area)}`
  })

  const note = computed({
    get() {
      if (!isComputerLaptop.value || !entry.value) return ''
      return entry.value.form.area_notes?.[targetKey.value] || ''
    },
    set(value) {
      if (!isComputerLaptop.value || !entry.value) return
      entry.value.form.area_notes = { ...(entry.value.form.area_notes || {}), [targetKey.value]: value }
    },
  })

  const currentPhotos = computed(() => {
    if (!isComputerLaptop.value || !entry.value) return []
    const paths = normalizePhotoBucket(entry.value.form.area_photo_paths?.[targetKey.value])
    const urls = normalizePhotoBucket(entry.value.form.area_photo_urls?.[targetKey.value])
    const names = normalizePhotoBucket(entry.value.form.area_photo_names?.[targetKey.value])
    const length = Math.max(paths.length, urls.length, names.length)
    return Array.from({ length }, (_, index) => ({
      path: paths[index] || '',
      url: urls[index] || '',
      name: names[index] || '',
    })).filter((photo) => String(photo.url || photo.path || '').trim() !== '')
  })

  const validation = computed(() => {
    if (!isComputerLaptop.value || !entry.value) {
      return { allAnswersFilled: false, hasNoAnswer: false, hasRequiredNote: false }
    }

    const items = currentSection.value?.items || []
    const statuses = items.map((item) => item.status || '')
    const allAnswersFilled = items.length > 0 && statuses.every((status) => status === 'yes' || status === 'no')
    return {
      allAnswersFilled,
      hasNoAnswer: statuses.includes('no'),
      hasRequiredNote: String(note.value || '').trim() !== '',
    }
  })

  const canScanBarcode = computed(() => {
    if (!isComputerLaptop.value || !entry.value || !showQrScanner.value) return false
    const selectedArea = String(entry.value.form.selected_area || '').trim()
    return Boolean(String(entry.value.form.date_value || '').trim())
      && Boolean(selectedArea)
      && !approvedAreas.value.includes(selectedArea)
      && validation.value.allAnswersFilled
      && (!validation.value.hasNoAnswer || validation.value.hasRequiredNote)
      && !String(currentBarcode.value || '').trim()
  })

  function findOpenDraft(entries = []) {
    return (Array.isArray(entries) ? entries : []).find(
      (item) => item?.template_id === 'komputer_laptop' && !Boolean(item?.form?.approved),
    ) || null
  }

  function updateDate(value) {
    if (!entry.value || !isComputerLaptop.value) return
    entry.value.form.date_value = value
    entry.value.form.date = formatDateInputDisplay(value)
    entry.value.form.approved_areas = []
    entry.value.form.area_barcodes = {}
    entry.value.form.area_notes = {}
    entry.value.form.area_scan_dates = {}
    entry.value.form.approved = false
    entry.value.form.sections = createComputerLaptopSections([])
  }

  function updateArea(value) {
    if (!entry.value || !isComputerLaptop.value) return
    entry.value.form.selected_area = value
  }

  function updateNote(value) {
    if (!entry.value || !isComputerLaptop.value || !targetKey.value) return
    entry.value.form.area_notes = { ...(entry.value.form.area_notes || {}), [targetKey.value]: String(value || '') }
  }

  function cycleRowStatus(sectionId, itemId) {
    if (!entry.value || !isComputerLaptop.value) return
    if (approvedAreas.value.includes(String(entry.value.form.selected_area || '').trim())) return

    entry.value.form.sections = (entry.value.form.sections || []).map((section) => {
      if (section.id !== sectionId) return section
      return {
        ...section,
        items: (section.items || []).map((item) => {
          if (item.id !== itemId) return item
          const currentValue = item.status || ''
          const status = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : ''
          return { ...item, status }
        }),
      }
    })
  }

  return {
    isComputerLaptop,
    areaOptions: computerLaptopAreaOptions,
    approvedAreas,
    currentSection,
    targetKey,
    currentBarcode,
    note,
    noteLabel,
    currentPhotos,
    validation,
    canScanBarcode,
    findOpenDraft,
    updateDate,
    updateArea,
    updateNote,
    cycleRowStatus,
  }
}

function normalizePhotoBucket(bucket) {
  if (Array.isArray(bucket)) return bucket.filter((item) => String(item || '').trim() !== '')
  const single = String(bucket || '').trim()
  return single ? [single] : []
}
