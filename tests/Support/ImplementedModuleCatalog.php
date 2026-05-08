<?php

namespace Tests\Support;

class ImplementedModuleCatalog
{
    /**
     * @return array<string, array{label: string, uri: string}>
     */
    public static function pages(): array
    {
        return [
            'utility.tickets' => ['label' => 'Tickets', 'uri' => '/tickets'],
            'utility.request_access' => ['label' => 'Request Access', 'uri' => '/request-access'],
            'utility.date_code' => ['label' => 'No Identity', 'uri' => '/gmisl/utility/date-code'],
            'utility.check_inline' => ['label' => 'Check Inline', 'uri' => '/check-inline'],
            'utility.berita_acara' => ['label' => 'Berita Acara', 'uri' => '/gmisl/utility/berita-acara'],
            'utility.stock_card' => ['label' => 'Stock Card', 'uri' => '/gmisl/utility/stock-card'],
            'tools.compress_pdf' => ['label' => 'Compress PDF', 'uri' => '/gmisl/tools/compress-pdf'],
            'tools.merge_pdf' => ['label' => 'Merge PDF', 'uri' => '/gmisl/tools/merge-pdf'],
            'tools.split_pdf' => ['label' => 'Split PDF', 'uri' => '/gmisl/tools/split-pdf'],
            'gmisl.master_data.customer' => ['label' => 'Customer', 'uri' => '/master-data/customer'],
            'gmisl.master_data.employee' => ['label' => 'Employee', 'uri' => '/master-data/employee'],
            'gmisl.master_data.department' => ['label' => 'Department', 'uri' => '/master-data/department'],
            'gmisl.master_data.position' => ['label' => 'Position', 'uri' => '/master-data/position'],
            'gmisl.master_data.vehicle_type' => ['label' => 'Jenis Kendaraan', 'uri' => '/master-data/vehicle-type'],
            'gmisl.master_data.stock_card' => ['label' => 'Stock Card Master', 'uri' => '/master-data/stock-card'],
            'gmisl.master_data.stock_card_item_type' => ['label' => 'Jenis/Tipe Barang', 'uri' => '/master-data/stock-card-item-type'],
            'gmisl.master_data.stock_card_unit' => ['label' => 'Satuan Stock Card', 'uri' => '/master-data/stock-card-unit'],
            'gmisl.master_data.attendance_lock_area' => ['label' => 'Area Absensi', 'uri' => '/master-data/attendance-lock-area'],
            'gmium.plugging' => ['label' => 'Plugging', 'uri' => '/gmium/plugging'],
            'gmium.plugging.approval' => ['label' => 'Plugging Approval', 'uri' => '/gmium/plugging/approval'],
            'gmium.resource_monitoring.electricity.standard_meter' => ['label' => 'Electricity Standard Meter', 'uri' => '/gmium/resource-monitoring/electricity/standard-meter'],
            'gmium.resource_monitoring.electricity.hv_meter' => ['label' => 'Electricity HV Meter', 'uri' => '/gmium/resource-monitoring/electricity/hv-meter'],
            'gmium.resource_monitoring.water_meter' => ['label' => 'Water Meter', 'uri' => '/gmium/resource-monitoring/water-meter'],
            'gmium.utility_report' => ['label' => 'Utility Report', 'uri' => '/gmium/utility-report'],
            'gmiic.checklist' => ['label' => 'Checklist', 'uri' => '/gmiic/checklist'],
            'gmi_visitor_permit.visitor_form' => ['label' => 'Visitor Form', 'uri' => '/gmi-visitor-permit/visitor-form'],
            'gmi_visitor_permit.exit_permit' => ['label' => 'Exit Permit', 'uri' => '/gmi-visitor-permit/exit-permit'],
            'gmihr.attendance.log' => ['label' => 'Attendance Log', 'uri' => '/attendance-log'],
            'gmihr.attendance.absensi' => ['label' => 'Absensi', 'uri' => '/absensi'],
            'gmihr.attendance.approval' => ['label' => 'Attendance Approval', 'uri' => '/attendance-approval'],
            'gmihr.attendance.leave_permission' => ['label' => 'Leave Permission', 'uri' => '/leave-permission'],
            'gmihr.payroll.roster.upload' => ['label' => 'Roster Upload', 'uri' => '/roster/upload'],
            'gmihr.payroll.roster.list' => ['label' => 'Roster List', 'uri' => '/roster/list'],
            'gmihr.attendance.overtime' => ['label' => 'Overtime', 'uri' => '/overtime'],
            'gmihr.attendance.the_days' => ['label' => 'The Days', 'uri' => '/the-days'],
            'gmihr.payroll.salary' => ['label' => 'Salary', 'uri' => '/salary'],
            'gmihr.payroll.payslip' => ['label' => 'Payslip', 'uri' => '/payslip'],
            'gmihr.device.fingerprint' => ['label' => 'Fingerprint', 'uri' => '/fingerprint'],
            'control.users' => ['label' => 'Control Users', 'uri' => '/control-panel/user'],
            'control.module' => ['label' => 'Module Control', 'uri' => '/control-panel/module-control'],
            'control.logs' => ['label' => 'Logs', 'uri' => '/control-panel/logs'],
            'control.access_rules' => ['label' => 'Access Rules', 'uri' => '/control-panel/access-rules'],
            'control.database_backup' => ['label' => 'Database Backup', 'uri' => '/control-panel/database-backup'],
        ];
    }
}
