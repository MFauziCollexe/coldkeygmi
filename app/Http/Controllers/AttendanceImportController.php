<?php

namespace App\Http\Controllers;

use App\Models\AttendanceImportBatch;
use App\Models\AttendanceImportEntry;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceImportController extends Controller
{
    private const LEAVE_TYPES = ['CUTI', 'IZIN', 'SAKIT', 'DINAS LUAR', 'DINAS'];

    public function index(Request $request)
    {
        $dateFrom = $this->normalizeAttendanceDateFilter($request->input('date_from'));
        $dateTo = $this->normalizeAttendanceDateFilter($request->input('date_to'));
        $monthInput = $request->input('month');
        $yearInput = $request->input('year');
        $month = ($monthInput === null || $monthInput === '' || $monthInput === 'all') ? null : (int) $monthInput;
        $year = ($yearInput === null || $yearInput === '' || $yearInput === 'all') ? null : (int) $yearInput;
        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }
        if ($year !== null && ($year < 2000 || $year > 2100)) {
            $year = null;
        }
        if ($month === null && $year === null) {
            $latestBatch = AttendanceImportBatch::query()->orderByDesc('id')->first();
            if ($latestBatch !== null) {
                $month = (int) $latestBatch->month;
                $year = (int) $latestBatch->year;
            } else {
                $month = (int) now()->month;
                $year = (int) now()->year;
            }
        }
        if ($dateFrom === null && $dateTo === null) {
            if ($monthInput !== null && $month !== null && $yearInput !== null && $year !== null) {
                $rangeStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
                $dateFrom = $rangeStart->toDateString();
                $dateTo = $rangeStart->copy()->endOfMonth()->toDateString();
            } else {
                $rangeStart = now()->startOfMonth();
                $dateFrom = $rangeStart->toDateString();
                $dateTo = $rangeStart->copy()->endOfMonth()->toDateString();
            }
        }
        $dateFrom ??= $dateTo;
        $dateTo ??= $dateFrom;
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        $statusFilter = strtolower(trim((string) $request->input('status', 'all')));
        $q = trim((string) $request->input('q', ''));
        $perPage = (int) $request->input('per_page', 50);
        if ($perPage < 5 || $perPage > 500) {
            $perPage = 50;
        }

        $entries = AttendanceImportEntry::query()
            ->whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->when($statusFilter !== '' && $statusFilter !== 'all', function ($query) use ($statusFilter) {
                $query->whereRaw('LOWER(status) = ?', [$statusFilter]);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('pin', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->orderBy('attendance_date')
            ->get();

        $rawRows = $entries->map(fn (AttendanceImportEntry $entry) => [
            'attendance_date' => $entry->attendance_date->format('Y-m-d'),
            'attendance_date_label' => $entry->attendance_date->format('d M Y'),
            'day_name' => $entry->attendance_date->locale('id')->translatedFormat('l'),
            'pin' => $entry->pin,
            'name' => $entry->name,
            'check_in' => $entry->check_in,
            'check_out' => $entry->check_out,
            'status' => $entry->status,
            'is_off' => $entry->is_off,
            'leave_type' => $entry->leave_type,
            'shift_code' => $entry->shift_code,
            'schedule_start' => $entry->schedule_start,
            'schedule_end' => $entry->schedule_end,
            'overtime_label' => $entry->overtime_label,
        ])->values();

        $employeeQuery = Employee::query()
            ->whereNotNull('nik')
            ->where('nik', '<>', '')
            ->with(['department:id,name']);
        $supervisorColumns = ['id', 'name'];
        if (Schema::hasColumn('employees', 'alias_name')) {
            $supervisorColumns[] = 'alias_name';
        }
        if (Schema::hasColumn('employees', 'reports_to')) {
            $employeeQuery->with(['supervisor:' . implode(',', $supervisorColumns)]);
        }
        $employeesByPin = $employeeQuery
            ->get(['id', 'nik', 'department_id', 'reports_to'])
            ->mapWithKeys(fn (Employee $employee) => [
                $this->normalizeAttendancePin((string) $employee->nik) => $employee,
            ]);

        $groups = $rawRows
            ->groupBy(fn (array $row) => $row['pin'] !== '' ? $row['pin'] : ('name:' . Str::slug($row['name'])))
            ->map(function ($rows, $groupKey) use ($employeesByPin) {
                $rows = collect($rows);
                $first = $rows->first();
                $employee = $employeesByPin->get($this->normalizeAttendancePin((string) ($first['pin'] ?? '')));
                $supervisor = $employee?->supervisor;

                return [
                    'key' => (string) $groupKey,
                    'pin' => $first['pin'],
                    'name' => $first['name'],
                    'total_records' => $rows->count(),
                    'department_name' => $employee?->department?->name ?? '-',
                    'supervisor_name' => ($supervisor?->alias_name ?: $supervisor?->name) ?? '-',
                    'total_late' => $rows->filter(fn (array $row) => mb_strtolower(trim((string) ($row['status'] ?? ''))) === 'terlambat')->count(),
                    'rows' => $rows->sortBy('attendance_date')->values(),
                ];
            })
            ->values()
            ->sortBy(fn (array $group) => strtolower($group['name']))
            ->values();

        $filterDefinitions = [
            'pins' => ['flag' => 'filter_pins', 'field' => 'pin'],
            'names' => ['flag' => 'filter_names', 'field' => 'name'],
            'departments' => ['flag' => 'filter_departments', 'field' => 'department_name'],
            'reporting_tos' => ['flag' => 'filter_reporting_tos', 'field' => 'supervisor_name'],
        ];
        $filterOptions = [];
        foreach ($filterDefinitions as $key => $definition) {
            $filterOptions[$key] = $groups
                ->map(function (array $group) use ($definition) {
                    $value = trim((string) ($group[$definition['field']] ?? ''));
                    return $value !== '' ? $value : '-';
                })
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        $columnFilters = [];
        foreach ($filterDefinitions as $key => $definition) {
            $selected = array_values(array_unique(array_filter(
                array_map(static fn ($value) => trim((string) $value), (array) $request->input($key, [])),
                static fn (string $value) => $value !== ''
            )));
            $active = $request->boolean($definition['flag']);
            $columnFilters[$key] = [
                'active' => $active,
                'selected' => $selected,
            ];

            if ($active) {
                $groups = $groups->filter(function (array $group) use ($definition, $selected) {
                    $value = trim((string) ($group[$definition['field']] ?? ''));
                    $value = $value !== '' ? $value : '-';
                    return in_array($value, $selected, true);
                })->values();
            }
        }

        if ((string) $request->input('export') === 'excel') {
            return $this->exportAttendanceExcel($groups, $dateFrom, $dateTo);
        }

        $summary = [
            'total_records' => $rawRows->count(),
            'total_employees' => $groups->count(),
            'status_counts' => $rawRows
                ->groupBy(fn (array $row) => $row['status'] !== '' ? $row['status'] : '(kosong)')
                ->map(fn ($rows) => $rows->count()),
        ];

        $statusOptions = $rawRows
            ->pluck('status')
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->sort()
            ->values();

        $canImportAttendance = $this->isItDepartment($request);
        $canViewImportHistory = $canImportAttendance;
        $batches = $canViewImportHistory
            ? AttendanceImportBatch::query()
                ->with('uploader')
                ->orderByDesc('id')
                ->limit(15)
                ->get()
                ->map(fn (AttendanceImportBatch $batch) => [
                    'id' => $batch->id,
                    'filename' => $batch->filename,
                    'month' => $batch->month,
                    'year' => $batch->year,
                    'total_rows' => $batch->total_rows,
                    'valid_rows' => $batch->valid_rows,
                    'saved_rows' => $batch->saved_rows,
                    'uploaded_by' => $batch->uploader?->name ?? 'System',
                    'imported_at' => $batch->created_at?->format('d M Y H:i'),
                ])
            : collect();

        [$paginatedGroups, $total] = $this->paginateGroups($groups, $perPage, $request);

        return Inertia::render('GMIHR/Attendance/Index', [
            'groups' => $paginatedGroups,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => (int) $request->input('page', 1),
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
            'summary' => $summary,
            'statusOptions' => $statusOptions,
            'filterOptions' => $filterOptions,
            'columnFilters' => $columnFilters,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'month' => $month,
                'year' => $year,
                'status' => $statusFilter,
                'q' => $q,
            ],
            'canImportAttendance' => $canImportAttendance,
            'canViewImportHistory' => $canViewImportHistory,
            'batches' => $batches,
        ]);
    }

    private function exportAttendanceExcel(Collection $groups, string $dateFrom, string $dateTo)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Attendance');

        $dates = [];
        $startDate = Carbon::parse($dateFrom)->startOfDay();
        $endDate = Carbon::parse($dateTo)->startOfDay();
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        $lastColumn = $this->columnLetter(count($dates) + 2);
        $sheet->mergeCells('A1:A2');
        $sheet->mergeCells('B1:B2');
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Nama');

        $dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        foreach ($dates as $index => $attendanceDate) {
            $column = $this->columnLetter($index + 3);
            $date = Carbon::parse($attendanceDate);
            $sheet->setCellValue("{$column}1", $date->day);
            $sheet->setCellValue("{$column}2", $dayNames[$date->dayOfWeek]);
        }

        $gridColor = '6AA84F';
        $sheet->getStyle("A1:{$lastColumn}2")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '111827']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $gridColor]],
            ],
        ]);
        if ($dates !== []) {
            $sheet->getStyle('C1:' . $lastColumn . '1')->getFont()->getColor()->setRGB('7C3AED');
            $sheet->getStyle('C1:' . $lastColumn . '1')->getFont()->setSize(12);
            $sheet->getStyle('C2:' . $lastColumn . '2')->getFont()->setSize(9);
        }

        $rowNumber = 3;
        foreach ($groups as $group) {
            $sheet->setCellValueExplicit(
                "A{$rowNumber}",
                (string) ($group['pin'] ?? ''),
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $sheet->setCellValue("B{$rowNumber}", (string) ($group['name'] ?? '-'));
            $sheet->getStyle("A{$rowNumber}:B{$rowNumber}")->applyFromArray([
                'font' => ['color' => ['rgb' => '111827'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E5E7EB']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $gridColor]],
                ],
            ]);
            $sheet->getStyle("B{$rowNumber}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $rowsByDate = collect($group['rows'])->keyBy('attendance_date');
            foreach ($dates as $dateIndex => $attendanceDate) {
                $column = $this->columnLetter($dateIndex + 3);
                $row = $rowsByDate->get($attendanceDate);
                $cellLines = [];
                $fillColor = 'FFFFFF';

                if (is_array($row)) {
                    $checkIn = trim((string) ($row['check_in'] ?? ''));
                    $checkOut = trim((string) ($row['check_out'] ?? ''));
                    $status = trim((string) ($row['status'] ?? ''));
                    if ($checkIn !== '' && $checkIn !== '-') $cellLines[] = substr($checkIn, 0, 5);
                    if ($checkOut !== '' && $checkOut !== '-') $cellLines[] = substr($checkOut, 0, 5);
                    if ($status === '' && ($row['is_off'] ?? false)) $status = 'OFF';
                    if ($status !== '' && $status !== '-') $cellLines[] = $status;

                    $statusLower = mb_strtolower($status);
                    if ($statusLower === 'terlambat') {
                        $fillColor = 'FDE047';
                    } elseif ($statusLower === 'tidak masuk') {
                        $fillColor = 'FCA5A5';
                    } elseif ($statusLower === 'off' || $statusLower === 'libur nasional' || $cellLines !== []) {
                        $fillColor = 'D1D5DB';
                    }
                }

                $sheet->setCellValue("{$column}{$rowNumber}", implode("\n", $cellLines));
                $sheet->getStyle("{$column}{$rowNumber}")->applyFromArray([
                    'font' => ['color' => ['rgb' => '111827'], 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $fillColor]],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $gridColor]],
                    ],
                ]);
            }

            $sheet->getRowDimension($rowNumber)->setRowHeight(58);
            $rowNumber++;
        }

        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(30);
        foreach ($dates as $index => $attendanceDate) {
            $sheet->getColumnDimension($this->columnLetter($index + 3))->setWidth(9.5);
        }
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->freezePane('C3');

        $writer = new Xlsx($spreadsheet);
        $filename = 'attendance_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function normalizeAttendanceDateFilter($value): ?string
    {
        $value = trim((string) $value);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);

            return $date && $date->format('Y-m-d') === $value ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeAttendancePin(string $pin): string
    {
        return mb_strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', trim($pin)) ?? '');
    }

    public function preview(Request $request)
    {
        abort_unless($this->isItDepartment($request), 403);

        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $month = (int) $request->input('month');
        $year = (int) $request->input('year');
        $file = $request->file('file');

        try {
            $rows = $this->loadFileRows($file->getRealPath(), $file->getClientOriginalName());

            $flatColumns = $this->detectFlatExportColumns($rows);

            if ($flatColumns !== null) {
                $result = $this->parseAttendanceRowsFlat($rows, $flatColumns);

                if (empty($result['preview_rows'])) {
                    return response()->json([
                        'message' => 'Tidak ada data attendance yang terbaca dari file.',
                    ], 422);
                }

                $months = [];
                foreach ($result['preview_rows'] as $previewRow) {
                    if (!empty($previewRow['attendance_date'])) {
                        $months[substr($previewRow['attendance_date'], 0, 7)] = true;
                    }
                }

                if (count($months) === 0) {
                    return response()->json([
                        'message' => 'Tidak ada tanggal valid terbaca dari file export.',
                    ], 422);
                }

                if (count($months) > 1) {
                    return response()->json([
                        'message' => 'File berisi data lebih dari satu bulan (' . count($months) . ' bulan). Filter export Attendance Log ke satu bulan lalu ulangi.',
                    ], 422);
                }

                $periodKey = array_key_first($months);
                $month = (int) substr($periodKey, 5, 2);
                $year = (int) substr($periodKey, 0, 4);
                $detected = ['month' => $month, 'year' => $year];
            } else {
                $detected = $this->detectPeriodFromRows($rows)
                    ?? $this->detectPeriodFromFilename((string) $file->getClientOriginalName());
                if ($detected !== null && is_array($detected)) {
                    $month = (int) $detected['month'];
                    $year = (int) $detected['year'];
                }

                $result = $this->parseAttendanceRows(
                    $rows,
                    $file->getClientOriginalName(),
                    $month,
                    $year
                );

                if (empty($result['preview_rows'])) {
                    return response()->json([
                        'message' => 'Tidak ada data attendance yang terbaca dari file.',
                    ], 422);
                }
            }

            $previewKey = 'attendance_preview_' . Str::uuid();
            $originalFilename = basename((string) $file->getClientOriginalName());

            $previewPayload = [
                'month' => $month,
                'year' => $year,
                'period_source' => $detected !== null && is_array($detected) ? 'file' : 'filter',
                'filename' => $originalFilename,
                'preview_rows' => $result['preview_rows'],
                'valid_rows' => $result['valid_rows'],
                'generated_at' => now()->toIso8601String(),
            ];
            $json = json_encode($previewPayload, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new \RuntimeException('Gagal encode preview payload ke JSON.');
            }
            Storage::disk('local')->put("attendance_import_previews/{$previewKey}.json", $json);

            return response()->json([
                'preview_key' => $previewKey,
                'month' => $month,
                'year' => $year,
                'period_source' => $detected !== null && is_array($detected) ? 'file' : 'filter',
                'summary' => [
                    'total_preview_rows' => count($result['preview_rows']),
                    'valid_rows' => count($result['valid_rows']),
                    'invalid_rows' => count($result['preview_rows']) - count($result['valid_rows']),
                ],
                'rows' => $result['preview_rows'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Attendance preview failed', [
                'error' => $e->getMessage(),
                'filename' => (string) $file->getClientOriginalName(),
                'user_id' => optional($request->user())->id,
            ]);

            $msg = strtolower($e->getMessage());
            $isZipError = str_contains($msg, 'ziparchive') || str_contains($msg, 'zip extension');
            $message = $isZipError
                ? 'Preview Excel gagal: ekstensi PHP ZIP belum aktif di server. Aktifkan php_zip atau upload file CSV.'
                : 'Preview attendance gagal diproses. Silakan coba lagi atau upload file CSV.';

            return response()->json([
                'message' => $message,
            ], 422);
        }
    }

    public function store(Request $request)
    {
        abort_unless($this->isItDepartment($request), 403);

        $request->validate([
            'preview_key' => 'required|string',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $previewKey = $request->input('preview_key');
        $path = "attendance_import_previews/{$previewKey}.json";
        if (!Storage::disk('local')->exists($path)) {
            return response()->json([
                'message' => 'Sesi preview sudah kadaluarsa. Silakan upload ulang file Anda.',
            ], 422);
        }

        $payload = json_decode((string) Storage::disk('local')->get($path), true);
        if (!is_array($payload)) {
            return response()->json([
                'message' => 'Sesi preview rusak. Silakan upload ulang file Anda.',
            ], 422);
        }

        $month = (int) $request->input('month');
        $year = (int) $request->input('year');
        if ((int) ($payload['month'] ?? 0) !== $month || (int) ($payload['year'] ?? 0) !== $year) {
            return response()->json([
                'message' => 'Periode tidak cocok dengan file. Pilih ulang bulan/tahun lalu preview lagi.',
            ], 422);
        }

        $validRows = $payload['valid_rows'] ?? [];
        if (empty($validRows)) {
            return response()->json([
                'message' => 'Tidak ada data valid untuk disimpan.',
            ], 422);
        }

        $rows = [];
        foreach ($validRows as $row) {
            $rows[] = [
                'pin' => (string) ($row['pin'] ?? ''),
                'attendance_date' => (string) ($row['attendance_date'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
                'check_in' => ($row['check_in'] ?? null) !== null && $row['check_in'] !== '' ? (string) $row['check_in'] : null,
                'check_out' => ($row['check_out'] ?? null) !== null && $row['check_out'] !== '' ? (string) $row['check_out'] : null,
                'status' => (string) ($row['status'] ?? ''),
                'is_off' => (bool) ($row['is_off'] ?? false),
                'leave_type' => ($row['leave_type'] ?? null) !== null && $row['leave_type'] !== '' ? (string) $row['leave_type'] : null,
                'shift_code' => ($row['shift_code'] ?? null) !== null && $row['shift_code'] !== '' ? (string) $row['shift_code'] : null,
                'schedule_start' => ($row['schedule_start'] ?? null) !== null && $row['schedule_start'] !== '' ? (string) $row['schedule_start'] : null,
                'schedule_end' => ($row['schedule_end'] ?? null) !== null && $row['schedule_end'] !== '' ? (string) $row['schedule_end'] : null,
                'overtime_label' => ($row['overtime_label'] ?? null) !== null && $row['overtime_label'] !== '' ? (string) $row['overtime_label'] : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $batch = AttendanceImportBatch::create([
            'filename' => (string) ($payload['filename'] ?? 'attendance.xlsx'),
            'month' => $month,
            'year' => $year,
            'uploaded_by' => $request->user()?->id,
            'total_rows' => count($payload['preview_rows'] ?? []),
            'valid_rows' => count($validRows),
            'saved_rows' => 0,
        ]);

        $batchId = $batch->id;
        foreach ($rows as $index => $row) {
            $rows[$index]['batch_id'] = $batchId;
        }

        try {
            AttendanceImportEntry::upsert($rows, ['pin', 'attendance_date'], [
                'batch_id',
                'name',
                'check_in',
                'check_out',
                'status',
                'is_off',
                'leave_type',
                'shift_code',
                'schedule_start',
                'schedule_end',
                'overtime_label',
                'updated_at',
            ]);
        } catch (\Throwable $e) {
            $batch->delete();
            Log::error('Attendance store failed', [
                'error' => $e->getMessage(),
                'user_id' => optional($request->user())->id,
            ]);

            return response()->json([
                'message' => 'Gagal menyimpan data attendance. Silakan coba lagi.',
            ], 422);
        }

        $batch->update(['saved_rows' => count($rows)]);
        Storage::disk('local')->delete($path);

        return response()->json([
            'message' => "Berhasil menyimpan {$batch->saved_rows} data attendance.",
            'batch_id' => $batchId,
        ]);
    }

    public function destroy(Request $request, int $batch)
    {
        abort_unless($this->isItDepartment($request), 403);

        $batchModel = AttendanceImportBatch::find($batch);
        if (!$batchModel) {
            return response()->json([
                'message' => 'Batch tidak ditemukan.',
            ], 404);
        }

        $batchModel->delete();

        return redirect()->back()->with('success', 'Batch import attendance berhasil dihapus.');
    }

    private function isItDepartment(Request $request): bool
    {
        return strtoupper(trim((string) $request->user()?->department?->code)) === 'IT';
    }

    private function paginateGroups($groups, int $perPage, Request $request): array
    {
        $collection = collect($groups);
        $page = max(1, (int) $request->input('page', 1));
        $total = $collection->count();
        $items = $collection->forPage($page, $perPage)->values();

        new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return [$items, $total];
    }

    private function loadFileRows(string $path, string $originalName): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            if (!class_exists(\ZipArchive::class)) {
                throw new \RuntimeException('ZipArchive extension is required to read Excel files.');
            }

            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getSheet(0);
            $rows = $sheet->toArray(null, true, true, false);

            return array_map(fn ($line) => array_map(fn ($v) => $this->cleanCellValue($v), $line), $rows);
        }

        $firstLine = (string) (fgets(fopen($path, 'r')) ?: '');
        $delimiter = str_contains($firstLine, "\t") ? "\t" : ';';
        if (!str_contains($firstLine, $delimiter) && str_contains($firstLine, ',')) {
            $delimiter = ',';
        }
        $handle = fopen($path, 'r');
        $lines = [];
        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lines[] = array_map(fn ($v) => $this->cleanCellValue($v), $line);
        }
        fclose($handle);

        return $lines;
    }

    private function monthTokenMap(): array
    {
        return [
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
    }

    private function detectPeriodFromRows(array $rows): ?array
    {
        $scanRows = array_slice($rows, 0, 8);
        foreach ($scanRows as $row) {
            foreach ((array) $row as $cell) {
                $text = strtoupper(trim((string) $cell));
                if ($text === '') {
                    continue;
                }
                if (!preg_match('/(20\d{2})/', $text, $matches)) {
                    continue;
                }
                $year = (int) $matches[1];
                if ($year < 2000 || $year > 2100) {
                    continue;
                }
                foreach ($this->monthTokenMap() as $monthToken => $monthNumber) {
                    if (strpos($text, $monthToken) !== false) {
                        return [
                            'month' => $monthNumber,
                            'year' => $year,
                        ];
                    }
                }
            }
        }

        return null;
    }

    private function detectPeriodFromFilename(string $filename): ?array
    {
        $name = strtoupper($filename);

        if (preg_match('/(20\d{2})\D+(1[0-2]|0?[1-9])(?:\D|$)/', $name, $m)) {
            $month = (int) $m[2];
            $year = (int) $m[1];
            if ($year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12) {
                return ['month' => $month, 'year' => $year];
            }
        }

        if (preg_match('/(?:^|\D)(1[0-2]|0?[1-9])\D+(20\d{2})(?:\D|$)/', $name, $m)) {
            $month = (int) $m[1];
            $year = (int) $m[2];
            if ($year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12) {
                return ['month' => $month, 'year' => $year];
            }
        }

        if (preg_match('/(20\d{2})/', $name, $m)) {
            $year = (int) $m[1];
            if ($year >= 2000 && $year <= 2100) {
                foreach ($this->monthTokenMap() as $monthToken => $monthNumber) {
                    if (strpos($name, $monthToken) !== false) {
                        return ['month' => $monthNumber, 'year' => $year];
                    }
                }
            }
        }

        return null;
    }

    private const FLAT_HEADER_REQUIRED = ['TANGGAL', 'PIN', 'NAMA', 'MASUK', 'PULANG', 'STATUS'];
    private const FLAT_HEADER_OPTIONAL = ['SHIFT' => 'shift', 'HARI' => 'hari', 'JADWAL' => 'jadwal', 'LEMBUR' => 'lembur'];

    private function detectFlatExportColumns(array $rows): ?array
    {
        $scanLimit = min(20, count($rows));
        for ($rowIndex = 0; $rowIndex < $scanLimit; $rowIndex++) {
            $normalized = [];
            foreach (($rows[$rowIndex] ?? []) as $index => $value) {
                $text = strtoupper(trim((string) $value));
                if ($text !== '') {
                    $normalized[$index] = $text;
                }
            }

            $match = true;
            foreach (self::FLAT_HEADER_REQUIRED as $needle) {
                if (!in_array($needle, $normalized, true)) {
                    $match = false;
                    break;
                }
            }
            if (!$match) {
                continue;
            }

            $columns = [
                'header_row' => $rowIndex,
                'tanggal' => (int) array_search('TANGGAL', $normalized, true),
                'pin' => (int) array_search('PIN', $normalized, true),
                'nama' => (int) array_search('NAMA', $normalized, true),
                'masuk' => (int) array_search('MASUK', $normalized, true),
                'pulang' => (int) array_search('PULANG', $normalized, true),
                'status' => (int) array_search('STATUS', $normalized, true),
                'shift' => null,
                'hari' => null,
                'jadwal' => null,
                'lembur' => null,
            ];

            foreach (self::FLAT_HEADER_OPTIONAL as $needle => $key) {
                $position = array_search($needle, $normalized, true);
                if ($position !== false) {
                    $columns[$key] = (int) $position;
                }
            }

            return $columns;
        }

        return null;
    }

    private function parseAttendanceRowsFlat(array $rows, array $columns): array
    {
        $previewRows = [];
        $validRows = [];
        $dataStart = (int) $columns['header_row'] + 1;
        $dateOrder = $this->detectFlatDateOrder($rows, (int) $columns['tanggal'], $dataStart);

        for ($index = $dataStart; $index < count($rows); $index++) {
            $row = $rows[$index];

            $pin = $this->cleanCellValue($row[$columns['pin']] ?? '');
            $name = $this->cleanCellValue($row[$columns['nama']] ?? '');
            $dateRaw = $this->cleanCellValue($row[$columns['tanggal']] ?? '');
            $checkInRaw = $this->cleanCellValue($row[$columns['masuk']] ?? '');
            $checkOutRaw = $this->cleanCellValue($row[$columns['pulang']] ?? '');
            $statusRaw = $this->cleanCellValue($row[$columns['status']] ?? '');
            $shiftRaw = $columns['shift'] !== null ? $this->cleanCellValue($row[$columns['shift']] ?? '') : '';
            $jadwalRaw = $columns['jadwal'] !== null ? $this->cleanCellValue($row[$columns['jadwal']] ?? '') : '';
            $lemburRaw = $columns['lembur'] !== null ? $this->cleanCellValue($row[$columns['lembur']] ?? '') : '';

            if ($pin === '' && $name === '') {
                continue;
            }

            $attendanceDate = $this->parseFlatDateCell($dateRaw, $dateOrder);
            if ($attendanceDate === null) {
                $rowData = $this->buildFlatPreviewRow($pin, $name, '', '-', '-', null, null, $statusRaw, false, null, null, null, null, null, false, 'Tanggal tidak valid atau kosong.');
                $previewRows[] = $rowData;
                continue;
            }

            $statusUpper = strtoupper($statusRaw);
            $isOff = $statusUpper === 'OFF';
            $leaveType = $statusUpper !== '' && in_array($statusUpper, self::LEAVE_TYPES, true) ? $statusUpper : null;
            [$scheduleStart, $scheduleEnd] = $this->parseFlatSchedule($jadwalRaw);

            $checkIn = $this->parseFlatTime($checkInRaw);
            $checkOut = $this->parseFlatTime($checkOutRaw);

            $error = null;
            if ($checkInRaw !== '' && $checkInRaw !== '-' && $checkIn === null) {
                $error = 'Format jam masuk tidak valid.';
            } elseif ($checkOutRaw !== '' && $checkOutRaw !== '-' && $checkOut === null) {
                $error = 'Format jam pulang tidak valid.';
            } elseif ($statusRaw === '' && $checkIn === null && $checkOut === null) {
                continue;
            }

            $date = Carbon::parse($attendanceDate);
            $rowData = $this->buildFlatPreviewRow(
                $pin,
                $name,
                $attendanceDate,
                $date->format('d M Y'),
                $date->locale('id')->translatedFormat('l'),
                $checkIn,
                $checkOut,
                $statusRaw,
                $isOff,
                $leaveType,
                $shiftRaw !== '' && $shiftRaw !== '-' ? $shiftRaw : null,
                $scheduleStart,
                $scheduleEnd,
                $lemburRaw !== '' && $lemburRaw !== '-' ? $lemburRaw : null,
                $error === null,
                $error
            );

            $previewRows[] = $rowData;
            if ($error === null) {
                $validRows[] = $rowData;
            }
        }

        return ['preview_rows' => $previewRows, 'valid_rows' => $validRows];
    }

    private function buildFlatPreviewRow(
        string $pin,
        string $name,
        string $attendanceDate,
        string $attendanceDateLabel,
        string $dayName,
        ?string $checkIn,
        ?string $checkOut,
        string $status,
        bool $isOff,
        ?string $leaveType,
        ?string $shiftCode,
        ?string $scheduleStart,
        ?string $scheduleEnd,
        ?string $overtimeLabel,
        bool $isValid,
        ?string $error
    ): array {
        return [
            'pin' => $pin,
            'name' => $name,
            'attendance_date' => $attendanceDate,
            'attendance_date_label' => $attendanceDateLabel,
            'day_name' => $dayName,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => $status,
            'is_off' => $isOff,
            'leave_type' => $leaveType,
            'shift_code' => $shiftCode,
            'schedule_start' => $scheduleStart,
            'schedule_end' => $scheduleEnd,
            'overtime_label' => $overtimeLabel,
            'is_valid' => $isValid,
            'error' => $error,
        ];
    }

    private function detectFlatDateOrder(array $rows, int $dateColumn, int $dataStart): string
    {
        $dayFirstEvidence = 0;
        $monthFirstEvidence = 0;

        for ($index = $dataStart; $index < count($rows); $index++) {
            $value = trim((string) ($rows[$index][$dateColumn] ?? ''));
            if (!preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-]\d{4}$/', $value, $matches)) {
                continue;
            }

            $first = (int) $matches[1];
            $second = (int) $matches[2];
            if ($first > 12 && $second >= 1 && $second <= 12) {
                $dayFirstEvidence++;
            } elseif ($second > 12 && $first >= 1 && $first <= 12) {
                $monthFirstEvidence++;
            }
        }

        return $monthFirstEvidence > $dayFirstEvidence ? 'mdy' : 'dmy';
    }

    private function parseFlatDateCell(string $value, string $dateOrder = 'dmy'): ?string
    {
        $value = trim($value);
        if ($value === '' || $value === '-') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                $date = ExcelDate::excelToDateTimeObject((float) $value);
                if ((int) $date->format('Y') >= 2000 && (int) $date->format('Y') <= 2100) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // fallthrough ke format teks
            }
        }

        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $value, $matches)) {
            return $this->formatStrictFlatDate((int) $matches[1], (int) $matches[2], (int) $matches[3]);
        }

        if (preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/', $value, $matches)) {
            $first = (int) $matches[1];
            $second = (int) $matches[2];
            $year = (int) $matches[3];
            $month = $dateOrder === 'mdy' ? $first : $second;
            $day = $dateOrder === 'mdy' ? $second : $first;

            return $this->formatStrictFlatDate($year, $month, $day);
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            $parsed = Carbon::createFromTimestamp($timestamp);
            if ($parsed->year >= 2000 && $parsed->year <= 2100) {
                return $parsed->format('Y-m-d');
            }
        }

        return null;
    }

    private function formatStrictFlatDate(int $year, int $month, int $day): ?string
    {
        if ($year < 2000 || $year > 2100 || !checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private function parseFlatTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || $value === '-') {
            return null;
        }
        if (!preg_match('/^(\d{1,2})[:.](\d{2})$/', $value, $matches)) {
            return null;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];
        if ($hour > 23 || $minute > 59) {
            return null;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }

    private function parseFlatSchedule(string $value): array
    {
        $value = trim($value);
        if ($value === '' || $value === '-') {
            return [null, null];
        }

        preg_match_all('/\b(\d{1,2})[:.](\d{2})\b/', $value, $matches, PREG_SET_ORDER);
        $times = [];
        foreach ($matches as $match) {
            $hour = (int) $match[1];
            $minute = (int) $match[2];
            if ($hour > 23 || $minute > 59) {
                continue;
            }
            $times[] = sprintf('%02d:%02d', $hour, $minute);
        }

        return [$times[0] ?? null, $times[1] ?? null];
    }

    private function parseAttendanceRows(array $rows, string $originalName, int $month, int $year): array
    {
        if (count($rows) < 2) {
            return ['preview_rows' => [], 'valid_rows' => []];
        }

        [$dayHeaderRowIndex, $dayColumnMap] = $this->detectDayColumns($rows);
        if ($dayHeaderRowIndex === null || empty($dayColumnMap)) {
            return ['preview_rows' => [], 'valid_rows' => []];
        }

        $pinCol = $this->detectColumnIndex($rows, ['PIN', 'NIK', 'NRP', 'NO', 'NO.', 'ID'], 0, $dayHeaderRowIndex);
        $nameCol = $this->detectColumnIndex($rows, ['NAMA', 'NAME'], 1, $dayHeaderRowIndex);
        if ($pinCol === $nameCol) {
            $nameCol = $pinCol === 0 ? 1 : 0;
        }

        $dataStartRow = $dayHeaderRowIndex + 1;
        if (isset($rows[$dayHeaderRowIndex + 1])) {
            $nextRow = array_map(static fn ($v) => strtoupper(trim((string) $v)), $rows[$dayHeaderRowIndex + 1]);
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
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        for ($rowIndex = $dataStartRow; $rowIndex < count($rows); $rowIndex++) {
            $row = $rows[$rowIndex];
            $employeePin = $this->cleanCellValue($row[$pinCol] ?? '');
            $employeeName = $this->cleanCellValue($row[$nameCol] ?? '');

            if ($employeePin === '' && $employeeName === '') {
                continue;
            }

            foreach ($dayColumnMap as $colIndex => $day) {
                $rawCell = $this->cleanCellValue($row[$colIndex] ?? '');
                if ($rawCell === '' || $day < 1 || $day > $daysInMonth) {
                    continue;
                }

                $parsed = $this->parseAttendanceCell($rawCell);
                if ($parsed === null) {
                    continue;
                }

                try {
                    $attendanceDate = Carbon::createFromDate($year, $month, $day);
                } catch (\Exception $e) {
                    continue;
                }

                $error = null;
                if ($employeePin === '' && $employeeName === '') {
                    $error = 'PIN dan nama kosong.';
                } elseif ($parsed['check_in'] !== null && !$this->isTime($parsed['check_in'])) {
                    $error = 'Format jam masuk tidak valid.';
                } elseif ($parsed['check_out'] !== null && !$this->isTime($parsed['check_out'])) {
                    $error = 'Format jam pulang tidak valid.';
                }

                $previewRow = [
                    'pin' => $employeePin,
                    'name' => $employeeName,
                    'attendance_date' => $attendanceDate->format('Y-m-d'),
                    'attendance_date_label' => $attendanceDate->format('d M Y'),
                    'day_name' => $attendanceDate->locale('id')->translatedFormat('l'),
                    'check_in' => $parsed['check_in'],
                    'check_out' => $parsed['check_out'],
                    'status' => $parsed['status'],
                    'is_off' => $parsed['is_off'],
                    'leave_type' => $parsed['leave_type'],
                    'shift_code' => null,
                    'schedule_start' => null,
                    'schedule_end' => null,
                    'overtime_label' => null,
                    'is_valid' => $error === null,
                    'error' => $error,
                ];

                $previewRows[] = $previewRow;
                if ($error === null) {
                    $validRows[] = $previewRow;
                }
            }
        }

        return ['preview_rows' => $previewRows, 'valid_rows' => $validRows];
    }

    private function parseAttendanceCell(string $cell): ?array
    {
        $cell = str_replace(["\r\n", "\r"], "\n", $cell);
        $lines = array_values(array_filter(
            array_map(fn ($line) => trim($line), explode("\n", $cell)),
            fn ($line) => $line !== ''
        ));

        if (empty($lines)) {
            return null;
        }

        $times = [];
        $statusLines = [];
        $timePattern = '/\b(\d{1,2})[:.]([0-5]\d)\b/';
        foreach ($lines as $line) {
            if (!preg_match_all($timePattern, $line, $matches, PREG_SET_ORDER)) {
                $statusLines[] = $line;
                continue;
            }

            foreach ($matches as $match) {
                $hour = (int) $match[1];
                if ($hour <= 23) {
                    $times[] = sprintf('%02d:%02d', $hour, (int) $match[2]);
                }
            }

            $remaining = preg_replace($timePattern, ' ', $line);
            $remaining = trim((string) preg_replace('/[\s,;\/]+/', ' ', $remaining));
            if ($remaining !== '') {
                $statusLines[] = $remaining;
            }
        }

        $checkIn = $times[0] ?? null;
        $checkOut = $times[1] ?? null;
        $status = $statusLines !== [] ? implode(' ', array_slice($statusLines, -1)) : '';
        $statusUpper = strtoupper($status);

        return [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => $status,
            'is_off' => $statusUpper === 'OFF',
            'leave_type' => in_array($statusUpper, self::LEAVE_TYPES, true) ? $statusUpper : null,
        ];
    }

    private function normalizeTimeLine(string $line): ?string
    {
        $line = trim($line);
        if ($line === '') {
            return null;
        }
        if (!preg_match('/^(\d{1,2})[:.]([0-5]\d)$/', $line, $matches)) {
            return null;
        }

        $hour = (int) $matches[1];
        if ($hour > 23) {
            return null;
        }

        return sprintf('%02d:%02d', $hour, (int) $matches[2]);
    }

    private function isTime(string $value): bool
    {
        return $value !== '' && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
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

    private function cleanCellValue($value): string
    {
        $text = trim((string) $value);
        if (strlen($text) >= 2 && $text[0] === '"' && $text[strlen($text) - 1] === '"') {
            $text = substr($text, 1, -1);
        }
        return trim(str_replace('""', '"', $text));
    }
}