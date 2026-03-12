<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\Fingerprint;

class EmployeeObserver
{
    public function deleting(Employee $employee): void
    {
        $nik = trim((string) $employee->nik);
        $name = trim((string) $employee->name);

        if ($nik === '' || $name === '') {
            return;
        }

        Fingerprint::query()
            ->where('pin', $nik)
            ->where(function ($query) {
                $query->whereNull('name')->orWhere('name', '=', '');
            })
            ->update(['name' => $name]);
    }
}

