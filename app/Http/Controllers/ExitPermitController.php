<?php

namespace App\Http\Controllers;

use App\Models\ExitPermit;
use App\Models\Position;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExitPermitController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'date' => trim((string) $request->input('date', '')),
            'status' => trim((string) $request->input('status', '')),
        ];

        $authUser = $request->user();
        $isSecurityApprover = $this->isSecurityUser($authUser);
        $isHrdApprover = $this->isHrdUser($authUser);
        $query = ExitPermit::query()
            ->with([
                'user:id,name',
                'department:id,name,code',
                'securityApprover:id,name',
                'hrdApprover:id,name',
                'managerApprover:id,name',
            ]);

        if ($filters['search'] !== '') {
            $query->where(function ($inner) use ($filters) {
                $inner->where('permit_number', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('employee_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('department_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('purpose', 'like', '%' . $filters['search'] . '%');
            });
        }

        if ($filters['date'] !== '') {
            $query->whereDate('request_date', $filters['date']);
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        $exitPermits = $query
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $exitPermits->getCollection()->transform(function (ExitPermit $permit) use ($authUser, $isSecurityApprover, $isHrdApprover) {
            $isOwner = (int) $permit->user_id === (int) optional($authUser)->id;
            $canManager = $this->isDepartmentManager($authUser, (int) $permit->department_id);

            $permit->setAttribute('can_approve_security', !$isOwner && $isSecurityApprover && $permit->status === 'pending' && $permit->security_status === 'pending');
            $permit->setAttribute('can_approve_hrd', !$isOwner && $isHrdApprover && $permit->status === 'pending' && $permit->hrd_status === 'pending');
            $permit->setAttribute('can_approve_manager', $canManager && $permit->status === 'pending' && $permit->manager_status === 'pending');

            return $permit;
        });

        return Inertia::render('GMIVP/ExitPermit/Index', [
            'exitPermits' => $exitPermits,
            'filters' => $filters,
            'authUserId' => optional($authUser)->id,
            'isSecurityApprover' => $isSecurityApprover,
            'isHrdApprover' => $isHrdApprover,
        ]);
    }

    public function create(Request $request)
    {
        $authUser = $request->user();
        $authUser?->loadMissing('department:id,name');

        return Inertia::render('GMIVP/ExitPermit/Create', [
            'authProfile' => [
                'name' => $authUser?->name,
                'department_name' => optional($authUser?->department)->name,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $authUser = $request->user();
        if (!$authUser) {
            abort(403);
        }

        $authUser->loadMissing('department:id,name');

        $validated = $request->validate([
            'request_date' => ['required', 'date'],
            'purpose' => ['required', 'string', 'max:1000'],
            'time_out' => ['required', 'date_format:H:i'],
            'time_back' => ['nullable', 'date_format:H:i'],
        ]);

        $permit = ExitPermit::create([
            'request_date' => $validated['request_date'],
            'employee_name' => (string) $authUser->name,
            'department_name' => (string) optional($authUser->department)->name,
            'purpose' => $validated['purpose'],
            'time_out' => $validated['time_out'] . ':00',
            'time_back' => !empty($validated['time_back']) ? $validated['time_back'] . ':00' : null,
            'status' => 'pending',
            'security_status' => 'pending',
            'hrd_status' => 'pending',
            'manager_status' => 'pending',
            'user_id' => $authUser->id,
            'department_id' => $authUser->department_id,
        ]);

        $permit->permit_number = 'SIK-' . now()->format('Ymd') . '-' . str_pad((string) $permit->id, 4, '0', STR_PAD_LEFT);
        $permit->save();

        return redirect()->route('gmi-visitor-permit.exit-permit.index')->with('success', 'Surat izin keluar berhasil diajukan.');
    }

    public function approve(Request $request, ExitPermit $exitPermit)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:security,hrd,manager'],
            'decision' => ['required', 'in:approved,rejected'],
        ]);

        $authUser = $request->user();
        if (!$authUser) {
            abort(403);
        }
        $isOwner = (int) $exitPermit->user_id === (int) $authUser->id;
        if ($isOwner && $validated['role'] !== 'manager') {
            abort(403, 'Pemohon hanya dapat melakukan approval pada tahap manager.');
        }

        if ($validated['role'] === 'security') {
            if (!$this->isSecurityUser($authUser)) {
                abort(403, 'Hanya user Security yang dapat approve/reject Security.');
            }
            $exitPermit->security_status = $validated['decision'];
            $exitPermit->security_approved_by = $authUser->id;
            $exitPermit->security_approved_at = now();
        }

        if ($validated['role'] === 'hrd') {
            if (!$this->isHrdUser($authUser)) {
                abort(403, 'Hanya user HRD yang dapat approve/reject HRD.');
            }
            $exitPermit->hrd_status = $validated['decision'];
            $exitPermit->hrd_approved_by = $authUser->id;
            $exitPermit->hrd_approved_at = now();
        }

        if ($validated['role'] === 'manager') {
            if (!$this->isDepartmentManager($authUser, (int) $exitPermit->department_id)) {
                abort(403, 'Hanya Manager/Supervisor departemen terkait yang dapat approve/reject.');
            }
            $exitPermit->manager_status = $validated['decision'];
            $exitPermit->manager_approved_by = $authUser->id;
            $exitPermit->manager_approved_at = now();
        }

        $exitPermit->status = $this->resolveFinalStatus($exitPermit);
        $exitPermit->save();

        return redirect()->back()->with('success', 'Approval surat izin keluar berhasil diproses.');
    }

    private function resolveFinalStatus(ExitPermit $permit): string
    {
        $statuses = [$permit->security_status, $permit->hrd_status, $permit->manager_status];

        if (in_array('rejected', $statuses, true)) {
            return 'rejected';
        }

        if ($permit->security_status === 'approved' && $permit->hrd_status === 'approved' && $permit->manager_status === 'approved') {
            return 'approved';
        }

        return 'pending';
    }

    private function isSecurityUser($user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $user->loadMissing('department:id,code');
        return strtoupper((string) optional($user->department)->code) === 'SEC';
    }

    private function isHrdUser($user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $user->loadMissing('department:id,code');
        return strtoupper((string) optional($user->department)->code) === 'HRD';
    }

    private function isDepartmentManager($user, int $departmentId): bool
    {
        if (!$user || !$departmentId) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->position_id) {
            return false;
        }

        $position = Position::query()->select('department_id', 'is_manager')->find($user->position_id);
        if (!$position || !$position->is_manager) {
            return false;
        }

        return (int) $position->department_id === $departmentId;
    }
}
