<?php

namespace App\Http\Controllers;

use App\Models\AttendanceHoliday;
use App\Models\ChecklistHeader;
use App\Models\ChecklistState;
use App\Models\ChecklistTemplate;
use App\Models\Employee;
use App\Models\LeavePermission;
use App\Support\AccessRuleService;
use Dompdf\Dompdf;
use Dompdf\Options;
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

    protected function accessRules(): AccessRuleService
    {
        return app(AccessRuleService::class);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $selectedTemplate = $request->string('template')->toString();
        $selectedDate = $request->string('date')->toString();

        if ($selectedDate === '') {
            $selectedDate = now()->format('Y-m-d');
        }

        return Inertia::render('GMIIC/Checklist/Index', [
            'checklistAbilities' => $this->resolveChecklistAbilities($request),
            'checklistSettings' => $this->resolveChecklistSettings(),
            'entries' => $this->getChecklistEntries($user, 15, $selectedTemplate, $selectedDate),
            'checklistTemplatePermissions' => $this->getChecklistTemplatePermissions($user),
            'selectedChecklist' => $selectedTemplate,
            'selectedDate' => $selectedDate,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $checklistTemplatePermissions = $this->getChecklistTemplatePermissions($user);
        $selectedTemplate = $request->string('template')->toString();
        if ($selectedTemplate !== '' && !$this->canAccessChecklistTemplate($user, $selectedTemplate, 'view')) {
            abort(403, 'Template checklist ini berada di luar akses Anda.');
        }

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
            'selectedTemplate' => $selectedTemplate,
            'entryId' => $entryId,
            'savedEntry' => $entryId !== '' ? $this->findChecklistEntry($entryId, $user, true) : null,
            'existingEntries' => $this->getChecklistEntries($user),
            'checklistTemplatePermissions' => $checklistTemplatePermissions,
            'checklistAbilities' => $this->resolveChecklistAbilities($request),
            'checklistSettings' => $this->resolveChecklistSettings(),
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
            'approval_action' => ['sometimes', 'boolean'],
        ]);

        $entry = $this->normalizeEntryPayload($payload['entry']);
        $existingEntry = $this->findChecklistEntry($entry['id'], $request->user(), false);
        $requiresWorkflowAuthorization = $this->requiresWorkflowAuthorization($existingEntry, $entry);
        $this->authorizeChecklistTemplate($request->user(), $entry['template_id'], 'view');

        if ((bool) ($payload['approval_action'] ?? false) || $requiresWorkflowAuthorization) {
            $this->authorizeChecklistTemplate($request->user(), $entry['template_id'], 'approve');
        }

        if ($requiresWorkflowAuthorization) {
            $this->ensureQrRequirementSatisfied($entry);
        }

        $savedEntry = DB::transaction(fn () => $this->persistEntry($entry, $request));

        return response()->json([
            'message' => 'Checklist berhasil disimpan.',
            'entry' => $savedEntry,
        ]);
    }

    public function updateQrBypass(Request $request): JsonResponse
    {
        $accessRules = $this->accessRules();
        if (!$accessRules->allows($request->user(), 'gmiic_checklist', 'qr_bypass_manage')) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah QR bypass.');
        }

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $beforeModules = $accessRules->modules();
        $beforeOverrides = $accessRules->overrideModules();
        $afterOverrides = $beforeOverrides;
        data_set($afterOverrides, 'gmiic_checklist.settings.qr_bypass_enabled', (bool) $data['enabled']);

        $accessRules->saveOverrides($afterOverrides);
        $accessRules->logAudit(
            $request->user(),
            'save',
            $beforeModules,
            $accessRules->modules(),
            $beforeOverrides,
            $accessRules->overrideModules()
        );

        return response()->json([
            'message' => 'Status QR bypass berhasil diperbarui.',
            'settings' => $this->resolveChecklistSettings(),
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
                ->map(function ($entry) use ($request) {
                    $normalizedEntry = $this->normalizeEntryPayload($entry);
                    $this->authorizeChecklistTemplate($request->user(), $normalizedEntry['template_id'], 'view');

                    return $this->persistEntry($normalizedEntry, $request);
                })
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
        $accessRules = $this->accessRules();
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

    public function downloadPdf(Request $request, string $entryCode)
    {
        $user = $request->user();
        $entry = $this->findChecklistEntry($entryCode, $user, true);

        if (!$entry) {
            abort(404, 'Checklist tidak ditemukan.');
        }

        $templateId = (string) ($entry['template_id'] ?? '');
        $templateName = (string) ($entry['name'] ?? 'checklist');
        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $templateName) ?: 'checklist';
        $downloadName = $safeName . '_' . $entryCode . '.pdf';

        try {
            $html = view('pdf.checklist', [
                'entry' => $entry,
            ])->render();

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('tempDir', $this->ensureDompdfWorkDir('temp'));
            $options->set('fontDir', $this->ensureDompdfWorkDir('fonts'));
            $options->set('fontCache', $this->ensureDompdfWorkDir('fonts'));

            $dompdf = new Dompdf($options);
            $dompdf->setPaper('A4', $this->resolvePdfOrientation($entry));
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        } catch (\Throwable $e) {
            abort(500, 'Gagal menghasilkan PDF: ' . $e->getMessage());
        }
    }

    private function resolvePdfOrientation(array $entry): string
    {
        $landscapeTemplates = [
            'kompresor_harian', 'charger_baterai', 'checklist_baterai',
            'non_warehouse_sanitation', 'personal_hygiene_karyawan',
            'sarana_dan_prasarana',
        ];
        $tid = (string) ($entry['template_id'] ?? '');
        return in_array($tid, $landscapeTemplates, true) ? 'landscape' : 'portrait';
    }

    private function ensureDompdfWorkDir(string $segment): string
    {
        $path = storage_path('app/dompdf/' . $segment);
        if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
            throw new \RuntimeException('Failed to create Dompdf directory: ' . $path);
        }
        return $path;
    }

    private function resolveChecklistAbilities(Request $request): array
    {
        $accessRules = $this->accessRules();
        $user = $request->user();

        return [
            'delete_entries' => $accessRules->allows($user, 'gmiic_checklist', 'delete_entries'),
            'qr_bypass_manage' => $accessRules->allows($user, 'gmiic_checklist', 'qr_bypass_manage'),
            'kotak_p3k_hse_approve' => $accessRules->allows($user, 'gmiic_checklist', 'kotak_p3k_hse_approve'),
            'warehouse_final_approve' => $accessRules->allows($user, 'gmiic_checklist', 'warehouse_final_approve'),
        ];
    }

    private function resolveChecklistSettings(): array
    {
        return [
            'qr_bypass_enabled' => $this->accessRules()->booleanSetting('gmiic_checklist', 'qr_bypass_enabled'),
        ];
    }

    private function getChecklistEntries($user = null, ?int $perPage = null, string $templateId = '', string $selectedDate = '')
    {
        $allowedTemplateIds = $this->getAllowedChecklistTemplateIds($user, 'view');

        $query = ChecklistHeader::query()
            ->with('template:id,code,module')
            ->whereHas('template', fn ($query) => $query->where('module', self::CHECKLIST_MODULE))
            ->when(!empty($allowedTemplateIds), fn ($query) => $query->whereHas('template', fn ($templateQuery) => $templateQuery->whereIn('code', $allowedTemplateIds)), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($templateId !== '', fn ($query) => $query->whereHas('template', fn ($templateQuery) => $templateQuery->where('code', $templateId)))
            ->when($selectedDate !== '', function ($query) use ($selectedDate) {
                $formattedDisplayDate = $this->formatChecklistDisplayDateForQuery($selectedDate);
                $periodValue = substr($selectedDate, 0, 7);

                $query->where(function ($subQuery) use ($selectedDate, $formattedDisplayDate, $periodValue) {
                    $subQuery->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload_summary_json, '$.form.date_value')) = ?", [$selectedDate])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload_summary_json, '$.form.period')) = ?", [$periodValue]);

                    if ($formattedDisplayDate !== null) {
                        $subQuery->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload_summary_json, '$.form.date')) = ?", [$formattedDisplayDate]);
                    }
                });
            })
            ->orderByDesc('updated_at');

        if ($perPage !== null) {
            $paginator = $query->paginate($perPage)->withQueryString();

            $paginator->setCollection(
                $paginator->getCollection()
                    ->map(fn (ChecklistHeader $header) => $this->extractEntryFromHeader($header))
                    ->filter(fn ($entry) => is_array($entry) && !empty($entry))
                    ->values()
            );

            return $paginator;
        }

        return $query->get()
            ->map(fn (ChecklistHeader $header) => $this->extractEntryFromHeader($header))
            ->filter(fn ($entry) => is_array($entry) && !empty($entry))
            ->values()
            ->all();
    }

    private function formatChecklistDisplayDateForQuery(string $date): ?string
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            return null;
        }

        [$year, $month, $day] = [$matches[1], $matches[2], $matches[3]];
        $monthNames = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];

        $monthName = $monthNames[$month] ?? null;
        if (!$monthName) {
            return null;
        }

        return ltrim($day, '0') . ' ' . $monthName . ' ' . $year;
    }

    private function findChecklistEntry(string $entryCode, $user = null, bool $enforceScope = false): ?array
    {
        $baseQuery = ChecklistHeader::query()
            ->with('template:id,code,module')
            ->where('entry_code', $entryCode)
            ->whereHas('template', fn ($query) => $query->where('module', self::CHECKLIST_MODULE));

        $header = $baseQuery->first();

        if (!$header) {
            return null;
        }

        if ($enforceScope && !$this->canAccessChecklistTemplate($user, (string) $header->template?->code, 'view')) {
            abort(403, 'Checklist ini berada di luar akses template departement Anda.');
        }

        $entry = $this->extractEntryFromHeader($header);

        return is_array($entry) ? $entry : null;
    }

    private function getChecklistTemplatePermissionConfig(): array
    {
        return (array) data_get(
            $this->accessRules()->modules(),
            'gmiic_checklist.template_permissions',
            []
        );
    }

    private function getChecklistTemplatePermissions($user): array
    {
        $permissions = [];

        foreach ($this->getChecklistTemplatePermissionConfig() as $templateId => $actions) {
            $permissions[$templateId] = [];

            foreach ((array) $actions as $action => $rules) {
                $permissions[$templateId][$action] = $this->accessRules()->allowsRules(
                    $user,
                    (array) $rules
                );
            }
        }

        return $permissions;
    }

    private function getAllowedChecklistTemplateIds($user, string $action = 'view'): array
    {
        return array_values(array_keys(array_filter(
            $this->getChecklistTemplatePermissions($user),
            fn ($permissions) => !empty($permissions[$action])
        )));
    }

    private function canAccessChecklistTemplate($user, string $templateId, string $action = 'view'): bool
    {
        $normalizedTemplateId = trim($templateId);
        if ($normalizedTemplateId === '') {
            return false;
        }

        $rules = (array) data_get(
            $this->getChecklistTemplatePermissionConfig(),
            "{$normalizedTemplateId}.{$action}",
            []
        );

        if (empty($rules)) {
            return false;
        }

        return $this->accessRules()->allowsRules($user, $rules);
    }

    private function authorizeChecklistTemplate($user, string $templateId, string $action = 'view'): void
    {
        if ($this->canAccessChecklistTemplate($user, $templateId, $action)) {
            return;
        }

        if ($action === 'approve') {
            abort(403, 'Anda tidak memiliki akses approve untuk template checklist ini.');
        }

        abort(403, 'Template checklist ini berada di luar akses Anda.');
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
        $entry['created_at'] = $header->created_at?->format('H.i');
        $entry['approved_at'] = $header->approved_at?->format('H.i');

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

        if (
            in_array($entry['template_id'], ['kompresor_harian', 'charger_baterai', 'checklist_baterai'], true)
            && is_array(data_get($entry, 'form.approved_days'))
            && count(data_get($entry, 'form.approved_days')) > 0
        ) {
            return 'approved';
        }

        if (
            $entry['template_id'] === 'non_warehouse_sanitation'
            && is_array(data_get($entry, 'form.submitted_days'))
            && count(data_get($entry, 'form.submitted_days')) > 0
        ) {
            return 'waiting_hse';
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

    private function requiresWorkflowAuthorization(?array $existingEntry, array $nextEntry): bool
    {
        $templateId = (string) ($nextEntry['template_id'] ?? '');
        $currentForm = is_array($existingEntry['form'] ?? null) ? $existingEntry['form'] : [];
        $nextForm = is_array($nextEntry['form'] ?? null) ? $nextEntry['form'] : [];

        return match ($templateId) {
            'kotak_p3k' => $this->hasListExpanded($currentForm['submitted_months'] ?? [], $nextForm['submitted_months'] ?? [])
                || $this->hasListExpanded($currentForm['approved_months'] ?? [], $nextForm['approved_months'] ?? []),
            'non_warehouse_sanitation' => $this->hasListExpanded($currentForm['submitted_days'] ?? [], $nextForm['submitted_days'] ?? [])
                || $this->hasListExpanded($currentForm['approved_days'] ?? [], $nextForm['approved_days'] ?? []),
            'apar_smoke_detector_fire_alarm' => $this->hasListExpanded($currentForm['approved_months'] ?? [], $nextForm['approved_months'] ?? []),
            'pengangkutan_sampah_pt_sier' => $this->hasListExpanded($currentForm['approved_days'] ?? [], $nextForm['approved_days'] ?? []),
            'warehouse_sanitation_1' => !$this->isFilled($currentForm['verification']['prepared_date'] ?? null) && $this->isFilled($nextForm['verification']['prepared_date'] ?? null)
                || !$this->isFilled($currentForm['verification']['verified_date'] ?? null) && $this->isFilled($nextForm['verification']['verified_date'] ?? null),
            'sarana_dan_prasarana' => $this->hasMapListExpanded($currentForm['approved_days_by_area'] ?? [], $nextForm['approved_days_by_area'] ?? []),
            'patroli_security', 'site_visit_hse' => $this->hasListExpanded($currentForm['approved_areas'] ?? [], $nextForm['approved_areas'] ?? []),
            default => !$this->isTruthy($currentForm['approved'] ?? false) && $this->isTruthy($nextForm['approved'] ?? false),
        };
    }

    private function ensureQrRequirementSatisfied(array $entry): void
    {
        if ($this->resolveChecklistSettings()['qr_bypass_enabled']) {
            return;
        }

        $templateId = (string) ($entry['template_id'] ?? '');
        $form = is_array($entry['form'] ?? null) ? $entry['form'] : [];

        $hasRequiredQr = match ($templateId) {
            'kotak_p3k', 'apar_smoke_detector_fire_alarm' => $this->isFilled($this->latestListValue($form['approved_months'] ?? [], $form['monthly_barcodes'] ?? [])),
            'non_warehouse_sanitation' => $this->allExpectedSanitationAreasScanned($form),
            'warehouse_sanitation_1' => $this->isFilled($form['barcode'] ?? null),
            'sarana_dan_prasarana' => $this->hasSaranaPrasaranaScanForLatestApproval($form),
            'patroli_security', 'site_visit_hse' => $this->isFilled($this->latestListValue($form['approved_areas'] ?? [], $form['area_barcodes'] ?? [])),
            'site_visit_maintenance' => $this->hasMaintenanceQrScan($form),
            'genset_running', 'running_genset' => $this->hasGensetRunningQrScan($form),
            default => true,
        };

        if (!$hasRequiredQr) {
            abort(422, 'QRCode wajib discan sebelum approval saat QR bypass tidak aktif.');
        }
    }

    private function allExpectedSanitationAreasScanned(array $form): bool
    {
        $approvedDays = array_values(array_filter((array) ($form['submitted_days'] ?? $form['approved_days'] ?? []), fn ($value) => $value !== null && $value !== ''));
        if (empty($approvedDays)) {
            return false;
        }

        $targetDay = (string) end($approvedDays);
        $rowsByArea = is_array($form['rows_by_area'] ?? null) ? $form['rows_by_area'] : [];
        $scansByDay = is_array($form['area_scans_by_day'] ?? null) ? $form['area_scans_by_day'] : [];
        $dayScans = is_array($scansByDay[$targetDay] ?? null) ? $scansByDay[$targetDay] : [];

        $expectedAreaIds = collect($rowsByArea)
            ->filter(fn ($rows) => is_array($rows) && count($rows) > 0)
            ->keys()
            ->map(fn ($areaId) => (string) $areaId)
            ->values()
            ->all();

        if (empty($expectedAreaIds)) {
            return false;
        }

        foreach ($expectedAreaIds as $areaId) {
            if (!$this->isFilled($dayScans[$areaId]['barcode'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function hasSaranaPrasaranaScanForLatestApproval(array $form): bool
    {
        $approvedDaysByArea = is_array($form['approved_days_by_area'] ?? null) ? $form['approved_days_by_area'] : [];
        $selectedArea = (string) ($form['selected_area'] ?? '');
        $targetDay = null;

        if ($selectedArea !== '' && !empty($approvedDaysByArea[$selectedArea]) && is_array($approvedDaysByArea[$selectedArea])) {
            $targetDay = end($approvedDaysByArea[$selectedArea]);
        }

        if ($targetDay === null) {
            foreach ($approvedDaysByArea as $areaId => $days) {
                if (is_array($days) && !empty($days)) {
                    $selectedArea = (string) $areaId;
                    $targetDay = end($days);
                    break;
                }
            }
        }

        if ($selectedArea === '' || $targetDay === null) {
            return false;
        }

        return $this->isFilled($form['area_scans_by_day'][(string) $targetDay][$selectedArea]['barcode'] ?? null);
    }

    private function latestListValue(array $keys, array $valuesByKey): mixed
    {
        if (empty($keys)) {
            return null;
        }

        $latestKey = (string) end($keys);

        return $valuesByKey[$latestKey] ?? null;
    }

    private function hasMaintenanceQrScan(array $form): bool
    {
        $selectedArea = (string) ($form['selected_area'] ?? '');
        if ($selectedArea !== '' && $this->isFilled($form['area_barcodes'][$selectedArea] ?? null)) {
            return true;
        }

        return $this->isFilled($form['area_barcodes']['lantai_1_area_belakang'] ?? null);
    }

    private function hasGensetRunningQrScan(array $form): bool
    {
        $selectedArea = (string) ($form['selected_area'] ?? '');
        if ($selectedArea !== '' && $this->isFilled($form['area_barcodes'][$selectedArea] ?? null)) {
            return true;
        }

        return $this->isFilled($form['area_barcodes']['genset'] ?? null);
    }

    private function hasListExpanded(array $before, array $after): bool
    {
        return count(array_unique(array_map('strval', $after))) > count(array_unique(array_map('strval', $before)));
    }

    private function hasMapListExpanded(array $before, array $after): bool
    {
        foreach ($after as $key => $values) {
            $beforeValues = is_array($before[$key] ?? null) ? $before[$key] : [];
            $afterValues = is_array($values) ? $values : [];

            if ($this->hasListExpanded($beforeValues, $afterValues)) {
                return true;
            }
        }

        return false;
    }

    private function isFilled(mixed $value): bool
    {
        return trim((string) $value) !== '';
    }

    private function isTruthy(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
