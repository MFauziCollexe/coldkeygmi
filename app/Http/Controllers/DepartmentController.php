<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $departments = Department::orderBy('name', 'asc')->paginate(10);

        return Inertia::render('MasterData/Department/Index', [
            'departments' => $departments,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('MasterData/Department/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $department = Department::create($data);

        // Activity Log for Create
        $this->logActivity(
            'departments',
            $department->id,
            'created',
            null,
            $data,
            'Created department: ' . $data['name']
        );

        return redirect()->route('departments.index')->with('success', 'Department created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department)
    {
        return Inertia::render('MasterData/Department/Edit', [
            'department' => $department->load('positions'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Department $department)
    {
        return Inertia::render('MasterData/Department/Edit', [
            'department' => $department,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code,' . $department->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $oldData = $department->toArray();
        $department->update($data);

        // Activity Log for Update
        $this->logActivity(
            'departments',
            $department->id,
            'updated',
            $oldData,
            $data,
            'Updated department: ' . $data['name']
        );

        return redirect()->route('departments.index')->with('success', 'Department updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        $deletedName = $department->name;
        $oldData = $department->toArray();
        $department->delete();

        // Activity Log for Delete
        $this->logActivity(
            'departments',
            null,
            'deleted',
            $oldData,
            null,
            'Deleted department: ' . $deletedName
        );

        return redirect()->route('departments.index')->with('success', 'Department deleted successfully');
    }

    /**
     * Helper function to log activity
     */
    private function logActivity($tableName, $recordId, $action, $oldValues, $newValues, $description)
    {
        $user = Auth::user();
        
        ActivityLog::create([
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'ip_address' => request()->ip(),
            'created_date' => now(),
            'description' => $description,
        ]);
    }
}
