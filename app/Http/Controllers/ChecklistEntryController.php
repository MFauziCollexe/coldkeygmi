<?php

namespace App\Http\Controllers;

use App\Models\AttendanceHoliday;
use App\Models\ChecklistHeader;
use App\Models\ChecklistState;
use App\Models\ChecklistTemplate;
use App\Models\Employee;
use App\Models\LeavePermission;
use App\Support\AccessRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ChecklistEntryController extends Controller
{
    private const CHECKLIST_MODULE = 'gmiic.checklist';

    public function index(Request $request): Response
    {
        return Inertia::render('GMIIC/Checklist/Index', [
            'checklistAbilities' => $this->resolveChecklistAbilities($request),
            'entries' => $this->getChecklistEntries(),
        ]);
    }

    public function create(Request $request): Response
    {
        $employees = Employee::query()
            ->with([
                'position:id,name,department_id',
                'position.department:id,name',
                'department:id,name',
                'user:id,position_id',
                'user.position:id,name,department_id',
                'user.position.department:id,name',
            ])
            ->when(Schema::hasColumn('employees', 'employment_status'), function ($query) {
                $query->where('employment_status', 'active');
            })
            ->orderBy('name')
            ->get();

        $employeeNikById = $employees
            ->filter(fn (Employee $employee) => !empty($employee->nik))
            ->mapWithKeys(fn (Employee $employee) => [$employee->id => $employee->nik]);

        $employeeNikByUserId = $employees
            ->filter(fn (Employee $employee) => !empty($employee->nik) && !empty($employee->user_id))
            ->mapWithKeys(fn (Employee $employee) => [$employee->user_id => $employee->nik]);

        $leaveDatesByNik = LeavePermission::query()
            ->where('status', 'approved')
            ->where('type', 'cuti')
            ->get(['employee_id', 'user_id', 'start_date', 'end_date'])
            ->reduce(function ($carry, LeavePermission $leave) use ($employeeNikById, $employeeNikByUserId) {
                $nik = $employeeNikById->get($leave->employee_id) ?: $employeeNikByUserId->get($leave->user_id);
                if (!$nik || !$leave->start_date || !$leave->end_date) {
                    return $carry;
                }

                $cursor = Carbon::parse($leave->start_date)->startOfDay();
                $end = Carbon::parse($leave->end_date)->startOfDay();

                while ($cursor->lte($end)) {
                    $carry[$nik] = $carry[$nik] ?? [];
                    $carry[$nik][] = $cursor->format('Y-m-d');
                    $cursor->addDay();
                }

                return $carry;
            }, []);

        $entryId = $request->string('entry_id')->toString();

        return Inertia::render('GMIIC/Checklist/Create', [
            'selectedTemplate' => $request->string('template')->toString(),
            'entryId' => $entryId,
            'savedEntry' => $entryId !== '' ? $this->findChecklistEntry($entryId) : null,
            'existingEntries' => $this->getChecklistEntries(),
            'checklistAbilities' => $this->resolveChecklistAbilities($request),
            'holidayDates' => AttendanceHoliday::query()
                ->orderBy('holiday_date')
                ->pluck('holiday_date')
                ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
                ->values(),
            'leaveDatesByNik' => collect($leaveDatesByNik)
                ->map(fn ($dates) => array_values(array_unique($dates)))
                ->all(),
            'employees' => $employees
                ->map(function (Employee $employee) {
                    $positionName = $employee->position?->name ?: $employee->user?->position?->name;

                    return [
                        'id' => $employee->id,
                        'nik' => $employee->nik,
                        'name' => $employee->name,
                        'gender' => $employee->gender,
                        'bagian' => $positionName,
                        'position' => $positionName,
                    ];
                })
                ->values(),
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'entry' => ['required', 'array'],
        ]);

        $entry = $this->normalizeEntryPayload($payload['entry']);
        $savedEntry = DB::transaction(fn () => $this->persistEntry($entry, $request));

        return response()->json([
            'message' => 'Checklist berhasil disimpan.',
            'entry' => $savedEntry,
        ]);
    }

    public function saveMany(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'entries' => ['required', 'array', 'min:1'],
            'entries.*' => ['required', 'array'],
        ]);

        $savedEntries = DB::transaction(function () use ($payload, $request) {
            return collect($payload['entries'])
                ->map(fn ($entry) => $this->persistEntry($this->normalizeEntryPayload($entry), $request))
                ->values()
                ->all();
        });

        return response()->json([
            'message' => 'Checklist berhasil disimpan.',
            'entries' => $savedEntries,
        ]);
    }

    public function destroyMany(Request $request): JsonResponse
    {
        $accessRules = app(AccessRuleService::class);
        if (!$accessRules->allows($request->user(), 'gmiic_checklist', 'delete_entries')) {
            abort(403);
        }

        $payload = $request->validate([
            'entry_ids' => ['required', 'array', 'min:1'],
            'entry_ids.*' => ['required', 'string'],
        ]);

        $deletedCount = ChecklistHeader::query()
            ->whereIn('entry_code', $payload['entry_ids'])
            ->delete();

        return response()->json([
            'message' => 'Checklist berhasil dihapus.',
            'deleted_count' => $deletedCount,
        ]);
    }

    private function resolveChecklistAbilities(Request $request): array
    {
        $accessRules = app(AccessRuleService::class);
        $user = $request->user();

        return [
            'delete_entries' => $accessRules->allows($user, 'gmiic_checklist', 'delete_entries'),
            'kotak_p3k_hse_approve' => $accessRules->allows($user, 'gmiic_checklist', 'kotak_p3k_hse_approve'),
            'warehouse_final_approve' => $accessRules->allows($user, 'gmiic_checklist', 'warehouse_final_approve'),
        ];
    }

    private function getChecklistEntries(): array
    {
        return ChecklistHeader::query()
            ->with('template:id,code,module')
            ->whereHas('template', fn ($query) => $query->where('module', self::CHECKLIST_MODULE))
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (ChecklistHeader $header) => $this->extractEntryFromHeader($header))
            ->filter(fn ($entry) => is_array($entry) && !empty($entry))
            ->values()
            ->all();
    }

    private function findChecklistEntry(string $entryCode): ?array
    {
        $header = ChecklistHeader::query()
            ->with('template:id,code,module')
            ->where('entry_code', $entryCode)
            ->whereHas('template', fn ($query) => $query->where('module', self::CHECKLIST_MODULE))
            ->first();

        if (!$header) {
            return null;
        }

        $entry = $this->extractEntryFromHeader($header);

        return is_array($entry) ? $entry : null;
    }

    private function extractEntryFromHeader(ChecklistHeader $header): ?array
    {
        $entry = $header->payload_summary_json;

        if (!is_array($entry) || empty($entry)) {
            $latestState = $header->states()->orderByDesc('version_no')->first();
            $entry = $latestState?->state_json;
        }

        if (!is_array($entry) || empty($entry)) {
            return null;
        }

        $entry['id'] = (string) ($entry['id'] ?? $header->entry_code);
        $entry['template_id'] = (string) ($entry['template_id'] ?? $header->template?->code ?? '');
        $entry['name'] = (string) ($entry['name'] ?? $header->title ?? $header->template?->name ?? '');
        $entry['form'] = is_array($entry['form'] ?? null) ? $entry['form'] : [];

        return $entry;
    }

    private function normalizeEntryPayload(array $entry): array
    {
        $entryId = trim((string) ($entry['id'] ?? ''));
        $templateCode = trim((string) ($entry['template_id'] ?? ''));

        if ($entryId === '' || $templateCode === '') {
            abort(422, 'Data checklist tidak valid.');
        }

        $entry['id'] = $entryId;
        $entry['template_id'] = $templateCode;
        $entry['name'] = trim((string) ($entry['name'] ?? $templateCode));
        $entry['form'] = is_array($entry['form'] ?? null) ? $entry['form'] : [];

        return $entry;
    }

    private function persistEntry(array $entry, Request $request): array
    {
        $template = ChecklistTemplate::query()->firstOrCreate(
            ['code' => $entry['template_id']],
            [
                'name' => $entry['name'],
                'module' => self::CHECKLIST_MODULE,
                'version_no' => 1,
                'is_active' => true,
            ]
        );

        $header = ChecklistHeader::query()->firstOrNew([
            'entry_code' => $entry['id'],
        ]);

        $wasApproved = (bool) $header->approved_at;
        $isApproved = (bool) data_get($entry, 'form.approved');
        $header->template_id = $template->id;
        $header->title = $entry['name'];
        $header->period_type = $this->resolvePeriodType($entry);
        $header->period_value = $this->resolvePeriodValue($entry);
        $header->area_code = $this->resolveAreaCode($entry);
        $header->location_code = $this->resolveLocationCode($entry);
        $header->status = $this->resolveHeaderStatus($entry);
        $header->current_step = $this->resolveCurrentStep($entry);
        $header->payload_summary_json = $entry;

        if (!$header->exists) {
            $header->created_by = optional($request->user())->id;
        }

        if ($isApproved && !$wasApproved) {
            $header->approved_by = optional($request->user())->id;
            $header->approved_at = now();
        } elseif (!$isApproved) {
            $header->approved_by = null;
            $header->approved_at = null;
        }

        $header->save();

        $state = $header->states()->orderByDesc('version_no')->first();
        if (!$state) {
            $state = new ChecklistState([
                'checklist_header_id' => $header->id,
                'version_no' => 1,
            ]);
        }

        $state->state_json = $entry;
        $state->saved_by = optional($request->user())->id;
        $state->saved_at = now();
        $state->save();

        return $this->extractEntryFromHeader($header->fresh());
    }

    private function resolvePeriodType(array $entry): ?string
    {
        if (trim((string) data_get($entry, 'form.period_value')) !== '') {
            return 'week';
        }

        if (trim((string) data_get($entry, 'form.period')) !== '') {
            return 'period';
        }

        if (trim((string) data_get($entry, 'form.date_value')) !== '' || trim((string) data_get($entry, 'form.date')) !== '') {
            return 'date';
        }

        return null;
    }

    private function resolvePeriodValue(array $entry): ?string
    {
        foreach (['form.period_value', 'form.period', 'form.date_value', 'form.date'] as $path) {
            $value = trim((string) data_get($entry, $path));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function resolveAreaCode(array $entry): ?string
    {
        $selectedAreas = data_get($entry, 'form.selected_areas');
        if (is_array($selectedAreas) && count($selectedAreas) > 0) {
            return implode(',', array_map('strval', $selectedAreas));
        }

        foreach (['form.selected_area', 'form.area'] as $path) {
            $value = trim((string) data_get($entry, $path));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function resolveLocationCode(array $entry): ?string
    {
        foreach (['form.location', 'form.card_type'] as $path) {
            $value = trim((string) data_get($entry, $path));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function resolveCurrentStep(array $entry): ?string
    {
        foreach (['form.selected_area', 'form.active_month', 'form.location', 'form.area'] as $path) {
            $value = trim((string) data_get($entry, $path));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function resolveHeaderStatus(array $entry): string
    {
        if ((bool) data_get($entry, 'form.approved')) {
            return 'approved';
        }

        if ($entry['template_id'] === 'kotak_p3k' && is_array(data_get($entry, 'form.submitted_months')) && count(data_get($entry, 'form.submitted_months')) > 0) {
            return 'waiting_hse';
        }

        if ($entry['template_id'] === 'warehouse_sanitation_1' && trim((string) data_get($entry, 'form.verification.prepared_date')) !== '') {
            return 'waiting_manager';
        }

        if ($entry['template_id'] === 'personal_hygiene_karyawan' && trim((string) data_get($entry, 'form.generated_at')) !== '') {
            return 'generated';
        }

        return 'draft';
    }
}
