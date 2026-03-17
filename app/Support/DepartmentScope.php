<?php

namespace App\Support;

use App\Models\Department;

class DepartmentScope
{
    /**
     * Business rule: Operational (OPS) manager can manage additional departments.
     * This mirrors the roster approval behavior (OPS can approve INV, RSC, ADL),
     * plus HSE approvals are handled by OPS manager.
     */
    public static function expandManagedDepartmentIds(array $baseDepartmentIds): array
    {
        $baseIds = array_values(array_unique(array_filter(array_map('intval', $baseDepartmentIds))));
        if (empty($baseIds)) {
            return [];
        }

        $codes = Department::query()
            ->whereIn('id', $baseIds)
            ->pluck('code')
            ->map(fn($code) => strtoupper(trim((string) $code)))
            ->filter()
            ->values()
            ->all();

        if (!in_array('OPS', $codes, true)) {
            return $baseIds;
        }

        $extraIds = Department::query()
            ->whereIn('code', ['INV', 'RSC', 'ADL', 'HSE'])
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge($baseIds, $extraIds)));
    }
}
