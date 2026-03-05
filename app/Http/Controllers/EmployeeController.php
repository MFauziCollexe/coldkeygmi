<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        
        // Query from employees table with user relation
        $employees = Employee::with(['user.department', 'user.position'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nik', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get departments and positions for filters/dropdowns
        $departments = Department::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();

        return Inertia::render('MasterData/Employee/Index', [
            'employees' => $employees,
            'departments' => $departments,
            'positions' => $positions,
            'filters' => [
                'search' => $search
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();
        
        // Get users that don't have employee records yet
        $availableUsers = User::whereDoesntHave('employee')
            ->orderBy('name')
            ->get();

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
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
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
        ]);

        // Create employee record
        Employee::create($validated);

        return redirect('/master-data/employee')
            ->with('message', [
                'type' => 'success',
                'text' => 'Employee created successfully.'
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        $departments = Department::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();

        // Load user relation
        $employee->load('user');

        // Get available users (current user + users without employee records)
        $availableUsers = User::whereDoesntHave('employee')
            ->orWhere('id', $employee->user_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('MasterData/Employee/Edit', [
            'employee' => $employee,
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
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
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
        ]);

        // Update employee record
        $employee->update($validated);

        return redirect('/master-data/employee')
            ->with('message', [
                'type' => 'success',
                'text' => 'Employee updated successfully.'
            ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect('/master-data/employee')
            ->with('message', [
                'type' => 'success',
                'text' => 'Employee deleted successfully.'
            ]);
    }
}
