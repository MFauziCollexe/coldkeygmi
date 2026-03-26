<?php

return [
    [
        'key' => 'gmisl',
        'label' => 'GMISL',
        'children' => [
            ['key' => 'gmisl.report_accounting', 'label' => 'Report Accounting'],
            ['key' => 'gmisl.hpp', 'label' => 'HPP'],
            ['key' => 'gmisl.purchasing', 'label' => 'Purchasing'],
            ['key' => 'gmisl.utility', 'label' => 'Utility', 'children' => [
                ['key' => 'utility.tickets', 'label' => 'Tickets'],
                ['key' => 'utility.request_access', 'label' => 'Request Access'],
                ['key' => 'utility.date_code', 'label' => 'No Identity'],
                ['key' => 'utility.check_inline', 'label' => 'Check Inline'],
                ['key' => 'utility.berita_acara', 'label' => 'Berita Acara'],
            ]],
        ],
    ],
    [
        'key' => 'master_data',
        'label' => 'Master Data',
        'children' => [
            ['key' => 'gmisl.master_data.customer', 'label' => 'Customer'],
            ['key' => 'gmisl.master_data.employee', 'label' => 'Employee'],
            ['key' => 'gmisl.master_data.department', 'label' => 'Department'],
            ['key' => 'gmisl.master_data.position', 'label' => 'Position'],
            ['key' => 'gmisl.master_data.vehicle_type', 'label' => 'Jenis Kendaraan'],
        ],
    ],
    [
        'key' => 'gmium',
        'label' => 'GMIUM',
        'children' => [
            ['key' => 'gmium.plugging', 'label' => 'Plugging'],
            ['key' => 'gmium.plugging.approval', 'label' => 'Plugging Approval'],
            ['key' => 'gmium.resource_monitoring', 'label' => 'Resource Monitoring', 'children' => [
                ['key' => 'gmium.resource_monitoring.electricity', 'label' => 'Electricity', 'children' => [
                    ['key' => 'gmium.resource_monitoring.electricity.hv_meter', 'label' => 'HV Meter'],
                    ['key' => 'gmium.resource_monitoring.electricity.standard_meter', 'label' => 'Standard Meter'],
                ]],
                ['key' => 'gmium.resource_monitoring.water_meter', 'label' => 'Water Meter'],
            ]],
            ['key' => 'gmium.utility_report', 'label' => 'Utility Report'],
        ],
    ],
    [
        'key' => 'gmiic',
        'label' => 'GMIIC',
        'children' => [
            ['key' => 'gmiic.checklist', 'label' => 'Checklist'],
        ],
    ],
    [
        'key' => 'gmi_visitor_permit',
        'label' => 'Leave Permit',
        'children' => [
            ['key' => 'gmi_visitor_permit.visitor_form', 'label' => 'Visitor Form'],
            ['key' => 'gmi_visitor_permit.exit_permit', 'label' => 'Exit Permit (Surat Izin Keluar)'],
        ],
    ],
    [
        'key' => 'gmihr',
        'label' => 'GMIHR',
        'children' => [
            ['key' => 'gmihr.attendance', 'label' => 'Time & Attendance', 'children' => [
                ['key' => 'gmihr.attendance.log', 'label' => 'Attendance Log'],
                ['key' => 'gmihr.attendance.leave_permission', 'label' => 'Leave & Permission'],
                ['key' => 'gmihr.attendance.roster', 'label' => 'Roster', 'children' => [
                    ['key' => 'gmihr.payroll.roster.upload', 'label' => 'Upload'],
                    ['key' => 'gmihr.payroll.roster.list', 'label' => 'List'],
                ]],
                ['key' => 'gmihr.attendance.overtime', 'label' => 'Overtime'],
                ['key' => 'gmihr.attendance.the_days', 'label' => 'The Days'],
            ]],
            ['key' => 'gmihr.payroll', 'label' => 'Payroll', 'children' => [
                ['key' => 'gmihr.payroll.salary', 'label' => 'Salary'],
                ['key' => 'gmihr.payroll.payslip', 'label' => 'Payslip'],
            ]],
            ['key' => 'gmihr.device', 'label' => 'Device Integration', 'children' => [
                ['key' => 'gmihr.device.fingerprint', 'label' => 'Fingerprint'],
                ['key' => 'gmihr.device.download_fingerprint', 'label' => 'Download FingerPrint'],
            ]],
        ],
    ],
    [
        'key' => 'control',
        'label' => 'Control Panel',
        'children' => [
            ['key' => 'control.users', 'label' => 'Users'],
            ['key' => 'control.module', 'label' => 'Modul Control'],
            ['key' => 'control.logs', 'label' => 'Logs'],
        ],
    ],
];

