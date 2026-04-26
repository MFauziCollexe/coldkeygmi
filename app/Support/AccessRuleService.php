<?php

namespace App\Support;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\File;

class AccessRuleService
{
    protected int $auditLimit = 100;

    public function modules(): array
    {
        $defaults = $this->defaultModules();
        $overrides = $this->loadOverrides();

        if (empty($overrides)) {
            return $defaults;
        }

        return array_replace($defaults, $overrides);
    }

    public function defaultModules(): array
    {
        return config('access_rules.modules', []);
    }

    public function overrideModules(): array
    {
        return $this->loadOverrides();
    }

    public function hasOverrides(): bool
    {
        return File::exists($this->overrideFilePath());
    }

    public function overrideMeta(): array
    {
        $path = $this->overrideFilePath();

        if (!File::exists($path)) {
            return [
                'exists' => false,
                'path' => $path,
                'updated_at' => null,
            ];
        }

        return [
            'exists' => true,
            'path' => $path,
            'updated_at' => date('Y-m-d H:i:s', File::lastModified($path)),
        ];
    }

    public function saveOverrides(array $modules): void
    {
        $path = $this->overrideFilePath();
        $directory = dirname($path);

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put(
            $path,
            json_encode($modules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public function resetOverrides(): void
    {
        $path = $this->overrideFilePath();

        if (File::exists($path)) {
            File::delete($path);
        }
    }

    public function auditTrail(int $limit = 30): array
    {
        return array_slice($this->loadAuditTrail(), 0, max(1, $limit));
    }

    public function rollbackToAudit(string $auditId): bool
    {
        $entry = collect($this->loadAuditTrail())
            ->first(fn ($item) => (string) ($item['id'] ?? '') === $auditId);

        if (!is_array($entry)) {
            return false;
        }

        $afterOverrides = $entry['snapshots']['after_overrides'] ?? null;

        if (!is_array($afterOverrides)) {
            return false;
        }

        if (empty($afterOverrides)) {
            $this->resetOverrides();
            return true;
        }

        $this->saveOverrides($afterOverrides);

        return true;
    }

    public function logAudit(
        User|int|null $user,
        string $action,
        array $beforeModules,
        array $afterModules,
        array $beforeOverrides = [],
        array $afterOverrides = []
    ): void
    {
        $resolvedUser = $this->resolveUser($user);
        $entry = [
            'id' => uniqid('access-rule-', true),
            'action' => $action,
            'performed_at' => now()->format('Y-m-d H:i:s'),
            'user' => [
                'id' => $resolvedUser?->id,
                'name' => $resolvedUser?->name,
                'email' => $resolvedUser?->email,
            ],
            'summary' => $this->buildAuditSummary($beforeModules, $afterModules),
            'snapshots' => [
                'before_overrides' => $beforeOverrides,
                'after_overrides' => $afterOverrides,
            ],
        ];

        $entries = $this->loadAuditTrail();
        array_unshift($entries, $entry);
        $entries = array_slice($entries, 0, $this->auditLimit);

        $path = $this->auditFilePath();
        $directory = dirname($path);

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put(
            $path,
            json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public function isAdmin(User|int|null $user): bool
    {
        $resolvedUser = $this->resolveUser($user);
        if (!$resolvedUser) {
            return false;
        }

        return $resolvedUser->email === 'admin@coldkeygmi.com' || $resolvedUser->isAdmin();
    }

    public function isManager(User|int|null $user): bool
    {
        return (bool) $this->resolvePosition($user)?->is_manager;
    }

    public function isSupervisor(User|int|null $user): bool
    {
        $position = $this->resolvePosition($user);
        if (!$position) {
            return false;
        }

        $code = strtoupper(trim((string) ($position->code ?? '')));
        $name = strtoupper(trim((string) ($position->name ?? '')));

        if ($code === 'SPV' || str_ends_with($code, '-SPV') || str_contains($code, 'SPV')) {
            return true;
        }

        return str_contains($name, 'SUPERVISOR') || str_contains($name, 'SPV');
    }

    public function allows(User|int|null $user, string $moduleKey, string $ability, array $context = []): bool
    {
        $rules = (array) data_get($this->modules(), "{$moduleKey}.abilities.{$ability}", []);
        if (empty($rules)) {
            return false;
        }

        return $this->matchesAnyRule($user, $rules, $context);
    }

    public function allowsRules(User|int|null $user, array $rules, array $context = []): bool
    {
        if (empty($rules)) {
            return false;
        }

        return $this->matchesAnyRule($user, $rules, $context);
    }

    public function canViewAllDepartments(User|int|null $user, string $moduleKey, string $scope = 'view_list'): bool
    {
        $rules = (array) data_get($this->modules(), "{$moduleKey}.scopes.{$scope}.all_if", []);
        if (empty($rules)) {
            return false;
        }

        return $this->matchesAnyRule($user, $rules);
    }

    public function visibleDepartmentIds(User|int|null $user, string $moduleKey, string $scope = 'view_list'): array
    {
        if ($this->canViewAllDepartments($user, $moduleKey, $scope)) {
            return Department::query()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $sources = (array) data_get($this->modules(), "{$moduleKey}.scopes.{$scope}.ids_from", []);
        $resolvedUser = $this->resolveUser($user);
        $employee = $this->resolveEmployee($resolvedUser);

        $departmentIds = [];
        foreach ($sources as $source) {
            $departmentIds = array_merge($departmentIds, $this->resolveDepartmentIdsFromSource($source, $resolvedUser, $employee));
        }

        $conditionalAppends = (array) data_get($this->modules(), "{$moduleKey}.scopes.{$scope}.append_ids_if", []);
        foreach ($conditionalAppends as $appendConfig) {
            $conditions = (array) ($appendConfig['if'] ?? []);
            if (empty($conditions) || !$this->matchesAnyRule($resolvedUser, $conditions)) {
                continue;
            }

            $departmentCodes = array_values(array_filter(array_map(
                fn ($code) => strtoupper(trim((string) $code)),
                (array) ($appendConfig['department_codes'] ?? [])
            )));

            if (!empty($departmentCodes)) {
                $departmentIds = array_merge($departmentIds, $this->resolveDepartmentIdsByCodes($departmentCodes));
            }
        }

        return array_values(array_unique(array_filter(array_map('intval', $departmentIds))));
    }

    public function canAccessDepartment(User|int|null $user, string $moduleKey, string $scope, ?int $departmentId): bool
    {
        $targetDepartmentId = (int) $departmentId;
        if ($targetDepartmentId <= 0) {
            return false;
        }

        if ($this->canViewAllDepartments($user, $moduleKey, $scope)) {
            return true;
        }

        return in_array($targetDepartmentId, $this->visibleDepartmentIds($user, $moduleKey, $scope), true);
    }

    protected function matchesAnyRule(User|int|null $user, array $rules, array $context = []): bool
    {
        $resolvedUser = $this->resolveUser($user);
        if (!$resolvedUser) {
            return false;
        }

        $employee = $this->resolveEmployee($resolvedUser);

        foreach ($rules as $rule) {
            if ($this->evaluateRule($rule, $resolvedUser, $employee, $context)) {
                return true;
            }
        }

        return false;
    }

    protected function evaluateRule(array|string $rule, User $user, ?Employee $employee, array $context = []): bool
    {
        $rule = is_array($rule) ? $rule : ['type' => $rule];
        $type = (string) ($rule['type'] ?? '');

        return match ($type) {
            'admin' => $this->isAdmin($user),
            'manager' => $this->isManager($user),
            'supervisor' => $this->isSupervisor($user),
            'manager_in_department_codes' => $this->isManager($user)
                && in_array(
                    strtoupper((string) ($this->resolveDepartment($user, $employee)?->code ?? '')),
                    array_map(fn ($code) => strtoupper((string) $code), (array) ($rule['values'] ?? [])),
                    true
                ),
            'department_code' => strtoupper((string) ($this->resolveDepartment($user, $employee)?->code ?? '')) === strtoupper((string) ($rule['value'] ?? '')),
            'department_name_contains' => str_contains(
                strtoupper((string) ($this->resolveDepartment($user, $employee)?->name ?? '')),
                strtoupper((string) ($rule['value'] ?? ''))
            ),
            'position_code' => strtoupper((string) ($this->resolvePosition($user, $employee)?->code ?? '')) === strtoupper((string) ($rule['value'] ?? '')),
            'position_name_contains' => str_contains(
                strtoupper((string) ($this->resolvePosition($user, $employee)?->name ?? '')),
                strtoupper((string) ($rule['value'] ?? ''))
            ),
            default => false,
        };
    }

    protected function resolveDepartmentIdsFromSource(string $source, ?User $user, ?Employee $employee): array
    {
        return match ($source) {
            'managed_departments' => $this->resolveManagedDepartmentIds($user, $employee),
            'own_department' => $this->resolveOwnDepartmentId($user, $employee) ? [(int) $this->resolveOwnDepartmentId($user, $employee)] : [],
            default => [],
        };
    }

    protected function resolveManagedDepartmentIds(?User $user, ?Employee $employee): array
    {
        if (!$user || !$this->isManager($user)) {
            return [];
        }

        $position = $this->resolvePosition($user, $employee);
        $departmentId = (int) ($position?->department_id ?? 0);
        if ($departmentId <= 0) {
            return [];
        }

        return DepartmentScope::expandManagedDepartmentIds([$departmentId]);
    }

    protected function resolveOwnDepartmentId(?User $user, ?Employee $employee): ?int
    {
        $departmentId = (int) ($user?->department_id ?? 0);
        if ($departmentId > 0) {
            return $departmentId;
        }

        $departmentId = (int) ($employee?->department_id ?? 0);
        return $departmentId > 0 ? $departmentId : null;
    }

    protected function resolveDepartmentIdsByCodes(array $departmentCodes): array
    {
        if (empty($departmentCodes)) {
            return [];
        }

        return Department::query()
            ->whereIn('code', $departmentCodes)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function resolveDepartment(?User $user, ?Employee $employee)
    {
        if ($user?->department) {
            return $user->department;
        }

        if ($employee?->department) {
            return $employee->department;
        }

        $departmentId = $this->resolveOwnDepartmentId($user, $employee);
        return $departmentId ? Department::query()->select('id', 'code', 'name')->find($departmentId) : null;
    }

    protected function resolvePosition(User|int|null $user, ?Employee $employee = null): ?Position
    {
        $resolvedUser = $this->resolveUser($user);
        $employee ??= $this->resolveEmployee($resolvedUser);

        if ($resolvedUser?->position) {
            return $resolvedUser->position;
        }

        if ($employee?->position) {
            return $employee->position;
        }

        $positionId = (int) ($resolvedUser?->position_id ?? 0);
        if ($positionId <= 0) {
            $positionId = (int) ($employee?->position_id ?? 0);
        }

        return $positionId > 0
            ? Position::query()->select('id', 'name', 'code', 'is_manager', 'department_id')->find($positionId)
            : null;
    }

    protected function resolveEmployee(?User $user): ?Employee
    {
        if (!$user) {
            return null;
        }

        return Employee::query()
            ->with([
                'department:id,code,name',
                'position:id,name,code,is_manager,department_id',
            ])
            ->select('id', 'user_id', 'department_id', 'position_id', 'name', 'nik', 'employment_status')
            ->where('user_id', $user->id)
            ->first();
    }

    protected function resolveUser(User|int|null $user): ?User
    {
        if ($user instanceof User) {
            $user->loadMissing([
                'department:id,code,name',
                'position:id,name,code,is_manager,department_id',
            ]);

            return $user;
        }

        if (!$user) {
            return null;
        }

        return User::query()
            ->with([
                'department:id,code,name',
                'position:id,name,code,is_manager,department_id',
            ])
            ->find($user);
    }

    protected function loadOverrides(): array
    {
        $path = $this->overrideFilePath();
        if (!File::exists($path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function loadAuditTrail(): array
    {
        $path = $this->auditFilePath();
        if (!File::exists($path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function overrideFilePath(): string
    {
        return storage_path('app/access-rules-overrides.json');
    }

    protected function auditFilePath(): string
    {
        return storage_path('app/access-rules-audit.json');
    }

    protected function buildAuditSummary(array $beforeModules, array $afterModules): array
    {
        $beforeKeys = array_keys($beforeModules);
        $afterKeys = array_keys($afterModules);

        $addedModules = array_values(array_diff($afterKeys, $beforeKeys));
        $removedModules = array_values(array_diff($beforeKeys, $afterKeys));

        $commonModules = array_values(array_intersect($beforeKeys, $afterKeys));
        $changedModules = [];

        foreach ($commonModules as $moduleKey) {
            $beforeModule = $beforeModules[$moduleKey] ?? [];
            $afterModule = $afterModules[$moduleKey] ?? [];

            if ($this->stableJson($beforeModule) === $this->stableJson($afterModule)) {
                continue;
            }

            $beforeScopeKeys = array_keys((array) ($beforeModule['scopes'] ?? []));
            $afterScopeKeys = array_keys((array) ($afterModule['scopes'] ?? []));
            $beforeAbilityKeys = array_keys((array) ($beforeModule['abilities'] ?? []));
            $afterAbilityKeys = array_keys((array) ($afterModule['abilities'] ?? []));

            $scopesChanged = [];
            foreach (array_values(array_intersect($beforeScopeKeys, $afterScopeKeys)) as $scopeKey) {
                $beforeScope = data_get($beforeModule, "scopes.{$scopeKey}", []);
                $afterScope = data_get($afterModule, "scopes.{$scopeKey}", []);

                if ($this->stableJson($beforeScope) === $this->stableJson($afterScope)) {
                    continue;
                }

                $scopesChanged[] = [
                    'key' => $scopeKey,
                    'before' => $beforeScope,
                    'after' => $afterScope,
                ];
            }

            $abilitiesChanged = [];
            foreach (array_values(array_intersect($beforeAbilityKeys, $afterAbilityKeys)) as $abilityKey) {
                $beforeAbility = data_get($beforeModule, "abilities.{$abilityKey}", []);
                $afterAbility = data_get($afterModule, "abilities.{$abilityKey}", []);

                if ($this->stableJson($beforeAbility) === $this->stableJson($afterAbility)) {
                    continue;
                }

                $abilitiesChanged[] = [
                    'key' => $abilityKey,
                    'before' => $beforeAbility,
                    'after' => $afterAbility,
                ];
            }

            $changedModules[] = [
                'module' => $moduleKey,
                'scopes_added' => array_values(array_diff($afterScopeKeys, $beforeScopeKeys)),
                'scopes_removed' => array_values(array_diff($beforeScopeKeys, $afterScopeKeys)),
                'scopes_changed' => $scopesChanged,
                'abilities_added' => array_values(array_diff($afterAbilityKeys, $beforeAbilityKeys)),
                'abilities_removed' => array_values(array_diff($beforeAbilityKeys, $afterAbilityKeys)),
                'abilities_changed' => $abilitiesChanged,
                'structure_changed' => true,
            ];
        }

        return [
            'total_modules_before' => count($beforeKeys),
            'total_modules_after' => count($afterKeys),
            'added_modules' => $addedModules,
            'removed_modules' => $removedModules,
            'changed_modules' => $changedModules,
            'touched_modules' => array_values(array_unique(array_merge(
                $addedModules,
                $removedModules,
                array_map(fn ($item) => (string) ($item['module'] ?? ''), $changedModules)
            ))),
        ];
    }

    protected function stableJson(mixed $value): string
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                return '[' . implode(',', array_map(fn ($item) => $this->stableJson($item), $value)) . ']';
            }

            $keys = array_keys($value);
            sort($keys);

            return '{' . implode(',', array_map(
                fn ($key) => json_encode((string) $key) . ':' . $this->stableJson($value[$key]),
                $keys
            )) . '}';
        }

        return json_encode($value);
    }
}
