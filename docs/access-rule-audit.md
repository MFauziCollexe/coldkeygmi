# Access Rule Audit

Dokumen ini merangkum rule `approval` dan `list view` yang masih di-hardcode per modul, lalu menandai modul pilot yang sudah mulai dipindahkan ke pondasi modular rule.

## UI Access Rules

- UI pengelolaan override rule sekarang tersedia di [AccessRules.vue](/c:/xampp/htdocs/coldkeygmi/resources/js/Pages/ControlPanel/AccessRules.vue)
- Backend pengelolaan override tersedia di [AccessRuleController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/AccessRuleController.php)
- Override disimpan lewat [AccessRuleService.php](/c:/xampp/htdocs/coldkeygmi/app/Support/AccessRuleService.php) ke file `storage/app/access-rules-overrides.json`
- Audit trail perubahan access rule disimpan ke file `storage/app/access-rules-audit.json`
- Default rule di [access_rules.php](/c:/xampp/htdocs/coldkeygmi/config/access_rules.php) tetap menjadi baseline
- UI sekarang mendukung tambah module baru langsung dari aplikasi, builder rule per scenario `scope/ability`, compare `default vs override`, audit trail save/reset, filter per module, detail diff per rule, badge aksi audit yang jelas, preview snapshot sebelum rollback, dan rollback ke versi audit yang punya snapshot

## Pilot Yang Sudah Dimulai

- `Overtime`
  - Pondasi baru: [access_rules.php](/c:/xampp/htdocs/coldkeygmi/config/access_rules.php)
  - Evaluator rule: [AccessRuleService.php](/c:/xampp/htdocs/coldkeygmi/app/Support/AccessRuleService.php)
  - Controller pilot: [OvertimeController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/OvertimeController.php)
- `Leave Permission`
  - Pondasi baru: [access_rules.php](/c:/xampp/htdocs/coldkeygmi/config/access_rules.php)
  - Evaluator rule: [AccessRuleService.php](/c:/xampp/htdocs/coldkeygmi/app/Support/AccessRuleService.php)
  - Controller tahap awal: [LeavePermissionController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/LeavePermissionController.php)
- `Roster`
  - Pondasi baru: [access_rules.php](/c:/xampp/htdocs/coldkeygmi/config/access_rules.php)
  - Evaluator rule: [AccessRuleService.php](/c:/xampp/htdocs/coldkeygmi/app/Support/AccessRuleService.php)
  - Controller tahap awal: [RosterController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/RosterController.php)
- `Attendance Log`
  - Pondasi baru: [access_rules.php](/c:/xampp/htdocs/coldkeygmi/config/access_rules.php)
  - Evaluator rule: [AccessRuleService.php](/c:/xampp/htdocs/coldkeygmi/app/Support/AccessRuleService.php)
  - Controller tahap awal: [AttendanceLogController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/AttendanceLogController.php)
- `Request Access`
  - Pondasi baru: [access_rules.php](/c:/xampp/htdocs/coldkeygmi/config/access_rules.php)
  - Evaluator rule: [AccessRuleService.php](/c:/xampp/htdocs/coldkeygmi/app/Support/AccessRuleService.php)
  - Controller tahap awal: [RequestAccessController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/RequestAccessController.php)
  - Model tahap awal: [RequestAccess.php](/c:/xampp/htdocs/coldkeygmi/app/Models/RequestAccess.php)
- `Exit Permit`
  - Pondasi baru: [access_rules.php](/c:/xampp/htdocs/coldkeygmi/config/access_rules.php)
  - Evaluator rule: [AccessRuleService.php](/c:/xampp/htdocs/coldkeygmi/app/Support/AccessRuleService.php)
  - Controller tahap awal: [ExitPermitController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/ExitPermitController.php)
- `Visitor Form`
  - Pondasi baru: [access_rules.php](/c:/xampp/htdocs/coldkeygmi/config/access_rules.php)
  - Evaluator rule: [AccessRuleService.php](/c:/xampp/htdocs/coldkeygmi/app/Support/AccessRuleService.php)
  - Controller tahap awal: [VisitorFormController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/VisitorFormController.php)
- `Plugging`
  - Pondasi baru: [access_rules.php](/c:/xampp/htdocs/coldkeygmi/config/access_rules.php)
  - Evaluator rule: [AccessRuleService.php](/c:/xampp/htdocs/coldkeygmi/app/Support/AccessRuleService.php)
  - Controller tahap awal: [PluggingController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/PluggingController.php)
- `Berita Acara`
  - Pondasi baru: [access_rules.php](/c:/xampp/htdocs/coldkeygmi/config/access_rules.php)
  - Evaluator rule: [AccessRuleService.php](/c:/xampp/htdocs/coldkeygmi/app/Support/AccessRuleService.php)
  - Controller tahap awal: [BeritaAcaraController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/BeritaAcaraController.php)
- `Electricity Standard Meter`
  - Pondasi baru: [access_rules.php](/c:/xampp/htdocs/coldkeygmi/config/access_rules.php)
  - Evaluator rule: [AccessRuleService.php](/c:/xampp/htdocs/coldkeygmi/app/Support/AccessRuleService.php)
  - Controller tahap awal: [ElectricityStandardMeterController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/ElectricityStandardMeterController.php)
- `Electricity HV Meter`
  - Pondasi baru: [access_rules.php](/c:/xampp/htdocs/coldkeygmi/config/access_rules.php)
  - Evaluator rule: [AccessRuleService.php](/c:/xampp/htdocs/coldkeygmi/app/Support/AccessRuleService.php)
  - Controller tahap awal: [ElectricityHvMeterController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/ElectricityHvMeterController.php)
- `Water Meter`
  - Pondasi baru: [access_rules.php](/c:/xampp/htdocs/coldkeygmi/config/access_rules.php)
  - Evaluator rule: [AccessRuleService.php](/c:/xampp/htdocs/coldkeygmi/app/Support/AccessRuleService.php)
  - Controller tahap awal: [WaterMeterController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/WaterMeterController.php)
- `Tickets`
  - Pondasi baru: [access_rules.php](/c:/xampp/htdocs/coldkeygmi/config/access_rules.php)
  - Evaluator rule: [AccessRuleService.php](/c:/xampp/htdocs/coldkeygmi/app/Support/AccessRuleService.php)
  - Controller tahap awal: [TicketController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/TicketController.php)
  - Model tahap awal: [Ticket.php](/c:/xampp/htdocs/coldkeygmi/app/Models/Ticket.php)

## Audit Modul Hardcoded

| Modul | File | Jenis Rule | Hardcode Saat Ini | Catatan Refactor |
| --- | --- | --- | --- | --- |
| Overtime | [OvertimeController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/OvertimeController.php) | List view, submit, approve | Admin, HRD, manager, supervisor, scope departemen | Pilot refactor dimulai ke config + service |
| Leave Permission | [LeavePermissionController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/LeavePermissionController.php) | List view, review, edit, create scope | IT, HRD, manager, supervisor, OPS | Tahap refactor lanjut selesai: list view, review, submit-for-others, edit ability, dan pengecualian OPS->IT sudah pindah ke config |
| Roster List | [RosterController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/RosterController.php) | List view, approve, special approve | IT, HRD, manager, OPS, INV/RSC/ADL | Tahap awal refactor selesai untuk list view dan approve scope; extra OPS->INV/RSC/ADL sudah pindah ke config |
| Attendance Log | [AttendanceLogController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/AttendanceLogController.php) | Approval correction, manage correction | IT/HRD | Tahap awal refactor selesai untuk ability manage/approve/reject correction |
| Exit Permit | [ExitPermitController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/ExitPermitController.php) | Multi-step approval | Security, HRD, manager/supervisor | Tahap awal refactor selesai untuk rule per tahap approval |
| Visitor Form | [VisitorFormController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/VisitorFormController.php) | Approval | Security, user tujuan | Tahap awal refactor selesai untuk security approver; host approver tetap dinamis per record |
| Request Access | [RequestAccessController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/RequestAccessController.php) | List view, review, process | IT, manager, admin | Tahap awal refactor selesai untuk list view, create-new-user, review, process |
| Plugging | [PluggingController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/PluggingController.php) | Approval | OPS / operational manager | Tahap awal refactor selesai untuk ability approve |
| Berita Acara | [BeritaAcaraController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/BeritaAcaraController.php) | Delete | IT | Tahap awal refactor selesai untuk ability delete |
| Electricity Standard Meter | [ElectricityStandardMeterController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/ElectricityStandardMeterController.php) | Edit list | IT | Tahap awal refactor selesai untuk ability edit list |
| Electricity HV Meter | [ElectricityHvMeterController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/ElectricityHvMeterController.php), [Index.vue](/c:/xampp/htdocs/coldkeygmi/resources/js/Pages/GMIUM/ResourceMonitoring/Electricity/HvMeter/Index.vue) | Edit list, export | IT | Tahap refactor lanjut selesai untuk ability edit list dan export logs |
| Water Meter | [WaterMeterController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/WaterMeterController.php), [Index.vue](/c:/xampp/htdocs/coldkeygmi/resources/js/Pages/GMIUM/ResourceMonitoring/WaterMeter/Index.vue) | Edit list, export | IT | Tahap refactor lanjut selesai untuk ability edit list dan export logs |
| GMIIC Checklist | [Create.vue](/c:/xampp/htdocs/coldkeygmi/resources/js/Pages/GMIIC/Checklist/Create.vue), [Index.vue](/c:/xampp/htdocs/coldkeygmi/resources/js/Pages/GMIIC/Checklist/Index.vue), [web.php](/c:/xampp/htdocs/coldkeygmi/routes/web.php) | Frontend approve visibility, delete visibility | HSE, Manager/HSE, IT | Tahap awal sinkronisasi selesai: ability backend sudah dikirim ke frontend untuk delete checklist, final approve warehouse, dan HSE approve Kotak P3K |
| Tickets | [TicketController.php](/c:/xampp/htdocs/coldkeygmi/app/Http/Controllers/TicketController.php), [Ticket.php](/c:/xampp/htdocs/coldkeygmi/app/Models/Ticket.php) | Department manager view/distribute | Admin, manager departemen | Tahap awal refactor selesai untuk scope manager departemen dan admin override via access rule service |

## Pola Hardcode Yang Paling Sering Muncul

1. `department code / name`
   - Contoh: `IT`, `HRD`, `OPS`, `SEC`
2. `position capability`
   - Contoh: `is_manager`, supervisor dari nama/kode posisi
3. `department scope`
   - Contoh: semua departemen, departemen sendiri, departemen turunan manager
4. `special department set`
   - Contoh: `INV`, `RSC`, `ADL`
5. `approval stage`
   - Contoh: tahap manager, HSE, Security, HRD

## Rekomendasi Lanjutan

1. Lanjutkan modul berikutnya:
   - `Electricity Standard Meter` bila nanti ditambah endpoint export atau action lain selain `edit_list`
   - modul frontend lain yang masih menyembunyikan tombol berdasarkan role tanpa ability backend
2. Lanjutkan sinkronisasi rule frontend `GMIIC Checklist` yang masih tersisa bila nanti ada ability baru per template, supaya semua visibility tombol tetap mengikuti backend.
