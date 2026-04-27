<?php

$itChecklistRules = [
    ['type' => 'admin'],
    ['type' => 'department_code', 'value' => 'IT'],
    ['type' => 'department_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
];

$hseChecklistRules = [
    ['type' => 'department_code', 'value' => 'HSE'],
    ['type' => 'department_name_contains', 'value' => 'HSE'],
    ['type' => 'position_code', 'value' => 'HSE'],
    ['type' => 'position_name_contains', 'value' => 'HSE'],
];

$securityChecklistRules = [
    ['type' => 'department_code', 'value' => 'SEC'],
    ['type' => 'department_name_contains', 'value' => 'SECURITY'],
    ['type' => 'position_name_contains', 'value' => 'SECURITY'],
];

$maintenanceChecklistRules = [
    ['type' => 'department_code', 'value' => 'MNT'],
    ['type' => 'department_name_contains', 'value' => 'MAINT'],
    ['type' => 'position_name_contains', 'value' => 'MAINT'],
];

$mergeChecklistRules = static fn (array ...$groups) => array_values(array_merge(...$groups));

$buildTemplatePermissions = static function (array $templateIds, array $viewRules, ?array $approveRules = null): array {
    $permissions = [];
    $approveRules ??= $viewRules;

    foreach ($templateIds as $templateId) {
        $permissions[$templateId] = [
            'view' => $viewRules,
            'approve' => $approveRules,
        ];
    }

    return $permissions;
};

$templatePermissions = array_merge(
    $buildTemplatePermissions(
        [
            'non_warehouse_sanitation',
            'kotak_p3k',
            'apar_smoke_detector_fire_alarm',
            'pengangkutan_sampah_pt_sier',
            'warehouse_sanitation_1',
            'personal_hygiene_karyawan',
            'site_visit_hse',
        ],
        $mergeChecklistRules($itChecklistRules, $hseChecklistRules)
    ),
    $buildTemplatePermissions(
        [
            'sarana_dan_prasarana',
            'site_visit_maintenance',
        ],
        $mergeChecklistRules($itChecklistRules, $maintenanceChecklistRules)
    ),
    $buildTemplatePermissions(
        [
            'patroli_security',
        ],
        $mergeChecklistRules($itChecklistRules, $securityChecklistRules)
    )
);

return [
    'modules' => [
        'overtime' => [
            'scopes' => [
                'view_list' => [
                    'all_if' => [
                        ['type' => 'admin'],
                        ['type' => 'department_code', 'value' => 'HRD'],
                        ['type' => 'department_name_contains', 'value' => 'HRD'],
                    ],
                    'ids_from' => [
                        'managed_departments',
                        'own_department',
                    ],
                ],
                'approve' => [
                    'all_if' => [
                        ['type' => 'admin'],
                    ],
                    'ids_from' => [
                        'managed_departments',
                    ],
                ],
            ],
            'abilities' => [
                'submit_for_others' => [
                    ['type' => 'admin'],
                    ['type' => 'manager'],
                    ['type' => 'supervisor'],
                ],
            ],
        ],
        'leave_permission' => [
            'scopes' => [
                'view_list' => [
                    'all_if' => [
                        ['type' => 'admin'],
                        ['type' => 'department_code', 'value' => 'IT'],
                        ['type' => 'department_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                        ['type' => 'department_code', 'value' => 'HRD'],
                        ['type' => 'department_name_contains', 'value' => 'HRD'],
                    ],
                    'ids_from' => [
                        'managed_departments',
                        'own_department',
                    ],
                    'append_ids_if' => [
                        [
                            'if' => [
                                ['type' => 'manager_in_department_codes', 'values' => ['OPS']],
                            ],
                            'department_codes' => ['IT'],
                        ],
                    ],
                ],
                'review' => [
                    'all_if' => [
                        ['type' => 'admin'],
                    ],
                    'ids_from' => [
                        'managed_departments',
                    ],
                    'append_ids_if' => [
                        [
                            'if' => [
                                ['type' => 'manager_in_department_codes', 'values' => ['OPS']],
                            ],
                            'department_codes' => ['IT'],
                        ],
                    ],
                ],
            ],
            'abilities' => [
                'submit_for_others' => [
                    ['type' => 'admin'],
                    ['type' => 'manager'],
                    ['type' => 'supervisor'],
                ],
                'edit_request_data' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'IT'],
                    ['type' => 'department_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                ],
            ],
        ],
        'roster' => [
            'scopes' => [
                'view_list' => [
                    'all_if' => [
                        ['type' => 'admin'],
                        ['type' => 'department_code', 'value' => 'IT'],
                        ['type' => 'department_code', 'value' => 'HRD'],
                        ['type' => 'department_name_contains', 'value' => 'HRD'],
                    ],
                    'ids_from' => [
                        'own_department',
                    ],
                    'append_ids_if' => [
                        [
                            'if' => [
                                ['type' => 'manager_in_department_codes', 'values' => ['OPS']],
                            ],
                            'department_codes' => ['INV', 'RSC', 'ADL'],
                        ],
                    ],
                ],
                'approve' => [
                    'all_if' => [
                        ['type' => 'admin'],
                        ['type' => 'department_code', 'value' => 'IT'],
                        ['type' => 'department_code', 'value' => 'HRD'],
                        ['type' => 'department_name_contains', 'value' => 'HRD'],
                    ],
                    'ids_from' => [
                        'own_department',
                    ],
                    'append_ids_if' => [
                        [
                            'if' => [
                                ['type' => 'manager_in_department_codes', 'values' => ['OPS']],
                            ],
                            'department_codes' => ['INV', 'RSC', 'ADL'],
                        ],
                    ],
                ],
            ],
        ],
        'attendance_log' => [
            'abilities' => [
                'view_correction_totals' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'IT'],
                    ['type' => 'department_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                    ['type' => 'position_code', 'value' => 'IT'],
                    ['type' => 'position_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                ],
                'manage_corrections' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'IT'],
                    ['type' => 'department_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                    ['type' => 'position_code', 'value' => 'IT'],
                    ['type' => 'position_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                    ['type' => 'department_code', 'value' => 'HRD'],
                    ['type' => 'department_name_contains', 'value' => 'HRD'],
                    ['type' => 'position_code', 'value' => 'HRD'],
                    ['type' => 'position_name_contains', 'value' => 'HRD'],
                ],
            ],
        ],
        'request_access' => [
            'scopes' => [
                'view_list' => [
                    'all_if' => [
                        ['type' => 'admin'],
                        ['type' => 'department_code', 'value' => 'IT'],
                        ['type' => 'department_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                    ],
                    'ids_from' => [
                        'managed_departments',
                    ],
                ],
                'review' => [
                    'all_if' => [
                        ['type' => 'admin'],
                    ],
                    'ids_from' => [
                        'managed_departments',
                    ],
                ],
            ],
            'abilities' => [
                'create_new_user' => [
                    ['type' => 'admin'],
                    ['type' => 'manager'],
                ],
                'process' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'IT'],
                    ['type' => 'department_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                ],
            ],
        ],
        'exit_permit' => [
            'scopes' => [
                'manager_approve' => [
                    'all_if' => [
                        ['type' => 'admin'],
                    ],
                    'ids_from' => [
                        'managed_departments',
                    ],
                ],
            ],
            'abilities' => [
                'security_approve' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'SEC'],
                    ['type' => 'department_name_contains', 'value' => 'SECURITY'],
                ],
                'hrd_approve' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'HRD'],
                    ['type' => 'department_name_contains', 'value' => 'HRD'],
                ],
            ],
        ],
        'visitor_form' => [
            'abilities' => [
                'security_approve' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'SEC'],
                    ['type' => 'department_name_contains', 'value' => 'SECURITY'],
                ],
            ],
        ],
        'plugging' => [
            'abilities' => [
                'approve' => [
                    ['type' => 'manager_in_department_codes', 'values' => ['OPS']],
                ],
            ],
        ],
        'berita_acara' => [
            'abilities' => [
                'delete' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'IT'],
                    ['type' => 'department_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                ],
            ],
        ],
        'electricity_standard_meter' => [
            'abilities' => [
                'edit_list' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'IT'],
                    ['type' => 'department_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                ],
            ],
        ],
        'electricity_hv_meter' => [
            'abilities' => [
                'edit_list' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'IT'],
                    ['type' => 'department_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                ],
                'export_logs' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'IT'],
                    ['type' => 'department_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                ],
            ],
        ],
        'water_meter' => [
            'abilities' => [
                'edit_list' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'IT'],
                    ['type' => 'department_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                ],
                'export_logs' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'IT'],
                    ['type' => 'department_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                ],
            ],
        ],
        'gmiic_checklist' => [
            'template_permissions' => $templatePermissions,
            'abilities' => [
                'delete_entries' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'IT'],
                    ['type' => 'department_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                    ['type' => 'position_code', 'value' => 'IT'],
                    ['type' => 'position_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                ],
                'kotak_p3k_hse_approve' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'HSE'],
                    ['type' => 'department_name_contains', 'value' => 'HSE'],
                    ['type' => 'position_code', 'value' => 'HSE'],
                    ['type' => 'position_name_contains', 'value' => 'HSE'],
                ],
                'warehouse_final_approve' => [
                    ['type' => 'admin'],
                    ['type' => 'manager'],
                    ['type' => 'department_code', 'value' => 'HSE'],
                    ['type' => 'department_name_contains', 'value' => 'HSE'],
                    ['type' => 'position_code', 'value' => 'HSE'],
                    ['type' => 'position_name_contains', 'value' => 'HSE'],
                ],
            ],
        ],
        'tickets' => [
            'scopes' => [
                'manage_department' => [
                    'all_if' => [
                        ['type' => 'admin'],
                    ],
                    'ids_from' => [
                        'managed_departments',
                    ],
                ],
            ],
        ],
        'stock_card' => [
            'abilities' => [
                'manage_master' => [
                    ['type' => 'admin'],
                ],
                'add_stock' => [
                    ['type' => 'admin'],
                ],
                'request_stock' => [
                    ['type' => 'admin'],
                ],
                'approve_request' => [
                    ['type' => 'admin'],
                ],
                'view_history' => [
                    ['type' => 'admin'],
                ],
            ],
        ],
    ],
];
