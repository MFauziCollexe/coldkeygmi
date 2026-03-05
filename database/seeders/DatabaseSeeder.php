<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed departments and positions first
        $this->call([
            DepartmentAndPositionSeeder::class,
            VehicleTypeSeeder::class,
        ]);

        // Create one active admin user (if not exists)
        User::firstOrCreate(
            ['email' => 'admin@coldkeygmi.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => \Illuminate\Support\Facades\Hash::make('Admin@1234'),
                'status' => 'active',
                'department' => 'Administration',
                'job_position' => 'System Administrator',
                'work_position' => 'Lead',
                'user_created' => 'system',
                'user_updated' => 'system',
            ]
        );

        // Create 5 sample deactivated users
        User::firstOrCreate(
            ['email' => 'john@example.com'],
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'status' => 'deactivated',
                'department' => 'Engineering',
                'job_position' => 'Developer',
                'work_position' => 'Senior',
                'user_created' => 'system',
                'user_updated' => 'system',
            ]
        );

        User::firstOrCreate(
            ['email' => 'jane@example.com'],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'status' => 'deactivated',
                'department' => 'Marketing',
                'job_position' => 'Marketing Manager',
                'work_position' => 'Lead',
                'user_created' => 'system',
                'user_updated' => 'system',
            ]
        );

        User::firstOrCreate(
            ['email' => 'bob@example.com'],
            [
                'first_name' => 'Bob',
                'last_name' => 'Johnson',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'status' => 'deactivated',
                'department' => 'Sales',
                'job_position' => 'Sales Representative',
                'work_position' => 'Junior',
                'user_created' => 'system',
                'user_updated' => 'system',
            ]
        );

        User::firstOrCreate(
            ['email' => 'alice@example.com'],
            [
                'first_name' => 'Alice',
                'last_name' => 'Williams',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'status' => 'deactivated',
                'department' => 'HR',
                'job_position' => 'HR Specialist',
                'work_position' => 'Senior',
                'user_created' => 'system',
                'user_updated' => 'system',
            ]
        );

        User::firstOrCreate(
            ['email' => 'charlie@example.com'],
            [
                'first_name' => 'Charlie',
                'last_name' => 'Brown',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'status' => 'deactivated',
                'department' => 'Finance',
                'job_position' => 'Accountant',
                'work_position' => 'Manager',
                'user_created' => 'system',
                'user_updated' => 'system',
            ]
        );
    }
}
