import { computed } from 'vue'
import { rebuildChargerBateraiRows } from '../checklistConfig'

export function useChargerBaterai(entry) {
  const isChargerBaterai = computed(() => entry.value?.template_id === 'charger_baterai')

  const chargerBateraiRows = computed(() => {
    if (!isChargerBaterai.value || !entry.value) return []
    return Array.isArray(entry.value.form.rows) ? entry.value.form.rows : []
  })

  const activeChargerBateraiDay = computed(() => Number(entry.value?.form?.active_day) || Number(chargerBateraiRows.value[0]?.day) || 1)

  const activeChargerBateraiRow = computed(() => (
    chargerBateraiRows.value.find((row) => Number(row.day) === activeChargerBateraiDay.value) || chargerBateraiRows.value[0] || null
  ))

  const chargerBateraiApprovedDays = computed(() => (
    Array.isArray(entry.value?.form?.approved_days) ? entry.value.form.approved_days.map(Number) : []
  ))

  const isActiveChargerBateraiDayApproved = computed(() => chargerBateraiApprovedDays.value.includes(activeChargerBateraiDay.value))

  const chargerBateraiNote = computed({
    get() {
      if (!isChargerBaterai.value || !entry.value) return ''
      return entry.value.form.note || ''
    },
    set(value) {
      if (!isChargerBaterai.value || !entry.value) return
      entry.value.form.note = String(value || '')
    },
  })

  function updateChargerBateraiField(field, value) {
    if (!entry.value || !isChargerBaterai.value) return
    if (field === 'period') {
      const period = String(value || '').trim()
      entry.value.form.period = period
      entry.value.form.year = period.split('-')[0] || entry.value.form.year
      entry.value.form.rows = rebuildChargerBateraiRows(period, entry.value.form.rows || [])
      entry.value.form.active_day = Number(entry.value.form.rows?.[0]?.day) || 1
      entry.value.form.date_value = entry.value.form.rows?.[0]?.date || ''
      entry.value.form.approved = false
      return
    }
    entry.value.form[field] = value
  }

  function setActiveChargerBateraiDay(value) {
    if (!entry.value || !isChargerBaterai.value) return
    const normalizedValue = String(value || '').trim()
    if (normalizedValue.includes('-')) {
      const nextPeriod = normalizedValue.slice(0, 7)
      if (nextPeriod && nextPeriod !== entry.value.form.period) {
        entry.value.form.period = nextPeriod
        entry.value.form.year = nextPeriod.split('-')[0] || entry.value.form.year
        entry.value.form.rows = rebuildChargerBateraiRows(nextPeriod, entry.value.form.rows || [])
        entry.value.form.approved = false
      }
    }
    const day = Number(normalizedValue.includes('-') ? normalizedValue.split('-')[2] : normalizedValue)
    const matchedRow = chargerBateraiRows.value.find((row) => Number(row.day) === day)
    if (!matchedRow) return
    entry.value.form.active_day = Number(matchedRow.day)
    entry.value.form.date_value = matchedRow.date
  }

  function cycleChargerBateraiRowSymbol(day, field) {
    if (!entry.value || !isChargerBaterai.value) return
    const approvedDays = Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days.map(Number) : []
    if (approvedDays.includes(Number(day))) return
    const symbolOrder = ['', 'yes', 'no', 'minus']
    entry.value.form.rows = chargerBateraiRows.value.map((row) => {
      if (Number(row.day) !== Number(day)) return row
      const currentValue = String(row[field] || '')
      const currentIndex = symbolOrder.indexOf(currentValue)
      const nextValue = symbolOrder[(currentIndex + 1) % symbolOrder.length]
      return { ...row, [field]: nextValue }
    })
    entry.value.form.approved = false
  }

  function updateChargerBateraiRowField(day, field, value) {
    if (!entry.value || !isChargerBaterai.value) return
    const approvedDays = Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days.map(Number) : []
    if (approvedDays.includes(Number(day))) return
    entry.value.form.rows = chargerBateraiRows.value.map((row) => (
      Number(row.day) === Number(day) ? { ...row, [field]: value } : row
    ))
    entry.value.form.approved = false
  }

  function updateChargerBateraiNote(value) { chargerBateraiNote.value = value }

  return {
    isChargerBaterai, chargerBateraiRows, chargerBateraiNote,
    activeChargerBateraiDay, activeChargerBateraiRow,
    chargerBateraiApprovedDays, isActiveChargerBateraiDayApproved,
    updateChargerBateraiField, updateChargerBateraiRowField,
    cycleChargerBateraiRowSymbol, updateChargerBateraiNote,
    setActiveChargerBateraiDay,
  }
}
