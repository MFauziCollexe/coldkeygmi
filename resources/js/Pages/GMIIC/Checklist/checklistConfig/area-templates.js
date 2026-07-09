import { getDaysInPeriod, toPeriodValue, toDateInputValue, formatDateDisplay, formatDateTimeDisplay } from './date-utils';
import { patroliSecuritySections, patroliSecurityAreaOptions, cleaningOBShifts, cleaningOBShiftOptions, saranaPrasaranaSections, saranaPrasaranaAreaOptions, siteVisitHseSections, siteVisitHseAreaOptions } from './area-data';

export function createPatroliSecuritySections() {
    return patroliSecuritySections.map((section) => ({
        id: section.id,
        title: section.title,
        items: section.items.map((itemName, index) => ({
            no: index + 1,
            id: `${section.id}-${index + 1}`,
            name: itemName,
            status: "",
        })),
    }));
}

export function rebuildPatroliSecuritySections(existingSections = []) {
    return createPatroliSecuritySections().map((section) => {
        const matchedSection = existingSections.find(
            (item) => item.id === section.id,
        );

        return {
            ...section,
            items: section.items.map((item) => {
                const matchedItem = matchedSection?.items?.find(
                    (sectionItem) => sectionItem.id === item.id,
                );

                return {
                    ...item,
                    status: matchedItem?.status || "",
                };
            }),
        };
    });
}

export function getPatroliSecurityAreaLabel(areaId) {
    return (
        patroliSecurityAreaOptions.find((area) => area.id === areaId)?.name ||
        "-"
    );
}

export function getPatroliSecurityBarcodeAliases(areaId) {
    const aliases = {
        pos_security: "Lantai 1 - Depan dan Luar",
        loading_dock_luar: "Lantai 1 - Area Dalam (Ateroom, Loading Dock)",
        travo_pln_depan: "Lantai 1 - Area Depan (Travo Pln Depan)",
        tanah_kosong_dan_sekitar_bangunan: "Lantai 1 Area Luar Timur",
        travo_pln_belakang: "Lantai 1 - Area Belakang (Travo Pln Belakang)",
        genset: "Lantai 1 - Area Belakang (Genset)",
        ruang_mesin_ruang_kontrol:
            "Lantai 1 - Area Belakang (R. Mesin, R. Kontrol)",
        sekitar_bangunan: "Lantai 1 Area Luar Barat",
        loading_dock_dalam: "Lantai 1 - Area Dalam (Loading Dock Dalam)",
        anteroom_cold_storage: "Lantai 1 - Area Dalam (Anteroom, Cold Storage)",
        lobby_ruang_loker: "Lantai 1 - Area Dalam (Lobby, Ruang Loker)",
        ruang_baterai: "Lantai 1 - Ruang Baterai",
        lantai_2: "Lantai 2",
        lantai_3: "Lantai 3",
        ruang_admin: "Ruang Admin",
    };
    const fullTitle =
        aliases[areaId] ||
        patroliSecuritySections.find((section) => section.id === areaId)
            ?.title ||
        "";
    const shortLabel = getPatroliSecurityAreaLabel(areaId);

    return [
        ...new Set(
            [fullTitle, shortLabel].filter(
                (value) => String(value || "").trim() !== "",
            ),
        ),
    ];
}

export function createPatroliSecurityEntry(userName) {
    const now = new Date();
    const defaultArea = patroliSecuritySections[0]?.id || "";

    return {
        id: `patroli_security-${Date.now()}`,
        template_id: "patroli_security",
        name: "Patroli Security",
        created_at: formatDateTimeDisplay(now),
        form: {
            date_value: toDateInputValue(now),
            date: formatDateDisplay(now),
            selected_area: defaultArea,
            pic: userName || "User Login",
            approved: false,
            approved_areas: [],
            area_barcodes: {},
            area_notes: {},
            area_photo_paths: {},
            area_photo_urls: {},
            area_photo_names: {},
            area_scan_dates: {},
            document_no: "FRM.HSE.15.02",
            rev: "00",
            effective_date: "22 Desember 2025",
            page: "1 dari 1",
            sections: createPatroliSecuritySections(),
        },
    };
}

export function createCleaningOBSections() {
    return cleaningOBShifts.map((shift) => ({
        id: shift.id,
        title: shift.title,
        sections: shift.sections.map((section) => ({
            id: section.id,
            title: section.title,
            items: section.items.map((itemName, index) => ({
                no: index + 1,
                id: `${section.id}-${index + 1}`,
                name: itemName,
                status: "",
            })),
        })),
    }));
}

export function rebuildCleaningOBSections(existingSections = []) {
    return createCleaningOBSections().map((shift) => {
        const matchedShift = existingSections.find(
            (item) => item.id === shift.id,
        );

        return {
            ...shift,
            sections: shift.sections.map((section) => {
                const matchedSection = matchedShift?.sections?.find(
                    (s) => s.id === section.id,
                );

                return {
                    ...section,
                    items: section.items.map((item) => {
                        const matchedItem = matchedSection?.items?.find(
                            (i) => i.id === item.id,
                        );

                        return {
                            ...item,
                            status: matchedItem?.status || "",
                        };
                    }),
                };
            }),
        };
    });
}

export function getCleaningOBShiftLabel(shiftId) {
    return (
        cleaningOBShiftOptions.find((shift) => shift.id === shiftId)?.name ||
        "-"
    );
}

export function createCleaningOBEntry(userName) {
    const now = new Date();
    const defaultShift = cleaningOBShiftOptions[0]?.id || "";

    return {
        id: `jadwal_cleaning_ob-${Date.now()}`,
        template_id: "jadwal_cleaning_ob",
        name: "Jadwal Cleaning OB",
        created_at: formatDateTimeDisplay(now),
        form: {
            date_value: toDateInputValue(now),
            date: formatDateDisplay(now),
            selected_shift: defaultShift,
            pic: userName || "User Login",
            approved: false,
            approved_areas: [],
            area_barcodes: {},
            area_notes: {},
            area_photo_paths: {},
            area_photo_urls: {},
            area_photo_names: {},
            area_scan_dates: {},
            document_no: "FRM.HSE.15.02",
            rev: "00",
            effective_date: "22 Desember 2025",
            page: "1 dari 1",
            sections: createCleaningOBSections(),
        },
    };
}

export function createSaranaPrasaranaDayMap(periodValue) {
    return getDaysInPeriod(periodValue).reduce((result, dayInfo) => {
        result[dayInfo.day] = "";
        return result;
    }, {});
}

export function createSaranaPrasaranaSections(periodValue) {
    return saranaPrasaranaSections.map((section) => ({
        id: section.id,
        title: section.title,
        items: section.items.map((itemName, index) => ({
            no: index + 1,
            id: `${section.id}-${index + 1}`,
            name: itemName,
            days: createSaranaPrasaranaDayMap(periodValue),
        })),
    }));
}

export function rebuildSaranaPrasaranaSections(
    periodValue,
    existingSections = [],
) {
    return createSaranaPrasaranaSections(periodValue).map((section) => {
        const matchedSection = existingSections.find(
            (item) => item.id === section.id,
        );

        return {
            ...section,
            items: section.items.map((item) => {
                const matchedItem = matchedSection?.items?.find(
                    (sectionItem) => sectionItem.id === item.id,
                );

                return {
                    ...item,
                    days: {
                        ...item.days,
                        ...(matchedItem?.days || {}),
                    },
                };
            }),
        };
    });
}

export function getSaranaPrasaranaAreaLabel(areaId) {
    return (
        saranaPrasaranaAreaOptions.find((area) => area.id === areaId)?.name ||
        "-"
    );
}

export function createSaranaPrasaranaEntry(userName) {
    const now = new Date();
    const period = toPeriodValue(now);
    const defaultArea = saranaPrasaranaSections[0]?.id || "";

    return {
        id: `sarana_dan_prasarana-${Date.now()}`,
        template_id: "sarana_dan_prasarana",
        name: "Sarana dan Prasarana",
        created_at: formatDateTimeDisplay(now),
        form: {
            period,
            selected_area: defaultArea,
            pic: userName || "User Login",
            approved: false,
            approved_days_by_area: saranaPrasaranaSections.reduce(
                (result, section) => {
                    result[section.id] = [];
                    return result;
                },
                {},
            ),
            area_scans_by_day: {},
            document_no: "FRM.HRGA.01.06",
            rev: "00",
            effective_date: "22 Desember 2025",
            page: "1 dari 1",
            sections: createSaranaPrasaranaSections(period),
        },
    };
}

export function createSiteVisitHseSections() {
    return siteVisitHseSections.map((section) => ({
        id: section.id,
        title: section.title,
        items: section.items.map((itemName, index) => ({
            no: index + 1,
            id: `${section.id}-${index + 1}`,
            name: itemName,
            status: "",
        })),
    }));
}

export function rebuildSiteVisitHseSections(existingSections = []) {
    return createSiteVisitHseSections().map((section) => {
        const matchedSection = existingSections.find(
            (item) => item.id === section.id,
        );

        return {
            ...section,
            items: section.items.map((item) => {
                const matchedItem = matchedSection?.items?.find(
                    (sectionItem) => sectionItem.id === item.id,
                );

                return {
                    ...item,
                    status: matchedItem?.status || "",
                };
            }),
        };
    });
}

export function getSiteVisitHseAreaLabel(areaId) {
    return (
        siteVisitHseAreaOptions.find((area) => area.id === areaId)?.name || "-"
    );
}

export function createSiteVisitHseEntry(userName) {
    const now = new Date();
    const defaultArea = siteVisitHseSections[0]?.id || "";

    return {
        id: `site_visit_hse-${Date.now()}`,
        template_id: "site_visit_hse",
        name: "Site Visit HSE",
        created_at: formatDateTimeDisplay(now),
        form: {
            date_value: toDateInputValue(now),
            date: formatDateDisplay(now),
            selected_area: defaultArea,
            pic: userName || "User Login",
            approved: false,
            approved_areas: [],
            area_barcodes: {},
            area_notes: {},
            area_scan_dates: {},
            document_no: "FRM.HSE.15.01",
            rev: "00",
            effective_date: "22 Desember 2025",
            page: "1 dari 1",
            sections: createSiteVisitHseSections(),
        },
    };
}
