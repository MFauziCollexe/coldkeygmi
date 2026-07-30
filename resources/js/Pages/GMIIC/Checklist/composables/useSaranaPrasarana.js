import { computed } from 'vue'
import { getDaysInPeriod, toPeriodValue, getSaranaPrasaranaAreaLabel, saranaPrasaranaAreaOptions, rebuildSaranaPrasaranaSections } from '../checklistConfig'

export function useSaranaPrasarana(entry, { showQrScanner }) {
  const isSaranaPrasarana = computed(() => entry.value?.template_id === 'sarana_dan_prasarana')

  const saranaPrasaranaTargetKey = computed(() => {
    if (!isSaranaPrasarana.value || !entry.value) return ''
    const areaId = String(entry.value.form.selected_area || '').trim()
    const areaLabel = getSaranaPrasaranaAreaLabel(areaId)
    return areaLabel || areaId || ''
  })

  const saranaPrasaranaDays = computed(() => {
    if (!isSaranaPrasarana.value || !entry.value) return []
    return getDaysInPeriod(entry.value.form.period)
  })

  const saranaPrasaranaApprovedDays = computed(() => {
    if (!isSaranaPrasarana.value || !entry.value) return []
    const areaId = String(entry.value.form.selected_area || '').trim()
    const approvedDaysByArea = entry.value.form.approved_days_by_area || {}
    return Array.isArray(approvedDaysByArea?.[areaId]) ? approvedDaysByArea[areaId] : []
  })

  const isSaranaPrasaranaAreaApproved = computed(() => {
    if (!isSaranaPrasarana.value || !entry.value) return false
    const areaId = String(entry.value.form.selected_area || '').trim()
    if (!areaId) return false
    const nonSundayDays = saranaPrasaranaDays.value.filter((day) => !day.isSunday)
    const approvedDays = saranaPrasaranaApprovedDays.value
    return nonSundayDays.length > 0 && nonSundayDays.every((day) => approvedDays.includes(day.day))
  })

  const saranaPrasaranaNoteLabel = computed(() => {
    if (!isSaranaPrasarana.value || !entry.value) return 'Keterangan'
    return `Keterangan ${getSaranaPrasaranaAreaLabel(entry.value.form.selected_area)}`
  })

  const saranaPrasaranaNote = computed({
    get() {
      if (!isSaranaPrasarana.value || !entry.value) return ''
      return entry.value.form.area_notes?.[saranaPrasaranaTargetKey.value] || ''
    },
    set(value) {
      if (!isSaranaPrasarana.value || !entry.value) return
      entry.value.form.area_notes = {
        ...(entry.value.form.area_notes || {}),
        [saranaPrasaranaTargetKey.value]: value
      }
    },
  })

  const currentSaranaPrasaranaSection = computed(() => {
    if (!isSaranaPrasarana.value || !entry.value) return null
    const areaId = String(entry.value.form.selected_area || '').trim()
    return (entry.value.form.sections || []).find((section) => section.id === areaId) || null
  })

  const currentSaranaPrasaranaScan = computed(() => {
    if (!isSaranaPrasarana.value || !entry.value || !nextPendingSaranaPrasaranaDay.value) return null
    const dayScans = entry.value.form.area_scans_by_day?.[nextPendingSaranaPrasaranaDay.value.day] || {}
    return dayScans?.[entry.value.form.selected_area] || null
  })

  const nextPendingSaranaPrasaranaDay = computed(() => {
    if (!isSaranaPrasarana.value || !entry.value) return null
    return saranaPrasaranaDays.value.find((day) => !day.isSunday && !saranaPrasaranaApprovedDays.value.includes(day.day)) || null
  })

  const canScanSaranaPrasaranaArea = computed(() => {
    if (!isSaranaPrasarana.value || !entry.value || !nextPendingSaranaPrasaranaDay.value || !showQrScanner.value) return false
    if (currentSaranaPrasaranaScan.value?.barcode) return false
    return Boolean(String(entry.value.form.period || '').trim())
      && Boolean(String(entry.value.form.selected_area || '').trim())
      && (currentSaranaPrasaranaSection.value?.items || []).every((item) => Boolean(item.days?.[nextPendingSaranaPrasaranaDay.value.day]))
  })

  function findOpenSaranaPrasaranaDraft(entries = []) {
    const currentPeriod = toPeriodValue(new Date())
    const days = getDaysInPeriod(currentPeriod)
    const nonSundayDayNumbers = days.filter((d) => !d.isSunday).map((d) => d.day)
    if (!nonSundayDayNumbers.length) return null
    return (Array.isArray(entries) ? entries : []).find((entry) => {
      if (entry?.template_id !== 'sarana_dan_prasarana') return false
      if (String(entry?.form?.period || '').trim() !== currentPeriod) return false
      const approvedByArea = entry?.form?.approved_days_by_area || {}
      const allFullyApproved = saranaPrasaranaAreaOptions.every((area) => {
        const approvedDays = approvedByArea[area.id] || []
        return nonSundayDayNumbers.every((day) => approvedDays.includes(day))
      })
      return !allFullyApproved
    }) || null
  }

  function updateSaranaPrasaranaPeriod(value) {
    if (!entry.value || !isSaranaPrasarana.value) return
    entry.value.form.period = value
    entry.value.form.area_scans_by_day = {}
    entry.value.form.approved_days_by_area = saranaPrasaranaAreaOptions.reduce((result, area) => { result[area.id] = []; return result }, {})
    entry.value.form.sections = rebuildSaranaPrasaranaSections(value, [])
  }

  function updateSaranaPrasaranaArea(value) {
    if (!entry.value || !isSaranaPrasarana.value) return
    entry.value.form.selected_area = value
  }

  function cycleSaranaPrasaranaDay(sectionId, itemId, day) {
    if (!entry.value || !isSaranaPrasarana.value || saranaPrasaranaApprovedDays.value.includes(day)) return
    entry.value.form.sections = (entry.value.form.sections || []).map((section) => {
      if (section.id !== sectionId) return section
      return { ...section, items: (section.items || []).map((item) => {
        if (item.id !== itemId) return item
        const currentValue = item.days?.[day] || ''
        const nextValue = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : ''
        return { ...item, days: { ...(item.days || {}), [day]: nextValue } }
      }) }
    })
  }

  function updateSaranaPrasaranaNote(value) {
    if (!entry.value || !isSaranaPrasarana.value || !saranaPrasaranaTargetKey.value) return
    entry.value.form.area_notes = {
      ...(entry.value.form.area_notes || {}),
      [saranaPrasaranaTargetKey.value]: String(value || '')
    }
  }

  return {
    isSaranaPrasarana, saranaPrasaranaDays, saranaPrasaranaApprovedDays,
    isSaranaPrasaranaAreaApproved, saranaPrasaranaNote, saranaPrasaranaNoteLabel,
    saranaPrasaranaTargetKey,
    currentSaranaPrasaranaSection, currentSaranaPrasaranaScan,
    nextPendingSaranaPrasaranaDay, canScanSaranaPrasaranaArea,
    saranaPrasaranaAreaOptions, getSaranaPrasaranaAreaLabel,
    findOpenSaranaPrasaranaDraft,
    updateSaranaPrasaranaPeriod, updateSaranaPrasaranaArea, cycleSaranaPrasaranaDay,
    updateSaranaPrasaranaNote,
  }
}
