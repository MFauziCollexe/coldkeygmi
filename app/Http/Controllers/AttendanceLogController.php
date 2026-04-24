<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLogCorrection;
use App\Models\User;
use App\Support\AccessRuleService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceLogController extends Controller
{
    // Easy rollback switch for scan pairing logic.
    private const ACCESS_MODULE = 'attendance_log';
    private const USE_SCHEDULE_WINDOWS = true;
    protected function accessRules(): AccessRuleService
    {
        return app(AccessRuleService::class);
    }

    public function index(Request $request)
    {
        $canManageCorrections = $this->canManageCorrections($request->user());
        $forcedDepartmentName = $this->resolveForcedAttendanceDepartment($request->user());

        $monthInput = $request->input('month');
        $yearInput = $request->input('year');
        $month = ($monthInput === null || $monthInput === '' || $monthInput === 'all') ? null : (int) $monthInput;
        $year = ($yearInput === null || $yearInput === '' || $yearInput === 'all') ? null : (int) $yearInput;
        $status = strtolower(trim((string) $request->input('status', 'all')));
        $q = trim((string) $request->input('q', ''));
        $perPage = (int) $request->input('per_page', 2000);
        $selectedPins = $this->normalizeAttendanceHeaderFilterInput($request->input('pins'));
        $selectedNames = $this->normalizeAttendanceHeaderFilterInput($request->input('names'));
        $selectedDepartments = $this->normalizeAttendanceHeaderFilterInput($request->input('departments'));
        if ($forcedDepartmentName !== null) {
            $selectedDepartments = [$forcedDepartmentName];
        }

        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }
        if ($year !== null && ($year < 2000 || $year > 2100)) {
            $year = null;
        }
        if ($perPage < 10 || $perPage > 5000) {
            $perPage = 2000;
        }

        [$dateFrom, $dateTo] = $this->resolveAttendanceFilterDates(
            $request->input('date_from'),
            $request->input('date_to'),
            $year,
            $month
        );

        [$scanRangeStart, $scanRangeEnd] = $this->resolveScanDateRangeForDates($dateFrom, $dateTo);

        $corrections = AttendanceLogCorrection::query()
            ->when($dateFrom !== null, fn($query) => $query->whereDate('log_date', '>=', $dateFrom))
            ->when($dateTo !== null, fn($query) => $query->whereDate('log_date', '<=', $dateTo))
            ->get();

        $correctionMap = $corrections->keyBy(function (AttendanceLogCorrection $correction) {
            return $correction->log_date->format('Y-m-d') . '|' . $this->normalizePin((string) $correction->pin);
        });

        $scanRows = DB::table('fingerprints')
            ->when($scanRangeStart !== null, fn($query) => $query->whereDate('scan_date_only', '>=', $scanRangeStart))
            ->when($scanRangeEnd !== null, fn($query) => $query->whereDate('scan_date_only', '<=', $scanRangeEnd))
            ->whereNotNull('pin')
            ->where('pin', '<>', '')
            ->get([
                'scan_date_only as log_date',
                'pin',
                'name',
                'scan_date',
            ]);

        $employeeColumns = [
            'e.id as employee_id',
            'e.nik',
            'e.name',
            'd.name as department_name',
        ];
        if (Schema::hasColumn('employees', 'alias_name')) {
            $employeeColumns[] = 'e.alias_name';
        }

        $employeeInfoByPin = DB::table('employees as e')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->whereNotNull('e.nik')
            ->where('e.nik', '<>', '')
            ->get($employeeColumns)
            ->mapWithKeys(function ($row) {
                $pin = $this->normalizePin((string) ($row->nik ?? ''));
                if ($pin === '') {
                    return [];
                }

                return [
                    $pin => [
                        'employee_id' => (int) ($row->employee_id ?? 0),
                        'nik' => trim((string) ($row->nik ?? '')),
                        'name' => trim((string) ($row->name ?? '')),
                        'alias_name' => trim((string) ($row->alias_name ?? '')),
                        'department_name' => trim((string) ($row->department_name ?? '')),
                    ],
                ];
            });

        $employeeStatusByPin = collect();
        if (Schema::hasColumn('employees', 'employment_status')) {
            $employeeStatusByPin = DB::table('employees')
                ->whereNotNull('nik')
                ->where('nik', '<>', '')
                ->pluck('employment_status', 'nik')
                ->mapWithKeys(function ($status, $nik) {
                    $pin = $this->normalizePin((string) $nik);
                    if ($pin === '') {
                        return [];
                    }
                    return [$pin => strtolower(trim((string) $status))];
                });
        }

        $scanGroups = $scanRows
            ->groupBy(fn($row) => $this->toDateString($row->log_date) . '|' . $this->normalizePin((string) $row->pin))
            ->map(fn(Collection $group) => $group->sortBy('scan_date')->values());

        $effectiveCurrentBatchIds = $this->effectiveRosterCurrentBatchIds($dateFrom, $dateTo);

        $rosterRows = DB::table('roster_entries as re')
            ->join('roster_upload_batches as rub', 'rub.id', '=', 're.batch_id')
            ->when($dateFrom !== null, fn($query) => $query->whereDate('re.roster_date', '>=', $dateFrom))
            ->when($dateTo !== null, fn($query) => $query->whereDate('re.roster_date', '<=', $dateTo))
            ->where('rub.status', 'approved')
            ->when(!empty($effectiveCurrentBatchIds), function ($query) use ($effectiveCurrentBatchIds) {
                $query->whereIn('rub.id', $effectiveCurrentBatchIds);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->whereNotNull('re.employee_nrp')
            ->where('re.employee_nrp', '<>', '')
            ->get([
                're.roster_date as log_date',
                're.employee_nrp as pin',
                're.employee_name as roster_name',
                're.shift_code',
                're.is_off',
                're.start_time',
                're.end_time',
            ]);

        $rosterScheduleIndex = $this->buildRosterScheduleIndex($rosterRows);

        $pinsInScope = $scanRows
            ->pluck('pin')
            ->merge($rosterRows->pluck('pin'))
            ->map(fn($pin) => $this->normalizePin((string) $pin))
            ->filter()
            ->unique()
            ->values();

        // When "Semua Tahun" is selected, we still want leave/permission overlay (izin/sakit/cuti/dinas)
        // for the date range currently in scope. Derive the range from roster + scan dates.
        $rangeDates = $scanRows
            ->pluck('log_date')
            ->merge($rosterRows->pluck('log_date'))
            ->map(fn($date) => $this->toDateString($date))
            ->filter()
            ->values();
        $rangeStartDate = $rangeDates->min();
        $rangeEndDate = $rangeDates->max();

        $approvedLeaveTypeByPinDate = $this->buildApprovedLeaveTypesByPinDate(
            null,
            null,
            $pinsInScope,
            $rangeStartDate,
            $rangeEndDate
        );
        $approvedLeaveDetailsByPinDate = $this->buildApprovedLeaveDetailsByPinDate(
            $pinsInScope,
            $rangeStartDate,
            $rangeEndDate
        );
        $overtimeFormByPinDate = $this->buildOvertimeFormByPinDate(
            $employeeInfoByPin,
            $rangeStartDate,
            $rangeEndDate
        );
        $overtimeDetailsByPinDate = $this->buildOvertimeDetailsByPinDate(
            $employeeInfoByPin,
            $rangeStartDate,
            $rangeEndDate
        );

        $holidaysByDate = DB::table('attendance_holidays')
            ->when($dateFrom !== null, fn($query) => $query->whereDate('holiday_date', '>=', $dateFrom))
            ->when($dateTo !== null, fn($query) => $query->whereDate('holiday_date', '<=', $dateTo))
            ->get(['holiday_date', 'name', 'scope_type'])
            ->groupBy(function ($row) {
                $date = $this->toDateString($row->holiday_date);
                return $date ?? '__invalid__';
            });

        if ($holidaysByDate->has('__invalid__')) {
            $holidaysByDate->forget('__invalid__');
        }

        $pinWorkGroupMap = DB::table('employees')
            ->whereNotNull('nik')
            ->where('nik', '<>', '')
            ->whereNotNull('work_group')
            ->where('work_group', '<>', '')
            ->pluck('work_group', 'nik')
            ->mapWithKeys(function ($group, $nik) {
                $pin = $this->normalizePin((string) $nik);
                if ($pin === '') {
                    return [];
                }
                $normalized = strtolower(trim((string) $group));
                if (!in_array($normalized, ['office', 'operational'], true)) {
                    return [];
                }
                return [$pin => $normalized];
            })
            ->all();

        $fingerprintWorkGroupFallback = DB::table('fingerprints')
            ->whereNotNull('pin')
            ->where('pin', '<>', '')
            ->whereNotNull('department')
            ->where('department', '<>', '')
            ->orderBy('scan_date', 'desc')
            ->get(['pin', 'department'])
            ->reduce(function ($carry, $row) {
                $pin = $this->normalizePin((string) $row->pin);
                if ($pin === '' || isset($carry[$pin])) {
                    return $carry;
                }
                $carry[$pin] = $this->resolveWorkGroup((string) $row->department);
                return $carry;
            }, []);

        foreach ($fingerprintWorkGroupFallback as $pin => $group) {
            if (!isset($pinWorkGroupMap[$pin]) && in_array($group, ['office', 'operational'], true)) {
                $pinWorkGroupMap[$pin] = $group;
            }
        }

        $rosterKeys = [];
        $rosterPinIndex = [];
        $rows = collect();

        foreach ($rosterRows as $row) {
            $logDate = $this->toDateString($row->log_date);
            $pin = $this->normalizePin((string) $row->pin);
            $scanLookupKeys = $this->pinCandidatesForMatch($pin);
            foreach ($scanLookupKeys as $rosterPinCandidate) {
                $rosterPinIndex[$rosterPinCandidate] = true;
            }
            $scans = collect();
            $isOff = (bool) ($row->is_off ?? false);
            $startTime = $this->normalizeTime($row->start_time);
            $endTime = $this->normalizeTime($row->end_time);
            $nextDayStartTime = $this->resolveNextDayStartTime($rosterScheduleIndex, $pin, $logDate);
            $lookupDates = $isOff
                ? array_values(array_filter([$logDate]))
                : $this->scanLookupDatesForSchedule($logDate, $startTime, $endTime, $nextDayStartTime);

            foreach ($scanLookupKeys as $lookupPin) {
                foreach ($lookupDates as $lookupDate) {
                    $extraKey = $lookupDate . '|' . $lookupPin;
                    if ($lookupDate === $logDate) {
                        $rosterKeys[$extraKey] = true;
                    }
                    $scans = $scans->merge($scanGroups->get($extraKey, collect()));
                }
            }
            $scans = $scans->sortBy('scan_date')->values();
            if ($isOff) {
                $scans = $this->filterOffdayBoundaryScans($scans);
                [$firstScan, $lastScan] = $this->resolveOffdayScanWindow($scans, $logDate);
                $scanCount = ($firstScan !== null && $lastScan !== null) ? $scans->count() : 0;
            } else {
                $scanCount = $scans->count();
                [$firstScan, $lastScan] = $this->resolveScanWindow($scans, $startTime, $endTime, $logDate, $nextDayStartTime);
            }
            $correction = $correctionMap->get($logDate . '|' . $pin);
            if ($correction && $correction->status === 'approved') {
                if ($correction->corrected_first_scan !== null) {
                    $firstScan = $correction->corrected_first_scan->format('Y-m-d H:i:s');
                }
                if ($correction->corrected_last_scan !== null) {
                    $lastScan = $correction->corrected_last_scan->format('Y-m-d H:i:s');
                }
            }
            if ($isOff) {
                if ($firstScan === null || $lastScan === null) {
                    $firstScan = null;
                    $lastScan = null;
                    $scanCount = 0;
                } else {
                    $scanCount = max($scanCount, 2);
                }
            }
            $firstScanTime = $this->normalizeTime($firstScan);
            $lastScanTime = $this->normalizeTime($lastScan);
            $timeMatched = $startTime !== null && $firstScanTime !== null && substr($startTime, 0, 5) === substr($firstScanTime, 0, 5);
            $hasFirstScan = $firstScan !== null;
            $hasLastScan = $lastScan !== null;
            $hasScheduleTime = $startTime !== null && $endTime !== null;
            $expected = null;
            $reason = null;
            [$isNationalHoliday, $holidayName] = $this->resolveHolidayForPin(
                $holidaysByDate,
                $logDate,
                $pin,
                $pinWorkGroupMap
            );

            $rowStatus = 'no_roster';
            if ($isNationalHoliday) {
                $rowStatus = 'holiday_national';
                $expected = 'Libur Nasional';
                $reason = $holidayName !== '' ? 'Libur Nasional: ' . $holidayName : 'Libur Nasional.';
            } elseif ($isOff && $scanCount > 0) {
                $rowStatus = 'check_again';
                $expected = 'Cek Lagi';
                $reason = 'Jadwal OFF namun ada scan.';
            } elseif ($isOff) {
                $rowStatus = 'offday';
                $expected = 'OFF';
            } elseif (!$hasScheduleTime && ($hasFirstScan || $hasLastScan)) {
                $rowStatus = 'check_again';
                $expected = 'Cek Lagi';
                $reason = 'Jadwal tidak memiliki jam tetapi ada scan.';
            } elseif (!$hasFirstScan && !$hasLastScan) {
                $rowStatus = 'missing_scan';
                $expected = 'Tidak Masuk';
                $reason = 'Tidak ada scan masuk pada tanggal jadwal.';
            } elseif ($hasFirstScan && !$hasLastScan) {
                [$evaluationExpected, $evaluationReason] = $this->evaluateCheckIn($startTime, $firstScanTime, $firstScan, $logDate);
                if ($evaluationExpected === 'Terlambat') {
                    $rowStatus = 'time_mismatch';
                    $expected = 'Terlambat';
                    $reason = $evaluationReason;
                } else {
                    $rowStatus = 'missing_checkout';
                    $expected = 'Tidak Scan pulang';
                    $reason = 'Scan pulang tidak ditemukan.';
                }
            } elseif (!$hasFirstScan && $hasLastScan) {
                $rowStatus = 'missing_checkin';
                $expected = 'Tidak Scan masuk';
                $reason = 'Scan masuk tidak ditemukan.';
            } elseif ($timeMatched) {
                $rowStatus = 'matched';
                $expected = 'On Time';
                $reason = 'Jam masuk sesuai jadwal.';
            } else {
                $rowStatus = 'time_mismatch';
                [$expected, $reason] = $this->evaluateCheckIn($startTime, $firstScanTime, $firstScan, $logDate);
            }

            $hasOvertime = $this->hasOvertime($logDate, $startTime, $endTime, $firstScan, $lastScan, $isOff, $isNationalHoliday);
            $overtimeMinutes = $this->overtimeMinutes($logDate, $startTime, $endTime, $firstScan, $lastScan, $isOff, $isNationalHoliday);
            $overtimeLabel = $this->overtimeLabel($logDate, $startTime, $endTime, $firstScan, $lastScan, $isOff, $isNationalHoliday);

            $leaveType = $approvedLeaveTypeByPinDate[$pin][$logDate] ?? null;
            if ($leaveType !== null) {
                $rowStatus = (string) $leaveType;
                $expected = $this->leaveTypeLabel((string) $leaveType);
                $reason = 'Leave/permission disetujui.';
                $hasOvertime = false;
                $overtimeMinutes = 0;
                $overtimeLabel = '-';
            }

            $rows->push([
                'log_date' => $logDate,
                'pin' => $this->resolveAttendanceDisplayPin(
                    (string) ($row->pin ?? ''),
                    $employeeInfoByPin->get($pin),
                    $scans
                ),
                'name' => $this->resolveAttendanceDisplayName(
                    $employeeInfoByPin->get($pin),
                    (string) ($row->roster_name ?? ''),
                    (string) ($scans->last()->name ?? ''),
                ),
                'department_name' => (string) (($employeeInfoByPin->get($pin)['department_name'] ?? '')),
                'roster_name' => $row->roster_name,
                'fingerprint_name' => $scans->last()->name ?? null,
                'shift_code' => $row->shift_code,
                'is_off' => $isOff,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'first_scan' => $firstScan,
                'last_scan' => $lastScan,
                'scan_time' => $firstScanTime,
                'scan_count' => $scanCount,
                'status' => $rowStatus,
                'expected' => $expected,
                'reason' => $reason,
                'leave_request' => $approvedLeaveDetailsByPinDate[$pin][$logDate] ?? null,
                'has_overtime' => $hasOvertime,
                'overtime_minutes' => $overtimeMinutes,
                'overtime_label' => $overtimeLabel,
                'overtime_form_label' => $overtimeFormByPinDate[$pin][$logDate] ?? '-',
                'overtime_request' => $overtimeDetailsByPinDate[$pin][$logDate] ?? null,
                'correction' => $this->formatCorrection($correction),
            ]);
        }

        $unmatchedByPinDate = [];
        foreach ($scanGroups as $key => $scans) {
            if (isset($rosterKeys[$key])) {
                continue;
            }

            [$logDate, $pin] = explode('|', $key, 2);
            $normalizedPin = $this->normalizePin($pin);
            if (!isset($unmatchedByPinDate[$normalizedPin])) {
                $unmatchedByPinDate[$normalizedPin] = [];
            }
            $unmatchedByPinDate[$normalizedPin][$logDate] = $scans->sortBy('scan_date')->values();
        }

        // Also include historical fingerprint PINs even when selected month has no scans,
        // so "Tanpa Roster" can still be shown for that period.
        $historicalPins = DB::table('fingerprints')
            ->whereNotNull('pin')
            ->where('pin', '<>', '')
            ->distinct()
            ->pluck('pin')
            ->map(fn($pin) => $this->normalizePin((string) $pin))
            ->filter()
            ->unique()
            ->values();

        foreach ($historicalPins as $historicalPin) {
            $pin = (string) $historicalPin;
            if ($pin === '') {
                continue;
            }

            if ($this->hasRosterPinMatch($pin, $rosterPinIndex)) {
                continue;
            }

            if (!isset($unmatchedByPinDate[$pin])) {
                $unmatchedByPinDate[$pin] = [];
            }
        }

        foreach ($unmatchedByPinDate as $pin => $dateMap) {
            // If this PIN can still be matched with roster using normalized/legacy variants,
            // do not generate fallback no-roster rows.
            if ($this->hasRosterPinMatch($pin, $rosterPinIndex)) {
                continue;
            }

            $monthKeys = collect(array_keys($dateMap))
                ->map(fn($date) => Carbon::parse($date)->format('Y-m'))
                ->unique()
                ->values()
                ->all();

            if ($dateFrom !== null && $dateTo !== null) {
                $monthKeys = $this->buildMonthKeysWithinDateRange($dateFrom, $dateTo);
            } elseif (empty($monthKeys) && $year !== null && $month !== null) {
                $monthKeys = [sprintf('%04d-%02d', $year, $month)];
            }

            foreach ($monthKeys as $monthKey) {
                $monthStart = Carbon::createFromFormat('Y-m-d', $monthKey . '-01');
                $daysInMonth = $monthStart->daysInMonth;
                $fingerprintName = collect($dateMap)
                    ->flatten(1)
                    ->sortByDesc('scan_date')
                    ->map(fn($scan) => trim((string) ($scan->name ?? '')))
                    ->first(fn($name) => $name !== '');
                $displayName = $this->resolveAttendanceDisplayName(
                    $employeeInfoByPin->get($pin),
                    null,
                    (string) ($fingerprintName ?? ''),
                );

                $employeeInfo = $employeeInfoByPin->get($pin);
                $displayPin = $this->resolveAttendanceDisplayPin(null, $employeeInfo, collect($dateMap)->flatten(1), $pin);
                $departmentName = trim((string) (($employeeInfo['department_name'] ?? '')));

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $logDate = $monthStart->copy()->day($day)->format('Y-m-d');
                    ['start_time' => $defaultStartTime, 'end_time' => $defaultEndTime, 'next_day_start_time' => $defaultNextDayStartTime, 'label' => $defaultScheduleLabel] = $this->resolveNoRosterSchedule($logDate, $displayPin, $departmentName);
                    $lookupDates = $this->scanLookupDatesForSchedule($logDate, $defaultStartTime, $defaultEndTime, $defaultNextDayStartTime);
                    if ($this->usesFlexibleT2PNoRosterSchedule($displayPin)) {
                        $lookupDates = $this->resolveFlexibleT2PScanLookupDates($logDate);
                    }
                    $scans = collect();
                    foreach ($this->pinCandidatesForMatch($pin) as $candidatePin) {
                        foreach ($lookupDates as $lookupDate) {
                            $scans = $scans->merge($scanGroups->get($lookupDate . '|' . $candidatePin, collect()));
                        }
                    }
                    $scans = $scans->sortBy('scan_date')->values();

                    $scanCount = $scans->count();
                    if ($this->usesFlexibleT2PNoRosterSchedule($displayPin)) {
                        [$firstScan, $lastScan] = $this->resolveFlexibleT2PNoRosterScanWindow($scans, $logDate);
                    } else {
                        [$firstScan, $lastScan] = $this->resolveScanWindow($scans, $defaultStartTime, $defaultEndTime, $logDate, $defaultNextDayStartTime);
                    }
                    $correction = $correctionMap->get($logDate . '|' . $pin);
                    if ($correction && $correction->status === 'approved') {
                        if ($correction->corrected_first_scan !== null) {
                            $firstScan = $correction->corrected_first_scan->format('Y-m-d H:i:s');
                        }
                        if ($correction->corrected_last_scan !== null) {
                            $lastScan = $correction->corrected_last_scan->format('Y-m-d H:i:s');
                        }
                    }
                    $firstScanTime = $this->normalizeTime($firstScan);
                    $lastScanTime = $this->normalizeTime($lastScan);
                    if ($this->usesFlexibleT2PNoRosterSchedule($displayPin)) {
                        ['start_time' => $defaultStartTime, 'end_time' => $defaultEndTime, 'next_day_start_time' => $defaultNextDayStartTime, 'label' => $defaultScheduleLabel] = $this->resolveFlexibleT2PNoRosterSchedule($firstScan, $lastScan);
                    }
                    $hasFirstScan = $firstScan !== null;
                    $hasLastScan = $lastScan !== null;
                    $isSunday = Carbon::parse($logDate)->isSunday();
                    $ignoreSundayOff = $this->usesT2PNoRosterSchedule($displayPin);
                    [$isNationalHoliday, $holidayName] = $this->resolveHolidayForPin(
                        $holidaysByDate,
                        $logDate,
                        $pin,
                        $pinWorkGroupMap
                    );

                    $rowStatus = 'no_roster';
                    $expected = 'Cek Lagi';
                    $reason = 'Tidak ada roster, jadwal default ' . $defaultScheduleLabel . '.';
                    if ($this->usesFlexibleT2PNoRosterSchedule($displayPin)) {
                        $reason = 'Tidak ada roster, jadwal T2P security mengikuti scan dengan acuan 12 jam.';
                    } elseif ($this->usesFixedT2PNoRosterSchedule($displayPin)) {
                        $reason = 'Tidak ada roster, jadwal T2P241201001 tetap 08:00 - 16:00.';
                    }

                    if ($isNationalHoliday) {
                        $rowStatus = 'holiday_national';
                        $expected = 'Libur Nasional';
                        $reason = $holidayName !== '' ? 'Libur Nasional: ' . $holidayName : 'Libur Nasional.';
                    } elseif ($isSunday && !$ignoreSundayOff) {
                        $rowStatus = 'offday';
                        $expected = 'OFF';
                        $reason = 'Hari Minggu tanpa roster dianggap OFF.';
                    } elseif (!$hasFirstScan && !$hasLastScan) {
                        $rowStatus = 'missing_scan';
                        $expected = 'Tidak Masuk';
                        $reason = 'Tidak ada scan masuk pada tanggal jadwal.';
                    } elseif ($hasFirstScan && !$hasLastScan) {
                        [$evaluationExpected, $evaluationReason] = $this->evaluateCheckIn($defaultStartTime, $firstScanTime, $firstScan, $logDate);
                        if ($evaluationExpected === 'Terlambat') {
                            $rowStatus = 'time_mismatch';
                            $expected = 'Terlambat';
                            $reason = $evaluationReason;
                        } else {
                            $rowStatus = 'missing_checkout';
                            $expected = 'Tidak Scan pulang';
                            $reason = 'Scan pulang tidak ditemukan.';
                        }
                    } elseif (!$hasFirstScan && $hasLastScan) {
                        $rowStatus = 'missing_checkin';
                        $expected = 'Tidak Scan masuk';
                        $reason = 'Scan masuk tidak ditemukan.';
                    } else {
                        [$evaluationExpected, $evaluationReason] = $this->evaluateCheckIn($defaultStartTime, $firstScanTime, $firstScan, $logDate);
                        if ($evaluationExpected === 'Terlambat') {
                            $rowStatus = 'time_mismatch';
                            $expected = 'Terlambat';
                            $reason = $evaluationReason;
                        } elseif ($evaluationExpected === 'On Time') {
                            $rowStatus = 'matched';
                            $expected = 'On Time';
                            $reason = $evaluationReason;
                        }
                    }

                    $hasOvertime = $this->hasOvertime($logDate, $defaultStartTime, $defaultEndTime, $firstScan, $lastScan, $isSunday, $isNationalHoliday);
                    $overtimeMinutes = $this->overtimeMinutes($logDate, $defaultStartTime, $defaultEndTime, $firstScan, $lastScan, $isSunday, $isNationalHoliday);
                    $overtimeLabel = $this->overtimeLabel($logDate, $defaultStartTime, $defaultEndTime, $firstScan, $lastScan, $isSunday, $isNationalHoliday);

                    $leaveType = $approvedLeaveTypeByPinDate[$pin][$logDate] ?? null;
                    if ($leaveType !== null) {
                        $rowStatus = (string) $leaveType;
                        $expected = $this->leaveTypeLabel((string) $leaveType);
                        $reason = 'Leave/permission disetujui.';
                        $hasOvertime = false;
                        $overtimeMinutes = 0;
                        $overtimeLabel = '-';
                    }

                    $rows->push([
                        'log_date' => $logDate,
                        'pin' => $this->resolveAttendanceDisplayPin(null, $employeeInfo, $scans, $displayPin),
                        'name' => $displayName,
                        'department_name' => $departmentName,
                        'roster_name' => null,
                        'fingerprint_name' => $fingerprintName ?: null,
                        'shift_code' => $this->usesT2PNoRosterSchedule($displayPin)
                            ? $this->resolveFlexibleT2PShiftCode($firstScan, $lastScan)
                            : null,
                        'is_off' => false,
                        'start_time' => $defaultStartTime,
                        'end_time' => $defaultEndTime,
                        'first_scan' => $firstScan,
                        'last_scan' => $lastScan,
                        'scan_time' => $firstScanTime,
                        'scan_count' => $scanCount,
                        'status' => $rowStatus,
                        'expected' => $expected,
                        'reason' => $reason,
                        'leave_request' => $approvedLeaveDetailsByPinDate[$pin][$logDate] ?? null,
                        'has_overtime' => $hasOvertime,
                        'overtime_minutes' => $overtimeMinutes,
                        'overtime_label' => $overtimeLabel,
                        'overtime_form_label' => $overtimeFormByPinDate[$pin][$logDate] ?? '-',
                        'overtime_request' => $overtimeDetailsByPinDate[$pin][$logDate] ?? null,
                        'correction' => $this->formatCorrection($correction),
                    ]);
                }
            }
        }

        if ($employeeStatusByPin->isNotEmpty()) {
            $rows = $rows->filter(function (array $row) use ($employeeStatusByPin) {
                $pin = $this->normalizePin((string) ($row['pin'] ?? ''));
                if ($pin === '') {
                    return true;
                }

                $status = (string) ($employeeStatusByPin->get($pin) ?? 'active');
                $isNonActive = $status !== '' && $status !== 'active';
                return !$isNonActive;
            })->values();
        }

        if ($dateFrom !== null || $dateTo !== null) {
            $rows = $rows->filter(function (array $row) use ($dateFrom, $dateTo) {
                $logDate = $this->toDateString($row['log_date'] ?? null);
                if ($logDate === null) {
                    return false;
                }
                if ($dateFrom !== null && $logDate < $dateFrom) {
                    return false;
                }
                if ($dateTo !== null && $logDate > $dateTo) {
                    return false;
                }
                return true;
            })->values();
        }

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = $rows->filter(function (array $row) use ($needle) {
                return str_contains(mb_strtolower((string) $row['pin']), $needle)
                    || str_contains(mb_strtolower((string) $row['name']), $needle);
            })->values();
        }

        if (!empty($selectedPins) || !empty($selectedNames) || !empty($selectedDepartments)) {
            $pinLookup = array_fill_keys($selectedPins, true);
            $nameLookup = array_fill_keys($selectedNames, true);
            $departmentLookup = array_fill_keys($selectedDepartments, true);

            $rows = $rows->filter(function (array $row) use ($pinLookup, $nameLookup, $departmentLookup) {
                $pin = trim((string) ($row['pin'] ?? ''));
                $name = trim((string) ($row['name'] ?? ''));
                $departmentName = trim((string) ($row['department_name'] ?? ''));

                if (!empty($pinLookup) && !isset($pinLookup[$pin])) {
                    return false;
                }

                if (!empty($nameLookup) && !isset($nameLookup[$name])) {
                    return false;
                }

                if (!empty($departmentLookup) && !isset($departmentLookup[$departmentName])) {
                    return false;
                }

                return true;
            })->values();
        }

        if ($forcedDepartmentName !== null) {
            $rows = $rows->filter(function (array $row) use ($forcedDepartmentName) {
                return $this->departmentMatches((string) ($row['department_name'] ?? ''), $forcedDepartmentName);
            })->values();
        }

        $rows = $rows->map(function (array $row) {
            $displayPin = trim((string) ($row['pin'] ?? ''));
            if (!$this->usesT2PNoRosterSchedule($displayPin)) {
                return $row;
            }

            $expected = trim((string) ($row['expected'] ?? ''));
            if (mb_strtolower($expected) !== 'tidak masuk') {
                return $row;
            }

            $row['status'] = 'offday';
            $row['expected'] = 'OFF';
            $row['reason'] = 'PIN T2P tanpa scan ditampilkan sebagai OFF.';

            return $row;
        })->values();

        $summaryRows = $rows;

        if ($status !== 'all') {
            $rows = $rows->filter(function (array $row) use ($status) {
                $expected = mb_strtolower(trim((string) ($row['expected'] ?? '')));
                $hasNoRosterSource = trim((string) ($row['roster_name'] ?? '')) === '';

                return match ($status) {
                    'on_time' => $expected === 'on time',
                    'terlambat' => $expected === 'terlambat',
                    'tidak_masuk' => $expected === 'tidak masuk',
                    'tidak_scan_masuk' => $expected === 'tidak scan masuk',
                    'tidak_scan_pulang' => $expected === 'tidak scan pulang',
                    'off' => $expected === 'off',
                    'libur_nasional' => $expected === 'libur nasional',
                    'cek_lagi' => $expected === 'cek lagi',
                    'no_roster' => $row['status'] === 'no_roster' || $hasNoRosterSource,
                    default => $row['status'] === $status,
                };
            })->values();
        }

        $rows = $rows
            ->sortBy([
                ['name', 'asc'],
                ['log_date', 'asc'],
                ['pin', 'asc'],
            ])
            ->values();

        if ($request->boolean('export')) {
            if ($this->shouldUseSecurityIndividualExport($selectedDepartments)) {
                return $this->exportSecurityRowsToPdf($rows, $dateFrom, $dateTo);
            }

            return $this->exportRowsToExcel($rows, $dateFrom, $dateTo);
        }

        $expectedCounts = $summaryRows
            ->groupBy(function (array $row) {
                $label = trim((string) ($row['expected'] ?? ''));
                return $label !== '' ? $label : '-';
            })
            ->map(fn(Collection $group) => $group->count())
            ->all();

        $summary = [
            'total' => $summaryRows->count(),
            'on_time' => (int) ($expectedCounts['On Time'] ?? 0),
            'terlambat' => (int) ($expectedCounts['Terlambat'] ?? 0),
            'tidak_masuk' => (int) ($expectedCounts['Tidak Masuk'] ?? 0),
            'tidak_scan_pulang' => (int) ($expectedCounts['Tidak Scan pulang'] ?? 0),
            'tidak_scan_masuk' => (int) ($expectedCounts['Tidak Scan masuk'] ?? 0),
            'off' => (int) ($expectedCounts['OFF'] ?? 0),
            'cek_lagi' => (int) ($expectedCounts['Cek Lagi'] ?? 0),
            'expected_counts' => $expectedCounts,
        ];

        $monthlyInsights = $this->buildMonthlyLateAbsentInsights($dateFrom, $dateTo, $forcedDepartmentName);

        $attendanceLogs = $this->paginateCollection($rows, $perPage, $request);

        return Inertia::render('GMIHR/AttendanceLog/Index', [
            'attendanceLogs' => $attendanceLogs,
            'summary' => $summary,
            'monthlyInsights' => $monthlyInsights,
            'canManageCorrections' => $canManageCorrections,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status' => $status,
                'q' => $q,
                'per_page' => $perPage,
                'pins' => $selectedPins,
                'names' => $selectedNames,
                'departments' => $selectedDepartments,
            ],
        ]);
    }

    private function normalizeAttendanceHeaderFilterInput($value): array
    {
        $items = [];

        if ($value instanceof Collection) {
            $items = $value->all();
        } elseif (is_array($value)) {
            $items = $value;
        } elseif (is_string($value) && trim($value) !== '') {
            $items = explode(',', $value);
        }

        return collect($items)
            ->map(fn($item) => trim((string) $item))
            ->filter(fn($item) => $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function shouldUseSecurityIndividualExport(array $selectedDepartments): bool
    {
        if (count($selectedDepartments) !== 1) {
            return false;
        }

        $department = strtolower(trim((string) ($selectedDepartments[0] ?? '')));

        return $department === 'security';
    }

    private function resolveForcedAttendanceDepartment(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        $user->loadMissing('department:id,name');
        $departmentName = trim((string) optional($user->department)->name);

        return $this->departmentMatches($departmentName, 'Security') ? 'Security' : null;
    }

    private function departmentMatches(?string $left, ?string $right): bool
    {
        $normalizedLeft = mb_strtolower(trim((string) ($left ?? '')));
        $normalizedRight = mb_strtolower(trim((string) ($right ?? '')));

        return $normalizedLeft !== '' && $normalizedRight !== '' && $normalizedLeft === $normalizedRight;
    }

    private function effectiveRosterCurrentBatchIds(
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $year = null,
        ?int $month = null
    ): array {
        $query = DB::table('roster_upload_batches')
            ->where('status', 'approved');

        if ($year !== null && $year > 0) {
            $query->where('year', $year);
        }

        if ($month !== null && $month > 0) {
            $query->where('month', $month);
        }

        if (($year === null || $year <= 0) && ($month === null || $month <= 0) && ($dateFrom || $dateTo)) {
            $start = $dateFrom ? Carbon::parse($dateFrom)->startOfMonth() : null;
            $end = $dateTo ? Carbon::parse($dateTo)->startOfMonth() : null;

            if ($start === null && $end !== null) {
                $start = $end->copy();
            }

            if ($end === null && $start !== null) {
                $end = $start->copy();
            }

            if ($start !== null && $end !== null) {
                if ($start->gt($end)) {
                    [$start, $end] = [$end, $start];
                }

                $periods = [];
                $cursor = $start->copy();
                while ($cursor->lte($end)) {
                    $periods[] = [
                        'year' => (int) $cursor->year,
                        'month' => (int) $cursor->month,
                    ];
                    $cursor->addMonth();
                }

                $query->where(function ($nested) use ($periods) {
                    foreach ($periods as $period) {
                        $nested->orWhere(function ($periodQuery) use ($period) {
                            $periodQuery
                                ->where('year', $period['year'])
                                ->where('month', $period['month']);
                        });
                    }
                });
            }
        }

        return $query
            ->selectRaw('department_id, year, month, MAX(id) as latest_id')
            ->groupBy('department_id', 'year', 'month')
            ->pluck('latest_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function buildMonthlyLateAbsentInsights(?string $dateFrom, ?string $dateTo, ?string $forcedDepartmentName = null): array
    {
        $now = Carbon::now();
        $minCount = 5;

        $target = $dateFrom !== null
            ? Carbon::parse($dateFrom)->startOfMonth()
            : $now->copy()->startOfMonth();

        if ($dateTo !== null) {
            try {
                $rangeEnd = Carbon::parse($dateTo)->startOfMonth();
                if (!$target->isSameMonth($rangeEnd)) {
                    $target = Carbon::parse($dateFrom ?? $dateTo)->startOfMonth();
                }
            } catch (\Throwable $e) {
                // Use target from dateFrom/current month.
            }
        }

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        [$lateCounts, $absentCounts] = $this->buildLateAbsentCountsByNameForMonth((int) $target->year, (int) $target->month, $forcedDepartmentName);

        $ym = $target->format('Y-m');
        $byMonth = [
            $ym => [
                'month_key' => $ym,
                'month_label' => ($monthNames[(int) $target->month] ?? $target->format('F')) . ' ' . $target->year,
                'late' => [
                    'events' => 0,
                    'people' => 0,
                    'top' => [],
                    'others' => 0,
                ],
                'absent' => [
                    'events' => 0,
                    'people' => 0,
                    'top' => [],
                    'others' => 0,
                ],
            ],
        ];

        $topLimit = 12;

        arsort($lateCounts);
        $lateCounts = array_filter($lateCounts, fn($count) => (int) $count >= $minCount);
        $lateEvents = array_sum($lateCounts);
        $latePeople = count($lateCounts);
        $lateTop = array_slice($lateCounts, 0, $topLimit, true);
        $byMonth[$ym]['late']['events'] = (int) $lateEvents;
        $byMonth[$ym]['late']['people'] = (int) $latePeople;
        $byMonth[$ym]['late']['top'] = collect($lateTop)->map(function ($count, $name) {
            return ['name' => (string) $name, 'count' => (int) $count];
        })->values()->all();
        $byMonth[$ym]['late']['others'] = max(0, $latePeople - count($lateTop));

        arsort($absentCounts);
        $absentCounts = array_filter($absentCounts, fn($count) => (int) $count >= $minCount);
        $absentEvents = array_sum($absentCounts);
        $absentPeople = count($absentCounts);
        $absentTop = array_slice($absentCounts, 0, $topLimit, true);
        $byMonth[$ym]['absent']['events'] = (int) $absentEvents;
        $byMonth[$ym]['absent']['people'] = (int) $absentPeople;
        $byMonth[$ym]['absent']['top'] = collect($absentTop)->map(function ($count, $name) {
            return ['name' => (string) $name, 'count' => (int) $count];
        })->values()->all();
        $byMonth[$ym]['absent']['others'] = max(0, $absentPeople - count($absentTop));

        return [
            'range_start' => $target->format('Y-m-01'),
            'range_end' => $target->copy()->endOfMonth()->format('Y-m-d'),
            'min_count' => $minCount,
            'months' => array_values($byMonth),
        ];
    }

    private function buildLateAbsentCountsByNameForMonth(int $year, int $month, ?string $forcedDepartmentName = null): array
    {
        [$scanRangeStart, $scanRangeEnd] = $this->resolveScanDateRange($year, $month);

        $corrections = AttendanceLogCorrection::query()
            ->whereYear('log_date', $year)
            ->whereMonth('log_date', $month)
            ->get();

        $correctionMap = $corrections->keyBy(function (AttendanceLogCorrection $correction) {
            return $correction->log_date->format('Y-m-d') . '|' . $this->normalizePin((string) $correction->pin);
        });

        $scanRows = DB::table('fingerprints')
            ->when($scanRangeStart !== null, fn($query) => $query->whereDate('scan_date_only', '>=', $scanRangeStart))
            ->when($scanRangeEnd !== null, fn($query) => $query->whereDate('scan_date_only', '<=', $scanRangeEnd))
            ->whereNotNull('pin')
            ->where('pin', '<>', '')
            ->get([
                'scan_date_only as log_date',
                'pin',
                'name',
                'scan_date',
            ]);

        $scanGroups = $scanRows
            ->groupBy(fn($row) => $this->toDateString($row->log_date) . '|' . $this->normalizePin((string) $row->pin))
            ->map(fn(Collection $group) => $group->sortBy('scan_date')->values());

        $employeeNameColumns = ['nik', 'name'];
        if (Schema::hasColumn('employees', 'alias_name')) {
            $employeeNameColumns[] = 'alias_name';
        }

        $employeeNameMap = DB::table('employees')
            ->whereNotNull('nik')
            ->where('nik', '<>', '')
            ->get($employeeNameColumns)
            ->mapWithKeys(function ($row) {
                $nik = (string) ($row->nik ?? '');
                $pin = $this->normalizePin((string) $nik);
                if ($pin === '') {
                    return [];
                }
                $aliasName = trim((string) ($row->alias_name ?? ''));
                $name = trim((string) ($row->name ?? ''));
                return [$pin => ($aliasName !== '' ? $aliasName : $name)];
            });

        $employeeDepartmentByPin = DB::table('employees as e')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->whereNotNull('e.nik')
            ->where('e.nik', '<>', '')
            ->get(['e.nik', 'd.name as department_name'])
            ->mapWithKeys(function ($row) {
                $pin = $this->normalizePin((string) ($row->nik ?? ''));
                if ($pin === '') {
                    return [];
                }

                return [$pin => trim((string) ($row->department_name ?? ''))];
            });

        $effectiveCurrentBatchIds = $this->effectiveRosterCurrentBatchIds(null, null, $year, $month);

        $rosterRows = DB::table('roster_entries as re')
            ->join('roster_upload_batches as rub', 'rub.id', '=', 're.batch_id')
            ->whereYear('re.roster_date', $year)
            ->whereMonth('re.roster_date', $month)
            ->where('rub.status', 'approved')
            ->when(!empty($effectiveCurrentBatchIds), function ($query) use ($effectiveCurrentBatchIds) {
                $query->whereIn('rub.id', $effectiveCurrentBatchIds);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->whereNotNull('re.employee_nrp')
            ->where('re.employee_nrp', '<>', '')
            ->get([
                're.roster_date as log_date',
                're.employee_nrp as pin',
                're.employee_name as roster_name',
                're.shift_code',
                're.is_off',
                're.start_time',
                're.end_time',
            ]);

        $rosterScheduleIndex = $this->buildRosterScheduleIndex($rosterRows);

        $pinsInScope = $scanRows
            ->pluck('pin')
            ->merge($rosterRows->pluck('pin'))
            ->map(fn($pin) => $this->normalizePin((string) $pin))
            ->filter()
            ->unique()
            ->values();

        $approvedLeaveTypeByPinDate = $this->buildApprovedLeaveTypesByPinDate($year, $month, $pinsInScope);

        $holidaysByDate = DB::table('attendance_holidays')
            ->whereYear('holiday_date', $year)
            ->whereMonth('holiday_date', $month)
            ->get(['holiday_date', 'name', 'scope_type'])
            ->groupBy(function ($row) {
                $date = $this->toDateString($row->holiday_date);
                return $date ?? '__invalid__';
            });

        if ($holidaysByDate->has('__invalid__')) {
            $holidaysByDate->forget('__invalid__');
        }

        // Work group map used for holiday scoping (office/operational).
        $pinWorkGroupMap = DB::table('employees')
            ->whereNotNull('nik')
            ->where('nik', '<>', '')
            ->whereNotNull('work_group')
            ->where('work_group', '<>', '')
            ->pluck('work_group', 'nik')
            ->mapWithKeys(function ($group, $nik) {
                $pin = $this->normalizePin((string) $nik);
                if ($pin === '') {
                    return [];
                }
                $normalized = strtolower(trim((string) $group));
                if (!in_array($normalized, ['office', 'operational'], true)) {
                    return [];
                }
                return [$pin => $normalized];
            })
            ->all();

        $fingerprintWorkGroupFallback = DB::table('fingerprints')
            ->whereYear('scan_date_only', $year)
            ->whereMonth('scan_date_only', $month)
            ->whereNotNull('pin')
            ->where('pin', '<>', '')
            ->whereNotNull('department')
            ->where('department', '<>', '')
            ->orderBy('scan_date', 'desc')
            ->get(['pin', 'department'])
            ->reduce(function ($carry, $row) {
                $pin = $this->normalizePin((string) $row->pin);
                if ($pin === '' || isset($carry[$pin])) {
                    return $carry;
                }
                $carry[$pin] = $this->resolveWorkGroup((string) $row->department);
                return $carry;
            }, []);

        foreach ($fingerprintWorkGroupFallback as $pin => $group) {
            if (!isset($pinWorkGroupMap[$pin]) && in_array($group, ['office', 'operational'], true)) {
                $pinWorkGroupMap[$pin] = $group;
            }
        }

        $lateCounts = [];
        $absentCounts = [];

        // Build roster pin index so we can detect pins that have roster coverage (including pin variants).
        $rosterPinIndex = [];
        foreach ($rosterRows as $row) {
            $pin = $this->normalizePin((string) $row->pin);
            foreach ($this->pinCandidatesForMatch($pin) as $candidate) {
                $rosterPinIndex[$candidate] = true;
            }
        }

        foreach ($rosterRows as $row) {
            $logDate = $this->toDateString($row->log_date);
            $pin = $this->normalizePin((string) $row->pin);
            if ($logDate === null || $pin === '') {
                continue;
            }

            if ($forcedDepartmentName !== null && !$this->departmentMatches((string) ($employeeDepartmentByPin->get($pin) ?? ''), $forcedDepartmentName)) {
                continue;
            }

            $startTime = $this->normalizeTime($row->start_time);
            $endTime = $this->normalizeTime($row->end_time);
            $isOff = (bool) ($row->is_off ?? false);

            $scanLookupKeys = $this->pinCandidatesForMatch($pin);
            $nextDayStartTime = $this->resolveNextDayStartTime($rosterScheduleIndex, $pin, $logDate);
            $lookupDates = $this->scanLookupDatesForSchedule($logDate, $startTime, $endTime, $nextDayStartTime);
            $scans = collect();
            foreach ($scanLookupKeys as $lookupPin) {
                foreach ($lookupDates as $lookupDate) {
                    $scans = $scans->merge($scanGroups->get($lookupDate . '|' . $lookupPin, collect()));
                }
            }
            $scans = $scans->sortBy('scan_date')->values();
            if ($isOff) {
                $scans = $this->filterOffdayBoundaryScans($scans);
            }

            [$firstScan, $lastScan] = $this->resolveScanWindow($scans, $startTime, $endTime, $logDate, $nextDayStartTime);
            $correction = $correctionMap->get($logDate . '|' . $pin);
            if ($correction && $correction->status === 'approved') {
                if ($correction->corrected_first_scan !== null) {
                    $firstScan = $correction->corrected_first_scan->format('Y-m-d H:i:s');
                }
                if ($correction->corrected_last_scan !== null) {
                    $lastScan = $correction->corrected_last_scan->format('Y-m-d H:i:s');
                }
            }

            $firstScanTime = $this->normalizeTime($firstScan);
            $hasFirstScan = $firstScan !== null;
            $hasLastScan = $lastScan !== null;
            $hasScheduleTime = $startTime !== null && $endTime !== null;
            $timeMatched = $startTime !== null && $firstScanTime !== null && substr($startTime, 0, 5) === substr($firstScanTime, 0, 5);

            [$isNationalHoliday] = $this->resolveHolidayForPin(
                $holidaysByDate,
                $logDate,
                $pin,
                $pinWorkGroupMap
            );

            // Skip OFF/holiday days.
            if ($isNationalHoliday || $isOff) {
                continue;
            }

            // Overlay approved leave/permission, skip counting late/absent.
            $leaveType = $approvedLeaveTypeByPinDate[$pin][$logDate] ?? null;
            if ($leaveType !== null) {
                continue;
            }

            $expected = null;
            if (!$hasScheduleTime && ($hasFirstScan || $hasLastScan)) {
                $expected = 'Cek Lagi';
            } elseif (!$hasFirstScan && !$hasLastScan) {
                $expected = 'Tidak Masuk';
            } elseif ($hasFirstScan && !$hasLastScan) {
                [$evaluationExpected] = $this->evaluateCheckIn($startTime, $firstScanTime, $firstScan, $logDate);
                $expected = $evaluationExpected === 'Terlambat' ? 'Terlambat' : 'Tidak Scan pulang';
            } elseif (!$hasFirstScan && $hasLastScan) {
                $expected = 'Tidak Scan masuk';
            } elseif ($timeMatched) {
                $expected = 'On Time';
            } else {
                [$expected] = $this->evaluateCheckIn($startTime, $firstScanTime, $firstScan, $logDate);
            }

            $name = $this->resolveAttendanceDisplayName(
                ['alias_name' => trim((string) ($employeeNameMap->get($pin) ?? ''))],
                (string) ($row->roster_name ?? ''),
                (string) ($scans->last()->name ?? ''),
                ''
            );
            if ($name === '') {
                continue;
            }

            if ($expected === 'Terlambat') {
                $lateCounts[$name] = ($lateCounts[$name] ?? 0) + 1;
            } elseif ($expected === 'Tidak Masuk') {
                $absentCounts[$name] = ($absentCounts[$name] ?? 0) + 1;
            }
        }

        // Include "Tanpa Roster" evaluation (default 08:00-16:00) for pins that do not match roster at all.
        $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
        $daysInMonth = $monthStart->daysInMonth;

        $pinsNoRoster = $scanRows
            ->pluck('pin')
            ->map(fn($pin) => $this->normalizePin((string) $pin))
            ->filter()
            ->unique()
            ->values()
            ->filter(function ($pin) use ($rosterPinIndex) {
                return !$this->hasRosterPinMatch((string) $pin, $rosterPinIndex);
            })
            ->values();

        foreach ($pinsNoRoster as $pinValue) {
            $pin = (string) $pinValue;
            if ($pin === '') {
                continue;
            }

            if ($forcedDepartmentName !== null && !$this->departmentMatches((string) ($employeeDepartmentByPin->get($pin) ?? ''), $forcedDepartmentName)) {
                continue;
            }

            $pinScans = $scanRows
                ->filter(fn($row) => $this->normalizePin((string) $row->pin) === $pin)
                ->sortByDesc('scan_date');
            $displayPin = $this->resolveAttendanceDisplayPin(null, null, $pinScans, $pin);
            $fingerprintName = $pinScans
                ->map(fn($row) => trim((string) ($row->name ?? '')))
                ->first(fn($name) => $name !== '');
            $displayName = $this->resolveAttendanceDisplayName(
                ['alias_name' => trim((string) ($employeeNameMap->get($pin) ?? ''))],
                null,
                (string) ($fingerprintName ?? ''),
            );

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $logDate = $monthStart->copy()->day($day)->format('Y-m-d');
                $isSunday = Carbon::parse($logDate)->isSunday();

                [$isNationalHoliday] = $this->resolveHolidayForPin(
                    $holidaysByDate,
                    $logDate,
                    $pin,
                    $pinWorkGroupMap
                );

                if ($isNationalHoliday || ($isSunday && !$this->usesT2PNoRosterSchedule($displayPin))) {
                    continue;
                }

                // Overlay approved leave/permission, do not count late/absent.
                $leaveType = $approvedLeaveTypeByPinDate[$pin][$logDate] ?? null;
                if ($leaveType !== null) {
                    continue;
                }

                // Merge scans across known pin variants.
                ['start_time' => $defaultStartTime, 'end_time' => $defaultEndTime, 'next_day_start_time' => $defaultNextDayStartTime] = $this->resolveNoRosterSchedule($logDate, $displayPin);
                $scans = collect();
                $lookupDates = $this->scanLookupDatesForSchedule($logDate, $defaultStartTime, $defaultEndTime, $defaultNextDayStartTime);
                if ($this->usesFlexibleT2PNoRosterSchedule($displayPin)) {
                    $lookupDates = $this->resolveFlexibleT2PScanLookupDates($logDate);
                }
                foreach ($this->pinCandidatesForMatch($pin) as $candidatePin) {
                    foreach ($lookupDates as $lookupDate) {
                        $scans = $scans->merge($scanGroups->get($lookupDate . '|' . $candidatePin, collect()));
                    }
                }
                $scans = $scans->sortBy('scan_date')->values();

                if ($this->usesFlexibleT2PNoRosterSchedule($displayPin)) {
                    [$firstScan, $lastScan] = $this->resolveFlexibleT2PNoRosterScanWindow($scans, $logDate);
                } else {
                    [$firstScan, $lastScan] = $this->resolveScanWindow($scans, $defaultStartTime, $defaultEndTime, $logDate, $defaultNextDayStartTime);
                }
                $correction = $correctionMap->get($logDate . '|' . $pin);
                if ($correction && $correction->status === 'approved') {
                    if ($correction->corrected_first_scan !== null) {
                        $firstScan = $correction->corrected_first_scan->format('Y-m-d H:i:s');
                    }
                    if ($correction->corrected_last_scan !== null) {
                        $lastScan = $correction->corrected_last_scan->format('Y-m-d H:i:s');
                    }
                }

                $firstScanTime = $this->normalizeTime($firstScan);
                if ($this->usesFlexibleT2PNoRosterSchedule($displayPin)) {
                    ['start_time' => $defaultStartTime, 'end_time' => $defaultEndTime] = $this->resolveFlexibleT2PNoRosterSchedule($firstScan, $lastScan);
                }
                $hasFirstScan = $firstScan !== null;
                $hasLastScan = $lastScan !== null;

                $expected = null;
                if (!$hasFirstScan && !$hasLastScan) {
                    $expected = 'Tidak Masuk';
                } elseif ($hasFirstScan && !$hasLastScan) {
                    [$evaluationExpected] = $this->evaluateCheckIn($defaultStartTime, $firstScanTime, $firstScan, $logDate);
                    $expected = $evaluationExpected === 'Terlambat' ? 'Terlambat' : 'Tidak Scan pulang';
                } elseif (!$hasFirstScan && $hasLastScan) {
                    $expected = 'Tidak Scan masuk';
                } else {
                    [$expected] = $this->evaluateCheckIn($defaultStartTime, $firstScanTime, $firstScan, $logDate);
                }

                if ($expected === 'Terlambat') {
                    $lateCounts[$displayName] = ($lateCounts[$displayName] ?? 0) + 1;
                } elseif ($expected === 'Tidak Masuk') {
                    $absentCounts[$displayName] = ($absentCounts[$displayName] ?? 0) + 1;
                }
            }
        }

        return [$lateCounts, $absentCounts];
    }

    private function resolveAttendanceDisplayName(
        array|null $employeeInfo,
        ?string $rosterName,
        ?string $fingerprintName,
        string $fallback = '-'
    ): string {
        $aliasName = trim((string) ($employeeInfo['alias_name'] ?? ''));
        if ($aliasName !== '') {
            return $aliasName;
        }

        $employeeName = trim((string) ($employeeInfo['name'] ?? ''));
        if ($employeeName !== '') {
            return $employeeName;
        }

        $rosterName = trim((string) ($rosterName ?? ''));
        if ($rosterName !== '') {
            return $rosterName;
        }

        $fingerprintName = trim((string) ($fingerprintName ?? ''));
        if ($fingerprintName !== '') {
            return $fingerprintName;
        }

        return $fallback;
    }

    private function resolveAttendanceDisplayPin(
        ?string $preferredPin,
        array|null $employeeInfo,
        Collection $scans,
        string $fallback = '-'
    ): string {
        $preferredPin = trim((string) ($preferredPin ?? ''));
        if ($preferredPin !== '') {
            return $preferredPin;
        }

        $scanPin = $scans
            ->sortByDesc('scan_date')
            ->map(fn($scan) => trim((string) ($scan->pin ?? '')))
            ->first(fn($pin) => $pin !== '');
        if (!empty($scanPin)) {
            return (string) $scanPin;
        }

        $employeeNik = trim((string) ($employeeInfo['nik'] ?? ''));
        if ($employeeNik !== '') {
            return $employeeNik;
        }

        return $fallback;
    }

    public function storeCorrection(Request $request)
    {
        if (!$this->canManageCorrections($request->user())) {
            abort(403, 'Hanya IT/HRD yang dapat melakukan koreksi attendance.');
        }

        $validated = $request->validate([
            'log_date' => ['required', 'date'],
            'pin' => ['required'],
            'start_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'end_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'corrected_first_time' => ['nullable', 'date_format:H:i'],
            'corrected_last_time' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (empty($validated['corrected_first_time']) && empty($validated['corrected_last_time'])) {
            return redirect()->back()->withErrors([
                'corrected_time' => 'Isi minimal salah satu jam koreksi: masuk atau pulang.',
            ]);
        }

        $pin = $this->normalizePin((string) $validated['pin']);
        if ($pin === '' || strlen($pin) > 32) {
            return redirect()->back()->withErrors([
                'pin' => 'PIN tidak valid.',
            ]);
        }

        $logDate = Carbon::parse($validated['log_date'])->format('Y-m-d');
        $startTime = $this->normalizeScheduleTimeInput($validated['start_time'] ?? null);
        $endTime = $this->normalizeScheduleTimeInput($validated['end_time'] ?? null);

        $firstScan = $this->buildCorrectionDateTime($logDate, $validated['corrected_first_time'] ?? null, $startTime, $endTime, false);
        $lastScan = $this->buildCorrectionDateTime($logDate, $validated['corrected_last_time'] ?? null, $startTime, $endTime, true);

        AttendanceLogCorrection::updateOrCreate(
            [
                'log_date' => $logDate,
                'pin' => $pin,
            ],
            [
                'corrected_first_scan' => $firstScan,
                'corrected_last_scan' => $lastScan,
                'note' => $validated['note'] ?? null,
                'status' => 'approved',
                'requested_by' => optional($request->user())->id,
                'approved_by' => optional($request->user())->id,
                'approved_at' => now(),
                'rejected_at' => null,
                'rejection_reason' => null,
            ]
        );

        return redirect()->back()->with('success', 'Koreksi attendance berhasil disimpan dan langsung diterapkan.');
    }

    public function approveCorrection(Request $request, AttendanceLogCorrection $correction)
    {
        if (!$this->canManageCorrections($request->user())) {
            abort(403, 'Hanya IT/HRD yang dapat menyetujui koreksi attendance.');
        }

        $correction->update([
            'status' => 'approved',
            'approved_by' => optional($request->user())->id,
            'approved_at' => now(),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        return redirect()->back()->with('success', 'Koreksi attendance disetujui.');
    }

    public function rejectCorrection(Request $request, AttendanceLogCorrection $correction)
    {
        if (!$this->canManageCorrections($request->user())) {
            abort(403, 'Hanya IT/HRD yang dapat menolak koreksi attendance.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $correction->update([
            'status' => 'rejected',
            'approved_by' => optional($request->user())->id,
            'approved_at' => null,
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Koreksi attendance ditolak.');
    }

    private function resolveScanWindow(
        Collection $scans,
        ?string $startTime,
        ?string $endTime,
        ?string $logDate = null,
        ?string $nextDayStartTime = null
    ): array
    {
        if (self::USE_SCHEDULE_WINDOWS && $logDate !== null && $startTime !== null && $endTime !== null) {
            return $this->resolveByScheduleWindows($scans, $startTime, $endTime, $logDate, $nextDayStartTime);
        }

        return $this->resolveScanWindowLegacy($scans, $startTime, $endTime, $logDate);
    }

    private function resolveByScheduleWindows(
        Collection $scans,
        string $startTime,
        string $endTime,
        string $logDate,
        ?string $nextDayStartTime = null
    ): array
    {
        if ($scans->isEmpty()) {
            return [null, null];
        }

        try {
            $sorted = $scans->sortBy('scan_date')->values();

            $anchorStart = Carbon::parse($logDate . ' ' . $startTime);
            $anchorEnd = Carbon::parse($logDate . ' ' . $endTime);
            if ($this->isOvernightShift($startTime, $endTime)) {
                $anchorEnd->addDay();
            }

            $inFrom = $anchorStart->copy()->subHours(2);
            $inTo = $anchorEnd->copy();
            $outFrom = $anchorEnd->copy();
            $outTo = $this->resolveCheckoutWindowEnd($logDate, $startTime, $endTime, $nextDayStartTime);
            $sessionScans = $sorted->filter(function ($scan) use ($inFrom, $outTo) {
                try {
                    $scanAt = Carbon::parse($scan->scan_date);
                    return $scanAt->greaterThanOrEqualTo($inFrom) && $scanAt->lt($outTo);
                } catch (\Throwable $e) {
                    return false;
                }
            })->sortBy('scan_date')->values();

            $checkinCandidates = $sorted->filter(function ($scan) use ($inFrom, $inTo) {
                try {
                    $scanAt = Carbon::parse($scan->scan_date);
                    return $scanAt->greaterThanOrEqualTo($inFrom) && $scanAt->lt($inTo);
                } catch (\Throwable $e) {
                    return false;
                }
            })->sortBy('scan_date')->values();

            $checkoutCandidates = $sorted->filter(function ($scan) use ($outFrom, $outTo) {
                try {
                    $scanAt = Carbon::parse($scan->scan_date);
                    return $scanAt->greaterThanOrEqualTo($outFrom) && $scanAt->lt($outTo);
                } catch (\Throwable $e) {
                    return false;
                }
            })->sortByDesc('scan_date')->values();

            if ($sessionScans->count() >= 2) {
                return [
                    $this->toDateTimeString(optional($sessionScans->first())->scan_date ?? null),
                    $this->toDateTimeString(optional($sessionScans->last())->scan_date ?? null),
                ];
            }

            if ($sessionScans->count() === 1) {
                $onlyScan = $sessionScans->first();
                $onlyScanDateTime = $this->toDateTimeString(optional($onlyScan)->scan_date ?? null);
                $onlyScanTime = $this->normalizeTime(optional($onlyScan)->scan_date ?? null);

                if ($this->isLikelyCheckoutTime($onlyScanTime, $startTime, $endTime)) {
                    return [null, $onlyScanDateTime];
                }

                return [$onlyScanDateTime, null];
            }

            $checkinScan = $checkinCandidates->first();
            $checkoutScan = $checkoutCandidates->first();
            if ($checkinScan !== null && $checkoutScan === null) {
                $checkoutScan = $this->resolveFallbackCheckoutScan($sorted, $checkinScan, $outTo);
            }

            return [
                $this->toDateTimeString(optional($checkinScan)->scan_date ?? null),
                $this->toDateTimeString(optional($checkoutScan)->scan_date ?? null),
            ];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }

    private function resolveFallbackCheckoutScan(Collection $sortedScans, $checkinScan, ?Carbon $windowEnd = null)
    {
        $checkinDateTime = $this->toDateTimeString(optional($checkinScan)->scan_date ?? null);
        if ($checkinDateTime === null) {
            return null;
        }

        $otherScans = $sortedScans
            ->filter(fn ($scan) => $this->toDateTimeString($scan->scan_date ?? null) !== $checkinDateTime)
            ->values();

        if ($otherScans->isEmpty()) {
            return null;
        }

        $laterScan = $otherScans
            ->filter(function ($scan) use ($checkinDateTime, $windowEnd) {
                $scanDateTime = $this->toDateTimeString($scan->scan_date ?? null);
                if ($scanDateTime === null || $scanDateTime <= $checkinDateTime) {
                    return false;
                }

                if ($windowEnd === null) {
                    return true;
                }

                try {
                    return Carbon::parse($scanDateTime)->lt($windowEnd);
                } catch (\Throwable $e) {
                    return false;
                }
            })
            ->sortByDesc('scan_date')
            ->first();

        if ($laterScan !== null) {
            return $laterScan;
        }

        if ($windowEnd !== null) {
            return null;
        }

        return $otherScans->sortBy('scan_date')->first();
    }

    private function resolveScanWindowLegacy(Collection $scans, ?string $startTime, ?string $endTime, ?string $logDate = null): array
    {
        if ($scans->isEmpty()) {
            return [null, null];
        }

        $sorted = $scans->sortBy('scan_date')->values();
        if ($this->requiresPreviousDayWindow($startTime, $endTime) && $logDate !== null) {
            return $this->resolveEarlyStartWindow($sorted, $startTime, $endTime, $logDate);
        }

        if ($sorted->count() === 1) {
            $only = $sorted->first();
            $onlyTime = $this->normalizeTime($only->scan_date);
            if ($this->isEarlyMorningTime($onlyTime) || $this->isLikelyCheckoutTime($onlyTime, $startTime, $endTime)) {
                return [null, $this->toDateTimeString($only->scan_date ?? null)];
            }

            return [$this->toDateTimeString($only->scan_date ?? null), null];
        }

        $earlyMorningScans = $sorted->filter(function ($scan) {
            $time = $this->normalizeTime($scan->scan_date);
            return $this->isEarlyMorningTime($time);
        })->values();
        $regularScans = $sorted->reject(function ($scan) {
            $time = $this->normalizeTime($scan->scan_date);
            return $this->isEarlyMorningTime($time);
        })->values();

        if ($earlyMorningScans->isNotEmpty() && $regularScans->isNotEmpty()) {
            $first = $regularScans->sortBy('scan_date')->first();
            $last = $earlyMorningScans->sortByDesc('scan_date')->first();
            return [
                $this->toDateTimeString($first->scan_date ?? null),
                $this->toDateTimeString($last->scan_date ?? null),
            ];
        }

        $isOvernightShift = $this->isOvernightShift($startTime, $endTime);
        $candidate = $isOvernightShift
            ? $sorted->first()
            : $sorted->last();

        $first = $isOvernightShift ? $sorted->last() : $sorted->first();
        return [
            $this->toDateTimeString($first->scan_date ?? null),
            $this->toDateTimeString($candidate->scan_date ?? null),
        ];
    }

    private function resolveEarlyStartWindow(Collection $sortedScans, ?string $startTime, ?string $endTime, string $logDate): array
    {
        if ($startTime === null || $endTime === null) {
            return [null, null];
        }

        try {
            $startAt = Carbon::parse($logDate . ' ' . $startTime);
            $endAt = Carbon::parse($logDate . ' ' . $endTime);

            $checkinCandidates = $sortedScans->filter(function ($scan) use ($startAt) {
                try {
                    $scanAt = Carbon::parse($scan->scan_date);
                } catch (\Throwable $e) {
                    return false;
                }
                return $scanAt->betweenIncluded($startAt->copy()->subHours(6), $startAt->copy()->addHours(4));
            })->values();

            $checkoutCandidates = $sortedScans->filter(function ($scan) use ($endAt) {
                try {
                    $scanAt = Carbon::parse($scan->scan_date);
                } catch (\Throwable $e) {
                    return false;
                }
                return $scanAt->betweenIncluded($endAt->copy()->subHours(4), $endAt->copy()->addHours(8));
            })->values();

            $checkinScan = $this->pickClosestScan($checkinCandidates, $startAt, 'before');
            $checkoutScan = $this->pickClosestScan($checkoutCandidates, $endAt, 'after');

            return [
                $this->toDateTimeString(optional($checkinScan)->scan_date ?? null),
                $this->toDateTimeString(optional($checkoutScan)->scan_date ?? null),
            ];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }

    private function pickClosestScan(Collection $candidates, Carbon $anchor, string $prefer = 'before')
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        $primary = $candidates->filter(function ($scan) use ($anchor, $prefer) {
            try {
                $scanAt = Carbon::parse($scan->scan_date);
            } catch (\Throwable $e) {
                return false;
            }
            return $prefer === 'after'
                ? $scanAt->greaterThanOrEqualTo($anchor)
                : $scanAt->lessThanOrEqualTo($anchor);
        });

        $pool = $primary->isNotEmpty() ? $primary : $candidates;
        return $pool->sortBy(function ($scan) use ($anchor) {
            try {
                return abs(Carbon::parse($scan->scan_date)->diffInMinutes($anchor, false));
            } catch (\Throwable $e) {
                return PHP_INT_MAX;
            }
        })->first();
    }

    private function isOvernightShift(?string $startTime, ?string $endTime): bool
    {
        if ($startTime === null || $endTime === null) {
            return false;
        }

        return $endTime <= $startTime;
    }

    private function scanLookupDatesForSchedule(
        ?string $logDate,
        ?string $startTime,
        ?string $endTime,
        ?string $nextDayStartTime = null
    ): array
    {
        if ($logDate === null) {
            return [];
        }

        $dates = [$logDate];
        try {
            if ($startTime !== null) {
                $checkinStart = Carbon::parse($logDate . ' ' . $startTime)->subHours(2);
                if ($checkinStart->toDateString() !== $logDate) {
                    $dates[] = $checkinStart->format('Y-m-d');
                }
            }

            $checkoutEnd = $this->resolveCheckoutWindowEnd($logDate, $startTime, $endTime, $nextDayStartTime);
            if ($checkoutEnd->toDateString() !== $logDate) {
                $dates[] = $checkoutEnd->format('Y-m-d');
            }
        } catch (\Throwable $e) {
            // keep base log date only
        }

        return array_values(array_unique($dates));
    }

    private function resolveCheckoutWindowEnd(
        string $logDate,
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $nextDayStartTime = null
    ): Carbon
    {
        $nextStart = $this->normalizeTime($nextDayStartTime) ?? '08:00:00';
        $checkoutEnd = Carbon::parse($logDate . ' ' . $nextStart)->addDay()->subHours(2);

        $normalizedStartTime = $this->normalizeTime($startTime);
        $normalizedEndTime = $this->normalizeTime($endTime);
        if (
            $normalizedStartTime !== null
            && $normalizedEndTime !== null
            && !$this->isOvernightShift($normalizedStartTime, $normalizedEndTime)
        ) {
            try {
                $scheduledEnd = Carbon::parse($logDate . ' ' . $normalizedEndTime);
                $maxCheckoutEnd = $scheduledEnd->copy()->addHours(10);

                if ($checkoutEnd->greaterThan($maxCheckoutEnd)) {
                    $checkoutEnd = $maxCheckoutEnd;
                }
            } catch (\Throwable $e) {
                // Keep the roster-based fallback cutoff.
            }
        }

        return $checkoutEnd;
    }

    private function buildRosterScheduleIndex(Collection $rosterRows): array
    {
        $index = [];

        foreach ($rosterRows as $row) {
            $logDate = $this->toDateString($row->log_date);
            $pin = $this->normalizePin((string) $row->pin);
            if ($logDate === null || $pin === '') {
                continue;
            }

            foreach ($this->pinCandidatesForMatch($pin) as $candidatePin) {
                $index[$candidatePin][$logDate] = [
                    'is_off' => (bool) ($row->is_off ?? false),
                    'start_time' => $this->normalizeTime($row->start_time),
                ];
            }
        }

        return $index;
    }

    private function resolveNextDayStartTime(array $rosterScheduleIndex, string $pin, ?string $logDate): ?string
    {
        if ($logDate === null) {
            return '08:00:00';
        }

        try {
            $baseDate = Carbon::parse($logDate);
        } catch (\Throwable $e) {
            return '08:00:00';
        }

        foreach ($this->pinCandidatesForMatch($pin) as $candidatePin) {
            $scheduleByDate = $rosterScheduleIndex[$candidatePin] ?? null;
            if (!$scheduleByDate || !is_array($scheduleByDate)) {
                continue;
            }

            $nextScheduledDates = collect($scheduleByDate)
                ->keys()
                ->filter(function ($date) use ($baseDate) {
                    try {
                        return Carbon::parse((string) $date)->greaterThan($baseDate);
                    } catch (\Throwable $e) {
                        return false;
                    }
                })
                ->sort()
                ->values();

            foreach ($nextScheduledDates as $nextScheduledDate) {
                $nextDaySchedule = $scheduleByDate[$nextScheduledDate] ?? null;
                if (!$nextDaySchedule || ($nextDaySchedule['is_off'] ?? false) === true) {
                    continue;
                }

                $startTime = $this->normalizeTime($nextDaySchedule['start_time'] ?? null);
                if ($startTime !== null) {
                    return $startTime;
                }
            }
        }

        return '08:00:00';
    }

    private function resolveScanDateRange(?int $year, ?int $month): array
    {
        if ($year === null) {
            return [null, null];
        }

        try {
            $rangeStart = $month === null
                ? Carbon::create($year, 1, 1)->subDay()->toDateString()
                : Carbon::create($year, $month, 1)->startOfMonth()->subDay()->toDateString();

            $rangeEnd = $month === null
                ? Carbon::create($year, 12, 31)->addDay()->toDateString()
                : Carbon::create($year, $month, 1)->endOfMonth()->addDay()->toDateString();

            return [$rangeStart, $rangeEnd];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }

    private function resolveAttendanceFilterDates($dateFromInput, $dateToInput, ?int $year = null, ?int $month = null): array
    {
        $dateFrom = $this->normalizeFilterDate($dateFromInput);
        $dateTo = $this->normalizeFilterDate($dateToInput);

        if ($dateFrom === null && $dateTo === null && $year !== null) {
            try {
                $base = $month !== null
                    ? Carbon::create($year, $month, 1)
                    : Carbon::create($year, 1, 1);
                $dateFrom = $base->copy()->startOfMonth()->toDateString();
                $dateTo = ($month !== null
                    ? $base->copy()->endOfMonth()
                    : Carbon::create($year, 12, 31)
                )->toDateString();
            } catch (\Throwable $e) {
                $dateFrom = null;
                $dateTo = null;
            }
        }

        if ($dateFrom === null && $dateTo !== null) {
            $dateFrom = $dateTo;
        }
        if ($dateTo === null && $dateFrom !== null) {
            $dateTo = $dateFrom;
        }

        if ($dateFrom === null || $dateTo === null) {
            $now = Carbon::now();
            $dateFrom = $now->copy()->startOfMonth()->toDateString();
            $dateTo = $now->copy()->endOfMonth()->toDateString();
        }

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [$dateFrom, $dateTo];
    }

    private function normalizeFilterDate($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $supportedFormats = [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'Y/m/d',
        ];

        foreach ($supportedFormats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false && $parsed->format($format) === $value) {
                    return $parsed->toDateString();
                }
            } catch (\Throwable $e) {
                // Try the next supported format.
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveScanDateRangeForDates(?string $dateFrom, ?string $dateTo): array
    {
        if ($dateFrom === null || $dateTo === null) {
            return [null, null];
        }

        try {
            return [
                Carbon::parse($dateFrom)->subDay()->toDateString(),
                Carbon::parse($dateTo)->addDay()->toDateString(),
            ];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }

    private function buildMonthKeysWithinDateRange(?string $dateFrom, ?string $dateTo): array
    {
        if ($dateFrom === null || $dateTo === null) {
            return [];
        }

        try {
            $cursor = Carbon::parse($dateFrom)->startOfMonth();
            $end = Carbon::parse($dateTo)->startOfMonth();
            $result = [];

            while ($cursor->lte($end)) {
                $result[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }

            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function requiresPreviousDayWindow(?string $startTime, ?string $endTime): bool
    {
        if ($startTime === null || $endTime === null) {
            return false;
        }

        if ($this->isOvernightShift($startTime, $endTime)) {
            return false;
        }

        try {
            $startHour = (int) substr($startTime, 0, 2);
            $endHour = (int) substr($endTime, 0, 2);
            return $startHour <= 2 && $endHour <= 12;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function filterOffdayBoundaryScans(Collection $scans): Collection
    {
        return $scans->filter(function ($scan) {
            $time = $this->normalizeTime($scan->scan_date);
            if ($time === null) {
                return false;
            }

            // For OFF rows, ignore midnight boundary scans that usually belong
            // to adjacent scheduled shifts (previous/next day).
            return $time >= '06:00:00' && $time < '21:00:00';
        })->values();
    }

    private function resolveOffdayScanWindow(Collection $scans, ?string $logDate): array
    {
        if ($logDate === null) {
            return [null, null];
        }

        $sameDayScans = $scans->filter(function ($scan) use ($logDate) {
            return $this->toDateString($scan->scan_date ?? null) === $logDate;
        })->sortBy('scan_date')->values();

        if ($sameDayScans->count() < 2) {
            return [null, null];
        }

        return [
            $this->toDateTimeString(optional($sameDayScans->first())->scan_date ?? null),
            $this->toDateTimeString(optional($sameDayScans->last())->scan_date ?? null),
        ];
    }

    private function paginateCollection(Collection $rows, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->input('page', 1));
        $total = $rows->count();
        $items = $rows->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function toDateString($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function toDateTimeString($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizePin(string $value): string
    {
        return preg_replace('/\D+/', '', trim($value)) ?? '';
    }

    private function pinCandidatesForMatch(string $pin): array
    {
        $pin = $this->normalizePin($pin);
        if ($pin === '') {
            return [];
        }

        $candidates = [$pin];

        // Support legacy pattern where fingerprint stores extra 0 after "25":
        // 2581507 <-> 25081507
        if (strlen($pin) === 7 && str_starts_with($pin, '25')) {
            $candidates[] = substr($pin, 0, 2) . '0' . substr($pin, 2);
        }
        if (strlen($pin) === 8 && str_starts_with($pin, '250')) {
            $candidates[] = substr($pin, 0, 2) . substr($pin, 3);
        }

        // Support PIN with leading zero in one source and without leading zero in another:
        // 080414383 <-> 80414383
        if (str_starts_with($pin, '0')) {
            $trimmedLeadingZero = ltrim($pin, '0');
            if ($trimmedLeadingZero !== '') {
                $candidates[] = $trimmedLeadingZero;
            }
        }

        // Support decimal artifact from import (e.g. 25120126.0 -> 251201260 after normalization):
        // try removing one trailing zero variant.
        if (strlen($pin) >= 8 && str_ends_with($pin, '0')) {
            $candidates[] = substr($pin, 0, -1);
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function buildApprovedLeaveTypesByPinDate(
        ?int $year,
        ?int $month,
        Collection $pinsInScope,
        ?string $rangeStartDate = null,
        ?string $rangeEndDate = null
    ): array
    {
        if ($pinsInScope->isEmpty()) {
            return [];
        }

        if ($year === null) {
            if ($rangeStartDate === null || $rangeEndDate === null) {
                return [];
            }
            $rangeStart = Carbon::parse($rangeStartDate)->startOfDay();
            $rangeEnd = Carbon::parse($rangeEndDate)->endOfDay();
        } else {
            $rangeStart = $month === null
                ? Carbon::create($year, 1, 1)->startOfDay()
                : Carbon::create($year, $month, 1)->startOfDay();
            $rangeEnd = $month === null
                ? Carbon::create($year, 12, 31)->endOfDay()
                : Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        }

        $employeeIdByPin = [];
        $pinByEmployeeId = [];
        $userIdByPin = [];
        $pinByUserId = [];
        $employeePairs = DB::table('employees')
            ->whereNotNull('nik')
            ->where('nik', '<>', '')
            ->get(['id', 'user_id', 'nik']);

        foreach ($employeePairs as $row) {
            $pin = $this->normalizePin((string) $row->nik);
            if ($pin === '') {
                continue;
            }

            $employeeId = (int) $row->id;
            if ($employeeId > 0) {
                $employeeIdByPin[$pin] = $employeeId;
                if (!isset($pinByEmployeeId[$employeeId])) {
                    $pinByEmployeeId[$employeeId] = $pin;
                }
            }

            $userId = (int) ($row->user_id ?? 0);
            if ($userId > 0) {
                $userIdByPin[$pin] = $userId;
                if (!isset($pinByUserId[$userId])) {
                    $pinByUserId[$userId] = $pin;
                }
            }
        }

        $employeeIdsInScope = $pinsInScope
            ->map(fn($pin) => $employeeIdByPin[(string) $pin] ?? null)
            ->filter(fn($id) => $id !== null)
            ->unique()
            ->values();

        $userIdsInScope = $pinsInScope
            ->map(fn($pin) => $userIdByPin[(string) $pin] ?? null)
            ->filter(fn($id) => $id !== null)
            ->unique()
            ->values();

        if ($employeeIdsInScope->isEmpty() && $userIdsInScope->isEmpty()) {
            return [];
        }

        $leaveRows = DB::table('leave_permissions')
            ->where('status', 'approved')
            ->where(function ($q) use ($employeeIdsInScope, $userIdsInScope) {
                $hasEmp = !$employeeIdsInScope->isEmpty();
                $hasUser = !$userIdsInScope->isEmpty();

                if ($hasEmp && $hasUser) {
                    $q->whereIn('employee_id', $employeeIdsInScope->all())
                        ->orWhereIn('user_id', $userIdsInScope->all());
                } elseif ($hasEmp) {
                    $q->whereIn('employee_id', $employeeIdsInScope->all());
                } elseif ($hasUser) {
                    $q->whereIn('user_id', $userIdsInScope->all());
                }
            })
            ->whereDate('start_date', '<=', $rangeEnd->toDateString())
            ->whereDate('end_date', '>=', $rangeStart->toDateString())
            ->get(['employee_id', 'user_id', 'type', 'start_date', 'end_date']);

        $result = [];
        foreach ($leaveRows as $leave) {
            $pin = null;

            $employeeId = (int) ($leave->employee_id ?? 0);
            if ($employeeId > 0) {
                $pin = $pinByEmployeeId[$employeeId] ?? null;
            }

            if (($pin === null || $pin === '') && !empty($leave->user_id)) {
                $userId = (int) $leave->user_id;
                $pin = $pinByUserId[$userId] ?? null;
            }
            if ($pin === null || $pin === '') {
                continue;
            }

            $from = Carbon::parse($leave->start_date)->startOfDay();
            $to = Carbon::parse($leave->end_date)->startOfDay();
            if ($from->lt($rangeStart)) {
                $from = $rangeStart->copy()->startOfDay();
            }
            if ($to->gt($rangeEnd)) {
                $to = $rangeEnd->copy()->startOfDay();
            }

            $type = (string) ($leave->type ?? '');
            if ($type === '') {
                continue;
            }

            for ($cursor = $from->copy(); $cursor->lte($to); $cursor->addDay()) {
                $dateKey = $cursor->format('Y-m-d');
                foreach ($this->pinCandidatesForMatch($pin) as $candidatePin) {
                    $result[$candidatePin][$dateKey] = $type;
                }
            }
        }

        return $result;
    }

    private function buildOvertimeFormByPinDate(Collection $employeeInfoByPin, ?string $rangeStartDate, ?string $rangeEndDate): array
    {
        if (
            $rangeStartDate === null
            || $rangeEndDate === null
            || !Schema::hasTable('overtimes')
            || !Schema::hasColumn('overtimes', 'employee_id')
            || !Schema::hasColumn('overtimes', 'overtime_date')
        ) {
            return [];
        }

        $employeeIdByPin = $employeeInfoByPin
            ->mapWithKeys(function (array $info, string $pin) {
                $employeeId = (int) ($info['employee_id'] ?? 0);
                return $employeeId > 0 ? [$pin => $employeeId] : [];
            });

        if ($employeeIdByPin->isEmpty()) {
            return [];
        }

        $employeeIds = $employeeIdByPin->values()->unique()->values();

        $overtimeRows = DB::table('overtimes')
            ->whereIn('employee_id', $employeeIds->all())
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('overtime_date', '>=', $rangeStartDate)
            ->whereDate('overtime_date', '<=', $rangeEndDate)
            ->get(['employee_id', 'overtime_date']);

        $pinByEmployeeId = $employeeIdByPin
            ->mapWithKeys(fn(int $employeeId, string $pin) => [$employeeId => $pin]);

        $result = [];

        foreach ($overtimeRows as $overtime) {
            $employeeId = (int) ($overtime->employee_id ?? 0);
            $pin = $pinByEmployeeId[$employeeId] ?? null;
            $logDate = $this->toDateString($overtime->overtime_date ?? null);

            if ($pin === null || $logDate === null) {
                continue;
            }

            $result[$pin][$logDate] = 'Lembur';
        }

        return $result;
    }

    private function buildApprovedLeaveDetailsByPinDate(Collection $pinsInScope, ?string $rangeStartDate, ?string $rangeEndDate): array
    {
        if ($pinsInScope->isEmpty()) {
            return [];
        }

        $employeePairs = DB::table('employees')
            ->select(['id', 'nik', 'user_id'])
            ->whereNotNull('nik')
            ->where('nik', '<>', '')
            ->get();

        $employeeIdByPin = [];
        $userIdByPin = [];
        $pinByEmployeeId = [];
        $pinByUserId = [];

        foreach ($employeePairs as $row) {
            $pin = $this->normalizePin((string) $row->nik);
            if ($pin === '') {
                continue;
            }

            $employeeId = (int) $row->id;
            if ($employeeId > 0) {
                $employeeIdByPin[$pin] = $employeeId;
                if (!isset($pinByEmployeeId[$employeeId])) {
                    $pinByEmployeeId[$employeeId] = $pin;
                }
            }

            $userId = (int) ($row->user_id ?? 0);
            if ($userId > 0) {
                $userIdByPin[$pin] = $userId;
                if (!isset($pinByUserId[$userId])) {
                    $pinByUserId[$userId] = $pin;
                }
            }
        }

        $employeeIdsInScope = $pinsInScope
            ->map(fn($pin) => $employeeIdByPin[(string) $pin] ?? null)
            ->filter(fn($id) => $id !== null)
            ->unique()
            ->values();

        $userIdsInScope = $pinsInScope
            ->map(fn($pin) => $userIdByPin[(string) $pin] ?? null)
            ->filter(fn($id) => $id !== null)
            ->unique()
            ->values();

        if ($employeeIdsInScope->isEmpty() && $userIdsInScope->isEmpty()) {
            return [];
        }

        $rangeStart = $rangeStartDate
            ? Carbon::parse($rangeStartDate)->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();
        $rangeEnd = $rangeEndDate
            ? Carbon::parse($rangeEndDate)->startOfDay()
            : Carbon::now()->endOfMonth()->startOfDay();

        $leaveRows = DB::table('leave_permissions')
            ->where('status', 'approved')
            ->where(function ($q) use ($employeeIdsInScope, $userIdsInScope) {
                $hasEmp = !$employeeIdsInScope->isEmpty();
                $hasUser = !$userIdsInScope->isEmpty();

                if ($hasEmp && $hasUser) {
                    $q->whereIn('employee_id', $employeeIdsInScope->all())
                        ->orWhereIn('user_id', $userIdsInScope->all());
                } elseif ($hasEmp) {
                    $q->whereIn('employee_id', $employeeIdsInScope->all());
                } elseif ($hasUser) {
                    $q->whereIn('user_id', $userIdsInScope->all());
                }
            })
            ->whereDate('start_date', '<=', $rangeEnd->toDateString())
            ->whereDate('end_date', '>=', $rangeStart->toDateString())
            ->orderByDesc('created_at')
            ->get([
                'id',
                'employee_id',
                'user_id',
                'type',
                'start_date',
                'end_date',
                'days',
                'reason',
                'status',
                'created_at',
                'review_notes',
                'attachment_image',
                'attachment_images',
            ]);

        $result = [];

        foreach ($leaveRows as $leave) {
            $pin = null;

            $employeeId = (int) ($leave->employee_id ?? 0);
            if ($employeeId > 0) {
                $pin = $pinByEmployeeId[$employeeId] ?? null;
            }

            if (($pin === null || $pin === '') && !empty($leave->user_id)) {
                $userId = (int) $leave->user_id;
                $pin = $pinByUserId[$userId] ?? null;
            }

            if ($pin === null || $pin === '') {
                continue;
            }

            $from = Carbon::parse($leave->start_date)->startOfDay();
            $to = Carbon::parse($leave->end_date)->startOfDay();
            if ($from->lt($rangeStart)) {
                $from = $rangeStart->copy()->startOfDay();
            }
            if ($to->gt($rangeEnd)) {
                $to = $rangeEnd->copy()->startOfDay();
            }

            $payload = [
                'id' => (int) ($leave->id ?? 0),
                'type' => trim((string) ($leave->type ?? '')),
                'start_date' => $this->toDateString($leave->start_date ?? null),
                'end_date' => $this->toDateString($leave->end_date ?? null),
                'days' => $leave->days !== null ? (int) $leave->days : null,
                'reason' => trim((string) ($leave->reason ?? '')),
                'status' => trim((string) ($leave->status ?? '')),
                'created_at' => $this->formatDateTimeValue($leave->created_at ?? null),
                'review_notes' => trim((string) ($leave->review_notes ?? '')),
                'attachments' => $this->formatLeaveAttachmentFiles(
                    $leave->attachment_image ?? null,
                    $leave->attachment_images ?? null
                ),
            ];

            for ($cursor = $from->copy(); $cursor->lte($to); $cursor->addDay()) {
                $dateKey = $cursor->format('Y-m-d');
                foreach ($this->pinCandidatesForMatch($pin) as $candidatePin) {
                    $result[$candidatePin][$dateKey] = $payload;
                }
            }
        }

        return $result;
    }

    private function buildOvertimeDetailsByPinDate(Collection $employeeInfoByPin, ?string $rangeStartDate, ?string $rangeEndDate): array
    {
        if (
            $rangeStartDate === null
            || $rangeEndDate === null
            || !Schema::hasTable('overtimes')
            || !Schema::hasColumn('overtimes', 'employee_id')
            || !Schema::hasColumn('overtimes', 'overtime_date')
        ) {
            return [];
        }

        $employeeIdByPin = $employeeInfoByPin
            ->mapWithKeys(function (array $info, string $pin) {
                $employeeId = (int) ($info['employee_id'] ?? 0);
                return $employeeId > 0 ? [$pin => $employeeId] : [];
            });

        if ($employeeIdByPin->isEmpty()) {
            return [];
        }

        $employeeIds = $employeeIdByPin->values()->unique()->values();

        $overtimeRows = DB::table('overtimes')
            ->whereIn('employee_id', $employeeIds->all())
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('overtime_date', '>=', $rangeStartDate)
            ->whereDate('overtime_date', '<=', $rangeEndDate)
            ->orderByDesc('created_at')
            ->get([
                'id',
                'employee_id',
                'overtime_date',
                'start_time',
                'end_time',
                'hours',
                'reason',
                'status',
                'created_at',
                'review_notes',
                'attachment_path',
                'attachment_original_name',
            ]);

        $pinByEmployeeId = $employeeIdByPin
            ->mapWithKeys(fn(int $employeeId, string $pin) => [$employeeId => $pin]);

        $result = [];

        foreach ($overtimeRows as $overtime) {
            $employeeId = (int) ($overtime->employee_id ?? 0);
            $pin = $pinByEmployeeId[$employeeId] ?? null;
            $logDate = $this->toDateString($overtime->overtime_date ?? null);

            if ($pin === null || $logDate === null) {
                continue;
            }

            $attachmentPath = trim((string) ($overtime->attachment_path ?? ''));

            $result[$pin][$logDate] = [
                'id' => (int) ($overtime->id ?? 0),
                'overtime_date' => $logDate,
                'start_time' => $this->formatTimeValue($overtime->start_time ?? null),
                'end_time' => $this->formatTimeValue($overtime->end_time ?? null),
                'hours' => $overtime->hours !== null ? (float) $overtime->hours : null,
                'reason' => trim((string) ($overtime->reason ?? '')),
                'status' => trim((string) ($overtime->status ?? '')),
                'created_at' => $this->formatDateTimeValue($overtime->created_at ?? null),
                'review_notes' => trim((string) ($overtime->review_notes ?? '')),
                'attachment_url' => $attachmentPath !== '' ? Storage::disk('public')->url($attachmentPath) : null,
                'attachment_original_name' => trim((string) ($overtime->attachment_original_name ?? '')),
            ];
        }

        return $result;
    }

    private function formatTimeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $time = trim((string) $value);
        if ($time === '') {
            return null;
        }

        return substr($time, 0, 5);
    }

    private function formatDateTimeValue(mixed $value): ?string
    {
        $dateTime = $this->toDateTimeString($value);
        return $dateTime !== null ? str_replace(' ', 'T', $dateTime) : null;
    }

    private function formatLeaveAttachmentFiles(mixed $singlePath, mixed $multiplePaths): array
    {
        $paths = $multiplePaths;

        if (is_string($paths) && trim($paths) !== '') {
            $decoded = json_decode($paths, true);
            $paths = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($paths)) {
            $paths = [];
        }

        $single = trim((string) ($singlePath ?? ''));
        if (empty($paths) && $single !== '') {
            $paths = [$single];
        }

        return collect($paths)
            ->filter(fn($path) => is_string($path) && trim($path) !== '')
            ->unique()
            ->values()
            ->map(function (string $path) {
                return [
                    'path' => $path,
                    'name' => basename($path),
                    'url' => Storage::disk('public')->url($path),
                ];
            })
            ->all();
    }

    private function leaveTypeLabel(string $type): string
    {
        return match (strtolower(trim($type))) {
            'cuti' => 'Cuti',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'dinas_luar' => 'Dinas Luar',
            default => $type !== '' ? $type : 'Izin',
        };
    }

    private function hasRosterPinMatch(string $pin, array $rosterPinIndex): bool
    {
        foreach ($this->pinCandidatesForMatch($pin) as $candidate) {
            if (isset($rosterPinIndex[$candidate])) {
                return true;
            }
        }

        return false;
    }

    private function resolveWorkGroup(string $department): ?string
    {
        $value = mb_strtolower(trim($department));
        if ($value === '') {
            return null;
        }

        if (str_contains($value, 'office') || str_contains($value, 'kantor')) {
            return 'office';
        }

        if (
            str_contains($value, 'operational')
            || str_contains($value, 'opperational')
            || str_contains($value, 'operation')
            || str_contains($value, 'ops')
        ) {
            return 'operational';
        }

        return null;
    }

    private function resolveHolidayForPin(Collection $holidaysByDate, string $logDate, string $pin, array $pinWorkGroupMap): array
    {
        $holidayRows = $holidaysByDate->get($logDate, collect());
        if ($holidayRows->isEmpty()) {
            return [false, ''];
        }

        $workGroup = $pinWorkGroupMap[$pin] ?? null;
        $scopes = $holidayRows
            ->map(fn($row) => strtolower((string) ($row->scope_type ?? 'all')))
            ->filter()
            ->unique()
            ->values();

        // If both scoped holidays exist on same date (office + operational),
        // treat the day as holiday for everyone.
        if ($scopes->contains('office') && $scopes->contains('operational')) {
            $first = $holidayRows->first();
            return [true, trim((string) ($first->name ?? ''))];
        }

        $matched = $holidayRows->first(function ($row) use ($workGroup) {
            $scope = strtolower((string) ($row->scope_type ?? 'all'));
            if ($scope === 'all') {
                return true;
            }
            if ($scope === 'office' && $workGroup === 'office') {
                return true;
            }
            if ($scope === 'operational' && $workGroup === 'operational') {
                return true;
            }
            return false;
        });

        if (!$matched) {
            return [false, ''];
        }

        return [true, trim((string) ($matched->name ?? ''))];
    }

    private function isEarlyMorningTime(?string $time): bool
    {
        if ($time === null) {
            return false;
        }

        return strcmp(substr($time, 0, 8), '05:00:00') <= 0;
    }

    private function isLikelyCheckoutTime(?string $time, ?string $startTime, ?string $endTime): bool
    {
        if ($time === null || $startTime === null || $endTime === null) {
            return false;
        }

        try {
            $scan = Carbon::parse('2000-01-01 ' . $time);
            $start = Carbon::parse('2000-01-01 ' . $startTime);
            $end = Carbon::parse('2000-01-01 ' . $endTime);
            $isOvernight = $end->lessThanOrEqualTo($start);
            if ($isOvernight) {
                $end->addDay();
            }

            $scanAlt = Carbon::parse('2000-01-01 ' . $time);
            // Only shift scan to next day for overnight schedule.
            if ($isOvernight && $scanAlt->lessThan($start)) {
                $scanAlt->addDay();
            }

            $toStart = abs($scanAlt->diffInMinutes($start, false));
            $toEnd = abs($scanAlt->diffInMinutes($end, false));
            return $toEnd < $toStart;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function buildCorrectionDateTime(?string $logDate, ?string $timeHm, ?string $startTime, ?string $endTime, bool $isLastScan): ?string
    {
        if ($logDate === null || $timeHm === null || $timeHm === '') {
            return null;
        }

        try {
            $scan = Carbon::parse($logDate . ' ' . $timeHm . ':00');

            // For correction checkout, treat times that are earlier than shift start
            // as next-day checkout (crossing midnight).
            if ($isLastScan && $startTime !== null) {
                $start = Carbon::parse($logDate . ' ' . $startTime);
                if ($scan->lessThan($start)) {
                    $scan->addDay();
                }
            }

            return $scan->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeScheduleTimeInput(?string $value): ?string
    {
        $time = trim((string) $value);
        if ($time === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        return null;
    }

    private function resolveNoRosterSchedule(string $logDate, ?string $displayPin = null, ?string $departmentName = null): array
    {
        if ($this->usesFixedT2PNoRosterSchedule($displayPin)) {
            $startTime = '08:00:00';
            $endTime = '16:00:00';

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'next_day_start_time' => $startTime,
                'label' => substr($startTime, 0, 5) . ' - ' . substr($endTime, 0, 5),
            ];
        }

        $date = Carbon::parse($logDate);
        $normalizedDepartment = strtoupper(trim((string) ($departmentName ?? '')));
        if ($normalizedDepartment === 'OB') {
            $startTime = '07:00:00';
            $endTime = '15:00:00';
        } else {
            $startTime = '08:00:00';
            $endTime = $date->isSaturday() ? '13:00:00' : '16:00:00';
        }

        return [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'next_day_start_time' => $startTime,
            'label' => substr($startTime, 0, 5) . ' - ' . substr($endTime, 0, 5),
        ];
    }

    private function usesT2PNoRosterSchedule(?string $displayPin): bool
    {
        $pin = strtoupper(trim((string) ($displayPin ?? '')));
        return $pin !== '' && str_starts_with($pin, 'T2P');
    }

    private function usesFixedT2PNoRosterSchedule(?string $displayPin): bool
    {
        $pin = strtoupper(trim((string) ($displayPin ?? '')));
        return $pin === 'T2P241201001';
    }

    private function usesFlexibleT2PNoRosterSchedule(?string $displayPin): bool
    {
        return $this->usesT2PNoRosterSchedule($displayPin) && !$this->usesFixedT2PNoRosterSchedule($displayPin);
    }

    private function resolveFlexibleT2PScanLookupDates(string $logDate): array
    {
        try {
            $date = Carbon::parse($logDate);
            return [
                $date->copy()->subDay()->toDateString(),
                $date->toDateString(),
                $date->copy()->addDay()->toDateString(),
            ];
        } catch (\Throwable $e) {
            return [$logDate];
        }
    }

    private function resolveFlexibleT2PNoRosterScanWindow(Collection $scans, string $logDate): array
    {
        if ($scans->isEmpty()) {
            return [null, null];
        }

        try {
            $shiftStartDate = Carbon::parse($logDate)->startOfDay();
            $shiftEndDate = Carbon::parse($logDate)->endOfDay();

            $sameDayScans = $scans
                ->filter(function ($scan) use ($shiftStartDate, $shiftEndDate) {
                    try {
                        $scanAt = Carbon::parse($scan->scan_date);
                        return $scanAt->betweenIncluded($shiftStartDate, $shiftEndDate);
                    } catch (\Throwable $e) {
                        return false;
                    }
                })
                ->sortBy('scan_date')
                ->values();

            $sameDayScans = $this->excludeFlexibleT2PCarryoverScans($sameDayScans, $scans, $logDate);

            $firstScan = $sameDayScans->first();

            if ($firstScan === null) {
                return [null, null];
            }

            $firstScanAt = Carbon::parse($firstScan->scan_date);
            $checkoutLimit = $firstScanAt->copy()->addHours(14);

            $lastScan = $scans
                ->filter(function ($scan) use ($firstScanAt, $checkoutLimit) {
                    try {
                        $scanAt = Carbon::parse($scan->scan_date);
                        return $scanAt->greaterThan($firstScanAt) && $scanAt->lessThanOrEqualTo($checkoutLimit);
                    } catch (\Throwable $e) {
                        return false;
                    }
                })
                ->sortByDesc('scan_date')
                ->first();

            return [
                $this->toDateTimeString(optional($firstScan)->scan_date ?? null),
                $this->toDateTimeString(optional($lastScan)->scan_date ?? null),
            ];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }

    private function resolveFlexibleT2PNoRosterSchedule(?string $firstScan, ?string $lastScan): array
    {
        $shiftCode = $this->resolveFlexibleT2PShiftCode($firstScan, $lastScan);
        if ($shiftCode === '1') {
            $startTime = '07:00:00';
            $endTime = '19:00:00';
        } elseif ($shiftCode === '2') {
            $startTime = '19:00:00';
            $endTime = '07:00:00';
        } else {
            $startTime = $this->normalizeTime($firstScan);
            $endTime = $this->normalizeTime($lastScan);

            $referenceTime = $startTime ?? $endTime;
            if ($referenceTime !== null) {
                if ($referenceTime >= '06:00:00' && $referenceTime < '17:00:00') {
                    $startTime = '07:00:00';
                    $endTime = '19:00:00';
                } else {
                    $startTime = '19:00:00';
                    $endTime = '07:00:00';
                }
            }
        }

        $label = ($startTime !== null && $endTime !== null)
            ? (substr($startTime, 0, 5) . ' - ' . substr($endTime, 0, 5))
            : '-';

        return [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'next_day_start_time' => $this->isOvernightShift($startTime, $endTime) ? $endTime : $startTime,
            'label' => $label,
        ];
    }

    private function resolveFlexibleT2PShiftCode(?string $firstScan, ?string $lastScan): ?string
    {
        if ($firstScan === null) {
            return null;
        }

        $startTime = $this->normalizeTime($firstScan);
        if ($startTime === null) {
            return null;
        }

        if ($startTime >= '06:00:00' && $startTime <= '08:00:00') {
            return '1';
        }

        if ($startTime >= '17:00:00' && $startTime <= '20:00:00') {
            return '2';
        }

        return null;
    }

    private function excludeFlexibleT2PCarryoverScans(Collection $sameDayScans, Collection $allScans, string $logDate): Collection
    {
        try {
            $previousDate = Carbon::parse($logDate)->subDay()->toDateString();
        } catch (\Throwable $e) {
            return $sameDayScans;
        }

        $previousNightScans = $allScans
            ->filter(function ($scan) use ($previousDate) {
                if ($this->toDateString($scan->scan_date ?? null) !== $previousDate) {
                    return false;
                }

                $time = $this->normalizeTime($scan->scan_date ?? null);
                return $time !== null && $time >= '17:00:00';
            })
            ->sortByDesc('scan_date')
            ->values();

        if ($previousNightScans->isEmpty()) {
            return $sameDayScans;
        }

        $hasLikelyNightCheckin = $sameDayScans->contains(function ($scan) {
            $time = $this->normalizeTime($scan->scan_date ?? null);
            return $time !== null && $time >= '17:00:00';
        });

        $onlyHasEarlyMorningScans = $sameDayScans->isNotEmpty() && $sameDayScans->every(function ($scan) {
            $time = $this->normalizeTime($scan->scan_date ?? null);
            return $time !== null && $time <= '12:00:00';
        });

        if (!$hasLikelyNightCheckin && !$onlyHasEarlyMorningScans) {
            return $sameDayScans;
        }

        return $sameDayScans
            ->reject(function ($scan) use ($previousNightScans) {
                $time = $this->normalizeTime($scan->scan_date ?? null);
                if ($time === null || $time > '12:00:00') {
                    return false;
                }

                try {
                    $scanAt = Carbon::parse($scan->scan_date);
                } catch (\Throwable $e) {
                    return false;
                }

                return $previousNightScans->contains(function ($previousScan) use ($scanAt) {
                    try {
                        $previousAt = Carbon::parse($previousScan->scan_date);
                        return $previousAt->lt($scanAt) && $scanAt->diffInHours($previousAt) <= 14;
                    } catch (\Throwable $e) {
                        return false;
                    }
                });
            })
            ->values();
    }

    private function formatCorrection(?AttendanceLogCorrection $correction): ?array
    {
        if (!$correction) {
            return null;
        }

        return [
            'id' => $correction->id,
            'status' => $correction->status,
            'first_scan' => optional($correction->corrected_first_scan)->format('Y-m-d H:i:s'),
            'last_scan' => optional($correction->corrected_last_scan)->format('Y-m-d H:i:s'),
            'note' => $correction->note,
            'rejection_reason' => $correction->rejection_reason,
        ];
    }

    private function exportRowsToExcel(Collection $rows, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Attendance Logs');
        $dateColumns = $this->buildExportDateColumns($rows, $dateFrom, $dateTo);
        $title = $this->buildAttendanceExportTitle($dateFrom, $dateTo, $rows);
        $headerStartRow = 2;
        $subHeaderRow = 3;
        $bodyStartRow = 4;

        $lastColumn = Coordinate::stringFromColumnIndex(max(2, count($dateColumns) + 2));

        $sheet->mergeCells('A1:' . $lastColumn . '1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '111827'],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
            'fill' => [
                'fillType' => 'solid',
                'color' => ['rgb' => 'FFFFFF'],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        $sheet->mergeCells('A' . $headerStartRow . ':A' . $subHeaderRow);
        $sheet->mergeCells('B' . $headerStartRow . ':B' . $subHeaderRow);
        $sheet->setCellValue('A' . $headerStartRow, 'No');
        $sheet->setCellValue('B' . $headerStartRow, 'Nama');

        $gridColor = '6AA84F';
        $headerFill = 'FFFFFF';
        $headerText = '111827';
        $dateNumberText = '7C3AED';
        $subHeaderText = '4B5563';
        $leftBodyFill = 'E5E7EB';
        $filledCellFill = 'D1D5DB';
        $lateCellFill = 'FDE047';
        $dangerCellFill = 'FCA5A5';
        $blankCellFill = 'FFFFFF';
        $textColor = '111827';

        foreach ($dateColumns as $index => $logDate) {
            $column = Coordinate::stringFromColumnIndex($index + 3);
            $sheet->setCellValue($column . $headerStartRow, Carbon::parse($logDate)->format('j'));
            $sheet->setCellValue($column . $subHeaderRow, $this->exportDayShortName($logDate));
        }

        $sheet->getStyle('A' . $headerStartRow . ':' . $lastColumn . $subHeaderRow)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => $headerText],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => 'solid',
                'color' => ['rgb' => $headerFill],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => $gridColor],
                ],
            ],
        ]);

        if (!empty($dateColumns)) {
            $sheet->getStyle('C' . $headerStartRow . ':' . $lastColumn . $headerStartRow)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => $dateNumberText],
                    'size' => 12,
                ],
            ]);
            $sheet->getStyle('C' . $subHeaderRow . ':' . $lastColumn . $subHeaderRow)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => $subHeaderText],
                    'size' => 9,
                ],
            ]);
        }

        $sheet->getRowDimension($headerStartRow)->setRowHeight(24);
        $sheet->getRowDimension($subHeaderRow)->setRowHeight(20);
        $sheet->getColumnDimension('A')->setWidth(11);
        $sheet->getColumnDimension('B')->setWidth(18);
        foreach ($dateColumns as $index => $logDate) {
            $column = Coordinate::stringFromColumnIndex($index + 3);
            $sheet->getColumnDimension($column)->setWidth(9.5);
        }

        $groupedRows = $rows
            ->groupBy(function (array $row) {
                $pin = trim((string) ($row['pin'] ?? ''));
                $name = trim((string) ($row['name'] ?? '-'));
                return $pin . '|' . $name;
            })
            ->map(function (Collection $group) {
                $first = $group->first();
                return [
                    'name' => trim((string) ($first['name'] ?? '-')) ?: '-',
                    'pin' => trim((string) ($first['pin'] ?? '')),
                    'rows' => $group->keyBy(fn(array $row) => (string) ($row['log_date'] ?? '')),
                ];
            })
            ->filter(fn(array $group) => !$this->shouldExcludeFromAttendanceExport((string) ($group['pin'] ?? '')))
            ->sortBy([
                ['name', 'asc'],
                ['pin', 'asc'],
            ])
            ->values();

        $rowIndex = $bodyStartRow;
        foreach ($groupedRows as $group) {
            $sheet->setCellValueExplicit('A' . $rowIndex, (string) ($group['pin'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $rowIndex, (string) ($group['name'] ?? '-'));

            $sheet->getStyle('A' . $rowIndex . ':B' . $rowIndex)->applyFromArray([
                'font' => [
                    'bold' => false,
                    'color' => ['rgb' => $textColor],
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'color' => ['rgb' => $leftBodyFill],
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => 'thin',
                        'color' => ['rgb' => $gridColor],
                    ],
                ],
            ]);
            $sheet->getStyle('B' . $rowIndex)->getAlignment()->setHorizontal('left');

            foreach ($dateColumns as $dateIndex => $logDate) {
                $column = Coordinate::stringFromColumnIndex($dateIndex + 3);
                /** @var Collection $employeeRows */
                $employeeRows = $group['rows'];
                $row = $employeeRows->get($logDate);
                $sheet->setCellValue($column . $rowIndex, $this->exportScanSummaryCell($row));

                $fillColor = $blankCellFill;
                if (is_array($row)) {
                    $category = $this->exportCellCategory($row);
                    $cellText = $this->exportScanSummaryCell($row);

                    if ($category === 'danger') {
                        $fillColor = $dangerCellFill;
                    } elseif ($cellText !== '') {
                        $fillColor = $category === 'late' ? $lateCellFill : $filledCellFill;
                    }
                }

                $sheet->getStyle($column . $rowIndex)->applyFromArray([
                    'font' => [
                        'bold' => false,
                        'color' => ['rgb' => $textColor],
                        'size' => 8.5,
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'color' => ['rgb' => $fillColor],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => 'thin',
                            'color' => ['rgb' => $gridColor],
                        ],
                    ],
                ]);
            }

            $sheet->getRowDimension($rowIndex)->setRowHeight(42);
            $rowIndex++;
        }

        $bodyLastRow = max($bodyStartRow, $rowIndex - 1);
        $sheet->freezePane('C' . $bodyStartRow);
        $this->appendAttendanceStatusLegendSheet($spreadsheet, $rows);

        $filename = 'attendance_logs_' . now()->format('Ymd_His') . '.xlsx';
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

    private function exportSecurityRowsToPdf(Collection $rows, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $dateColumns = $this->buildExportDateColumns($rows, $dateFrom, $dateTo);
        $groups = $this->buildSecurityExportGroups($rows);
        $monthLabel = $this->buildSecurityExportMonthLabel($dateFrom, $dateTo, $dateColumns);
        $logoDataUri = $this->buildSecurityLogoDataUri();

        $html = view('exports.attendance-security-pdf', [
            'groups' => $groups,
            'dateColumns' => $dateColumns,
            'monthLabel' => $monthLabel,
            'logoDataUri' => $logoDataUri,
            'companyLines' => [
                'TRI TUNGGAL PUTRA',
                'Graha Permata Sidorejo Indah',
                'Blok X No 20 Sidorejo',
                'Krian - Sidoarjo',
                'Telp/Fax (031) 9989 0975',
            ],
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'attendance_security_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function buildSecurityExportGroups(Collection $rows): Collection
    {
        return $rows
            ->groupBy(function (array $row) {
                $pin = trim((string) ($row['pin'] ?? ''));
                $name = trim((string) ($row['name'] ?? '-'));

                return $pin . '|' . $name;
            })
            ->map(function (Collection $group) {
                $first = $group->first();

                return [
                    'name' => trim((string) ($first['name'] ?? '-')) ?: '-',
                    'pin' => trim((string) ($first['pin'] ?? '')),
                    'rows' => $group->keyBy(fn(array $row) => (string) ($row['log_date'] ?? '')),
                ];
            })
            ->filter(fn(array $group) => !$this->shouldExcludeFromAttendanceExport((string) ($group['pin'] ?? '')))
            ->sortBy([
                ['name', 'asc'],
                ['pin', 'asc'],
            ])
            ->values();
    }

    private function buildSecurityLogoDataUri(): ?string
    {
        $path = public_path('image/T2P.jpg');
        if (!is_file($path)) {
            return null;
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        return 'data:image/jpeg;base64,' . base64_encode($contents);
    }

    private function exportSecurityRowsToWorkbook(Collection $rows, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $dateColumns = $this->buildExportDateColumns($rows, $dateFrom, $dateTo);
        $groupedRows = $this->buildSecurityExportGroups($rows);

        $spreadsheet = new Spreadsheet();
        $sheetIndex = 0;

        if ($groupedRows->isEmpty()) {
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Security');
            $sheet->setCellValue('A1', 'Tidak ada data security untuk filter ini.');
        } else {
            foreach ($groupedRows as $group) {
                $sheet = $sheetIndex === 0
                    ? $spreadsheet->getActiveSheet()
                    : $spreadsheet->createSheet();

                $this->buildSecurityEmployeeSheet(
                    $sheet,
                    $group,
                    $dateColumns,
                    $dateFrom,
                    $dateTo,
                    $sheetIndex
                );

                $sheetIndex++;
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'attendance_security_' . now()->format('Ymd_His') . '.xlsx';
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

    private function buildSecurityEmployeeSheet($sheet, array $group, array $dateColumns, ?string $dateFrom, ?string $dateTo, int $sheetIndex): void
    {
        $sheet->setTitle($this->buildSecuritySheetTitle(
            (string) ($group['pin'] ?? ''),
            (string) ($group['name'] ?? 'Security'),
            $sheetIndex
        ));

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageMargins()->setTop(0.35);
        $sheet->getPageMargins()->setRight(0.25);
        $sheet->getPageMargins()->setLeft(0.25);
        $sheet->getPageMargins()->setBottom(0.35);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(1);
        $sheet->setShowGridlines(false);

        foreach (['A' => 7, 'B' => 16, 'C' => 12, 'D' => 12, 'E' => 13, 'F' => 9, 'G' => 18, 'H' => 18] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->mergeCells('E1:F4');
        $sheet->mergeCells('G1:H4');

        $logoPath = public_path('image/T2P.jpg');
        if (is_file($logoPath)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('T2P Logo');
            $drawing->setDescription('T2P Logo');
            $drawing->setPath($logoPath);
            $drawing->setCoordinates('E1');
            $drawing->setOffsetX(4);
            $drawing->setOffsetY(8);
            $drawing->setWidth(128);
            $drawing->setWorksheet($sheet);
        } else {
            $sheet->setCellValue('E1', "T2P");
        }

        $headerText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $headerTitle = $headerText->createTextRun('TRI TUNGGAL PUTRA');
        $headerTitle->getFont()->setBold(true)->setSize(16);
        $headerText->createText("\nGraha Permata Sidorejo Indah\nBlok X No 20 Sidorejo\nKrian - Sidoarjo\nTelp/Fax (031) 9989 0975");
        $sheet->setCellValue('G1', $headerText);
        $sheet->getStyle('E1:F4')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 22,
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => '111111'],
                ],
            ],
        ]);
        $sheet->getStyle('G1:H4')->applyFromArray([
            'font' => [
                'bold' => false,
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => 'left',
                'vertical' => 'top',
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => '111111'],
                ],
            ],
        ]);
        $sheet->getStyle('G1:H4')->getAlignment()->setIndent(0);
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(20);
        $sheet->getRowDimension(4)->setRowHeight(20);

        $sheet->mergeCells('A5:H5');
        $sheet->setCellValue('A5', 'DAFTAR HADIR DAN LEMBUR PERORANGAN');
        $sheet->getStyle('A5')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(26);

        $monthLabel = $this->buildSecurityExportMonthLabel($dateFrom, $dateTo, $dateColumns);
        $sheet->setCellValue('A6', 'Nama');
        $sheet->setCellValue('B6', ':');
        $sheet->mergeCells('C6:E6');
        $sheet->setCellValue('C6', (string) ($group['name'] ?? '-'));
        $sheet->setCellValue('F6', 'Bulan');
        $sheet->mergeCells('G6:H6');
        $sheet->setCellValue('G6', $monthLabel);

        $sheet->setCellValue('A7', 'NIK');
        $sheet->setCellValue('B7', ':');
        $sheet->mergeCells('C7:E7');
        $sheet->setCellValueExplicit('C7', (string) ($group['pin'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

        $sheet->setCellValue('A8', 'Lokasi Kerja');
        $sheet->setCellValue('B8', ':');
        $sheet->mergeCells('C8:H8');
        $sheet->setCellValue('C8', 'Golden Multi Indotama');

        $sheet->getStyle('A6:H8')->applyFromArray([
            'font' => [
                'size' => 11,
            ],
            'alignment' => [
                'vertical' => 'center',
            ],
        ]);

        $headerRow = 10;
        $dataStartRow = 11;
        $sheet->setCellValue('A' . $headerRow, 'Tgl');
        $sheet->setCellValue('B' . $headerRow, 'Hari');
        $sheet->setCellValue('C' . $headerRow, 'Masuk');
        $sheet->setCellValue('D' . $headerRow, 'Pulang');
        $sheet->setCellValue('E' . $headerRow, 'Status');
        $sheet->mergeCells('F' . $headerRow . ':H' . $headerRow);
        $sheet->setCellValue('F' . $headerRow, 'Keterangan');
        $sheet->getStyle('A' . $headerRow . ':H' . $headerRow)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => '111111'],
                ],
            ],
        ]);

        $employeeRows = $group['rows'] instanceof Collection
            ? $group['rows']
            : collect($group['rows'] ?? []);

        $rowIndex = $dataStartRow;
        foreach ($dateColumns as $logDate) {
            $row = $employeeRows->get($logDate);
            $sheet->mergeCells('F' . $rowIndex . ':H' . $rowIndex);
            $sheet->setCellValue('A' . $rowIndex, Carbon::parse($logDate)->format('j'));
            $sheet->setCellValue('B' . $rowIndex, $this->exportDayNameShortId($logDate));
            $sheet->setCellValue('C' . $rowIndex, $this->exportSecurityTimeCell($row, 'first_scan'));
            $sheet->setCellValue('D' . $rowIndex, $this->exportSecurityTimeCell($row, 'last_scan'));
            $sheet->setCellValue('E' . $rowIndex, $this->exportSecurityStatus($row));
            $sheet->setCellValue('F' . $rowIndex, $this->exportSecurityRemark($row));
            $sheet->getStyle('A' . $rowIndex . ':H' . $rowIndex)->applyFromArray([
                'font' => [
                    'size' => 10,
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => 'thin',
                        'color' => ['rgb' => '111111'],
                    ],
                ],
            ]);
            $sheet->getStyle('F' . $rowIndex)->getAlignment()->setHorizontal('left');
            $rowIndex++;
        }

        $signatureLabelRow = $rowIndex + 1;
        $signatureTopRow = $rowIndex + 2;
        $signatureBottomRow = $rowIndex + 5;
        $nameRow = $rowIndex + 6;
        $roleRow = $rowIndex + 7;

        $sheet->mergeCells('A' . $signatureLabelRow . ':D' . $signatureLabelRow);
        $sheet->mergeCells('E' . $signatureLabelRow . ':H' . $signatureLabelRow);
        $sheet->setCellValue('E' . $signatureLabelRow, 'Mengetahui');
        $sheet->getStyle('E' . $signatureLabelRow)->getAlignment()->setHorizontal('center');

        $sheet->mergeCells('A' . $signatureTopRow . ':D' . $signatureBottomRow);
        $sheet->mergeCells('E' . $signatureTopRow . ':H' . $signatureBottomRow);
        $sheet->mergeCells('A' . $nameRow . ':D' . $nameRow);
        $sheet->mergeCells('E' . $nameRow . ':H' . $nameRow);
        $sheet->mergeCells('A' . $roleRow . ':D' . $roleRow);
        $sheet->mergeCells('E' . $roleRow . ':H' . $roleRow);

        $sheet->setCellValue('A' . $nameRow, (string) ($group['name'] ?? '-'));
        $sheet->setCellValue('A' . $roleRow, 'ANGGOTA SATPAM');
        $sheet->setCellValue('E' . $roleRow, 'KOMANDAN REGU');

        $sheet->getStyle('A' . $signatureTopRow . ':H' . $roleRow)->applyFromArray([
            'font' => [
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => '111111'],
                ],
            ],
        ]);
    }

    private function buildSecuritySheetTitle(string $pin, string $name, int $sheetIndex): string
    {
        $base = trim($pin !== '' ? $pin : $name);
        if ($base === '') {
            $base = 'Security';
        }

        $base = preg_replace('/[\\\\\\/\\?\\*\\:\\[\\]]+/', ' ', $base) ?: 'Security';
        $base = trim($base);

        if ($sheetIndex > 0) {
            $suffix = ' ' . ($sheetIndex + 1);
            $base = mb_substr($base, 0, 31 - mb_strlen($suffix)) . $suffix;
        } else {
            $base = mb_substr($base, 0, 31);
        }

        return $base;
    }

    private function buildSecurityExportMonthLabel(?string $dateFrom, ?string $dateTo, array $dateColumns): string
    {
        try {
            if ($dateFrom !== null) {
                return Carbon::parse($dateFrom)->locale('id')->translatedFormat('F Y');
            }

            if (!empty($dateColumns)) {
                return Carbon::parse((string) $dateColumns[0])->locale('id')->translatedFormat('F Y');
            }

            if ($dateTo !== null) {
                return Carbon::parse($dateTo)->locale('id')->translatedFormat('F Y');
            }
        } catch (\Throwable $e) {
            // Ignore and fallback below.
        }

        return now()->locale('id')->translatedFormat('F Y');
    }

    private function exportDayNameShortId(string $logDate): string
    {
        if ($logDate === '') {
            return '-';
        }

        try {
            return ucfirst(Carbon::parse($logDate)->locale('id')->translatedFormat('l'));
        } catch (\Throwable $e) {
            return '-';
        }
    }

    private function exportSecurityTimeCell($row, string $field): string
    {
        if (!is_array($row)) {
            return '';
        }

        $value = $this->exportTimeOnly($row[$field] ?? null);

        return $value === '-' ? '' : $value;
    }

    private function exportSecurityRemark($row): string
    {
        return '';
    }

    private function exportSecurityStatus($row): string
    {
        if (!is_array($row)) {
            return '';
        }

        return trim((string) ($row['expected'] ?? ''));
    }

    private function buildExportDateColumns(Collection $rows, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        try {
            if ($dateFrom !== null && $dateTo !== null) {
                $start = Carbon::parse($dateFrom)->startOfDay();
                $end = Carbon::parse($dateTo)->startOfDay();
            } else {
                $dates = $rows
                    ->pluck('log_date')
                    ->map(fn($date) => $this->toDateString($date))
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();

                if ($dates->isEmpty()) {
                    return [];
                }

                $start = Carbon::parse((string) $dates->first());
                $end = Carbon::parse((string) $dates->last());
            }

            $result = [];
            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                $result[] = $cursor->format('Y-m-d');
            }

            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function buildAttendanceExportTitle(?string $dateFrom, ?string $dateTo, Collection $rows): string
    {
        $periodLabel = 'Semua Periode';

        try {
            if ($dateFrom !== null && $dateTo !== null) {
                $start = Carbon::parse($dateFrom);
                $end = Carbon::parse($dateTo);
                $periodLabel = $start->isSameDay($end)
                    ? $start->locale('id')->translatedFormat('d F Y')
                    : $start->locale('id')->translatedFormat('d F Y') . ' s/d ' . $end->locale('id')->translatedFormat('d F Y');
            } else {
                $firstDate = $rows
                    ->pluck('log_date')
                    ->map(fn($date) => $this->toDateString($date))
                    ->filter()
                    ->sort()
                    ->first();

                if ($firstDate) {
                    $parsed = Carbon::parse($firstDate);
                    $periodLabel = $parsed->locale('id')->translatedFormat('F Y');
                }
            }
        } catch (\Throwable $e) {
            // Use defaults above.
        }

        return sprintf('ABSENSI %s Golden Multi Indotama', $periodLabel);
    }

    private function exportDayShortName(string $logDate): string
    {
        if ($logDate === '') {
            return '-';
        }

        try {
            return Carbon::parse($logDate)->locale('id')->translatedFormat('D');
        } catch (\Throwable $e) {
            return '-';
        }
    }

    private function exportScanSummaryCell($row): string
    {
        if (!is_array($row)) {
            return '';
        }

        $firstScan = $this->exportTimeOnly($row['first_scan'] ?? null);
        $lastScan = $this->exportTimeOnly($row['last_scan'] ?? null);
        $expected = trim((string) ($row['expected'] ?? ''));
        $lines = [];

        if ($firstScan !== '-') {
            $lines[] = $firstScan;
        }

        if ($lastScan !== '-') {
            $lines[] = $lastScan;
        }

        if ($expected !== '') {
            $lines[] = strtolower($expected) === 'libur nasional' ? 'Libur Nasional' : $expected;
        }

        return implode("\n", $lines);
    }

    private function exportCellCategory(array $row): string
    {
        $expected = strtolower(trim((string) ($row['expected'] ?? '')));

        if ($expected === 'terlambat') {
            return 'late';
        }

        if ($expected === 'tidak masuk') {
            return 'danger';
        }

        return 'normal';
    }

    private function shouldExcludeFromAttendanceExport(string $pin): bool
    {
        return false;
    }

    private function exportTimeOnly($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable $e) {
            return '-';
        }
    }

    private function exportSchedule($start, $end): string
    {
        $startTime = $this->normalizeTime($start);
        $endTime = $this->normalizeTime($end);

        if ($startTime === null || $endTime === null) {
            return '-';
        }

        return substr($startTime, 0, 5) . ' - ' . substr($endTime, 0, 5);
    }

    private function appendAttendanceStatusLegendSheet(Spreadsheet $spreadsheet, Collection $rows): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Keterangan Status');

        $sheet->setCellValue('A1', 'Status Attendance Log');
        $sheet->setCellValue('A2', 'Kode Status');
        $sheet->setCellValue('B2', 'Label');
        $sheet->setCellValue('C2', 'Keterangan');

        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '111827'],
            ],
        ]);

        $sheet->getStyle('A2:C2')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '111827'],
            ],
            'fill' => [
                'fillType' => 'solid',
                'color' => ['rgb' => 'E5E7EB'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => '9CA3AF'],
                ],
            ],
        ]);

        $statusMeta = [
            'matched' => ['label' => 'On Time', 'description' => 'Scan masuk sesuai jadwal kerja.'],
            'time_mismatch' => ['label' => 'Terlambat', 'description' => 'Jam scan masuk tidak sesuai jadwal kerja.'],
            'missing_scan' => ['label' => 'Tidak Masuk', 'description' => 'Tidak ada scan masuk maupun scan pulang pada hari kerja.'],
            'missing_checkin' => ['label' => 'Tidak Scan masuk', 'description' => 'Hanya ada scan pulang, scan masuk tidak ditemukan.'],
            'missing_checkout' => ['label' => 'Tidak Scan pulang', 'description' => 'Hanya ada scan masuk, scan pulang tidak ditemukan.'],
            'offday' => ['label' => 'OFF', 'description' => 'Hari OFF sesuai roster atau aturan default.'],
            'holiday_national' => ['label' => 'Libur Nasional', 'description' => 'Tanggal termasuk hari libur nasional.'],
            'check_again' => ['label' => 'Cek Lagi', 'description' => 'Data perlu dicek lagi karena kondisi scan atau jadwal tidak normal.'],
            'izin' => ['label' => 'Izin', 'description' => 'Izin yang sudah disetujui.'],
            'sakit' => ['label' => 'Sakit', 'description' => 'Sakit yang sudah disetujui.'],
            'cuti' => ['label' => 'Cuti', 'description' => 'Cuti yang sudah disetujui.'],
            'dinas_luar' => ['label' => 'Dinas Luar', 'description' => 'Dinas luar yang sudah disetujui.'],
            'no_roster' => ['label' => 'Tanpa Roster', 'description' => 'Tidak ada roster, sehingga evaluasi memakai aturan default atau perlu pengecekan.'],
        ];

        $usedStatuses = $rows
            ->pluck('status')
            ->map(fn($status) => strtolower(trim((string) $status)))
            ->filter()
            ->unique()
            ->values();

        $rowIndex = 3;
        foreach ($usedStatuses as $status) {
            $matchedRow = $rows->first(function (array $row) use ($status) {
                return strtolower(trim((string) ($row['status'] ?? ''))) === $status;
            });
            $meta = $statusMeta[$status] ?? [
                'label' => trim((string) ($matchedRow['expected'] ?? '')) ?: $status,
                'description' => 'Status attendance yang digunakan pada data export.',
            ];

            $sheet->setCellValue('A' . $rowIndex, $status);
            $sheet->setCellValue('B' . $rowIndex, $meta['label']);
            $sheet->setCellValue('C' . $rowIndex, $meta['description']);
            $rowIndex++;
        }

        $lastRow = max(2, $rowIndex - 1);
        $sheet->getStyle('A2:C' . $lastRow)->applyFromArray([
            'alignment' => [
                'vertical' => 'top',
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(60);
    }

    private function exportDayName(string $logDate): string
    {
        if ($logDate === '') {
            return '-';
        }

        try {
            return Carbon::parse($logDate)->locale('id')->translatedFormat('l');
        } catch (\Throwable $e) {
            return '-';
        }
    }

    private function hasOvertime(
        ?string $logDate,
        ?string $startTime,
        ?string $endTime,
        ?string $firstScan,
        ?string $lastScan,
        bool $isOff = false,
        bool $isNationalHoliday = false
    ): bool
    {
        return $this->overtimeMinutes($logDate, $startTime, $endTime, $firstScan, $lastScan, $isOff, $isNationalHoliday) > 0;
    }

    private function overtimeLabel(
        ?string $logDate,
        ?string $startTime,
        ?string $endTime,
        ?string $firstScan,
        ?string $lastScan,
        bool $isOff = false,
        bool $isNationalHoliday = false
    ): string
    {
        $minutes = $this->overtimeMinutes($logDate, $startTime, $endTime, $firstScan, $lastScan, $isOff, $isNationalHoliday);
        if ($minutes <= 0) {
            return '-';
        }

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function overtimeMinutes(
        ?string $logDate,
        ?string $startTime,
        ?string $endTime,
        ?string $firstScan,
        ?string $lastScan,
        bool $isOff = false,
        bool $isNationalHoliday = false
    ): int
    {
        if ($isOff || $logDate === null || $lastScan === null) {
            return 0;
        }

        try {
            if ($isNationalHoliday && $startTime !== null && $endTime !== null && $firstScan !== null) {
                $firstScanAt = Carbon::parse($firstScan);
                $lastScanAt = Carbon::parse($lastScan);
                if ($lastScanAt->lessThan($firstScanAt)) {
                    $lastScanAt->addDay();
                }

                return max(0, (int) $firstScanAt->diffInMinutes($lastScanAt, false));
            }

            if ($endTime === null) {
                return 0;
            }

            $scheduledEnd = Carbon::parse($logDate . ' ' . $endTime);
            if ($this->isOvernightShift($startTime, $endTime)) {
                $scheduledEnd->addDay();
            }

            $lastScanAt = Carbon::parse($lastScan);
            if ($startTime !== null) {
                $startAt = Carbon::parse($logDate . ' ' . $startTime);
                if ($lastScanAt->lessThan($startAt)) {
                    $lastScanAt->addDay();
                }
            }
            $minutes = (int) $scheduledEnd
                ->diffInMinutes($lastScanAt, false);

            return max(0, $minutes);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function canManageCorrections($user): bool
    {
        return $this->accessRules()->allows($user, self::ACCESS_MODULE, 'manage_corrections');
    }

    private function evaluateCheckIn(?string $startTime, ?string $firstScanTime, ?string $firstScanDateTime = null, ?string $logDate = null): array
    {
        if ($startTime === null || $firstScanTime === null) {
            return [null, null];
        }

        try {
            if ($firstScanDateTime !== null && $logDate !== null) {
                $start = Carbon::parse($logDate . ' ' . $startTime);
                $scan = Carbon::parse($firstScanDateTime);
            } else {
                $start = Carbon::parse('2000-01-01 ' . $startTime);
                $scan = Carbon::parse('2000-01-01 ' . $firstScanTime);
            }
            $diffMinutes = (int) floor(($scan->getTimestamp() - $start->getTimestamp()) / 60);
            $scanHm = substr($firstScanTime, 0, 5);
            $startHm = substr($startTime, 0, 5);

            if ($diffMinutes > 10) {
                return [
                    'Terlambat',
                    "{$scanHm} masuk telat. Telat jika lebih dari 10 menit dari jadwal {$startHm}.",
                ];
            }

            if ($diffMinutes > 0) {
                return [
                    'On Time',
                    "{$scanHm} masuk telat tapi masih dalam toleransi 10 menit dari jadwal {$startHm}.",
                ];
            }

            if ($diffMinutes < 0) {
                return [
                    'On Time',
                    "{$scanHm} masuk lebih cepat dari jadwal {$startHm}.",
                ];
            }

            return ['On Time', "Jam masuk sesuai jadwal {$startHm}."];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }
}
