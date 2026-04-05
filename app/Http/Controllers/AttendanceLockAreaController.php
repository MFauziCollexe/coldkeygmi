<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\AttendanceLockArea;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceLockAreaController extends Controller
{
    use RemembersIndexUrl;

    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'attendance_lock_areas');

        $search = trim((string) $request->input('search', ''));

        $areas = AttendanceLockArea::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('MasterData/AttendanceLockArea/Index', [
            'areas' => $areas,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('MasterData/AttendanceLockArea/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:10000'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        AttendanceLockArea::create([
            ...$validated,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'created_by' => optional($request->user())->id,
            'updated_by' => optional($request->user())->id,
        ]);

        return $this->redirectToRememberedIndex($request, 'attendance_lock_areas', 'attendance-lock-areas.index')
            ->with('message', [
                'type' => 'success',
                'text' => 'Area absensi berhasil ditambahkan.',
            ]);
    }

    public function edit(AttendanceLockArea $attendanceLockArea)
    {
        return Inertia::render('MasterData/AttendanceLockArea/Edit', [
            'area' => $attendanceLockArea,
        ]);
    }

    public function update(Request $request, AttendanceLockArea $attendanceLockArea)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:10000'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $attendanceLockArea->update([
            ...$validated,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'updated_by' => optional($request->user())->id,
        ]);

        return $this->redirectToRememberedIndex($request, 'attendance_lock_areas', 'attendance-lock-areas.index')
            ->with('message', [
                'type' => 'success',
                'text' => 'Area absensi berhasil diperbarui.',
            ]);
    }

    public function destroy(Request $request, AttendanceLockArea $attendanceLockArea)
    {
        $attendanceLockArea->delete();

        return $this->redirectToRememberedIndex($request, 'attendance_lock_areas', 'attendance-lock-areas.index')
            ->with('message', [
                'type' => 'success',
                'text' => 'Area absensi berhasil dihapus.',
            ]);
    }
}
