<?php

namespace App\Http\Controllers;

use App\Models\AttendanceHoliday;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TheDaysController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $holidays = AttendanceHoliday::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('name', 'like', "%{$q}%")
                        ->orWhere('holiday_date', 'like', "%{$q}%");
                });
            })
            ->orderBy('holiday_date', 'desc')
            ->orderBy('scope_type')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('GMIHR/TheDays/Index', [
            'holidays' => $holidays,
            'filters' => [
                'q' => $q,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'holiday_date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:150'],
            'scope_type' => ['required', 'in:all,office,operational'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $exists = AttendanceHoliday::whereDate('holiday_date', $validated['holiday_date'])
            ->where('scope_type', $validated['scope_type'])
            ->exists();
        if ($exists) {
            return back()->withErrors([
                'holiday_date' => 'Tanggal libur dengan cakupan ini sudah ada di daftar.',
            ]);
        }

        AttendanceHoliday::create([
            'holiday_date' => $validated['holiday_date'],
            'name' => $validated['name'],
            'scope_type' => $validated['scope_type'],
            'notes' => $validated['notes'] ?? null,
            'is_national' => true,
            'created_by' => optional($request->user())->id,
        ]);

        return back()->with('success', 'Hari libur nasional berhasil ditambahkan.');
    }

    public function destroy(AttendanceHoliday $theDay)
    {
        $theDay->delete();

        return back()->with('success', 'Hari libur berhasil dihapus.');
    }
}
