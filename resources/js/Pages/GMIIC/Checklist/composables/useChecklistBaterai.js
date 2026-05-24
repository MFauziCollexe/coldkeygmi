import { computed } from 'vue'
import { rebuildChecklistBateraiRows } from '../checklistConfig'

export function useChecklistBaterai(entry) {
  const isChecklistBaterai = computed(() => entry.value?.template_id === 'checklist_baterai')

  const checklistBateraiRows = computed(() => {
    if (!isChecklistBaterai.value || !entry.value) return []
    return Array.isArray(entry.value.form.rows) ? entry.value.form.rows : []
  })

  const activeChecklistBateraiDay = computed(() => Number(entry.value?.form?.active_day) || Number(checklistBateraiRows.value[0]?.day) || 1)

  const activeChecklistBateraiRow = computed(() => (
    checklistBateraiRows.value.find((row) => Number(row.day) === activeChecklistBateraiDay.value) || checklistBateraiRows.value[0] || null
  ))

  const checklistBateraiApprovedDays = computed(() => (
    Array.isArray(entry.value?.form?.approved_days) ? entry.value.form.approved_days.map(Number) : []
  ))

  const isActiveChecklistBateraiDayApproved = computed(() => checklistBateraiApprovedDays.value.includes(activeChecklistBateraiDay.value))

  const checklistBateraiNote = computed({
    get() {
      if (!isChecklistBaterai.value || !entry.value) return ''
      return entry.value.form.note || ''
    },
    set(value) {
      if (!isChecklistBaterai.value || !entry.value) return
      entry.value.form.note = String(value || '')
    },
  })

  function updateChecklistBateraiField(field, value) {
    if (!entry.value || !isChecklistBaterai.value) return
    entry.value.form[field] = value
  }

  function setActiveChecklistBateraiDay(value) {
    if (!entry.value || !isChecklistBaterai.value) return
    const normalizedValue = String(value || '').trim()
    if (normalizedValue.includes('-')) {
      const nextPeriod = normalizedValue.slice(0, 7)
      if (nextPeriod && nextPeriod !== entry.value.form.period) {
        entry.value.form.period = nextPeriod
        entry.value.form.year = nextPeriod.split('-')[0] || entry.value.form.year
        entry.value.form.rows = rebuildChecklistBateraiRows(nextPeriod, entry.value.form.rows || [])
        entry.value.form.approved = false
      }
    }
    const day = Number(normalizedValue.includes('-') ? normalizedValue.split('-')[2] : normalizedValue)
    const matchedRow = checklistBateraiRows.value.find((row) => Number(row.day) === day)
    if (!matchedRow) return
    entry.value.form.active_day = Number(matchedRow.day)
    entry.value.form.date_value = matchedRow.date
  }

  function cycleChecklistBateraiRowSymbol(day, field) {
    if (!entry.value || !isChecklistBaterai.value) return
    const approvedDays = Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days.map(Number) : []
    if (approvedDays.includes(Number(day))) return
    const symbolOrder = ['', 'yes', 'no', 'minus']
    entry.value.form.rows = checklistBateraiRows.value.map((row) => {
      if (Number(row.day) !== Number(day)) return row
      const currentValue = String(row[field] || '')
      const currentIndex = symbolOrder.indexOf(currentValue)
      const nextValue = symbolOrder[(currentIndex + 1) % symbolOrder.length]
      return { ...row, [field]: nextValue }
    })
    entry.value.form.approved = false
  }

  function updateChecklistBateraiNote(value) { checklistBateraiNote.value = value }

  return {
    isChecklistBaterai, checklistBateraiRows, checklistBateraiNote,
    activeChecklistBateraiDay, activeChecklistBateraiRow,
    checklistBateraiApprovedDays, isActiveChecklistBateraiDayApproved,
    updateChecklistBateraiField, cycleChecklistBateraiRowSymbol,
    updateChecklistBateraiNote, setActiveChecklistBateraiDay,
  }
}
