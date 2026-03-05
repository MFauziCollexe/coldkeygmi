<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Position;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $positions = Position::with('department')->orderBy('name', 'asc')->paginate(10);

        return Inertia::render('MasterData/Position/Index', [
            'positions' => $positions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('MasterData/Position/Create', [
            'departments' => Department::active()->orderBy('name', 'asc')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:positions,code',
            'department_id' => 'required|exists:departments,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_manager' => 'boolean',
        ]);

        $position = Position::create($data);

        // Activity Log for Create
        $this->logActivity(
            'positions',
            $position->id,
            'created',
            null,
            $data,
            'Created position: ' . $data['name']
        );

        return redirect()->route('positions.index')->with('success', 'Position created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Position $position)
    {
        return Inertia::render('MasterData/Position/Edit', [
            'position' => $position->load('department'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Position $position)
    {
        return Inertia::render('MasterData/Position/Edit', [
            'position' => $position->load('department'),
            'departments' => Department::active()->orderBy('name', 'asc')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Position $position)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:positions,code,' . $position->id,
            'department_id' => 'required|exists:departments,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_manager' => 'boolean',
        ]);

        $oldData = $position->toArray();
        $position->update($data);

        // Activity Log for Update
        $this->logActivity(
            'positions',
            $position->id,
            'updated',
            $oldData,
            $data,
            'Updated position: ' . $data['name']
        );

        return redirect()->route('positions.index')->with('success', 'Position updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position)
    {
        $deletedName = $position->name;
        $oldData = $position->toArray();
        $position->delete();

        // Activity Log for Delete
        $this->logActivity(
            'positions',
            null,
            'deleted',
            $oldData,
            null,
            'Deleted position: ' . $deletedName
        );

        return redirect()->route('positions.index')->with('success', 'Position deleted successfully');
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
