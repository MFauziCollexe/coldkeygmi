import { kotakP3KMonths, fireSafetyCardOptions, fireSafetyLocationOptionsByType, fireSafetyItemsByType } from './shared-data';
import { getCurrentKotakP3KMonthKey } from './kotak-p3k';
import { formatDateTimeDisplay, formatDateDisplay } from './date-utils';

export function createFireSafetyMonthValue(initialValue = "") {
    return kotakP3KMonths.reduce((result, month) => {
        result[month.key] = initialValue;
        return result;
    }, {});
}

export function createFireSafetyRows(cardType = "fire_alarm") {
    const items =
        fireSafetyItemsByType[cardType] || fireSafetyItemsByType.fire_alarm;

    return items.map((item, index) => ({
        no: index + 1,
        id: item.id,
        name: item.name,
        months: createFireSafetyMonthValue(""),
    }));
}

export function rebuildFireSafetyRows(
    cardType = "fire_alarm",
    existingRows = [],
) {
    return createFireSafetyRows(cardType).map((row) => {
        const matchedRow = existingRows.find((item) => item.id === row.id);
        if (!matchedRow) {
            return row;
        }

        return {
            ...row,
            months: {
                ...row.months,
                ...(matchedRow.months || {}),
            },
        };
    });
}

export function getFireSafetyRecordKey(cardType = "", locationId = "") {
    return `${String(cardType || "").trim()}::${String(locationId || "").trim()}`;
}

export function createFireSafetyLocationState(
    cardType = "fire_alarm",
    existingState = {},
) {
    return {
        approved_months: Array.isArray(existingState?.approved_months)
            ? [...existingState.approved_months]
            : [],
        monthly_notes: {
            ...createFireSafetyMonthValue(""),
            ...(existingState?.monthly_notes || {}),
        },
        monthly_barcodes: {
            ...createFireSafetyMonthValue(""),
            ...(existingState?.monthly_barcodes || {}),
        },
        monthly_check_dates: {
            ...createFireSafetyMonthValue(""),
            ...(existingState?.monthly_check_dates || {}),
        },
        rows: rebuildFireSafetyRows(cardType, existingState?.rows || []),
    };
}

export function getFireSafetyCardLabel(cardType) {
    return (
        fireSafetyCardOptions.find((option) => option.id === cardType)?.name ||
        "-"
    );
}

export function getFireSafetyCardTitle(cardType) {
    return (
        fireSafetyCardOptions.find((option) => option.id === cardType)?.title ||
        "KARTU PEMELIHARAAN"
    );
}

export function getFireSafetyLocationOptions(cardType) {
    return fireSafetyLocationOptionsByType[cardType] || [];
}

export function getFireSafetyLocationLabel(cardType, locationId) {
    return (
        getFireSafetyLocationOptions(cardType).find(
            (location) => location.id === locationId,
        )?.name || "-"
    );
}

export function createFireSafetyEntry(userName) {
    const now = new Date();
    const cardType = "fire_alarm";
    const defaultLocation = getFireSafetyLocationOptions(cardType)[0]?.id || "";
    const activeMonth = getCurrentKotakP3KMonthKey(now);

    return {
        id: `apar_smoke_detector_fire_alarm-${Date.now()}`,
        template_id: "apar_smoke_detector_fire_alarm",
        name: "APAR, Smoke Detector, Fire Alarm",
        created_at: formatDateTimeDisplay(now),
        form: {
            card_type: cardType,
            location: defaultLocation,
            year: String(now.getFullYear()),
            active_month: activeMonth,
            approved: false,
            approved_months: [],
            monthly_notes: createFireSafetyMonthValue(""),
            monthly_barcodes: createFireSafetyMonthValue(""),
            monthly_check_dates: createFireSafetyMonthValue(""),
            rows: createFireSafetyRows(cardType),
            location_records: {
                [getFireSafetyRecordKey(cardType, defaultLocation)]:
                    createFireSafetyLocationState(cardType),
            },
            pic: userName || "User Login",
        },
    };
}
