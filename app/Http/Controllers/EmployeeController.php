<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Http\Controllers\Concerns\RemembersIndexUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    use RemembersIndexUrl;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'employees');

        $search = $request->search;
        $status = $request->input('status');
        
        // Query from employees table with user relation
        $employees = Employee::with(['user.department', 'user.position', 'department', 'position'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nik', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($status, function ($query) use ($status) {
                $normalized = trim((string) $status);
                if (in_array($normalized, ['active', 'resigned'], true)) {
                    $query->where('employment_status', $normalized);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get departments and positions for filters/dropdowns
        $departments = Department::orderBy('name')->get();
        $positions = Position::with('department:id,name,code')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'department_id']);

        return Inertia::render('MasterData/Employee/Index', [
            'employees' => $employees,
            'departments' => $departments,
            'positions' => $positions,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $positions = Position::with('department:id,name,code')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'department_id']);
        
        // Get users that don't have employee records yet
        $availableUsers = User::whereDoesntHave('employee')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'account', 'department_id', 'position_id']);

        return Inertia::render('MasterData/Employee/Create', [
            'departments' => $departments,
            'positions' => $positions,
            'availableUsers' => $availableUsers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Ensure optional select inputs don't fail `exists` validation when sent as empty strings.
        $request->merge([
            'user_id' => $request->input('user_id') ?: null,
            'department_id' => $request->input('department_id') ?: null,
            'position_id' => $request->input('position_id') ?: null,
            'face_reference_photo_data' => $request->input('face_reference_photo_data') ?: null,
            'face_reference_descriptor' => $request->input('face_reference_photo_data') ? $request->input('face_reference_descriptor') : null,
        ]);

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'nik' => 'nullable|string|max:255|unique:employees,nik',
            'work_group' => 'nullable|in:office,operational',
            'join_date' => 'nullable|date',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female',
            'religion' => 'nullable|string|max:255',
            'marital_status' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
            'face_reference_photo_data' => 'nullable|string',
            'face_reference_descriptor' => 'nullable|required_with:face_reference_photo_data|array|size:128',
            'face_reference_descriptor.*' => 'numeric',
        ]);

        if (!empty($validated['face_reference_photo_data'])) {
            $validated['face_reference_photo_path'] = $this->storeFaceReferencePhoto(
                $validated['face_reference_photo_data'],
                $validated['nik'] ?? $validated['user_id'] ?? Str::random(8),
            );
            $validated['face_reference_descriptor'] = json_encode($validated['face_reference_descriptor'] ?? []);
        }

        unset($validated['face_reference_photo_data']);

        // Create employee record
        Employee::create($validated);

        $departmentId = $validated['department_id'] ?? null;
        $positionId = $validated['position_id'] ?? null;
        if (!empty($validated['user_id'])) {
            User::whereKey((int) $validated['user_id'])->update([
                'department_id' => $departmentId,
                'position_id' => $positionId,
            ]);
        }

        return $this->redirectToRememberedIndex($request, 'employees', 'employees.index')
            ->with('message', [
                'type' => 'success',
                'text' => 'Employee created successfully.',
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        $departments = Department::orderBy('name')->get();
        $positions = Position::with('department:id,name,code')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'department_id']);

        // Load user relation
        $employee->load(['user', 'user.department', 'user.position']);

        // Get available users (current user + users without employee records)
        $availableUsers = User::whereDoesntHave('employee')
            ->orWhere('id', $employee->user_id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'account', 'department_id', 'position_id']);

        return Inertia::render('MasterData/Employee/Edit', [
            'employee' => [
                ...$employee->toArray(),
                'face_reference_photo_url' => $employee->face_reference_photo_path
                    ? Storage::disk('public')->url($employee->face_reference_photo_path)
                    : null,
            ],
            'departments' => $departments,
            'positions' => $positions,
            'availableUsers' => $availableUsers,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        // Ensure optional select inputs don't fail `exists` validation when sent as empty strings.
        $request->merge([
            'user_id' => $request->input('user_id') ?: null,
            'department_id' => $request->input('department_id') ?: null,
            'position_id' => $request->input('position_id') ?: null,
            'face_reference_photo_data' => $request->input('face_reference_photo_data') ?: null,
            'face_reference_descriptor' => $request->input('face_reference_photo_data') ? $request->input('face_reference_descriptor') : null,
        ]);

        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'nik' => 'nullable|string|max:255|unique:employees,nik,' . $employee->id,
            'work_group' => 'nullable|in:office,operational',
            'join_date' => 'nullable|date',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female',
            'religion' => 'nullable|string|max:255',
            'marital_status' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
            'face_reference_photo_data' => 'nullable|string',
            'face_reference_descriptor' => 'nullable|required_with:face_reference_photo_data|array|size:128',
            'face_reference_descriptor.*' => 'numeric',
            'remove_face_reference' => 'nullable|boolean',
        ]);

        $validated = $validator->validate();

        // Avoid unintentionally clearing existing user link when user_id is left empty.
        if (array_key_exists('user_id', $validated) && $validated['user_id'] === null) {
            unset($validated['user_id']);
        }

        if (!empty($validated['remove_face_reference'])) {
            $this->deleteFaceReferencePhoto($employee->face_reference_photo_path);
            $validated['face_reference_photo_path'] = null;
            $validated['face_reference_descriptor'] = null;
        } elseif (!empty($validated['face_reference_photo_data'])) {
            $validated['face_reference_photo_path'] = $this->storeFaceReferencePhoto(
                $validated['face_reference_photo_data'],
                $validated['nik'] ?? $employee->nik ?? $employee->user_id ?? Str::random(8),
                $employee->face_reference_photo_path,
            );
            $validated['face_reference_descriptor'] = json_encode($validated['face_reference_descriptor'] ?? []);
        }

        unset($validated['face_reference_photo_data'], $validated['remove_face_reference']);

        $targetUserId = (int) (($validated['user_id'] ?? null) ?: $employee->user_id);

        // Update employee record
        $employee->update($validated);

        if ($targetUserId > 0) {
            $departmentId = $validated['department_id'] ?? null;
            $positionId = $validated['position_id'] ?? null;
            User::whereKey($targetUserId)->update([
                'department_id' => $departmentId,
                'position_id' => $positionId,
            ]);
        }

        return $this->redirectToRememberedIndex($request, 'employees', 'employees.index')
            ->with('message', [
                'type' => 'success',
                'text' => 'Employee updated successfully.',
            ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Employee $employee)
    {
        $employee->delete();

        return $this->redirectToRememberedIndex($request, 'employees', 'employees.index')
            ->with('message', [
                'type' => 'success',
                'text' => 'Employee deleted successfully.',
            ]);
    }

    public function resign(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'resigned_at' => 'nullable|date',
        ]);

        $resignedAt = $validated['resigned_at'] ?? now()->toDateString();

        DB::transaction(function () use ($employee, $resignedAt) {
            $employee->update([
                'employment_status' => 'resigned',
                'resigned_at' => $resignedAt,
            ]);
        });

        return $this->redirectToRememberedIndex($request, 'employees', 'employees.index')
            ->with('message', [
                'type' => 'success',
                'text' => 'Employee marked as resigned.',
            ]);
    }

    public function cancelResign(Request $request, Employee $employee)
    {
        DB::transaction(function () use ($employee) {
            $employee->update([
                'employment_status' => 'active',
                'resigned_at' => null,
            ]);
        });

        return $this->redirectToRememberedIndex($request, 'employees', 'employees.index')
            ->with('message', [
                'type' => 'success',
                'text' => 'Resign canceled. Employee marked as active.',
            ]);
    }

    private function storeFaceReferencePhoto(string $photoData, mixed $identifier, ?string $existingPath = null): string
    {
        if (!preg_match('/^data:image\/(?P<extension>jpeg|jpg|png);base64,(?P<data>.+)$/i', $photoData, $matches)) {
            abort(422, 'Format foto referensi wajah tidak valid.');
        }

        $binary = base64_decode($matches['data'], true);
        if ($binary === false) {
            abort(422, 'Foto referensi wajah tidak dapat diproses.');
        }

        $extension = strtolower($matches['extension']) === 'png' ? 'png' : 'jpg';
        $safeIdentifier = Str::slug((string) $identifier ?: 'employee');
        $directory = 'employee-face-references';
        $filename = $safeIdentifier . '-' . now()->format('YmdHis') . '-' . Str::random(8) . '.' . $extension;
        $path = $directory . '/' . $filename;

        if ($existingPath && Storage::disk('public')->exists($existingPath)) {
            Storage::disk('public')->delete($existingPath);
        }

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    private function deleteFaceReferencePhoto(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
