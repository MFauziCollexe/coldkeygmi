import { kotakP3KMonths, kotakP3KItems } from './shared-data';
import { formatDateTimeDisplay, formatDateDisplay } from './date-utils';

export const KOTAK_P3K_LOCATIONS = [
    { id: 'ruang_admin', name: 'Lantai 1 Depan (R.Admin)' },
    { id: 'ruang_kontrol', name: 'Lantai 1 Belakang (R.Kontrol)' },
    { id: 'pos_security', name: 'Lantai 1 Depan Luar (Pos Security)' },
];

export function getCurrentKotakP3KMonthKey(date = new Date()) {
    return kotakP3KMonths[date.getMonth()]?.key || "jan";
}

export function getKotakP3KMonthLabel(monthKey) {
    return (
        kotakP3KMonths.find((month) => month.key === monthKey)?.label ||
        monthKey
    );
}

export function createKotakP3KMonthValue(initialValue = "") {
    return kotakP3KMonths.reduce((result, month) => {
        result[month.key] = initialValue;
        return result;
    }, {});
}

export function createKotakP3KLocationState() {
    return {
        items: kotakP3KItems.map((item) => ({
            ...item,
            months: createKotakP3KMonthValue(""),
        })),
        submitted_months: [],
        approved_months: [],
        monthly_notes: createKotakP3KMonthValue(""),
        monthly_barcodes: createKotakP3KMonthValue(""),
        monthly_check_dates: createKotakP3KMonthValue(""),
        monthly_hse_approved_by: createKotakP3KMonthValue(""),
    };
}

export function createKotakP3KEntry(userName) {
    const now = new Date();
    const activeMonth = getCurrentKotakP3KMonthKey(now);

    const location_entries = {};
    for (const loc of KOTAK_P3K_LOCATIONS) {
        location_entries[loc.id] = createKotakP3KLocationState();
    }

    return {
        id: `kotak_p3k-${Date.now()}`,
        template_id: "kotak_p3k",
        name: "Kotak P3K",
        created_at: formatDateTimeDisplay(now),
        form: {
            location: "ruang_kontrol",
            box_type: "A",
            pic: userName || "User Login",
            year: String(now.getFullYear()),
            document_no: "FRM.HSE.11.01",
            date: formatDateDisplay(now),
            rev: "00",
            page: "1",
            barcode: "",
            approved: false,
            active_month: activeMonth,
            submitted_months: [],
            approved_months: [],
            monthly_hse_approved_by: createKotakP3KMonthValue(""),
            monthly_notes: createKotakP3KMonthValue(""),
            monthly_barcodes: createKotakP3KMonthValue(""),
            monthly_check_dates: createKotakP3KMonthValue(""),
            check_date: formatDateDisplay(now),
            items: kotakP3KItems.map((item) => ({
                ...item,
                months: createKotakP3KMonthValue(""),
            })),
            location_entries,
        },
    };
}
