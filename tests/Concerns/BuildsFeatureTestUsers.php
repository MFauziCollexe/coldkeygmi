<?php

namespace Tests\Concerns;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Str;

trait BuildsFeatureTestUsers
{
    protected function createDepartment(array $attributes = []): Department
    {
        $code = $attributes['code'] ?? null;
        if (is_string($code) && trim($code) !== '') {
            return Department::query()->firstOrCreate(
                ['code' => $code],
                array_merge([
                    'name' => $attributes['name'] ?? $code,
                    'is_active' => true,
                ], $attributes)
            );
        }

        return Department::factory()->create($attributes);
    }

    protected function createPosition(?Department $department = null, array $attributes = []): Position
    {
        $department ??= $this->createDepartment();

        $code = $attributes['code'] ?? null;
        if (is_string($code) && trim($code) !== '') {
            return Position::query()->firstOrCreate(
                [
                    'code' => $code,
                ],
                array_merge([
                    'department_id' => $department->id,
                    'name' => $attributes['name'] ?? $code,
                    'is_active' => true,
                ], $attributes)
            );
        }

        return Position::factory()
            ->for($department)
            ->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $departmentAttributes
     * @param  array<string, mixed>  $positionAttributes
     * @param  array<string, mixed>  $userAttributes
     * @param  array<int, string>|string  $moduleKeys
     */
    protected function createManagerUser(
        array $departmentAttributes = [],
        array $positionAttributes = [],
        array $userAttributes = [],
        array|string $moduleKeys = []
    ): User {
        $department = $this->createDepartment($departmentAttributes);
        $position = $this->createPosition($department, array_merge([
            'is_manager' => true,
            'code' => $positionAttributes['code'] ?? 'MGR-' . strtoupper(Str::random(4)),
            'name' => $positionAttributes['name'] ?? 'Manager',
        ], $positionAttributes));

        return $this->createUser(array_merge($userAttributes, [
            'department' => $department,
            'position' => $position,
        ]), $moduleKeys);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createEmployee(User $user, array $attributes = []): Employee
    {
        return Employee::create(array_merge([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'position_id' => $user->position_id,
            'nik' => 'NIK' . strtoupper(Str::random(8)),
            'name' => $attributes['name'] ?? $user->name,
            'employment_status' => 'active',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>|string  $moduleKeys
     */
    protected function createUser(array $attributes = [], array|string $moduleKeys = []): User
    {
        $department = $attributes['department'] ?? null;
        $position = $attributes['position'] ?? null;

        unset($attributes['department'], $attributes['position']);

        if ($position instanceof Position && !$department instanceof Department) {
            $department = $position->department;
        }

        if (!$department instanceof Department) {
            $department = $this->createDepartment();
        }

        if (!$position instanceof Position) {
            $position = $this->createPosition($department);
        }

        $user = User::factory()
            ->active()
            ->create(array_merge([
                'department_id' => $department->id,
                'position_id' => $position->id,
            ], $attributes));

        return $this->grantModulePermissions($user, $moduleKeys);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createAdminUser(array $attributes = []): User
    {
        return $this->createUser(array_merge($attributes, [
            'is_admin' => true,
        ]));
    }

    /**
     * @param  array<int, string>|string  $moduleKeys
     */
    protected function grantModulePermissions(User $user, array|string $moduleKeys): User
    {
        $keys = is_array($moduleKeys) ? $moduleKeys : [$moduleKeys];

        foreach (array_unique(array_filter($keys)) as $moduleKey) {
            $user->modulePermissions()->firstOrCreate([
                'module_key' => $moduleKey,
            ]);
        }

        return $user->refresh();
    }
}
