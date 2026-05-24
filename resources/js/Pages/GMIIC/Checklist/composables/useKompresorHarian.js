import { computed } from 'vue'
import { rebuildKompresorDailyRows } from '../checklistConfig'

export function useKompresorHarian(entry) {
  const isKompresorHarian = computed(() => entry.value?.template_id === 'kompresor_harian')

  const kompresorHarianRows = computed(() => {
    if (!isKompresorHarian.value || !entry.value) return []
    return Array.isArray(entry.value.form.rows) ? entry.value.form.rows : []
  })

  const activeKompresorDay = computed(() => Number(entry.value?.form?.active_day) || Number(kompresorHarianRows.value[0]?.day) || 1)

  const activeKompresorRow = computed(() => (
    kompresorHarianRows.value.find((row) => Number(row.day) === activeKompresorDay.value) || kompresorHarianRows.value[0] || null
  ))

  const kompresorApprovedDays = computed(() => (
    Array.isArray(entry.value?.form?.approved_days) ? entry.value.form.approved_days.map(Number) : []
  ))

  const isActiveKompresorDayApproved = computed(() => kompresorApprovedDays.value.includes(activeKompresorDay.value))

  const kompresorHarianNote = computed({
    get() {
      if (!isKompresorHarian.value || !entry.value) return ''
      return entry.value.form.note || ''
    },
    set(value) {
      if (!isKompresorHarian.value || !entry.value) return
      entry.value.form.note = String(value || '')
    },
  })

  function updateKompresorHarianField(field, value) {
    if (!entry.value || !isKompresorHarian.value) return
    if (field === 'period') {
      const period = String(value || '').trim()
      entry.value.form.period = period
      entry.value.form.year = period.split('-')[0] || entry.value.form.year
      entry.value.form.rows = rebuildKompresorDailyRows(period, entry.value.form.rows || [])
      entry.value.form.active_day = Number(entry.value.form.rows?.[0]?.day) || 1
      entry.value.form.date_value = entry.value.form.rows?.[0]?.date || ''
      entry.value.form.approved = false
      return
    }
    entry.value.form[field] = value
  }

  function setActiveKompresorDay(value) {
    if (!entry.value || !isKompresorHarian.value) return
    const normalizedValue = String(value || '').trim()
    if (normalizedValue.includes('-')) {
      const nextPeriod = normalizedValue.slice(0, 7)
      if (nextPeriod && nextPeriod !== entry.value.form.period) {
        entry.value.form.period = nextPeriod
        entry.value.form.year = nextPeriod.split('-')[0] || entry.value.form.year
        entry.value.form.rows = rebuildKompresorDailyRows(nextPeriod, entry.value.form.rows || [])
        entry.value.form.approved = false
      }
    }
    const day = Number(normalizedValue.includes('-') ? normalizedValue.split('-')[2] : normalizedValue)
    const matchedRow = kompresorHarianRows.value.find((row) => Number(row.day) === day)
    if (!matchedRow) return
    entry.value.form.active_day = Number(matchedRow.day)
    entry.value.form.date_value = matchedRow.date
  }

  function updateKompresorCheckHeader(key, value) {
    if (!entry.value || !isKompresorHarian.value) return
    entry.value.form.check_headers = { ...(entry.value.form.check_headers || {}), [key]: String(value || '') }
  }

  function updateKompresorHarianNote(value) { kompresorHarianNote.value = value }

  function updateKompresorRowField(day, field, value) {
    if (!entry.value || !isKompresorHarian.value) return
    const approvedDays = Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days.map(Number) : []
    if (approvedDays.includes(Number(day))) return
    entry.value.form.rows = kompresorHarianRows.value.map((row) => (
      Number(row.day) === Number(day) ? { ...row, [field]: value } : row
    ))
    entry.value.form.approved = false
  }

  function cycleKompresorRowSymbol(day, field) {
    if (!entry.value || !isKompresorHarian.value) return
    const approvedDays = Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days.map(Number) : []
    if (approvedDays.includes(Number(day))) return
    const symbolOrder = ['', 'yes', 'no']
    entry.value.form.rows = kompresorHarianRows.value.map((row) => {
      if (Number(row.day) !== Number(day)) return row
      const currentValue = String(row[field] || '')
      const currentIndex = symbolOrder.indexOf(currentValue)
      const nextValue = symbolOrder[(currentIndex + 1) % symbolOrder.length]
      return { ...row, [field]: nextValue }
    })
    entry.value.form.approved = false
  }

  return {
    isKompresorHarian, kompresorHarianRows, kompresorHarianNote,
    activeKompresorDay, activeKompresorRow, kompresorApprovedDays,
    isActiveKompresorDayApproved,
    updateKompresorHarianField, updateKompresorCheckHeader,
    updateKompresorRowField, cycleKompresorRowSymbol, updateKompresorHarianNote,
    setActiveKompresorDay,
  }
}
