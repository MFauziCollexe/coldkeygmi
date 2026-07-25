import {
    getDaysInPeriod,
    toPeriodValue,
    toDateInputValue,
    formatDateTimeDisplay,
    formatDateDisplay,
} from "./date-utils";
import {
    sanitationAreaOptions,
    warehouseAreaOptions,
    warehouseCleanlinessRowsByFrequency,
    warehouseIceControlRows,
    warehouseCleaningMaterialRows,
    personalHygieneRows,
} from "./location-data";

export function createWasteTransportRows(periodValue) {
    return getDaysInPeriod(periodValue).map((dayInfo) => ({
        day: dayInfo.day,
        handover_name: "",
        pickup_time: "",
        collector_name: "",
        collector_photo_name: "",
        collector_photo_preview: "",
    }));
}

export function rebuildWasteTransportRows(periodValue, existingRows = []) {
    return getDaysInPeriod(periodValue).map((dayInfo) => {
        const matchedRow = existingRows.find(
            (row) => Number(row.day) === dayInfo.day,
        );

        return {
            day: dayInfo.day,
            handover_name: matchedRow?.handover_name || "",
            pickup_time: matchedRow?.pickup_time || "",
            collector_name: matchedRow?.collector_name || "",
            collector_photo_name: matchedRow?.collector_photo_name || "",
            collector_photo_preview: matchedRow?.collector_photo_preview || "",
        };
    });
}

export function createWasteTransportEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);

    return {
        id: `pengangkutan_sampah_pt_sier-${Date.now()}`,
        template_id: "pengangkutan_sampah_pt_sier",
        name: "Pengangkutan Sampah PT SIER",
        created_at: formatDateTimeDisplay(now),
        form: {
            period,
            date: formatDateDisplay(now),
            pic: userName || "User Login",
            approved: false,
            approved_days: [],
            rows: createWasteTransportRows(period),
        },
    };
}

export function createPersonalHygieneDayMap(periodValue) {
    return getDaysInPeriod(periodValue).reduce((result, dayInfo) => {
        result[dayInfo.day] = "";
        return result;
    }, {});
}

export function createPersonalHygieneRows(periodValue) {
    return personalHygieneRows.map((row, index) => ({
        no: index + 1,
        id: row.id,
        name: row.name,
        days: createPersonalHygieneDayMap(periodValue),
    }));
}

export function rebuildPersonalHygieneRows(periodValue, existingRows = []) {
    const baseRows = createPersonalHygieneRows(periodValue);

    return baseRows.map((row) => {
        const matchedRow = existingRows.find((item) => item.id === row.id);
        if (!matchedRow) {
            return row;
        }

        const nextDays = { ...row.days };
        Object.keys(nextDays).forEach((day) => {
            if (
                Object.prototype.hasOwnProperty.call(matchedRow.days || {}, day)
            ) {
                const value = matchedRow.days[day];
                nextDays[day] = value === true ? "yes" : value || "";
            }
        });

        return {
            ...row,
            days: nextDays,
        };
    });
}

export function createPersonalHygieneEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);

    return {
        id: `personal_hygiene_karyawan-${Date.now()}`,
        template_id: "personal_hygiene_karyawan",
        name: "Personal Hygiene Karyawan",
        created_at: formatDateTimeDisplay(now),
        form: {
            year: String(now.getFullYear()),
            period,
            employee_name: "",
            gender: "",
            nik: "",
            bagian: "",
            approved: false,
            generated_at: "",
            rows: createPersonalHygieneRows(period),
            generated_employees: [],
        },
    };
}

export function createWarehouseStatusMap() {
    return {
        clean_condition: "",
        no_ice_pooling: "",
        no_odor: "",
        note: "",
    };
}

export function createWarehouseBooleanMap() {
    return {
        halal: "",
        dosage: "",
        note: "",
        material_name: "",
    };
}

export function getWarehouseCleanlinessRows(frequency = "daily") {
    return (
        warehouseCleanlinessRowsByFrequency[frequency] ||
        warehouseCleanlinessRowsByFrequency.daily
    );
}

export function buildWarehouseAreaRows(frequency = "daily", existingRows = []) {
    return getWarehouseCleanlinessRows(frequency).map((row, index) => {
        const matchedRow = existingRows.find((item) => item.id === row.id);

        return {
            no: index + 1,
            id: row.id,
            name: row.name,
            clean_condition: matchedRow?.clean_condition || "",
            no_ice_pooling: matchedRow?.no_ice_pooling || "",
            no_odor: matchedRow?.no_odor || "",
            note: matchedRow?.note || "",
        };
    });
}

export function createWarehouseSanitationEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);
    const frequency = "daily";

    return {
        id: `warehouse_sanitation-${Date.now()}`,
        template_id: "warehouse_sanitation_1",
        name: "Kebersihan dan Sanitasi (Warehouse Area)",
        created_at: formatDateTimeDisplay(now),
        form: {
            frequency,
            date: formatDateDisplay(now),
            period,
            barcode: "",
            scan_date: "",
            room_temperature: "",
            petugas: userName || "User Login",
            hse: "",
            selected_areas: [],
            approved: false,
            area_rows: buildWarehouseAreaRows(frequency),
            ice_control_rows: warehouseIceControlRows.map((row, index) => ({
                no: index + 1,
                id: row.id,
                name: row.name,
                status: "",
                note: "",
            })),
            cleaning_material_rows: warehouseCleaningMaterialRows.map(
                (row) => ({
                    no: row.no,
                    id: row.id,
                    ...createWarehouseBooleanMap(),
                }),
            ),
            verification: {
                prepared_name: "",
                prepared_signature: "",
                prepared_date: "",
                verified_name: "",
                verified_signature: "",
                verified_date: "",
            },
        },
    };
}

export function createSanitationDayMap(periodValue) {
    return getDaysInPeriod(periodValue).reduce((result, dayInfo) => {
        result[dayInfo.day] = false;
        return result;
    }, {});
}

export function createSanitationRows(areaId, periodValue) {
    const selectedArea =
        sanitationAreaOptions.find((area) => area.id === areaId) ||
        sanitationAreaOptions[0];

    return selectedArea.items.map((itemName, index) => ({
        id: `${selectedArea.id}-${index + 1}`,
        name: itemName,
        days: createSanitationDayMap(periodValue),
    }));
}

export function createSanitationRowsByArea(periodValue) {
    return sanitationAreaOptions.reduce((result, area) => {
        result[area.id] = createSanitationRows(area.id, periodValue);
        return result;
    }, {});
}

export function rebuildSanitationRows(areaId, periodValue, existingRows = []) {
    const baseRows = createSanitationRows(areaId, periodValue);

    return baseRows.map((row) => {
        const matchedRow = existingRows.find((item) => item.name === row.name);
        if (!matchedRow) {
            return row;
        }

        const nextDays = { ...row.days };
        Object.keys(nextDays).forEach((day) => {
            if (
                Object.prototype.hasOwnProperty.call(matchedRow.days || {}, day)
            ) {
                nextDays[day] = Boolean(matchedRow.days[day]);
            }
        });

        return {
            ...row,
            days: nextDays,
        };
    });
}

export function rebuildAllSanitationRowsByArea(
    periodValue,
    existingRowsByArea = {},
) {
    return sanitationAreaOptions.reduce((result, area) => {
        result[area.id] = rebuildSanitationRows(
            area.id,
            periodValue,
            existingRowsByArea?.[area.id] || [],
        );

        return result;
    }, {});
}

export function createNonWarehouseSanitationEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);
    const defaultArea = sanitationAreaOptions[0];
    const rowsByArea = createSanitationRowsByArea(period);

    return {
        id: `non_warehouse_sanitation-${Date.now()}`,
        template_id: "non_warehouse_sanitation",
        name: "Kebersihan dan Sanitasi (Non-Warehouse Area)",
        created_at: formatDateTimeDisplay(now),
        form: {
            period,
            area: defaultArea.id,
            pic: userName || "User Login",
            document_no: "FRM/HSE/02/02",
            rev: "00",
            approved: false,
            approved_days: [],
            submitted_days: [],
            approval_requests_by_day: {},
            area_scans_by_day: {},
            area_notes: {},
            date: formatDateDisplay(now),
            verifier: "HSE",
            verifier_title: "Diperiksa oleh,",
            rows_by_area: rowsByArea,
        },
    };
}

const INSPEKSI_LOKER_LOCKER_COUNT = 32;
const INSPEKSI_LOKER_PARAMETERS = [
    "Loker dalam kondisi bersih",
    "Loker dalam kondisi rapi dan teratur",
    "Tidak terdapat sampah di dalam loker",
    "Tidak terdapat makanan terbuka di dalam loker",
    "Tidak terdapat bahan kimia tanpa izin",
    "Tidak terdapat obat-obatan yang tidak diperbolehkan",
    "Tidak terdapat minuman beralkohol",
    "Tidak terdapat narkotika",
    "Tidak terdapat senjata api dan senjata tajam",
    "Tidak terdapat aset perusahaan yang disimpan tanpa izin",
    "Tidak terdapat aktivitas hama",
    "Loker dalam kondisi layak",
];

function createInspeksiLokerRow(index) {
    const lockers = {};
    for (let i = 1; i <= INSPEKSI_LOKER_LOCKER_COUNT; i += 1) {
        lockers[String(i)] = "";
    }

    return {
        no: index + 1,
        key: `parameter_${index + 1}`,
        label: INSPEKSI_LOKER_PARAMETERS[index],
        lockers,
    };
}

export function createInspeksiLokerRows() {
    return INSPEKSI_LOKER_PARAMETERS.map((_, index) =>
        createInspeksiLokerRow(index),
    );
}

export function rebuildInspeksiLokerRows(existingRows = []) {
    return INSPEKSI_LOKER_PARAMETERS.map((_, index) => {
        const matchedRow =
            existingRows.find((row) => Number(row.no) === index + 1) || {};
        const lockers = {};
        for (let i = 1; i <= INSPEKSI_LOKER_LOCKER_COUNT; i += 1) {
            lockers[String(i)] = String(matchedRow.lockers?.[String(i)] || "");
        }

        return {
            no: index + 1,
            key: `parameter_${index + 1}`,
            label: INSPEKSI_LOKER_PARAMETERS[index],
            lockers,
        };
    });
}

export function createChecklistITEntry(userName) {
    const now = new Date();

    return {
        id: `checklist_it-${Date.now()}`,
        template_id: "checklist_it",
        name: "Checklist IT",
        created_at: formatDateTimeDisplay(now),
        form: {
            week_value: `${now.getFullYear()}-W${String(getWeekNumber(now)).padStart(2, "0")}`,
            pic: userName || "User Login",
            check_type: "hardware",
            check_items: {
                "LP-001": {
                    no: 1,
                    name: "LP-001 - Manager Finance - Yoga - Laptop - Ruang Staff",
                    user: "Yoga",
                    type: "Laptop",
                    location: "Ruang Staff",
                    status: "",
                },
                "LP-002": {
                    no: 2,
                    name: "LP-002 - Staff Finance - Ilham - Laptop - Ruang Staff",
                    user: "Ilham",
                    type: "Laptop",
                    location: "Ruang Staff",
                    status: "",
                },
                "LP-003": {
                    no: 3,
                    name: "LP-003 - Staff HSE - Nata - Laptop - Ruang Staff",
                    user: "Nata",
                    type: "Laptop",
                    location: "Ruang Staff",
                    status: "",
                },
                "LP-004": {
                    no: 4,
                    name: "LP-004 - SPV HRD - Diah - Laptop - Ruang Staff",
                    user: "Diah",
                    type: "Laptop",
                    location: "Ruang Staff",
                    status: "",
                },
                "LP-005": {
                    no: 5,
                    name: "LP-005 - Manager Commercial - Nazumah - Laptop - Ruang Staff",
                    user: "Nazumah",
                    type: "Laptop",
                    location: "Ruang Staff",
                    status: "",
                },
                "LP-006": {
                    no: 6,
                    name: "LP-006 - SPV Risk Control - Chandra - Laptop - Ruang Admin",
                    user: "Chandra",
                    type: "Laptop",
                    location: "Ruang Admin",
                    status: "",
                },
                "LP-007": {
                    no: 7,
                    name: "LP-007 - Staff IT - Fauzi - Laptop - Ruang IT",
                    user: "Fauzi",
                    type: "Laptop",
                    location: "Ruang IT",
                    status: "",
                },
                "PC-001": {
                    no: 8,
                    name: "PC-001 - Staff Leader Admin - Rahayu - PC - Ruang Admin",
                    user: "Rahayu",
                    type: "PC",
                    location: "Ruang Admin",
                    status: "",
                },
                "PC-002": {
                    no: 9,
                    name: "PC-002 - Staff Admin - Sofia/Rofiq - PC - Ruang Admin",
                    user: "Sofia/Rofiq",
                    type: "PC",
                    location: "Ruang Admin",
                    status: "",
                },
                "PC-003": {
                    no: 10,
                    name: "PC-003 - Staff Risk Control - Chandra - PC - Ruang Admin",
                    user: "Chandra",
                    type: "PC",
                    location: "Ruang Admin",
                    status: "",
                },
                "PC-004": {
                    no: 11,
                    name: "PC-004 - Staff Inventory - Said/Imanda - PC - Ruang Admin",
                    user: "Said/Imanda",
                    type: "PC",
                    location: "Ruang Admin",
                    status: "",
                },
                "PC-005": {
                    no: 12,
                    name: "PC-005 - Staff Risk Control - Tio - PC - Ruang Admin",
                    user: "Tio",
                    type: "PC",
                    location: "Ruang Admin",
                    status: "",
                },
                "PC-006": {
                    no: 13,
                    name: "PC-006 - Staff Maintenance - Sultan - PC - Ruang Maintanance",
                    user: "Sultan",
                    type: "PC",
                    location: "Ruang Maintanance",
                    status: "",
                },
                "PC-007": {
                    no: 14,
                    name: "PC-007 - Staff Security - SCR - PC - Ruang Security",
                    user: "SCR",
                    type: "PC",
                    location: "Ruang Security",
                    status: "",
                },
                "PRN-001": {
                    no: 15,
                    name: "PRN-001 - Epson LQ 590 - Admin - Printer - Ruang Admin",
                    user: "Admin",
                    type: "Printer",
                    location: "Ruang Admin",
                    status: "",
                },
                "PRN-002": {
                    no: 16,
                    name: "PRN-002 - Epson LQ 590 - Admin - Printer - Ruang Admin",
                    user: "Admin",
                    type: "Printer",
                    location: "Ruang Admin",
                    status: "",
                },
                "PRN-003": {
                    no: 17,
                    name: "PRN-003 - SATO Print Label - Admin - Printer - Ruang Admin",
                    user: "Admin",
                    type: "Printer",
                    location: "Ruang Admin",
                    status: "",
                },
                "PRN-004": {
                    no: 18,
                    name: "PRN-004 - Epson L120 - Staff - Printer - Ruang Staff",
                    user: "Staff",
                    type: "Printer",
                    location: "Ruang Staff",
                    status: "",
                },
            },
            note: "",
            approved: false,
        },
    };
}

export function createInspeksiLokerEntry(userName) {
    const now = new Date();

    return {
        id: `inspeksi_loker-${Date.now()}`,
        template_id: "inspeksi_loker",
        name: "Inspeksi Loker",
        created_at: formatDateTimeDisplay(now),
        form: {
            date_value: `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`,
            pic: userName || "User Login",
            document_no: "FRM.HSE.16.01",
            rev: "00",
            effective_date: formatDateDisplay(now),
            page: "1 dari 1",
            approved: false,
            rows: createInspeksiLokerRows(),
        },
    };
}

function getWeekNumber(date) {
    const startOfYear = new Date(date.getFullYear(), 0, 1);
    const days = Math.floor((date - startOfYear) / (24 * 60 * 60 * 1000));
    return Math.floor((days + startOfYear.getDay() + 6) / 7);
}
