<?php

namespace App\Http\Controllers;

use App\Models\Fingerprint;
use App\Models\Employee;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FingerprintController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $fingerprints = Fingerprint::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('pin', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%")
                        ->orWhere('department', 'like', "%{$q}%")
                        ->orWhere('machine', 'like', "%{$q}%");
                });
            })
            ->orderBy('scan_date', 'desc')
            ->paginate(50);

        return Inertia::render('GMIHR/Fingerprint/Index', [
            'fingerprints' => $fingerprints,
            'filters' => [
                'q' => $q,
            ],
        ]);
    }

    /**
     * Preview fingerprint data from CSV file (check for duplicates without saving).
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        
        $allData = [];
        $rowNumber = 0;

        // Get header row and skip it
        $headers = fgetcsv($handle);
        $rowNumber++;
        
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            // Skip empty rows
            if (empty($row) || (count($row) === 1 && empty(trim($row[0])))) {
                continue;
            }
            
            try {
                // Check minimum columns (14 columns expected)
                if (count($row) < 14) {
                    continue;
                }

                // Parse the row
                $scanDateTime = isset($row[0]) ? trim($row[0]) : '';
                $scanDate = isset($row[1]) ? trim($row[1]) : '';
                $scanTime = isset($row[2]) ? trim($row[2]) : '';
                $pin = isset($row[3]) ? trim($row[3]) : '';
                $nip = isset($row[4]) ? trim($row[4]) : '';
                $name = isset($row[5]) ? trim($row[5]) : '';
                $position = isset($row[6]) ? trim($row[6]) : '';
                $department = isset($row[7]) ? trim($row[7]) : '';
                $office = isset($row[8]) ? trim($row[8]) : '';
                $verify = isset($row[9]) ? intval(trim($row[9])) : 0;
                $io = isset($row[10]) ? intval(trim($row[10])) : 0;
                $workcode = isset($row[11]) ? intval(trim($row[11])) : 0;
                $sn = isset($row[12]) ? trim($row[12]) : '';
                $machine = isset($row[13]) ? trim($row[13]) : '';

                // Skip if PIN or scan_date is empty
                if (empty($pin) || empty($scanDateTime)) {
                    continue;
                }

                // Convert date format from dd-mm-yyyy to yyyy-mm-dd
                $scanDateFormatted = $this->convertDate($scanDate);
                $scanTimeFormatted = $this->convertTime($scanTime);
                
                // Combine date and time for scan_date
                $fullScanDate = $scanDateFormatted . ' ' . $scanTimeFormatted;

                // Check for duplicate in fingerprints table
                $existsFingerprint = Fingerprint::where('scan_date', $fullScanDate)
                    ->where('pin', $pin)
                    ->exists();

                // Check if employee already exists with this PIN
                $existsEmployee = Employee::where('nik', $pin)->exists();

                $allData[] = [
                    'scan_date' => $fullScanDate,
                    'scan_date_only' => $scanDateFormatted,
                    'scan_time' => $scanTimeFormatted,
                    'pin' => $pin,
                    'nip' => $nip,
                    'name' => $name,
                    'position' => $position,
                    'department' => $department,
                    'office' => $office,
                    'verify' => $verify,
                    'io' => $io,
                    'workcode' => $workcode,
                    'sn' => $sn,
                    'machine' => $machine,
                    'duplicate' => $existsFingerprint,
                    'employee_exists' => $existsEmployee,
                ];

            } catch (\Exception $e) {
                Log::error('Preview error at row ' . $rowNumber . ': ' . $e->getMessage());
            }
        }

        fclose($handle);

        // Paginate the data - ensure integers
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 50);
        
        // Prevent invalid values
        if ($page < 1) $page = 1;
        if ($perPage < 1) $perPage = 50;
        
        $total = count($allData);
        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;
        $paginatedData = array_slice($allData, $offset, $perPage);

        return response()->json([
            'preview' => [
                'data' => $paginatedData,
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Confirm and save fingerprint data.
     */
    public function confirmSave(Request $request)
    {
        $request->validate([
            'data' => 'required|array',
        ]);

        $data = $request->input('data');
        
        $savedCount = 0;
        $skippedCount = 0;
        $employeeSavedCount = 0;
        $employeeUpdatedCount = 0;

        // Track unique employees (PIN -> Name) to avoid duplicate employee records
        $processedEmployees = [];

        foreach ($data as $item) {
            // Skip duplicates in fingerprint table
            if ($item['duplicate']) {
                $skippedCount++;
                continue;
            }

            try {
                // Save fingerprint data
                Fingerprint::create([
                    'scan_date' => $item['scan_date'],
                    'scan_date_only' => $item['scan_date_only'],
                    'scan_time' => $item['scan_time'],
                    'pin' => $item['pin'],
                    'nip' => $item['nip'],
                    'name' => $item['name'],
                    'position' => $item['position'],
                    'department' => $item['department'],
                    'office' => $item['office'],
                    'verify' => $item['verify'],
                    'io' => $item['io'],
                    'workcode' => $item['workcode'],
                    'sn' => $item['sn'],
                    'machine' => $item['machine'],
                ]);
                $savedCount++;

                // Save or Update employee record (avoid duplicates)
                // Only process each unique PIN once
                if (!isset($processedEmployees[$item['pin']])) {
                    $processedEmployees[$item['pin']] = true;
                    
                    // Check if employee with this NIK (PIN) already exists
                    $employee = Employee::where('nik', $item['pin'])->first();
                    
                    if ($employee) {
                        // Update existing employee
                        $employee->update([
                            'nik' => $item['pin'],
                            'name' => $item['name'],
                        ]);
                        $employeeUpdatedCount++;
                    } else {
                        // Create new employee record
                        Employee::create([
                            'nik' => $item['pin'],
                            'name' => $item['name'],
                            'phone' => '',
                            'address' => '',
                            'join_date' => $item['scan_date_only'] ?? null,
                        ]);
                        $employeeSavedCount++;
                    }
                }

            } catch (\Exception $e) {
                Log::error('Save error: ' . $e->getMessage());
            }
        }

        // Activity Log
        $this->logActivity(
            'fingerprints',
            null,
            'bulk_import',
            null,
            [
                'fingerprints_saved' => $savedCount,
                'fingerprints_skipped' => $skippedCount,
                'employees_saved' => $employeeSavedCount,
                'employees_updated' => $employeeUpdatedCount,
            ],
            "Imported fingerprint data: {$savedCount} fingerprints saved, {$skippedCount} skipped. {$employeeSavedCount} employees created, {$employeeUpdatedCount} updated."
        );

        return response()->json([
            'message' => [
                'type' => 'success',
                'text' => "Data berhasil disimpan! {$savedCount} record fingerprint baru, {$skippedCount} duplikat dilewati. {$employeeSavedCount} employee baru, {$employeeUpdatedCount} employee diupdate.",
                'saved' => $savedCount,
                'skipped' => $skippedCount,
                'employees_saved' => $employeeSavedCount,
                'employees_updated' => $employeeUpdatedCount,
            ]
        ]);
    }

    /**
     * Remove all fingerprint data.
     */
    public function clear(Request $request)
    {
        $totalCount = Fingerprint::count();
        
        Fingerprint::truncate();

        // Activity Log
        $this->logActivity(
            'fingerprints',
            null,
            'bulk_clear',
            ['total_records' => $totalCount],
            null,
            "Cleared all fingerprint data ({$totalCount} records deleted)"
        );

        return response()->json([
            'message' => [
                'type' => 'success',
                'text' => 'All fingerprint data has been cleared.',
            ]
        ]);
    }

    /**
     * Download all fingerprint scan logs as CSV.
     */
    public function downloadScanlog(Request $request): StreamedResponse
    {
        $filename = 'scanlog_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = [
            'Tanggal scan',
            'Tanggal',
            'Jam',
            'PIN',
            'NIP',
            'Nama',
            'Jabatan',
            'Departemen',
            'Kantor',
            'Verifikasi',
            'I/O',
            'Workcode',
            'SN',
            'Mesin',
        ];

        return response()->stream(function () use ($columns) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);

            Fingerprint::query()
                ->orderBy('scan_date', 'desc')
                ->chunk(1000, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            optional($row->scan_date)->format('Y-m-d H:i:s'),
                            optional($row->scan_date_only)->format('Y-m-d'),
                            $row->scan_time ?? '',
                            $row->pin ?? '',
                            $row->nip ?? '',
                            $row->name ?? '',
                            $row->position ?? '',
                            $row->department ?? '',
                            $row->office ?? '',
                            $row->verify ?? '',
                            $row->io ?? '',
                            $row->workcode ?? '',
                            $row->sn ?? '',
                            $row->machine ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Convert date from dd-mm-yyyy to yyyy-mm-dd
     */
    private function convertDate(string $date): string
    {
        if (empty($date)) {
            return date('Y-m-d');
        }

        $parts = explode('-', $date);
        if (count($parts) === 3) {
            // Check if first part is day (dd-mm-yyyy)
            if (intval($parts[0]) > 12) {
                return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }
        }
        
        // Try to parse as is
        try {
            return date('Y-m-d', strtotime($date));
        } catch (\Exception $e) {
            return date('Y-m-d');
        }
    }

    /**
     * Convert time format
     */
    private function convertTime(string $time): string
    {
        if (empty($time)) {
            return date('H:i:s');
        }

        try {
            $parsed = date('H:i:s', strtotime($time));
            return $parsed;
        } catch (\Exception $e) {
            return date('H:i:s');
        }
    }

    /**
     * Helper function to log activity
     */
    private function logActivity($tableName, $recordId, $action, $oldValues, $newValues, $description)
    {
        try {
            $user = Auth::user();
            
            $activityLog = new ActivityLog();
            $activityLog->table_name = $tableName;
            $activityLog->record_id = $recordId;
            $activityLog->action = $action;
            $activityLog->old_values = json_encode($oldValues);
            $activityLog->new_values = json_encode($newValues);
            $activityLog->user_id = $user ? $user->id : null;
            $activityLog->user_email = $user ? $user->email : null;
            $activityLog->ip_address = request()->ip();
            $activityLog->created_date = now();
            $activityLog->description = $description;
            $activityLog->save();
            
            Log::info('Activity logged: ' . $description);
        } catch (\Exception $e) {
            Log::error('Activity log error: ' . $e->getMessage());
        }
    }
}
