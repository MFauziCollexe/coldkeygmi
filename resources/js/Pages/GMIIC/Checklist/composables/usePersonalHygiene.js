import { computed } from 'vue'
import { getDaysInPeriod, personalHygieneRows as personalHygieneRowTemplates, rebuildPersonalHygieneRows, formatDateTimeDisplay, toPeriodValue, createPersonalHygieneEntry } from '../checklistConfig'

export function usePersonalHygiene(entry, { props, currentUser }) {
  const isPersonalHygiene = computed(() => entry.value?.template_id === 'personal_hygiene_karyawan')

  const personalHygieneRows = computed(() => {
    if (!isPersonalHygiene.value || !entry.value) return []
    return Array.isArray(entry.value.form.rows) ? entry.value.form.rows : []
  })

  const generatedPersonalHygieneEmployees = computed(() => {
    if (!isPersonalHygiene.value || !entry.value) return []
    return Array.isArray(entry.value.form.generated_employees) ? entry.value.form.generated_employees : []
  })

  const personalHygieneDays = computed(() => {
    if (!isPersonalHygiene.value || !entry.value) return []
    const holidayDates = new Set((props.holidayDates || []).map((value) => String(value)))
    const employeeNik = String(entry.value.form.nik || '').trim()
    const leaveDates = new Set(Array.isArray(props.leaveDatesByNik?.[employeeNik]) ? props.leaveDatesByNik[employeeNik].map((value) => String(value)) : [])
    return getDaysInPeriod(entry.value.form.period).map((day) => ({ ...day, isHoliday: holidayDates.has(day.date), isLeave: leaveDates.has(day.date) }))
  })

  function resolvePersonalHygieneBagianByNik(nik) {
    const normalizedNik = String(nik || '').trim()
    if (!normalizedNik) return ''
    const matchedEmployee = (props.employees || []).find((employee) => String(employee?.nik || '').trim() === normalizedNik)
    return matchedEmployee?.bagian || matchedEmployee?.position || ''
  }

  function updatePersonalHygieneField(field, value) {
    if (!entry.value || !isPersonalHygiene.value) return
    if (field === 'period') { entry.value.form.period = value; entry.value.form.year = String(value || '').split('-')[0] || entry.value.form.year; return }
    entry.value.form[field] = value
  }

  function togglePersonalHygieneDay(row, day) {
    if (!row?.days || !isPersonalHygiene.value) return
    const currentValue = row.days[day] || ''
    row.days[day] = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : ''
  }

  function toggleGeneratedPersonalHygieneDay(employeeId, rowId, day) {
    if (!entry.value || !isPersonalHygiene.value) return
    entry.value.form.generated_employees = generatedPersonalHygieneEmployees.value.map((employee) => {
      if (String(employee.employee_id) !== String(employeeId)) return employee
      return { ...employee, rows: (employee.rows || []).map((row) => {
        if (row.id !== rowId) return row
        const currentValue = row.days?.[day] || ''
        const nextValue = currentValue === '' ? 'yes' : currentValue === 'yes' ? 'no' : ''
        return { ...row, days: { ...(row.days || {}), [day]: nextValue } }
      }) }
    })
  }

  function createGeneratedPersonalHygieneEmployees(periodValue, existingEmployees = []) {
    const days = getDaysInPeriod(periodValue)
    const holidayDates = new Set((props.holidayDates || []).map((value) => String(value)))
    const sourceEmployees = existingEmployees.length ? existingEmployees.map((e) => ({ id: e.employee_id, nik: e.nik, name: e.name, gender: e.gender, position: e.position })) : (props.employees || [])
    return sourceEmployees.map((employee, employeeIndex) => {
      const employeeNik = String(employee?.nik || '').trim()
      const leaveDates = new Set(Array.isArray(props.leaveDatesByNik?.[employeeNik]) ? props.leaveDatesByNik[employeeNik].map((value) => String(value)) : [])
      const leaveDays = days.filter((day) => leaveDates.has(day.date)).map((day) => day.day)
      const existingEmployee = existingEmployees.find((item) => String(item.employee_id) === String(employee?.id))
      return {
        employee_id: employee?.id ?? `generated-${employeeIndex + 1}`, nik: employeeNik, name: employee?.name || '-',
        gender: employee?.gender || '-', bagian: employee?.bagian || '-', position: employee?.position || '-',
        leave_days: leaveDays,
        rows: personalHygieneRowTemplates.map((rowTemplate, rowIndex) => {
          const existingRow = existingEmployee?.rows?.find((row) => row.id === rowTemplate.id)
          const nextDays = days.reduce((result, day) => {
            const isRedDay = day.isSunday || holidayDates.has(day.date) || leaveDates.has(day.date)
            const defaultValue = ['plester_perban_in', 'plester_perban_out'].includes(rowTemplate.id) ? 'no' : 'yes'
            result[day.day] = isRedDay ? '' : (existingRow?.days?.[day.day] || defaultValue)
            return result
          }, {})
          return { no: rowIndex + 1, id: rowTemplate.id, name: rowTemplate.name, days: nextDays }
        }),
      }
    })
  }

  function normalizePersonalHygieneGender(value) {
    const normalizedValue = String(value || '').trim().toLowerCase()
    if (['male', 'laki-laki', 'laki laki', 'pria'].includes(normalizedValue)) return 'male'
    if (['female', 'perempuan', 'wanita'].includes(normalizedValue)) return 'female'
    return ''
  }

  function createGeneratedPersonalHygieneRows(periodValue, employeeNik, existingRows = []) {
    const days = getDaysInPeriod(periodValue)
    const holidayDates = new Set((props.holidayDates || []).map((value) => String(value)))
    const normalizedNik = String(employeeNik || '').trim()
    const leaveDates = new Set(Array.isArray(props.leaveDatesByNik?.[normalizedNik]) ? props.leaveDatesByNik[normalizedNik].map((value) => String(value)) : [])
    return personalHygieneRowTemplates.map((rowTemplate, rowIndex) => {
      const existingRow = existingRows.find((row) => row.id === rowTemplate.id)
      const nextDays = days.reduce((result, day) => {
        const isRedDay = day.isSunday || holidayDates.has(day.date) || leaveDates.has(day.date)
        const defaultValue = ['plester_perban_in', 'plester_perban_out'].includes(rowTemplate.id) ? 'no' : 'yes'
        result[day.day] = isRedDay ? '' : (existingRow?.days?.[day.day] || defaultValue)
        return result
      }, {})
      return { no: rowIndex + 1, id: rowTemplate.id, name: rowTemplate.name, days: nextDays }
    })
  }

  async function generatePersonalHygieneFullMonth({ persistChecklistEntries, knownChecklistEntries }) {
    if (!entry.value || !isPersonalHygiene.value) return
    const targetPeriod = entry.value.form.period || toPeriodValue(new Date())
    const targetYear = String(targetPeriod || '').split('-')[0] || String(new Date().getFullYear())
    const existingEntries = knownChecklistEntries.filter((savedEntry) => savedEntry?.template_id === 'personal_hygiene_karyawan' && String(savedEntry?.form?.period || '') === targetPeriod)
    let createdCount = 0
    let skippedCount = 0
    const entriesToSave = []
    ;(props.employees || []).forEach((employee, index) => {
      const employeeNik = String(employee?.nik || '').trim()
      const existingEntry = existingEntries.find((savedEntry) => String(savedEntry?.form?.nik || '').trim() === employeeNik)
      if (existingEntry) { skippedCount += 1; return }
      const nextEntry = createPersonalHygieneEntry(currentUser.value?.name || 'User Login')
      nextEntry.id = `personal_hygiene_karyawan-${Date.now()}-${index + 1}`
      nextEntry.created_at = formatDateTimeDisplay(new Date())
      nextEntry.form.year = targetYear
      nextEntry.form.period = targetPeriod
      nextEntry.form.employee_name = employee?.name || ''
      nextEntry.form.gender = normalizePersonalHygieneGender(employee?.gender)
      nextEntry.form.nik = employeeNik
      nextEntry.form.bagian = employee?.bagian || employee?.position || ''
      nextEntry.form.approved = true
      nextEntry.form.generated_at = formatDateTimeDisplay(new Date())
      nextEntry.form.rows = createGeneratedPersonalHygieneRows(targetPeriod, employeeNik, [])
      nextEntry.form.generated_employees = []
      entriesToSave.push(nextEntry)
      createdCount += 1
    })
    if (entriesToSave.length) await persistChecklistEntries(entriesToSave)
    if (typeof window !== 'undefined') {
      if (createdCount === 0 && skippedCount > 0) { window.alert(`Semua checklist personal hygiene periode ${targetPeriod} sudah tersedia. Tidak ada data baru yang digenerate.`); return }
      if (skippedCount > 0) window.alert(`${createdCount} checklist berhasil digenerate. ${skippedCount} checklist dilewati karena sudah ada di periode yang sama.`)
    }
    const { router } = await import('@inertiajs/vue3')
    router.visit('/gmiic/checklist')
  }

  function rebuildPersonalHygieneEntryRows() {
    if (!entry.value || !isPersonalHygiene.value) return
    entry.value.form.rows = rebuildPersonalHygieneRows(entry.value.form.period, entry.value.form.rows || [])
    if (generatedPersonalHygieneEmployees.value.length > 0) {
      entry.value.form.generated_employees = createGeneratedPersonalHygieneEmployees(entry.value.form.period, generatedPersonalHygieneEmployees.value)
    }
  }

  return {
    isPersonalHygiene, personalHygieneRows, generatedPersonalHygieneEmployees,
    personalHygieneDays, personalHygieneRowTemplates,
    resolvePersonalHygieneBagianByNik,
    updatePersonalHygieneField, togglePersonalHygieneDay, toggleGeneratedPersonalHygieneDay,
    generatePersonalHygieneFullMonth, rebuildPersonalHygieneEntryRows,
    normalizePersonalHygieneGender,
  }
}
