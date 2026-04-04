<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\StockCardItemType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockCardItemTypeController extends Controller
{
    use RemembersIndexUrl;

    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'stock-card-item-types');

        $query = StockCardItemType::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active') === '1');
        }

        return Inertia::render('MasterData/StockCardItemType/Index', [
            'itemTypes' => $query->orderBy('name')->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'is_active', 'page']),
        ]);
    }

    public function create()
    {
        return Inertia::render('MasterData/StockCardItemType/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:stock_card_item_types,name',
            'is_active' => 'boolean',
        ]);

        StockCardItemType::create([
            'name' => trim((string) $data['name']),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return $this->redirectToRememberedIndex($request, 'stock-card-item-types', 'stock-card-item-types.index')
            ->with('success', 'Jenis/Tipe barang berhasil dibuat.');
    }

    public function edit(StockCardItemType $stockCardItemType)
    {
        return Inertia::render('MasterData/StockCardItemType/Edit', [
            'itemType' => $stockCardItemType,
        ]);
    }

    public function update(Request $request, StockCardItemType $stockCardItemType)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:stock_card_item_types,name,' . $stockCardItemType->id,
            'is_active' => 'boolean',
        ]);

        $stockCardItemType->update([
            'name' => trim((string) $data['name']),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return $this->redirectToRememberedIndex($request, 'stock-card-item-types', 'stock-card-item-types.index')
            ->with('success', 'Jenis/Tipe barang berhasil diperbarui.');
    }

    public function destroy(Request $request, StockCardItemType $stockCardItemType)
    {
        $stockCardItemType->delete();

        return $this->redirectToRememberedIndex($request, 'stock-card-item-types', 'stock-card-item-types.index')
            ->with('success', 'Jenis/Tipe barang berhasil dihapus.');
    }
}
