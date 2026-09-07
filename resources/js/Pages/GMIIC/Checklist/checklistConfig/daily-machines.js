import {
    getDaysInPeriod,
    toPeriodValue,
    toDateInputValue,
    formatDateTimeDisplay,
    formatDateDisplay,
} from "./date-utils";

export function createKompresorDailyRows(periodValue) {
    return getDaysInPeriod(periodValue).map((dayInfo) => ({
        day: dayInfo.day,
        date: dayInfo.date,
        status_mesin: "",
        visual_bersih: "",
        visual_kotor: "",
        tek_suct: "",
        tek_disch: "",
        delta_tekanan_oli: "",
        check_1: "",
        check_2: "",
        check_3: "",
        check_4: "",
        tambah_grease_motor: "",
        tambah_oli: "",
        hours_meter: "",
    }));
}

export function rebuildKompresorDailyRows(periodValue, existingRows = []) {
    return createKompresorDailyRows(periodValue).map((row) => {
        const matchedRow = existingRows.find(
            (item) => Number(item.day) === Number(row.day),
        );

        return {
            ...row,
            status_mesin: matchedRow?.status_mesin || "",
            visual_bersih: matchedRow?.visual_bersih || "",
            visual_kotor: matchedRow?.visual_kotor || "",
            tek_suct: matchedRow?.tek_suct || "",
            tek_disch: matchedRow?.tek_disch || "",
            delta_tekanan_oli: matchedRow?.delta_tekanan_oli || "",
            check_1: matchedRow?.check_1 || "",
            check_2: matchedRow?.check_2 || "",
            check_3: matchedRow?.check_3 || "",
            check_4: matchedRow?.check_4 || "",
            tambah_grease_motor: matchedRow?.tambah_grease_motor || "",
            tambah_oli: matchedRow?.tambah_oli || "",
            hours_meter: matchedRow?.hours_meter || "",
        };
    });
}

export function createChargerBateraiRows(periodValue) {
    return getDaysInPeriod(periodValue).map((dayInfo) => ({
        day: dayInfo.day,
        date: dayInfo.date,
        switch_on_off: "",
        kondisi_fisik: "",
        kabel_konektor: "",
        legrand: "",
        display_charger: "",
        temuan: "",
        tindakan: "",
    }));
}

export function rebuildChargerBateraiRows(periodValue, existingRows = []) {
    return createChargerBateraiRows(periodValue).map((row) => {
        const matchedRow = existingRows.find(
            (item) => Number(item.day) === Number(row.day),
        );

        return {
            ...row,
            switch_on_off: matchedRow?.switch_on_off || "",
            kondisi_fisik: matchedRow?.kondisi_fisik || "",
            kabel_konektor: matchedRow?.kabel_konektor || "",
            legrand: matchedRow?.legrand || "",
            display_charger: matchedRow?.display_charger || "",
            temuan: matchedRow?.temuan || "",
            tindakan: matchedRow?.tindakan || "",
        };
    });
}

export function createChecklistBateraiRows(periodValue) {
    return getDaysInPeriod(periodValue).map((dayInfo) => ({
        day: dayInfo.day,
        date: dayInfo.date,
        level_elektrolit: "",
        kabel_konektor: "",
        cover_pelampung: "",
        kebersihan_baterai: "",
        voltage_dc: "",
    }));
}

export function rebuildChecklistBateraiRows(periodValue, existingRows = []) {
    return createChecklistBateraiRows(periodValue).map((row) => {
        const matchedRow = existingRows.find(
            (item) => Number(item.day) === Number(row.day),
        );

        return {
            ...row,
            level_elektrolit: matchedRow?.level_elektrolit || "",
            kabel_konektor: matchedRow?.kabel_konektor || "",
            cover_pelampung: matchedRow?.cover_pelampung || "",
            kebersihan_baterai: matchedRow?.kebersihan_baterai || "",
            voltage_dc: matchedRow?.voltage_dc || "",
        };
    });
}

const unitCoolerItems = [
    ["cs1_1", "CS1", "1"],
    ["cs1_2", "CS1", "2"],
    ["cs2_1", "CS2", "1"],
    ["cs2_2", "CS2", "2"],
    ["cs3_1", "CS3", "1"],
    ["cs3_2", "CS3", "2"],
    ["cs5_1", "CS5", "1"],
    ["cs5_2", "CS5", "2"],
    ["cs6_1", "CS6", "1"],
    ["cs7_1", "CS7", "1"],
    ["cs8_1", "CS8", "1"],
    ["cs8_2", "CS8", "2"],
    ["cs9_1", "CS9", "1"],
    ["cs9_2", "CS9", "2"],
    ["cs10_1", "CS10", "1"],
    ["cs10_2", "CS10", "2"],
    ["cs11_1", "CS11", "1"],
    ["cs11_2", "CS11", "2"],
    ["cs12_1", "CS12", "1"],
    ["cs12_2", "CS12", "2"],
    ["ante_room_1", "ANTE ROOM", "1"],
    ["ante_room_2", "ANTE ROOM", "2"],
    ["ante_room_3", "ANTE ROOM", "3"],
    ["ante_room_4", "ANTE ROOM", "4"],
    ["loading_1", "LOADING", "1"],
    ["loading_2", "LOADING", "2"],
];

export { unitCoolerItems };

export function createUnitCoolerRows(periodValue) {
    return getDaysInPeriod(periodValue).map((dayInfo) => ({
        day: dayInfo.day,
        date: dayInfo.date,
        note: "",
        ...Object.fromEntries(unitCoolerItems.map(([key]) => [key, ""])),
    }));
}

export function rebuildUnitCoolerRows(periodValue, existingRows = []) {
    return createUnitCoolerRows(periodValue).map((row) => {
        const matchedRow = existingRows.find(
            (item) => Number(item.day) === Number(row.day),
        );
        return {
            ...row,
            note: matchedRow?.note || "",
            ...Object.fromEntries(
                unitCoolerItems.map(([key]) => [key, matchedRow?.[key] || ""]),
            ),
        };
    });
}

export function createKompresorHarianEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);

    return {
        id: `kompresor_harian-${Date.now()}`,
        template_id: "kompresor_harian",
        name: "Kompresor",
        created_at: formatDateTimeDisplay(now),
        form: {
            period,
            date_value: toDateInputValue(now),
            year: String(now.getFullYear()),
            active_day: now.getDate(),
            compressor_no: "",
            location: "GOLDEN MULTI INDOTAMA",
            pic: userName || "User Login",
            document_no: "DF-GMI-MTC-06",
            approved: false,
            approved_days: [],
            note: "",
            check_headers: {
                check_1: "TEMP SUCT (deg C)",
                check_2: "TEMP DISCH (deg C)",
                check_3: "TEMP OLI (deg C)",
                check_4: "LEVE OLI (%)",
            },
            rows: createKompresorDailyRows(period),
        },
    };
}

export function createChargerBateraiEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);

    return {
        id: `charger_baterai-${Date.now()}`,
        template_id: "charger_baterai",
        name: "Charger Baterai",
        created_at: formatDateTimeDisplay(now),
        form: {
            period,
            date_value: toDateInputValue(now),
            year: String(now.getFullYear()),
            active_day: now.getDate(),
            serial_no: "",
            pic: userName || "User Login",
            document_no: "DF-GMI-MTC-08",
            approved: false,
            approved_days: [],
            note: "",
            rows: createChargerBateraiRows(period),
        },
    };
}

export function createChecklistBateraiEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);

    return {
        id: `checklist_baterai-${Date.now()}`,
        template_id: "checklist_baterai",
        name: "Checklist Baterai",
        created_at: formatDateTimeDisplay(now),
        form: {
            period,
            date_value: toDateInputValue(now),
            year: String(now.getFullYear()),
            active_day: now.getDate(),
            battery_no: "",
            pic: userName || "User Login",
            document_no: "DF-GMI-MTC-09",
            approved: false,
            approved_days: [],
            note: "",
            rows: createChecklistBateraiRows(period),
        },
    };
}

export function createUnitCoolerEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);

    return {
        id: `unit_cooler-${Date.now()}`,
        template_id: "unit_cooler",
        name: "Unit Cooler",
        created_at: formatDateTimeDisplay(now),
        form: {
            period,
            date_value: toDateInputValue(now),
            year: String(now.getFullYear()),
            active_day: now.getDate(),
            unit_cooler_no: "",
            location: "GOLDEN MULTI INDOTAMA",
            pic: userName || "User Login",
            document_no: "DF-GMI-MTC-10",
            approved: false,
            approved_days: [],
            note: "",
            rows: createUnitCoolerRows(period),
        },
    };
}
