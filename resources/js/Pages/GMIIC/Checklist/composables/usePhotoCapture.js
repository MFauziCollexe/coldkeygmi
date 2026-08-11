import { computed, ref, nextTick } from "vue";
import { swalConfirm } from "@/Utils/swalConfirm";
import axios from "axios";

export function usePhotoCapture({
    entry,
    isWasteTransport,
    isPatroliSecurity,
    isSiteVisitMaintenance,
    isCleaningOB,
    isChecklistIT,
    isRunningGenset,
    isKompresorHarian,
    isChargerBaterai,
    isChecklistBaterai,
    isInspeksiLoker,
    isSaranaPrasarana,
    isWarehouseSanitation,
    warehouseTargetKey,
    patroliSecurityTargetKey,
    maintenanceScanTargetKey,
    cleaningOBTargetKey,
    runningGensetTargetKey,
    kompresorHarianTargetKey,
    chargerBateraiTargetKey,
    checklistBateraiTargetKey,
    saranaPrasaranaTargetKey,
    wasteTransportRows,
    currentPatroliSecurityPhotos,
    currentCleaningOBPhotos,
    getPatroliSecurityAreaLabel,
    getCleaningOBShiftLabel,
    patroliSecurityOverlayAddressLines,
    currentUser,
}) {
    const photoModalOpen = ref(false);
    const photoLoading = ref(false);
    const photoError = ref("");
    const photoCaptureDay = ref(null);
    const photoCaptureMode = ref("");
    const photoVideoRef = ref(null);
    const photoGalleryInput = ref(null);

    const patroliSecurityPhotoUploading = ref(false);
    const patroliSecurityPhotoError = ref("");
    const maintenancePhotoUploading = ref(false);
    const maintenancePhotoError = ref("");
    const cleaningOBPhotoUploading = ref(false);
    const cleaningOBPhotoError = ref("");
    const checklistITPhotoUploading = ref(false);
    const checklistITPhotoError = ref("");
    const runningGensetPhotoUploading = ref(false);
    const runningGensetPhotoError = ref("");
    const kompresorHarianPhotoUploading = ref(false);
    const kompresorHarianPhotoError = ref("");
    const chargerBateraiPhotoUploading = ref(false);
    const chargerBateraiPhotoError = ref("");
    const checklistBateraiPhotoUploading = ref(false);
    const checklistBateraiPhotoError = ref("");
    const inspeksiLokerPhotoUploading = ref(false);
    const inspeksiLokerPhotoError = ref("");
    const saranaPrasaranaPhotoUploading = ref(false);
    const saranaPrasaranaPhotoError = ref("");
    const warehousePhotoUploading = ref(false);
    const warehousePhotoError = ref("");
    const photoCapturing = ref(false);

    let photoStream = null;

    const photoModalTitle = ref("Ambil Foto Petugas Pengangkut");
    const photoModalDescription = ref(
        "Gunakan kamera HP atau laptop, lalu ambil foto langsung.",
    );
    const photoCaptureButtonLabel = ref("Ambil Foto");

    function updatePhotoModalLabels(mode) {
        if (mode === "patroli_security") {
            photoModalTitle.value = "Ambil Foto Patroli Security";
            photoModalDescription.value =
                "Gunakan kamera HP atau laptop, lalu ambil foto area patroli secara langsung.";
            photoCaptureButtonLabel.value = "Capture & Upload";
        } else if (mode === "maintenance") {
            photoModalTitle.value = "Ambil Foto Visit Maintenance";
            photoModalDescription.value =
                "Gunakan kamera HP atau laptop, lalu ambil foto area maintenance secara langsung.";
            photoCaptureButtonLabel.value = "Capture & Upload";
        } else if (mode === "cleaning_ob") {
            photoModalTitle.value = "Ambil Foto Cleaning OB";
            photoModalDescription.value =
                "Gunakan kamera HP atau laptop, lalu ambil foto area cleaning secara langsung.";
            photoCaptureButtonLabel.value = "Capture & Upload";
        } else if (mode === "checklist_it") {
            photoModalTitle.value = "Ambil Foto Checklist IT";
            photoModalDescription.value =
                "Gunakan kamera HP atau laptop, lalu ambil foto area IT secara langsung.";
            photoCaptureButtonLabel.value = "Capture & Upload";
        } else if (mode === "running_genset") {
            photoModalTitle.value = "Ambil Foto Running Genset";
            photoModalDescription.value =
                "Gunakan kamera HP atau laptop, lalu ambil foto genset secara langsung.";
            photoCaptureButtonLabel.value = "Capture & Upload";
        } else if (mode === "kompresor_harian") {
            photoModalTitle.value = "Ambil Foto Kompresor Harian";
            photoModalDescription.value =
                "Gunakan kamera HP atau laptop, lalu ambil foto area kompresor secara langsung.";
            photoCaptureButtonLabel.value = "Capture & Upload";
        } else if (mode === "charger_baterai") {
            photoModalTitle.value = "Ambil Foto Charger Baterai";
            photoModalDescription.value =
                "Gunakan kamera HP atau laptop, lalu ambil foto charger baterai secara langsung.";
            photoCaptureButtonLabel.value = "Capture & Upload";
        } else if (mode === "checklist_baterai") {
            photoModalTitle.value = "Ambil Foto Checklist Baterai";
            photoModalDescription.value =
                "Gunakan kamera HP atau laptop, lalu ambil foto checklist baterai secara langsung.";
            photoCaptureButtonLabel.value = "Capture & Upload";
        } else if (mode === "inspeksi_loker") {
            photoModalTitle.value = "Ambil Foto Inspeksi Loker";
            photoModalDescription.value =
                "Gunakan kamera HP atau laptop, lalu ambil foto inspeksi loker secara langsung.";
            photoCaptureButtonLabel.value = "Capture & Upload";
        } else if (mode === "sarana_prasarana") {
            photoModalTitle.value = "Ambil Foto Sarana dan Prasarana";
            photoModalDescription.value =
                "Gunakan kamera HP atau laptop, lalu ambil foto area sarana dan prasarana secara langsung.";
            photoCaptureButtonLabel.value = "Capture & Upload";
        } else if (mode === "warehouse_sanitation") {
            photoModalTitle.value = "Ambil Foto Warehouse";
            photoModalDescription.value =
                "Gunakan kamera HP atau laptop, lalu ambil foto area warehouse secara langsung.";
            photoCaptureButtonLabel.value = "Capture & Upload";
        } else {
            photoModalTitle.value = "Ambil Foto Petugas Pengangkut";
            photoModalDescription.value =
                "Ambil foto langsung dari kamera HP/laptop, atau pilih dari galeri.";
            photoCaptureButtonLabel.value = "Ambil Foto";
        }
    }

    async function openWasteTransportCamera(day) {
        if (!entry.value || !isWasteTransport.value) return;
        photoCaptureMode.value = "waste_transport";
        photoCaptureDay.value = day;
        updatePhotoModalLabels("waste_transport");
        photoError.value = "";
        photoLoading.value = true;
        photoModalOpen.value = true;
        await nextTick();
        await startPhotoCamera();
    }

    function openWasteTransportGallery() {
        if (!entry.value || !isWasteTransport.value || !photoCaptureDay.value) return;
        if (!photoGalleryInput.value) return;
        photoGalleryInput.value.click();
    }

    function readImageFile(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => {
                const image = new Image();
                image.onload = () => resolve(image);
                image.onerror = () => reject(new Error("Gambar gagal dimuat."));
                image.src = reader.result;
            };
            reader.onerror = () => reject(new Error("File gagal dibaca."));
            reader.readAsDataURL(file);
        });
    }

    function drawImageWithinMaxDimension(image, maxDimension = 1920) {
        const width = image.naturalWidth || image.width;
        const height = image.naturalHeight || image.height;
        const scale = Math.min(1, maxDimension / Math.max(width, height));
        const canvas = document.createElement("canvas");
        canvas.width = Math.round(width * scale);
        canvas.height = Math.round(height * scale);
        const context = canvas.getContext("2d");
        if (!context) throw new Error("Foto gagal diproses.");
        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = "high";
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        return canvas;
    }

    async function handleWasteTransportGalleryFile(event) {
        const file = event.target?.files?.[0];
        if (event.target) event.target.value = "";
        if (!file) return;
        if (!String(file.type || "").startsWith("image/")) {
            photoError.value = "File yang dipilih bukan gambar.";
            return;
        }
        if (file.size > 20 * 1024 * 1024) {
            photoError.value = "Ukuran foto maksimal 20MB.";
            return;
        }
        photoLoading.value = true;
        photoCapturing.value = true;
        try {
            const image = await readImageFile(file);
            const canvas = drawImageWithinMaxDimension(image, 1920);
            const matchedRow = wasteTransportRows.value.find(
                (row) => Number(row.day) === Number(photoCaptureDay.value),
            ) || {};
            applyWasteTransportPhotoOverlay(canvas, new Date(), {
                day: photoCaptureDay.value,
                period: entry.value?.form?.period,
                handoverName: matchedRow.handover_name,
                collectorName: matchedRow.collector_name,
            });
            const preview = canvas.toDataURL("image/jpeg", 0.9);
            const fileName = `foto-pengangkut-hari-${photoCaptureDay.value}.jpg`;
            entry.value.form.rows = wasteTransportRows.value.map((row) =>
                row.day === photoCaptureDay.value
                    ? {
                          ...row,
                          collector_photo_name: fileName,
                          collector_photo_preview: preview,
                      }
                    : row,
            );
            await closePhotoModal();
        } catch (error) {
            photoError.value = error?.message || "Foto gagal diproses.";
        } finally {
            photoLoading.value = false;
            photoCapturing.value = false;
        }
    }

    async function openPatroliSecurityCamera() {
        if (
            !entry.value ||
            !isPatroliSecurity.value ||
            !patroliSecurityTargetKey.value
        )
            return;
        photoCaptureMode.value = "patroli_security";
        photoCaptureDay.value = null;
        updatePhotoModalLabels("patroli_security");
        photoError.value = "";
        photoLoading.value = true;
        photoModalOpen.value = true;
        await nextTick();
        await startPhotoCamera();
    }

    async function openMaintenanceCamera() {
        if (
            !entry.value ||
            !isSiteVisitMaintenance.value ||
            !maintenanceScanTargetKey.value
        )
            return;
        photoCaptureMode.value = "maintenance";
        photoCaptureDay.value = null;
        updatePhotoModalLabels("maintenance");
        photoError.value = "";
        photoLoading.value = true;
        photoModalOpen.value = true;
        await nextTick();
        await startPhotoCamera();
    }

    async function openCleaningOBCamera() {
        if (!entry.value || !isCleaningOB.value || !cleaningOBTargetKey.value)
            return;
        photoCaptureMode.value = "cleaning_ob";
        photoCaptureDay.value = null;
        updatePhotoModalLabels("cleaning_ob");
        photoError.value = "";
        photoLoading.value = true;
        photoModalOpen.value = true;
        await nextTick();
        await startPhotoCamera();
    }

    async function openChecklistITCamera() {
        if (!entry.value || !isChecklistIT.value) return;
        photoCaptureMode.value = "checklist_it";
        photoCaptureDay.value = null;
        photoError.value = "";
        photoLoading.value = true;
        photoModalOpen.value = true;
        await nextTick();
        await startPhotoCamera();
    }

    async function openKompresorHarianCamera() {
        if (
            !entry.value ||
            !isKompresorHarian.value ||
            !kompresorHarianTargetKey.value
        )
            return;
        photoCaptureMode.value = "kompresor_harian";
        photoCaptureDay.value = null;
        updatePhotoModalLabels("kompresor_harian");
        photoError.value = "";
        photoLoading.value = true;
        photoModalOpen.value = true;
        await nextTick();
        await startPhotoCamera();
    }

    async function openRunningGensetCamera() {
        if (
            !entry.value ||
            !isRunningGenset.value ||
            !runningGensetTargetKey.value
        )
            return;
        photoCaptureMode.value = "running_genset";
        photoCaptureDay.value = null;
        updatePhotoModalLabels("running_genset");
        photoError.value = "";
        photoLoading.value = true;
        photoModalOpen.value = true;
        await nextTick();
        await startPhotoCamera();
    }

    async function openChargerBateraiCamera() {
        if (
            !entry.value ||
            !isChargerBaterai.value ||
            !chargerBateraiTargetKey.value
        )
            return;
        photoCaptureMode.value = "charger_baterai";
        photoCaptureDay.value = null;
        updatePhotoModalLabels("charger_baterai");
        photoError.value = "";
        photoLoading.value = true;
        photoModalOpen.value = true;
        await nextTick();
        await startPhotoCamera();
    }

    async function openChecklistBateraiCamera() {
        if (
            !entry.value ||
            !isChecklistBaterai.value ||
            !checklistBateraiTargetKey.value
        )
            return;
        photoCaptureMode.value = "checklist_baterai";
        photoCaptureDay.value = null;
        updatePhotoModalLabels("checklist_baterai");
        photoError.value = "";
        photoLoading.value = true;
        photoModalOpen.value = true;
        await nextTick();
        await startPhotoCamera();
    }

    async function openInspeksiLokerCamera() {
        if (!entry.value || !isInspeksiLoker.value) return;
        photoCaptureMode.value = "inspeksi_loker";
        photoCaptureDay.value = null;
        updatePhotoModalLabels("inspeksi_loker");
        photoError.value = "";
        photoLoading.value = true;
        photoModalOpen.value = true;
        await nextTick();
        await startPhotoCamera();
    }

    async function openSaranaPrasaranaCamera() {
        if (
            !entry.value ||
            !isSaranaPrasarana.value ||
            !saranaPrasaranaTargetKey.value
        )
            return;
        photoCaptureMode.value = "sarana_prasarana";
        photoCaptureDay.value = null;
        updatePhotoModalLabels("sarana_prasarana");
        photoError.value = "";
        photoLoading.value = true;
        photoModalOpen.value = true;
        await nextTick();
        await startPhotoCamera();
    }

    async function openWarehouseCamera() {
        if (
            !entry.value ||
            !isWarehouseSanitation.value ||
            !warehouseTargetKey.value
        )
            return;
        photoCaptureMode.value = "warehouse_sanitation";
        photoCaptureDay.value = null;
        updatePhotoModalLabels("warehouse_sanitation");
        photoError.value = "";
        photoLoading.value = true;
        photoModalOpen.value = true;
        await nextTick();
        await startPhotoCamera();
    }

    async function startPhotoCamera() {
        try {
            await stopPhotoCamera();
            if (!navigator.mediaDevices?.getUserMedia)
                throw new Error("Browser ini tidak mendukung akses kamera.");
            photoStream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: {
                    facingMode: { ideal: "environment" },
                    width: { ideal: 1920, min: 1280 },
                    height: { ideal: 1080, min: 720 },
                    aspectRatio: { ideal: 1.777778 },
                },
            });
            if (photoVideoRef.value) {
                photoVideoRef.value.srcObject = photoStream;
                await photoVideoRef.value.play();
                if (
                    !photoVideoRef.value.videoWidth ||
                    !photoVideoRef.value.videoHeight
                ) {
                    await Promise.race([
                        new Promise((resolve) => {
                            photoVideoRef.value.addEventListener(
                                "loadedmetadata",
                                resolve,
                                { once: true },
                            );
                        }),
                        new Promise((_, reject) =>
                            setTimeout(
                                () =>
                                    reject(
                                        new Error(
                                            "Timeout menunggu video kamera.",
                                        ),
                                    ),
                                8000,
                            ),
                        ),
                    ]);
                }
            }
            photoError.value = "";
        } catch (error) {
            photoError.value = normalizeScannerError(error).replace(
                "Scanner barcode",
                "Kamera foto",
            );
            await stopPhotoCamera();
        } finally {
            photoLoading.value = false;
        }
    }

    function normalizeScannerError(error) {
        const message = String(error?.message || error || "");
        const lowered = message.toLowerCase();
        if (lowered.includes("permission"))
            return "Izin kamera ditolak. Izinkan akses kamera lalu coba lagi.";
        if (lowered.includes("secure context") || lowered.includes("https"))
            return "Kamera membutuhkan koneksi aman (HTTPS) atau localhost.";
        return message || "Kamera gagal dijalankan.";
    }

    function canvasToJpegFile(canvas, fileName) {
        return new Promise((resolve, reject) => {
            let settled = false;
            const timer = setTimeout(() => {
                if (!settled) {
                    settled = true;
                    reject(new Error("Foto gagal diproses (waktu habis)."));
                }
            }, 10000);
            canvas.toBlob(
                (blob) => {
                    if (settled) return;
                    settled = true;
                    clearTimeout(timer);
                    if (!blob) {
                        reject(new Error("Foto gagal diproses."));
                        return;
                    }
                    resolve(new File([blob], fileName, { type: "image/jpeg" }));
                },
                "image/jpeg",
                0.85,
            );
        });
    }

    function formatPatroliSecurityOverlayTime(date = new Date()) {
        return new Intl.DateTimeFormat("id-ID", {
            hour: "2-digit",
            minute: "2-digit",
            hour12: false,
        })
            .format(date)
            .replace(".", ":");
    }

    function formatPatroliSecurityOverlayDay(date = new Date()) {
        const formatted = new Intl.DateTimeFormat("id-ID", {
            weekday: "long",
        }).format(date);
        return formatted
            ? formatted.charAt(0).toUpperCase() + formatted.slice(1)
            : "-";
    }

    function formatPatroliSecurityOverlayDate(date = new Date()) {
        return `${String(date.getDate()).padStart(2, "0")}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getFullYear())}`;
    }

    function getPatroliSecurityPersonnelName() {
        const aliasName = String(currentUser.value?.alias_name || "").trim();
        return (
            aliasName ||
            String(currentUser.value?.name || "Personil Security").trim() ||
            "Personil Security"
        );
    }

    function drawPatroliSecurityDivider(
        context,
        x,
        y,
        width,
        dashWidth,
        gapWidth,
    ) {
        context.save();
        context.strokeStyle = "rgba(255, 255, 255, 0.9)";
        context.lineWidth = Math.max(2, dashWidth * 0.18);
        context.setLineDash([dashWidth, gapWidth]);
        context.beginPath();
        context.moveTo(x, y);
        context.lineTo(x + width, y);
        context.stroke();
        context.restore();
    }

    function drawPatroliSecurityMapPin(context, x, y, size) {
        context.save();
        context.fillStyle = "#ffffff";
        context.beginPath();
        context.arc(x, y - size * 0.1, size * 0.32, Math.PI, 0);
        context.lineTo(x + size * 0.24, y + size * 0.08);
        context.quadraticCurveTo(
            x,
            y + size * 0.62,
            x - size * 0.24,
            y + size * 0.08,
        );
        context.closePath();
        context.fill();
        context.fillStyle = "rgba(52, 92, 191, 0.85)";
        context.beginPath();
        context.arc(x, y - size * 0.12, size * 0.11, 0, Math.PI * 2);
        context.fill();
        context.restore();
    }

    function drawPatroliSecurityPersonIcon(context, x, y, size) {
        context.save();
        context.fillStyle = "#ffffff";
        context.beginPath();
        context.arc(x, y - size * 0.32, size * 0.2, 0, Math.PI * 2);
        context.fill();
        context.beginPath();
        context.arc(x, y + size * 0.18, size * 0.34, Math.PI, 0);
        context.lineTo(x + size * 0.34, y + size * 0.5);
        context.lineTo(x - size * 0.34, y + size * 0.5);
        context.closePath();
        context.fill();
        context.restore();
    }

    function drawPatroliSecurityShieldIcon(context, x, y, size) {
        context.save();
        context.strokeStyle = "#cfd5df";
        context.lineWidth = Math.max(2, size * 0.09);
        context.beginPath();
        context.moveTo(x, y - size * 0.48);
        context.lineTo(x + size * 0.34, y - size * 0.24);
        context.lineTo(x + size * 0.24, y + size * 0.24);
        context.quadraticCurveTo(
            x,
            y + size * 0.54,
            x - size * 0.24,
            y + size * 0.24,
        );
        context.lineTo(x - size * 0.34, y - size * 0.24);
        context.closePath();
        context.stroke();
        context.beginPath();
        context.moveTo(x - size * 0.12, y + size * 0.02);
        context.lineTo(x - size * 0.01, y + size * 0.14);
        context.lineTo(x + size * 0.17, y - size * 0.1);
        context.stroke();
        context.restore();
    }

    function drawWrappedPatroliSecurityText(
        context,
        textLines,
        x,
        startY,
        maxWidth,
        lineHeight,
    ) {
        let cursorY = startY;
        textLines.forEach((line) => {
            const words = String(line || "")
                .split(/\s+/)
                .filter(Boolean);
            let currentLine = "";
            words.forEach((word) => {
                const candidate = currentLine ? `${currentLine} ${word}` : word;
                if (
                    context.measureText(candidate).width <= maxWidth ||
                    currentLine === ""
                ) {
                    currentLine = candidate;
                    return;
                }
                context.fillText(currentLine, x, cursorY);
                cursorY += lineHeight;
                currentLine = word;
            });
            if (currentLine) {
                context.fillText(currentLine, x, cursorY);
                cursorY += lineHeight;
            }
        });
        return cursorY;
    }

    function applyPatroliSecurityPhotoOverlay(canvas, capturedAt = new Date()) {
        const context = canvas.getContext("2d");
        if (!context) throw new Error("Overlay foto gagal diproses.");
        const width = canvas.width;
        const height = canvas.height;
        const cardWidth = Math.max(
            180,
            Math.min(270, Math.round(width * 0.29)),
        );
        const scale = cardWidth / 230;
        const cardHeight = Math.round(214 * scale);
        const cardX = Math.round(18 * scale);
        const cardY = height - cardHeight - Math.round(18 * scale);
        const headerHeight = Math.round(34 * scale);
        const sidePadding = Math.round(10 * scale);
        const dividerWidth = cardWidth - sidePadding * 2;
        const timeText = formatPatroliSecurityOverlayTime(capturedAt);
        const dayText = formatPatroliSecurityOverlayDay(capturedAt);
        const dateText = formatPatroliSecurityOverlayDate(capturedAt);
        const personnelText = `Personil: ${getPatroliSecurityPersonnelName()}`;
        const verifiedText = "Diverifikasi oleh Tim GMI";

        context.save();
        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = "high";
        context.shadowColor = "rgba(0, 0, 0, 0.45)";
        context.shadowBlur = Math.max(1, scale * 1.4);
        context.shadowOffsetX = 0;
        context.shadowOffsetY = Math.max(1, scale * 0.8);
        context.fillStyle = "rgba(15, 20, 30, 0.88)";
        context.fillRect(cardX, cardY, cardWidth, cardHeight);
        context.strokeStyle = "rgba(72, 111, 212, 0.95)";
        context.lineWidth = Math.max(2, scale * 1.4);
        context.strokeRect(cardX, cardY, cardWidth, cardHeight);
        context.fillStyle = "#2f5fc5";
        context.fillRect(cardX, cardY, cardWidth, headerHeight);
        context.fillStyle = "#ffffff";
        context.font = `700 ${Math.round(14 * scale)}px "Arial", sans-serif`;
        context.textBaseline = "middle";
        context.fillText(
            "SECURITY GMI",
            cardX + sidePadding,
            cardY + headerHeight / 2,
        );

        const timeBaselineY = cardY + headerHeight + Math.round(46 * scale);
        context.fillStyle = "#ffffff";
        context.textBaseline = "alphabetic";
        context.font = `700 ${Math.round(30 * scale)}px "Arial", sans-serif`;
        context.fillText(timeText, cardX + sidePadding, timeBaselineY);

        const timeMetrics = context.measureText(timeText);
        const separatorX =
            cardX + sidePadding + timeMetrics.width + Math.round(8 * scale);
        context.strokeStyle = "rgba(255, 255, 255, 0.95)";
        context.lineWidth = Math.max(2, scale * 1.1);
        context.beginPath();
        context.moveTo(
            separatorX,
            cardY + headerHeight + Math.round(10 * scale),
        );
        context.lineTo(
            separatorX,
            cardY + headerHeight + Math.round(52 * scale),
        );
        context.stroke();

        const dayDateX = separatorX + Math.round(8 * scale);
        context.font = `700 ${Math.round(12 * scale)}px "Arial", sans-serif`;
        context.fillText(
            dayText,
            dayDateX,
            cardY + headerHeight + Math.round(24 * scale),
        );
        context.font = `700 ${Math.round(11 * scale)}px "Arial", sans-serif`;
        context.fillText(
            dateText,
            dayDateX,
            cardY + headerHeight + Math.round(42 * scale),
        );

        drawPatroliSecurityDivider(
            context,
            cardX + sidePadding,
            cardY + headerHeight + Math.round(64 * scale),
            dividerWidth,
            Math.max(4, Math.round(5 * scale)),
            Math.max(3, Math.round(3 * scale)),
        );

        const mapIconSize = Math.round(12 * scale);
        const mapIconX = cardX + sidePadding + Math.round(5 * scale);
        const addressX = cardX + sidePadding + Math.round(22 * scale);
        let addressY = cardY + headerHeight + Math.round(80 * scale);
        drawPatroliSecurityMapPin(
            context,
            mapIconX,
            addressY - Math.round(6 * scale),
            mapIconSize,
        );
        context.font = `700 ${Math.round(10 * scale)}px "Arial", sans-serif`;
        context.fillStyle = "#ffffff";
        addressY = drawWrappedPatroliSecurityText(
            context,
            patroliSecurityOverlayAddressLines,
            addressX,
            addressY,
            cardX + cardWidth - addressX - sidePadding,
            Math.round(12 * scale),
        );

        const personIconSize = Math.round(11 * scale);
        const personRowY = addressY + Math.round(4 * scale);
        drawPatroliSecurityPersonIcon(
            context,
            mapIconX,
            personRowY - Math.round(2 * scale),
            personIconSize,
        );
        context.font = `700 ${Math.round(10 * scale)}px "Arial", sans-serif`;
        context.fillText(
            personnelText,
            addressX,
            personRowY + Math.round(3 * scale),
        );

        drawPatroliSecurityDivider(
            context,
            cardX + sidePadding,
            cardY + cardHeight - Math.round(26 * scale),
            dividerWidth,
            Math.max(4, Math.round(5 * scale)),
            Math.max(3, Math.round(3 * scale)),
        );

        const shieldIconSize = Math.round(10 * scale);
        const verifiedY = cardY + cardHeight - Math.round(10 * scale);
        drawPatroliSecurityShieldIcon(
            context,
            mapIconX,
            verifiedY - Math.round(2 * scale),
            shieldIconSize,
        );
        context.fillStyle = "#cfd5df";
        context.font = `700 ${Math.round(9 * scale)}px "Arial", sans-serif`;
        context.fillText(
            verifiedText,
            addressX,
            verifiedY + Math.round(2 * scale),
        );
        context.restore();
    }

    function applyCleaningOBPhotoOverlay(canvas, capturedAt = new Date()) {
        const context = canvas.getContext("2d");
        if (!context) throw new Error("Overlay foto gagal diproses.");
        const width = canvas.width;
        const height = canvas.height;
        const cardWidth = Math.max(
            180,
            Math.min(270, Math.round(width * 0.29)),
        );
        const scale = cardWidth / 230;
        const cardHeight = Math.round(197 * scale);
        const cardX = Math.round(18 * scale);
        const cardY = height - cardHeight - Math.round(18 * scale);
        const headerHeight = Math.round(34 * scale);
        const sidePadding = Math.round(10 * scale);
        const dividerWidth = cardWidth - sidePadding * 2;
        const timeText = formatPatroliSecurityOverlayTime(capturedAt);
        const dayText = formatPatroliSecurityOverlayDay(capturedAt);
        const dateText = formatPatroliSecurityOverlayDate(capturedAt);
        const shiftLabel = getCleaningOBShiftLabel(
            entry.value?.form?.selected_shift,
        );
        const shiftText = `Shift: ${shiftLabel || "-"}`;
        const personnelText = `Personil: ${getPatroliSecurityPersonnelName()}`;
        const verifiedText = "Diverifikasi oleh Tim GMI";

        context.save();
        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = "high";
        context.shadowColor = "rgba(0, 0, 0, 0.45)";
        context.shadowBlur = Math.max(1, scale * 1.4);
        context.shadowOffsetX = 0;
        context.shadowOffsetY = Math.max(1, scale * 0.8);
        context.fillStyle = "rgba(15, 20, 30, 0.88)";
        context.fillRect(cardX, cardY, cardWidth, cardHeight);
        context.strokeStyle = "rgba(72, 111, 212, 0.95)";
        context.lineWidth = Math.max(2, scale * 1.4);
        context.strokeRect(cardX, cardY, cardWidth, cardHeight);
        context.fillStyle = "#2f5fc5";
        context.fillRect(cardX, cardY, cardWidth, headerHeight);
        context.fillStyle = "#ffffff";
        context.font = `700 ${Math.round(14 * scale)}px "Arial", sans-serif`;
        context.textBaseline = "middle";
        context.fillText(
            "CLEANING OB GMI",
            cardX + sidePadding,
            cardY + headerHeight / 2,
        );

        const timeBaselineY = cardY + headerHeight + Math.round(46 * scale);
        context.fillStyle = "#ffffff";
        context.textBaseline = "alphabetic";
        context.font = `700 ${Math.round(30 * scale)}px "Arial", sans-serif`;
        context.fillText(timeText, cardX + sidePadding, timeBaselineY);

        const timeMetrics = context.measureText(timeText);
        const separatorX =
            cardX + sidePadding + timeMetrics.width + Math.round(8 * scale);
        context.strokeStyle = "rgba(255, 255, 255, 0.95)";
        context.lineWidth = Math.max(2, scale * 1.1);
        context.beginPath();
        context.moveTo(
            separatorX,
            cardY + headerHeight + Math.round(10 * scale),
        );
        context.lineTo(
            separatorX,
            cardY + headerHeight + Math.round(52 * scale),
        );
        context.stroke();

        const dayDateX = separatorX + Math.round(8 * scale);
        context.font = `700 ${Math.round(12 * scale)}px "Arial", sans-serif`;
        context.fillText(
            dayText,
            dayDateX,
            cardY + headerHeight + Math.round(24 * scale),
        );
        context.font = `700 ${Math.round(11 * scale)}px "Arial", sans-serif`;
        context.fillText(
            dateText,
            dayDateX,
            cardY + headerHeight + Math.round(42 * scale),
        );

        drawPatroliSecurityDivider(
            context,
            cardX + sidePadding,
            cardY + headerHeight + Math.round(64 * scale),
            dividerWidth,
            Math.max(4, Math.round(5 * scale)),
            Math.max(3, Math.round(3 * scale)),
        );

        const iconX = cardX + sidePadding + Math.round(5 * scale);
        const textX = cardX + sidePadding + Math.round(22 * scale);

        context.font = `700 ${Math.round(11 * scale)}px "Arial", sans-serif`;
        context.fillStyle = "#ffffff";
        context.fillText(
            shiftText,
            textX,
            cardY + headerHeight + Math.round(84 * scale),
        );

        const personIconSize = Math.round(11 * scale);
        const personnelY = cardY + headerHeight + Math.round(106 * scale);
        drawPatroliSecurityPersonIcon(
            context,
            iconX,
            personnelY - Math.round(2 * scale),
            personIconSize,
        );
        context.font = `700 ${Math.round(10 * scale)}px "Arial", sans-serif`;
        context.fillText(
            personnelText,
            textX,
            personnelY + Math.round(3 * scale),
        );

        drawPatroliSecurityDivider(
            context,
            cardX + sidePadding,
            cardY + cardHeight - Math.round(26 * scale),
            dividerWidth,
            Math.max(4, Math.round(5 * scale)),
            Math.max(3, Math.round(3 * scale)),
        );

        const shieldIconSize = Math.round(10 * scale);
        const verifiedY = cardY + cardHeight - Math.round(10 * scale);
        drawPatroliSecurityShieldIcon(
            context,
            iconX,
            verifiedY - Math.round(2 * scale),
            shieldIconSize,
        );
        context.fillStyle = "#cfd5df";
        context.font = `700 ${Math.round(9 * scale)}px "Arial", sans-serif`;
        context.fillText(
            verifiedText,
            textX,
            verifiedY + Math.round(2 * scale),
        );
        context.restore();
    }

    function applyWasteTransportPhotoOverlay(
        canvas,
        capturedAt = new Date(),
        payload = {},
    ) {
        const context = canvas.getContext("2d");
        if (!context) throw new Error("Overlay foto gagal diproses.");
        const width = canvas.width;
        const height = canvas.height;
        const cardWidth = Math.max(
            180,
            Math.min(270, Math.round(width * 0.29)),
        );
        const scale = cardWidth / 230;
        const cardHeight = Math.round(222 * scale);
        const cardX = Math.round(18 * scale);
        const cardY = height - cardHeight - Math.round(18 * scale);
        const headerHeight = Math.round(34 * scale);
        const sidePadding = Math.round(10 * scale);
        const dividerWidth = cardWidth - sidePadding * 2;
        const timeText = formatPatroliSecurityOverlayTime(capturedAt);
        const dayText = formatPatroliSecurityOverlayDay(capturedAt);
        const dateText = formatPatroliSecurityOverlayDate(capturedAt);
        const periodText = `Periode: ${String(payload.period || "").trim() || "-"}`;
        const dayNoText = `Hari: ${payload.day || "-"}`;
        const handoverText = `Penyerah: ${String(payload.handoverName || "").trim() || "-"}`;
        const collectorText = `Pengangkut: ${String(payload.collectorName || "").trim() || "-"}`;
        const verifiedText = "Diverifikasi oleh Tim GMI";

        context.save();
        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = "high";
        context.shadowColor = "rgba(0, 0, 0, 0.45)";
        context.shadowBlur = Math.max(1, scale * 1.4);
        context.shadowOffsetX = 0;
        context.shadowOffsetY = Math.max(1, scale * 0.8);
        context.fillStyle = "rgba(15, 20, 30, 0.88)";
        context.fillRect(cardX, cardY, cardWidth, cardHeight);
        context.strokeStyle = "rgba(72, 111, 212, 0.95)";
        context.lineWidth = Math.max(2, scale * 1.4);
        context.strokeRect(cardX, cardY, cardWidth, cardHeight);
        context.fillStyle = "#2f5fc5";
        context.fillRect(cardX, cardY, cardWidth, headerHeight);
        context.fillStyle = "#ffffff";
        context.font = `700 ${Math.round(13 * scale)}px "Arial", sans-serif`;
        context.textBaseline = "middle";
        context.fillText(
            "PENGANGKUTAN SAMPAH GMI",
            cardX + sidePadding,
            cardY + headerHeight / 2,
        );

        const timeBaselineY = cardY + headerHeight + Math.round(46 * scale);
        context.fillStyle = "#ffffff";
        context.textBaseline = "alphabetic";
        context.font = `700 ${Math.round(30 * scale)}px "Arial", sans-serif`;
        context.fillText(timeText, cardX + sidePadding, timeBaselineY);

        const timeMetrics = context.measureText(timeText);
        const separatorX =
            cardX + sidePadding + timeMetrics.width + Math.round(8 * scale);
        context.strokeStyle = "rgba(255, 255, 255, 0.95)";
        context.lineWidth = Math.max(2, scale * 1.1);
        context.beginPath();
        context.moveTo(
            separatorX,
            cardY + headerHeight + Math.round(10 * scale),
        );
        context.lineTo(
            separatorX,
            cardY + headerHeight + Math.round(52 * scale),
        );
        context.stroke();

        const dayDateX = separatorX + Math.round(8 * scale);
        context.font = `700 ${Math.round(12 * scale)}px "Arial", sans-serif`;
        context.fillText(
            dayText,
            dayDateX,
            cardY + headerHeight + Math.round(24 * scale),
        );
        context.font = `700 ${Math.round(11 * scale)}px "Arial", sans-serif`;
        context.fillText(
            dateText,
            dayDateX,
            cardY + headerHeight + Math.round(42 * scale),
        );

        drawPatroliSecurityDivider(
            context,
            cardX + sidePadding,
            cardY + headerHeight + Math.round(64 * scale),
            dividerWidth,
            Math.max(4, Math.round(5 * scale)),
            Math.max(3, Math.round(3 * scale)),
        );

        const textX = cardX + sidePadding + Math.round(12 * scale);
        const maxTextWidth = cardX + cardWidth - textX - sidePadding;
        const lineHeight = Math.round(14 * scale);
        let cursorY = cardY + headerHeight + Math.round(80 * scale);

        context.textBaseline = "alphabetic";
        context.fillStyle = "#ffffff";
        context.font = `700 ${Math.round(11 * scale)}px "Arial", sans-serif`;
        context.fillText(periodText, textX, cursorY);
        cursorY += lineHeight + Math.round(2 * scale);

        context.fillText(dayNoText, textX, cursorY);
        cursorY += lineHeight + Math.round(2 * scale);

        context.font = `400 ${Math.round(10 * scale)}px "Arial", sans-serif`;
        cursorY = drawWrappedPatroliSecurityText(
            context,
            [handoverText],
            textX,
            cursorY,
            maxTextWidth,
            Math.round(12 * scale),
        );
        cursorY += Math.round(2 * scale);

        drawWrappedPatroliSecurityText(
            context,
            [collectorText],
            textX,
            cursorY,
            maxTextWidth,
            Math.round(12 * scale),
        );

        drawPatroliSecurityDivider(
            context,
            cardX + sidePadding,
            cardY + cardHeight - Math.round(30 * scale),
            dividerWidth,
            Math.max(4, Math.round(5 * scale)),
            Math.max(3, Math.round(3 * scale)),
        );

        const shieldIconSize = Math.round(10 * scale);
        const verifiedY = cardY + cardHeight - Math.round(10 * scale);
        drawPatroliSecurityShieldIcon(
            context,
            cardX + sidePadding + Math.round(5 * scale),
            verifiedY - Math.round(2 * scale),
            shieldIconSize,
        );
        context.fillStyle = "#cfd5df";
        context.font = `700 ${Math.round(9 * scale)}px "Arial", sans-serif`;
        context.fillText(
            verifiedText,
            cardX + sidePadding + Math.round(22 * scale),
            verifiedY + Math.round(2 * scale),
        );
        context.restore();
    }

    function normalizePatroliSecurityPhotoBucket(bucket) {
        if (Array.isArray(bucket))
            return bucket.filter((item) => String(item || "").trim() !== "");
        const single = String(bucket || "").trim();
        return single ? [single] : [];
    }

    function normalizeMaintenancePhotoBucket(bucket) {
        if (Array.isArray(bucket))
            return bucket.filter((item) => String(item || "").trim() !== "");
        const single = String(bucket || "").trim();
        return single ? [single] : [];
    }

    function normalizeCleaningOBPhotoBucket(bucket) {
        if (Array.isArray(bucket))
            return bucket.filter((item) => String(item || "").trim() !== "");
        const single = String(bucket || "").trim();
        return single ? [single] : [];
    }

    function normalizeChecklistITPhotoBucket(bucket) {
        if (Array.isArray(bucket))
            return bucket.filter((item) => String(item || "").trim() !== "");
        const single = String(bucket || "").trim();
        return single ? [single] : [];
    }

    function normalizeKompresorHarianPhotoBucket(bucket) {
        if (Array.isArray(bucket))
            return bucket.filter((item) => String(item || "").trim() !== "");
        const single = String(bucket || "").trim();
        return single ? [single] : [];
    }

    function normalizeRunningGensetPhotoBucket(bucket) {
        if (Array.isArray(bucket))
            return bucket.filter((item) => String(item || "").trim() !== "");
        const single = String(bucket || "").trim();
        return single ? [single] : [];
    }

    function normalizeChargerBateraiPhotoBucket(bucket) {
        if (Array.isArray(bucket))
            return bucket.filter((item) => String(item || "").trim() !== "");
        const single = String(bucket || "").trim();
        return single ? [single] : [];
    }

    function normalizeChecklistBateraiPhotoBucket(bucket) {
        if (Array.isArray(bucket))
            return bucket.filter((item) => String(item || "").trim() !== "");
        const single = String(bucket || "").trim();
        return single ? [single] : [];
    }

    function normalizeInspeksiLokerPhotoBucket(bucket) {
        if (Array.isArray(bucket))
            return bucket.filter((item) => String(item || "").trim() !== "");
        const single = String(bucket || "").trim();
        return single ? [single] : [];
    }

    function normalizeSaranaPrasaranaPhotoBucket(bucket) {
        if (Array.isArray(bucket))
            return bucket.filter((item) => String(item || "").trim() !== "");
        const single = String(bucket || "").trim();
        return single ? [single] : [];
    }

    function normalizeWarehousePhotoBucket(bucket) {
        if (Array.isArray(bucket))
            return bucket.filter((item) => String(item || "").trim() !== "");
        const single = String(bucket || "").trim();
        return single ? [single] : [];
    }

    function getChecklistITPhotoBucketKey() {
        return "checklist_it";
    }

    function getKompresorHarianPhotoBucketKey() {
        return kompresorHarianTargetKey.value || "kompresor_harian";
    }

    function getRunningGensetPhotoBucketKey() {
        return runningGensetTargetKey.value || "genset";
    }

    function getChargerBateraiPhotoBucketKey() {
        return chargerBateraiTargetKey.value || "charger_baterai";
    }

    function getChecklistBateraiPhotoBucketKey() {
        return checklistBateraiTargetKey.value || "checklist_baterai";
    }

    function getInspeksiLokerPhotoBucketKey() {
        return "inspeksi_loker";
    }

    function getSaranaPrasaranaPhotoBucketKey() {
        return saranaPrasaranaTargetKey.value || "sarana_prasarana";
    }

    const currentChecklistITPhotos = computed(() => {
        if (!entry.value || !isChecklistIT.value) return [];
        const targetKey = getChecklistITPhotoBucketKey();
        const paths = normalizeChecklistITPhotoBucket(
            entry.value.form.area_photo_paths?.[targetKey],
        );
        const urls = normalizeChecklistITPhotoBucket(
            entry.value.form.area_photo_urls?.[targetKey],
        );
        const names = normalizeChecklistITPhotoBucket(
            entry.value.form.area_photo_names?.[targetKey],
        );
        const length = Math.max(paths.length, urls.length, names.length);
        return Array.from({ length }, (_, index) => ({
            path: paths[index] || "",
            url: urls[index] || "",
            name: names[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );
    });

    const currentRunningGensetPhotos = computed(() => {
        if (
            !entry.value ||
            !isRunningGenset.value ||
            !runningGensetTargetKey.value
        )
            return [];
        const targetKey = getRunningGensetPhotoBucketKey();
        const paths = normalizeRunningGensetPhotoBucket(
            entry.value.form.area_photo_paths?.[targetKey],
        );
        const urls = normalizeRunningGensetPhotoBucket(
            entry.value.form.area_photo_urls?.[targetKey],
        );
        const names = normalizeRunningGensetPhotoBucket(
            entry.value.form.area_photo_names?.[targetKey],
        );
        const length = Math.max(paths.length, urls.length, names.length);
        return Array.from({ length }, (_, index) => ({
            path: paths[index] || "",
            url: urls[index] || "",
            name: names[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );
    });

    const currentKompresorHarianPhotos = computed(() => {
        if (
            !entry.value ||
            !isKompresorHarian.value ||
            !kompresorHarianTargetKey.value
        )
            return [];
        const targetKey = getKompresorHarianPhotoBucketKey();
        const paths = normalizeKompresorHarianPhotoBucket(
            entry.value.form.area_photo_paths?.[targetKey],
        );
        const urls = normalizeKompresorHarianPhotoBucket(
            entry.value.form.area_photo_urls?.[targetKey],
        );
        const names = normalizeKompresorHarianPhotoBucket(
            entry.value.form.area_photo_names?.[targetKey],
        );
        const length = Math.max(paths.length, urls.length, names.length);
        return Array.from({ length }, (_, index) => ({
            path: paths[index] || "",
            url: urls[index] || "",
            name: names[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );
    });

    const currentChargerBateraiPhotos = computed(() => {
        if (
            !entry.value ||
            !isChargerBaterai.value ||
            !chargerBateraiTargetKey.value
        )
            return [];
        const targetKey = getChargerBateraiPhotoBucketKey();
        const paths = normalizeChargerBateraiPhotoBucket(
            entry.value.form.area_photo_paths?.[targetKey],
        );
        const urls = normalizeChargerBateraiPhotoBucket(
            entry.value.form.area_photo_urls?.[targetKey],
        );
        const names = normalizeChargerBateraiPhotoBucket(
            entry.value.form.area_photo_names?.[targetKey],
        );
        const length = Math.max(paths.length, urls.length, names.length);
        return Array.from({ length }, (_, index) => ({
            path: paths[index] || "",
            url: urls[index] || "",
            name: names[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );
    });

    const currentChecklistBateraiPhotos = computed(() => {
        if (
            !entry.value ||
            !isChecklistBaterai.value ||
            !checklistBateraiTargetKey.value
        )
            return [];
        const targetKey = getChecklistBateraiPhotoBucketKey();
        const paths = normalizeChecklistBateraiPhotoBucket(
            entry.value.form.area_photo_paths?.[targetKey],
        );
        const urls = normalizeChecklistBateraiPhotoBucket(
            entry.value.form.area_photo_urls?.[targetKey],
        );
        const names = normalizeChecklistBateraiPhotoBucket(
            entry.value.form.area_photo_names?.[targetKey],
        );
        const length = Math.max(paths.length, urls.length, names.length);
        return Array.from({ length }, (_, index) => ({
            path: paths[index] || "",
            url: urls[index] || "",
            name: names[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );
    });

    const currentInspeksiLokerPhotos = computed(() => {
        if (!entry.value || !isInspeksiLoker.value) return [];
        const targetKey = getInspeksiLokerPhotoBucketKey();
        const paths = normalizeInspeksiLokerPhotoBucket(
            entry.value.form.area_photo_paths?.[targetKey],
        );
        const urls = normalizeInspeksiLokerPhotoBucket(
            entry.value.form.area_photo_urls?.[targetKey],
        );
        const names = normalizeInspeksiLokerPhotoBucket(
            entry.value.form.area_photo_names?.[targetKey],
        );
        const length = Math.max(paths.length, urls.length, names.length);
        return Array.from({ length }, (_, index) => ({
            path: paths[index] || "",
            url: urls[index] || "",
            name: names[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );
    });

    const currentSaranaPrasaranaPhotos = computed(() => {
        if (
            !entry.value ||
            !isSaranaPrasarana.value ||
            !saranaPrasaranaTargetKey.value
        )
            return [];
        const targetKey = getSaranaPrasaranaPhotoBucketKey();
        const paths = normalizeSaranaPrasaranaPhotoBucket(
            entry.value.form.area_photo_paths?.[targetKey],
        );
        const urls = normalizeSaranaPrasaranaPhotoBucket(
            entry.value.form.area_photo_urls?.[targetKey],
        );
        const names = normalizeSaranaPrasaranaPhotoBucket(
            entry.value.form.area_photo_names?.[targetKey],
        );
        const length = Math.max(paths.length, urls.length, names.length);
        return Array.from({ length }, (_, index) => ({
            path: paths[index] || "",
            url: urls[index] || "",
            name: names[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );
    });

    function updateChecklistITPhotoState(payload = {}) {
        if (!entry.value || !isChecklistIT.value) return;
        const targetKey = getChecklistITPhotoBucketKey();
        const nextPaths = { ...(entry.value.form.area_photo_paths || {}) };
        const nextUrls = { ...(entry.value.form.area_photo_urls || {}) };
        const nextNames = { ...(entry.value.form.area_photo_names || {}) };
        const pathBucket = normalizeChecklistITPhotoBucket(
            nextPaths[targetKey],
        );
        const urlBucket = normalizeChecklistITPhotoBucket(nextUrls[targetKey]);
        const nameBucket = normalizeChecklistITPhotoBucket(
            nextNames[targetKey],
        );
        const length = Math.max(
            pathBucket.length,
            urlBucket.length,
            nameBucket.length,
        );
        let photoEntries = Array.from({ length }, (_, index) => ({
            path: pathBucket[index] || "",
            url: urlBucket[index] || "",
            name: nameBucket[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );

        if (payload.clear) {
            photoEntries = [];
        } else if (payload.removePhoto) {
            let removed = false;
            photoEntries = photoEntries.filter((photo) => {
                if (removed) return true;
                const isMatch =
                    (payload.removePhoto.path &&
                        photo.path === payload.removePhoto.path) ||
                    (payload.removePhoto.url &&
                        photo.url === payload.removePhoto.url) ||
                    (payload.removePhoto.name &&
                        photo.name === payload.removePhoto.name);
                if (isMatch) {
                    removed = true;
                    return false;
                }
                return true;
            });
        } else if (typeof payload.removeIndex === "number") {
            photoEntries.splice(payload.removeIndex, 1);
        } else {
            photoEntries.push({
                path: payload.path || "",
                url: payload.url || "",
                name: payload.name || "",
            });
        }

        if (photoEntries.length === 0) {
            delete nextPaths[targetKey];
            delete nextUrls[targetKey];
            delete nextNames[targetKey];
        } else {
            nextPaths[targetKey] = photoEntries.map(
                (photo) => photo.path || "",
            );
            nextUrls[targetKey] = photoEntries.map((photo) => photo.url || "");
            nextNames[targetKey] = photoEntries.map(
                (photo) => photo.name || "",
            );
        }
        entry.value.form.area_photo_paths = nextPaths;
        entry.value.form.area_photo_urls = nextUrls;
        entry.value.form.area_photo_names = nextNames;
    }

    function updatePatroliSecurityPhotoState(payload = {}) {
        if (
            !entry.value ||
            !isPatroliSecurity.value ||
            !patroliSecurityTargetKey.value
        )
            return;
        const targetKey = patroliSecurityTargetKey.value;
        const nextPaths = { ...(entry.value.form.area_photo_paths || {}) };
        const nextUrls = { ...(entry.value.form.area_photo_urls || {}) };
        const nextNames = { ...(entry.value.form.area_photo_names || {}) };
        const pathBucket = normalizePatroliSecurityPhotoBucket(
            nextPaths[targetKey],
        );
        const urlBucket = normalizePatroliSecurityPhotoBucket(
            nextUrls[targetKey],
        );
        const nameBucket = normalizePatroliSecurityPhotoBucket(
            nextNames[targetKey],
        );
        const length = Math.max(
            pathBucket.length,
            urlBucket.length,
            nameBucket.length,
        );
        let photoEntries = Array.from({ length }, (_, index) => ({
            path: pathBucket[index] || "",
            url: urlBucket[index] || "",
            name: nameBucket[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );

        if (payload.clear) {
            photoEntries = [];
        } else if (payload.removePhoto) {
            let removed = false;
            photoEntries = photoEntries.filter((photo) => {
                if (removed) return true;
                const isMatch =
                    (payload.removePhoto.path &&
                        photo.path === payload.removePhoto.path) ||
                    (payload.removePhoto.url &&
                        photo.url === payload.removePhoto.url) ||
                    (payload.removePhoto.name &&
                        photo.name === payload.removePhoto.name);
                if (isMatch) {
                    removed = true;
                    return false;
                }
                return true;
            });
        } else if (typeof payload.removeIndex === "number") {
            photoEntries.splice(payload.removeIndex, 1);
        } else {
            photoEntries.push({
                path: payload.path || "",
                url: payload.url || "",
                name: payload.name || "",
            });
        }

        if (photoEntries.length === 0) {
            delete nextPaths[targetKey];
            delete nextUrls[targetKey];
            delete nextNames[targetKey];
        } else {
            nextPaths[targetKey] = photoEntries.map(
                (photo) => photo.path || "",
            );
            nextUrls[targetKey] = photoEntries.map((photo) => photo.url || "");
            nextNames[targetKey] = photoEntries.map(
                (photo) => photo.name || "",
            );
        }
        entry.value.form.area_photo_paths = nextPaths;
        entry.value.form.area_photo_urls = nextUrls;
        entry.value.form.area_photo_names = nextNames;
    }

    async function uploadPatroliSecurityPhoto(file) {
        if (
            !entry.value ||
            !isPatroliSecurity.value ||
            !patroliSecurityTargetKey.value ||
            !file
        )
            return;
        patroliSecurityPhotoUploading.value = true;
        patroliSecurityPhotoError.value = "";
        try {
            const formData = new FormData();
            formData.append("photo", file);
            const response = await axios.post(
                "/gmiic/checklist/patroli-security/photo",
                formData,
                { headers: { "Content-Type": "multipart/form-data" } },
            );
            updatePatroliSecurityPhotoState({
                path: response.data?.path || "",
                url: response.data?.url || "",
                name: response.data?.original_name || file.name || "",
            });
        } catch (error) {
            patroliSecurityPhotoError.value =
                error?.response?.data?.message || "Foto gagal di-upload.";
        } finally {
            patroliSecurityPhotoUploading.value = false;
        }
    }

    async function uploadChecklistITPhoto(file) {
        if (!entry.value || !isChecklistIT.value || !file) return;
        checklistITPhotoUploading.value = true;
        checklistITPhotoError.value = "";
        try {
            const formData = new FormData();
            formData.append("photo", file);
            const response = await axios.post(
                "/gmiic/checklist/checklist-it/photo",
                formData,
                { headers: { "Content-Type": "multipart/form-data" } },
            );
            updateChecklistITPhotoState({
                path: response.data?.path || "",
                url: response.data?.url || "",
                name: response.data?.original_name || file.name || "",
            });
        } catch (error) {
            checklistITPhotoError.value =
                error?.response?.data?.message || "Foto gagal di-upload.";
        } finally {
            checklistITPhotoUploading.value = false;
        }
    }

    async function removeChecklistITPhoto(index) {
        if (!entry.value || !isChecklistIT.value) return;
        checklistITPhotoError.value = "";
        const photo = currentChecklistITPhotos.value[Number(index)];
        if (!photo) return;
        const confirmed = await swalConfirm({
            title: "Hapus Foto",
            text: "Foto area ini akan dihapus. Lanjutkan?",
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Tidak",
            confirmButtonColor: "#dc2626",
        });
        if (!confirmed) return;
        try {
            if (String(photo.path || "").trim()) {
                await axios.delete("/gmiic/checklist/checklist-it/photo", {
                    data: { path: photo.path },
                });
            }
            updateChecklistITPhotoState({
                removeIndex: Number(index),
                removePhoto: photo,
            });
        } catch (error) {
            checklistITPhotoError.value =
                error?.response?.data?.message || "Foto gagal dihapus.";
        }
    }

    function updateRunningGensetPhotoState(payload = {}) {
        if (
            !entry.value ||
            !isRunningGenset.value ||
            !runningGensetTargetKey.value
        )
            return;
        const targetKey = getRunningGensetPhotoBucketKey();
        const nextPaths = { ...(entry.value.form.area_photo_paths || {}) };
        const nextUrls = { ...(entry.value.form.area_photo_urls || {}) };
        const nextNames = { ...(entry.value.form.area_photo_names || {}) };
        const pathBucket = normalizeRunningGensetPhotoBucket(
            nextPaths[targetKey],
        );
        const urlBucket = normalizeRunningGensetPhotoBucket(
            nextUrls[targetKey],
        );
        const nameBucket = normalizeRunningGensetPhotoBucket(
            nextNames[targetKey],
        );
        const length = Math.max(
            pathBucket.length,
            urlBucket.length,
            nameBucket.length,
        );
        let photoEntries = Array.from({ length }, (_, index) => ({
            path: pathBucket[index] || "",
            url: urlBucket[index] || "",
            name: nameBucket[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );

        if (payload.clear) {
            photoEntries = [];
        } else if (payload.removePhoto) {
            let removed = false;
            photoEntries = photoEntries.filter((photo) => {
                if (removed) return true;
                const isMatch =
                    (payload.removePhoto.path &&
                        photo.path === payload.removePhoto.path) ||
                    (payload.removePhoto.url &&
                        photo.url === payload.removePhoto.url) ||
                    (payload.removePhoto.name &&
                        photo.name === payload.removePhoto.name);
                if (isMatch) {
                    removed = true;
                    return false;
                }
                return true;
            });
        } else if (typeof payload.removeIndex === "number") {
            photoEntries.splice(payload.removeIndex, 1);
        } else {
            photoEntries.push({
                path: payload.path || "",
                url: payload.url || "",
                name: payload.name || "",
            });
        }

        if (photoEntries.length === 0) {
            delete nextPaths[targetKey];
            delete nextUrls[targetKey];
            delete nextNames[targetKey];
        } else {
            nextPaths[targetKey] = photoEntries.map(
                (photo) => photo.path || "",
            );
            nextUrls[targetKey] = photoEntries.map((photo) => photo.url || "");
            nextNames[targetKey] = photoEntries.map(
                (photo) => photo.name || "",
            );
        }
        entry.value.form.area_photo_paths = nextPaths;
        entry.value.form.area_photo_urls = nextUrls;
        entry.value.form.area_photo_names = nextNames;
    }

    function updateChargerBateraiPhotoState(payload = {}) {
        if (
            !entry.value ||
            !isChargerBaterai.value ||
            !chargerBateraiTargetKey.value
        )
            return;
        const targetKey = getChargerBateraiPhotoBucketKey();
        const nextPaths = { ...(entry.value.form.area_photo_paths || {}) };
        const nextUrls = { ...(entry.value.form.area_photo_urls || {}) };
        const nextNames = { ...(entry.value.form.area_photo_names || {}) };
        const pathBucket = normalizeChargerBateraiPhotoBucket(
            nextPaths[targetKey],
        );
        const urlBucket = normalizeChargerBateraiPhotoBucket(
            nextUrls[targetKey],
        );
        const nameBucket = normalizeChargerBateraiPhotoBucket(
            nextNames[targetKey],
        );
        const length = Math.max(
            pathBucket.length,
            urlBucket.length,
            nameBucket.length,
        );
        let photoEntries = Array.from({ length }, (_, index) => ({
            path: pathBucket[index] || "",
            url: urlBucket[index] || "",
            name: nameBucket[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );

        if (payload.clear) {
            photoEntries = [];
        } else if (payload.removePhoto) {
            let removed = false;
            photoEntries = photoEntries.filter((photo) => {
                if (removed) return true;
                const isMatch =
                    (payload.removePhoto.path &&
                        photo.path === payload.removePhoto.path) ||
                    (payload.removePhoto.url &&
                        photo.url === payload.removePhoto.url) ||
                    (payload.removePhoto.name &&
                        photo.name === payload.removePhoto.name);
                if (isMatch) {
                    removed = true;
                    return false;
                }
                return true;
            });
        } else if (typeof payload.removeIndex === "number") {
            photoEntries.splice(payload.removeIndex, 1);
        } else {
            photoEntries.push({
                path: payload.path || "",
                url: payload.url || "",
                name: payload.name || "",
            });
        }

        if (photoEntries.length === 0) {
            delete nextPaths[targetKey];
            delete nextUrls[targetKey];
            delete nextNames[targetKey];
        } else {
            nextPaths[targetKey] = photoEntries.map(
                (photo) => photo.path || "",
            );
            nextUrls[targetKey] = photoEntries.map((photo) => photo.url || "");
            nextNames[targetKey] = photoEntries.map(
                (photo) => photo.name || "",
            );
        }
        entry.value.form.area_photo_paths = nextPaths;
        entry.value.form.area_photo_urls = nextUrls;
        entry.value.form.area_photo_names = nextNames;
    }

    function updateChecklistBateraiPhotoState(payload = {}) {
        if (
            !entry.value ||
            !isChecklistBaterai.value ||
            !checklistBateraiTargetKey.value
        )
            return;
        const targetKey = getChecklistBateraiPhotoBucketKey();
        const nextPaths = { ...(entry.value.form.area_photo_paths || {}) };
        const nextUrls = { ...(entry.value.form.area_photo_urls || {}) };
        const nextNames = { ...(entry.value.form.area_photo_names || {}) };
        const pathBucket = normalizeChecklistBateraiPhotoBucket(
            nextPaths[targetKey],
        );
        const urlBucket = normalizeChecklistBateraiPhotoBucket(
            nextUrls[targetKey],
        );
        const nameBucket = normalizeChecklistBateraiPhotoBucket(
            nextNames[targetKey],
        );
        const length = Math.max(
            pathBucket.length,
            urlBucket.length,
            nameBucket.length,
        );
        let photoEntries = Array.from({ length }, (_, index) => ({
            path: pathBucket[index] || "",
            url: urlBucket[index] || "",
            name: nameBucket[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );

        if (payload.clear) {
            photoEntries = [];
        } else if (payload.removePhoto) {
            let removed = false;
            photoEntries = photoEntries.filter((photo) => {
                if (removed) return true;
                const isMatch =
                    (payload.removePhoto.path &&
                        photo.path === payload.removePhoto.path) ||
                    (payload.removePhoto.url &&
                        photo.url === payload.removePhoto.url) ||
                    (payload.removePhoto.name &&
                        photo.name === payload.removePhoto.name);
                if (isMatch) {
                    removed = true;
                    return false;
                }
                return true;
            });
        } else if (typeof payload.removeIndex === "number") {
            photoEntries.splice(payload.removeIndex, 1);
        } else {
            photoEntries.push({
                path: payload.path || "",
                url: payload.url || "",
                name: payload.name || "",
            });
        }

        if (photoEntries.length === 0) {
            delete nextPaths[targetKey];
            delete nextUrls[targetKey];
            delete nextNames[targetKey];
        } else {
            nextPaths[targetKey] = photoEntries.map(
                (photo) => photo.path || "",
            );
            nextUrls[targetKey] = photoEntries.map((photo) => photo.url || "");
            nextNames[targetKey] = photoEntries.map(
                (photo) => photo.name || "",
            );
        }
        entry.value.form.area_photo_paths = nextPaths;
        entry.value.form.area_photo_urls = nextUrls;
        entry.value.form.area_photo_names = nextNames;
    }

    async function uploadRunningGensetPhoto(file) {
        if (
            !entry.value ||
            !isRunningGenset.value ||
            !runningGensetTargetKey.value ||
            !file
        )
            return;
        runningGensetPhotoUploading.value = true;
        runningGensetPhotoError.value = "";
        try {
            const formData = new FormData();
            formData.append("photo", file);
            const response = await axios.post(
                "/gmiic/checklist/running-genset/photo",
                formData,
                { headers: { "Content-Type": "multipart/form-data" } },
            );
            updateRunningGensetPhotoState({
                path: response.data?.path || "",
                url: response.data?.url || "",
                name: response.data?.original_name || file.name || "",
            });
        } catch (error) {
            runningGensetPhotoError.value =
                error?.response?.data?.message || "Foto gagal di-upload.";
        } finally {
            runningGensetPhotoUploading.value = false;
        }
    }

    async function removeRunningGensetPhoto(index) {
        if (
            !entry.value ||
            !isRunningGenset.value ||
            !runningGensetTargetKey.value
        )
            return;
        runningGensetPhotoError.value = "";
        const photo = currentRunningGensetPhotos.value[Number(index)];
        if (!photo) return;
        const confirmed = await swalConfirm({
            title: "Hapus Foto",
            text: "Foto area ini akan dihapus. Lanjutkan?",
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Tidak",
            confirmButtonColor: "#dc2626",
        });
        if (!confirmed) return;
        try {
            if (String(photo.path || "").trim()) {
                await axios.delete("/gmiic/checklist/running-genset/photo", {
                    data: { path: photo.path },
                });
            }
            updateRunningGensetPhotoState({
                removeIndex: Number(index),
                removePhoto: photo,
            });
        } catch (error) {
            runningGensetPhotoError.value =
                error?.response?.data?.message || "Foto gagal dihapus.";
        }
    }

    function updateKompresorHarianPhotoState(payload = {}) {
        if (
            !entry.value ||
            !isKompresorHarian.value ||
            !kompresorHarianTargetKey.value
        )
            return;
        const targetKey = getKompresorHarianPhotoBucketKey();
        const nextPaths = { ...(entry.value.form.area_photo_paths || {}) };
        const nextUrls = { ...(entry.value.form.area_photo_urls || {}) };
        const nextNames = { ...(entry.value.form.area_photo_names || {}) };
        const pathBucket = normalizeKompresorHarianPhotoBucket(
            nextPaths[targetKey],
        );
        const urlBucket = normalizeKompresorHarianPhotoBucket(
            nextUrls[targetKey],
        );
        const nameBucket = normalizeKompresorHarianPhotoBucket(
            nextNames[targetKey],
        );
        const length = Math.max(
            pathBucket.length,
            urlBucket.length,
            nameBucket.length,
        );
        let photoEntries = Array.from({ length }, (_, index) => ({
            path: pathBucket[index] || "",
            url: urlBucket[index] || "",
            name: nameBucket[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );

        if (payload.clear) {
            photoEntries = [];
        } else if (payload.removePhoto) {
            let removed = false;
            photoEntries = photoEntries.filter((photo) => {
                if (removed) return true;
                const isMatch =
                    (payload.removePhoto.path &&
                        photo.path === payload.removePhoto.path) ||
                    (payload.removePhoto.url &&
                        photo.url === payload.removePhoto.url) ||
                    (payload.removePhoto.name &&
                        photo.name === payload.removePhoto.name);
                if (isMatch) {
                    removed = true;
                    return false;
                }
                return true;
            });
        } else if (typeof payload.removeIndex === "number") {
            photoEntries.splice(payload.removeIndex, 1);
        } else {
            photoEntries.push({
                path: payload.path || "",
                url: payload.url || "",
                name: payload.name || "",
            });
        }

        if (photoEntries.length === 0) {
            delete nextPaths[targetKey];
            delete nextUrls[targetKey];
            delete nextNames[targetKey];
        } else {
            nextPaths[targetKey] = photoEntries.map(
                (photo) => photo.path || "",
            );
            nextUrls[targetKey] = photoEntries.map((photo) => photo.url || "");
            nextNames[targetKey] = photoEntries.map(
                (photo) => photo.name || "",
            );
        }
        entry.value.form.area_photo_paths = nextPaths;
        entry.value.form.area_photo_urls = nextUrls;
        entry.value.form.area_photo_names = nextNames;
    }

    async function uploadKompresorHarianPhoto(file) {
        if (
            !entry.value ||
            !isKompresorHarian.value ||
            !kompresorHarianTargetKey.value ||
            !file
        )
            return;
        kompresorHarianPhotoUploading.value = true;
        kompresorHarianPhotoError.value = "";
        try {
            const formData = new FormData();
            formData.append("photo", file);
            const response = await axios.post(
                "/gmiic/checklist/kompresor-harian/photo",
                formData,
                { headers: { "Content-Type": "multipart/form-data" } },
            );
            updateKompresorHarianPhotoState({
                path: response.data?.path || "",
                url: response.data?.url || "",
                name: response.data?.original_name || file.name || "",
            });
        } catch (error) {
            kompresorHarianPhotoError.value =
                error?.response?.data?.message || "Foto gagal di-upload.";
        } finally {
            kompresorHarianPhotoUploading.value = false;
        }
    }

    async function removeKompresorHarianPhoto(index) {
        if (
            !entry.value ||
            !isKompresorHarian.value ||
            !kompresorHarianTargetKey.value
        )
            return;
        kompresorHarianPhotoError.value = "";
        const photo = currentKompresorHarianPhotos.value[Number(index)];
        if (!photo) return;
        const confirmed = await swalConfirm({
            title: "Hapus Foto",
            text: "Foto area ini akan dihapus. Lanjutkan?",
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Tidak",
            confirmButtonColor: "#dc2626",
        });
        if (!confirmed) return;
        try {
            if (String(photo.path || "").trim()) {
                await axios.delete("/gmiic/checklist/kompresor-harian/photo", {
                    data: { path: photo.path },
                });
            }
            updateKompresorHarianPhotoState({
                removeIndex: Number(index),
                removePhoto: photo,
            });
        } catch (error) {
            kompresorHarianPhotoError.value =
                error?.response?.data?.message || "Foto gagal dihapus.";
        }
    }

    async function uploadChargerBateraiPhoto(file) {
        if (
            !entry.value ||
            !isChargerBaterai.value ||
            !chargerBateraiTargetKey.value ||
            !file
        )
            return;
        chargerBateraiPhotoUploading.value = true;
        chargerBateraiPhotoError.value = "";
        try {
            const formData = new FormData();
            formData.append("photo", file);
            const response = await axios.post(
                "/gmiic/checklist/charger-baterai/photo",
                formData,
                { headers: { "Content-Type": "multipart/form-data" } },
            );
            updateChargerBateraiPhotoState({
                path: response.data?.path || "",
                url: response.data?.url || "",
                name: response.data?.original_name || file.name || "",
            });
        } catch (error) {
            chargerBateraiPhotoError.value =
                error?.response?.data?.message || "Foto gagal di-upload.";
        } finally {
            chargerBateraiPhotoUploading.value = false;
        }
    }

    async function removeChargerBateraiPhoto(index) {
        if (
            !entry.value ||
            !isChargerBaterai.value ||
            !chargerBateraiTargetKey.value
        )
            return;
        chargerBateraiPhotoError.value = "";
        const photo = currentChargerBateraiPhotos.value[Number(index)];
        if (!photo) return;
        const confirmed = await swalConfirm({
            title: "Hapus Foto",
            text: "Foto area ini akan dihapus. Lanjutkan?",
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Tidak",
            confirmButtonColor: "#dc2626",
        });
        if (!confirmed) return;
        try {
            if (String(photo.path || "").trim()) {
                await axios.delete("/gmiic/checklist/charger-baterai/photo", {
                    data: { path: photo.path },
                });
            }
            updateChargerBateraiPhotoState({
                removeIndex: Number(index),
                removePhoto: photo,
            });
        } catch (error) {
            chargerBateraiPhotoError.value =
                error?.response?.data?.message || "Foto gagal dihapus.";
        }
    }

    async function uploadChecklistBateraiPhoto(file) {
        if (
            !entry.value ||
            !isChecklistBaterai.value ||
            !checklistBateraiTargetKey.value ||
            !file
        )
            return;
        checklistBateraiPhotoUploading.value = true;
        checklistBateraiPhotoError.value = "";
        try {
            const formData = new FormData();
            formData.append("photo", file);
            const response = await axios.post(
                "/gmiic/checklist/checklist-baterai/photo",
                formData,
                { headers: { "Content-Type": "multipart/form-data" } },
            );
            updateChecklistBateraiPhotoState({
                path: response.data?.path || "",
                url: response.data?.url || "",
                name: response.data?.original_name || file.name || "",
            });
        } catch (error) {
            checklistBateraiPhotoError.value =
                error?.response?.data?.message || "Foto gagal di-upload.";
        } finally {
            checklistBateraiPhotoUploading.value = false;
        }
    }

    async function removeChecklistBateraiPhoto(index) {
        if (
            !entry.value ||
            !isChecklistBaterai.value ||
            !checklistBateraiTargetKey.value
        )
            return;
        checklistBateraiPhotoError.value = "";
        const photo = currentChecklistBateraiPhotos.value[Number(index)];
        if (!photo) return;
        const confirmed = await swalConfirm({
            title: "Hapus Foto",
            text: "Foto area ini akan dihapus. Lanjutkan?",
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Tidak",
            confirmButtonColor: "#dc2626",
        });
        if (!confirmed) return;
        try {
            if (String(photo.path || "").trim()) {
                await axios.delete("/gmiic/checklist/checklist-baterai/photo", {
                    data: { path: photo.path },
                });
            }
            updateChecklistBateraiPhotoState({
                removeIndex: Number(index),
                removePhoto: photo,
            });
        } catch (error) {
            checklistBateraiPhotoError.value =
                error?.response?.data?.message || "Foto gagal dihapus.";
        }
    }

    function updateInspeksiLokerPhotoState(payload = {}) {
        if (!entry.value || !isInspeksiLoker.value) return;
        const targetKey = getInspeksiLokerPhotoBucketKey();
        const nextPaths = { ...(entry.value.form.area_photo_paths || {}) };
        const nextUrls = { ...(entry.value.form.area_photo_urls || {}) };
        const nextNames = { ...(entry.value.form.area_photo_names || {}) };
        const pathBucket = normalizeInspeksiLokerPhotoBucket(
            nextPaths[targetKey],
        );
        const urlBucket = normalizeInspeksiLokerPhotoBucket(
            nextUrls[targetKey],
        );
        const nameBucket = normalizeInspeksiLokerPhotoBucket(
            nextNames[targetKey],
        );
        const length = Math.max(
            pathBucket.length,
            urlBucket.length,
            nameBucket.length,
        );
        let photoEntries = Array.from({ length }, (_, index) => ({
            path: pathBucket[index] || "",
            url: urlBucket[index] || "",
            name: nameBucket[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );

        if (payload.clear) {
            photoEntries = [];
        } else if (payload.removePhoto) {
            let removed = false;
            photoEntries = photoEntries.filter((photo) => {
                if (removed) return true;
                const isMatch =
                    (payload.removePhoto.path &&
                        photo.path === payload.removePhoto.path) ||
                    (payload.removePhoto.url &&
                        photo.url === payload.removePhoto.url) ||
                    (payload.removePhoto.name &&
                        photo.name === payload.removePhoto.name);
                if (isMatch) {
                    removed = true;
                    return false;
                }
                return true;
            });
        } else if (typeof payload.removeIndex === "number") {
            photoEntries.splice(payload.removeIndex, 1);
        } else {
            photoEntries.push({
                path: payload.path || "",
                url: payload.url || "",
                name: payload.name || "",
            });
        }

        if (photoEntries.length === 0) {
            delete nextPaths[targetKey];
            delete nextUrls[targetKey];
            delete nextNames[targetKey];
        } else {
            nextPaths[targetKey] = photoEntries.map(
                (photo) => photo.path || "",
            );
            nextUrls[targetKey] = photoEntries.map((photo) => photo.url || "");
            nextNames[targetKey] = photoEntries.map(
                (photo) => photo.name || "",
            );
        }
        entry.value.form.area_photo_paths = nextPaths;
        entry.value.form.area_photo_urls = nextUrls;
        entry.value.form.area_photo_names = nextNames;
    }

    async function uploadInspeksiLokerPhoto(file) {
        if (!entry.value || !isInspeksiLoker.value || !file) return;
        inspeksiLokerPhotoUploading.value = true;
        inspeksiLokerPhotoError.value = "";
        try {
            const formData = new FormData();
            formData.append("photo", file);
            const response = await axios.post(
                "/gmiic/checklist/inspeksi-loker/photo",
                formData,
                { headers: { "Content-Type": "multipart/form-data" } },
            );
            updateInspeksiLokerPhotoState({
                path: response.data?.path || "",
                url: response.data?.url || "",
                name: response.data?.original_name || file.name || "",
            });
        } catch (error) {
            inspeksiLokerPhotoError.value =
                error?.response?.data?.message || "Foto gagal di-upload.";
        } finally {
            inspeksiLokerPhotoUploading.value = false;
        }
    }

    async function removeInspeksiLokerPhoto(index) {
        if (!entry.value || !isInspeksiLoker.value) return;
        inspeksiLokerPhotoError.value = "";
        const photo = currentInspeksiLokerPhotos.value[Number(index)];
        if (!photo) return;
        const confirmed = await swalConfirm({
            title: "Hapus Foto",
            text: "Foto ini akan dihapus. Lanjutkan?",
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Tidak",
            confirmButtonColor: "#dc2626",
        });
        if (!confirmed) return;
        try {
            if (String(photo.path || "").trim()) {
                await axios.delete("/gmiic/checklist/inspeksi-loker/photo", {
                    data: { path: photo.path },
                });
            }
            updateInspeksiLokerPhotoState({
                removeIndex: Number(index),
                removePhoto: photo,
            });
        } catch (error) {
            inspeksiLokerPhotoError.value =
                error?.response?.data?.message || "Foto gagal dihapus.";
        }
    }

    function updateSaranaPrasaranaPhotoState(payload = {}) {
        if (
            !entry.value ||
            !isSaranaPrasarana.value ||
            !saranaPrasaranaTargetKey.value
        )
            return;
        const targetKey = getSaranaPrasaranaPhotoBucketKey();
        const nextPaths = { ...(entry.value.form.area_photo_paths || {}) };
        const nextUrls = { ...(entry.value.form.area_photo_urls || {}) };
        const nextNames = { ...(entry.value.form.area_photo_names || {}) };
        const pathBucket = normalizeSaranaPrasaranaPhotoBucket(
            nextPaths[targetKey],
        );
        const urlBucket = normalizeSaranaPrasaranaPhotoBucket(
            nextUrls[targetKey],
        );
        const nameBucket = normalizeSaranaPrasaranaPhotoBucket(
            nextNames[targetKey],
        );
        const length = Math.max(
            pathBucket.length,
            urlBucket.length,
            nameBucket.length,
        );
        let photoEntries = Array.from({ length }, (_, index) => ({
            path: pathBucket[index] || "",
            url: urlBucket[index] || "",
            name: nameBucket[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );

        if (payload.clear) {
            photoEntries = [];
        } else if (payload.removePhoto) {
            let removed = false;
            photoEntries = photoEntries.filter((photo) => {
                if (removed) return true;
                const isMatch =
                    (payload.removePhoto.path &&
                        photo.path === payload.removePhoto.path) ||
                    (payload.removePhoto.url &&
                        photo.url === payload.removePhoto.url) ||
                    (payload.removePhoto.name &&
                        photo.name === payload.removePhoto.name);
                if (isMatch) {
                    removed = true;
                    return false;
                }
                return true;
            });
        } else if (typeof payload.removeIndex === "number") {
            photoEntries.splice(payload.removeIndex, 1);
        } else {
            photoEntries.push({
                path: payload.path || "",
                url: payload.url || "",
                name: payload.name || "",
            });
        }

        if (photoEntries.length === 0) {
            delete nextPaths[targetKey];
            delete nextUrls[targetKey];
            delete nextNames[targetKey];
        } else {
            nextPaths[targetKey] = photoEntries.map(
                (photo) => photo.path || "",
            );
            nextUrls[targetKey] = photoEntries.map((photo) => photo.url || "");
            nextNames[targetKey] = photoEntries.map(
                (photo) => photo.name || "",
            );
        }
        entry.value.form.area_photo_paths = nextPaths;
        entry.value.form.area_photo_urls = nextUrls;
        entry.value.form.area_photo_names = nextNames;
    }

    async function uploadSaranaPrasaranaPhoto(file) {
        if (
            !entry.value ||
            !isSaranaPrasarana.value ||
            !saranaPrasaranaTargetKey.value ||
            !file
        )
            return;
        saranaPrasaranaPhotoUploading.value = true;
        saranaPrasaranaPhotoError.value = "";
        try {
            const formData = new FormData();
            formData.append("photo", file);
            const response = await axios.post(
                "/gmiic/checklist/sarana-prasarana/photo",
                formData,
                { headers: { "Content-Type": "multipart/form-data" } },
            );
            updateSaranaPrasaranaPhotoState({
                path: response.data?.path || "",
                url: response.data?.url || "",
                name: response.data?.original_name || file.name || "",
            });
        } catch (error) {
            saranaPrasaranaPhotoError.value =
                error?.response?.data?.message || "Foto gagal di-upload.";
        } finally {
            saranaPrasaranaPhotoUploading.value = false;
        }
    }

    async function removeSaranaPrasaranaPhoto(index) {
        if (
            !entry.value ||
            !isSaranaPrasarana.value ||
            !saranaPrasaranaTargetKey.value
        )
            return;
        saranaPrasaranaPhotoError.value = "";
        const photo = currentSaranaPrasaranaPhotos.value[Number(index)];
        if (!photo) return;
        const confirmed = await swalConfirm({
            title: "Hapus Foto",
            text: "Foto area ini akan dihapus. Lanjutkan?",
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Tidak",
            confirmButtonColor: "#dc2626",
        });
        if (!confirmed) return;
        try {
            if (String(photo.path || "").trim()) {
                await axios.delete("/gmiic/checklist/sarana-prasarana/photo", {
                    data: { path: photo.path },
                });
            }
            updateSaranaPrasaranaPhotoState({
                removeIndex: Number(index),
                removePhoto: photo,
            });
        } catch (error) {
            saranaPrasaranaPhotoError.value =
                error?.response?.data?.message || "Foto gagal dihapus.";
        }
    }

    function updateWarehousePhotoState(payload = {}) {
        if (
            !entry.value ||
            !isWarehouseSanitation.value ||
            !warehouseTargetKey.value
        )
            return;
        const targetKey = warehouseTargetKey.value;
        const nextPaths = { ...(entry.value.form.area_photo_paths || {}) };
        const nextUrls = { ...(entry.value.form.area_photo_urls || {}) };
        const nextNames = { ...(entry.value.form.area_photo_names || {}) };
        const pathBucket = normalizeWarehousePhotoBucket(
            nextPaths[targetKey],
        );
        const urlBucket = normalizeWarehousePhotoBucket(nextUrls[targetKey]);
        const nameBucket = normalizeWarehousePhotoBucket(nextNames[targetKey]);
        const length = Math.max(
            pathBucket.length,
            urlBucket.length,
            nameBucket.length,
        );
        let photoEntries = Array.from({ length }, (_, index) => ({
            path: pathBucket[index] || "",
            url: urlBucket[index] || "",
            name: nameBucket[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );

        if (payload.clear) {
            photoEntries = [];
        } else if (payload.removePhoto) {
            let removed = false;
            photoEntries = photoEntries.filter((photo) => {
                if (removed) return true;
                const isMatch =
                    (payload.removePhoto.path &&
                        photo.path === payload.removePhoto.path) ||
                    (payload.removePhoto.url &&
                        photo.url === payload.removePhoto.url) ||
                    (payload.removePhoto.name &&
                        photo.name === payload.removePhoto.name);
                if (isMatch) {
                    removed = true;
                    return false;
                }
                return true;
            });
        } else if (typeof payload.removeIndex === "number") {
            photoEntries.splice(payload.removeIndex, 1);
        } else {
            photoEntries.push({
                path: payload.path || "",
                url: payload.url || "",
                name: payload.name || "",
            });
        }

        if (photoEntries.length === 0) {
            delete nextPaths[targetKey];
            delete nextUrls[targetKey];
            delete nextNames[targetKey];
        } else {
            nextPaths[targetKey] = photoEntries.map(
                (photo) => photo.path || "",
            );
            nextUrls[targetKey] = photoEntries.map((photo) => photo.url || "");
            nextNames[targetKey] = photoEntries.map(
                (photo) => photo.name || "",
            );
        }
        entry.value.form.area_photo_paths = nextPaths;
        entry.value.form.area_photo_urls = nextUrls;
        entry.value.form.area_photo_names = nextNames;
    }

    async function uploadWarehousePhoto(file) {
        if (
            !entry.value ||
            !isWarehouseSanitation.value ||
            !warehouseTargetKey.value ||
            !file
        )
            return;
        warehousePhotoUploading.value = true;
        warehousePhotoError.value = "";
        try {
            const formData = new FormData();
            formData.append("photo", file);
            const response = await axios.post(
                "/gmiic/checklist/warehouse/photo",
                formData,
                { headers: { "Content-Type": "multipart/form-data" } },
            );
            updateWarehousePhotoState({
                path: response.data?.path || "",
                url: response.data?.url || "",
                name: response.data?.original_name || file.name || "",
            });
        } catch (error) {
            warehousePhotoError.value =
                error?.response?.data?.message || "Foto gagal di-upload.";
        } finally {
            warehousePhotoUploading.value = false;
        }
    }

    async function removeWarehousePhoto(index) {
        if (
            !entry.value ||
            !isWarehouseSanitation.value ||
            !warehouseTargetKey.value
        )
            return;
        warehousePhotoError.value = "";
        const photo = currentWarehousePhotos.value[Number(index)];
        if (!photo) return;
        const confirmed = await swalConfirm({
            title: "Hapus Foto",
            text: "Foto warehouse ini akan dihapus. Lanjutkan?",
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Tidak",
            confirmButtonColor: "#dc2626",
        });
        if (!confirmed) return;
        try {
            if (String(photo.path || "").trim()) {
                await axios.delete("/gmiic/checklist/warehouse/photo", {
                    data: { path: photo.path },
                });
            }
            updateWarehousePhotoState({
                removeIndex: Number(index),
                removePhoto: photo,
            });
        } catch (error) {
            warehousePhotoError.value =
                error?.response?.data?.message || "Foto gagal dihapus.";
        }
    }

    async function removePatroliSecurityPhoto(index) {
        if (
            !entry.value ||
            !isPatroliSecurity.value ||
            !patroliSecurityTargetKey.value
        )
            return;
        patroliSecurityPhotoError.value = "";
        const photo = currentPatroliSecurityPhotos.value[Number(index)];
        if (!photo) return;
        const confirmed = await swalConfirm({
            title: "Hapus Foto",
            text: "Foto area ini akan dihapus. Lanjutkan?",
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Tidak",
            confirmButtonColor: "#dc2626",
        });
        if (!confirmed) return;
        try {
            if (String(photo.path || "").trim()) {
                await axios.delete("/gmiic/checklist/patroli-security/photo", {
                    data: { path: photo.path },
                });
            }
            updatePatroliSecurityPhotoState({
                removeIndex: Number(index),
                removePhoto: photo,
            });
        } catch (error) {
            patroliSecurityPhotoError.value =
                error?.response?.data?.message || "Foto gagal dihapus.";
        }
    }

    function updateCleaningOBPhotoState(payload = {}) {
        if (!entry.value || !isCleaningOB.value || !cleaningOBTargetKey.value)
            return;
        const targetKey = cleaningOBTargetKey.value;
        const nextPaths = { ...(entry.value.form.area_photo_paths || {}) };
        const nextUrls = { ...(entry.value.form.area_photo_urls || {}) };
        const nextNames = { ...(entry.value.form.area_photo_names || {}) };
        const pathBucket = normalizeCleaningOBPhotoBucket(nextPaths[targetKey]);
        const urlBucket = normalizeCleaningOBPhotoBucket(nextUrls[targetKey]);
        const nameBucket = normalizeCleaningOBPhotoBucket(nextNames[targetKey]);
        const length = Math.max(
            pathBucket.length,
            urlBucket.length,
            nameBucket.length,
        );
        let photoEntries = Array.from({ length }, (_, index) => ({
            path: pathBucket[index] || "",
            url: urlBucket[index] || "",
            name: nameBucket[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );

        if (payload.clear) {
            photoEntries = [];
        } else if (payload.removePhoto) {
            let removed = false;
            photoEntries = photoEntries.filter((photo) => {
                if (removed) return true;
                const isMatch =
                    (payload.removePhoto.path &&
                        photo.path === payload.removePhoto.path) ||
                    (payload.removePhoto.url &&
                        photo.url === payload.removePhoto.url) ||
                    (payload.removePhoto.name &&
                        photo.name === payload.removePhoto.name);
                if (isMatch) {
                    removed = true;
                    return false;
                }
                return true;
            });
        } else if (typeof payload.removeIndex === "number") {
            photoEntries.splice(payload.removeIndex, 1);
        } else {
            photoEntries.push({
                path: payload.path || "",
                url: payload.url || "",
                name: payload.name || "",
            });
        }
        if (photoEntries.length === 0) {
            delete nextPaths[targetKey];
            delete nextUrls[targetKey];
            delete nextNames[targetKey];
        } else {
            nextPaths[targetKey] = photoEntries.map(
                (photo) => photo.path || "",
            );
            nextUrls[targetKey] = photoEntries.map((photo) => photo.url || "");
            nextNames[targetKey] = photoEntries.map(
                (photo) => photo.name || "",
            );
        }
        entry.value.form.area_photo_paths = nextPaths;
        entry.value.form.area_photo_urls = nextUrls;
        entry.value.form.area_photo_names = nextNames;
    }

    function updateMaintenancePhotoState(payload = {}) {
        if (
            !entry.value ||
            !isSiteVisitMaintenance.value ||
            !maintenanceScanTargetKey.value
        )
            return;
        const targetKey = maintenanceScanTargetKey.value;
        const nextPaths = { ...(entry.value.form.area_photo_paths || {}) };
        const nextUrls = { ...(entry.value.form.area_photo_urls || {}) };
        const nextNames = { ...(entry.value.form.area_photo_names || {}) };
        const pathBucket = normalizeMaintenancePhotoBucket(
            nextPaths[targetKey],
        );
        const urlBucket = normalizeMaintenancePhotoBucket(nextUrls[targetKey]);
        const nameBucket = normalizeMaintenancePhotoBucket(
            nextNames[targetKey],
        );
        const length = Math.max(
            pathBucket.length,
            urlBucket.length,
            nameBucket.length,
        );
        let photoEntries = Array.from({ length }, (_, index) => ({
            path: pathBucket[index] || "",
            url: urlBucket[index] || "",
            name: nameBucket[index] || "",
        })).filter(
            (photo) =>
                String(photo.path || photo.url || photo.name || "").trim() !==
                "",
        );

        if (payload.clear) {
            photoEntries = [];
        } else if (payload.removePhoto) {
            let removed = false;
            photoEntries = photoEntries.filter((photo) => {
                if (removed) return true;
                const isMatch =
                    (payload.removePhoto.path &&
                        photo.path === payload.removePhoto.path) ||
                    (payload.removePhoto.url &&
                        photo.url === payload.removePhoto.url) ||
                    (payload.removePhoto.name &&
                        photo.name === payload.removePhoto.name);
                if (isMatch) {
                    removed = true;
                    return false;
                }
                return true;
            });
        } else if (typeof payload.removeIndex === "number") {
            photoEntries.splice(payload.removeIndex, 1);
        } else {
            photoEntries.push({
                path: payload.path || "",
                url: payload.url || "",
                name: payload.name || "",
            });
        }
        if (photoEntries.length === 0) {
            delete nextPaths[targetKey];
            delete nextUrls[targetKey];
            delete nextNames[targetKey];
        } else {
            nextPaths[targetKey] = photoEntries.map(
                (photo) => photo.path || "",
            );
            nextUrls[targetKey] = photoEntries.map((photo) => photo.url || "");
            nextNames[targetKey] = photoEntries.map(
                (photo) => photo.name || "",
            );
        }
        entry.value.form.area_photo_paths = nextPaths;
        entry.value.form.area_photo_urls = nextUrls;
        entry.value.form.area_photo_names = nextNames;
    }

    async function uploadMaintenancePhoto(file) {
        if (
            !entry.value ||
            !isSiteVisitMaintenance.value ||
            !maintenanceScanTargetKey.value ||
            !file
        )
            return;
        maintenancePhotoUploading.value = true;
        maintenancePhotoError.value = "";
        try {
            const formData = new FormData();
            formData.append("photo", file);
            const response = await axios.post(
                "/gmiic/checklist/site-visit-maintenance/photo",
                formData,
                { headers: { "Content-Type": "multipart/form-data" } },
            );
            updateMaintenancePhotoState({
                path: response.data?.path || "",
                url: response.data?.url || "",
                name: response.data?.original_name || file.name || "",
            });
        } catch (error) {
            maintenancePhotoError.value =
                error?.response?.data?.message || "Foto gagal di-upload.";
        } finally {
            maintenancePhotoUploading.value = false;
        }
    }

    async function removeMaintenancePhoto(index) {
        if (
            !entry.value ||
            !isSiteVisitMaintenance.value ||
            !maintenanceScanTargetKey.value
        )
            return;
        maintenancePhotoError.value = "";
        const photo = currentMaintenancePhotos.value[Number(index)];
        if (!photo) return;
        const confirmed = await swalConfirm({
            title: "Hapus Foto",
            text: "Foto area ini akan dihapus. Lanjutkan?",
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Tidak",
            confirmButtonColor: "#dc2626",
        });
        if (!confirmed) return;
        try {
            if (String(photo.path || "").trim()) {
                await axios.delete(
                    "/gmiic/checklist/site-visit-maintenance/photo",
                    { data: { path: photo.path } },
                );
            }
            updateMaintenancePhotoState({
                removeIndex: Number(index),
                removePhoto: photo,
            });
        } catch (error) {
            maintenancePhotoError.value =
                error?.response?.data?.message || "Foto gagal dihapus.";
        }
    }

    async function uploadCleaningOBPhoto(file) {
        if (
            !entry.value ||
            !isCleaningOB.value ||
            !cleaningOBTargetKey.value ||
            !file
        )
            return;
        cleaningOBPhotoUploading.value = true;
        cleaningOBPhotoError.value = "";
        try {
            const formData = new FormData();
            formData.append("photo", file);
            const response = await axios.post(
                "/gmiic/checklist/cleaning-ob/photo",
                formData,
                { headers: { "Content-Type": "multipart/form-data" } },
            );
            updateCleaningOBPhotoState({
                path: response.data?.path || "",
                url: response.data?.url || "",
                name: response.data?.original_name || file.name || "",
            });
        } catch (error) {
            cleaningOBPhotoError.value =
                error?.response?.data?.message || "Foto gagal di-upload.";
        } finally {
            cleaningOBPhotoUploading.value = false;
        }
    }

    async function removeCleaningOBPhoto(index) {
        if (!entry.value || !isCleaningOB.value || !cleaningOBTargetKey.value)
            return;
        cleaningOBPhotoError.value = "";
        const photo = currentCleaningOBPhotos.value[Number(index)];
        if (!photo) return;
        const confirmed = await swalConfirm({
            title: "Hapus Foto",
            text: "Foto shift ini akan dihapus. Lanjutkan?",
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Tidak",
            confirmButtonColor: "#dc2626",
        });
        if (!confirmed) return;
        try {
            if (String(photo.path || "").trim()) {
                await axios.delete("/gmiic/checklist/cleaning-ob/photo", {
                    data: { path: photo.path },
                });
            }
            updateCleaningOBPhotoState({
                removeIndex: Number(index),
                removePhoto: photo,
            });
        } catch (error) {
            cleaningOBPhotoError.value =
                error?.response?.data?.message || "Foto gagal dihapus.";
        }
    }

    async function capturePhoto() {
        if (!entry.value || !photoVideoRef.value || photoCapturing.value)
            return;
        const video = photoVideoRef.value;
        if (!video.videoWidth || !video.videoHeight) {
            photoError.value =
                "Kamera belum siap. Tunggu sesaat lalu coba lagi.";
            return;
        }
        photoCapturing.value = true;
        const width = video.videoWidth;
        const height = video.videoHeight;
        const canvas = document.createElement("canvas");
        canvas.width = width;
        canvas.height = height;
        const context = canvas.getContext("2d");
        if (!context) {
            photoError.value = "Foto gagal diproses.";
            photoCapturing.value = false;
            return;
        }
        context.drawImage(video, 0, 0, width, height);

        try {
            if (photoCaptureMode.value === "waste_transport") {
                if (!isWasteTransport.value || !photoCaptureDay.value) return;
                const matchedRow = wasteTransportRows.value.find(
                    (row) =>
                        Number(row.day) === Number(photoCaptureDay.value),
                ) || {};
                applyWasteTransportPhotoOverlay(canvas, new Date(), {
                    day: photoCaptureDay.value,
                    period: entry.value?.form?.period,
                    handoverName: matchedRow.handover_name,
                    collectorName: matchedRow.collector_name,
                });
                const preview = canvas.toDataURL("image/jpeg", 0.9);
                const fileName = `foto-pengangkut-hari-${photoCaptureDay.value}.jpg`;
                entry.value.form.rows = wasteTransportRows.value.map((row) =>
                    row.day === photoCaptureDay.value
                        ? {
                              ...row,
                              collector_photo_name: fileName,
                              collector_photo_preview: preview,
                          }
                        : row,
                );
                await closePhotoModal();
                return;
            }
            if (photoCaptureMode.value === "patroli_security") {
                if (!isPatroliSecurity.value || !patroliSecurityTargetKey.value)
                    return;
                applyPatroliSecurityPhotoOverlay(canvas, new Date());
                const selectedAreaLabel = getPatroliSecurityAreaLabel(
                    entry.value.form.selected_area,
                )
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, "-")
                    .replace(/(^-|-$)/g, "");
                const file = await canvasToJpegFile(
                    canvas,
                    `patroli-security-${selectedAreaLabel || "area"}-${Date.now()}.jpg`,
                );
                await uploadPatroliSecurityPhoto(file);
                await closePhotoModal();
                return;
            }
            if (photoCaptureMode.value === "maintenance") {
                if (
                    !isSiteVisitMaintenance.value ||
                    !maintenanceScanTargetKey.value
                )
                    return;
                const file = await canvasToJpegFile(
                    canvas,
                    `site-visit-maintenance-${maintenanceScanTargetKey.value}-${Date.now()}.jpg`,
                );
                await uploadMaintenancePhoto(file);
                await closePhotoModal();
                return;
            }
            if (photoCaptureMode.value === "cleaning_ob") {
                if (!isCleaningOB.value || !cleaningOBTargetKey.value) return;
                applyCleaningOBPhotoOverlay(canvas, new Date());
                const selectedShiftLabel = getCleaningOBShiftLabel(
                    entry.value.form.selected_shift,
                )
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, "-")
                    .replace(/(^-|-$)/g, "");
                const file = await canvasToJpegFile(
                    canvas,
                    `cleaning-ob-${selectedShiftLabel || "shift"}-${Date.now()}.jpg`,
                );
                await uploadCleaningOBPhoto(file);
                await closePhotoModal();
                return;
            }
            if (photoCaptureMode.value === "checklist_it") {
                if (!isChecklistIT.value) return;
                const file = await canvasToJpegFile(
                    canvas,
                    `checklist-it-${Date.now()}.jpg`,
                );
                await uploadChecklistITPhoto(file);
                await closePhotoModal();
                return;
            }
            if (photoCaptureMode.value === "running_genset") {
                if (!isRunningGenset.value || !runningGensetTargetKey.value)
                    return;
                const file = await canvasToJpegFile(
                    canvas,
                    `running-genset-${runningGensetTargetKey.value}-${Date.now()}.jpg`,
                );
                await uploadRunningGensetPhoto(file);
                await closePhotoModal();
                return;
            }
            if (photoCaptureMode.value === "kompresor_harian") {
                if (!isKompresorHarian.value || !kompresorHarianTargetKey.value)
                    return;
                const file = await canvasToJpegFile(
                    canvas,
                    `kompresor-harian-${kompresorHarianTargetKey.value}-${Date.now()}.jpg`,
                );
                await uploadKompresorHarianPhoto(file);
                await closePhotoModal();
                return;
            }
            if (photoCaptureMode.value === "charger_baterai") {
                if (!isChargerBaterai.value || !chargerBateraiTargetKey.value)
                    return;
                const file = await canvasToJpegFile(
                    canvas,
                    `charger-baterai-${chargerBateraiTargetKey.value}-${Date.now()}.jpg`,
                );
                await uploadChargerBateraiPhoto(file);
                await closePhotoModal();
                return;
            }
            if (photoCaptureMode.value === "checklist_baterai") {
                if (!isChecklistBaterai.value || !checklistBateraiTargetKey.value)
                    return;
                const file = await canvasToJpegFile(
                    canvas,
                    `checklist-baterai-${checklistBateraiTargetKey.value}-${Date.now()}.jpg`,
                );
                await uploadChecklistBateraiPhoto(file);
                await closePhotoModal();
                return;
            }
            if (photoCaptureMode.value === "inspeksi_loker") {
                if (!isInspeksiLoker.value) return;
                const file = await canvasToJpegFile(
                    canvas,
                    `inspeksi-loker-${Date.now()}.jpg`,
                );
                await uploadInspeksiLokerPhoto(file);
                await closePhotoModal();
                return;
            }
            if (photoCaptureMode.value === "sarana_prasarana") {
                if (!isSaranaPrasarana.value || !saranaPrasaranaTargetKey.value)
                    return;
                const file = await canvasToJpegFile(
                    canvas,
                    `sarana-prasarana-${saranaPrasaranaTargetKey.value}-${Date.now()}.jpg`,
                );
                await uploadSaranaPrasaranaPhoto(file);
                await closePhotoModal();
                return;
            }
            if (photoCaptureMode.value === "warehouse_sanitation") {
                if (!isWarehouseSanitation.value || !warehouseTargetKey.value)
                    return;
                const file = await canvasToJpegFile(
                    canvas,
                    `warehouse-${warehouseTargetKey.value}-${Date.now()}.jpg`,
                );
                await uploadWarehousePhoto(file);
                await closePhotoModal();
                return;
            }
        } catch (error) {
            photoError.value = error?.message || "Foto gagal diproses.";
        } finally {
            photoCapturing.value = false;
        }
    }

    async function closePhotoModal() {
        photoModalOpen.value = false;
        photoLoading.value = false;
        photoError.value = "";
        photoCaptureDay.value = null;
        photoCaptureMode.value = "";
        await stopPhotoCamera();
    }

    async function stopPhotoCamera() {
        if (photoVideoRef.value?.srcObject)
            photoVideoRef.value.srcObject = null;
        if (photoStream)
            photoStream.getTracks().forEach((track) => track.stop());
        photoStream = null;
    }

    return {
        photoModalOpen,
        photoLoading,
        photoCapturing,
        photoError,
        photoCaptureDay,
        photoCaptureMode,
        photoVideoRef,
        photoGalleryInput,
        patroliSecurityPhotoUploading,
        patroliSecurityPhotoError,
        maintenancePhotoUploading,
        maintenancePhotoError,
        cleaningOBPhotoUploading,
        cleaningOBPhotoError,
        checklistITPhotoUploading,
        checklistITPhotoError,
        runningGensetPhotoUploading,
        runningGensetPhotoError,
        kompresorHarianPhotoUploading,
        kompresorHarianPhotoError,
        chargerBateraiPhotoUploading,
        chargerBateraiPhotoError,
        checklistBateraiPhotoUploading,
        checklistBateraiPhotoError,
        inspeksiLokerPhotoUploading,
        inspeksiLokerPhotoError,
        saranaPrasaranaPhotoUploading,
        saranaPrasaranaPhotoError,
        warehousePhotoUploading,
        warehousePhotoError,
        photoModalTitle,
        photoModalDescription,
        photoCaptureButtonLabel,
        openWasteTransportCamera,
        openWasteTransportGallery,
        handleWasteTransportGalleryFile,
        openPatroliSecurityCamera,
        openMaintenanceCamera,
        openCleaningOBCamera,
        openChecklistITCamera,
        openKompresorHarianCamera,
        openRunningGensetCamera,
        openChargerBateraiCamera,
        openChecklistBateraiCamera,
        openInspeksiLokerCamera,
        openSaranaPrasaranaCamera,
        openWarehouseCamera,
        capturePhoto,
        closePhotoModal,
        stopPhotoCamera,
        removePatroliSecurityPhoto,
        removeMaintenancePhoto,
        removeCleaningOBPhoto,
        removeChecklistITPhoto,
        removeKompresorHarianPhoto,
        removeRunningGensetPhoto,
        removeChargerBateraiPhoto,
        removeChecklistBateraiPhoto,
        removeInspeksiLokerPhoto,
        removeSaranaPrasaranaPhoto,
        removeWarehousePhoto,
        updatePatroliSecurityPhotoState,
        updateMaintenancePhotoState,
        updateCleaningOBPhotoState,
        updateChecklistITPhotoState,
        updateKompresorHarianPhotoState,
        updateRunningGensetPhotoState,
        updateChargerBateraiPhotoState,
        updateChecklistBateraiPhotoState,
        updateInspeksiLokerPhotoState,
        updateSaranaPrasaranaPhotoState,
        updateWarehousePhotoState,
        uploadPatroliSecurityPhoto,
        uploadMaintenancePhoto,
        uploadCleaningOBPhoto,
        uploadChecklistITPhoto,
        uploadKompresorHarianPhoto,
        uploadRunningGensetPhoto,
        uploadChargerBateraiPhoto,
        uploadChecklistBateraiPhoto,
        uploadInspeksiLokerPhoto,
        uploadSaranaPrasaranaPhoto,
        uploadWarehousePhoto,
        normalizePatroliSecurityPhotoBucket,
        normalizeMaintenancePhotoBucket,
        normalizeCleaningOBPhotoBucket,
        normalizeChecklistITPhotoBucket,
        normalizeKompresorHarianPhotoBucket,
        normalizeRunningGensetPhotoBucket,
        normalizeChargerBateraiPhotoBucket,
        normalizeChecklistBateraiPhotoBucket,
        normalizeInspeksiLokerPhotoBucket,
        normalizeSaranaPrasaranaPhotoBucket,
        normalizeWarehousePhotoBucket,
        currentChecklistITPhotos,
        currentKompresorHarianPhotos,
        currentRunningGensetPhotos,
        currentChargerBateraiPhotos,
        currentChecklistBateraiPhotos,
        currentInspeksiLokerPhotos,
        currentSaranaPrasaranaPhotos,
    };
}
