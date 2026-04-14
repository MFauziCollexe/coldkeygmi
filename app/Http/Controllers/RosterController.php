<?php

namespace App\Http\Controllers;

use App\Models\RosterEntry;
use App\Models\RosterUploadBatch;
use App\Models\Department;
use App\Support\AccessRuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Log;

class RosterController extends Controller
{
    private const ACCESS_MODULE = 'roster';

    protected function accessRules(): AccessRuleService
    {
        return app(AccessRuleService::class);
    }

    public function index()
    {
        return redirect()->route('roster.upload.index');
    }

    public function uploadPage()
    {
        return Inertia::render('GMIHR/Roster/Upload');
    }

    public function listPage()
    {
        $user = Auth::user()->loadMissing(['department', 'position']);
        $departmentIds = $this->visibleDepartmentIdsForList($user);
        $canViewAllDepartments = $this->canViewAllRosterDepartments($user);
        $canApproveAllDepartments = $this->canApproveAllRosterDepartments($user);
        $canApproveAnyBatch = $this->canApproveAnyRosterBatch($user);
        $latestPendingBatchIdsByDepartment = $this->latestPendingBatchIdsByDepartment();

        $batches = RosterUploadBatch::query()
            ->with([
                'department:id,name,code',
                'uploader:id,name',
                'approver:id,name',
                'revisionOf:id,version',
            ])
            ->when(!empty($departmentIds), function ($query) use ($departmentIds) {
                $query->whereIn('department_id', $departmentIds);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->when(!$canApproveAnyBatch && !$canApproveAllDepartments, function ($query) use ($user) {
                $query->where(function ($nested) use ($user) {
                    $nested
                        ->where('status', 'approved')
                        ->orWhere('uploaded_by', $user->id);
                });
            })
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->paginate(8, [
                'id',
                'month',
                'year',
                'version',
                'is_current',
                'filename',
                'delimiter',
                'department_id',
                'uploaded_by',
                'total_rows',
                'saved_rows',
                'status',
                'approved_by',
                'approved_at',
                'revision_of_batch_id',
                'change_reason',
                'reject_reason',
                'created_at',
            ])
            ->withQueryString()
            ->through(function ($batch) use ($user, $latestPendingBatchIdsByDepartment) {
                $isLatestPendingForDepartment = $this->isLatestPendingBatchForDepartment($batch, $latestPendingBatchIdsByDepartment);
                $batch->is_latest_pending_for_department = $isLatestPendingForDepartment;
                $batch->approval_locked_reason = $this->approvalLockedReason($batch, $isLatestPendingForDepartment);
                $batch->can_approve = $this->canApproveBatch($user, $batch) && $isLatestPendingForDepartment;
                return $batch;
            });

        return Inertia::render('GMIHR/Roster/List', [
            'batches' => $batches,
            'canApprove' => $canApproveAnyBatch || $canApproveAllDepartments,
            'departmentName' => $canViewAllDepartments ? 'Semua Departemen' : optional($user->department)->name,
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'template_type' => 'nullable|string|in:inventory,risk_control,admin_loket,maintanance,maintenance',
        ]);

        $month = (int) $request->input('month');
        $year = (int) $request->input('year');
        $file = $request->file('file');
        $templateType = $this->normalizeTemplateType((string) $request->input('template_type', 'inventory'));
        $detectedTemplateType = $this->detectTemplateTypeFromFilename((string) $file->getClientOriginalName());
        if ($detectedTemplateType !== null) {
            $templateType = $detectedTemplateType;
        }

        $user = Auth::user();
        $targetDepartmentId = $this->resolveDepartmentIdForTemplateType($templateType) ?: $user?->department_id;

        try {
            $result = $this->parseRosterFile(
                $file->getRealPath(),
                $file->getClientOriginalName(),
                $month,
                $year,
                $targetDepartmentId
            );

            if (empty($result['preview_rows'])) {
                return response()->json([
                    'message' => 'Tidak ada data roster yang terbaca dari file.',
                ], 422);
            }

            $previewKey = 'roster_preview_' . Str::uuid();
            $originalFilename = basename((string) $file->getClientOriginalName());
            $tempUploadedFilePath = "roster_previews/files/{$previewKey}_" . Str::random(8) . "_{$originalFilename}";
            Storage::disk('local')->putFileAs('roster_previews/files', $file, basename($tempUploadedFilePath));

            $previewPayload = [
                'month' => (int) ($result['effective_month'] ?? $month),
                'year' => (int) ($result['effective_year'] ?? $year),
                'template_type' => $templateType,
                'target_department_id' => $targetDepartmentId,
                'delimiter' => $result['delimiter'],
                'filename' => $originalFilename,
                'source_file_path_temp' => $tempUploadedFilePath,
                'preview_rows' => $result['preview_rows'],
                'valid_rows' => $result['valid_rows'],
                'generated_at' => now()->toIso8601String(),
            ];
            $json = json_encode($previewPayload, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new \RuntimeException('Gagal encode preview payload ke JSON.');
            }
            Storage::disk('local')->put("roster_previews/{$previewKey}.json", $json);

            return response()->json([
                'preview_key' => $previewKey,
                'month' => (int) ($result['effective_month'] ?? $month),
                'year' => (int) ($result['effective_year'] ?? $year),
                'delimiter' => $result['delimiter'],
                'summary' => [
                    'total_preview_rows' => count($result['preview_rows']),
                    'valid_rows' => count($result['valid_rows']),
                    'invalid_rows' => count($result['preview_rows']) - count($result['valid_rows']),
                ],
                'rows' => $result['preview_rows'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Roster preview failed', [
                'error' => $e->getMessage(),
                'filename' => (string) $file->getClientOriginalName(),
                'user_id' => optional($user)->id,
            ]);

            $msg = strtolower($e->getMessage());
            $isZipError = str_contains($msg, 'ziparchive') || str_contains($msg, 'zip extension');
            $message = $isZipError
                ? 'Preview Excel gagal: ekstensi PHP ZIP belum aktif di server. Aktifkan php_zip atau upload file CSV.'
                : 'Preview roster gagal diproses. Silakan coba lagi atau upload file CSV.';

            return response()->json([
                'message' => $message,
            ], 422);
        }
    }

    public function upload(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'preview_key' => 'required|string',
            'template_type' => 'nullable|string|in:inventory,risk_control,admin_loket,maintanance,maintenance',
            'change_reason' => 'nullable|string|max:1000',
            'edited_rows' => 'nullable|array',
            'edited_rows.*.employee_key' => 'required_with:edited_rows|string|max:120',
            'edited_rows.*.employee_nrp' => 'nullable|string|max:100',
            'edited_rows.*.employee_name' => 'required_with:edited_rows|string|max:255',
            'edited_rows.*.roster_date' => 'required_with:edited_rows|date',
            'edited_rows.*.shift_code' => 'required_with:edited_rows|string|max:20',
        ]);

        $previewKey = $request->input('preview_key');
        $previewPath = "roster_previews/{$previewKey}.json";

        if (!Storage::disk('local')->exists($previewPath)) {
            return response()->json([
                'message' => 'Upload ditolak. Klik preview dulu sebelum upload.',
            ], 422);
        }
        $previewData = json_decode(Storage::disk('local')->get($previewPath), true) ?? [];

        $month = (int) $request->input('month');
        $year = (int) $request->input('year');

        if ((int) $previewData['month'] !== $month || (int) $previewData['year'] !== $year) {
            return response()->json([
                'message' => 'Bulan/tahun tidak sesuai dengan hasil preview. Silakan preview ulang.',
            ], 422);
        }

        $templateType = $this->normalizeTemplateType((string) $request->input('template_type', (string) ($previewData['template_type'] ?? 'inventory')));
        $targetDepartmentId = (int) ($previewData['target_department_id'] ?? 0);
        if ($targetDepartmentId <= 0) {
            $targetDepartmentId = (int) ($this->resolveDepartmentIdForTemplateType($templateType) ?? 0);
        }

        $sourceRows = $request->input('edited_rows');
        if (empty($sourceRows)) {
            $sourceRows = $previewData['preview_rows'] ?? [];
        }

        $user = Auth::user()->loadMissing(['department', 'position']);
        if (!$user || !$user->department_id) {
            return response()->json([
                'message' => 'User tidak memiliki departemen. Upload roster ditolak.',
            ], 422);
        }

        $validRows = [];
        foreach ($sourceRows as $row) {
            $normalized = $this->normalizeEditableRow($row, $month, $year, $targetDepartmentId ?: $user->department_id);
            if ($normalized !== null && $normalized['is_valid']) {
                $validRows[] = $normalized;
            }
        }

        if (empty($validRows)) {
            return response()->json([
                'message' => 'Tidak ada baris valid untuk disimpan.',
            ], 422);
        }

        $changeReason = trim((string) $request->input('change_reason', ''));
        $currentApprovedBatch = RosterUploadBatch::query()
            ->where('department_id', $targetDepartmentId ?: $user->department_id)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', 'approved')
            ->where('is_current', true)
            ->orderByDesc('id')
            ->first();

        if ($currentApprovedBatch && $changeReason === '') {
            return response()->json([
                'message' => "Roster periode ini sudah final (v{$currentApprovedBatch->version}). Isi alasan perubahan untuk membuat revisi.",
            ], 422);
        }

        $revisionOfId = $currentApprovedBatch?->id;
        $version = $currentApprovedBatch ? ((int) $currentApprovedBatch->version + 1) : 1;

        $batch = DB::transaction(function () use ($previewData, $validRows, $month, $year, $user, $revisionOfId, $version, $changeReason, $targetDepartmentId) {
            $batch = RosterUploadBatch::create([
                'revision_of_batch_id' => $revisionOfId,
                'month' => $month,
                'year' => $year,
                'version' => $version,
                'filename' => $previewData['filename'] ?? 'roster.csv',
                'source_file_path' => null,
                'delimiter' => substr((string) ($previewData['delimiter'] ?? ';'), 0, 1),
                'uploaded_by' => $user->id,
                'department_id' => $targetDepartmentId ?: $user->department_id,
                'total_rows' => count($validRows),
                'saved_rows' => 0,
                'status' => 'pending',
                'is_current' => false,
                'change_reason' => $changeReason !== '' ? $changeReason : null,
            ]);

            $draftPath = "roster_batches/batch_{$batch->id}.json";
            Storage::disk('local')->put($draftPath, json_encode([
                'month' => $month,
                'year' => $year,
                'rows' => $validRows,
                'uploaded_by' => $user->id,
                'uploaded_at' => now()->toIso8601String(),
            ]));

            $batch->update([
                'draft_payload_path' => $draftPath,
            ]);

            return $batch;
        });

        $tempUploadedFilePath = (string) ($previewData['source_file_path_temp'] ?? '');
        if ($tempUploadedFilePath !== '' && Storage::disk('local')->exists($tempUploadedFilePath)) {
            $originalFilename = basename((string) ($previewData['filename'] ?? 'roster.csv'));
            $finalPath = "roster_upload_files/{$batch->id}_" . Str::random(8) . "_{$originalFilename}";

            // Copy first to keep original binary intact, then remove temp file.
            $copied = Storage::disk('local')->copy($tempUploadedFilePath, $finalPath);
            if ($copied) {
                $batch->update(['source_file_path' => $finalPath]);
                Storage::disk('local')->delete($tempUploadedFilePath);
            }
        }

        Storage::disk('local')->delete($previewPath);

        return response()->json([
            'message' => "Upload berhasil dikirim sebagai v{$batch->version}. {$batch->total_rows} baris menunggu approval manager.",
            'batch_id' => $batch->id,
            'status' => $batch->status,
        ]);
    }

    public function approve(Request $request, RosterUploadBatch $batch)
    {
        $user = Auth::user()->loadMissing(['department', 'position']);
        if (!$this->canApproveBatch($user, $batch)) {
            return response()->json([
                'message' => 'Anda tidak berhak approve roster ini.',
            ], 403);
        }

        if (!$this->isLatestPendingBatchForDepartment($batch)) {
            return response()->json([
                'message' => 'Roster ini tidak bisa di-approve karena sudah ada upload roster yang lebih baru pada departemen yang sama.',
            ], 422);
        }

        if ($batch->status !== 'pending') {
            return response()->json([
                'message' => 'Roster ini sudah diproses sebelumnya.',
            ], 422);
        }

        $draftPath = $batch->draft_payload_path;
        if (!$draftPath || !Storage::disk('local')->exists($draftPath)) {
            return response()->json([
                'message' => 'Draft roster tidak ditemukan. Upload ulang diperlukan.',
            ], 422);
        }

        $draftData = json_decode(Storage::disk('local')->get($draftPath), true) ?? [];
        $rows = $draftData['rows'] ?? [];
        if (empty($rows)) {
            return response()->json([
                'message' => 'Draft roster kosong. Upload ulang diperlukan.',
            ], 422);
        }

        $saved = 0;
        DB::transaction(function () use (&$saved, $rows, $batch, $user) {
            foreach ($rows as $row) {
                RosterEntry::updateOrCreate(
                    [
                        'roster_date' => $row['roster_date'],
                        'employee_key' => $row['employee_key'],
                    ],
                    [
                        'batch_id' => $batch->id,
                        'month' => $batch->month,
                        'year' => $batch->year,
                        'day_name' => $row['day_name'] ?? null,
                        'employee_nrp' => $row['employee_nrp'] ?? null,
                        'employee_name' => $row['employee_name'],
                        'shift_code' => $row['shift_code'],
                        'is_off' => (bool) ($row['is_off'] ?? false),
                        'start_time' => $row['start_time'] ?? null,
                        'end_time' => $row['end_time'] ?? null,
                        'work_hours' => (float) ($row['work_hours'] ?? 0),
                        'created_by' => $batch->uploaded_by,
                    ]
                );
                $saved++;
            }

            RosterUploadBatch::query()
                ->where('department_id', $batch->department_id)
                ->where('year', $batch->year)
                ->where('month', $batch->month)
                ->where('status', 'approved')
                ->update(['is_current' => false]);

            $batch->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'saved_rows' => $saved,
                'draft_payload_path' => null,
                'reject_reason' => null,
                'is_current' => true,
            ]);
        });

        Storage::disk('local')->delete($draftPath);

        return response()->json([
            'message' => "Roster approved. {$saved} baris berhasil disimpan.",
            'saved_rows' => $saved,
        ]);
    }

    public function reject(Request $request, RosterUploadBatch $batch)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $user = Auth::user()->loadMissing(['department', 'position']);
        if (!$this->canApproveBatch($user, $batch)) {
            return response()->json([
                'message' => 'Anda tidak berhak reject roster ini.',
            ], 403);
        }

        if (!$this->isLatestPendingBatchForDepartment($batch)) {
            return response()->json([
                'message' => 'Roster ini tidak bisa di-reject karena sudah ada upload roster yang lebih baru pada departemen yang sama.',
            ], 422);
        }

        if ($batch->status !== 'pending') {
            return response()->json([
                'message' => 'Roster ini sudah diproses sebelumnya.',
            ], 422);
        }

        $batch->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'saved_rows' => 0,
            'reject_reason' => trim($data['reason']),
            'is_current' => false,
        ]);

        return response()->json([
            'message' => 'Roster berhasil di-reject.',
        ]);
    }

    public function view(Request $request, RosterUploadBatch $batch)
    {
        $user = Auth::user()->loadMissing(['department', 'position']);
        if (!$this->canViewBatch($user, $batch)) {
            return response()->json([
                'message' => 'Anda tidak berhak melihat roster ini.',
            ], 403);
        }

        $rows = [];
        if ($batch->status === 'approved') {
            $rows = RosterEntry::query()
                ->where('batch_id', $batch->id)
                ->orderBy('employee_name')
                ->orderBy('roster_date')
                ->get([
                    'employee_key',
                    'employee_nrp',
                    'employee_name',
                    'roster_date',
                    'shift_code',
                    'is_off',
                ])
                ->map(function ($row) {
                    return [
                        'employee_key' => $row->employee_key,
                        'employee_nrp' => $row->employee_nrp,
                        'employee_name' => $row->employee_name,
                        'roster_date' => optional($row->roster_date)->format('Y-m-d'),
                        'shift_code' => strtoupper((string) $row->shift_code),
                        'is_valid' => true,
                    ];
                })
                ->values()
                ->all();
        }

        if (empty($rows)) {
            $draftPath = $batch->draft_payload_path;
            if ($draftPath && Storage::disk('local')->exists($draftPath)) {
                $draftData = json_decode(Storage::disk('local')->get($draftPath), true) ?? [];
                $rows = collect($draftData['rows'] ?? [])
                    ->map(function ($row) {
                        return [
                            'employee_key' => $row['employee_key'] ?? '',
                            'employee_nrp' => $row['employee_nrp'] ?? '',
                            'employee_name' => $row['employee_name'] ?? '',
                            'roster_date' => $row['roster_date'] ?? '',
                            'shift_code' => strtoupper((string) ($row['shift_code'] ?? '')),
                            'is_valid' => isset($row['is_valid']) ? (bool) $row['is_valid'] : true,
                        ];
                    })
                    ->filter(fn($row) => !empty($row['employee_key']) && !empty($row['roster_date']))
                    ->values()
                    ->all();
            }
        }

        return response()->json([
            'batch' => [
                'id' => $batch->id,
                'month' => $batch->month,
                'year' => $batch->year,
                'status' => $batch->status,
            ],
            'rows' => $rows,
            'summary' => [
                'total_preview_rows' => count($rows),
                'valid_rows' => count($rows),
                'invalid_rows' => 0,
            ],
        ]);
    }

    public function download(Request $request, RosterUploadBatch $batch)
    {
        $user = Auth::user()->loadMissing(['department', 'position']);
        if (!$this->canViewBatch($user, $batch)) {
            abort(403, 'Anda tidak berhak mengunduh file roster ini.');
        }

        if ($batch->source_file_path && Storage::disk('local')->exists($batch->source_file_path)) {
            $downloadName = (string) ($batch->filename ?: basename((string) $batch->source_file_path));
            return Storage::disk('local')->download($batch->source_file_path, $downloadName);
        }

        $rows = [];
        if ($batch->status === 'approved') {
            $rows = RosterEntry::query()
                ->where('batch_id', $batch->id)
                ->orderBy('employee_name')
                ->orderBy('roster_date')
                ->get([
                    'employee_nrp',
                    'employee_name',
                    'roster_date',
                    'shift_code',
                ])
                ->map(function ($row) {
                    return [
                        'employee_nrp' => (string) ($row->employee_nrp ?? ''),
                        'employee_name' => (string) ($row->employee_name ?? ''),
                        'roster_date' => optional($row->roster_date)->format('Y-m-d') ?? '',
                        'shift_code' => strtoupper((string) ($row->shift_code ?? '')),
                    ];
                })
                ->values()
                ->all();
        }

        if (empty($rows)) {
            $draftPath = $batch->draft_payload_path;
            if ($draftPath && Storage::disk('local')->exists($draftPath)) {
                $draftData = json_decode(Storage::disk('local')->get($draftPath), true) ?? [];
                $rows = collect($draftData['rows'] ?? [])
                    ->map(function ($row) {
                        return [
                            'employee_nrp' => (string) ($row['employee_nrp'] ?? ''),
                            'employee_name' => (string) ($row['employee_name'] ?? ''),
                            'roster_date' => (string) ($row['roster_date'] ?? ''),
                            'shift_code' => strtoupper((string) ($row['shift_code'] ?? '')),
                        ];
                    })
                    ->values()
                    ->all();
            }
        }

        $filename = (string) ($batch->filename ?: sprintf('roster_%04d_%02d_v%d.csv', $batch->year, $batch->month, $batch->version ?: 1));
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = 'csv';
            $filename .= '.csv';
        }

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            return $this->downloadAsSpreadsheet($rows, preg_replace('/\.(xlsx|xls)$/i', '', $filename) . '.xlsx');
        }

        return $this->downloadAsCsv($rows, $filename, (string) ($batch->delimiter ?: ';'));
    }

    public function template(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'type' => 'nullable|string|in:inventory,risk_control,admin_loket,maintanance,maintenance',
        ]);

        $month = (int) $request->input('month');
        $year = (int) $request->input('year');
        $type = strtolower((string) $request->input('type', 'inventory'));
        if ($type === 'maintenance') {
            $type = 'maintanance';
        }
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $monthLabel = strtoupper(Carbon::create($year, $month, 1)->locale('id')->translatedFormat('F'));

        $employees = $this->getFixedTemplateEmployees($type);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Roster');

        $startCol = 3; // C
        $endCol = $startCol + $daysInMonth - 1;
        $endColLetter = $this->columnLetter($endCol);

        $sheet->setCellValue('A1', 'NRP');
        $sheet->setCellValue('B1', 'Nama');
        $sheet->mergeCells("C1:{$endColLetter}1");
        $sheet->setCellValue("C1", "{$monthLabel} {$year}");

        $dayNames = ['MIN', 'SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB'];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $col = $startCol + $day - 1;
            $colLetter = $this->columnLetter($col);
            $date = Carbon::create($year, $month, $day);
            $sheet->setCellValue("{$colLetter}2", $day);
            $sheet->setCellValue("{$colLetter}3", $dayNames[$date->dayOfWeek]);
        }

        $row = 4;
        foreach ($employees as $employee) {
            $sheet->setCellValue("A{$row}", $employee['nrp']);
            $sheet->setCellValue("B{$row}", $employee['name']);
            $row++;
        }
        $lastDataRow = max(4, $row - 1);

        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(30);
        for ($col = $startCol; $col <= $endCol; $col++) {
            $sheet->getColumnDimension($this->columnLetter($col))->setWidth(4.5);
        }

        $sheet->getStyle("A1:{$endColLetter}3")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$endColLetter}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF6E05E');
        $sheet->getStyle("A2:{$endColLetter}3")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF99');
        $sheet->getStyle("A1:{$endColLetter}{$lastDataRow}")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FF000000');
        $sheet->getStyle("A1:{$endColLetter}{$lastDataRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("B4:B{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $filename = sprintf('template_roster_%s_%04d_%02d.xlsx', $type, $year, $month);
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function parseRosterFile(string $path, string $originalName, int $month, int $year, ?int $departmentId = null): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $rows = [];
        $delimiter = ';';

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            if (!class_exists(\ZipArchive::class)) {
                throw new \RuntimeException('ZipArchive extension is required to read Excel files.');
            }

            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getSheet(0);
            $rows = $sheet->toArray(null, true, true, false);
            $rows = array_map(fn($line) => array_map(fn($v) => $this->cleanCellValue($v), $line), $rows);
            $delimiter = 'excel';
        } else {
            $firstLine = (string) (fgets(fopen($path, 'r')) ?: '');
            $delimiter = $this->detectDelimiter($firstLine);
            $handle = fopen($path, 'r');
            while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rows[] = array_map(fn($v) => $this->cleanCellValue($v), $line);
            }
            fclose($handle);
        }

        if (count($rows) < 2) {
            return [
                'delimiter' => $delimiter,
                'effective_month' => $month,
                'effective_year' => $year,
                'preview_rows' => [],
                'valid_rows' => [],
            ];
        }

        $detectedPeriod = $this->detectMonthYearFromRows($rows);
        if ($detectedPeriod !== null) {
            $month = (int) $detectedPeriod['month'];
            $year = (int) $detectedPeriod['year'];
        }

        [$dayHeaderRowIndex, $dayColumnMap] = $this->detectDayColumns($rows);
        if ($dayHeaderRowIndex === null || empty($dayColumnMap)) {
            return [
                'delimiter' => $delimiter,
                'effective_month' => $month,
                'effective_year' => $year,
                'preview_rows' => [],
                'valid_rows' => [],
            ];
        }

        $nrpCol = $this->detectColumnIndex($rows, ['NRP', 'NIK', 'PIN'], 2, $dayHeaderRowIndex);
        $nameCol = $this->detectColumnIndex($rows, ['NAMA', 'NAME'], 3, $dayHeaderRowIndex);

        $dataStartRow = $dayHeaderRowIndex + 1;
        if (isset($rows[$dayHeaderRowIndex + 1])) {
            $nextRow = array_map(static fn($v) => strtoupper(trim((string) $v)), $rows[$dayHeaderRowIndex + 1]);
            $dayNameCount = 0;
            foreach ($nextRow as $value) {
                if (in_array($value, ['MIN', 'SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB', 'SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'], true)) {
                    $dayNameCount++;
                }
            }
            if ($dayNameCount >= 5) {
                $dataStartRow = $dayHeaderRowIndex + 2;
            }
        }

        $previewRows = [];
        $validRows = [];

        for ($rowIndex = $dataStartRow; $rowIndex < count($rows); $rowIndex++) {
            $row = $rows[$rowIndex];
            $employeeNrp = $this->cleanCellValue($row[$nrpCol] ?? '');
            $employeeName = $this->cleanCellValue($row[$nameCol] ?? '');

            if ($employeeNrp === '' && $employeeName === '') {
                continue;
            }

            $employeeKey = $employeeNrp !== '' ? $employeeNrp : Str::slug($employeeName);
            if ($employeeKey === '') {
                $employeeKey = 'unknown-' . $rowIndex;
            }

            foreach ($dayColumnMap as $colIndex => $day) {
                $rawCode = strtoupper($this->cleanCellValue($row[$colIndex] ?? ''));
                if ($rawCode === '') {
                    continue;
                }

                try {
                    $rosterDate = Carbon::createFromDate($year, $month, $day);
                } catch (\Exception $e) {
                    continue;
                }

                $defaultHours = $this->resolveDefaultWorkHours($rosterDate, $departmentId);
                if ($this->isMaintananceEmployee($employeeNrp, $employeeName)) {
                    $defaultHours = 8;
                }
                $dayName = $rosterDate->locale('id')->translatedFormat('D');

                $isOff = false;
                $startTime = null;
                $endTime = null;
                $workHours = 0;
                $error = null;

                if (in_array($rawCode, ['OFF', 'NONE'], true)) {
                    $isOff = true;
                } elseif (is_numeric($rawCode)) {
                    $hour = (int) $rawCode;
                    if ($hour < 0 || $hour > 23) {
                        $error = "Jam tidak valid: {$rawCode}";
                    } else {
                        $start = Carbon::createFromTime($hour, 0, 0);
                        $end = (clone $start)->addHours($defaultHours);
                        $startTime = $start->format('H:i:s');
                        $endTime = $end->format('H:i:s');
                        $workHours = $defaultHours;
                    }
                } else {
                    $error = "Kode shift tidak dikenali: {$rawCode}";
                }

                $parsed = [
                    'employee_key' => $employeeKey,
                    'employee_nrp' => $employeeNrp,
                    'employee_name' => $employeeName,
                    'roster_date' => $rosterDate->format('Y-m-d'),
                    'day_name' => $dayName,
                    'shift_code' => $rawCode,
                    'is_off' => $isOff,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'work_hours' => $workHours,
                    'is_valid' => $error === null,
                    'error' => $error,
                ];

                $previewRows[] = $parsed;
                if ($parsed['is_valid']) {
                    $validRows[] = $parsed;
                }
            }
        }

        return [
            'delimiter' => $delimiter,
            'effective_month' => $month,
            'effective_year' => $year,
            'preview_rows' => $previewRows,
            'valid_rows' => $validRows,
        ];
    }

    private function detectMonthYearFromRows(array $rows): ?array
    {
        $monthMap = [
            'JAN' => 1, 'JANUARI' => 1, 'JANUARY' => 1,
            'FEB' => 2, 'FEBRUARI' => 2, 'FEBRUARY' => 2,
            'MAR' => 3, 'MARET' => 3, 'MARCH' => 3,
            'APR' => 4, 'APRIL' => 4,
            'MEI' => 5, 'MAY' => 5,
            'JUN' => 6, 'JUNI' => 6, 'JUNE' => 6,
            'JUL' => 7, 'JULI' => 7, 'JULY' => 7,
            'AGU' => 8, 'AGUSTUS' => 8, 'AUG' => 8, 'AUGUST' => 8,
            'SEP' => 9, 'SEPT' => 9, 'SEPTEMBER' => 9,
            'OKT' => 10, 'OKTOBER' => 10, 'OCT' => 10, 'OCTOBER' => 10,
            'NOV' => 11, 'NOVEMBER' => 11,
            'DES' => 12, 'DESEMBER' => 12, 'DEC' => 12, 'DECEMBER' => 12,
        ];

        $scanRows = array_slice($rows, 0, 8);
        foreach ($scanRows as $row) {
            foreach ((array) $row as $cell) {
                $text = strtoupper(trim((string) $cell));
                if ($text === '') {
                    continue;
                }

                foreach ($monthMap as $monthToken => $monthNumber) {
                    if (strpos($text, $monthToken) === false) {
                        continue;
                    }

                    if (preg_match('/(20\d{2})/', $text, $matches)) {
                        $year = (int) $matches[1];
                        if ($year >= 2000 && $year <= 2100) {
                            return [
                                'month' => $monthNumber,
                                'year' => $year,
                            ];
                        }
                    }
                }
            }
        }

        return null;
    }

    private function detectDelimiter(string $line): string
    {
        $delimiters = [',', ';', '|'];
        $best = ';';
        $maxParts = 0;

        foreach ($delimiters as $delimiter) {
            $parts = count(str_getcsv($line, $delimiter));
            if ($parts > $maxParts) {
                $maxParts = $parts;
                $best = $delimiter;
            }
        }

        return $best;
    }

    private function detectDayColumns(array $rows): array
    {
        $bestRowIndex = null;
        $bestMap = [];

        $scanLimit = min(6, count($rows));
        for ($rowIndex = 0; $rowIndex < $scanLimit; $rowIndex++) {
            $candidate = [];
            foreach (($rows[$rowIndex] ?? []) as $index => $value) {
                $value = trim((string) $value);
                if ($value !== '' && ctype_digit($value)) {
                    $day = (int) $value;
                    if ($day >= 1 && $day <= 31) {
                        $candidate[$index] = $day;
                    }
                }
            }
            if (count($candidate) > count($bestMap)) {
                $bestMap = $candidate;
                $bestRowIndex = $rowIndex;
            }
        }

        return [$bestRowIndex, $bestMap];
    }

    private function detectColumnIndex(array $rows, array $needles, int $fallback, int $maxRow): int
    {
        $limit = min($maxRow, count($rows) - 1);
        for ($r = 0; $r <= $limit; $r++) {
            foreach (($rows[$r] ?? []) as $index => $value) {
                $normalized = strtoupper(trim((string) $value));
                if (in_array($normalized, $needles, true)) {
                    return (int) $index;
                }
            }
        }

        return $fallback;
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = (int) floor($index / 26);
        }
        return $letter;
    }

    private function normalizeEditableRow(array $row, int $month, int $year, ?int $departmentId = null): ?array
    {
        $employeeKey = $this->cleanCellValue($row['employee_key'] ?? '');
        $employeeNrp = $this->cleanCellValue($row['employee_nrp'] ?? '');
        $employeeName = $this->cleanCellValue($row['employee_name'] ?? '');
        $rawCode = strtoupper($this->cleanCellValue($row['shift_code'] ?? ''));
        $rosterDateRaw = $this->cleanCellValue($row['roster_date'] ?? '');

        if ($employeeKey === '' || $employeeName === '' || $rawCode === '' || $rosterDateRaw === '') {
            return null;
        }

        try {
            $rosterDate = Carbon::parse($rosterDateRaw);
        } catch (\Exception $e) {
            return null;
        }

        if ((int) $rosterDate->month !== $month || (int) $rosterDate->year !== $year) {
            return null;
        }

        $defaultHours = $this->resolveDefaultWorkHours($rosterDate, $departmentId);
        if ($this->isMaintananceEmployee($employeeNrp, $employeeName)) {
            $defaultHours = 8;
        }
        $dayName = $rosterDate->locale('id')->translatedFormat('D');

        $isOff = false;
        $startTime = null;
        $endTime = null;
        $workHours = 0;
        $error = null;

        if (in_array($rawCode, ['OFF', 'NONE'], true)) {
            $isOff = true;
        } elseif (is_numeric($rawCode)) {
            $hour = (int) $rawCode;
            if ($hour < 0 || $hour > 23) {
                $error = "Jam tidak valid: {$rawCode}";
            } else {
                $start = Carbon::createFromTime($hour, 0, 0);
                $end = (clone $start)->addHours($defaultHours);
                $startTime = $start->format('H:i:s');
                $endTime = $end->format('H:i:s');
                $workHours = $defaultHours;
            }
        } else {
            $error = "Kode shift tidak dikenali: {$rawCode}";
        }

        return [
            'employee_key' => $employeeKey,
            'employee_nrp' => $employeeNrp,
            'employee_name' => $employeeName,
            'roster_date' => $rosterDate->format('Y-m-d'),
            'day_name' => $dayName,
            'shift_code' => $rawCode,
            'is_off' => $isOff,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'work_hours' => $workHours,
            'is_valid' => $error === null,
            'error' => $error,
        ];
    }

    private function normalizeTemplateType(string $type): string
    {
        $type = strtolower(trim($type));
        if ($type === 'maintenance') {
            $type = 'maintanance';
        }
        if (!in_array($type, ['inventory', 'risk_control', 'admin_loket', 'maintanance'], true)) {
            return 'inventory';
        }
        return $type;
    }

    private function resolveDepartmentIdForTemplateType(string $type): ?int
    {
        $normalized = $this->normalizeTemplateType($type);
        $codeMap = [
            'inventory' => 'INV',
            'risk_control' => 'RSC',
            'admin_loket' => 'ADL',
            'maintanance' => 'MNT',
        ];
        $departmentCode = $codeMap[$normalized] ?? null;
        if (!$departmentCode) {
            return null;
        }

        $departmentId = Department::query()
            ->where('code', $departmentCode)
            ->value('id');

        return $departmentId ? (int) $departmentId : null;
    }

    private function detectTemplateTypeFromFilename(string $filename): ?string
    {
        $name = strtolower($filename);
        if (str_contains($name, 'admin_loket') || str_contains($name, 'admin-loket') || str_contains($name, 'loket')) {
            return 'admin_loket';
        }
        if (str_contains($name, 'risk_control') || str_contains($name, 'risk-control') || str_contains($name, 'risk')) {
            return 'risk_control';
        }
        if (str_contains($name, 'maintanance') || str_contains($name, 'maintenance') || str_contains($name, 'mnt')) {
            return 'maintanance';
        }
        if (str_contains($name, 'inventory') || str_contains($name, 'inv')) {
            return 'inventory';
        }

        return null;
    }

    private function getFixedTemplateEmployees(string $type = 'inventory'): array
    {
        if ($type === 'inventory') {
            return [
                ['nrp' => '25081507', 'name' => 'IMANDA ARIESANDY'],
                ['nrp' => '25091508', 'name' => 'ISNINDAR UMAR SAID'],
                ['nrp' => '26010536', 'name' => 'JOJOK SETIYADI'],
                ['nrp' => '25110418', 'name' => 'GROMY ANGGRIAWAN BUDHY PRABOWO'],
                ['nrp' => '25111724', 'name' => 'EKO PURNIAWAN'],
                ['nrp' => '25111725', 'name' => 'RAFI EKA PRASTIAWAN'],
                ['nrp' => '25111727', 'name' => 'FEBRIHAN BAGUS PERMANA'],
                ['nrp' => '25111728', 'name' => 'DIMAS SEPTIAN D'],
                ['nrp' => '26011538', 'name' => 'RANGGA SURYADIPTA LINTANG KUSUMA'],
                ['nrp' => '26010535', 'name' => 'ADITYA RAINDY ANSHAR'],
                ['nrp' => '26011539', 'name' => 'RIO SEPTIANTO FANDY PRATAMA'],
                ['nrp' => '26011537', 'name' => 'EDI ATMADJA'],
                ['nrp' => '25120126', 'name' => 'ADI PUJI P'],
                ['nrp' => '25111723', 'name' => 'HUSNI ANSORY'],
                ['nrp' => '25100110', 'name' => 'CHOIRUL ANWAR'],
                ['nrp' => '25101312', 'name' => 'FIRMAN EFENDI'],
                ['nrp' => '25101313', 'name' => 'ELRIES ARIF AFIFUDIN'],
                ['nrp' => '25100109', 'name' => 'YOGA ADITYA PRADANA'],
            ];
        }

        if ($type === 'maintanance') {
            return [
                ['nrp' => '25110419', 'name' => 'Reza Sahidul A'],
                ['nrp' => '25111022', 'name' => 'Rizky Fadhlika W. D'],
                ['nrp' => '25111021', 'name' => 'Sultan Rafliansyah'],
            ];
        }

        if ($type === 'risk_control') {
            return [
                ['nrp' => '25091506', 'name' => 'Chandra Tirto Adi Ch'],
                ['nrp' => '25101314', 'name' => 'Muhammad Solihin'],
                ['nrp' => '25111732', 'name' => 'Memet Wibowo'],
                ['nrp' => '25111729', 'name' => 'Saka Anggara Putra'],
                ['nrp' => '26010533', 'name' => 'M. Hadiri'],
                ['nrp' => '26010534', 'name' => 'Tio Isman Prayogi'],
                ['nrp' => '25110317', 'name' => 'M. Dhani Rahardian'],
            ];
        }

        if ($type === 'admin_loket') {
            return [
                ['nrp' => '25111731', 'name' => 'RAHAYU ANJAS SARI'],
                ['nrp' => '25110316', 'name' => 'AINUR RAFIQ SAIFULLAH'],
                ['nrp' => '25111730', 'name' => 'SOFIA NOVA PRADANI'],
            ];
        }

        return [
            ['nrp' => '020806200', 'name' => 'Defin'],
            ['nrp' => '080414383', 'name' => 'fauzi'],
            ['nrp' => '2', 'name' => 'rahadiyanp'],
            ['nrp' => '25040101', 'name' => 'PANDU ST'],
            ['nrp' => '25071502', 'name' => 'T YOGA F'],
            ['nrp' => '25081507', 'name' => 'IMANDA ARIESAND'],
            ['nrp' => '25090304', 'name' => 'NAZUMAH A'],
            ['nrp' => '25091506', 'name' => 'CHANDRA TAC'],
            ['nrp' => '25091508', 'name' => 'I UMAR SAID'],
            ['nrp' => '25100109', 'name' => 'YOGA AP'],
            ['nrp' => '25100110', 'name' => 'CHOIRUL ANWAR'],
            ['nrp' => '25101312', 'name' => 'FIRMAN EFENDI'],
            ['nrp' => '25101313', 'name' => 'ELRIES A'],
            ['nrp' => '25101314', 'name' => 'M SOLIHIN'],
            ['nrp' => '25101615', 'name' => 'M ILHAM Z'],
            ['nrp' => '25110316', 'name' => 'AINUR RAFIQ S'],
            ['nrp' => '25110317', 'name' => 'M DHANI R'],
            ['nrp' => '25110418', 'name' => 'GROMY ABP'],
            ['nrp' => '25110419', 'name' => 'REZA SA'],
            ['nrp' => '25110420', 'name' => 'ILHAM M'],
            ['nrp' => '25111021', 'name' => 'SULTAN RAFLIAN'],
            ['nrp' => '25111022', 'name' => 'RIZKY FADLIKA'],
            ['nrp' => '25111723', 'name' => 'HUSNI A'],
            ['nrp' => '25111724', 'name' => 'EKO P'],
            ['nrp' => '25111725', 'name' => 'RAFI EKA'],
            ['nrp' => '25111727', 'name' => 'FEBRIHAN BAGUS'],
            ['nrp' => '25111728', 'name' => 'DIMAS SD'],
            ['nrp' => '25111729', 'name' => 'SAKA AP'],
            ['nrp' => '25111730', 'name' => 'SOFIA NP'],
            ['nrp' => '25111731', 'name' => 'RAHAYU AS'],
            ['nrp' => '25111732', 'name' => 'MEMET W'],
            ['nrp' => '25120126', 'name' => 'ADI PUJI'],
            ['nrp' => '25120832', 'name' => 'NATHANIA'],
            ['nrp' => '26010533', 'name' => 'M HADIRI'],
            ['nrp' => '26010534', 'name' => 'TIO ISMAN'],
            ['nrp' => '26010535', 'name' => 'ADITYA R A'],
            ['nrp' => '26010536', 'name' => 'JOJOK S'],
            ['nrp' => '26011537', 'name' => 'EDI ATMAJA'],
            ['nrp' => '26011538', 'name' => 'RANGGA SURYA'],
            ['nrp' => '26011539', 'name' => 'RIO SEPTIANTO'],
            ['nrp' => 'T2P 251117007', 'name' => 'M RAMLI'],
            ['nrp' => 'T2P241201001', 'name' => 'DAUD SETIAWAN'],
            ['nrp' => 'T2P250209004', 'name' => 'HERIYANT'],
            ['nrp' => 'T2P251001006', 'name' => 'ARI BUDI'],
            ['nrp' => 'T2P2511170005', 'name' => 'M ALI G'],
            ['nrp' => 'T2P251117003', 'name' => 'YUNANDA TB'],
            ['nrp' => 'T2P251215002', 'name' => 'THOLUT K'],
        ];
    }

    private function cleanCellValue($value): string
    {
        $text = trim((string) $value);
        if (strlen($text) >= 2 && $text[0] === '"' && $text[strlen($text) - 1] === '"') {
            $text = substr($text, 1, -1);
        }
        return trim(str_replace('""', '"', $text));
    }

    private function downloadAsCsv(array $rows, string $filename, string $delimiter = ';')
    {
        $delimiter = in_array($delimiter, [',', ';', '|', "\t"], true) ? $delimiter : ';';

        return response()->streamDownload(function () use ($rows, $delimiter) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['employee_nrp', 'employee_name', 'roster_date', 'shift_code'], $delimiter);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    (string) ($row['employee_nrp'] ?? ''),
                    (string) ($row['employee_name'] ?? ''),
                    (string) ($row['roster_date'] ?? ''),
                    (string) ($row['shift_code'] ?? ''),
                ], $delimiter);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function downloadAsSpreadsheet(array $rows, string $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Roster');
        $sheet->fromArray(['employee_nrp', 'employee_name', 'roster_date', 'shift_code'], null, 'A1');

        $rowNumber = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$rowNumber}", (string) ($row['employee_nrp'] ?? ''));
            $sheet->setCellValue("B{$rowNumber}", (string) ($row['employee_name'] ?? ''));
            $sheet->setCellValue("C{$rowNumber}", (string) ($row['roster_date'] ?? ''));
            $sheet->setCellValue("D{$rowNumber}", (string) ($row['shift_code'] ?? ''));
            $rowNumber++;
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function resolveDefaultWorkHours(Carbon $rosterDate, ?int $departmentId = null): int
    {
        if ($this->isMaintananceDepartment($departmentId)) {
            return 8;
        }

        return $rosterDate->isSaturday() ? 4 : 8;
    }

    private function isMaintananceDepartment(?int $departmentId): bool
    {
        if (!$departmentId) {
            return false;
        }

        static $maintananceDepartmentId = null;
        if ($maintananceDepartmentId === null) {
            $maintananceDepartmentId = (int) Department::query()
                ->where('code', 'MNT')
                ->value('id');
        }

        return (int) $departmentId === (int) $maintananceDepartmentId;
    }

    private function isMaintananceEmployee(string $employeeNrp, string $employeeName = ''): bool
    {
        $normalizedNrp = strtoupper(trim($employeeNrp));
        $normalizedName = strtoupper(trim($employeeName));

        static $maintananceNrps = null;
        static $maintananceNames = null;

        if ($maintananceNrps === null || $maintananceNames === null) {
            $employees = $this->getFixedTemplateEmployees('maintanance');
            $maintananceNrps = collect($employees)
                ->pluck('nrp')
                ->map(fn($nrp) => strtoupper(trim((string) $nrp)))
                ->filter()
                ->values()
                ->all();
            $maintananceNames = collect($employees)
                ->pluck('name')
                ->map(fn($name) => strtoupper(trim((string) $name)))
                ->filter()
                ->values()
                ->all();
        }

        if ($normalizedNrp !== '' && in_array($normalizedNrp, $maintananceNrps, true)) {
            return true;
        }

        return $normalizedName !== '' && in_array($normalizedName, $maintananceNames, true);
    }

    private function isDepartmentManager($user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->accessRules()->isManager($user);
    }

    private function canApproveBatch($user, RosterUploadBatch $batch): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->canApproveAllRosterDepartments($user)) {
            return true;
        }

        if (!$this->isDepartmentManager($user)) {
            return false;
        }

        return $this->accessRules()->canAccessDepartment($user, self::ACCESS_MODULE, 'approve', (int) $batch->department_id);
    }

    private function canApproveAnyRosterBatch($user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->canApproveAllRosterDepartments($user)) {
            return true;
        }

        if (!$this->isDepartmentManager($user)) {
            return false;
        }

        return !empty($this->accessRules()->visibleDepartmentIds($user, self::ACCESS_MODULE, 'approve'));
    }

    private function canApproveAllRosterDepartments($user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->accessRules()->canViewAllDepartments($user, self::ACCESS_MODULE, 'approve');
    }

    private function visibleDepartmentIdsForList($user): array
    {
        if (!$user) {
            return [];
        }

        return $this->accessRules()->visibleDepartmentIds($user, self::ACCESS_MODULE, 'view_list');
    }

    private function canViewBatch($user, RosterUploadBatch $batch): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->canViewAllRosterDepartments($user)) {
            return true;
        }

        $visibleDepartmentIds = $this->visibleDepartmentIdsForList($user);
        if (!in_array((int) $batch->department_id, $visibleDepartmentIds, true)) {
            return false;
        }

        if ($this->canApproveBatch($user, $batch)) {
            return true;
        }

        if ($batch->status === 'approved') {
            return true;
        }

        return (int) $batch->uploaded_by === (int) $user->id;
    }

    private function canViewAllRosterDepartments($user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->accessRules()->canViewAllDepartments($user, self::ACCESS_MODULE, 'view_list');
    }

    private function latestPendingBatchIdsByDepartment(): array
    {
        return RosterUploadBatch::query()
            ->where('status', 'pending')
            ->selectRaw('department_id, MAX(id) as latest_id')
            ->groupBy('department_id')
            ->pluck('latest_id', 'department_id')
            ->mapWithKeys(fn($batchId, $departmentId) => [(int) $departmentId => (int) $batchId])
            ->all();
    }

    private function isLatestPendingBatchForDepartment(RosterUploadBatch $batch, ?array $latestPendingBatchIdsByDepartment = null): bool
    {
        if ($batch->status !== 'pending') {
            return true;
        }

        $departmentId = (int) $batch->department_id;
        if ($departmentId <= 0) {
            return true;
        }

        $latestPendingBatchIdsByDepartment ??= $this->latestPendingBatchIdsByDepartment();
        $latestBatchId = (int) ($latestPendingBatchIdsByDepartment[$departmentId] ?? 0);

        return $latestBatchId > 0 && $latestBatchId === (int) $batch->id;
    }

    private function approvalLockedReason(RosterUploadBatch $batch, bool $isLatestPendingForDepartment): ?string
    {
        if ($batch->status !== 'pending' || $isLatestPendingForDepartment) {
            return null;
        }

        return 'Approval dinonaktifkan karena sudah ada upload roster yang lebih baru untuk departemen ini.';
    }
}
