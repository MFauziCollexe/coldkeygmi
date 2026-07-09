import { checklistOptions } from './registry';
import { groupedChecklistAreaLabels } from './shared-data';
import { getFireSafetyCardLabel, getFireSafetyLocationLabel, getFireSafetyLocationOptions } from './fire-safety';
import { sanitationAreaOptions, warehouseAreaOptions, getLocationLabel, getSanitationAreaLabel } from './location-data';
import { getSaranaPrasaranaAreaLabel, getSiteVisitHseAreaLabel, getPatroliSecurityAreaLabel } from './area-templates';
import { getMaintenanceDailyAreaLabel } from './maintenances';

export function getChecklistLabel(templateId) {
    return (
        checklistOptions.find((option) => option.id === templateId)?.name || "-"
    );
}

export function getChecklistEntryAreaLabel(entry) {
    if (entry?.template_id === "apar_smoke_detector_fire_alarm") {
        const cardLabel = getFireSafetyCardLabel(entry.form?.card_type);
        const locationLabel = getFireSafetyLocationLabel(
            entry.form?.card_type,
            entry.form?.location,
        );
        return `${cardLabel} - ${locationLabel}`;
    }

    if (entry?.template_id === "kotak_p3k") {
        return getLocationLabel(entry.form?.location);
    }

    if (entry?.template_id === "non_warehouse_sanitation") {
        return sanitationAreaOptions.map((area) => area.name).join(", ");
    }

    if (entry?.template_id === "pengangkutan_sampah_pt_sier") {
        return "PT SIER";
    }

    if (entry?.template_id === "warehouse_sanitation_1") {
        const areas = Array.isArray(entry.form?.selected_areas)
            ? entry.form.selected_areas
            : [];
        const labels = warehouseAreaOptions
            .filter((area) => areas.includes(area.id))
            .map((area) => area.name);

        return labels.length ? labels.join(", ") : "Warehouse";
    }

    if (entry?.template_id === "personal_hygiene_karyawan") {
        return (
            entry.form?.employee_name ||
            entry.form?.bagian ||
            "Personal Hygiene"
        );
    }

    if (entry?.template_id === "sarana_dan_prasarana") {
        return getSaranaPrasaranaAreaLabel(entry.form?.selected_area);
    }

    if (entry?.template_id === "site_visit_hse") {
        return getSiteVisitHseAreaLabel(entry.form?.selected_area);
    }

    if (entry?.template_id === "patroli_security") {
        return getPatroliSecurityAreaLabel(entry.form?.selected_area);
    }

    if (entry?.template_id === "site_visit_maintenance") {
        if (entry.form?.visit_type === "maintenance_mingguan") {
            return groupedChecklistAreaLabels.lantai_1_area_belakang;
        }

        return getMaintenanceDailyAreaLabel(entry.form?.selected_area);
    }

    if (entry?.template_id === "genset_running") {
        return groupedChecklistAreaLabels.genset;
    }

    if (entry?.template_id === "running_genset") {
        return groupedChecklistAreaLabels.genset;
    }

    if (entry?.template_id === "kompresor_harian") {
        return "KOMPRESOR";
    }

    if (entry?.template_id === "charger_baterai") {
        return "CHARGER BATERAI";
    }

    if (entry?.template_id === "checklist_baterai") {
        return "BATERAI";
    }

    return "-";
}
