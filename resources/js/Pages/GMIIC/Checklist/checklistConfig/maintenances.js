import { groupedChecklistAreaLabels, kotakP3KMonths } from './shared-data';
import { toPeriodValue, toDateInputValue, toWeekInputValue, formatDateDisplay, formatWeekDisplay, formatDateTimeDisplay } from './date-utils';
import { maintenanceVisitTypeOptions, maintenanceDailySections, maintenanceWeeklyItems, gensetRunningSections, runningGensetSections, maintenanceDailyAreaOptions } from './area-data';

export function createMaintenanceRow(id, name, no, locationLabel = "") {
    return {
        id,
        no,
        name,
        status: "",
        note: "",
        scan_location: locationLabel,
    };
}

export function createMaintenanceDailySections() {
    return maintenanceDailySections.map((section) => ({
        id: section.id,
        area_id: section.area_id,
        title: section.title,
        items: section.items.map((itemName, index) =>
            createMaintenanceRow(
                `${section.id}-${index + 1}`,
                itemName,
                index + 1,
            ),
        ),
    }));
}

export function createMaintenanceWeeklyRows() {
    return maintenanceWeeklyItems.map((itemName, index) =>
        createMaintenanceRow(
            `genset-${index + 1}`,
            itemName,
            index + 1,
            groupedChecklistAreaLabels.lantai_1_area_belakang,
        ),
    );
}

export function rebuildMaintenanceDailySections(existingSections = []) {
    return createMaintenanceDailySections().map((section) => {
        const matchedSection = existingSections.find(
            (item) => item.id === section.id,
        );

        return {
            ...section,
            area_id: matchedSection?.area_id || section.area_id,
            items: section.items.map((item) => {
                const matchedItem = matchedSection?.items?.find(
                    (sectionItem) => sectionItem.id === item.id,
                );

                return {
                    ...item,
                    status: matchedItem?.status || "",
                    note: matchedItem?.note || "",
                    scan_location: matchedItem?.scan_location || "",
                };
            }),
        };
    });
}

export function rebuildMaintenanceWeeklyRows(existingRows = []) {
    return createMaintenanceWeeklyRows().map((row) => {
        const matchedRow = existingRows.find((item) => item.id === row.id);

        return {
            ...row,
            status: matchedRow?.status || "",
            note: matchedRow?.note || "",
            scan_location:
                matchedRow?.scan_location ||
                row.scan_location ||
                groupedChecklistAreaLabels.lantai_1_area_belakang,
        };
    });
}

export function getMaintenanceVisitTypeMeta(visitType) {
    return (
        maintenanceVisitTypeOptions.find((option) => option.id === visitType) ||
        maintenanceVisitTypeOptions[0]
    );
}

export function getMaintenanceVisitTypeLabel(visitType) {
    return getMaintenanceVisitTypeMeta(visitType)?.name || "-";
}

export function getMaintenanceDailyAreaLabel(areaId) {
    return (
        maintenanceDailyAreaOptions.find((area) => area.id === areaId)?.name ||
        "-"
    );
}

export function createGensetRunningRow(id, name, no, sectionId, sectionTitle) {
    return {
        id,
        no,
        name,
        section_id: sectionId,
        section_title: sectionTitle,
        status: "",
        scan_location: groupedChecklistAreaLabels.genset,
    };
}

export function createGensetRunningRows() {
    return gensetRunningSections.flatMap((section) =>
        section.items.map((item, index) =>
            createGensetRunningRow(
                item.id,
                item.name,
                index + 1,
                section.id,
                section.title,
            ),
        ),
    );
}

export function rebuildGensetRunningRows(existingRows = []) {
    return createGensetRunningRows().map((row) => {
        const matchedRow = existingRows.find((item) => item.id === row.id);

        return {
            ...row,
            status: matchedRow?.status || "",
        };
    });
}

export function createRunningGensetRow(id, name, no, sectionId, sectionTitle) {
    return {
        id,
        no,
        name,
        section_id: sectionId,
        section_title: sectionTitle,
        status: "",
        scan_location: groupedChecklistAreaLabels.genset,
    };
}

export function createRunningGensetRows() {
    return runningGensetSections.flatMap((section) =>
        section.items.map((item, index) =>
            createRunningGensetRow(
                item.id,
                item.name,
                index + 1,
                section.id,
                section.title,
            ),
        ),
    );
}

export function rebuildRunningGensetRows(existingRows = []) {
    return createRunningGensetRows().map((row) => {
        const matchedRow = existingRows.find((item) => item.id === row.id);

        return {
            ...row,
            status: matchedRow?.status || "",
        };
    });
}

export function createSiteVisitMaintenanceEntry(userName) {
    const now = new Date();
    const visitType =
        maintenanceVisitTypeOptions[0]?.id || "maintenance_harian";
    const typeMeta = getMaintenanceVisitTypeMeta(visitType);
    const dateValue = toDateInputValue(now);
    const periodValue = toWeekInputValue(now);

    return {
        id: `site_visit_maintenance-${Date.now()}`,
        template_id: "site_visit_maintenance",
        name: "Site Visit Maintenance",
        created_at: formatDateTimeDisplay(now),
        form: {
            visit_type: visitType,
            pic: userName || "User Login",
            approved: false,
            document_no: typeMeta.document_no,
            rev: "00",
            effective_date: "22 Desember 2025",
            page: "1 dari 1",
            selected_area: "lantai_1_area_belakang",
            area_barcodes: {},
            area_scan_dates: {},
            area_notes: {},
            date_value: dateValue,
            date: formatDateDisplay(now),
            period_value: periodValue,
            period: formatWeekDisplay(periodValue),
            sections: createMaintenanceDailySections(),
            rows: createMaintenanceWeeklyRows(),
        },
    };
}

export function createGensetRunningEntry(userName) {
    const now = new Date();
    const periodValue = toWeekInputValue(now);

    return {
        id: `genset_running-${Date.now()}`,
        template_id: "genset_running",
        name: "Pemanasan (Running) Genset",
        created_at: formatDateTimeDisplay(now),
        form: {
            period_value: periodValue,
            period: formatWeekDisplay(periodValue),
            selected_area: "genset",
            pic: userName || "User Login",
            date: formatDateDisplay(now),
            document_no: "DF-GMI-MTC-04",
            rev: "00",
            effective_date: "22 Desember 2025",
            page: "1",
            approved: false,
            area_barcodes: {},
            area_scan_dates: {},
            area_notes: {
                genset: "",
            },
            rows: createGensetRunningRows(),
        },
    };
}

export function createRunningGensetEntry(userName) {
    const now = new Date();
    const dateValue = toDateInputValue(now);

    return {
        id: `running_genset-${Date.now()}`,
        template_id: "running_genset",
        name: "Running Genset",
        created_at: formatDateTimeDisplay(now),
        form: {
            selected_area: "genset",
            pic: userName || "User Login",
            approved: false,
            document_no: "FRM.MTC.01.03",
            rev: "00",
            effective_date: "22 Desember 2025",
            page: "1 dari 1",
            date_value: dateValue,
            date: formatDateDisplay(now),
            hour_meter: "",
            area_barcodes: {},
            area_scan_dates: {},
            area_notes: {
                genset: "",
            },
            rows: createRunningGensetRows(),
        },
    };
}
