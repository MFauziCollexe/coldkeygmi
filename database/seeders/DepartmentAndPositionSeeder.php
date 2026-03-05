<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class DepartmentAndPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Departments
        $departments = [
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'Human Resources Department',
                'is_active' => true,
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
                'description' => 'Finance Department',
                'is_active' => true,
            ],
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'description' => 'Information Technology Department',
                'is_active' => true,
            ],
            [
                'name' => 'Marketing',
                'code' => 'MKT',
                'description' => 'Marketing Department',
                'is_active' => true,
            ],
            [
                'name' => 'Opperational',
                'code' => 'OPS',
                'description' => 'Opperational Department',
                'is_active' => true,
            ],
            [
                'name' => 'Sales',
                'code' => 'SLS',
                'description' => 'Sales Department',
                'is_active' => true,
            ],
            [
                'name' => 'Engineering',
                'code' => 'ENG',
                'description' => 'Engineering Department',
                'is_active' => true,
            ],
            [
                'name' => 'Customer Service',
                'code' => 'CS',
                'description' => 'Customer Service Department',
                'is_active' => true,
            ],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }

        // Create Positions
        $positions = [
            // HR Positions
            ['name' => 'HR Manager', 'code' => 'HR-MGR', 'department_id' => 1, 'description' => 'Human Resources Manager', 'is_active' => true],
            ['name' => 'HR Staff', 'code' => 'HR-STF', 'department_id' => 1, 'description' => 'Human Resources Staff', 'is_active' => true],
            ['name' => 'Recruiter', 'code' => 'HR-REC', 'department_id' => 1, 'description' => 'Recruitment Officer', 'is_active' => true],

            // Finance Positions
            ['name' => 'Finance Manager', 'code' => 'FIN-MGR', 'department_id' => 2, 'description' => 'Finance Manager', 'is_active' => true],
            ['name' => 'Accountant', 'code' => 'FIN-ACC', 'department_id' => 2, 'description' => 'Accountant', 'is_active' => true],
            ['name' => 'Finance Staff', 'code' => 'FIN-STF', 'department_id' => 2, 'description' => 'Finance Staff', 'is_active' => true],

            // IT Positions
            ['name' => 'IT Manager', 'code' => 'IT-MGR', 'department_id' => 3, 'description' => 'IT Manager', 'is_active' => true],
            ['name' => 'System Administrator', 'code' => 'IT-SYS', 'department_id' => 3, 'description' => 'System Administrator', 'is_active' => true],
            ['name' => 'Developer', 'code' => 'IT-DEV', 'department_id' => 3, 'description' => 'Software Developer', 'is_active' => true],
            ['name' => 'IT Support', 'code' => 'IT-SUP', 'department_id' => 3, 'description' => 'IT Support Specialist', 'is_active' => true],

            // Marketing Positions
            ['name' => 'Marketing Manager', 'code' => 'MKT-MGR', 'department_id' => 4, 'description' => 'Marketing Manager', 'is_active' => true],
            ['name' => 'Marketing Staff', 'code' => 'MKT-STF', 'department_id' => 4, 'description' => 'Marketing Staff', 'is_active' => true],
            ['name' => 'Content Creator', 'code' => 'MKT-CTC', 'department_id' => 4, 'description' => 'Content Creator', 'is_active' => true],

            // Operations Positions
            ['name' => 'Manager', 'code' => 'OPS-MGR', 'department_id' => 5, 'description' => 'Manager', 'is_active' => true],
            ['name' => 'Supervisor', 'code' => 'OPS-SPV', 'department_id' => 5, 'description' => 'Supervisor', 'is_active' => true],
            ['name' => 'Leader', 'code' => 'OPS-LDR', 'department_id' => 5, 'description' => 'Leader', 'is_active' => true],
            ['name' => 'Staff', 'code' => 'OPS-STF', 'department_id' => 5, 'description' => 'Staff', 'is_active' => true],

            // Admin Loket Positions
            ['name' => 'Leader', 'code' => 'ADL-LDR', 'department_id' => 9, 'description' => 'Leader', 'is_active' => true],
            ['name' => 'Staff', 'code' => 'ADL-STF', 'department_id' => 9, 'description' => 'Staff', 'is_active' => true],

            // Sales Positions
            ['name' => 'Sales Manager', 'code' => 'SLS-MGR', 'department_id' => 6, 'description' => 'Sales Manager', 'is_active' => true],
            ['name' => 'Sales Executive', 'code' => 'SLS-EXE', 'department_id' => 6, 'description' => 'Sales Executive', 'is_active' => true],
            ['name' => 'Sales Staff', 'code' => 'SLS-STF', 'department_id' => 6, 'description' => 'Sales Staff', 'is_active' => true],

            // Engineering Positions
            ['name' => 'Engineering Manager', 'code' => 'ENG-MGR', 'department_id' => 7, 'description' => 'Engineering Manager', 'is_active' => true],
            ['name' => 'Senior Engineer', 'code' => 'ENG-SR', 'department_id' => 7, 'description' => 'Senior Engineer', 'is_active' => true],
            ['name' => 'Junior Engineer', 'code' => 'ENG-JR', 'department_id' => 7, 'description' => 'Junior Engineer', 'is_active' => true],
            ['name' => 'Technician', 'code' => 'ENG-TECH', 'department_id' => 7, 'description' => 'Technician', 'is_active' => true],

            // Customer Service Positions
            ['name' => 'Customer Service Manager', 'code' => 'CS-MGR', 'department_id' => 8, 'description' => 'Customer Service Manager', 'is_active' => true],
            ['name' => 'Customer Service Representative', 'code' => 'CS-REP', 'department_id' => 8, 'description' => 'Customer Service Representative', 'is_active' => true],
            ['name' => 'Call Center Agent', 'code' => 'CS-AGT', 'department_id' => 8, 'description' => 'Call Center Agent', 'is_active' => true],
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }
    }
}
