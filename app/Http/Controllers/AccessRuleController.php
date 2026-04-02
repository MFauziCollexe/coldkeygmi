<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Position;
use App\Support\AccessRuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccessRuleController extends Controller
{
    public function index(AccessRuleService $accessRules): Response
    {
        return Inertia::render('ControlPanel/AccessRules', [
            'modules' => $accessRules->modules(),
            'defaultModules' => $accessRules->defaultModules(),
            'overrideMeta' => $accessRules->overrideMeta(),
            'auditTrail' => $accessRules->auditTrail(),
            'departmentCodes' => Department::query()
                ->active()
                ->orderBy('code')
                ->get(['code', 'name'])
                ->map(fn (Department $department) => [
                    'code' => (string) $department->code,
                    'name' => (string) $department->name,
                ])
                ->values()
                ->all(),
            'positionCodes' => Position::query()
                ->active()
                ->orderBy('code')
                ->get(['code', 'name'])
                ->map(fn (Position $position) => [
                    'code' => (string) $position->code,
                    'name' => (string) $position->name,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function save(Request $request, AccessRuleService $accessRules): RedirectResponse
    {
        $beforeModules = $accessRules->modules();
        $beforeOverrides = $accessRules->overrideModules();
        $data = $request->validate([
            'modules' => ['required', 'array'],
        ]);

        $modules = collect($data['modules'])
            ->filter(fn ($config, $moduleKey) => is_string($moduleKey) && trim($moduleKey) !== '' && is_array($config))
            ->map(function (array $config) {
                $nextConfig = [];

                if (isset($config['scopes']) && is_array($config['scopes'])) {
                    $nextConfig['scopes'] = collect($config['scopes'])
                        ->filter(fn ($scopeConfig, $scopeKey) => is_string($scopeKey) && trim($scopeKey) !== '' && is_array($scopeConfig))
                        ->map(fn ($scopeConfig) => $scopeConfig)
                        ->all();
                }

                if (isset($config['abilities']) && is_array($config['abilities'])) {
                    $nextConfig['abilities'] = collect($config['abilities'])
                        ->filter(fn ($abilityConfig, $abilityKey) => is_string($abilityKey) && trim($abilityKey) !== '' && is_array($abilityConfig))
                        ->map(fn ($abilityConfig) => $abilityConfig)
                        ->all();
                }

                return $nextConfig;
            })
            ->all();

        $accessRules->saveOverrides($modules);
        $accessRules->logAudit(
            $request->user(),
            'save',
            $beforeModules,
            $accessRules->modules(),
            $beforeOverrides,
            $accessRules->overrideModules()
        );

        return redirect()->back()->with('success', 'Access rule overrides berhasil disimpan.');
    }

    public function reset(Request $request, AccessRuleService $accessRules): RedirectResponse
    {
        $beforeModules = $accessRules->modules();
        $beforeOverrides = $accessRules->overrideModules();
        $accessRules->resetOverrides();
        $accessRules->logAudit(
            $request->user(),
            'reset',
            $beforeModules,
            $accessRules->modules(),
            $beforeOverrides,
            $accessRules->overrideModules()
        );

        return redirect()->back()->with('success', 'Access rule overrides berhasil direset ke default.');
    }

    public function rollback(Request $request, AccessRuleService $accessRules): RedirectResponse
    {
        $data = $request->validate([
            'audit_id' => ['required', 'string'],
        ]);

        $beforeModules = $accessRules->modules();
        $beforeOverrides = $accessRules->overrideModules();

        if (!$accessRules->rollbackToAudit($data['audit_id'])) {
            return redirect()->back()->with('error', 'Audit trail tidak ditemukan atau belum punya snapshot rollback.');
        }

        $accessRules->logAudit(
            $request->user(),
            'rollback',
            $beforeModules,
            $accessRules->modules(),
            $beforeOverrides,
            $accessRules->overrideModules()
        );

        return redirect()->back()->with('success', 'Access rule berhasil dikembalikan ke versi audit terpilih.');
    }
}
