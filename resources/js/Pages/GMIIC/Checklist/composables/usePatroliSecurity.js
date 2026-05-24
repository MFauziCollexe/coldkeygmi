import { computed, ref } from 'vue'
import { patroliSecurityAreaOptions, getPatroliSecurityAreaLabel, getPatroliSecurityBarcodeAliases, rebuildPatroliSecuritySections, formatDateInputDisplay } from '../checklistConfig'

export function usePatroliSecurity(entry, { currentUser, showQrScanner }) {
  const isPatroliSecurity = computed(() => entry.value?.template_id === 'patroli_security')

  const patroliSecurityOverlayAddressLines = ['Jl. Rungkut Industri Raya II', 'No.45 B, Kali Rungkut, Kec.', 'Rungkut, Kota SBY, Jawa', 'Timur 60293, Indonesia']

  const patroliSecurityApprovedAreas = computed(() => {
    if (!isPatroliSecurity.value || !entry.value) return []
    return Array.isArray(entry.value.form.approved_areas) ? entry.value.form.approved_areas : []
  })

  const currentPatroliSecuritySection = computed(() => {
    if (!isPatroliSecurity.value || !entry.value) return null
    const areaId = String(entry.value.form.selected_area || '').trim()
    return (entry.value.form.sections || []).find((section) => section.id === areaId) || null
  })

  const patroliSecurityTargetKey = computed(() => {
    if (!isPatroliSecurity.value || !entry.value) return ''
    return String(entry.value.form.selected_area || '').trim()
  })

  const currentPatroliSecurityBarcode = computed(() => {
    if (!isPatroliSecurity.value || !entry.value) return ''
    return entry.value.form.area_barcodes?.[patroliSecurityTargetKey.value] || ''
  })

  const patroliSecurityNoteLabel = computed(() => {
    if (!isPatroliSecurity.value || !entry.value) return 'Keterangan'
    return `Keterangan ${getPatroliSecurityAreaLabel(entry.value.form.selected_area)}`
  })

  const patroliSecurityNote = computed({
    get() {
      if (!isPatroliSecurity.value || !entry.value) return ''
      return entry.value.form.area_notes?.[patroliSecurityTargetKey.value] || ''
    },
    set(value) {
      if (!isPatroliSecurity.value || !entry.value) return
      entry.value.form.area_notes = { ...(entry.value.form.area_notes || {}), [patroliSecurityTargetKey.value]: value }
    },
  })

  const patroliSecurityPhotoUploading = ref(false)
  const patroliSecurityPhotoError = ref('')

  const currentPatroliSecurityPhotos = computed(() => {
    if (!isPatroliSecurity.value || !entry.value) return []
    const paths = normalizePatroliSecurityPhotoBucket(entry.value.form.area_photo_paths?.[patroliSecurityTargetKey.value])
    const urls = normalizePatroliSecurityPhotoBucket(entry.value.form.area_photo_urls?.[patroliSecurityTargetKey.value])
    const names = normalizePatroliSecurityPhotoBucket(entry.value.form.area_photo_names?.[patroliSecurityTargetKey.value])
    const length = Math.max(paths.length, urls.length, names.length)
    return Array.from({ length }, (_, i) => ({ path: paths[i] || '', url: urls[i] || '', name: names[i] || '' })).filter((p) => String(p.url || p.path || '').trim() !== '')
  })

  function normalizePatroliSecurityPhotoBucket(bucket) {
    if (Array.isArray(bucket)) return bucket.filter((item) => String(item || '').trim() !== '')
    const single = String(bucket || '').trim()
    return single ? [single] : []
  }

  const patroliSecurityValidation = computed(() => {
    if (!isPatroliSecurity.value || !entry.value) return { allAnswersFilled: false, hasNoAnswer: false, hasRequiredNote: false }
    const activeRows = currentPatroliSecuritySection.value?.items || []
    const statuses = activeRows.map((row) => row.status || '')
    const allAnswersFilled = activeRows.length > 0 && statuses.every((status) => status === 'yes' || status === 'no')
    const hasNoAnswer = statuses.includes('no')
    const hasRequiredNote = String(patroliSecurityNote.value || '').trim() !== ''
    return { allAnswersFilled, hasNoAnswer, hasRequiredNote }
  })

  const canScanPatroliSecurity = computed(() => {
    if (!isPatroliSecurity.value || !entry.value || !showQrScanner.value) return false
    const selectedArea = String(entry.value.form.selected_area || '').trim()
    return Boolean(String(entry.value.form.date_value || '').trim()) && Boolean(selectedArea)
      && !patroliSecurityApprovedAreas.value.includes(selectedArea)
      && patroliSecurityValidation.value.allAnswersFilled
      && (!patroliSecurityValidation.value.hasNoAnswer || patroliSecurityValidation.value.hasRequiredNote)
      && !String(currentPatroliSecurityBarcode.value || '').trim()
  })

  function findOpenPatroliSecurityDraft(entries = []) {
    return (Array.isArray(entries) ? entries : []).find((c) => c?.template_id === 'patroli_security' && !Boolean(c?.form?.approved)) || null
  }

  function updatePatroliSecurityDate(value) {
    if (!entry.value || !isPatroliSecurity.value) return
    entry.value.form.date_value = value
    entry.value.form.date = formatDateInputDisplay(value)
    entry.value.form.approved_areas = []
    entry.value.form.area_barcodes = {}
    entry.value.form.area_notes = {}
    entry.value.form.area_scan_dates = {}
    entry.value.form.approved = false
    entry.value.form.sections = rebuildPatroliSecuritySections([])
  }

  function updatePatroliSecurityArea(value) {
    if (!entry.value || !isPatroliSecurity.value) return
    entry.value.form.selected_area = value
  }

  function updatePatroliSecurityNote(value) {
    if (!entry.value || !isPatroliSecurity.value || !patroliSecurityTargetKey.value) return
    entry.value.form.area_notes = { ...(entry.value.form.area_notes || {}), [patroliSecurityTargetKey.value]: String(value || '') }
  }

  function cyclePatroliSecurityRowStatus(sectionId, itemId) {
    if (!entry.value || !isPatroliSecurity.value) return
    if (patroliSecurityApprovedAreas.value.includes(String(entry.value.form.selected_area || '').trim())) return
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
    isPatroliSecurity, patroliSecurityOverlayAddressLines, patroliSecurityApprovedAreas,
    currentPatroliSecuritySection, patroliSecurityTargetKey, currentPatroliSecurityBarcode,
    patroliSecurityNoteLabel, patroliSecurityNote, patroliSecurityPhotoUploading,
    patroliSecurityPhotoError, currentPatroliSecurityPhotos, patroliSecurityValidation,
    canScanPatroliSecurity, patroliSecurityAreaOptions, getPatroliSecurityAreaLabel,
    findOpenPatroliSecurityDraft, updatePatroliSecurityDate, updatePatroliSecurityArea,
    updatePatroliSecurityNote, cyclePatroliSecurityRowStatus,
    normalizePatroliSecurityPhotoBucket,
  }
}
