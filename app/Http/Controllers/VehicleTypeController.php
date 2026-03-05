<?php

namespace App\Http\Controllers;

use App\Models\VehicleType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleType::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active') === '1');
        }

        $vehicleTypes = $query
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterData/VehicleType/Index', [
            'vehicleTypes' => $vehicleTypes,
            'filters' => $request->only(['search', 'is_active', 'page']),
        ]);
    }

    public function create()
    {
        return Inertia::render('MasterData/VehicleType/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:vehicle_types,name',
            'is_active' => 'boolean',
        ]);

        VehicleType::create([
            'name' => trim((string) $data['name']),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return redirect()->route('vehicle-types.index')->with('success', 'Jenis kendaraan berhasil dibuat.');
    }

    public function edit(VehicleType $vehicleType)
    {
        return Inertia::render('MasterData/VehicleType/Edit', [
            'vehicleType' => $vehicleType,
        ]);
    }

    public function update(Request $request, VehicleType $vehicleType)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:vehicle_types,name,' . $vehicleType->id,
            'is_active' => 'boolean',
        ]);

        $vehicleType->update([
            'name' => trim((string) $data['name']),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return redirect()->route('vehicle-types.index')->with('success', 'Jenis kendaraan berhasil diperbarui.');
    }

    public function destroy(VehicleType $vehicleType)
    {
        $vehicleType->delete();
        return redirect()->route('vehicle-types.index')->with('success', 'Jenis kendaraan berhasil dihapus.');
    }
}

