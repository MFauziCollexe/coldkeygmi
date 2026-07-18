import { computed } from "vue";
import {
    getDaysInPeriod,
    getKotakP3KMonthLabel,
    kotakP3KMonths,
    getLocationLabel,
    getLocationBarcodeAliases,
    locationOptions,
    createKotakP3KMonthValue,
    createKotakP3KLocationState,
} from "../checklistConfig";

export function useKotakP3K(entry, { showQrScanner }) {
    const isKotakP3K = computed(() => entry.value?.template_id === "kotak_p3k");

    const currentLocationId = computed(() => {
        if (!isKotakP3K.value || !entry.value?.form) return "ruang_kontrol";
        return (
            String(entry.value.form.location || "ruang_kontrol").trim() ||
            "ruang_kontrol"
        );
    });

    function createLegacyLocationState(form) {
        const locationState = createKotakP3KLocationState();

        if (Array.isArray(form.items) && form.items.length) {
            locationState.items = form.items.map((item) => ({
                ...item,
                months: { ...(item.months || createKotakP3KMonthValue("")) },
            }));
        }

        locationState.monthly_notes = {
            ...(form.monthly_notes || createKotakP3KMonthValue("")),
        };
        locationState.monthly_barcodes = {
            ...(form.monthly_barcodes || createKotakP3KMonthValue("")),
        };
        locationState.monthly_check_dates = {
            ...(form.monthly_check_dates || createKotakP3KMonthValue("")),
        };
        locationState.submitted_months = Array.isArray(form.submitted_months)
            ? [...form.submitted_months]
            : [];
        locationState.approved_months = Array.isArray(form.approved_months)
            ? [...form.approved_months]
            : [];

        return locationState;
    }

    const currentLocationState = computed(() => {
        if (!isKotakP3K.value || !entry.value?.form) return null;
        if (
            !entry.value.form.location_entries ||
            typeof entry.value.form.location_entries !== "object"
        ) {
            entry.value.form.location_entries = {};
        }

        const locationId = currentLocationId.value;
        const hasLegacyState =
            !Object.keys(entry.value.form.location_entries).length &&
            Array.isArray(entry.value.form.items) &&
            entry.value.form.items.length;
        const useLegacyState =
            hasLegacyState && Boolean(entry.value.form.location);

        let locationState = entry.value.form.location_entries[locationId];
        if (!locationState) {
            if (
                useLegacyState &&
                locationId === String(entry.value.form.location).trim()
            ) {
                locationState = createLegacyLocationState(entry.value.form);
            } else {
                locationState = createKotakP3KLocationState();
            }
        }

        if (
            !Array.isArray(locationState.items) ||
            !locationState.items.length
        ) {
            locationState.items = createKotakP3KLocationState().items;
        }

        locationState.monthly_notes =
            locationState.monthly_notes || createKotakP3KMonthValue("");
        locationState.monthly_barcodes =
            locationState.monthly_barcodes || createKotakP3KMonthValue("");
        locationState.monthly_check_dates =
            locationState.monthly_check_dates || createKotakP3KMonthValue("");
        locationState.submitted_months = Array.isArray(
            locationState.submitted_months,
        )
            ? locationState.submitted_months
            : [];
        locationState.approved_months = Array.isArray(
            locationState.approved_months,
        )
            ? locationState.approved_months
            : [];

        entry.value.form.location_entries[locationId] = locationState;
        if (!entry.value.form.location) entry.value.form.location = locationId;

        return locationState;
    });

    const activeKotakP3KMonth = computed(() => {
        if (!isKotakP3K.value || !entry.value) return "jan";
        return entry.value.form.active_month || "jan";
    });

    const kotakP3KApprovedMonths = computed(() => {
        if (!isKotakP3K.value || !currentLocationState.value) return [];
        return Array.isArray(currentLocationState.value.approved_months)
            ? currentLocationState.value.approved_months
            : [];
    });

    const kotakP3KSubmittedMonths = computed(() => {
        if (!isKotakP3K.value || !currentLocationState.value) return [];
        return Array.isArray(currentLocationState.value.submitted_months)
            ? currentLocationState.value.submitted_months
            : [];
    });

    const currentLocationItems = computed(
        () => currentLocationState.value?.items || [],
    );
    const currentLocationMonthlyNotes = computed(
        () => currentLocationState.value?.monthly_notes || {},
    );
    const currentLocationMonthlyBarcodes = computed(
        () => currentLocationState.value?.monthly_barcodes || {},
    );
    const currentLocationMonthlyCheckDates = computed(
        () => currentLocationState.value?.monthly_check_dates || {},
    );

    const isActiveKotakP3KMonthApproved = computed(() =>
        kotakP3KApprovedMonths.value.includes(activeKotakP3KMonth.value),
    );
    const isActiveKotakP3KMonthSubmitted = computed(() =>
        kotakP3KSubmittedMonths.value.includes(activeKotakP3KMonth.value),
    );
    const isActiveKotakP3KMonthLocked = computed(
        () =>
            isActiveKotakP3KMonthSubmitted.value ||
            isActiveKotakP3KMonthApproved.value,
    );

    const kotakP3KActiveMonthStatusLabel = computed(() => {
        if (isActiveKotakP3KMonthApproved.value) return "Approved";
        if (isActiveKotakP3KMonthSubmitted.value) return "Waiting HSE";
        return "Pending";
    });

    const kotakP3KMonthNote = computed({
        get() {
            if (!isKotakP3K.value || !currentLocationState.value) return "";
            return (
                currentLocationMonthlyNotes.value[activeKotakP3KMonth.value] ||
                ""
            );
        },
        set(value) {
            if (!isKotakP3K.value || !currentLocationState.value) return;
            currentLocationState.value.monthly_notes = {
                ...(currentLocationState.value.monthly_notes || {}),
                [activeKotakP3KMonth.value]: value,
            };
        },
    });

    const currentKotakP3KBarcode = computed(() => {
        if (!isKotakP3K.value || !currentLocationState.value) return "";
        return (
            currentLocationMonthlyBarcodes.value[activeKotakP3KMonth.value] ||
            ""
        );
    });

    const kotakP3KMonthValidation = computed(() => {
        if (!isKotakP3K.value || !currentLocationState.value)
            return {
                allAnswersFilled: false,
                hasNoAnswer: false,
                hasRequiredNote: false,
                canScan: false,
            };
        const answers = currentLocationItems.value.map(
            (item) => item.months?.[activeKotakP3KMonth.value] || "",
        );
        const allAnswersFilled = answers.every(
            (answer) => answer === "yes" || answer === "no",
        );
        const hasNoAnswer = answers.includes("no");
        const hasRequiredNote =
            String(kotakP3KMonthNote.value || "").trim() !== "";
        const canScan =
            !isActiveKotakP3KMonthLocked.value &&
            showQrScanner.value &&
            allAnswersFilled &&
            (!hasNoAnswer || hasRequiredNote);
        return { allAnswersFilled, hasNoAnswer, hasRequiredNote, canScan };
    });

    const kotakP3KApprovalButtonLabel = computed(() => {
        if (!isKotakP3K.value || !entry.value) return "Approval";
        if (isActiveKotakP3KMonthApproved.value) return "Approved";
        if (isActiveKotakP3KMonthSubmitted.value) return "Approval HSE";
        return "Approval";
    });

    function toggleLocationMenu(locationMenuOpen) {
        locationMenuOpen.value = !locationMenuOpen.value;
    }

    function selectLocation(locationId) {
        if (!entry.value || !isKotakP3K.value) return;
        if (
            !entry.value.form.location_entries ||
            typeof entry.value.form.location_entries !== "object"
        ) {
            entry.value.form.location_entries = {};
        }
        if (!entry.value.form.location_entries[locationId]) {
            entry.value.form.location_entries[locationId] =
                createKotakP3KLocationState();
        }
        entry.value.form.location = locationId;
    }

    function setKotakP3KActiveMonth(monthKey) {
        if (!entry.value || !isKotakP3K.value) return;
        entry.value.form.active_month = monthKey;
    }

    function cycleKotakP3KMonthAnswer(item, monthKey) {
        if (
            !item?.months ||
            isActiveKotakP3KMonthApproved.value ||
            monthKey !== activeKotakP3KMonth.value
        )
            return;
        const currentValue = item.months?.[monthKey] || "";
        const nextValue =
            currentValue === "" ? "yes" : currentValue === "yes" ? "no" : "";
        item.months = { ...item.months, [monthKey]: nextValue };
    }

    function updateKotakP3KMonthNote(value) {
        kotakP3KMonthNote.value = value;
    }

    return {
        isKotakP3K,
        activeKotakP3KMonth,
        kotakP3KApprovedMonths,
        kotakP3KSubmittedMonths,
        isActiveKotakP3KMonthApproved,
        isActiveKotakP3KMonthSubmitted,
        isActiveKotakP3KMonthLocked,
        kotakP3KActiveMonthStatusLabel,
        kotakP3KMonthNote,
        currentKotakP3KBarcode,
        kotakP3KMonthValidation,
        kotakP3KApprovalButtonLabel,
        kotakP3KMonths,
        locationOptions,
        getLocationLabel,
        getKotakP3KMonthLabel,
        currentLocationId,
        currentLocationItems,
        currentLocationMonthlyCheckDates,
        toggleLocationMenu,
        selectLocation,
        setKotakP3KActiveMonth,
        cycleKotakP3KMonthAnswer,
        updateKotakP3KMonthNote,
    };
}
