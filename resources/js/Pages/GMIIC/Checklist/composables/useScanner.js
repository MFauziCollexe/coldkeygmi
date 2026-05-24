import { ref, nextTick } from 'vue'

export function useScanner({
  entry,
  isKotakP3K,
  isGensetRunning,
  isRunningGenset,
  isSanitation,
  isSaranaPrasarana,
  isFireSafety,
  isWarehouseSanitation,
  isSiteVisitMaintenance,
  isSiteVisitHse,
  isPatroliSecurity,
  showQrScanner,
  kotakP3KMonthValidation,
  gensetRunningValidation,
  runningGensetValidation,
  canScanSaranaPrasaranaArea,
  canScanFireSafety,
  canScanWarehouseBarcode,
  canScanMaintenance,
  canScanSiteVisitHse,
  canScanPatroliSecurity,
  nextPendingSanitationDay,
  nextPendingSaranaPrasaranaDay,
  activeKotakP3KMonth,
  activeFireSafetyMonth,
  maintenanceScanTargetKey,
  siteVisitHseTargetKey,
  patroliSecurityTargetKey,
  formatShortDateDisplay,
  formatDateTimeDisplay,
  getLocationBarcodeAliases,
  getPatroliSecurityBarcodeAliases,
  getFireSafetyLocationLabel,
  getSanitationAreaBarcodeAliases,
  getSaranaPrasaranaAreaLabel,
  getSiteVisitHseAreaLabel,
  getMaintenanceDailyAreaLabel,
  warehouseAreaOptions,
  persistCurrentFireSafetyState,
}) {
  const scannerModalOpen = ref(false)
  const scannerLoading = ref(false)
  const scannerError = ref('')
  const scannerMode = ref('')

  let html5QrcodeInstance = null
  let scannerStarting = false
  let scannerFinishing = false

  function normalizeBarcodeText(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, ' ')
      .trim()
      .replace(/\s+/g, ' ')
  }

  function isBarcodeTextMatchingExpected(decodedText, expectedValues = []) {
    const normalizedDecoded = normalizeBarcodeText(decodedText)
    if (!normalizedDecoded) return false
    return expectedValues.some((value) => {
      const normalizedExpected = normalizeBarcodeText(value)
      if (!normalizedExpected) return false
      return normalizedDecoded === normalizedExpected
        || normalizedDecoded.includes(normalizedExpected)
        || normalizedExpected.includes(normalizedDecoded)
    })
  }

  function getExpectedBarcodeValuesForCurrentScannerMode() {
    if (!entry.value) return []

    const mode = scannerMode.value

    if (mode === 'kotak_p3k') return getLocationBarcodeAliases(entry.value.form.location)
    if (mode === 'genset_running' || mode === 'running_genset') return getPatroliSecurityBarcodeAliases('genset')
    if (mode === 'fire_safety') {
      return getLocationBarcodeAliases(entry.value.form.location)
        .concat(getFireSafetyLocationLabel(entry.value.form.card_type, entry.value.form.location))
    }
    if (mode === 'sanitation_area') return getSanitationAreaBarcodeAliases(entry.value.form.area)
    if (mode === 'sarana_prasarana') return [getSaranaPrasaranaAreaLabel(entry.value.form.selected_area)]
    if (mode === 'site_visit_hse') return [getSiteVisitHseAreaLabel(entry.value.form.selected_area)]
    if (mode === 'patroli_security') return getPatroliSecurityBarcodeAliases(entry.value.form.selected_area)
    if (mode === 'site_visit_maintenance') return [getMaintenanceDailyAreaLabel(maintenanceScanTargetKey.value)]
    if (mode === 'warehouse_sanitation') {
      return warehouseAreaOptions
        .filter((area) => (entry.value.form.selected_areas || []).includes(area.id))
        .map((area) => area.name)
    }
    return []
  }

  function openScanner(mode) {
    scannerMode.value = mode
    scannerError.value = ''
    scannerLoading.value = true
    scannerModalOpen.value = true
    nextTick(() => startBarcodeScanner())
  }

  async function scanBarcode() {
    if (!entry.value || !isKotakP3K.value || !showQrScanner.value || !kotakP3KMonthValidation.value.canScan) return
    openScanner('kotak_p3k')
  }

  async function scanGensetRunningBarcode() {
    if (!entry.value || !isGensetRunning.value || !showQrScanner.value || !gensetRunningValidation.value.canScan) return
    openScanner('genset_running')
  }

  async function scanRunningGensetBarcode() {
    if (!entry.value || !isRunningGenset.value || !showQrScanner.value || !runningGensetValidation.value.canScan) return
    openScanner('running_genset')
  }

  async function scanSanitationArea() {
    if (!entry.value || !isSanitation.value || !nextPendingSanitationDay.value || !showQrScanner.value) return
    openScanner('sanitation_area')
  }

  async function scanSaranaPrasaranaArea() {
    if (!entry.value || !isSaranaPrasarana.value || !showQrScanner.value || !canScanSaranaPrasaranaArea.value) return
    openScanner('sarana_prasarana')
  }

  async function scanFireSafetyBarcode() {
    if (!entry.value || !isFireSafety.value || !showQrScanner.value || !canScanFireSafety.value) return
    openScanner('fire_safety')
  }

  async function scanWarehouseBarcode() {
    if (!entry.value || !isWarehouseSanitation.value || !showQrScanner.value || !canScanWarehouseBarcode.value) return
    openScanner('warehouse_sanitation')
  }

  async function scanMaintenanceBarcode() {
    if (!entry.value || !isSiteVisitMaintenance.value || !showQrScanner.value || !canScanMaintenance.value) return
    openScanner('site_visit_maintenance')
  }

  async function scanSiteVisitHseBarcode() {
    if (!entry.value || !isSiteVisitHse.value || !showQrScanner.value || !canScanSiteVisitHse.value) return
    openScanner('site_visit_hse')
  }

  async function scanPatroliSecurityBarcode() {
    if (!entry.value || !isPatroliSecurity.value || !showQrScanner.value || !canScanPatroliSecurity.value) return
    openScanner('patroli_security')
  }

  function handleScannedBarcode(decodedText) {
    if (!entry.value) return

    const mode = scannerMode.value
    const now = new Date()

    if (mode === 'kotak_p3k') {
      entry.value.form.barcode = decodedText
      entry.value.form.monthly_barcodes = {
        ...(entry.value.form.monthly_barcodes || {}),
        [activeKotakP3KMonth.value]: decodedText,
      }
      entry.value.form.monthly_check_dates = {
        ...(entry.value.form.monthly_check_dates || {}),
        [activeKotakP3KMonth.value]: formatShortDateDisplay(now),
      }
    }

    if (mode === 'genset_running' || mode === 'running_genset') {
      entry.value.form.area_barcodes = { ...(entry.value.form.area_barcodes || {}), genset: decodedText }
      entry.value.form.area_scan_dates = { ...(entry.value.form.area_scan_dates || {}), genset: formatShortDateDisplay(now) }
    }

    if (mode === 'sanitation_area' && nextPendingSanitationDay.value) {
      const day = nextPendingSanitationDay.value.day
      const dayScans = entry.value.form.area_scans_by_day?.[day] || {}
      entry.value.form.area_scans_by_day = {
        ...(entry.value.form.area_scans_by_day || {}),
        [day]: { ...dayScans, [entry.value.form.area]: { barcode: decodedText, scanned_at: formatDateTimeDisplay(now) } },
      }
    }

    if (mode === 'fire_safety') {
      entry.value.form.monthly_barcodes = { ...(entry.value.form.monthly_barcodes || {}), [activeFireSafetyMonth.value]: decodedText }
      persistCurrentFireSafetyState()
    }

    if (mode === 'sarana_prasarana' && nextPendingSaranaPrasaranaDay.value) {
      const day = nextPendingSaranaPrasaranaDay.value.day
      const dayScans = entry.value.form.area_scans_by_day?.[day] || {}
      entry.value.form.area_scans_by_day = {
        ...(entry.value.form.area_scans_by_day || {}),
        [day]: { ...dayScans, [entry.value.form.selected_area]: { barcode: decodedText, scanned_at: formatDateTimeDisplay(now) } },
      }
    }

    if (mode === 'warehouse_sanitation') {
      entry.value.form.barcode = decodedText
      entry.value.form.scan_date = formatShortDateDisplay(now)
    }

    if (mode === 'site_visit_maintenance') {
      entry.value.form.area_barcodes = { ...(entry.value.form.area_barcodes || {}), [maintenanceScanTargetKey.value]: decodedText }
      entry.value.form.area_scan_dates = { ...(entry.value.form.area_scan_dates || {}), [maintenanceScanTargetKey.value]: formatShortDateDisplay(now) }
    }

    if (mode === 'site_visit_hse') {
      entry.value.form.area_barcodes = { ...(entry.value.form.area_barcodes || {}), [siteVisitHseTargetKey.value]: decodedText }
      entry.value.form.area_scan_dates = { ...(entry.value.form.area_scan_dates || {}), [siteVisitHseTargetKey.value]: formatShortDateDisplay(now) }
    }

    if (mode === 'patroli_security') {
      entry.value.form.area_barcodes = { ...(entry.value.form.area_barcodes || {}), [patroliSecurityTargetKey.value]: decodedText }
      entry.value.form.area_scan_dates = { ...(entry.value.form.area_scan_dates || {}), [patroliSecurityTargetKey.value]: formatShortDateDisplay(now) }
    }
  }

  function choosePreferredCamera(cameras) {
    return [...cameras].sort((a, b) => scoreCameraLabel(b.label) - scoreCameraLabel(a.label))[0]
  }

  function scoreCameraLabel(label) {
    const text = String(label || '').toLowerCase()
    let score = 0
    if (text.includes('back') || text.includes('rear') || text.includes('environment')) score += 20
    if (text.includes('front') || text.includes('user')) score -= 5
    return score
  }

  function normalizeScannerError(error) {
    const message = String(error?.message || error || '')
    const lowered = message.toLowerCase()
    if (lowered.includes('permission')) return 'Izin kamera ditolak. Izinkan akses kamera lalu coba lagi.'
    if (lowered.includes('secure context') || lowered.includes('https')) return 'Kamera membutuhkan koneksi aman (HTTPS) atau localhost.'
    return message || 'Scanner QRCode gagal dijalankan.'
  }

  async function startBarcodeScanner() {
    if (scannerStarting) return
    scannerStarting = true
    try {
      await stopBarcodeScanner()
      const { Html5Qrcode } = await import('html5-qrcode')
      html5QrcodeInstance = new Html5Qrcode('barcode-scanner-region')
      const cameras = await Html5Qrcode.getCameras()
      if (!cameras.length) throw new Error('Kamera tidak ditemukan pada perangkat ini.')
      const preferredCamera = choosePreferredCamera(cameras)
      const viewportWidth = typeof window !== 'undefined' ? window.innerWidth : 1280
      const viewportHeight = typeof window !== 'undefined' ? window.innerHeight : 720
      const qrboxWidth = Math.min(Math.max(Math.floor(viewportWidth * 0.82), 320), 460)
      const qrboxHeight = Math.min(Math.max(Math.floor(viewportHeight * 0.34), 180), 280)
      await html5QrcodeInstance.start(
        preferredCamera.id,
        { fps: 10, qrbox: { width: qrboxWidth, height: qrboxHeight }, aspectRatio: 1.777778 },
        async (decodedText) => {
          if (scannerFinishing) return
          scannerFinishing = true
          const expectedBarcodeValues = getExpectedBarcodeValuesForCurrentScannerMode()
          const shouldValidateArea = expectedBarcodeValues.length > 0
          if (shouldValidateArea && !isBarcodeTextMatchingExpected(decodedText, expectedBarcodeValues)) {
            scannerError.value = `Area QRCode tidak sesuai. Harus sesuai area aktif: ${expectedBarcodeValues[0]}.`
            window.alert(`QRCode salah area.\nArea aktif: ${expectedBarcodeValues[0]}\nQRCode terbaca: ${decodedText}`)
            scannerFinishing = false
            return
          }
          handleScannedBarcode(decodedText)
          await closeScannerModal()
          scannerFinishing = false
        },
        () => {}
      )
      scannerError.value = ''
    } catch (error) {
      scannerError.value = normalizeScannerError(error)
      await stopBarcodeScanner()
    } finally {
      scannerLoading.value = false
      scannerStarting = false
    }
  }

  async function closeScannerModal() {
    scannerModalOpen.value = false
    scannerLoading.value = false
    scannerMode.value = ''
    await stopBarcodeScanner()
  }

  async function stopBarcodeScanner() {
    if (!html5QrcodeInstance) return
    try { if (html5QrcodeInstance.isScanning) await html5QrcodeInstance.stop() } catch {}
    try { await html5QrcodeInstance.clear() } catch {}
    html5QrcodeInstance = null
  }

  return {
    scannerModalOpen,
    scannerLoading,
    scannerError,
    scannerMode,
    scanBarcode,
    scanGensetRunningBarcode,
    scanRunningGensetBarcode,
    scanSanitationArea,
    scanSaranaPrasaranaArea,
    scanFireSafetyBarcode,
    scanWarehouseBarcode,
    scanMaintenanceBarcode,
    scanSiteVisitHseBarcode,
    scanPatroliSecurityBarcode,
    closeScannerModal,
    stopBarcodeScanner,
  }
}
