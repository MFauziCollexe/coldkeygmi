<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\StockCardUnit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockCardUnitController extends Controller
{
    use RemembersIndexUrl;

    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'stock-card-units');

        $query = StockCardUnit::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active') === '1');
        }

        return Inertia::render('MasterData/StockCardUnit/Index', [
            'units' => $query->orderBy('name')->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'is_active', 'page']),
        ]);
    }

    public function create()
    {
        return Inertia::render('MasterData/StockCardUnit/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50|unique:stock_card_units,name',
            'is_active' => 'boolean',
        ]);

        StockCardUnit::create([
            'name' => trim((string) $data['name']),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return $this->redirectToRememberedIndex($request, 'stock-card-units', 'stock-card-units.index')
            ->with('success', 'Satuan berhasil dibuat.');
    }

    public function edit(StockCardUnit $stockCardUnit)
    {
        return Inertia::render('MasterData/StockCardUnit/Edit', [
            'unit' => $stockCardUnit,
        ]);
    }

    public function update(Request $request, StockCardUnit $stockCardUnit)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50|unique:stock_card_units,name,' . $stockCardUnit->id,
            'is_active' => 'boolean',
        ]);

        $stockCardUnit->update([
            'name' => trim((string) $data['name']),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return $this->redirectToRememberedIndex($request, 'stock-card-units', 'stock-card-units.index')
            ->with('success', 'Satuan berhasil diperbarui.');
    }

    public function destroy(Request $request, StockCardUnit $stockCardUnit)
    {
        $stockCardUnit->delete();

        return $this->redirectToRememberedIndex($request, 'stock-card-units', 'stock-card-units.index')
            ->with('success', 'Satuan berhasil dihapus.');
    }
}
