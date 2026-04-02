<?php

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
                'manage_corrections' => [
                    ['type' => 'admin'],
                    ['type' => 'department_code', 'value' => 'IT'],
                    ['type' => 'department_name_contains', 'value' => 'INFORMATION TECHNOLOGY'],
                    ['type' => 'department_code', 'value' => 'HRD'],
                    ['type' => 'department_name_contains', 'value' => 'HRD'],
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
    ],
];
