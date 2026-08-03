import { computed, onBeforeUnmount, onMounted } from 'vue'
import { rebuildWasteTransportRows, toPeriodValue, WASTE_TRANSPORT_COLLECTOR_NAME } from '../checklistConfig'

function pad2(value) {
    return `${value}`.padStart(2, '0')
}

function currentTimeValue() {
    const now = new Date()
    return `${pad2(now.getHours())}:${pad2(now.getMinutes())}`
}

export function useWasteTransport(entry, { currentUser } = {}) {
    const isWasteTransport = computed(() => entry.value?.template_id === 'pengangkutan_sampah_pt_sier')

    const wasteTransportUserName = computed(() => String(currentUser?.value?.name || '').trim())

    const wasteTransportRows = computed(() => {
        if (!isWasteTransport.value || !entry.value) return []
        return Array.isArray(entry.value.form.rows) ? entry.value.form.rows : []
    })

    const wasteTransportApprovedDays = computed(() => {
        if (!isWasteTransport.value || !entry.value) return []
        return Array.isArray(entry.value.form.approved_days) ? entry.value.form.approved_days : []
    })

    const todayWasteTransportDay = computed(() => {
        if (!isWasteTransport.value || !entry.value) return null
        const now = new Date()
        if (entry.value.form.period !== toPeriodValue(now)) return null
        return now.getDate()
    })

    const nextPendingWasteTransportDay = computed(() => {
        if (!isWasteTransport.value || !entry.value) return null
        const todayDay = todayWasteTransportDay.value
        if (todayDay !== null) {
            const todayRow = wasteTransportRows.value.find((row) => Number(row.day) === Number(todayDay))
            if (todayRow && !wasteTransportApprovedDays.value.includes(todayRow.day)) return todayRow
        }
        return wasteTransportRows.value.find((row) => {
            if (wasteTransportApprovedDays.value.includes(row.day)) return false
            return Boolean(row.pickup_time || row.handover_name || row.collector_name || row.collector_photo_name)
        }) || null
    })

    function applyWasteTransportDefaults() {
        if (!entry.value || !isWasteTransport.value) return
        const userName = wasteTransportUserName.value
        const approvedDays = wasteTransportApprovedDays.value
        const todayDay = todayWasteTransportDay.value
        entry.value.form.rows = wasteTransportRows.value.map((row) => {
            const isToday = todayDay !== null && Number(row.day) === Number(todayDay)
            return {
                ...row,
                handover_name: isToday ? row.handover_name || userName || '' : row.handover_name,
                collector_name: isToday ? row.collector_name || WASTE_TRANSPORT_COLLECTOR_NAME : row.collector_name,
                pickup_time:
                    isToday && !approvedDays.includes(row.day)
                        ? currentTimeValue()
                        : row.pickup_time || '',
            }
        })
    }

    function updateWasteTransportRow(day, field, value) {
        if (!entry.value || !isWasteTransport.value) return
        entry.value.form.rows = wasteTransportRows.value.map((row) => (row.day === day ? { ...row, [field]: value } : row))
    }

    function rebuildWasteTransportEntryRows() {
        if (!entry.value || !isWasteTransport.value) return
        entry.value.form.rows = rebuildWasteTransportRows(
            entry.value.form.period,
            entry.value.form.rows || [],
            wasteTransportUserName.value,
        )
        applyWasteTransportDefaults()
    }

    function findOpenWasteTransportEntry(entries = []) {
        const currentPeriod = toPeriodValue(new Date())
        return (Array.isArray(entries) ? entries : []).find(
            (c) =>
                c?.template_id === 'pengangkutan_sampah_pt_sier' &&
                String(c?.form?.period || '').trim() === currentPeriod,
        ) || null
    }

    let realtimeTimer = null

    function startRealtimePickupTimer() {
        stopRealtimePickupTimer()
        realtimeTimer = setInterval(() => {
            applyWasteTransportDefaults()
        }, 30000)
    }

    function stopRealtimePickupTimer() {
        if (realtimeTimer) {
            clearInterval(realtimeTimer)
            realtimeTimer = null
        }
    }

    onMounted(() => {
        applyWasteTransportDefaults()
        startRealtimePickupTimer()
    })

    onBeforeUnmount(stopRealtimePickupTimer)

    return {
        isWasteTransport,
        wasteTransportRows,
        wasteTransportApprovedDays,
        wasteTransportUserName,
        todayWasteTransportDay,
        nextPendingWasteTransportDay,
        applyWasteTransportDefaults,
        updateWasteTransportRow,
        rebuildWasteTransportEntryRows,
        findOpenWasteTransportEntry,
    }
}
