import KotakP3KTemplate from '../Templates/KotakP3KTemplate.vue'
import FireSafetyTemplate from '../Templates/FireSafetyTemplate.vue'
import NonWarehouseSanitationTemplate from '../Templates/NonWarehouseSanitationTemplate.vue'
import WasteTransportTemplate from '../Templates/WasteTransportTemplate.vue'
import WarehouseSanitationTemplate from '../Templates/WarehouseSanitationTemplate.vue'
import PersonalHygieneTemplate from '../Templates/PersonalHygieneTemplate.vue'
import SaranaPrasaranaTemplate from '../Templates/SaranaPrasaranaTemplate.vue'
import PatroliSecurityTemplate from '../Templates/PatroliSecurityTemplate.vue'
import SiteVisitHseTemplate from '../Templates/SiteVisitHseTemplate.vue'
import SiteVisitMaintenanceTemplate from '../Templates/SiteVisitMaintenanceTemplate.vue'
import GensetRunningTemplate from '../Templates/GensetRunningTemplate.vue'
import RunningGensetTemplate from '../Templates/RunningGensetTemplate.vue'
import KompresorHarianTemplate from '../Templates/KompresorHarianTemplate.vue'
import ChargerBateraiTemplate from '../Templates/ChargerBateraiTemplate.vue'
import ChecklistBateraiTemplate from '../Templates/ChecklistBateraiTemplate.vue'

const supportedTemplates = [
  'kotak_p3k', 'non_warehouse_sanitation', 'apar_smoke_detector_fire_alarm',
  'pengangkutan_sampah_pt_sier', 'warehouse_sanitation_1', 'personal_hygiene_karyawan',
  'sarana_dan_prasarana', 'patroli_security', 'site_visit_hse', 'site_visit_maintenance',
  'genset_running', 'running_genset', 'kompresor_harian', 'charger_baterai', 'checklist_baterai',
]

export const templateRegistry = {
  kotak_p3k: { component: KotakP3KTemplate },
  non_warehouse_sanitation: { component: NonWarehouseSanitationTemplate },
  apar_smoke_detector_fire_alarm: { component: FireSafetyTemplate },
  pengangkutan_sampah_pt_sier: { component: WasteTransportTemplate },
  warehouse_sanitation_1: { component: WarehouseSanitationTemplate },
  personal_hygiene_karyawan: { component: PersonalHygieneTemplate },
  sarana_dan_prasarana: { component: SaranaPrasaranaTemplate },
  patroli_security: { component: PatroliSecurityTemplate },
  site_visit_hse: { component: SiteVisitHseTemplate },
  site_visit_maintenance: { component: SiteVisitMaintenanceTemplate },
  genset_running: { component: GensetRunningTemplate },
  running_genset: { component: RunningGensetTemplate },
  kompresor_harian: { component: KompresorHarianTemplate },
  charger_baterai: { component: ChargerBateraiTemplate },
  checklist_baterai: { component: ChecklistBateraiTemplate },
}

export const supportedTemplatesList = supportedTemplates

export function getTemplateComponent(templateId) {
  return templateRegistry[templateId]?.component || null
}

export function isTemplateSupported(templateId) {
  return supportedTemplates.includes(templateId)
}
